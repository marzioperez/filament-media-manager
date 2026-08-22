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
        labelFor(row){
            return row?.name ?? row?.file_name ?? ('Recurso #' + (row?.media_id ?? ''));
        },
        clearAll() {
            this.value = [];
        }
    }"
    x-init="value = ensureKeys(toRows(value))"
    @media-gallery-picked.window="add($event.detail.ids); $dispatch('close-modal', { id: 'media-gallery-modal-{{ $getId() }}' })"
    @close-gallery-picker.window="$dispatch('close-modal', { id: 'media-gallery-modal-{{ $getId() }}' })"
    class="media-gallery-field space-y-3"
>
    <div class="media-gallery-toolbar">
        <div class="media-gallery-toolbar-info">
            <span class="media-gallery-toolbar-count" x-text="(Array.isArray(value) ? value.length : 0) + ' recurso' + ((Array.isArray(value) && value.length === 1) ? '' : 's')"></span>
        </div>
        <div class="flex gap-2">
            <x-filament::button
                size="sm"
                icon="heroicon-m-plus"
                x-on:click="$dispatch('open-modal', { id: 'media-gallery-modal-{{ $getId() }}' })"
            >
                <span x-text="(Array.isArray(value) && value.length > 0) ? 'Agregar más' : 'Seleccionar recursos'"></span>
            </x-filament::button>
            <x-filament::button
                size="sm"
                color="gray"
                icon="heroicon-m-x-mark"
                x-show="Array.isArray(value) && value.length > 0"
                x-on:click="clearAll()"
            >
                Limpiar
            </x-filament::button>
        </div>
    </div>

    <template x-if="!Array.isArray(value) || value.length === 0">
        <div
            class="media-gallery-empty"
            role="button"
            tabindex="0"
            x-on:click="$dispatch('open-modal', { id: 'media-gallery-modal-{{ $getId() }}' })"
            x-on:keydown.enter.prevent="$dispatch('open-modal', { id: 'media-gallery-modal-{{ $getId() }}' })"
            x-on:keydown.space.prevent="$dispatch('open-modal', { id: 'media-gallery-modal-{{ $getId() }}' })"
        >
            <div class="media-picker-empty-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                    <circle cx="8.5" cy="8.5" r="1.5"/>
                    <polyline points="21 15 16 10 5 21"/>
                </svg>
            </div>
            <div class="media-picker-empty-text">
                <strong>Seleccionar recursos</strong>
                <span>Haz clic para elegir uno o varios archivos de la biblioteca</span>
            </div>
        </div>
    </template>

    <div x-show="Array.isArray(value) && value.length > 0">
        <ul class="media-gallery-grid">
            <template x-for="(row, i) in value" :key="row._k">
                <li class="media-gallery-item"
                    draggable="true"
                    @dragstart="startDrag(i)"
                    @dragover.prevent
                    @drop="drop(i)"
                    :class="{ 'media-gallery-item--dragging': dragIndex === i }"
                >
                    <div class="media-gallery-thumb">
                        <template x-if="isPdf(row)">
                            <div class="media-gallery-thumb-fallback">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                </svg>
                                <span>PDF</span>
                            </div>
                        </template>
                        <template x-if="!isPdf(row) && row?.url">
                            <img :src="row.url" :alt="labelFor(row)" loading="lazy" />
                        </template>
                        <template x-if="!isPdf(row) && !row?.url">
                            <div class="media-gallery-thumb-fallback">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                    <polyline points="21 15 16 10 5 21"/>
                                </svg>
                                <span x-text="'#' + (row?.media_id ?? '')"></span>
                            </div>
                        </template>
                    </div>
                    <div class="media-gallery-item-caption" :title="labelFor(row)" x-text="labelFor(row)"></div>

                    <span class="media-gallery-drag-handle" title="Arrastrar para reordenar" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <circle cx="9" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/>
                            <circle cx="15" cy="6" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="15" cy="18" r="1.5"/>
                        </svg>
                    </span>

                    <button
                        type="button"
                        title="Quitar"
                        class="media-gallery-remove"
                        @click.prevent="removeAt(i)"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
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
