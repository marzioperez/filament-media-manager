<?php

namespace Marzio\MediaManager\Forms\Components;

use Filament\Forms\Components\Field;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaPicker extends Field {

    protected string $view = 'media-manager::filament.forms.components.media-picker';

    protected string $returnType = 'id'; // 'id' o 'url'
    protected string $conversionName = 'webp'; // conversión a usar cuando returnType = 'url'

    protected function setUp(): void {

        parent::setUp();
        $this->dehydrated(true);
        $this->live(onBlur: false);

        // Reglas: siempre integer internamente (ID), conversión a URL solo al deshidratar
        //$this->rules(['nullable', 'integer']);

        $this->dehydrateStateUsing(function ($state) {
            if ($state === null || $state === '') {
                return null;
            }

            // En modo URL, si el estado ya es una URL string (recarga del form sin
            // tocar el picker), preservarla tal cual para no sobreescribirla con null.
            if ($this->returnType === 'url' && is_string($state) && !is_numeric($state)) {
                return $state;
            }

            $id = null;
            if (is_numeric($state)) {
                $id = (int) $state;
            } elseif (is_array($state)) {
                $id = isset($state['id']) ? (int) $state['id'] : null;
            } elseif (is_object($state) && isset($state->id)) {
                $id = (int) $state->id;
            }

            if (!$id) {
                return null;
            }

            if ($this->returnType === 'url') {
                return $this->getUrlFromState($id);
            }

            return $id;
        });
    }

    protected function getUrlFromState($state): ?string {
        $id = null;

        if (is_numeric($state)) {
            $id = (int) $state;
        } elseif (is_array($state)) {
            $id = isset($state['id']) ? (int) $state['id'] : null;
        } elseif (is_object($state) && isset($state->id)) {
            $id = (int) $state->id;
        }

        if (!$id) {
            return null;
        }

        $media = Media::find($id);
        if (!$media) {
            return null;
        }

        try {
            if ($media->hasGeneratedConversion($this->conversionName)) {
                return $media->getUrl($this->conversionName);
            }
            return $media->getUrl();
        } catch (\Throwable $e) {
            return null;
        }
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
            'label' => $this->getLabel(),
            'returnType' => $this->returnType,
            'conversionName' => $this->conversionName,
        ]);
    }

}
