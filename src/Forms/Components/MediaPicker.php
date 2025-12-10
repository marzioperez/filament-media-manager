<?php

namespace Marzio\MediaManager\Forms\Components;

use Filament\Forms\Components\Field;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaPicker extends Field {

    protected string $view = 'media-manager::filament.forms.components.media-picker';

    protected function setUp(): void {

        parent::setUp();
        $this->dehydrated(true);
        $this->rules(['nullable', 'integer']);

        $this->afterStateUpdated(function ($state, callable $set) {
            $id = null;
            if (is_numeric($state)) {
                $id = (int) $state;
            } elseif (is_array($state)) {
                $id = isset($state['id']) ? (int) $state['id'] : null;
            } elseif (is_object($state) && isset($state->id)) {
                $id = (int) $state->id;
            } elseif (is_string($state)) {
                $id = Media::query()->where('uuid', $state)->value('id');
                $id = $id ? (int) $id : null;
            }

            $set($this->getName(), $id);
        });

        $this->dehydrateStateUsing(function ($state) {
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

    public function getViewData(): array {
        return array_merge(parent::getViewData(), [
            'label' => $this->getLabel(),
        ]);
    }

}
