<?php

namespace Marzio\MediaManager\Filament\Pages;

use Marzio\MediaManager\Models\MediaVault;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaManager extends Page {

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-photo';
    protected static string|null|\UnitEnum $navigationGroup = 'Media';
    protected static ?string $title = 'Gestor de Medios';
    protected string $view = 'media-manager::filament.pages.media-manager';
    protected static ?int $navigationSort = 20;

    #[\App\Filament\Pages\Url(as: 'q', history: true)]
    public string $search = '';

    #[\App\Filament\Pages\Url(as: 'sort', history: true)]
    public string $sort = 'latest';

    public ?int $currentFolderId = null;

    public function mount(): void {
        MediaVault::firstOrCreate(['id' => 1]);
    }

    public function getMediaQueryProperty() {
        return Media::query()
            ->where('model_type', MediaVault::class)
            ->where('model_id', 1)
            ->when($this->currentFolderId, fn($q)=>$q->where('media_folder_id', $this->currentFolderId))
            ->latest();
    }

    #[On('upload-finished')]
    public function saveUploads(array $uploads): void {
        $vault = MediaVault::findOrFail(1);

        foreach ($uploads as $item) {
            $original = $item['original'] ?? basename($item['path']);

            $media = $vault
                ->addMediaFromDisk($item['path'], $item['disk'])
                ->usingFileName($original)                           // nombre exacto del archivo en S3
                ->usingName(pathinfo($original, PATHINFO_FILENAME))  // columna 'name'
                ->toMediaCollection('assets', 'media-manager');

            // $media->media_folder_id = $this->currentFolderId;
            $media->save();

            Storage::disk($item['disk'])->delete($item['path']);
        }

        $this->dispatch('refresh-media-grid');
    }

    #[On('set-folder')]
    public function setFolder(?int $id = null): void {
        $this->currentFolderId = $id;
        $this->dispatch('folder-changed');
    }

}
