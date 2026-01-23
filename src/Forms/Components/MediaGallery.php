<?php

namespace Marzio\MediaManager\Forms\Components;

use Filament\Forms\Components\Field;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaGallery extends Field {

    protected string $view = 'media-manager::filament.forms.components.media-gallery';

    protected string $returnType = 'id'; // 'id' o 'url'
    protected string $conversionName = 'webp'; // conversión a usar cuando returnType = 'url'

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
            if ($this->returnType === 'url') {
                return collect($state)
                    ->map(function ($row) {
                        $mediaId = is_array($row) ? (int) ($row['media_id'] ?? 0) : (int) $row;
                        if (!$mediaId) return null;

                        $media = Media::find($mediaId);
                        if (!$media) return null;

                        try {
                            if ($media->hasGeneratedConversion($this->conversionName)) {
                                return $media->getUrl($this->conversionName);
                            }
                            return $media->getUrl();
                        } catch (\Throwable $e) {
                            return null;
                        }
                    })
                    ->filter()
                    ->values()
                    ->all();
            }

            // Modo ID (default)
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

        // Si el estado es un array de URLs (cuando returnType = 'url')
        if ($this->returnType === 'url' && !empty($source) && is_string($source[0] ?? null)) {
            $ids = collect($source)
                ->map(function ($url) {
                    if (!filter_var($url, FILTER_VALIDATE_URL)) return null;

                    $media = Media::where(function($q) use ($url) {
                        $q->whereRaw("CONCAT(disk, '/', id, '/', file_name) LIKE ?", ['%' . basename($url) . '%']);
                    })->first();

                    return $media ? $media->id : null;
                })
                ->filter()
                ->values();
        } else {
            $ids = collect($source)
                ->map(fn ($v) => is_array($v) ? (int) ($v['media_id'] ?? 0) : (int) $v)
                ->filter()
                ->values();
        }

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
                '_k'       => (string) Str::uuid(),
                'media_id' => $id,
                'url'      => $url,
                'mime'     => $mime,
            ];
        })->all();
    }

    public function returnUrl(string $conversionName = 'webp'): static {
        $this->returnType = 'url';
        $this->conversionName = $conversionName;
        return $this;
    }

    public function returnId(): static {
        $this->returnType = 'id';
        return $this;
    }

    public function conversion(string $name): static {
        $this->conversionName = $name;
        return $this;
    }

    public function getViewData(): array {
        return array_merge(parent::getViewData(), [
            'returnType' => $this->returnType,
            'conversionName' => $this->conversionName,
        ]);
    }

}
