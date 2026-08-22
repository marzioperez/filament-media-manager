<?php

namespace Marzio\MediaManager\Http\Livewire\Concerns;

use Marzio\MediaManager\Models\MediaFolder;
use Marzio\MediaManager\Vault\VaultResolver;

/**
 * Navegación de carpetas LOCAL a un componente Livewire (a diferencia de
 * `MediaFolders`, que comunica el cambio de carpeta a la Page principal vía
 * el evento global `set-folder`). Pensado para componentes embebidos en un
 * modal (pickers de selección) donde cambiar de carpeta no debe afectar al
 * grid principal que pueda estar detrás.
 */
trait NavigatesMediaFolders {

    public ?int $currentFolderId = null;

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

    public function openFolder(int $id): void {
        $this->currentFolderId = $id;
        $this->afterFolderNavigated();
    }

    public function goToFolder(?int $id = null): void {
        $this->currentFolderId = $id;
        $this->afterFolderNavigated();
    }

    protected function afterFolderNavigated(): void {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }
}
