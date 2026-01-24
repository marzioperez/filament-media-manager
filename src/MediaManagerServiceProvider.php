<?php

namespace Marzio\MediaManager;

use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Assets\Css;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Marzio\MediaManager\Http\Livewire\Filament\MediaBulkUploader;
use Marzio\MediaManager\Http\Livewire\Filament\MediaGalleryPickerGrid;
use Marzio\MediaManager\Http\Livewire\Filament\MediaGrid;
use Marzio\MediaManager\Http\Livewire\Filament\MediaPickerGrid;

class MediaManagerServiceProvider extends ServiceProvider {

    public function boot(): void {
        // Vistas
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'media-manager');

        // Migraciones (si luego añades para MediaVault, folders, etc.)
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'media-manager-migrations');

        // Seeders opcionales (se publican)
        $this->publishes([
            __DIR__ . '/../database/seeders/MediaVaultSeeder.php' => database_path('seeders/MediaVaultSeeder.php'),
        ], 'media-manager-seeders');

        // Config opcional
        $this->publishes([
            __DIR__ . '/../config/media-manager.php' => config_path('media-manager.php'),
        ], 'media-manager-config');

        // Vistas opcionales (por si quieres overridear desde el proyecto)
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/media-manager'),
        ], 'media-manager-views');

        // CSS
        $this->publishes([
            __DIR__ . '/../resources/css' => public_path('vendor/media-manager/css'),
        ], 'media-manager-assets');

        // Registrar CSS en Filament
        FilamentAsset::register([
            Css::make('media-manager-styles', __DIR__ . '/../resources/css/media-manager.css'),
        ], 'marzio/media-manager');

        // Livewire components (gestor principal)
        Livewire::component('media-manager.media-grid', MediaGrid::class);
        Livewire::component('media-manager.media-picker-grid', MediaPickerGrid::class);
        Livewire::component('media-manager.media-gallery-picker-grid', MediaGalleryPickerGrid::class);
        Livewire::component('media-manager.media-bulk-uploader', MediaBulkUploader::class);

        // Registrar custom fields de Filament
        //
        // Filament v3: `registerFormComponents()` existe y permite aliases (p.ej. 'media-picker').
        // Filament v4: el método fue removido. Los custom fields se usan directamente vía la clase
        // (p.ej. `\Marzio\MediaManager\Forms\Components\MediaPicker::make('field')`).
        Filament::serving(function () {
            $filament = Filament::getFacadeRoot();

            if (method_exists($filament, 'registerFormComponents')) {
                Filament::registerFormComponents([
                    'media-picker'  => \Marzio\MediaManager\Forms\Components\MediaPicker::class,
                    'media-gallery' => \Marzio\MediaManager\Forms\Components\MediaGallery::class,
                ]);
            }
        });
    }

    public function register(): void {
        // Config merge si usas config/media-manager.php
        $this->mergeConfigFrom(__DIR__ . '/../config/media-manager.php', 'media-manager');
    }
}