<?php

namespace Marzio\MediaManager\Http\Livewire\Concerns;

use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Permite que un componente Livewire detecte si, entre los medios que está
 * mostrando, hay imágenes cuya conversión principal (`webp`) todavía no se
 * generó — porque `->queued()` la difiere al `PerformConversionsJob` de
 * Spatie. Mientras haya alguna pendiente, la vista puede activar
 * `wire:poll` para refrescarse sola hasta que la cola termine.
 */
trait PollsPendingConversions {

    /**
     * @param iterable<int, Media> $items
     */
    protected function mediaHasPendingConversions(iterable $items): bool {
        $conversion = config('media-manager.full_conversion', 'webp');

        foreach ($items as $media) {
            if (! str_starts_with((string) $media->mime_type, 'image/')) {
                continue;
            }

            if (! $media->hasGeneratedConversion($conversion)) {
                return true;
            }
        }

        return false;
    }
}
