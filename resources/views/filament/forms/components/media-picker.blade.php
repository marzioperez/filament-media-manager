@php
    use Spatie\MediaLibrary\MediaCollections\Models\Media;

    $raw = $getState();
    $returnType = $returnType ?? 'id';
    $conversionName = $conversionName ?? 'webp';

    $id = null;
    $url = null;

    if ($returnType === 'url' && is_string($raw) && filter_var($raw, FILTER_VALIDATE_URL)) {
        $url = $raw;
        $media = Media::where(function ($q) use ($raw) {
            $q->whereRaw("CONCAT(disk, '/', id, '/', file_name) LIKE ?", ['%' . basename($raw) . '%']);
        })->first();
        $id = $media ? $media->id : null;
    } else {
        if (is_numeric($raw)) {
            $id = (int) $raw;
        } elseif (is_array($raw)) {
            $id = isset($raw['id']) ? (int) $raw['id'] : null;
        } elseif (is_object($raw) && isset($raw->id)) {
            $id = (int) $raw->id;
        }
    }

    $media = $id ? Media::find($id) : null;

    if (!$url && $media) {
        try {
            $url = $media->hasGeneratedConversion('webp') ? $media->getUrl('webp') : $media->getUrl();
        } catch (\Throwable $e) {
            $url = null;
        }
    }

    $fileName = $media?->file_name;
    $mimeType = $media?->mime_type;
    $sizeHuman = $media ? \Illuminate\Support\Number::fileSize($media->size) : null;
    $hasSelection = (bool) ($id || $url);

    // Detectar si el recurso es imagen. Preferir el mime de DB; si no hay
    // (p.ej. recarga del form con solo la URL en URL mode), inferir por
    // la extensión del URL.
    $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'avif', 'ico'];
    if ($mimeType) {
        $isImageType = str_starts_with($mimeType, 'image/');
    } elseif ($url) {
        $urlPath = parse_url($url, PHP_URL_PATH) ?: '';
        $urlExt = strtolower(pathinfo($urlPath, PATHINFO_EXTENSION));
        $isImageType = in_array($urlExt, $imageExts, true);
    } else {
        $isImageType = false;
    }

    // Nombre mostrable: usar el del media si existe, si no el basename del URL.
    $displayName = $fileName ?? ($url ? basename(parse_url($url, PHP_URL_PATH) ?: $url) : 'Recurso seleccionado');
@endphp

<div class="fi-fo-field-wrp"
     x-on:close-picker.window="$dispatch('close-modal', { id: 'media-picker-modal-{{ $getId() }}' })"
     x-on:set-media-single.window="
        if ($event.detail.hostId === '{{ $getLivewire()->getId() }}' && $event.detail.statePath === '{{ $getStatePath() }}') {
            $wire.set('{{ $getStatePath() }}', $event.detail.value);
            $dispatch('close-modal', { id: 'media-picker-modal-{{ $getId() }}' });
        }
     ">
    @if (filled($getLabel()))
        <div class="media-picker-label">
            <label class="fi-fo-field-lbl text-sm font-medium leading-6 text-gray-950 dark:text-white">
                {{ $getLabel() }}
            </label>
        </div>
    @endif

    <div class="media-picker-card @if($hasSelection) media-picker-card--filled @else media-picker-card--empty @endif"
         wire:key="preview-{{ $getId() }}-{{ $id ?? 'empty' }}"
         @if(!$hasSelection) role="button" tabindex="0"
            x-on:click="$dispatch('open-modal', { id: 'media-picker-modal-{{ $getId() }}' })"
            x-on:keydown.enter.prevent="$dispatch('open-modal', { id: 'media-picker-modal-{{ $getId() }}' })"
            x-on:keydown.space.prevent="$dispatch('open-modal', { id: 'media-picker-modal-{{ $getId() }}' })"
         @endif
    >
        @if ($url)
            <div class="mp-field-thumb">
                @if ($isImageType)
                    <img src="{{ $url }}" alt="{{ $displayName }}" loading="lazy" />
                @else
                    <div class="mp-field-thumb-fallback">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                    </div>
                @endif
            </div>

            <div class="media-picker-meta">
                <div class="media-picker-meta-name" title="{{ $displayName }}">
                    {{ $displayName }}
                </div>
                <div class="media-picker-meta-sub">
                    @if($mimeType)
                        <span class="media-picker-badge">{{ $mimeType }}</span>
                    @endif
                    @if($sizeHuman)
                        <span>{{ $sizeHuman }}</span>
                    @endif
                </div>
            </div>

            <button
                type="button"
                class="media-picker-clear"
                title="Quitar selección"
                wire:click="$set('{{ $getStatePath() }}', null);"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        @else
            <div class="media-picker-empty">
                <div class="media-picker-empty-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <circle cx="8.5" cy="8.5" r="1.5"/>
                        <polyline points="21 15 16 10 5 21"/>
                    </svg>
                </div>
                <div class="media-picker-empty-text">
                    <strong>Seleccionar recurso</strong>
                    <span>Haz clic para elegir una imagen de la biblioteca</span>
                </div>
            </div>
        @endif
    </div>

    <div class="media-picker-buttons">
        <x-filament::button
            size="sm"
            icon="heroicon-m-photo"
            x-on:click="$dispatch('open-modal', { id: 'media-picker-modal-{{ $getId() }}' })"
        >
            {{ $hasSelection ? 'Cambiar' : 'Seleccionar' }}
        </x-filament::button>

        @if ($hasSelection)
            <x-filament::button
                size="sm"
                color="gray"
                icon="heroicon-m-x-mark"
                wire:click="$set('{{ $getStatePath() }}', null);"
            >
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
