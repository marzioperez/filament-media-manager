<?php

namespace Marzio\MediaManager\Http\Livewire\Filament;

use Marzio\MediaManager\Models\MediaVault;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaGalleryPickerGrid extends Component {

    use WithPagination;
    public array $selected = [];
    public string $search = '';

    public int $perPage = 24;

    public function updatingSearch(): void {
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
        $query = Media::query()
            ->where('model_type', MediaVault::class)
            ->where('model_id', 1);

        // Aplicar búsqueda
        if ($this->search) {
            $query->where(function($q) {
                $s = '%' . $this->search . '%';
                $q->where('file_name', 'like', $s)
                  ->orWhere('name', 'like', $s)
                  ->orWhere('mime_type', 'like', $s);
            });
        }

        // Ordenar: seleccionados primero, luego los más recientes
        if (!empty($this->selected)) {
            $query->orderByRaw('FIELD(id, ' . implode(',', array_map('intval', $this->selected)) . ') DESC');
        }

        return $query->latest('id')->paginate(18);
    }

    public function render() {
        return view('media-manager::livewire.filament.media-gallery-picker-grid', ['items' => $this->items]);
    }

}
