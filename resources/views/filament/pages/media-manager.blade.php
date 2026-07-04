<x-filament-panels::page>
    <div x-data="{ showUpload: true, selCount:0 }" x-on:selection-count-changed.window="selCount = $event.detail.count">

        <main class="space-y-4">
            <x-filament::section>
                <div class="flex items-center justify-between gap-3">
                    <div class="flex gap-2">
                        <x-filament::input.wrapper>
                            <x-filament::input placeholder="Buscar..." wire:model.debounce.500ms="search" @keyup.enter="$dispatch('media-search', { q: $event.target.value })" />
                        </x-filament::input.wrapper>
                    </div>

                    <div class="flex gap-2">
                        <x-filament::button color="primary" x-on:click="showUpload = !showUpload" icon="heroicon-m-arrow-up-tray">
                            Cargar archivos
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>

            <livewire:media-manager.media-folders
                :currentFolderId="$currentFolderId"
                wire:key="folders-{{ $currentFolderId ?? 'root' }}" />

            <template x-if="showUpload">
                <x-filament::section>
                    <div x-data
                         x-on:dragover.prevent
                         x-on:drop.prevent="$dispatch('trigger-upload-drop', { dt: $event.dataTransfer })">
                        <p class="text-sm font-medium mb-3">
                            Cargar archivos <span class="text-primary-600">*</span>
                            <span class="text-xs text-gray-500" x-cloak>
                                @if($this->currentFolderId)
                                    — se subirán a la carpeta actual
                                @else
                                    — se subirán a la raíz
                                @endif
                            </span>
                        </p>

                        <div class="relative">
                            <livewire:media-manager.media-bulk-uploader
                                :currentFolderId="$currentFolderId"
                                wire:key="uploader-{{ $currentFolderId ?? 'root' }}" />
                        </div>
                    </div>
                </x-filament::section>
            </template>

            <livewire:media-manager.media-grid
                :currentFolderId="$currentFolderId"
                :search="$search"
                :sort="$sort"
                wire:key="grid-{{ md5(($search ?? '') . '|' . ($sort ?? '') . '|' . ($currentFolderId ?? 'root')) }}"/>
        </main>

    </div>
</x-filament-panels::page>
