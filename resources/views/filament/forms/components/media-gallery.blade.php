@php
    $initial = $getState() ?? [];
    $preset  = collect($initial)->map(fn($v)=> is_array($v) ? (int)($v['media_id'] ?? null) : (int)$v)->filter()->values()->all();
@endphp

<div
    x-data="{
        open: false,
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
    @media-gallery-picked.window="add($event.detail.ids); open = false"
    @close-gallery-picker.window="open=false"
    class="space-y-3"
>
    <div class="flex gap-2">
        <x-filament::button size="sm" x-on:click="open = true">Seleccionar recursos</x-filament::button>
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

    <div x-show="open"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background-color: rgba(0, 0, 0, 0.5);"
         x-on:click.self="open = false">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-5xl max-h-[90vh] overflow-hidden flex flex-col"
             x-on:click.stop>
            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Seleccionar recursos</h3>
                <button type="button"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition"
                        x-on:click="open = false">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-4">
                <livewire:media-manager.media-gallery-picker-grid wire:key="gallery-picker" lazy/>
            </div>
        </div>
    </div>
</div>
