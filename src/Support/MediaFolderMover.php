<?php

namespace Marzio\MediaManager\Support;

use Illuminate\Support\Facades\Storage;
use Marzio\MediaManager\Models\MediaFolder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Mueve un `Media` a otra carpeta, actualizando `media_folder_id` Y
 * reubicando físicamente sus ficheros (original + conversiones) en el disco,
 * ya que `FolderAwarePathGenerator` calcula la ruta a partir de la carpeta.
 * Sin este paso, la URL calculada tras el cambio apuntaría a una ruta donde
 * el fichero ya no existe.
 */
class MediaFolderMover {

    public function move(Media $media, ?int $targetFolderId): Media {
        $originalDisk    = $media->disk;
        $conversionsDisk = $media->conversions_disk ?: $originalDisk;

        $oldOriginalPath = $media->getPathRelativeToRoot();

        $generatedConversions = $media->getGeneratedConversions()
            ->filter()
            ->keys();

        $oldConversionPaths = $generatedConversions
            ->mapWithKeys(fn (string $name) => [$name => $media->getPathRelativeToRoot($name)]);

        $targetFolder = $targetFolderId ? MediaFolder::find($targetFolderId) : null;
        $targetDir    = $targetFolder ? trim($targetFolder->path, '/') : '';

        $newFileName = UniqueFileNamer::forDisk($originalDisk, $targetDir, $media->file_name);

        $media->media_folder_id = $targetFolderId;

        if ($newFileName !== $media->file_name) {
            $media->file_name = $newFileName;
        }

        $media->save();

        // La ruta física depende de `media_folder_id`; olvidamos la caché de
        // FolderAwarePathGenerator para que las rutas de abajo se calculen
        // con la carpeta nueva, no con la que quedó cacheada.
        FolderAwarePathGenerator::forgetCache();

        $this->moveFile($originalDisk, $oldOriginalPath, $media->getPathRelativeToRoot());

        foreach ($oldConversionPaths as $name => $oldPath) {
            $this->moveFile($conversionsDisk, $oldPath, $media->getPathRelativeToRoot($name));
        }

        return $media;
    }

    protected function moveFile(string $disk, string $oldPath, string $newPath): void {
        if ($oldPath === $newPath) {
            return;
        }

        try {
            $filesystem = Storage::disk($disk);

            if ($filesystem->exists($oldPath)) {
                $filesystem->move($oldPath, $newPath);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
