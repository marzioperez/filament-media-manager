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

class MediaGalleryPickerGrid extends Component {

    use WithPagination, WithFileUploads;

    public array $selected = [];
    public string $search = '';
    public array $pickerFiles = [];
    public bool $isUploading = false;

    public int $perPage = 12;

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

        $this->dispatch('refresh-media-grid');
    }

    /**
     * Media objects for the selected IDs (for the preview strip)
     */
    public function getSelectedMediaProperty() {
        if (empty($this->selected)) {
            return collect();
        }

        $media = Media::query()->whereIn('id', $this->selected)->get()->keyBy('id');

        // Mantener el orden de selección
        return collect($this->selected)->map(function (int $id) use ($media) {
            $m = $media->get($id);
            if (!$m) return null;

            $isImage = str_starts_with($m->mime_type, 'image/');
            $thumb = null;
            if ($isImage) {
                try {
                    $thumb = $m->hasGeneratedConversion('thumb')
                        ? $m->getUrl('thumb')
                        : ($m->hasGeneratedConversion('webp') ? $m->getUrl('webp') : $m->getUrl());
                } catch (\Throwable $e) {}
            }

            return (object) [
                'id' => $m->id,
                'file_name' => $m->file_name,
                'mime_type' => $m->mime_type,
                'is_image' => $isImage,
                'thumb' => $thumb,
                'extension' => strtoupper(pathinfo($m->file_name, PATHINFO_EXTENSION)),
            ];
        })->filter()->values();
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

        return $query->latest('id')->paginate($this->perPage);
    }

    public function render() {
        return view('media-manager::livewire.filament.media-gallery-picker-grid', [
            'items' => $this->items,
            'selectedMedia' => $this->selectedMedia,
        ]);
    }

}
