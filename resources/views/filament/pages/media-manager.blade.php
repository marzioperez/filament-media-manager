<x-filament-panels::page>
    <div x-data="{ showUpload: true, selCount: 0 }" x-on:selection-count-changed.window="selCount = $event.detail.count">

        <main class="space-y-4">
            <x-filament::section>
                <div class="media-manager-toolbar">
                    <div class="flex-1 media-picker-search-wrapper">
                        <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                            <x-filament::input
                                placeholder="Buscar recursos..."
                                wire:model.debounce.500ms="search"
                                @keyup.enter="$dispatch('media-search', { q: $event.target.value })"
                            />
                        </x-filament::input.wrapper>
                    </div>

                    <div class="flex gap-2">
                        <x-filament::button
                            color="primary"
                            x-on:click="showUpload = !showUpload"
                            icon="heroicon-m-arrow-up-tray"
                        >
                            <span x-text="showUpload ? 'Ocultar carga' : 'Cargar archivos'"></span>
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>

            <template x-if="showUpload">
                <x-filament::section heading="Cargar archivos">
                    <livewire:media-manager.media-bulk-uploader :currentFolderId="$currentFolderId" />
                </x-filament::section>
            </template>

            <livewire:media-manager.media-grid
                :currentFolderId="$currentFolderId"
                :search="$search"
                :sort="$sort"
                wire:key="grid-{{ md5(($search ?? '') . '|' . ($sort ?? '') . '|' . ($currentFolderId ?? 'root')) }}"
            />
        </main>

    </div>
</x-filament-panels::page>
