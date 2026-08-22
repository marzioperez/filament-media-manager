<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Disk donde se almacenarán los recursos
    |--------------------------------------------------------------------------
    |
    | Aquí defines el disco donde guardará Spatie Media Library. Normalmente
    | será un disco S3.
    |
    */

    'disk' => env('MEDIA_MANAGER_DISK', 'media-manager'),

    /*
    |--------------------------------------------------------------------------
    | Colección principal de MediaVault
    |--------------------------------------------------------------------------
    |
    | Tu paquete trabaja con una colección principal para organizar todos
    | los archivos multimedia.
    |
    */

    'collection' => 'assets',

    /*
    |--------------------------------------------------------------------------
    | Conversión para previews (thumb)
    |--------------------------------------------------------------------------
    |
    | Cuando tu componente muestre previews, debe saber cuál conversión usar.
    | Si no existe la conversión, se usará el archivo original.
    |
    */

    'preview_conversion' => 'thumb',

    /*
    |--------------------------------------------------------------------------
    | Conversión principal (WebP)
    |--------------------------------------------------------------------------
    |
    | Conversión de formato para uso general, optimizada.
    |
    */

    'full_conversion' => 'webp',

    /*
    |--------------------------------------------------------------------------
    | Carpeta inicial (opcional)
    |--------------------------------------------------------------------------
    |
    | Si tu MediaVault tiene carpetas, esto ayuda a definir una raíz lógica.
    |
    */

    'root_folder_id' => null,

    /*
    |--------------------------------------------------------------------------
    | Límite máximo de archivos por lote al subir
    |--------------------------------------------------------------------------
    */

    'max_upload_batch' => 50,

    /*
    |--------------------------------------------------------------------------
    | ¿Procesar conversiones en cola?
    |--------------------------------------------------------------------------
    */

    'queued_conversions' => true,

    /*
    |--------------------------------------------------------------------------
    | Vault (contenedor de medias) — opcional
    |--------------------------------------------------------------------------
    |
    | Por defecto el paquete usa MediaVault singleton (id = 1) como modelo
    | que "posee" todas las medias — diseñado para proyectos single-tenant.
    |
    | Para multi-tenant, define un resolver: callable que devuelva el Model
    | (que implemente HasMedia) que actuará como vault para el contexto
    | actual. Si devuelve null o un objeto inválido, el paquete cae al
    | MediaVault default — nunca se rompe.
    |
    | Ejemplo (Filament tenancy):
    |
    |   'vault' => [
    |       'resolver' => fn () => \Filament\Facades\Filament::getTenant(),
    |   ],
    |
    */

    'vault' => [
        'resolver' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Reindexado de URLs tras mover/copiar un recurso
    |--------------------------------------------------------------------------
    |
    | Cuando un campo MediaPicker/MediaGallery usa ->returnUrl(), el proyecto
    | anfitrión guarda la URL completa del recurso (no su ID) en sus propios
    | modelos. Mover o copiar ese recurso a otra carpeta cambia su URL, lo que
    | dejaría esas referencias rotas.
    |
    | Declara aquí qué modelos y columnas (texto o JSON) deben revisarse; tras
    | mover/copiar, el Gestor de Medios ofrecerá (de forma opcional, nunca
    | automática) reemplazar la URL antigua por la nueva en esas columnas.
    |
    | Ejemplo:
    |
    |   'url_reference_scan' => [
    |       'enabled' => true,
    |       'targets' => [
    |           \App\Models\Post::class => ['body', 'meta'],
    |           \App\Models\Page::class => ['content'],
    |       ],
    |   ],
    |
    */

    'url_reference_scan' => [
        'enabled' => false,
        'targets' => [
            // \App\Models\Post::class => ['body', 'meta'],
        ],
    ],

];