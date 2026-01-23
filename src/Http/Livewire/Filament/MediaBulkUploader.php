<?php

namespace Marzio\MediaManager\Http\Livewire\Filament;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class MediaBulkUploader extends Component {

    use WithFileUploads;

    public ?int $currentFolderId = null;
    public array $files = [];

    public function updatedFiles(): void {
        $disk = 'private';
        $dir = 'tmp-media';
        $items = [];
        foreach ($this->files as $file) {
            // 1) Obtener nombre original del cliente
            $original = $file->getClientOriginalName();
            $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
            $base = pathinfo($original, PATHINFO_FILENAME);

            // 2) Sanitizar el nombre (solo el base, preservando extensión)
            $safeBase = Str::slug($base, '-');
            if ($safeBase === '') {
                $safeBase = 'file';
            }
            $candidate = $safeBase . ($ext ? ('.' . $ext) : '');

            // 3) Asegurar unicidad en el disco temporal
            $i = 0;
            while (Storage::disk($disk)->exists($dir . '/' . $candidate)) {
                $i++;
                $candidate = $safeBase . '-' . $i . ($ext ? ('.' . $ext) : '');
            }

            // 4) Guardar como el nombre "original" (sanitizado + único)
            $relative = $file->storeAs($dir, $candidate, $disk);

            // 5) Enviar a la Page: incluimos disk, path y el nombre original para S3
            $items[] = [
                'disk'     => $disk,
                'path'     => $relative,  // p.ej. tmp-media/imagen-1.png
                'original' => $candidate,  // nombre a conservar en S3
            ];
        }
        $this->dispatch('upload-finished', uploads: $items);

        $this->reset('files');
    }

    public function render() {
        return view('media-manager::livewire.filament.media-bulk-uploader');
    }
}
