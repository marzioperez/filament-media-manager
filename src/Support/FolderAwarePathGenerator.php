<?php

namespace Marzio\MediaManager\Support;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\DefaultPathGenerator;
use Marzio\MediaManager\Models\MediaFolder;

/**
 * PathGenerator que coloca los ficheros del VAULT dentro del prefijo físico de
 * su carpeta en el disco (S3), SIN una subcarpeta numérica por media:
 *
 *     {ruta-de-la-carpeta}/archivo.ext          (en una carpeta)
 *     archivo.ext                               (en la raíz del vault)
 *     {ruta-de-la-carpeta}/conversions/...
 *     {ruta-de-la-carpeta}/responsive-images/...
 *
 * SEGURIDAD EN PROYECTOS ANFITRIÓN
 * --------------------------------
 * Spatie usa un único path generator global. Como este paquete puede convivir
 * con muchas otras colecciones de media del proyecto anfitrión (teachers,
 * courses, etc.), esta clase EXTIENDE el generador por defecto de Spatie y solo
 * aplica la lógica de carpetas a las medias del vault (identificadas por su
 * colección, `media-manager.collection`). Para cualquier otra media delega en el
 * comportamiento por defecto ("{id}/archivo"), de modo que no altera el
 * almacenamiento existente del proyecto.
 *
 * Para que el borrado sea seguro (varios medios comparten directorio dentro de
 * una carpeta) el paquete registra también un FileRemover propio
 * (FolderAwareFileRemover) que borra el original por su ruta EXACTA.
 *
 * La carpeta se resuelve primero desde la columna `media_folder_id` del media
 * y, si aún no está persistida (durante la copia inicial del fichero), desde el
 * UploadContext.
 */
class FolderAwarePathGenerator extends DefaultPathGenerator {

    /** @var array<int|string, string> Cache de rutas de carpeta por id. */
    protected static array $folderPathCache = [];

    public function getPath(Media $media): string {
        if (! $this->isVaultMedia($media)) {
            return parent::getPath($media);
        }

        return $this->basePath($media);
    }

    public function getPathForConversions(Media $media): string {
        if (! $this->isVaultMedia($media)) {
            return parent::getPathForConversions($media);
        }

        return $this->basePath($media) . 'conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string {
        if (! $this->isVaultMedia($media)) {
            return parent::getPathForResponsiveImages($media);
        }

        return $this->basePath($media) . 'responsive-images/';
    }

    /**
     * ¿La media pertenece al vault del gestor? Se identifica por su colección.
     * Durante la subida la columna todavía puede no estar persistida, así que
     * también se acepta cualquier media mientras hay un UploadContext activo.
     */
    protected function isVaultMedia(Media $media): bool {
        $collection = config('media-manager.collection', 'assets');

        if ($media->collection_name === $collection) {
            return true;
        }

        return UploadContext::get() !== null && $media->collection_name === $collection;
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
