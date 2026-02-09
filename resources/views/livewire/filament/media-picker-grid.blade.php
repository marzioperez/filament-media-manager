<div class="space-y-6">
    <div class="media-picker-search-wrapper">
        <x-filament::input.wrapper>
            <x-filament::input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Buscar por nombre de archivo..."
            />
        </x-filament::input.wrapper>
    </div>

    <div class="media-manager-grid fi-sc fi-sc-has-gap fi-grid sm:fi-grid-cols xl:fi-grid-cols 2xl:fi-grid-cols" style="--cols-default: repeat(1, minmax(0, 1fr)); --cols-sm: repeat(3, minmax(0, 1fr)); --cols-xl: repeat(4, minmax(0, 1fr)); --cols-2xl: repeat(6, minmax(0, 1fr));">
        @foreach($media as $m)
            <label class="media-manager-grid-item block overflow-hidden cursor-pointer group relative">
                <input type="checkbox" class="sr-only" value="{{ $m->uuid }}" @checked($m->uuid == $selected) wire:click="toggle('{{ $m->uuid }}')">
                <img src="{{ $m->hasGeneratedConversion('webp') ? $m->getUrl('webp') : $m->getUrl() }}" alt="" class="media-grid-image" />
                @if($m->uuid == $selected)
                    <div class="media-grid-selected-overlay"></div>
                @endif
            </label>
        @endforeach
    </div>

    <x-filament::pagination :paginator="$media" />

    <div>
        <x-filament::button wire:click="confirm" :disabled="!$selected">Usar seleccionado</x-filament::button>
    </div>
</div>
