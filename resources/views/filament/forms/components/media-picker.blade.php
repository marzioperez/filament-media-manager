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
            // Usar webp o la imagen original, NO usar thumb porque está recortada
            $url = $media->hasGeneratedConversion('webp') ? $media->getUrl('webp') : $media->getUrl();
        } catch (\Throwable $e) {
            $url = null;
        }
    }

    $isImage = $media ? str_starts_with($media->mime_type, 'image/') : ($url !== null);
    $isPdf = $media && ($media->mime_type === 'application/pdf' || \Illuminate\Support\Str::endsWith(strtolower($media->file_name ?? ''), '.pdf'));
    $isVideo = $media && str_starts_with($media->mime_type ?? '', 'video/');
    $isAudio = $media && str_starts_with($media->mime_type ?? '', 'audio/');
    $isSpreadsheet = $media && (str_contains($media->mime_type ?? '', 'spreadsheet') || str_contains($media->mime_type ?? '', 'excel') || in_array(strtolower(pathinfo($media->file_name ?? '', PATHINFO_EXTENSION)), ['xlsx', 'xls', 'csv']));
    $isWord = $media && (str_contains($media->mime_type ?? '', 'wordprocessing') || str_contains($media->mime_type ?? '', 'msword') || in_array(strtolower(pathinfo($media->file_name ?? '', PATHINFO_EXTENSION)), ['docx', 'doc']));
    $isZip = $media && (str_contains($media->mime_type ?? '', 'zip') || str_contains($media->mime_type ?? '', 'compressed') || str_contains($media->mime_type ?? '', 'archive'));
    $fileExtension = $media ? strtoupper(pathinfo($media->file_name ?? '', PATHINFO_EXTENSION)) : '';
@endphp

<div class="fi-fo-field"
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
        <div class="media-picker-preview fi-input border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-800 flex items-center justify-center"
             wire:key="preview-{{ $getId() }}-{{ $id ?? 'empty' }}">
            @if ($media && $isImage)
                <img
                    src="{{ $url }}"
                    alt="{{ $media->file_name }}"
                    class="h-32 w-32 object-cover rounded-lg border border-gray-200/60 dark:border-white/10 shadow-sm"
                />
            @elseif ($media && !$isImage)
                <div class="h-32 w-32 flex flex-col items-center justify-center gap-2 rounded-lg border border-gray-200/60 dark:border-white/10 bg-gray-50 dark:bg-gray-700 p-2">
                    @if ($isPdf)
                        <x-filament::icon icon="heroicon-o-document-text" class="h-10 w-10 text-red-500" />
                    @elseif ($isVideo)
                        <x-filament::icon icon="heroicon-o-film" class="h-10 w-10 text-purple-500" />
                    @elseif ($isAudio)
                        <x-filament::icon icon="heroicon-o-musical-note" class="h-10 w-10 text-blue-500" />
                    @elseif ($isSpreadsheet)
                        <x-filament::icon icon="heroicon-o-table-cells" class="h-10 w-10 text-green-500" />
                    @elseif ($isWord)
                        <x-filament::icon icon="heroicon-o-document" class="h-10 w-10 text-blue-600" />
                    @elseif ($isZip)
                        <x-filament::icon icon="heroicon-o-archive-box" class="h-10 w-10 text-yellow-600" />
                    @else
                        <x-filament::icon icon="heroicon-o-paper-clip" class="h-10 w-10 text-gray-400" />
                    @endif
                    <span class="text-[10px] text-gray-500 dark:text-gray-400 text-center truncate w-full leading-tight">{{ $media->file_name }}</span>
                </div>
            @else
                <div class="media-picker-empty-state bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                    Sin selección
                </div>
            @endif
        </div>
    </x-filament::input.wrapper>

    <div class="media-picker-buttons">
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
