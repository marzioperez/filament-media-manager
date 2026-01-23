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
        $this->live();

        // Reglas dinámicas según returnType
        $this->rules(fn () => $this->returnType === 'url' ? ['nullable', 'string'] : ['nullable', 'integer']);

        $this->afterStateUpdated(function ($state, callable $set) {
            // Si returnType es 'url', no normalizar a ID
            if ($this->returnType === 'url') {
                return;
            }

            // Solo normalizar a ID si returnType es 'id'
            $id = null;
            if (is_numeric($state)) {
                $id = (int) $state;
            } elseif (is_array($state)) {
                $id = isset($state['id']) ? (int) $state['id'] : null;
            } elseif (is_object($state) && isset($state->id)) {
                $id = (int) $state->id;
            } elseif (is_string($state) && !filter_var($state, FILTER_VALIDATE_URL)) {
                // Solo convertir UUID a ID si no es una URL
                $id = Media::query()->where('uuid', $state)->value('id');
                $id = $id ? (int) $id : null;
            }

            if ($id !== null) {
                $set($this->getName(), $id);
            }
        });

        $this->dehydrateStateUsing(function ($state) {
            if ($this->returnType === 'url') {
                return $this->getUrlFromState($state);
            }

            // Modo ID (default)
            if (is_numeric($state)) {
                return (int) $state;
            }
            if (is_array($state)) {
                return isset($state['id']) ? (int) $state['id'] : null;
            }
            if (is_object($state) && isset($state->id)) {
                return (int) $state->id;
            }
            if (is_string($state)) {
                $id = Media::query()->where('uuid', $state)->value('id');
                return $id ? (int) $id : null;
            }
            return null;
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
