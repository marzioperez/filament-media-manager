<?php

namespace Marzio\MediaManager;

use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Assets\Css;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Marzio\MediaManager\Http\Livewire\Filament\MediaBulkUploader;
use Marzio\MediaManager\Http\Livewire\Filament\MediaFolders;
use Marzio\MediaManager\Http\Livewire\Filament\MediaGalleryPickerGrid;
use Marzio\MediaManager\Http\Livewire\Filament\MediaGrid;
use Marzio\MediaManager\Http\Livewire\Filament\MediaPickerGrid;
use Marzio\MediaManager\Support\FolderAwareFileRemover;
use Marzio\MediaManager\Support\FolderAwarePathGenerator;
use Spatie\MediaLibrary\Support\FileRemover\DefaultFileRemover;
use Spatie\MediaLibrary\Support\PathGenerator\DefaultPathGenerator;

class MediaManagerServiceProvider extends ServiceProvider {

    /** Nombre canonico de la migracion, sin el prefijo de fecha. */
    protected const MIGRATION_NAME = 'create_media_manager_tables';

    /** Memoiza el resultado del glob sobre database/migrations. */
    protected ?string $resolvedPublishedMigration = null;

    protected bool $publishedMigrationResolved = false;

    public function boot(): void {
        // Vistas
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'media-manager');

        // Migraciones (una sola, ver registerMigrations()).
        $this->registerMigrations();

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
        Livewire::component('media-manager.media-folders', MediaFolders::class);
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

        // API programatica: facade Marzio\MediaManager\Facades\MediaManager.
        $this->app->singleton(MediaManagerService::class, fn () => new MediaManagerService());
        $this->app->alias(MediaManagerService::class, 'media-manager');

        // Activa el PathGenerator que ubica los ficheros dentro del prefijo
        // físico de su carpeta en S3. Solo se activa si el proyecto anfitrión
        // sigue usando el generador por defecto de Spatie, para no pisar una
        // configuración personalizada. Cuando un media no tiene carpeta el
        // comportamiento es idéntico al generador por defecto.
        $current = config('media-library.path_generator');

        if (empty($current) || $current === DefaultPathGenerator::class) {
            config(['media-library.path_generator' => FolderAwarePathGenerator::class]);
        }

        // FileRemover que borra el original por ruta exacta. Necesario porque,
        // al no usar subcarpeta por media, varios ficheros comparten directorio.
        // Solo se activa si el proyecto usa el remover por defecto de Spatie.
        $currentRemover = config('media-library.file_remover_class');

        if (empty($currentRemover) || $currentRemover === DefaultFileRemover::class) {
            config(['media-library.file_remover_class' => FolderAwareFileRemover::class]);
        }
    }

    /**
     * Registra la migracion del paquete.
     *
     * Publicar la copia con la fecha actual (convencion del proyecto anfitrion)
     * y autocargarla desde vendor son caminos EXCLUYENTES: si ambos estuvieran
     * activos la misma migracion correria dos veces bajo dos nombres distintos,
     * y la segunda fallaria al intentar crear tablas que ya existen.
     *
     * Por eso, cuando se detecta una copia publicada en database/migrations,
     * esa copia manda y la autocarga se desactiva.
     */
    protected function registerMigrations(): void {
        // Las migraciones solo se consultan desde artisan (y desde los tests,
        // que tambien corren en consola). Evitamos el glob en cada request web.
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../database/migrations/9999_12_31_000000_' . self::MIGRATION_NAME . '.php'
                => $this->publishedMigrationPath(),
        ], 'media-manager-migrations');

        if (is_null($this->publishedMigration())) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }

    /**
     * Ruta destino al publicar.
     *
     * Si el proyecto ya publico la migracion antes, se reutiliza ese mismo
     * nombre para que republicar ACTUALICE el archivo existente en lugar de
     * dejar dos copias con fechas distintas. Si no, se genera con la fecha
     * actual siguiendo la convencion de Laravel.
     */
    protected function publishedMigrationPath(): string {
        return $this->publishedMigration()
            ?? database_path('migrations/' . date('Y_m_d_His') . '_' . self::MIGRATION_NAME . '.php');
    }

    /**
     * Copia publicada de la migracion en el proyecto anfitrion, si existe.
     */
    protected function publishedMigration(): ?string {
        if ($this->publishedMigrationResolved) {
            return $this->resolvedPublishedMigration;
        }

        $matches = glob(database_path('migrations/*_' . self::MIGRATION_NAME . '.php'));

        $this->resolvedPublishedMigration = $matches ? $matches[0] : null;
        $this->publishedMigrationResolved = true;

        return $this->resolvedPublishedMigration;
    }
}
