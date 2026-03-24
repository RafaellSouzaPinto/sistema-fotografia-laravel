<div class="card-rosa mb-4">
    <h2 style="font-family: 'Playfair Display', serif; font-style: italic; font-weight: 600; font-size: 20px; color: #4a2c3d; margin-bottom: 20px;">
        Fotos do trabalho
    </h2>

    <!-- Área de upload -->
    <div wire:loading.class="opacity-50" wire:target="arquivos">
        <label for="upload-fotos-{{ $trabalhoId }}" class="area-upload" 
               x-data
               @dragover.prevent="$el.classList.add('dragover')"
               @dragleave.prevent="$el.classList.remove('dragover')"
               @drop.prevent="$el.classList.remove('dragover')">
            <i class="bi bi-cloud-arrow-up"></i>
            <p style="font-size: 16px; color: #8c6b7d; margin: 0 0 4px;">Arraste as fotos aqui ou clique para selecionar</p>
            <small style="color: #8c6b7d; font-size: 13px;">Formatos aceitos: JPG, PNG, PSD, TIF · Máx. 200MB por arquivo</small>
        </label>
        <input type="file"
               id="upload-fotos-{{ $trabalhoId }}"
               wire:model="arquivos"
               multiple
               accept=".jpg,.jpeg,.png,.psd,.tif,.tiff"
               style="display: none;">
    </div>

    <!-- Progresso -->
    <div wire:loading wire:target="arquivos" style="margin-top: 12px;">
        <div class="progress-upload">
            <div class="progress-upload-bar" style="width: 100%;"></div>
        </div>
        <small style="color: #4a2c3d; font-size: 14px;">Enviando fotos...</small>
    </div>

    <!-- Grid de thumbnails -->
    @if($fotos->isNotEmpty())
        <div class="grid-fotos-admin mt-3">
            @foreach($fotos as $foto)
                <div class="foto-admin-wrapper">
                    @if($foto->drive_thumbnail && str_starts_with($foto->drive_thumbnail, 'http'))
                        <img src="{{ $foto->drive_thumbnail }}"
                             alt="{{ $foto->nome_arquivo }}"
                             class="foto-thumb"
                             loading="lazy"
                             onerror="this.src='/img/placeholder.svg'">
                    @elseif(str_starts_with($foto->drive_arquivo_id, 'fotos/'))
                        <img src="{{ asset('storage/' . $foto->drive_arquivo_id) }}"
                             alt="{{ $foto->nome_arquivo }}"
                             class="foto-thumb"
                             loading="lazy">
                    @else
                        <div class="foto-thumb skeleton" title="{{ $foto->nome_arquivo }}"></div>
                    @endif
                    <button wire:click="removerFoto({{ $foto->id }})"
                            wire:confirm="Remover esta foto?"
                            class="foto-remove-btn"
                            title="Remover foto">✕</button>
                </div>
            @endforeach
        </div>
        <p style="font-size: 14px; color: #8c6b7d; margin-top: 12px;">{{ $fotos->count() }} foto(s) enviada(s)</p>
    @endif
</div>
