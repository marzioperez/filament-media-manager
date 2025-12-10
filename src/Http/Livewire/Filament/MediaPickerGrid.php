<?php

namespace Marzio\MediaManager\Http\Livewire;

use Marzio\MediaManager\Models\MediaVault;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaPickerGrid extends Component {

    use WithPagination;

    public $preset = null;
    public string $hostId;
    public string $statePath;
    public $selected = null;

    public function mount($preset = null) {
        $this->applyPreset($preset);
    }

    protected function applyPreset($preset): void {
        if ($preset) {
            $media = Media::find($preset);
            if ($media) {
                $this->selected = $media->uuid;
            }
        }
    }

    public function updatedPreset($value): void {
        $this->applyPreset($value);
    }

    public function getItemsProperty() {
        return Media::query()->where('model_type', MediaVault::class)->where('model_id',1)->latest()->paginate(18);
    }

    public function toggle(string $uuid): void {
        $this->selected = $uuid;
    }

    public function confirm(): void {
        $ids = Media::where('uuid', $this->selected)->pluck('id', 'uuid');
        $this->dispatch('set-media-single', hostId: $this->hostId, statePath: $this->statePath, value: $ids[$this->selected]);
        $this->dispatch('close-picker');
    }

    public function render() {
        return view('media-manager::livewire.filament.media-picker-grid', ['media' => $this->items]);
    }

}
