@php
    use Spatie\MediaLibrary\MediaCollections\Models\Media;

    $raw = $getState();
    $returnType = $returnType ?? 'id';
    $conversionName = $conversionName ?? 'webp';

    $id = null;
    $url = null;

    // Si returnType es 'url' y el estado es una URL string
    if ($returnType === 'url' && is_string($raw) && filter_var($raw, FILTER_VALIDATE_URL)) {
        $url = $raw;
        // Intentar encontrar el ID a partir de la URL para el preview
        $media = Media::where(function($q) use ($raw) {
            $q->whereRaw("CONCAT(disk, '/', id, '/', file_name) LIKE ?", ['%' . basename($raw) . '%']);
        })->first();
        $id = $media ? $media->id : null;
    } else {
        // Modo ID o necesitamos extraer el ID
        if (is_numeric($raw)) {
            $id = (int) $raw;
        } elseif (is_array($raw)) {
            $id = isset($raw['id']) ? (int) $raw['id'] : null;
        } elseif (is_object($raw) && isset($raw->id)) {
            $id = (int) $raw->id;
        }
    }

    $media = $id ? Media::find($id) : null;

    // Si no tenemos URL pero tenemos media, obtenerla
    if (!$url && $media) {
        try {
            $url = $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media->getUrl();
        } catch (\Throwable $e) {
            $url = null;
        }
    }
@endphp

<div class="space-y-3" x-data="{ open: false }" x-on:close-picker.window="open = false;">
    @if (isset($label))
        <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $label }}</label>
        </div>
    @endif

    <x-filament::input.wrapper>
        <div class="fi-input rounded-xl border border-gray-300/60 dark:border-white/10 bg-white dark:bg-gray-800 p-4 flex items-center justify-center min-h-36">
            @if ($url)
                <img
                    src="{{ $url }}"
                    alt="{{ $media ? $media->file_name : 'Preview' }}"
                    class="h-32 w-32 object-cover rounded-lg border border-gray-200/60 dark:border-white/10"
                />
            @else
                <div class="h-32 w-32 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-xs text-gray-500">
                    Sin selección
                </div>
            @endif
        </div>
    </x-filament::input.wrapper>

    <div class="flex gap-2">
        <x-filament::button size="sm" x-data x-on:click="open = true">
            Seleccionar recurso
        </x-filament::button>

        @if ($id || $url)
            <x-filament::button size="sm" color="gray" wire:click="$set('{{ $getStatePath() }}', null);">
                Limpiar
            </x-filament::button>
        @endif
    </div>

    <div x-show="open"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background-color: rgba(0, 0, 0, 0.5);"
         x-on:click.self="open = false">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-5xl max-h-[90vh] overflow-hidden flex flex-col"
             x-on:click.stop>
            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Seleccionar recurso</h3>
                <button type="button"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition"
                        x-on:click="open=false">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-4">
                <livewire:media-manager.media-picker-grid lazy
                    multiple="false"
                    host-id="{{ $getLivewire()->getId() }}"
                    state-path="{{ $getStatePath() }}"
                    :preset="$id"
                    wire:key="picker-{{ $getId() }}-{{ (int) $id }}"
                />
            </div>
        </div>
    </div>
</div>
