<?php

namespace Marzio\MediaManager\Support;

use Marzio\MediaManager\Models\MediaFolder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

/**
 * PathGenerator que coloca los ficheros dentro del prefijo físico de su
 * carpeta en el disco (S3).
 *
 * Estructura resultante:
 *
 *     {ruta-de-la-carpeta}/{media_id}/archivo.ext
 *     {ruta-de-la-carpeta}/{media_id}/conversions/...
 *     {ruta-de-la-carpeta}/{media_id}/responsive-images/...
 *
 * Si el media no pertenece a ninguna carpeta, se comporta EXACTAMENTE igual
 * que el generador por defecto de Spatie ("{media_id}/"), de modo que no
 * altera el resto de medios del proyecto anfitrión.
 *
 * La carpeta se resuelve primero desde la columna `media_folder_id` del media
 * y, si aún no está persistida (durante la copia inicial del fichero), desde
 * el UploadContext.
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
        return $this->folderPrefix($media) . $media->getKey() . '/';
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
