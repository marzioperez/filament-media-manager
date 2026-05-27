<div x-data="{ selected: null }" class="fi-sc fi-sc-has-gap fi-grid sm:fi-grid-cols xl:fi-grid-cols 2xl:fi-grid-cols"
     style="--cols-default: repeat(1, minmax(0, 1fr)); --cols-sm: repeat(3, minmax(0, 1fr)); --cols-xl: repeat(12, minmax(0, 1fr)); --cols-2xl: repeat(12, minmax(0, 1fr));">

    <div class="fi-grid-col lg:fi-grid-col-span" x-transition
         :style="selected ? '--col-span-default: span 1 / span 1; --col-span-lg: span 8 / span 8;' : '--col-span-default: span 1 / span 1; --col-span-lg: span 12 / span 12;'">
        <x-filament::section heading="Recursos multimedia">
            <div class="fi-sc-component">
                <div class="space-y-6">
                    @if($media->isEmpty())
                        <div class="media-picker-empty-results">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                <polyline points="21 15 16 10 5 21"/>
                            </svg>
                            <div>Aún no hay recursos en la biblioteca.</div>
                        </div>
                    @else
                        <div class="media-manager-main-grid fi-sc fi-sc-has-gap fi-grid sm:fi-grid-cols xl:fi-grid-cols 2xl:fi-grid-cols"
                             style="--cols-default: repeat(1, minmax(0, 1fr)); --cols-sm: repeat(3, minmax(0, 1fr)); --cols-xl: repeat(4, minmax(0, 1fr)); --cols-2xl: repeat(4, minmax(0, 1fr));">
                            @foreach($media as $m)
                                @php
                                    $isImage = str_starts_with($m->mime_type, 'image/');
                                    $isPdf = $m->mime_type === 'application/pdf' || \Illuminate\Support\Str::endsWith(strtolower($m->file_name), '.pdf');
                                    $sizeHuman = $m->size >= 1048576
                                        ? number_format($m->size / 1048576, 2) . ' MB'
                                        : number_format($m->size / 1024, 2) . ' KB';
                                    $previewUrl = $isImage
                                        ? ($m->hasGeneratedConversion('webp') ? $m->getFullUrl('webp') : $m->getUrl())
                                        : $m->getUrl();
                                @endphp
                                <div
                                    class="media-manager-grid-item media-manager-grid-item--clickable"
                                    :class="selected && selected.id === {{ $m->id }} ? 'media-manager-grid-item--active' : ''"
                                    @click="selected = {
                                        id: {{ $m->id }},
                                        uuid: '{{ $m->uuid }}',
                                        name: @js($m->file_name),
                                        mime: '{{ $m->mime_type }}',
                                        url: '{{ $previewUrl }}',
                                        size: '{{ $sizeHuman }}',
                                        created: '{{ $m->created_at->format('d/m/Y H:i') }}',
                                        isImage: {{ $isImage ? 'true' : 'false' }},
                                        isPdf: {{ $isPdf ? 'true' : 'false' }}
                                    }"
                                >
                                    <div class="media-manager-grid-thumb">
                                        @if($isImage)
                                            <img src="{{ $previewUrl }}" alt="{{ $m->file_name }}" loading="lazy" class="media-grid-image" />
                                        @elseif($isPdf)
                                            <div class="media-manager-grid-thumb-fallback media-manager-grid-thumb-fallback--pdf">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                    <polyline points="14 2 14 8 20 8"/>
                                                    <line x1="9" y1="15" x2="15" y2="15"/>
                                                    <line x1="9" y1="18" x2="13" y2="18"/>
                                                </svg>
                                                <span>PDF</span>
                                            </div>
                                        @else
                                            <div class="media-manager-grid-thumb-fallback">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                    <polyline points="14 2 14 8 20 8"/>
                                                </svg>
                                                <span>{{ strtoupper(pathinfo($m->file_name, PATHINFO_EXTENSION)) ?: 'ARCHIVO' }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="media-grid-caption" title="{{ $m->file_name }}">
                                        {{ $m->file_name }}
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div>
                            <x-filament::pagination :paginator="$media" />
                        </div>
                    @endif
                </div>
            </div>
        </x-filament::section>
    </div>

    <div x-show="selected" x-transition class="fi-grid-col lg:fi-grid-col-span"
         style="--col-span-default: span 1 / span 1; --col-span-lg: span 4 / span 4;">
        <x-filament::section heading="Detalles del recurso">
            <template x-if="selected">
                <div class="media-detail-panel">
                    <div class="media-detail-preview">
                        <template x-if="selected.isImage">
                            <img :src="selected.url" :alt="selected.name" />
                        </template>
                        <template x-if="!selected.isImage">
                            <div class="media-detail-preview-fallback">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                </svg>
                                <span x-text="selected.mime"></span>
                            </div>
                        </template>
                    </div>

                    <dl class="media-detail-list">
                        <div>
                            <dt>Nombre</dt>
                            <dd x-text="selected.name"></dd>
                        </div>
                        <div>
                            <dt>Formato</dt>
                            <dd><span class="media-picker-badge" x-text="selected.mime"></span></dd>
                        </div>
                        <div>
                            <dt>Peso</dt>
                            <dd x-text="selected.size"></dd>
                        </div>
                        <div>
                            <dt>Fecha</dt>
                            <dd x-text="selected.created"></dd>
                        </div>
                        <div>
                            <dt>URL pública</dt>
                            <dd>
                                <a :href="selected.url" target="_blank" rel="noopener" class="media-detail-link" x-text="selected.url"></a>
                            </dd>
                        </div>
                    </dl>

                    <div class="media-detail-actions">
                        <x-filament::button color="gray" @click="selected = null" icon="heroicon-m-x-mark">
                            Cerrar
                        </x-filament::button>
                        <x-filament::button
                            color="danger"
                            icon="heroicon-m-trash"
                            x-on:click="if(confirm('¿Eliminar este recurso?')) { $wire.deleteSelected(selected.id); selected = null }"
                        >
                            Eliminar
                        </x-filament::button>
                    </div>
                </div>
            </template>
        </x-filament::section>
    </div>
</div>
