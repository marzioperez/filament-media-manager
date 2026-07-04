<?php

namespace Marzio\MediaManager\Support;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\FileRemover\DefaultFileRemover;

/**
 * FileRemover que borra el fichero ORIGINAL por su ruta exacta.
 *
 * El FileRemover por defecto de Spatie localiza el original listando el
 * directorio del media y filtrando por NOMBRE de fichero. Eso es seguro cuando
 * cada media vive en su propio subdirectorio ("{id}/"), pero en este paquete
 * varios medios comparten el directorio de una carpeta (o la raíz), así que un
 * borrado por nombre podría afectar a un fichero homónimo de otra carpeta.
 *
 * Aquí sobrescribimos únicamente la eliminación del original para borrar su
 * ruta EXACTA (`getPathRelativeToRoot()`), que la genera el PathGenerator. La
 * eliminación de conversiones e imágenes responsive se deja al comportamiento
 * por defecto, que ya opera por rutas exactas (array_intersect).
 */
class FolderAwareFileRemover extends DefaultFileRemover {

    public function removeFromMediaDirectory(Media $media, string $disk): void {
        try {
            // Ruta exacta del fichero original, relativa a la raíz del disco.
            $path = $media->getPathRelativeToRoot();

            if ($path !== '' && $this->filesystem->disk($disk)->exists($path)) {
                $this->filesystem->disk($disk)->delete($path);
            }

            // Limpia el subdirectorio propio del media SOLO si sigue el patrón
            // por-id (termina en "{id}/") y ha quedado vacío. Nunca se borra el
            // directorio de una carpeta compartida ni la raíz del disco.
            $directory = rtrim($this->mediaFileSystem->getMediaDirectory($media), '/');

            if ($directory !== '' && $directory === (string) $media->getKey()) {
                if (empty($this->filesystem->disk($disk)->allFiles($directory))) {
                    $this->filesystem->disk($disk)->deleteDirectory($directory);
                }
            }
        } catch (\Exception $exception) {
            report($exception);
        }
    }
}
