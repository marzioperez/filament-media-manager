# Filament Media Manager

Gestor multimedia para Filament PHP que permite cargar, organizar y seleccionar recursos multimedia (imágenes y videos) utilizando Spatie Media Library.

## Características

- 📁 Página de administración de medios en Filament
- 🖼️ Campo personalizado para seleccionar imágenes/videos
- 🎨 Galería de medios
- ☁️ Integración con Spatie Media Library
- 🚀 Compatible con PHP 8.4, Laravel 12, Filament 4 y Livewire 3.7

## Requisitos

- PHP ^8.4
- Laravel ^12.0
- Filament ^4.0
- Livewire ^3.7
- Spatie Laravel Media Library ^11.0

## Instalación

### 1. Instalar el paquete

```bash
composer require marzioperez/filament-media-manager:^1.0
```

### 2. Publicar archivos de configuración y migraciones

```bash
# Publicar migraciones
php artisan vendor:publish --tag=media-manager-migrations

# Publicar configuración (opcional)
php artisan vendor:publish --tag=media-manager-config

# Publicar vistas (opcional, solo si quieres personalizarlas)
php artisan vendor:publish --tag=media-manager-views

# Publicar seeder (opcional)
php artisan vendor:publish --tag=media-manager-seeders
```

### 3. Ejecutar migraciones

```bash
php artisan migrate
```

Esto creará la tabla `media_vaults` necesaria para el funcionamiento del paquete.

### 4. Configurar Spatie Media Library

Asegúrate de tener configurado Spatie Media Library en tu proyecto. Si aún no lo has hecho:

```bash
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
php artisan migrate
```

### 5. Configurar el disco de almacenamiento

En tu archivo `config/filesystems.php`, añade el disco `media-manager`:

```php
'disks' => [
    // ... otros discos

    'media-manager' => [
        'driver' => 'local',
        'root' => storage_path('app/media-manager'),
        'url' => env('APP_URL').'/storage/media-manager',
        'visibility' => 'public',
    ],
],
```

O si usas S3:

```php
'media-manager' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
    'url' => env('AWS_URL'),
],
```

### 6. Registrar el plugin en tu Panel de Filament

**IMPORTANTE:** Este es el paso clave para que aparezca la página de Gestor de Medios.

En tu `app/Providers/Filament/AdminPanelProvider.php` (o el panel que uses):

```php
use Marzio\MediaManager\Filament\Plugins\MediaManagerPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ... otras configuraciones
        ->plugins([
            MediaManagerPlugin::make(),
        ]);
}
```

### 7. (Opcional) Ejecutar el seeder para crear el MediaVault inicial

```bash
php artisan db:seed --class=MediaVaultSeeder
```

O simplemente accede a la página de Gestor de Medios y se creará automáticamente.

## Uso

### Acceder a la página de Gestor de Medios

Después de la instalación y registro del plugin, verás una nueva página en tu panel de Filament:

- **Menú:** Media > Gestor de Medios
- **Icono:** 📷 (heroicon-o-photo)

Desde esta página podrás:
- Subir imágenes y videos
- Ver todos los recursos multimedia
- Buscar y filtrar recursos
- Organizar por carpetas (opcional)

### Usar el campo MediaPicker en tus recursos

En cualquier formulario de Filament, puedes usar el campo personalizado para seleccionar imágenes:

```php
use Marzio\MediaManager\Forms\Components\MediaPicker;

public static function form(Form $form): Form
{
    return $form->schema([
        MediaPicker::make('image')
            ->label('Imagen destacada'),
    ]);
}
```

### Usar el campo MediaGallery

Para seleccionar múltiples imágenes:

```php
use Marzio\MediaManager\Forms\Components\MediaGallery;

public static function form(Form $form): Form
{
    return $form->schema([
        MediaGallery::make('gallery')
            ->label('Galería de imágenes'),
    ]);
}
```

## Configuración

El archivo de configuración `config/media-manager.php` contiene las siguientes opciones:

```php
return [
    // Disco donde se almacenarán los recursos
    'disk' => env('MEDIA_MANAGER_DISK', 'media-manager'),

    // Colección principal de MediaVault
    'collection' => 'assets',

    // Conversión para previews (thumb)
    'preview_conversion' => 'thumb',

    // Conversión principal (WebP)
    'full_conversion' => 'webp',

    // Carpeta inicial (opcional)
    'root_folder_id' => null,

    // Límite máximo de archivos por lote al subir
    'max_upload_batch' => 50,

    // ¿Procesar conversiones en cola?
    'queued_conversions' => true,
];
```

## Variables de entorno

Puedes personalizar el disco de almacenamiento en tu `.env`:

```env
MEDIA_MANAGER_DISK=media-manager
```

## Estructura del paquete

```
src/
├── Filament/
│   ├── Pages/
│   │   └── MediaManager.php          # Página principal del gestor
│   └── Plugins/
│       └── MediaManagerPlugin.php    # Plugin de Filament
├── Forms/
│   └── Components/
│       ├── MediaPicker.php           # Campo para seleccionar una imagen
│       └── MediaGallery.php          # Campo para seleccionar múltiples imágenes
├── Http/
│   └── Livewire/
│       └── Filament/
│           ├── MediaGrid.php         # Grid de medios en la página principal
│           ├── MediaPickerGrid.php   # Grid en el picker modal
│           ├── MediaGalleryPickerGrid.php
│           └── MediaBulkUploader.php # Uploader masivo
├── Models/
│   └── MediaVault.php                # Modelo principal
└── MediaManagerServiceProvider.php   # Service Provider
```

## Licencia

MIT

## Autor

Marzio Perez - marzioperez@gmail.com