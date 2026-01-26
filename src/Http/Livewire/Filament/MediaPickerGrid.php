<?php

namespace Marzio\MediaManager\Http\Livewire\Filament;

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
    public string $search = '';

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

    public function updatingSearch(): void {
        $this->resetPage();
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

        // Ordenar: preseleccionado (preset) primero solo al cargar inicialmente
        if ($this->preset && !$this->search) {
            $presetId = is_numeric($this->preset) ? $this->preset : null;
            if (!$presetId) {
                $presetMedia = Media::where('uuid', $this->preset)->first();
                $presetId = $presetMedia?->id;
            }
            if ($presetId) {
                $query->orderByRaw('id = ? DESC', [$presetId]);
            }
        }

        return $query->latest('id')->paginate(12);
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
