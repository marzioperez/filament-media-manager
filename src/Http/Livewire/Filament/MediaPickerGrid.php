<?php

namespace Marzio\MediaManager\Http\Livewire\Filament;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Marzio\MediaManager\Http\Livewire\Concerns\NavigatesMediaFolders;
use Marzio\MediaManager\Http\Livewire\Concerns\PollsPendingConversions;
use Marzio\MediaManager\Models\MediaFolder;
use Marzio\MediaManager\Support\UniqueFileNamer;
use Marzio\MediaManager\Support\UploadContext;
use Marzio\MediaManager\Vault\VaultResolver;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaPickerGrid extends Component {

    use WithPagination, WithFileUploads, PollsPendingConversions, NavigatesMediaFolders;

    public $preset = null;
    public string $hostId;
    public string $statePath;
    public $selected = null;
    public string $search = '';
    public array $pickerFiles = [];
    public bool $isUploading = false;

    public function mount($preset = null) {
        $this->applyPreset($preset);

        // Si ya hay un recurso seleccionado, abrimos el picker directamente en
        // su carpeta para que el usuario lo vea resaltado sin tener que
        // navegar manualmente hasta ahí.
        if ($preset) {
            $media = is_numeric($preset) ? Media::find($preset) : Media::where('uuid', $preset)->first();
            $this->currentFolderId = $media?->media_folder_id;
        }
    }

    protected function applyPreset($preset): void {
        if ($preset) {
            $media = Media::find($preset);
            if ($media) {
                $this->selected = $media->uuid;
            }
        }
    }

    public function updatedPreset($value): void {
        $this->applyPreset($value);
    }

    public function updatingSearch(): void {
        $this->resetPage();
    }

    public function getItemsProperty() {
        $query = Media::query()
            ->where('model_type', VaultResolver::modelType())
            ->where('model_id', VaultResolver::modelId())
            ->where('media_folder_id', $this->currentFolderId);

        // Aplicar búsqueda
        if ($this->search) {
            $query->where(function($q) {
                $s = '%' . $this->search . '%';
                $q->where('file_name', 'like', $s)
                  ->orWhere('name', 'like', $s)
                  ->orWhere('mime_type', 'like', $s);
            });
        }

        // Ordenar: preseleccionado (preset) primero solo al cargar inicialmente
        if ($this->preset && !$this->search) {
            $presetId = is_numeric($this->preset) ? $this->preset : null;
            if (!$presetId) {
                $presetMedia = Media::where('uuid', $this->preset)->first();
                $presetId = $presetMedia?->id;
            }
            if ($presetId) {
                $query->orderByRaw('id = ? DESC', [$presetId]);
            }
        }

        return $query->latest('id')->paginate(12);
    }

    public function toggle(string $uuid): void {
        $this->selected = $uuid;
    }

    public function confirm(): void {
        $ids = Media::where('uuid', $this->selected)->pluck('id', 'uuid');
        $this->dispatch('set-media-single', hostId: $this->hostId, statePath: $this->statePath, value: $ids[$this->selected]);
        $this->dispatch('close-picker');
    }

    public function updatedPickerFiles(): void {
        $this->isUploading = true;

        $vault = VaultResolver::model();
        $tmpDisk = 'private';
        $tmpDir = 'tmp-media';
        $mediaDisk = config('media-manager.disk', 'media-manager');

        $folder = $this->currentFolderId ? MediaFolder::find($this->currentFolderId) : null;
        $targetDir = $folder ? trim($folder->path, '/') : '';

        UploadContext::set($this->currentFolderId);

        try {
            foreach ($this->pickerFiles as $file) {
                $original = $file->getClientOriginalName();
                $tmpCandidate = UniqueFileNamer::forDisk($tmpDisk, $tmpDir, $original, slugify: true);
                $relative = $file->storeAs($tmpDir, $tmpCandidate, $tmpDisk);

                // Nombre final único dentro de la carpeta destino real (no el
                // directorio temporal), para no chocar con ficheros ya
                // existentes ahí.
                $finalCandidate = UniqueFileNamer::forDisk($mediaDisk, $targetDir, $tmpCandidate, slugify: true);

                $media = $vault
                    ->addMediaFromDisk($relative, $tmpDisk)
                    ->usingFileName($finalCandidate)
                    ->usingName(pathinfo($finalCandidate, PATHINFO_FILENAME))
                    ->toMediaCollection(config('media-manager.collection', 'assets'), $mediaDisk);

                $media->media_folder_id = $this->currentFolderId;
                $media->save();

                Storage::disk($tmpDisk)->delete($relative);
            }
        } finally {
            UploadContext::clear();
        }

        $this->reset('pickerFiles');
        $this->isUploading = false;
        $this->resetPage();

        Notification::make()
            ->title('Archivos cargados correctamente')
            ->success()
            ->send();

        // También refrescar la grid principal si está abierta detrás del modal
        $this->dispatch('refresh-media-grid');
    }

    public function render() {
        $items = $this->items;

        return view('media-manager::livewire.filament.media-picker-grid', [
            'media' => $items,
            'hasPendingConversions' => $this->mediaHasPendingConversions($items),
        ]);
    }

}
