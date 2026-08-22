<x-filament-panels::page>
    <div>
        <main class="space-y-4">
            <livewire:media-manager.media-folders
                :currentFolderId="$currentFolderId"
                wire:key="folders-{{ $currentFolderId ?? 'root' }}" />

            <livewire:media-manager.media-grid
                :currentFolderId="$currentFolderId"
                wire:key="grid-{{ $currentFolderId ?? 'root' }}" />
        </main>
    </div>
</x-filament-panels::page>
