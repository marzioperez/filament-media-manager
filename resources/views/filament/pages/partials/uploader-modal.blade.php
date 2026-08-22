<div>
    <livewire:media-manager.media-bulk-uploader
        :currentFolderId="$currentFolderId"
        wire:key="uploader-modal-{{ $currentFolderId ?? 'root' }}" />
</div>
