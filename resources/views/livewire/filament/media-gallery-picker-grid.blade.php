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
                $thumb = $m->hasGeneratedConversion('webp') ? $m->getUrl('webp') : $m->getUrl();
                $isSelected = in_array($m->id, $selected, true);
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
                <img src="{{ $thumb }}" alt="{{ $m->file_name }}" class="media-grid-image border {{ $isSelected ? 'border-primary-500 border-2' : 'border-gray-200/60 dark:border-white/10' }} group-hover:opacity-90" />
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
