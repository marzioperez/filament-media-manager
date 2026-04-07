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

        setSelection(ids) {
            this.value = this.ensureKeys(
                (ids ?? []).map(id => ({ media_id: Number(id), _k: this.uid() }))
            );
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
        isVideo(row){ return (row?.mime ?? '').startsWith('video/') },
        isAudio(row){ return (row?.mime ?? '').startsWith('audio/') },
        isImage(row){
            const mime = row?.mime ?? '';
            return mime.startsWith('image/');
        },
        clearAll() {
            this.value = [];
        }
    }"
    x-init="value = ensureKeys(toRows(value))"
    @media-gallery-picked.window="setSelection($event.detail.ids); $dispatch('close-modal', { id: 'media-gallery-modal-{{ $getId() }}' })"
    @close-gallery-picker.window="$dispatch('close-modal', { id: 'media-gallery-modal-{{ $getId() }}' })"
    class="fi-fo-field space-y-3"
>
    <x-filament::input.wrapper>
        <div class="media-picker-preview fi-input border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-800 flex items-center justify-center"
             style="min-height: auto; padding: 0.75rem;">
            <template x-if="!value || value.length === 0">
                <div class="media-picker-empty-state bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                    Sin selección
                </div>
            </template>
            <template x-if="value && value.length > 0">
                <ul class="media-gallery-preview-grid">
                    <template x-for="(row, i) in value" :key="row._k">
                        <li class="relative group" draggable="true" @dragstart="startDrag(i)" @dragover.prevent @drop="drop(i)">
                            <div class="aspect-square rounded-lg border border-gray-200/60 dark:border-white/10 overflow-hidden flex items-center justify-center bg-gray-50 dark:bg-gray-900 cursor-grab active:cursor-grabbing">
                                <template x-if="isImage(row) && row?.url">
                                    <img :src="row.url" alt="" class="w-full h-full object-cover" />
                                </template>
                                <template x-if="isPdf(row)">
                                    <div class="flex flex-col items-center gap-1 p-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-8 w-8 text-red-500"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                                        <span class="text-[9px] text-gray-400">PDF</span>
                                    </div>
                                </template>
                                <template x-if="isVideo(row)">
                                    <div class="flex flex-col items-center gap-1 p-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-8 w-8 text-purple-500"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                        <span class="text-[9px] text-gray-400">Video</span>
                                    </div>
                                </template>
                                <template x-if="isAudio(row)">
                                    <div class="flex flex-col items-center gap-1 p-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-8 w-8 text-blue-500"><path stroke-linecap="round" stroke-linejoin="round" d="m9 9 10.5-3m0 6.553v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 1 1-.99-3.467l2.31-.66a2.25 2.25 0 0 0 1.632-2.163Zm0 0V2.25L9 5.25v10.303m0 0v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 0 1-.99-3.467l2.31-.66A2.25 2.25 0 0 0 9 15.553Z" /></svg>
                                        <span class="text-[9px] text-gray-400">Audio</span>
                                    </div>
                                </template>
                                <template x-if="!isImage(row) && !isPdf(row) && !isVideo(row) && !isAudio(row)">
                                    <div class="flex flex-col items-center gap-1 p-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-8 w-8 text-gray-400"><path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" /></svg>
                                        <span class="text-[9px] text-gray-400">Archivo</span>
                                    </div>
                                </template>
                            </div>
                            <button type="button" title="Eliminar"
                                class="absolute -top-1.5 -right-1.5 opacity-0 group-hover:opacity-100 transition inline-flex items-center justify-center w-6 h-6 rounded-full bg-white dark:bg-gray-900 border border-gray-200/60 dark:border-white/10 shadow-sm text-gray-500 hover:text-red-500 dark:text-gray-400 dark:hover:text-red-400"
                                @click.prevent="removeAt(i)">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" /></svg>
                            </button>
                        </li>
                    </template>
                </ul>
            </template>
        </div>
    </x-filament::input.wrapper>

    <div class="media-picker-buttons">
        <x-filament::button size="sm" x-on:click="
            $dispatch('open-modal', { id: 'media-gallery-modal-{{ $getId() }}' });
            setTimeout(() => {
                Livewire.dispatch('load-gallery-selection', { ids: (value || []).map(r => Number(r?.media_id)).filter(Boolean) });
            }, 350);
        ">
            Seleccionar recursos
        </x-filament::button>

        <x-filament::button size="sm" color="gray" x-show="value && value.length > 0" x-on:click="clearAll()" x-cloak>
            Limpiar
        </x-filament::button>
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
