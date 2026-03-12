<?php

namespace Marzio\MediaManager\Http\Livewire\Filament;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Marzio\MediaManager\Models\MediaVault;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaPickerGrid extends Component {

    use WithPagination, WithFileUploads;

    public $preset = null;
    public string $hostId;
    public string $statePath;
    public $selected = null;
    public string $search = '';
    public array $pickerFiles = [];
    public bool $isUploading = false;

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

    public function updatedPickerFiles(): void {
        $this->isUploading = true;

        $vault = MediaVault::firstOrCreate(['id' => 1]);
        $disk = 'private';
        $dir = 'tmp-media';

        foreach ($this->pickerFiles as $file) {
            $original = $file->getClientOriginalName();
            $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
            $base = pathinfo($original, PATHINFO_FILENAME);
            $safeBase = Str::slug($base, '-');
            if ($safeBase === '') {
                $safeBase = 'file';
            }
            $candidate = $safeBase . ($ext ? ('.' . $ext) : '');

            $i = 0;
            while (Storage::disk($disk)->exists($dir . '/' . $candidate)) {
                $i++;
                $candidate = $safeBase . '-' . $i . ($ext ? ('.' . $ext) : '');
            }

            $relative = $file->storeAs($dir, $candidate, $disk);

            $vault
                ->addMediaFromDisk($relative, $disk)
                ->usingFileName($candidate)
                ->usingName(pathinfo($candidate, PATHINFO_FILENAME))
                ->toMediaCollection('assets', 'media-manager');

            Storage::disk($disk)->delete($relative);
        }

        $this->reset('pickerFiles');
        $this->isUploading = false;
        $this->resetPage();

        Notification::make()
            ->title('Archivos cargados correctamente')
            ->success()
            ->send();

        // También refrescar la grid principal si está abierta detrás del modal
        $this->dispatch('refresh-media-grid');
    }

    public function render() {
        return view('media-manager::livewire.filament.media-picker-grid', ['media' => $this->items]);
    }

}
