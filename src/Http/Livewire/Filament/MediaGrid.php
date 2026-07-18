<?php

namespace Marzio\MediaManager\Http\Livewire\Filament;

use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Marzio\MediaManager\Http\Livewire\Concerns\PollsPendingConversions;
use Marzio\MediaManager\Jobs\UpdateMediaUrlReferencesJob;
use Marzio\MediaManager\Models\MediaFolder;
use Marzio\MediaManager\Support\MediaFolderCopier;
use Marzio\MediaManager\Support\MediaFolderMover;
use Marzio\MediaManager\Vault\VaultResolver;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaGrid extends Component {

    use WithPagination, PollsPendingConversions;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    public string $sort   = 'latest';
    public ?int $currentFolderId = null;

    /** uuids seleccionados en la grid (checkboxes). */
    public array $selected = [];

    /** Navegación dentro del modal de "elegir carpeta destino". */
    public ?int $folderPickerFolderId = null;
    public string $folderPickerMode = 'move'; // 'move' | 'copy'
    public array $folderPickerUuids = [];

    /** Pares [old, new] de URLs pendientes de reindexar tras un "mover". */
    public array $pendingUrlReindex = [];

    protected $listeners = ['folder-changed' => '$refresh', 'refresh-media-grid' => '$refresh'];

    public function updatingSearch() {
        $this->resetPage();
    }

    #[On('media-search')]
    public function handleMediaSearch($q): void {
        $this->search = $q;
        $this->resetPage();
    }

    public function getItemsProperty() {
        return Media::query()
            ->where('model_type', VaultResolver::modelType())
            ->where('model_id', VaultResolver::modelId())
            // Muestra solo los medios de la carpeta actual (raíz = sin carpeta).
            ->where('media_folder_id', $this->currentFolderId)
            ->when($this->search, fn($q) => $q->where(function($qq){
                $s = "%".$this->search."%";
                $qq->where('file_name','like',$s)->orWhere('mime_type','like',$s);
            }))
            ->latest()->paginate(12);
    }

    public function toggleSelected(string $uuid): void {
        if (in_array($uuid, $this->selected, true)) {
            $this->selected = array_values(array_diff($this->selected, [$uuid]));
        } else {
            $this->selected[] = $uuid;
        }
    }

    public function clearSelected(): void {
        $this->selected = [];
    }

    public function deleteSelected(int $id): void {
        if ($m = Media::find($id)) {
            $m->delete();
        }
        $this->resetPage();
    }

    public function deleteSelectedBulk(): void {
        if (! count($this->selected)) {
            return;
        }

        Media::query()->whereIn('uuid', $this->selected)->get()->each->delete();
        $this->reset('selected');
        $this->resetPage();
        session()->flash('success', 'Archivos eliminados.');
    }

    /*
    |--------------------------------------------------------------------------
    | Selector de carpeta destino (mover / copiar)
    |--------------------------------------------------------------------------
    */

    /** Subcarpetas del punto donde está el selector, dentro del vault activo. */
    public function getFolderPickerFoldersProperty() {
        return MediaFolder::query()
            ->where('model_type', VaultResolver::modelType())
            ->where('model_id', VaultResolver::modelId())
            ->where('parent_id', $this->folderPickerFolderId)
            ->orderBy('name')
            ->get();
    }

    public function getFolderPickerCurrentFolderProperty(): ?MediaFolder {
        return $this->folderPickerFolderId ? MediaFolder::find($this->folderPickerFolderId) : null;
    }

    public function getFolderPickerBreadcrumbsProperty() {
        return $this->folderPickerCurrentFolder?->breadcrumbs() ?? collect();
    }

    public function openFolderPickerFolder(int $id): void {
        $this->folderPickerFolderId = $id;
    }

    public function goToFolderPickerFolder(?int $id = null): void {
        $this->folderPickerFolderId = $id;
    }

    /** Abre el selector de carpeta para mover/copiar un único recurso (panel de detalle). */
    public function openFolderPickerFor(string $mode, string $uuid): void {
        $this->folderPickerMode    = $mode === 'copy' ? 'copy' : 'move';
        $this->folderPickerUuids   = [$uuid];
        $this->folderPickerFolderId = null;
        $this->dispatch('open-modal', id: 'media-manager-folder-picker');
    }

    /** Abre el selector de carpeta para mover/copiar la selección múltiple. */
    public function openFolderPickerForSelected(string $mode): void {
        if (! count($this->selected)) {
            return;
        }

        $this->folderPickerMode     = $mode === 'copy' ? 'copy' : 'move';
        $this->folderPickerUuids    = $this->selected;
        $this->folderPickerFolderId = null;
        $this->dispatch('open-modal', id: 'media-manager-folder-picker');
    }

    public function confirmFolderPicker(): void {
        if (empty($this->folderPickerUuids)) {
            return;
        }

        $mover  = new MediaFolderMover();
        $copier = new MediaFolderCopier();

        $conversionName = config('media-manager.full_conversion', 'webp');
        $reindexEnabled = config('media-manager.url_reference_scan.enabled', false)
            && ! empty(config('media-manager.url_reference_scan.targets', []));

        $target = $this->folderPickerFolderId;
        $items  = Media::query()->whereIn('uuid', $this->folderPickerUuids)->get();

        foreach ($items as $media) {
            if ($this->folderPickerMode === 'copy') {
                $copier->copy($media, $target);
                continue;
            }

            $urlsBefore = $this->trackedUrls($media, $conversionName);
            $mover->move($media, $target);
            $media->refresh();
            $urlsAfter = $this->trackedUrls($media, $conversionName);

            if ($reindexEnabled) {
                foreach ($urlsBefore as $key => $oldUrl) {
                    $newUrl = $urlsAfter[$key] ?? null;

                    if ($oldUrl && $newUrl && $oldUrl !== $newUrl) {
                        $this->pendingUrlReindex[] = ['old' => $oldUrl, 'new' => $newUrl];
                    }
                }
            }
        }

        $verb = $this->folderPickerMode === 'copy' ? 'copiados' : 'movidos';

        $this->reset(['selected', 'folderPickerUuids', 'folderPickerFolderId']);
        $this->dispatch('close-modal', id: 'media-manager-folder-picker');
        $this->dispatch('refresh-media-grid');
        session()->flash('success', "Archivos {$verb}.");

        if (! empty($this->pendingUrlReindex)) {
            $this->dispatch('open-modal', id: 'media-manager-reindex-confirm');
        }
    }

    /**
     * @return array{original: ?string, conversion: ?string}
     */
    protected function trackedUrls(Media $media, string $conversionName): array {
        try {
            return [
                'original'   => $media->getUrl(),
                'conversion' => $media->hasGeneratedConversion($conversionName) ? $media->getUrl($conversionName) : null,
            ];
        } catch (\Throwable $e) {
            return ['original' => null, 'conversion' => null];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Reindexado de referencias de URL (opt-in, tras mover)
    |--------------------------------------------------------------------------
    */

    public function confirmReindex(): void {
        foreach ($this->pendingUrlReindex as $pair) {
            UpdateMediaUrlReferencesJob::dispatch($pair['old'], $pair['new']);
        }

        $this->pendingUrlReindex = [];
        $this->dispatch('close-modal', id: 'media-manager-reindex-confirm');
        session()->flash('success', 'Actualización de referencias en cola.');
    }

    public function skipReindex(): void {
        $this->pendingUrlReindex = [];
        $this->dispatch('close-modal', id: 'media-manager-reindex-confirm');
    }

    public function render() {
        $items = $this->items;

        return view('media-manager::livewire.filament.media-grid', [
            'media' => $items,
            'hasPendingConversions' => $this->mediaHasPendingConversions($items),
        ]);
    }

}
