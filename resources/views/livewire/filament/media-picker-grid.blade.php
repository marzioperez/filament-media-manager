<div class="space-y-4">
    <div class="media-picker-search-wrapper">
        <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
            <x-filament::input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Buscar por nombre de archivo..."
            />
        </x-filament::input.wrapper>
    </div>

    @if($media->isEmpty())
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
        <div class="media-manager-grid fi-sc fi-sc-has-gap fi-grid sm:fi-grid-cols xl:fi-grid-cols 2xl:fi-grid-cols"
             style="--cols-default: repeat(1, minmax(0, 1fr)); --cols-sm: repeat(3, minmax(0, 1fr)); --cols-xl: repeat(4, minmax(0, 1fr)); --cols-2xl: repeat(6, minmax(0, 1fr));">
            @foreach($media as $m)
                <label class="media-manager-grid-item block overflow-hidden cursor-pointer group relative">
                    <input type="checkbox" class="sr-only" value="{{ $m->uuid }}" @checked($m->uuid == $selected) wire:click="toggle('{{ $m->uuid }}')">
                    <img
                        src="{{ $m->hasGeneratedConversion('webp') ? $m->getUrl('webp') : $m->getUrl() }}"
                        alt="{{ $m->file_name }}"
                        loading="lazy"
                        class="media-grid-image"
                    />
                    <div class="media-grid-caption" title="{{ $m->file_name }}">
                        {{ $m->file_name }}
                    </div>
                    @if($m->uuid == $selected)
                        <div class="media-grid-selected-overlay"></div>
                    @endif
                </label>
            @endforeach
        </div>

        <div class="media-picker-footer">
            <x-filament::pagination :paginator="$media" />
            <x-filament::button
                wire:click="confirm"
                :disabled="!$selected"
                icon="heroicon-m-check"
            >
                Usar seleccionado
            </x-filament::button>
        </div>
    @endif
</div>
