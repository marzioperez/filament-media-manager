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

<div class="space-y-3"
     x-on:close-picker.window="$dispatch('close-modal', { id: 'media-picker-modal-{{ $getId() }}' })"
     x-on:set-media-single.window="
        if ($event.detail.hostId === '{{ $getLivewire()->getId() }}' && $event.detail.statePath === '{{ $getStatePath() }}') {
            $wire.set('{{ $getStatePath() }}', $event.detail.value);
            $dispatch('close-modal', { id: 'media-picker-modal-{{ $getId() }}' });
        }
     ">
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
        <x-filament::button size="sm" x-on:click="$dispatch('open-modal', { id: 'media-picker-modal-{{ $getId() }}' })">
            Seleccionar recurso
        </x-filament::button>

        @if ($id || $url)
            <x-filament::button size="sm" color="gray" wire:click="$set('{{ $getStatePath() }}', null);">
                Limpiar
            </x-filament::button>
        @endif
    </div>

    <x-filament::modal
        id="media-picker-modal-{{ $getId() }}"
        width="5xl"
    >
        <x-slot name="heading">
            Seleccionar recurso
        </x-slot>

        <livewire:media-manager.media-picker-grid lazy
            multiple="false"
            host-id="{{ $getLivewire()->getId() }}"
            state-path="{{ $getStatePath() }}"
            :preset="$id"
            wire:key="picker-{{ $getId() }}-{{ (int) $id }}"
        />
    </x-filament::modal>
</div>
