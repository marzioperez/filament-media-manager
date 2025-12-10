<?php

namespace Marzio\MediaManager\Http\Livewire;

use App\Models\MediaVault;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaGalleryPickerGrid extends Component {

    use WithPagination;
    public array $selected = [];

    public int $perPage = 24;

    public function updatingQ(): void {
        $this->resetPage();
    }

    public function toggle(int $id): void {
        if (in_array($id, $this->selected, true)) {
            $this->selected = array_values(array_diff($this->selected, [$id]));
        } else {
            $this->selected[] = $id;
        }
    }

    public function clearSelection(): void {
        $this->selected = [];
    }

    public function confirm(): void {
        $this->dispatch('media-gallery-picked', ids: $this->selected);
        $this->selected = [];
    }

    public function getItemsProperty() {
        return Media::query()->where('model_type', MediaVault::class)->where('model_id', 1)->latest()->paginate(18);
    }

    public function render() {
        return view('media-manager::livewire.filament.media-gallery-picker-grid', ['items' => $this->items]);
    }

}
