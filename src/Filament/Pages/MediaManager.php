<?php

namespace Marzio\MediaManager\Filament\Pages;

use Marzio\MediaManager\Models\MediaFolder;
use Marzio\MediaManager\Support\UniqueFileNamer;
use Marzio\MediaManager\Support\UploadContext;
use Marzio\MediaManager\Vault\VaultResolver;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaManager extends Page {

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-photo';
    protected static string|null|\UnitEnum $navigationGroup = 'Media';
    protected static ?string $title = 'Gestor de Medios';
    protected string $view = 'media-manager::filament.pages.media-manager';
    protected static ?int $navigationSort = 20;

    #[Url(as: 'folder', history: true)]
    public ?int $currentFolderId = null;

    public function mount(): void {
        // Asegura que el vault exista (crea MediaVault id=1 si aún no
        // hay resolver custom; si lo hay, normalmente el modelo ya existe).
        VaultResolver::model();

        // El id de carpeta puede venir de la URL (deep link / recarga). Si no
        // existe o no pertenece al vault actual, volvemos a la raíz en vez de
        // dejar un id inválido que rompería el filtrado del grid.
        if ($this->currentFolderId !== null) {
            $exists = MediaFolder::query()
                ->where('model_type', VaultResolver::modelType())
                ->where('model_id', VaultResolver::modelId())
                ->whereKey($this->currentFolderId)
                ->exists();

            if (! $exists) {
                $this->currentFolderId = null;
            }
        }
    }

    /**
     * Botón "Cargar recursos" junto al título de la página. Abre un modal con
     * el uploader masivo.
     */
    protected function getHeaderActions(): array {
        return [
            Action::make('upload')
                ->label('Cargar recursos')
                ->icon('heroicon-m-arrow-up-tray')
                ->color('primary')
                ->modalHeading('Cargar recursos')
                ->modalDescription(fn () => $this->currentFolderId
                    ? 'Los archivos se subirán a la carpeta actual.'
                    : 'Los archivos se subirán a la raíz.')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Cerrar')
                ->modalContent(fn () => view('media-manager::filament.pages.partials.uploader-modal', [
                    'currentFolderId' => $this->currentFolderId,
                ])),
        ];
    }

    #[On('upload-finished')]
    public function saveUploads(array $uploads): void {
        $vault     = VaultResolver::model();
        $mediaDisk = config('media-manager.disk', 'media-manager');

        $folder = $this->currentFolderId ? MediaFolder::find($this->currentFolderId) : null;
        $dir    = $folder ? trim($folder->path, '/') : '';

        // Comunica la carpeta destino al PathGenerator, de modo que el fichero
        // se copie directamente dentro del prefijo de la carpeta en S3.
        UploadContext::set($this->currentFolderId);

        try {
            foreach ($uploads as $item) {
                $original = $item['original'] ?? basename($item['path']);

                // Los ficheros conviven sin subcarpeta numérica, así que
                // garantizamos un nombre único dentro de la carpeta (o la raíz).
                $original = UniqueFileNamer::forDisk($mediaDisk, $dir, $original);

                $media = $vault
                    ->addMediaFromDisk($item['path'], $item['disk'])
                    ->usingFileName($original)                           // nombre exacto del archivo en S3
                    ->usingName(pathinfo($original, PATHINFO_FILENAME))  // columna 'name'
                    ->toMediaCollection(config('media-manager.collection', 'assets'), $mediaDisk);

                // Persiste la carpeta a la que pertenece el media (para el filtrado
                // del grid y para que las conversiones en cola usen la misma ruta).
                $media->media_folder_id = $this->currentFolderId;
                $media->save();

                Storage::disk($item['disk'])->delete($item['path']);
            }
        } finally {
            UploadContext::clear();
        }

        $this->dispatch('refresh-media-grid');

        // Cierra el modal del uploader (si está montado como header action).
        if (method_exists($this, 'unmountAction')) {
            $this->unmountAction();
        }
    }

    #[On('set-folder')]
    public function setFolder(?int $id = null): void {
        $this->currentFolderId = $id;
        $this->dispatch('folder-changed');
    }

}
