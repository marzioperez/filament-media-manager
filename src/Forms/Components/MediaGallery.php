<?php

namespace Marzio\MediaManager\Forms\Components;

use Filament\Forms\Components\Field;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaGallery extends Field {

    protected string $view = 'media-manager::filament.forms.components.media-gallery';

    protected function setUp(): void {
        parent::setUp();

        $this->dehydrated(true);
        $this->live();
        $this->default(fn () => []);
        $this->rules(['array']);

        $this->afterStateHydrated(function ($state, callable $set) {
            $set($this->getName(), $this->normalizeToRows($state));
        });

        $this->afterStateUpdated(function ($state, callable $set) {
            $set($this->getName(), $this->normalizeToRows($state));
        });

        $this->dehydrateStateUsing(function ($state) {
            return collect($state)
                ->map(fn ($row) => is_array($row) ? (int) ($row['media_id'] ?? 0) : (int) $row)
                ->filter()
                ->values()
                ->all();
        });
    }

    protected function normalizeToRows(mixed $state): array {
        $source = is_array($state) ? $state : [];
        if (array_key_exists('rows', $source)) {
            $source = is_array($source['rows']) ? $source['rows'] : [];
        }

        $ids = collect($source)
            ->map(fn ($v) => is_array($v) ? (int) ($v['media_id'] ?? 0) : (int) $v)
            ->filter()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $media = Media::query()->whereIn('id', $ids)->get();
        $byId  = $media->keyBy('id');

        return $ids->map(function (int $id) use ($byId) {
            $m    = $byId->get($id);
            $url  = null;
            $mime = null;

            if ($m) {
                $mime = $m->mime_type;
                if (!empty($m->disk)) {
                    try {
                        $url = $m->hasGeneratedConversion('thumb')
                            ? $m->getUrl('thumb')
                            : $m->getUrl();
                    } catch (\Throwable $e) {
                        $url = null;
                    }
                }
            }

            return [
                '_k'      => (string) Str::uuid(),
                'media_id' => $id,
                'url'      => $url,
                'mime'     => $mime,
            ];
        })->all();
    }

}
