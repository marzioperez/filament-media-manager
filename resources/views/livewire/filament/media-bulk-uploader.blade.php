<div
    x-data="{ isDragging: false }"
    @dragover.prevent="isDragging = true"
    @dragleave="isDragging = false"
    @drop.prevent="
        isDragging = false;
        const dt = new DataTransfer();
        for (const f of $event.dataTransfer.files) dt.items.add(f);
        $refs.file.files = dt.files;
        $refs.file.dispatchEvent(new Event('change', { bubbles: true }));
    "
    class="media-uploader-dropzone"
    :class="{ 'media-uploader-dropzone--active': isDragging }"
>
    <input x-ref="file" type="file" wire:model="files" multiple style="display: none;" />

    <div class="media-uploader-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="17 8 12 3 7 8"/>
            <line x1="12" y1="3" x2="12" y2="15"/>
        </svg>
    </div>

    <div class="media-uploader-text">
        <strong x-text="isDragging ? 'Suelta los archivos aquí' : 'Arrastra y suelta tus archivos para cargarlos'"></strong>
        <span class="text-sm">
            o
            <button type="button" class="media-uploader-browse" @click="$refs.file.click()">examínalos</button>
        </span>
    </div>

    <div wire:loading wire:target="files" class="media-uploader-loading">
        <svg class="animate-spin" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="12" y1="2" x2="12" y2="6"/>
            <line x1="12" y1="18" x2="12" y2="22"/>
            <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"/>
            <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"/>
            <line x1="2" y1="12" x2="6" y2="12"/>
            <line x1="18" y1="12" x2="22" y2="12"/>
            <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"/>
            <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"/>
        </svg>
        <span>Subiendo archivos...</span>
    </div>
</div>
