<?php

namespace Marzio\MediaManager;

use Filament\Facades\Filament;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class MediaManagerServiceProvider extends ServiceProvider {
    public function boot(): void {
        // Vistas
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'media-manager');

        // Migraciones (si luego añades para MediaVault, folders, etc.)
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

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

        // Livewire components (gestor principal)
        Livewire::component('media-manager.grid', \Marzio\MediaManager\Http\Livewire\MediaManager::class);

        // Registrar custom fields de Filament
        Filament::serving(function () {
            Filament::registerFormComponents([
                'media-picker'  => \Marzio\MediaManager\Forms\Components\MediaPicker::class,
                'media-gallery' => \Marzio\MediaManager\Forms\Components\MediaGallery::class,
            ]);
        });
    }

    public function register(): void
    {
        // Config merge si usas config/media-manager.php
        $this->mergeConfigFrom(__DIR__ . '/../config/media-manager.php', 'media-manager');
    }
}