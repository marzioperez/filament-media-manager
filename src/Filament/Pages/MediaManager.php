<?php

namespace Marzio\MediaManager\Filament\Pages;

use Marzio\MediaManager\Support\UploadContext;
use Marzio\MediaManager\Vault\VaultResolver;
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

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(as: 'sort', history: true)]
    public string $sort = 'latest';

    public ?int $currentFolderId = null;

    public function mount(): void {
        // Asegura que el vault exista (crea MediaVault id=1 si aún no
        // hay resolver custom; si lo hay, normalmente el modelo ya existe).
        VaultResolver::model();
    }

    public function getMediaQueryProperty() {
        return Media::query()
            ->where('model_type', VaultResolver::modelType())
            ->where('model_id', VaultResolver::modelId())
            ->when($this->currentFolderId, fn($q)=>$q->where('media_folder_id', $this->currentFolderId))
            ->latest();
    }

    #[On('upload-finished')]
    public function saveUploads(array $uploads): void {
        $vault = VaultResolver::model();

        // Comunica la carpeta destino al PathGenerator, de modo que el fichero
        // se copie directamente dentro del prefijo de la carpeta en S3.
        UploadContext::set($this->currentFolderId);

        try {
            foreach ($uploads as $item) {
                $original = $item['original'] ?? basename($item['path']);

                $media = $vault
                    ->addMediaFromDisk($item['path'], $item['disk'])
                    ->usingFileName($original)                           // nombre exacto del archivo en S3
                    ->usingName(pathinfo($original, PATHINFO_FILENAME))  // columna 'name'
                    ->toMediaCollection(config('media-manager.collection', 'assets'), config('media-manager.disk', 'media-manager'));

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
    }

    #[On('set-folder')]
    public function setFolder(?int $id = null): void {
        $this->currentFolderId = $id;
        $this->dispatch('folder-changed');
    }

}
