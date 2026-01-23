<div class="space-y-6">
    <div class="space-y-4">
        <x-filament::input.wrapper>
            <x-filament::input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Buscar por nombre de archivo..."
            />
        </x-filament::input.wrapper>
    </div>

    <div class="fi-sc fi-sc-has-gap fi-grid  sm:fi-grid-cols xl:fi-grid-cols 2xl:fi-grid-cols" style="--cols-default: repeat(1, minmax(0, 1fr)); --cols-sm: repeat(3, minmax(0, 1fr)); --cols-xl: repeat(4, minmax(0, 1fr)); --cols-2xl: repeat(6, minmax(0, 1fr));">
        @foreach($media as $m)
            <label class="block rounded-lg overflow-hidden cursor-pointer group relative">
                <input type="checkbox" class="sr-only" value="{{ $m->uuid }}" @checked($m->uuid == $selected) wire:click="toggle('{{ $m->uuid }}')">
                <img src="{{ $m->hasGeneratedConversion('thumb') ? $m->getUrl('thumb') : $m->getUrl() }}" alt="" class="aspect-square object-cover w-full" />
                @if($m->uuid == $selected)
                    <div class="absolute inset-0 bg-primary-500/20 border-2 border-primary-500 rounded-lg"></div>
                @endif
            </label>
        @endforeach
    </div>

    <x-filament::pagination :paginator="$media" />

    <div>
        <x-filament::button wire:click="confirm" :disabled="!$selected">Usar seleccionado</x-filament::button>
    </div>
</div>
