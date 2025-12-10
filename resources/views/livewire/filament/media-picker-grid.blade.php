<div class="space-y-6">
    <div class="fi-sc fi-sc-has-gap fi-grid  sm:fi-grid-cols xl:fi-grid-cols 2xl:fi-grid-cols" style="--cols-default: repeat(1, minmax(0, 1fr)); --cols-sm: repeat(3, minmax(0, 1fr)); --cols-xl: repeat(4, minmax(0, 1fr)); --cols-2xl: repeat(6, minmax(0, 1fr));">
        @foreach($media as $m)
            <label class="block rounded-lg overflow-hidden cursor-pointer group">
                <input type="checkbox" class="sr-only" value="{{ $m->uuid }}" @checked($m->uuid == $selected) wire:click="toggle('{{ $m->uuid }}')">
                <img src="{{ $m->hasGeneratedConversion('thumb') ? $m->getUrl('thumb') : $m->getUrl() }}" alt="" class="aspect-square object-cover w-full" />
            </label>
        @endforeach
    </div>
    <x-filament::pagination :paginator="$media" />
    <div>
        <x-filament::button wire:click="confirm" :disabled="!$selected">Usar seleccionados</x-filament::button>
    </div>
</div>
