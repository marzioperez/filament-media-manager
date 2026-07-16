<?php

namespace Marzio\MediaManager\Http\Livewire\Filament;

use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Marzio\MediaManager\Models\MediaFolder;
use Marzio\MediaManager\Vault\VaultResolver;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Gestión de carpetas del Media Manager.
 *
 * Muestra las subcarpetas de la carpeta actual, breadcrumbs de navegación y
 * permite crear / eliminar carpetas. Cada carpeta creada se materializa
 * físicamente en el disco (S3) configurado en `media-manager.disk`.
 *
 * La navegación se comunica a la Page mediante el evento `set-folder`.
 */
class MediaFolders extends Component {

    public ?int $currentFolderId = null;
    public string $newFolderName = '';

    protected $listeners = ['refresh-media-grid' => '$refresh'];

    /** Subcarpetas directas de la carpeta actual, dentro del vault activo. */
    public function getFoldersProperty() {
        return MediaFolder::query()
            ->where('model_type', VaultResolver::modelType())
            ->where('model_id', VaultResolver::modelId())
            ->where('parent_id', $this->currentFolderId)
            ->orderBy('name')
            ->get();
    }

    public function getCurrentFolderProperty(): ?MediaFolder {
        return $this->currentFolderId ? MediaFolder::find($this->currentFolderId) : null;
    }

    /** Cadena de ancestros para pintar los breadcrumbs. */
    public function getBreadcrumbsProperty() {
        return $this->currentFolder?->breadcrumbs() ?? collect();
    }

    public function createFolder(): void {
        $data = $this->validate([
            'newFolderName' => ['required', 'string', 'max:255'],
        ], [], ['newFolderName' => 'nombre']);

        $name = trim($data['newFolderName']);
        $slug = MediaFolder::makeSlug($name);
        $disk = config('media-manager.disk', 'media-manager');

        $parent = $this->currentFolder;
        $path   = ($parent ? trim($parent->path, '/') . '/' : '') . $slug;

        $exists = MediaFolder::query()
            ->where('model_type', VaultResolver::modelType())
            ->where('model_id', VaultResolver::modelId())
            ->where('parent_id', $this->currentFolderId)
            ->where('slug', $slug)
            ->exists();

        if ($exists) {
            $this->addError('newFolderName', 'Ya existe una carpeta con ese nombre aquí.');
            return;
        }

        $folder = new MediaFolder();
        $folder->forceFill([
            'name'       => $name,
            'slug'       => $slug,
            'path'       => $path,
            'disk'       => $disk,
            'parent_id'  => $this->currentFolderId,
            'model_type' => VaultResolver::modelType(),
            'model_id'   => VaultResolver::modelId(),
        ])->save();

        // Nota: NO creamos un objeto "placeholder" ("carpeta/") en el disco.
        // En S3 ese marcador aparece como una subcarpeta fantasma con el mismo
        // nombre. La carpeta se materializa físicamente en cuanto se sube el
        // primer archivo (su clave crea el prefijo). La carpeta existe siempre
        // de forma lógica en la base de datos.

        $this->newFolderName = '';
        $this->dispatch('close-modal', id: 'media-manager-create-folder');
        $this->dispatch('refresh-media-grid');
    }

    public function openFolder(int $id): void {
        $this->dispatch('set-folder', id: $id);
    }

    public function goToFolder(?int $id = null): void {
        $this->dispatch('set-folder', id: $id);
    }

    public function deleteFolder(int $id): void {
        $folder = MediaFolder::query()
            ->where('model_type', VaultResolver::modelType())
            ->where('model_id', VaultResolver::modelId())
            ->find($id);

        if (! $folder) {
            return;
        }

        $disk = $folder->disk ?: config('media-manager.disk', 'media-manager');
        $ids  = $this->collectSubtreeIds($folder);

        // Eliminar los medios de todo el subárbol (Spatie borra los ficheros).
        Media::query()->whereIn('media_folder_id', $ids)->get()->each->delete();

        // Eliminar las filas de carpetas del subárbol.
        MediaFolder::query()->whereIn('id', $ids)->delete();

        // Eliminar el directorio físico completo.
        try {
            Storage::disk($disk)->deleteDirectory($folder->path);
        } catch (\Throwable $e) {
            report($e);
        }

        // Si estábamos dentro del subárbol eliminado, subimos al padre.
        if (in_array($this->currentFolderId, $ids, true)) {
            $this->dispatch('set-folder', id: $folder->parent_id);
        } else {
            $this->dispatch('refresh-media-grid');
        }
    }

    /**
     * @return array<int, int>
     */
    protected function collectSubtreeIds(MediaFolder $folder): array {
        $ids      = [(int) $folder->id];
        $children = MediaFolder::where('parent_id', $folder->id)->get();

        foreach ($children as $child) {
            $ids = array_merge($ids, $this->collectSubtreeIds($child));
        }

        return $ids;
    }

    public function render() {
        return view('media-manager::livewire.filament.media-folders');
    }
}
