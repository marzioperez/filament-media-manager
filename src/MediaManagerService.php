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
