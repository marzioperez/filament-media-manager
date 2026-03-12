<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div class="flex-1 media-picker-search-wrapper">
            <x-filament::input.wrapper>
                <x-filament::input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar por nombre de archivo..."
                />
            </x-filament::input.wrapper>
        </div>
        @if(count($selected) > 0)
            <x-filament::button color="gray" wire:click="clearSelection" size="sm">
                Limpiar selección ({{ count($selected) }})
            </x-filament::button>
        @endif
    </div>

    <div class="media-manager-grid grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
        @foreach ($items as $m)
            @php
                $mIsImage = str_starts_with($m->mime_type, 'image/');
                $thumb = $mIsImage ? ($m->hasGeneratedConversion('webp') ? $m->getUrl('webp') : $m->getUrl()) : null;
                $isSelected = in_array($m->id, $selected, true);
                $mIsPdf = $m->mime_type === 'application/pdf' || \Illuminate\Support\Str::endsWith(strtolower($m->file_name), '.pdf');
                $mIsVideo = str_starts_with($m->mime_type, 'video/');
                $mIsAudio = str_starts_with($m->mime_type, 'audio/');
                $mIsSpreadsheet = str_contains($m->mime_type, 'spreadsheet') || str_contains($m->mime_type, 'excel') || in_array(strtolower(pathinfo($m->file_name, PATHINFO_EXTENSION)), ['xlsx', 'xls', 'csv']);
                $mIsWord = str_contains($m->mime_type, 'wordprocessing') || str_contains($m->mime_type, 'msword') || in_array(strtolower(pathinfo($m->file_name, PATHINFO_EXTENSION)), ['docx', 'doc']);
                $mIsZip = str_contains($m->mime_type, 'zip') || str_contains($m->mime_type, 'compressed') || str_contains($m->mime_type, 'archive');
            @endphp
            <label class="media-manager-grid-item group relative block cursor-pointer">
                <input
                    type="checkbox"
                    class="media-grid-checkbox"
                    value="{{ $m->id }}"
                    wire:model.live="selected"
                    @checked($isSelected)
                    wire:key="media-{{ $m->id }}"
                >
                @if($mIsImage)
                    <img src="{{ $thumb }}" alt="{{ $m->file_name }}" class="media-grid-image border {{ $isSelected ? 'border-primary-500 border-2' : 'border-gray-200/60 dark:border-white/10' }} group-hover:opacity-90" />
                @else
                    <div class="w-full aspect-square flex flex-col items-center justify-center rounded bg-gray-50 dark:bg-gray-800 p-3 gap-2 border {{ $isSelected ? 'border-primary-500 border-2' : 'border-gray-200/60 dark:border-white/10' }} group-hover:opacity-90">
                        @if($mIsPdf)
                            <x-filament::icon icon="heroicon-o-document-text" class="h-10 w-10 text-red-500" />
                        @elseif($mIsVideo)
                            <x-filament::icon icon="heroicon-o-film" class="h-10 w-10 text-purple-500" />
                        @elseif($mIsAudio)
                            <x-filament::icon icon="heroicon-o-musical-note" class="h-10 w-10 text-blue-500" />
                        @elseif($mIsSpreadsheet)
                            <x-filament::icon icon="heroicon-o-table-cells" class="h-10 w-10 text-green-500" />
                        @elseif($mIsWord)
                            <x-filament::icon icon="heroicon-o-document" class="h-10 w-10 text-blue-600" />
                        @elseif($mIsZip)
                            <x-filament::icon icon="heroicon-o-archive-box" class="h-10 w-10 text-yellow-600" />
                        @else
                            <x-filament::icon icon="heroicon-o-paper-clip" class="h-10 w-10 text-gray-400" />
                        @endif
                        <span class="text-[10px] text-gray-500 dark:text-gray-400 text-center truncate w-full leading-tight">{{ $m->file_name }}</span>
                    </div>
                @endif
                @if($isSelected)
                    <div class="media-grid-selected-overlay"></div>
                @endif
            </label>
        @endforeach
    </div>

    <x-filament::pagination :paginator="$items" />

    <div class="flex justify-end gap-2">
        <x-filament::button color="gray" x-on:click="$dispatch('close-gallery-picker')">Cancelar</x-filament::button>
        <x-filament::button wire:click="confirm" :disabled="count($selected) === 0">
            Agregar ({{ count($selected) }})
        </x-filament::button>
    </div>
</div>
