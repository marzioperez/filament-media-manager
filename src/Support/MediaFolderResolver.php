<?php

namespace Marzio\MediaManager\Support;

use Illuminate\Database\Eloquent\Model;
use Marzio\MediaManager\Models\MediaFolder;
use Marzio\MediaManager\Vault\VaultResolver;

/**
 * Resuelve (y crea si hace falta) carpetas del Media Manager a partir de una
 * ruta legible como "documentos/facturas".
 *
 * Replica exactamente la lógica de MediaFolders::createFolder() —el flujo del
 * panel— para que una carpeta creada por código sea indistinguible de una
 * creada por la interfaz: mismo slug, mismo `path` acumulado desde la raíz y
 * mismo scope por vault (model_type + model_id), que es la combinación del
 * índice único de la tabla.
 *
 * No se crea ningún objeto "placeholder" en el disco: igual que en el panel, la
 * carpeta existe de forma lógica en la base de datos y el prefijo físico se
 * materializa con el primer archivo que se sube.
 */
class MediaFolderResolver {

    /** @var array<string, MediaFolder> Cache por "modelType|modelId|path". */
    protected static array $cache = [];

    /**
     * Devuelve la carpeta indicada, creándola (y a sus ancestros) si no existe.
     *
     * @param  string  $folder  Ruta legible, admite anidamiento: "banners/home".
     */
    public static function resolve(string $folder, ?Model $vault = null): ?MediaFolder {
        $segments = static::segments($folder);

        if ($segments === []) {
            return null;
        }

        $vault ??= VaultResolver::model();
        $modelType = $vault::class;
        $modelId = $vault->getKey();
        $disk = config('media-manager.disk', 'media-manager');

        $parent = null;
        $path = '';

        foreach ($segments as $name) {
            $slug = MediaFolder::makeSlug($name);
            $path = ($path !== '' ? $path . '/' : '') . $slug;
            $cacheKey = $modelType . '|' . $modelId . '|' . $path;

            if (isset(static::$cache[$cacheKey])) {
                $parent = static::$cache[$cacheKey];
                continue;
            }

            $parent = MediaFolder::firstOrCreate([
                'model_type' => $modelType,
                'model_id' => $modelId,
                'parent_id' => $parent?->id,
                'slug' => $slug,
            ], [
                'name' => $name,
                'path' => $path,
                'disk' => $disk,
            ]);

            static::$cache[$cacheKey] = $parent;
        }

        return $parent;
    }

    /**
     * Busca la carpeta sin crearla. Devuelve null si algún tramo no existe.
     */
    public static function find(string $folder, ?Model $vault = null): ?MediaFolder {
        $segments = static::segments($folder);

        if ($segments === []) {
            return null;
        }

        $vault ??= VaultResolver::model();

        $parent = null;

        foreach ($segments as $name) {
            $parent = MediaFolder::query()
                ->where('model_type', $vault::class)
                ->where('model_id', $vault->getKey())
                ->where('parent_id', $parent?->id)
                ->where('slug', MediaFolder::makeSlug($name))
                ->first();

            if (! $parent) {
                return null;
            }
        }

        return $parent;
    }

    /**
     * Limpia la cache interna. Útil en tests y en comandos de larga duración.
     */
    public static function forgetCache(): void {
        static::$cache = [];
    }

    /**
     * @return array<int, string>
     */
    protected static function segments(string $folder): array {
        $segments = [];

        foreach (explode('/', trim($folder, '/')) as $segment) {
            $name = trim($segment);

            if ($name !== '') {
                $segments[] = $name;
            }
        }

        return $segments;
    }
}
