<?php

namespace Marzio\MediaManager;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Marzio\MediaManager\Models\MediaFolder;
use Marzio\MediaManager\Support\MediaFolderResolver;
use Marzio\MediaManager\Support\UniqueFileNamer;
use Marzio\MediaManager\Support\UploadContext;
use Marzio\MediaManager\Vault\VaultResolver;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * API programática del Media Manager.
 *
 * Permite incorporar recursos a la biblioteca desde código (seeders, comandos,
 * importadores, jobs) con el mismo resultado que el uploader del panel:
 * el fichero queda dentro del prefijo físico de su carpeta y el media queda
 * vinculado a ella, de modo que aparece en el gestor donde corresponde.
 *
 * Se accede normalmente a través del facade:
 *
 *     use Marzio\MediaManager\Facades\MediaManager;
 *
 *     $media = MediaManager::add('logos/logo.svg', folder: 'marca');
 *     $url   = $media->getUrl();
 *
 *     // o directamente la URL:
 *     $url = MediaManager::url('logos/logo.svg', folder: 'marca');
 */
class MediaManagerService {

    /**
     * Incorpora un fichero que vive en un disco de Laravel.
     *
     * @param  string       $path      Ruta del fichero dentro de $disk (p.ej. "seed/logo.svg").
     * @param  string|null  $folder    Carpeta destino, admite anidamiento ("banners/home").
     *                                 Si no existe se crea. Null deja el recurso en la raíz.
     * @param  string       $disk      Disco de ORIGEN donde está el fichero. No confundir con
     *                                 el disco de destino, que siempre es media-manager.disk.
     * @param  Model|null   $vault     Vault propietario. Por defecto, el del VaultResolver.
     * @param  string|null  $fileName  Nombre final. Por defecto, el del fichero de origen.
     * @param  bool         $unique    Si el nombre ya existe en la carpeta, añade "-1", "-2"…
     *                                 Por defecto false: se sobrescribe, de modo que llamadas
     *                                 repetidas produzcan siempre la misma URL (importante en
     *                                 seeders, donde la URL queda embebida en otros registros).
     * @param  bool         $preservingOriginal  Conservar el fichero de origen. Por defecto sí.
     */
    public function add(
        string $path,
        ?string $folder = null,
        string $disk = 'public',
        ?Model $vault = null,
        ?string $fileName = null,
        bool $unique = false,
        bool $preservingOriginal = true,
    ): Media {
        $vault ??= VaultResolver::model();

        return $this->store(
            fileAdder: $vault->addMediaFromDisk($path, $disk),
            vault: $vault,
            folder: $folder,
            fileName: $fileName ?? basename($path),
            unique: $unique,
            preservingOriginal: $preservingOriginal,
        );
    }

    /**
     * Igual que add(), pero desde una ruta absoluta del sistema de ficheros
     * (fuera de los discos de Laravel). Útil con storage_path()/base_path().
     */
    public function addFromFile(
        string $absolutePath,
        ?string $folder = null,
        ?Model $vault = null,
        ?string $fileName = null,
        bool $unique = false,
        bool $preservingOriginal = true,
    ): Media {
        $vault ??= VaultResolver::model();

        return $this->store(
            fileAdder: $vault->addMedia($absolutePath),
            vault: $vault,
            folder: $folder,
            fileName: $fileName ?? basename($absolutePath),
            unique: $unique,
            preservingOriginal: $preservingOriginal,
        );
    }

    /**
     * Atajo que devuelve directamente la URL pública del recurso incorporado.
     */
    public function url(
        string $path,
        ?string $folder = null,
        string $disk = 'public',
        ?Model $vault = null,
        ?string $fileName = null,
        bool $unique = false,
        bool $preservingOriginal = true,
    ): string {
        return $this->add(
            path: $path,
            folder: $folder,
            disk: $disk,
            vault: $vault,
            fileName: $fileName,
            unique: $unique,
            preservingOriginal: $preservingOriginal,
        )->getUrl();
    }

    /**
     * Devuelve la carpeta indicada, creándola (y a sus ancestros) si no existe.
     */
    public function folder(string $folder, ?Model $vault = null): ?MediaFolder {
        return MediaFolderResolver::resolve($folder, $vault);
    }

    /**
     * Busca una carpeta sin crearla.
     */
    public function findFolder(string $folder, ?Model $vault = null): ?MediaFolder {
        return MediaFolderResolver::find($folder, $vault);
    }

    /**
     * Busca un recurso ya existente por su ruta "carpeta/nombre".
     *
     * Pensado para leer desde código lo que ya está en la biblioteca —seeders,
     * comandos, importadores— sin volver a subir el fichero.
     *
     *     MediaManager::find('marca/logo.svg');
     *     MediaManager::find('logo.svg');                  // en la raíz
     *     MediaManager::find('logo.svg', folder: 'marca'); // equivalente al primero
     *
     * El nombre admite las dos formas, con y sin extensión: Spatie guarda el
     * nombre completo en `file_name` y el nombre sin extensión en `name`, y aquí
     * se contrastan ambos. Así `find('marca/logo')` y `find('marca/logo.svg')`
     * devuelven el mismo recurso.
     *
     * @param  string       $path    Ruta del recurso. El último tramo es el nombre.
     * @param  string|null  $folder  Carpeta explícita; si se pasa, $path es solo el nombre.
     * @param  Model|null   $vault   Vault donde buscar. Por defecto, el del VaultResolver.
     */
    public function find(string $path, ?string $folder = null, ?Model $vault = null): ?Media {
        $path = trim($path, '/');

        // Sin carpeta explícita, el último tramo de la ruta es el nombre.
        if (is_null($folder) && str_contains($path, '/')) {
            $folder = Str::beforeLast($path, '/');
            $name = Str::afterLast($path, '/');
        } else {
            $name = $path;
        }

        if ($name === '') {
            return null;
        }

        $vault ??= VaultResolver::model();

        $mediaFolder = filled($folder) ? MediaFolderResolver::find($folder, $vault) : null;

        // Se pidió una carpeta que no existe: no hay nada que buscar dentro.
        if (filled($folder) && is_null($mediaFolder)) {
            return null;
        }

        /** @var class-string<Media> $model */
        $model = config('media-library.media_model', Media::class);

        return $model::query()
            ->where('model_type', $vault::class)
            ->where('model_id', $vault->getKey())
            ->where('collection_name', config('media-manager.collection', 'assets'))
            ->where('media_folder_id', $mediaFolder?->id)
            ->where(fn ($query) => $query->where('file_name', $name)->orWhere('name', $name))
            ->orderBy('id')
            ->first();
    }

    /**
     * Igual que find(), pero lanza una excepción si no encuentra el recurso.
     *
     * Es la variante recomendada en seeders: si el asset falta, un null se
     * embebe en el contenido y el fallo aparece mucho después, como una imagen
     * rota difícil de rastrear. Aquí revienta en el punto exacto.
     *
     * @throws \RuntimeException
     */
    public function findOrFail(string $path, ?string $folder = null, ?Model $vault = null): Media {
        $media = $this->find($path, $folder, $vault);

        if (is_null($media)) {
            throw new \RuntimeException(sprintf(
                'Media Manager: no se encontró el recurso "%s"%s.',
                $path,
                filled($folder) ? sprintf(' en la carpeta "%s"', $folder) : ''
            ));
        }

        return $media;
    }

    /**
     * URL de un recurso ya existente, buscándolo por su ruta.
     *
     * Devuelve null si no existe. No confundir con url(), que SUBE un fichero y
     * devuelve la URL del recurso recién creado.
     *
     *     $url = MediaManager::findUrl('marca/logo.svg');
     *     $url = MediaManager::findUrl('fotos/portada.jpg', conversion: 'thumb');
     *
     * @param  string  $conversion  Conversión a devolver. Si aún no está generada,
     *                              se devuelve la URL del original en vez de una rota.
     */
    public function findUrl(
        string $path,
        ?string $folder = null,
        ?Model $vault = null,
        string $conversion = '',
    ): ?string {
        $media = $this->find($path, $folder, $vault);

        if (is_null($media)) {
            return null;
        }

        return $conversion !== '' && $media->hasGeneratedConversion($conversion)
            ? $media->getUrl($conversion)
            : $media->getUrl();
    }

    /**
     * Tronco común de add() y addFromFile().
     *
     * El orden importa: la carpeta debe comunicarse al PathGenerator ANTES de
     * que Spatie copie el fichero (vía UploadContext, porque en ese instante la
     * columna media_folder_id todavía no existe en base de datos), y persistirse
     * DESPUÉS, que es de donde la leen el grid del panel y las conversiones en
     * cola.
     *
     * @param  \Spatie\MediaLibrary\MediaCollections\FileAdder  $fileAdder
     */
    protected function store(
        $fileAdder,
        Model $vault,
        ?string $folder,
        string $fileName,
        bool $unique,
        bool $preservingOriginal,
    ): Media {
        $mediaDisk = config('media-manager.disk', 'media-manager');
        $mediaFolder = filled($folder) ? MediaFolderResolver::resolve($folder, $vault) : null;

        if ($unique) {
            $directory = $mediaFolder ? trim($mediaFolder->path, '/') : '';
            $fileName = UniqueFileNamer::forDisk($mediaDisk, $directory, $fileName);
        }

        UploadContext::set($mediaFolder?->id);

        try {
            if ($preservingOriginal) {
                $fileAdder = $fileAdder->preservingOriginal();
            }

            $media = $fileAdder
                ->usingFileName($fileName)
                ->usingName(pathinfo($fileName, PATHINFO_FILENAME))
                ->toMediaCollection(config('media-manager.collection', 'assets'), $mediaDisk);
        } finally {
            UploadContext::clear();
        }

        $media->media_folder_id = $mediaFolder?->id;

        if (is_null($media->uuid)) {
            $media->uuid = Str::uuid();
        }

        $media->save();

        return $media;
    }
}
