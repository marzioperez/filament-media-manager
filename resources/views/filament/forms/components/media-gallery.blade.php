@php
    $initial = $getState() ?? [];
    $preset  = collect($initial)->map(fn($v)=> is_array($v) ? (int)($v['media_id'] ?? null) : (int)$v)->filter()->values()->all();
@endphp

<div
    x-data="{
        value: {{ $applyStateBindingModifiers("\$wire.entangle('{$getStatePath()}')") }},

        uid(){
            return (window.crypto && crypto.randomUUID)
                ? crypto.randomUUID()
                : ('k-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2));
        },
        ensureKeys(arr){
            const rows = Array.isArray(arr) ? arr : [];
            return rows.map(r => (r && r._k) ? r : { ...(r || {}), _k: this.uid() });
        },

        toRows(arr) {
            let rows = Array.isArray(arr) ? arr : [];
            if (rows.length && Number.isInteger(rows[0])) {
                rows = rows.map(id => ({ media_id: Number(id) }));
            }
            rows = rows.map(r => typeof r === 'number' ? { media_id: r } : r);
            return this.ensureKeys(rows);
        },

        add(ids) {
            const current = this.toRows(this.value);
            const toAdd = (ids ?? []).map(id => ({ media_id: Number(id), _k: this.uid() }));
            this.value = this.ensureKeys(current.concat(toAdd));
        },

        dragIndex: null,
        startDrag(i){ this.dragIndex = i },
        drop(i){
            if(this.dragIndex === null || this.dragIndex === i) return;
            const rows = Array.isArray(this.value) ? this.value.slice() : [];
            const moved = rows.splice(this.dragIndex, 1)[0];
            rows.splice(i, 0, moved);
            this.value = rows;
            this.dragIndex = null;
        },
        removeAt(i){
            const rows = Array.isArray(this.value) ? this.value.slice() : [];
            rows.splice(i, 1);
            this.value = rows;
        },
        isPdf(row){ return (row?.mime ?? '').startsWith('application/pdf') },
        clearAll() {
            this.value = [];
        }
    }"
    x-init="value = ensureKeys(toRows(value))"
    @media-gallery-picked.window="add($event.detail.ids); $dispatch('close-modal', { id: 'media-gallery-modal-{{ $getId() }}' })"
    @close-gallery-picker.window="$dispatch('close-modal', { id: 'media-gallery-modal-{{ $getId() }}' })"
    class="space-y-3"
>
    <div class="flex gap-2">
        <x-filament::button size="sm" x-on:click="$dispatch('open-modal', { id: 'media-gallery-modal-{{ $getId() }}' })">Seleccionar recursos</x-filament::button>
        <x-filament::button size="sm" color="gray" x-show="value.length" x-on:click="clearAll()">Limpiar</x-filament::button>
    </div>

    <div>
        <ul class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
            <template x-for="(row, i) in value" :key="row._k">
                <li class="relative group" draggable="true" @dragstart="startDrag(i)" @dragover.prevent @drop="drop(i)">
                    <div class="aspect-square rounded border border-gray-200/60 dark:border-white/10 overflow-hidden flex items-center justify-center bg-gray-50 dark:bg-gray-900">
                        <template x-if="isPdf(row)">
                            <div class="text-xs text-gray-600 dark:text-gray-300">PDF #<span x-text="row.media_id"></span></div>
                        </template>
                        <template x-if="!isPdf(row)">
                            <img :src="row?.url" alt="" class="w-full h-full object-cover" />
                        </template>
                    </div>
                    <button type="button" title="Eliminar" class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition inline-flex items-center justify-center w-7 h-7 rounded-full bg-white/90 dark:bg-gray-900/90 border border-gray-200/60 dark:border-white/10" @click.prevent="removeAt(i)">✕</button>
                </li>
            </template>
        </ul>
    </div>

    <x-filament::modal
        id="media-gallery-modal-{{ $getId() }}"
        width="5xl"
    >
        <x-slot name="heading">
            Seleccionar recursos
        </x-slot>

        <livewire:media-manager.media-gallery-picker-grid wire:key="gallery-picker-{{ $getId() }}" lazy/>
    </x-filament::modal>
</div>
