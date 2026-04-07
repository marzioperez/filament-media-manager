<div x-data="{ selectedDetail: null }" class="media-picker-container">
    {{-- Barra de busqueda y upload --}}
    <div class="media-picker-toolbar">
        <div class="media-picker-search">
            <x-filament::input.wrapper>
                <x-filament::input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar por nombre de archivo..."
                />
            </x-filament::input.wrapper>
        </div>
        @if(count($selected) > 0)
            <x-filament::button color="gray" wire:click="clearSelection" size="sm">
                Limpiar selección ({{ count($selected) }})
            </x-filament::button>
        @endif
    </div>

    {{-- Tira de items seleccionados --}}
    @if($selectedMedia->isNotEmpty())
        <div class="media-gallery-selected-strip">
            <div class="media-gallery-selected-strip-header">
                <span class="text-xs font-medium text-gray-600 dark:text-gray-300">
                    {{ count($selected) }} {{ count($selected) === 1 ? 'seleccionado' : 'seleccionados' }}
                </span>
            </div>
            <div class="media-gallery-selected-strip-items">
                @foreach($selectedMedia as $sm)
                    <div class="media-gallery-selected-strip-item group" wire:key="sel-strip-{{ $sm->id }}">
                        @if($sm->is_image && $sm->thumb)
                            <img src="{{ $sm->thumb }}" alt="{{ $sm->file_name }}" class="media-gallery-selected-strip-img" />
                        @else
                            <div class="media-gallery-selected-strip-file">
                                <span class="text-[8px] font-semibold text-gray-500 dark:text-gray-400">{{ $sm->extension }}</span>
                            </div>
                        @endif
                        <button type="button"
                            wire:click="toggle({{ $sm->id }})"
                            class="media-gallery-selected-strip-remove">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3">
                                <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                            </svg>
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Zona de carga drag & drop --}}
    <div x-data="{ dragging: false }"
         x-on:dragover.prevent="dragging = true"
         x-on:dragleave.prevent="dragging = false"
         x-on:drop.prevent="
            dragging = false;
            const dt = new DataTransfer();
            for (const f of $event.dataTransfer.files) dt.items.add(f);
            $refs.galleryFileInput.files = dt.files;
            $refs.galleryFileInput.dispatchEvent(new Event('change', { bubbles: true }));
         "
         class="media-picker-dropzone"
         :class="{ 'media-picker-dropzone--active': dragging }"
    >
        <input x-ref="galleryFileInput" type="file" wire:model="pickerFiles" multiple class="hidden" />

        {{-- Loader de carga --}}
        <div wire:loading wire:target="pickerFiles" class="media-picker-uploading">
            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Subiendo archivos...</span>
        </div>

        <div wire:loading.remove wire:target="pickerFiles" class="media-picker-dropzone-content" @click="$refs.galleryFileInput.click()">
            <x-filament::icon icon="heroicon-o-cloud-arrow-up" class="media-picker-dropzone-icon" />
            <p class="media-picker-dropzone-text">Arrastra archivos aquí o <span class="media-picker-dropzone-link">haz clic para seleccionar</span></p>
            <p class="media-picker-dropzone-hint">PNG, JPG, WEBP, PDF, DOCX hasta 10MB</p>
        </div>
    </div>

    {{-- Contador de resultados --}}
    <div class="media-picker-results-info">
        <span class="text-xs text-gray-500 dark:text-gray-400">
            Se muestran de {{ $items->firstItem() ?? 0 }} a {{ $items->lastItem() ?? 0 }} de {{ $items->total() }} resultados
            @if(count($selected) > 0)
                · {{ count($selected) }} seleccionados
            @endif
        </span>
    </div>

    {{-- Layout principal: grid + sidebar --}}
    <div class="media-picker-layout">
        {{-- Grid de archivos --}}
        <div class="media-picker-grid-wrapper">
            <div class="media-picker-grid">
                @foreach($items as $m)
                    @php
                        $mIsImage = str_starts_with($m->mime_type, 'image/');
                        $mIsPdf = $m->mime_type === 'application/pdf' || \Illuminate\Support\Str::endsWith(strtolower($m->file_name), '.pdf');
                        $mIsVideo = str_starts_with($m->mime_type, 'video/');
                        $mIsAudio = str_starts_with($m->mime_type, 'audio/');
                        $mIsSpreadsheet = str_contains($m->mime_type, 'spreadsheet') || str_contains($m->mime_type, 'excel') || in_array(strtolower(pathinfo($m->file_name, PATHINFO_EXTENSION)), ['xlsx', 'xls', 'csv']);
                        $mIsWord = str_contains($m->mime_type, 'wordprocessing') || str_contains($m->mime_type, 'msword') || in_array(strtolower(pathinfo($m->file_name, PATHINFO_EXTENSION)), ['docx', 'doc']);
                        $mIsZip = str_contains($m->mime_type, 'zip') || str_contains($m->mime_type, 'compressed') || str_contains($m->mime_type, 'archive');
                        $isSelected = in_array($m->id, $selected, true);
                    @endphp
                    <div class="media-picker-grid-item {{ $isSelected ? 'media-picker-grid-item--selected' : '' }}"
                         @click="
                             $wire.toggle({{ $m->id }});
                             selectedDetail = {
                                 id: {{ $m->id }},
                                 uuid: '{{ $m->uuid }}',
                                 name: '{{ addslashes($m->file_name) }}',
                                 mime: '{{ $m->mime_type }}',
                                 isImage: {{ $mIsImage ? 'true' : 'false' }},
                                 url: '{{ $mIsImage ? ($m->hasGeneratedConversion('webp') ? $m->getFullUrl('webp') : $m->getFullUrl()) : $m->getFullUrl() }}',
                                 size: '{{ $m->size >= 1048576 ? number_format($m->size / 1048576, 2) . ' MB' : number_format($m->size / 1024, 2) . ' KB' }}',
                                 created: '{{ $m->created_at->format('d/m/Y H:i') }}',
                                 extension: '{{ strtoupper(pathinfo($m->file_name, PATHINFO_EXTENSION)) }}'
                             };
                         ">
                        @if($mIsImage)
                            <div class="media-picker-thumb">
                                <img src="{{ $m->hasGeneratedConversion('webp') ? $m->getUrl('webp') : $m->getUrl() }}" alt="{{ $m->file_name }}" loading="lazy" />
                            </div>
                        @else
                            <div class="media-picker-thumb media-picker-thumb--file">
                                @if($mIsPdf)
                                    <x-filament::icon icon="heroicon-o-document-text" class="h-8 w-8 text-red-500" />
                                @elseif($mIsVideo)
                                    <x-filament::icon icon="heroicon-o-film" class="h-8 w-8 text-purple-500" />
                                @elseif($mIsAudio)
                                    <x-filament::icon icon="heroicon-o-musical-note" class="h-8 w-8 text-blue-500" />
                                @elseif($mIsSpreadsheet)
                                    <x-filament::icon icon="heroicon-o-table-cells" class="h-8 w-8 text-green-500" />
                                @elseif($mIsWord)
                                    <x-filament::icon icon="heroicon-o-document" class="h-8 w-8 text-blue-600" />
                                @elseif($mIsZip)
                                    <x-filament::icon icon="heroicon-o-archive-box" class="h-8 w-8 text-yellow-600" />
                                @else
                                    <x-filament::icon icon="heroicon-o-paper-clip" class="h-8 w-8 text-gray-400" />
                                @endif
                                <span class="media-picker-thumb-label">{{ \Illuminate\Support\Str::limit($m->file_name, 20) }}</span>
                            </div>
                        @endif

                        {{-- Badge de selección (checkmark) --}}
                        @if($isSelected)
                            <div class="media-picker-selected-badge">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Paginación --}}
            <div class="media-picker-pagination">
                <x-filament::pagination :paginator="$items" />
            </div>
        </div>

        {{-- Sidebar de detalles --}}
        <div x-show="selectedDetail" x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 translate-x-4"
             class="media-picker-sidebar" x-cloak>
            <template x-if="selectedDetail">
                <div class="media-picker-sidebar-inner">
                    {{-- Header --}}
                    <div class="media-picker-sidebar-header">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Detalles</h3>
                        <button type="button" @click="selectedDetail = null" class="media-picker-sidebar-close">
                            <x-filament::icon icon="heroicon-o-x-mark" class="h-4 w-4" />
                        </button>
                    </div>

                    {{-- Preview grande --}}
                    <div class="media-picker-sidebar-preview">
                        <template x-if="selectedDetail.isImage">
                            <img :src="selectedDetail.url" :alt="selectedDetail.name" class="media-picker-sidebar-img" />
                        </template>
                        <template x-if="!selectedDetail.isImage">
                            <div class="media-picker-sidebar-file-icon">
                                <x-filament::icon icon="heroicon-o-document" class="h-12 w-12 text-gray-400" />
                                <span class="media-picker-sidebar-ext" x-text="selectedDetail.extension"></span>
                            </div>
                        </template>
                    </div>

                    {{-- Metadata --}}
                    <div class="media-picker-sidebar-meta">
                        <div class="media-picker-sidebar-meta-item">
                            <span class="media-picker-sidebar-meta-label">Nombre</span>
                            <p class="media-picker-sidebar-meta-value" x-text="selectedDetail.name"></p>
                        </div>
                        <div class="media-picker-sidebar-meta-item">
                            <span class="media-picker-sidebar-meta-label">Formato</span>
                            <p class="media-picker-sidebar-meta-value" x-text="selectedDetail.mime"></p>
                        </div>
                        <div class="media-picker-sidebar-meta-item">
                            <span class="media-picker-sidebar-meta-label">Peso</span>
                            <p class="media-picker-sidebar-meta-value" x-text="selectedDetail.size"></p>
                        </div>
                        <div class="media-picker-sidebar-meta-item">
                            <span class="media-picker-sidebar-meta-label">Subido</span>
                            <p class="media-picker-sidebar-meta-value" x-text="selectedDetail.created"></p>
                        </div>
                        <div class="media-picker-sidebar-meta-item">
                            <span class="media-picker-sidebar-meta-label">URL</span>
                            <a :href="selectedDetail.url" target="_blank" class="media-picker-sidebar-link">Ver archivo</a>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Botones de acción --}}
    <div class="media-picker-actions flex justify-end gap-2">
        <x-filament::button color="gray" x-on:click="$dispatch('close-gallery-picker')">Cancelar</x-filament::button>
        <x-filament::button wire:click="confirm" :disabled="count($selected) === 0" icon="heroicon-o-check">
            Agregar {{ count($selected) > 0 ? '(' . count($selected) . ')' : '' }}
        </x-filament::button>
    </div>
</div>
