<?php

namespace Marzio\MediaManager\Http\Livewire\Filament;

use Livewire\Attributes\On;
use Marzio\MediaManager\Models\MediaVault;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaGrid extends Component {

    use WithPagination;

    public string $search = '';
    public string $sort   = 'latest';
    public ?int $currentFolderId = null;
    public array $selected = [];
    public ?int $moveToFolderId = null;

    protected $listeners = ['folder-changed' => '$refresh', 'refresh-media-grid' => '$refresh'];

    public function updatingSearch() {
        $this->resetPage();
    }

    #[On('media-search')]
    public function handleMediaSearch($q): void {
        $this->search = $q;
        $this->resetPage();
    }

    public function getItemsProperty() {
        return Media::query()
            ->where('model_type', MediaVault::class)
            ->where('model_id', 1)
            ->when($this->currentFolderId, fn($q)=>$q->where('media_folder_id', $this->currentFolderId))
            ->when($this->search, fn($q) => $q->where(function($qq){
                $s = "%".$this->search."%";
                $qq->where('file_name','like',$s)->orWhere('mime_type','like',$s);
            }))
            ->latest()->paginate(12);
    }

    public function moveSelected(): void {
        if (! count($this->selected)) return;
        Media::query()->whereIn('uuid', $this->selected)->update(['media_folder_id' => $this->moveToFolderId]);
        $this->reset(['selected','moveToFolderId']);
        $this->dispatch('refresh-media-grid');
        session()->flash('success','Archivos movidos.');
    }

    public function deleteSelected(int $id): void {
        if ($m = Media::find($id)) {
            $m->delete();
        }
        $this->resetPage();
    }

    public function render() {
        return view('media-manager::livewire.filament.media-grid', ['media' => $this->items]);
    }

}
