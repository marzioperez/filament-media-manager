<div>
    <x-filament::section>
        {{-- Cabecera: breadcrumbs + acción crear carpeta --}}
        <div class="mm-folders-header">
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

            <x-filament::button
                color="gray"
                icon="heroicon-o-folder-plus"
                x-on:click="$dispatch('open-modal', { id: 'media-manager-create-folder' })">
                Nueva carpeta
            </x-filament::button>
        </div>

        {{-- Rejilla de carpetas --}}
        @if($this->folders->isNotEmpty())
            <div class="mm-folders-grid">
                @foreach($this->folders as $folder)
                    <div class="mm-folder-card" wire:key="mm-folder-{{ $folder->id }}">
                        <button type="button"
                                class="mm-folder-open"
                                wire:click="openFolder({{ $folder->id }})"
                                title="Abrir «{{ $folder->name }}»">
                            <span class="mm-folder-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M2 6a2 2 0 0 1 2-2h5.172a2 2 0 0 1 1.414.586l1.828 1.828A2 2 0 0 0 13.828 7H20a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6Z" />
                                </svg>
                            </span>
                            <span class="mm-folder-name">{{ $folder->name }}</span>
                        </button>

                        <button type="button"
                                class="mm-folder-delete"
                                wire:click="deleteFolder({{ $folder->id }})"
                                wire:confirm="¿Eliminar la carpeta «{{ $folder->name }}» y todo su contenido? Esta acción no se puede deshacer."
                                title="Eliminar carpeta">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                @endforeach
            </div>
        @else
            <p class="mm-folders-empty">Esta carpeta no tiene subcarpetas todavía.</p>
        @endif

        {{-- Modal: crear carpeta --}}
        <x-filament::modal id="media-manager-create-folder" width="md" :close-by-clicking-away="false">
            <x-slot name="heading">Crear carpeta</x-slot>
            <x-slot name="description">
                Se creará dentro de:
                <strong>{{ $this->currentFolder?->path ?? 'la raíz' }}</strong>
            </x-slot>

            <div class="space-y-2">
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        wire:model="newFolderName"
                        placeholder="Nombre de la carpeta"
                        wire:keydown.enter.prevent="createFolder"
                        autofocus />
                </x-filament::input.wrapper>

                @error('newFolderName')
                    <p class="text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                @enderror
            </div>

            <x-slot name="footerActions">
                <x-filament::button
                    color="gray"
                    x-on:click="$dispatch('close-modal', { id: 'media-manager-create-folder' })">
                    Cancelar
                </x-filament::button>
                <x-filament::button wire:click="createFolder" wire:loading.attr="disabled">
                    Crear carpeta
                </x-filament::button>
            </x-slot>
        </x-filament::modal>
    </x-filament::section>
</div>
