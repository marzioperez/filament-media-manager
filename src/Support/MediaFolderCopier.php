<?php

namespace Marzio\MediaManager\Support;

use Marzio\MediaManager\Models\MediaFolder;
use Marzio\MediaManager\Vault\VaultResolver;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Duplica un `Media` hacia otra carpeta re-subiéndolo como un `Media` nuevo:
 * más simple y robusto que copiar a mano los ficheros de conversión ya
 * generados (varios medios comparten directorio físico, sin subcarpeta por
 * id). Las conversiones (`thumb`/`webp`) se regeneran solas en cola, igual
 * que en una subida normal.
 */
class MediaFolderCopier {

    public function copy(Media $media, ?int $targetFolderId): Media {
        $vault     = VaultResolver::model();
        $mediaDisk = config('media-manager.disk', 'media-manager');

        $targetFolder = $targetFolderId ? MediaFolder::find($targetFolderId) : null;
        $targetDir    = $targetFolder ? trim($targetFolder->path, '/') : '';

        $newFileName = UniqueFileNamer::forDisk($mediaDisk, $targetDir, $media->file_name);

        // Se calcula ANTES de fijar el UploadContext: si el media origen está
        // en la raíz (media_folder_id null), FolderAwarePathGenerator cae al
        // UploadContext como fallback, y ya estaría apuntando a la carpeta
        // destino en vez de a la carpeta real del origen.
        $sourcePath = $media->getPathRelativeToRoot();

        // El PathGenerator resuelve la ruta física a partir de esto mientras
        // el nuevo Media todavía no tiene `media_folder_id` persistido.
        UploadContext::set($targetFolderId);

        try {
            $newMedia = $vault
                ->addMediaFromDisk($sourcePath, $media->disk)
                ->preservingOriginal() // no borrar el fichero origen que estamos copiando
                ->usingFileName($newFileName)
                ->usingName(pathinfo($newFileName, PATHINFO_FILENAME))
                ->toMediaCollection($media->collection_name, $mediaDisk);
        } finally {
            UploadContext::clear();
        }

        $newMedia->media_folder_id = $targetFolderId;
        $newMedia->save();

        return $newMedia;
    }
}
