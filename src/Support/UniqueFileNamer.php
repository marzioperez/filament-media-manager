<?php

namespace Marzio\MediaManager\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Genera un nombre de fichero único dentro de un directorio de un disco,
 * añadiendo un sufijo "-1", "-2"… si ya existe. Compartido por el uploader
 * principal, los pickers y el mover/copiar de carpetas para que dos medios
 * nunca choquen de nombre dentro del mismo directorio físico (los ficheros
 * de este paquete no usan subcarpeta numérica por media).
 */
class UniqueFileNamer {

    public static function forDisk(string $disk, string $dir, string $fileName, bool $slugify = false): string {
        $base = pathinfo($fileName, PATHINFO_FILENAME);
        $ext  = pathinfo($fileName, PATHINFO_EXTENSION);

        if ($slugify) {
            $base = Str::slug($base, '-');
            if ($base === '') {
                $base = 'file';
            }
        }

        $suffix = $ext !== '' ? '.' . $ext : '';
        $prefix = $dir !== '' ? rtrim($dir, '/') . '/' : '';

        $candidate = $base . $suffix;
        $i = 0;

        while (Storage::disk($disk)->exists($prefix . $candidate)) {
            $i++;
            $candidate = $base . '-' . $i . $suffix;
        }

        return $candidate;
    }
}
