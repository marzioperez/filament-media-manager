<?php

namespace Marzio\MediaManager\Support;

use Marzio\MediaManager\Models\MediaFolder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

/**
 * PathGenerator que coloca los ficheros dentro del prefijo físico de su
 * carpeta en el disco (S3).
 *
 * Los ficheros se guardan directamente en la ruta de su carpeta, SIN una
 * subcarpeta numérica por media:
 *
 *     {ruta-de-la-carpeta}/archivo.ext          (en una carpeta)
 *     archivo.ext                               (en la raíz)
 *     {ruta-de-la-carpeta}/conversions/...
 *     {ruta-de-la-carpeta}/responsive-images/...
 *
 * Para que esto sea seguro (varios medios comparten directorio), el paquete
 * registra también un FileRemover propio (FolderAwareFileRemover) que borra el
 * fichero original por su ruta EXACTA en lugar de por nombre, evitando que un
 * fichero homónimo de otra carpeta se elimine por accidente.
 *
 * La carpeta se resuelve primero desde la columna `media_folder_id` del media
 * y, si aún no está persistida (durante la copia inicial del fichero), desde
 * el UploadContext. La unicidad de nombres dentro de cada carpeta (y de la
 * raíz) la garantiza el uploader.
 */
class FolderAwarePathGenerator implements PathGenerator {

    /** @var array<int|string, string> Cache de rutas de carpeta por id. */
    protected static array $folderPathCache = [];

    public function getPath(Media $media): string {
        return $this->basePath($media);
    }

    public function getPathForConversions(Media $media): string {
        return $this->basePath($media) . 'conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string {
        return $this->basePath($media) . 'responsive-images/';
    }

    protected function basePath(Media $media): string {
        // Sin subcarpeta numérica por media. En una carpeta devuelve
        // "ruta/carpeta/"; en la raíz devuelve "" (fichero en la raíz del disco).
        return $this->folderPrefix($media);
    }

    protected function folderPrefix(Media $media): string {
        $folderId = $media->media_folder_id ?? UploadContext::get();

        if (! $folderId) {
            return '';
        }

        if (! array_key_exists($folderId, static::$folderPathCache)) {
            $folder = MediaFolder::find($folderId);
            static::$folderPathCache[$folderId] = $folder && $folder->path
                ? trim($folder->path, '/') . '/'
                : '';
        }

        return static::$folderPathCache[$folderId];
    }
}
