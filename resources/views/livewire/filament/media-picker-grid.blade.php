<div x-data="{ selectedDetail: null }" class="space-y-4">
    {{-- Barra de búsqueda y botón de subida --}}
    <div class="flex items-center gap-3">
        <div class="flex-1">
            <x-filament::input.wrapper>
                <x-filament::input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar por nombre de archivo..."
                />
            </x-filament::input.wrapper>
        </div>
        <div x-data @dragover.prevent @drop.prevent="
            const dt = new DataTransfer();
            for (const f of $event.dataTransfer.files) dt.items.add(f);
            $refs.pickerFileInput.files = dt.files;
            $refs.pickerFileInput.dispatchEvent(new Event('change', { bubbles: true }));
        ">
            <input x-ref="pickerFileInput" type="file" wire:model="pickerFiles" multiple class="hidden" />
            <x-filament::button size="sm" color="gray" icon="heroicon-o-arrow-up-tray" @click="$refs.pickerFileInput.click()">
                Subir archivos
            </x-filament::button>
        </div>
    </div>

    {{-- Loader de carga --}}
    <div wire:loading wire:target="pickerFiles" class="flex items-center gap-2 rounded-lg bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800 px-4 py-3 text-sm text-primary-700 dark:text-primary-300">
        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Cargando archivos...
    </div>

    {{-- Layout con grid y sidebar --}}
    <div class="flex gap-4">
        {{-- Grid de archivos --}}
        <div class="flex-1 min-w-0" :class="selectedDetail ? '' : ''">
            <div class="media-manager-grid fi-sc fi-sc-has-gap fi-grid sm:fi-grid-cols xl:fi-grid-cols 2xl:fi-grid-cols"
                 :style="selectedDetail
                    ? '--cols-default: repeat(1, minmax(0, 1fr)); --cols-sm: repeat(2, minmax(0, 1fr)); --cols-xl: repeat(3, minmax(0, 1fr)); --cols-2xl: repeat(4, minmax(0, 1fr));'
                    : '--cols-default: repeat(1, minmax(0, 1fr)); --cols-sm: repeat(3, minmax(0, 1fr)); --cols-xl: repeat(4, minmax(0, 1fr)); --cols-2xl: repeat(6, minmax(0, 1fr));'
                 ">
                @foreach($media as $m)
                    @php
                        $mIsImage = str_starts_with($m->mime_type, 'image/');
                        $mIsPdf = $m->mime_type === 'application/pdf' || \Illuminate\Support\Str::endsWith(strtolower($m->file_name), '.pdf');
                        $mIsVideo = str_starts_with($m->mime_type, 'video/');
                        $mIsAudio = str_starts_with($m->mime_type, 'audio/');
                        $mIsSpreadsheet = str_contains($m->mime_type, 'spreadsheet') || str_contains($m->mime_type, 'excel') || in_array(strtolower(pathinfo($m->file_name, PATHINFO_EXTENSION)), ['xlsx', 'xls', 'csv']);
                        $mIsWord = str_contains($m->mime_type, 'wordprocessing') || str_contains($m->mime_type, 'msword') || in_array(strtolower(pathinfo($m->file_name, PATHINFO_EXTENSION)), ['docx', 'doc']);
                        $mIsZip = str_contains($m->mime_type, 'zip') || str_contains($m->mime_type, 'compressed') || str_contains($m->mime_type, 'archive');
                    @endphp
                    <div class="media-manager-grid-item block overflow-hidden cursor-pointer group relative"
                         @click="
                             $wire.toggle('{{ $m->uuid }}');
                             selectedDetail = (selectedDetail && selectedDetail.uuid === '{{ $m->uuid }}') ? null : {
                                 id: {{ $m->id }},
                                 uuid: '{{ $m->uuid }}',
                                 name: '{{ addslashes($m->file_name) }}',
                                 mime: '{{ $m->mime_type }}',
                                 isImage: {{ $mIsImage ? 'true' : 'false' }},
                                 url: '{{ $mIsImage ? ($m->hasGeneratedConversion('webp') ? $m->getFullUrl('webp') : $m->getUrl()) : $m->getUrl() }}',
                                 size: '{{ $m->size >= 1048576 ? number_format($m->size / 1048576, 2) . ' MB' : number_format($m->size / 1024, 2) . ' KB' }}',
                                 created: '{{ $m->created_at->format('d/m/Y H:i') }}',
                                 extension: '{{ strtoupper(pathinfo($m->file_name, PATHINFO_EXTENSION)) }}'
                             };
                         ">
                        @if($mIsImage)
                            <img src="{{ $m->hasGeneratedConversion('webp') ? $m->getUrl('webp') : $m->getUrl() }}" alt="" class="media-grid-image" />
                        @else
                            <div class="w-full aspect-square flex flex-col items-center justify-center rounded bg-gray-50 dark:bg-gray-800 p-3 gap-2">
                                @if($mIsPdf)
                                    <x-filament::icon icon="heroicon-o-document-text" class="h-10 w-10 text-red-500" />
                                @elseif($mIsVideo)
                                    <x-filament::icon icon="heroicon-o-film" class="h-10 w-10 text-purple-500" />
                                @elseif($mIsAudio)
                                    <x-filament::icon icon="heroicon-o-musical-note" class="h-10 w-10 text-blue-500" />
                                @elseif($mIsSpreadsheet)
                                    <x-filament::icon icon="heroicon-o-table-cells" class="h-10 w-10 text-green-500" />
                                @elseif($mIsWord)
                                    <x-filament::icon icon="heroicon-o-document" class="h-10 w-10 text-blue-600" />
                                @elseif($mIsZip)
                                    <x-filament::icon icon="heroicon-o-archive-box" class="h-10 w-10 text-yellow-600" />
                                @else
                                    <x-filament::icon icon="heroicon-o-paper-clip" class="h-10 w-10 text-gray-400" />
                                @endif
                                <span class="text-[10px] text-gray-500 dark:text-gray-400 text-center truncate w-full leading-tight">{{ $m->file_name }}</span>
                            </div>
                        @endif
                        @if($m->uuid == $selected)
                            <div class="media-grid-selected-overlay"></div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                <x-filament::pagination :paginator="$media" />
            </div>
        </div>

        {{-- Sidebar de detalles --}}
        <div x-show="selectedDetail" x-transition.origin.right class="w-72 shrink-0 border-l border-gray-200 dark:border-white/10 pl-4" x-cloak>
            <template x-if="selectedDetail">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Detalles</h3>
                        <button type="button" @click="selectedDetail = null" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <x-filament::icon icon="heroicon-o-x-mark" class="h-4 w-4" />
                        </button>
                    </div>

                    {{-- Preview --}}
                    <div class="rounded-lg overflow-hidden border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-gray-800 flex items-center justify-center" style="min-height: 10rem;">
                        <template x-if="selectedDetail.isImage">
                            <img :src="selectedDetail.url" :alt="selectedDetail.name" class="max-w-full max-h-48 object-contain" />
                        </template>
                        <template x-if="!selectedDetail.isImage">
                            <div class="flex flex-col items-center gap-2 p-4 text-center">
                                <x-filament::icon icon="heroicon-o-document" class="h-12 w-12 text-gray-400" />
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 bg-gray-200 dark:bg-gray-700 rounded px-2 py-0.5" x-text="selectedDetail.extension"></span>
                            </div>
                        </template>
                    </div>

                    {{-- Metadata --}}
                    <div class="space-y-2 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400 text-xs">Nombre</span>
                            <p class="text-gray-900 dark:text-white break-all text-xs" x-text="selectedDetail.name"></p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400 text-xs">Formato</span>
                            <p class="text-gray-900 dark:text-white text-xs" x-text="selectedDetail.mime"></p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400 text-xs">Peso</span>
                            <p class="text-gray-900 dark:text-white text-xs" x-text="selectedDetail.size"></p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400 text-xs">Subido</span>
                            <p class="text-gray-900 dark:text-white text-xs" x-text="selectedDetail.created"></p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400 text-xs">URL</span>
                            <a :href="selectedDetail.url" target="_blank" class="text-primary-600 dark:text-primary-400 underline text-xs break-all block">Ver archivo</a>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Botón de confirmar --}}
    <div>
        <x-filament::button wire:click="confirm" :disabled="!$selected">Usar seleccionado</x-filament::button>
    </div>
</div>
