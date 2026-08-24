# Filament Media Manager

Gestor multimedia para Filament PHP que permite cargar, organizar y seleccionar recursos multimedia (imágenes y videos) utilizando Spatie Media Library.

## Características

- 📁 Página de administración de medios en Filament
- 🗂️ Carpetas anidadas con creación física en el disco (S3)
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

### 2. Configurar Spatie Media Library

**Este paso va primero, y el orden importa.**

Media Manager añade la columna `media_folder_id` a la tabla `media` de Spatie,
así que esa tabla debe existir antes. Si aún no la tienes:

```bash
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
```

### 3. Publicar archivos de configuración (opcional)

```bash
# Configuración
php artisan vendor:publish --tag=media-manager-config

# Vistas (solo si quieres personalizarlas)
php artisan vendor:publish --tag=media-manager-views

# Seeder
php artisan vendor:publish --tag=media-manager-seeders
```

Las migraciones **no** necesitan publicarse: el paquete las registra por sí
mismo. Publícalas solo si necesitas editar el esquema:

```bash
php artisan vendor:publish --tag=media-manager-migrations
```

El archivo se copia a `database/migrations/` con la fecha del momento, siguiendo
la convención de tu proyecto. En cuanto existe esa copia, el paquete deja de
cargar la suya para no ejecutar la misma migración dos veces. Republicar
actualiza el archivo ya publicado en lugar de crear un duplicado con otra fecha.

### 4. Ejecutar migraciones

```bash
php artisan migrate
```

Esto crea las tablas `media_vaults` y `media_folders`, y añade la columna
`media_folder_id` a la tabla `media` de Spatie (necesaria para organizar los
archivos en carpetas).

Todo vive en una única migración, `create_media_manager_tables`, que se ejecuta
siempre al final de la cola. El motivo: la columna `media_folder_id` se añade a
una tabla de terceros que tu proyecto publica con la fecha del día, así que
cualquier fecha fija podría quedar antes y la columna nunca llegaría a crearse.
La migración además es idempotente, por lo que es segura sobre instalaciones que
ya ejecutaron las tres migraciones antiguas.

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

### Carpetas

Desde la página **Gestor de Medios** puedes organizar tus archivos en carpetas
anidadas:

- Pulsa **Nueva carpeta** para crearla dentro de la ubicación actual.
- Navega con los **breadcrumbs** (Inicio › Carpeta › Subcarpeta).
- Los archivos se guardan **directamente en la ruta de su carpeta**, sin
  subcarpeta numérica por archivo (p. ej. `documentos/facturas/factura.pdf` en
  S3). Los archivos de la raíz se guardan en la raíz del disco (`factura.pdf`).
- Las carpetas se materializan **físicamente en el disco al subir el primer
  archivo** (su clave crea el prefijo). No se crean objetos "placeholder" para
  evitar carpetas fantasma en S3; la carpeta existe siempre de forma lógica en
  la base de datos.
- Los nombres de archivo se **deduplican** dentro de cada carpeta (y de la raíz)
  añadiendo `-1`, `-2`… si hiciera falta.
- Al eliminar una carpeta se borran también sus subcarpetas, sus archivos y el
  directorio físico.

> El paquete registra automáticamente, y solo si tu proyecto usa los valores por
> defecto de Spatie (respetando cualquier personalización tuya en
> `config/media-library.php`):
>
> - un `PathGenerator` (`FolderAwarePathGenerator`) que ubica cada fichero en la
>   ruta de su carpeta sin subcarpeta numérica;
> - un `FileRemover` (`FolderAwareFileRemover`) que borra el fichero original por
>   su **ruta exacta**. Esto es necesario porque, al compartir directorio, el
>   borrador por defecto de Spatie (que localiza el original por nombre) podría
>   afectar a un fichero homónimo de otra carpeta.

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

### Cargar recursos por código

El facade `MediaManager` incorpora ficheros a la biblioteca desde seeders,
comandos, importadores o jobs, con el mismo resultado que el uploader del panel:
el fichero queda dentro del prefijo físico de su carpeta y el media queda
vinculado a ella, de modo que aparece en el gestor donde corresponde.

```php
use Marzio\MediaManager\Facades\MediaManager;

// Desde el disco "public" (por defecto), a la raíz del vault
$media = MediaManager::add('logos/logo.svg');
$url = $media->getUrl();

// Dentro de una carpeta — se crea si no existe
$media = MediaManager::add('logos/logo.svg', folder: 'marca');

// Carpetas anidadas
$media = MediaManager::add('banners/home.jpg', folder: 'banners/home');

// Desde otro disco de origen
$media = MediaManager::add('seed/logo.svg', folder: 'marca', disk: 'seed-assets');

// Atajo que devuelve la URL directamente
$url = MediaManager::url('logos/logo.svg', folder: 'marca');

// Desde una ruta absoluta, fuera de los discos de Laravel
$media = MediaManager::addFromFile(storage_path('app/tmp/foto.jpg'), folder: 'fotos');
```

#### Parámetros

| Parámetro | Por defecto | Descripción |
|---|---|---|
| `path` | — | Ruta del fichero dentro del disco de origen |
| `folder` | `null` | Carpeta destino. Admite anidamiento con `/`. Se crea si no existe. `null` deja el recurso en la raíz |
| `disk` | `'public'` | Disco de **origen**. El de destino es siempre `media-manager.disk` |
| `vault` | `null` | Vault propietario. Por defecto el del `VaultResolver` |
| `fileName` | `null` | Nombre final. Por defecto el del fichero de origen |
| `unique` | `false` | Si el nombre ya existe en la carpeta, añade `-1`, `-2`… |
| `preservingOriginal` | `true` | Conservar el fichero de origen |

Sobre `unique`: el valor por defecto es `false` a propósito. Llamadas repetidas
sobrescriben el mismo objeto y producen siempre la misma URL, que es lo que
necesitas en un seeder cuando esa URL queda embebida en otros registros. Pásalo
a `true` en importadores donde cada fichero deba conservarse por separado.

#### Buscar un recurso ya existente

`add()` y `url()` **suben** un fichero. Para leer lo que ya está en la biblioteca
—sin volver a subirlo— están `find()` y `findUrl()`, que lo localizan por su
ruta `carpeta/nombre`:

```php
use Marzio\MediaManager\Facades\MediaManager;

// El recurso (Media), o null si no existe
$media = MediaManager::find('marca/logo.svg');
$url = $media?->getUrl();

// Directamente la URL
$url = MediaManager::findUrl('marca/logo.svg');

// En la raíz del vault
$media = MediaManager::find('logo.svg');

// Carpeta explícita: equivalente a la primera forma
$media = MediaManager::find('logo.svg', folder: 'marca');

// Carpetas anidadas
$url = MediaManager::findUrl('banners/home/portada.jpg');

// Una conversión concreta
$url = MediaManager::findUrl('fotos/retrato.jpg', conversion: 'thumb');
```

El nombre admite las dos formas, **con y sin extensión**. Spatie guarda el
nombre completo en `file_name` y el nombre sin extensión en `name`, y la
búsqueda contrasta ambos, así que `find('marca/logo')` y `find('marca/logo.svg')`
devuelven el mismo recurso.

Si pides una conversión que todavía no se ha generado, `findUrl()` devuelve la
URL del original en lugar de una ruta rota.

#### La variante que falla ruidosamente

En un seeder, un `null` inadvertido se embebe en el contenido y el problema
aparece mucho después, como una imagen rota difícil de rastrear. `findOrFail()`
corta ahí mismo:

```php
$url = MediaManager::findOrFail('marca/logo.svg')->getUrl();
// RuntimeException: Media Manager: no se encontró el recurso "marca/logo.svg".
```

#### Subir o reutilizar

Un patrón habitual en seeders que se ejecutan más de una vez: usar el recurso si
ya está, subirlo si no.

```php
$url = MediaManager::findUrl('marca/logo.svg')
    ?? MediaManager::url('logo.svg', folder: 'marca', disk: 'seed-assets');
```

#### Trabajar con carpetas

```php
// Devuelve la carpeta, creándola (y a sus ancestros) si no existe
$folder = MediaManager::folder('documentos/facturas');

// Busca sin crear — null si algún tramo no existe
$folder = MediaManager::findFolder('documentos/facturas');
```

Las carpetas creadas por código son indistinguibles de las creadas por la
interfaz: mismo slug, mismo `path` y mismo scope por vault.

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
│           ├── MediaFolders.php      # Carpetas: crear/eliminar/navegar
│           ├── MediaPickerGrid.php   # Grid en el picker modal
│           ├── MediaGalleryPickerGrid.php
│           └── MediaBulkUploader.php # Uploader masivo
├── Facades/
│   └── MediaManager.php              # Facade de la API programática
├── Models/
│   ├── MediaVault.php                # Modelo principal
│   └── MediaFolder.php               # Modelo de carpeta
├── Support/
│   ├── UploadContext.php             # Contexto de subida (carpeta destino)
│   ├── MediaFolderResolver.php       # Resuelve/crea carpetas desde una ruta legible
│   ├── UniqueFileNamer.php           # Nombres únicos dentro de un directorio
│   ├── FolderAwarePathGenerator.php  # Ubica los ficheros en el prefijo de su carpeta
│   └── FolderAwareFileRemover.php    # Borra el original por ruta exacta (borrado seguro)
├── MediaManagerService.php           # API programática (add/url/folder)
└── MediaManagerServiceProvider.php   # Service Provider

database/
└── migrations/
    └── 9999_12_31_000000_create_media_manager_tables.php   # Migración única
```

## Licencia

MIT

## Autor

Marzio Perez - marzioperez@gmail.com