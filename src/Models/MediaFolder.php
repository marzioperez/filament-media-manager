<?php

namespace Marzio\MediaManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Carpeta para organizar los medios.
 *
 * Cada carpeta se corresponde con un prefijo físico en el disco de
 * almacenamiento (normalmente S3). La columna `path` guarda la ruta completa
 * relativa a la raíz del disco (p.ej. "documentos/facturas").
 *
 * @property int         $id
 * @property string      $name
 * @property string      $slug
 * @property string      $path
 * @property string|null $disk
 * @property int|null    $parent_id
 * @property string|null $model_type
 * @property mixed       $model_id
 */
class MediaFolder extends Model {

    protected $table = 'media_folders';

    protected $guarded = [];

    public function parent(): BelongsTo {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Genera un slug seguro para el sistema de archivos a partir de un nombre.
     */
    public static function makeSlug(string $name): string {
        $slug = Str::slug($name, '-');

        return $slug !== '' ? $slug : 'carpeta';
    }

    /**
     * Cadena de ancestros (de la raíz hasta esta carpeta, incluida) — útil
     * para pintar breadcrumbs.
     *
     * @return \Illuminate\Support\Collection<int, MediaFolder>
     */
    public function breadcrumbs() {
        $chain = collect();
        $node  = $this;

        while ($node) {
            $chain->prepend($node);
            $node = $node->parent;
        }

        return $chain;
    }
}
