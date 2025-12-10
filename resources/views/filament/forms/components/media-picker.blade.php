@php
    use Spatie\MediaLibrary\MediaCollections\Models\Media;

    $raw = $getState();

    $id = null;
    if (is_numeric($raw)) {
        $id = (int) $raw;
    } elseif (is_array($raw)) {
        $id = isset($raw['id']) ? (int) $raw['id'] : null;
    } elseif (is_object($raw) && isset($raw->id)) {
        $id = (int) $raw->id;
    }

    $media = $id ? Media::find($id) : null;
@endphp

<div class="space-y-3" x-data="{ open: false }" x-on:close-picker.window="open = false;">
    @if (isset($label))
        <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $label }}</label>
        </div>
    @endif

    <x-filament::input.wrapper>
        <div class="fi-input rounded-xl border border-gray-300/60 dark:border-white/10 bg-white dark:bg-gray-800 p-4 flex items-center justify-center min-h-36">
            @if ($media)
                <img
                    src="{{ $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media->getUrl() }}"
                    alt="{{ $media->file_name }}"
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

        @if ($id)
            <x-filament::button size="sm" color="gray" wire:click="$set('{{ $getStatePath() }}', null);">
                Limpiar
            </x-filament::button>
        @endif
    </div>

    <div x-show="open" x-cloak class="fixed inset-0 z-50 bg-black/40 flex">
        <div class="m-auto dark:bg-gray-900 bg-white rounded-xl p-4 w-[92vw] max-w-5xl space-y-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-lg font-semibold">Seleccionar recurso</h3>
                <button type="button" class="fi-btn" x-on:click="open=false">✕</button>
            </div>

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
