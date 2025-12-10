<div class="space-y-3">
    <div>
        <x-filament::button color="gray" wire:click="clearSelection" x-data>
            Limpiar selección ({{ count($selected) }})
        </x-filament::button>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
        @foreach ($items as $m)
            @php
                $thumb = $m->hasGeneratedConversion('thumb') ? $m->getUrl('thumb') : $m->getUrl();
            @endphp
            <label class="group relative block cursor-pointer">
                <input
                    type="checkbox"
                    class="absolute left-2 top-2 z-10 h-4 w-4 rounded border-gray-300"
                    value="{{ $m->id }}"
                    wire:model.live="selected"
                    @checked(in_array($m->id, $selected, true))
                    wire:key="media-{{ $m->id }}"
                >
                <img src="{{ $thumb }}" alt="{{ $m->file_name }}" class="h-28 w-full object-cover rounded border border-gray-200/60 dark:border-white/10 group-hover:opacity-90" />
            </label>
        @endforeach
    </div>

    <x-filament::pagination :paginator="$items" />

    <div class="flex justify-end gap-2">
        <x-filament::button color="gray" x-on:click="$dispatch('close-gallery-picker')">Cancelar</x-filament::button>
        <x-filament::button wire:click="confirm">Agregar</x-filament::button>
    </div>
</div>
