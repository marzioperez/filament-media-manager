<?php

namespace Marzio\MediaManager\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Reemplaza una URL de media "congelada" (guardada como texto plano por
 * MediaPicker::returnUrl() / MediaGallery::returnUrl()) por la nueva URL, en
 * los modelos y columnas que el proyecto anfitrión declare en
 * `config('media-manager.url_reference_scan.targets')`.
 *
 * Opt-in y disparado explícitamente (no automático): el usuario decide desde
 * el Gestor de Medios si quiere reindexar las referencias tras mover/copiar
 * un recurso.
 *
 * Itera registros en PHP (en vez de un REPLACE a nivel SQL) porque estas
 * columnas suelen ser JSON de estructura variable definida por el proyecto
 * anfitrión, y el volumen de filas que referencian un media concreto suele
 * ser bajo.
 */
class UpdateMediaUrlReferencesJob implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $oldUrl,
        public string $newUrl,
    ) {}

    public function handle(): void {
        if ($this->oldUrl === $this->newUrl) {
            return;
        }

        $targets = config('media-manager.url_reference_scan.targets', []);

        foreach ($targets as $modelClass => $columns) {
            if (! class_exists($modelClass) || empty($columns)) {
                continue;
            }

            $this->updateModel($modelClass, (array) $columns);
        }
    }

    protected function updateModel(string $modelClass, array $columns): void {
        /** @var \Illuminate\Database\Eloquent\Model $model */
        $model = new $modelClass();
        $query = $modelClass::query();

        $query->where(function ($q) use ($columns) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', '%' . $this->oldUrl . '%');
            }
        });

        $query->chunkById(100, function ($records) use ($columns) {
            foreach ($records as $record) {
                $dirty = false;

                foreach ($columns as $column) {
                    $value = $record->getAttribute($column);

                    if ($value === null) {
                        continue;
                    }

                    $replaced = $this->replaceInValue($value);

                    if ($replaced !== $value) {
                        $record->setAttribute($column, $replaced);
                        $dirty = true;
                    }
                }

                if ($dirty) {
                    $record->saveQuietly();
                }
            }
        }, $model->getKeyName());
    }

    protected function replaceInValue(mixed $value): mixed {
        if (is_string($value)) {
            return str_replace($this->oldUrl, $this->newUrl, $value);
        }

        if (is_array($value)) {
            return array_map(fn ($item) => $this->replaceInValue($item), $value);
        }

        return $value;
    }
}
