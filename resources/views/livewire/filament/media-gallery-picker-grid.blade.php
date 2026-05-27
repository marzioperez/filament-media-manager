<div class="space-y-4">
    <div class="media-picker-toolbar">
        <div class="flex-1 media-picker-search-wrapper">
            <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                <x-filament::input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar por nombre de archivo..."
                />
            </x-filament::input.wrapper>
        </div>

        @if(count($selected) > 0)
            <div class="media-picker-selection-pill">
                <span>{{ count($selected) }} seleccionado{{ count($selected) === 1 ? '' : 's' }}</span>
                <button type="button" wire:click="clearSelection" title="Limpiar selección">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
        @endif
    </div>

    @if($items->isEmpty())
        <div class="media-picker-empty-results">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <circle cx="8.5" cy="8.5" r="1.5"/>
                <polyline points="21 15 16 10 5 21"/>
            </svg>
            <div>
                @if($search)
                    No se encontraron recursos para <strong>"{{ $search }}"</strong>.
                @else
                    Aún no hay recursos disponibles.
                @endif
            </div>
        </div>
    @else
        <div class="media-manager-grid grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
            @foreach ($items as $m)
                @php
                    $thumb = $m->hasGeneratedConversion('webp') ? $m->getUrl('webp') : $m->getUrl();
                    $isSelected = in_array($m->id, $selected, true);
                @endphp
                <label class="media-manager-grid-item @if($isSelected) media-manager-grid-item--selected @endif group relative block cursor-pointer">
                    <input
                        type="checkbox"
                        class="media-grid-checkbox"
                        value="{{ $m->id }}"
                        wire:model.live="selected"
                        @checked($isSelected)
                        wire:key="media-{{ $m->id }}"
                    >
                    <img
                        src="{{ $thumb }}"
                        alt="{{ $m->file_name }}"
                        loading="lazy"
                        class="media-grid-image"
                    />
                    <div class="media-grid-caption" title="{{ $m->file_name }}">
                        {{ $m->file_name }}
                    </div>
                    @if($isSelected)
                        <div class="media-grid-selected-overlay"></div>
                    @endif
                </label>
            @endforeach
        </div>

        <div class="media-picker-footer">
            <x-filament::pagination :paginator="$items" />
            <div class="flex gap-2">
                <x-filament::button color="gray" x-on:click="$dispatch('close-gallery-picker')">
                    Cancelar
                </x-filament::button>
                <x-filament::button
                    wire:click="confirm"
                    :disabled="count($selected) === 0"
                    icon="heroicon-m-check"
                >
                    Agregar ({{ count($selected) }})
                </x-filament::button>
            </div>
        </div>
    @endif
</div>
