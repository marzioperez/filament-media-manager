{{-- Navegación de carpetas compacta para pickers (MediaPickerGrid / MediaGalleryPickerGrid). --}}
<div class="mm-picker-folder-nav">
    <nav class="mm-breadcrumbs" aria-label="Ruta de carpetas">
        <button type="button"
                wire:click="goToFolder"
                @class(['mm-crumb', 'mm-crumb--current' => ! $currentFolderId])>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="mm-crumb-icon">
                <path d="M9.293 2.293a1 1 0 0 1 1.414 0l7 7A1 1 0 0 1 17 11h-1v6a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-3H9v3a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1v-6H4a1 1 0 0 1-.707-1.707l7-7Z" />
            </svg>
            <span>Inicio</span>
        </button>

        @foreach($this->breadcrumbs as $crumb)
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="mm-crumb-sep">
                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd" />
            </svg>
            <button type="button"
                    wire:click="goToFolder({{ $crumb->id }})"
                    @class(['mm-crumb', 'mm-crumb--current' => $crumb->id === $currentFolderId])>
                {{ $crumb->name }}
            </button>
        @endforeach
    </nav>

    @if($this->folders->isNotEmpty())
        <div class="mm-picker-folder-chips">
            @foreach($this->folders as $folder)
                <button type="button"
                        wire:click="openFolder({{ $folder->id }})"
                        wire:key="mm-picker-folder-{{ $folder->id }}"
                        class="mm-picker-folder-chip"
                        title="Abrir «{{ $folder->name }}»">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="mm-picker-folder-chip-icon">
                        <path d="M2 6a2 2 0 0 1 2-2h5.172a2 2 0 0 1 1.414.586l1.828 1.828A2 2 0 0 0 13.828 7H20a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6Z" />
                    </svg>
                    <span>{{ $folder->name }}</span>
                </button>
            @endforeach
        </div>
    @endif
</div>
