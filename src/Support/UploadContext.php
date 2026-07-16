<?php

namespace Marzio\MediaManager\Support;

/**
 * Contexto efímero de subida.
 *
 * El PathGenerator de Spatie se ejecuta mientras el fichero se copia al disco,
 * momento en el que la columna `media_folder_id` del modelo Media todavía puede
 * no estar persistida. Este contenedor estático permite comunicar la carpeta
 * destino al PathGenerator durante ese instante.
 *
 * Fuera de una subida activa su valor es `null`, de modo que no afecta a
 * ningún otro media del proyecto anfitrión.
 */
class UploadContext {

    protected static ?int $folderId = null;

    public static function set(?int $folderId): void {
        static::$folderId = $folderId;
    }

    public static function get(): ?int {
        return static::$folderId;
    }

    public static function clear(): void {
        static::$folderId = null;
    }
}
