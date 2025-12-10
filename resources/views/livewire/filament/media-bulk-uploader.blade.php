<div
    x-data
    @dragover.prevent="$el.classList.add('ring-2','ring-primary-600')"
    @dragleave="$el.classList.remove('ring-2','ring-primary-600')"
    @drop.prevent="
        $el.classList.remove('ring-2','ring-primary-600');
        const dt = new DataTransfer();
        for (const f of $event.dataTransfer.files) dt.items.add(f);
        $refs.file.files = dt.files;
        $refs.file.dispatchEvent(new Event('change', { bubbles: true }));
    "
    class="rounded-xl border dark:border-gray-500 dark:bg-gray-800 bg-white border-dashed p-6 flex items-center justify-center h-36"
>
    <input x-ref="file" type="file" wire:model="files" multiple style="display: none;" />

    <div class="text-center text-sm opacity-70">
        Arrastra y suelta tus archivos para cargarlos
        <span class="opacity-50"> o </span>
        <button type="button" class="text-primary-500 underline" @click="$refs.file.click()">Examinar</button>
    </div>
</div>
