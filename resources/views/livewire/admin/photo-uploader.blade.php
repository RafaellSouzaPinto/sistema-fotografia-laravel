<div class="card-rosa mb-4">
    <style>
        @keyframes spin-upload { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .spin-upload { display: inline-block; animation: spin-upload 1s linear infinite; }
    </style>
    <h2 style="font-family: 'Playfair Display', serif; font-style: italic; font-weight: 600; font-size: 20px; color: #4a2c3d; margin-bottom: 20px;">
        Fotos do trabalho
    </h2>

    {{-- wire:ignore preserva o estado Alpine durante os re-renders do Livewire --}}
    <div wire:ignore
         x-data="{
             lotes: [],
             loteAtual: 0,
             totalLotes: 0,
             fotosEnviadas: 0,
             fotosFalhas: 0,
             fotosTotal: 0,
             enviando: false,
             cancelado: false,
             concluido: false,
             erros: [],

             get porcentagem() {
                 if (this.fotosTotal === 0) return 0;
                 return Math.round((this.fotosEnviadas + this.fotosFalhas) / this.fotosTotal * 100);
             },

             get numLoteAtual() {
                 return Math.min(this.loteAtual + 1, this.totalLotes);
             },

             init() {
                 $wire.on('loteProcessado', (payload) => {
                     const d = Array.isArray(payload) ? payload[0] : payload;
                     this.fotosEnviadas += d.processadas ?? 0;
                     const falhas = d.falhas ?? [];
                     this.fotosFalhas += falhas.length;
                     this.erros.push(...falhas);
                     this.loteAtual++;
                     if (!this.cancelado && this.loteAtual < this.totalLotes) {
                         this.enviarProximoLote();
                     } else {
                         this.finalizarUpload();
                     }
                 });
             },

             selecionarArquivos(event) {
                 const files = Array.from(event.target.files);
                 if (!files.length) return;
                 event.target.value = '';
                 this.fotosTotal = files.length;
                 this.fotosEnviadas = 0;
                 this.fotosFalhas = 0;
                 this.loteAtual = 0;
                 this.cancelado = false;
                 this.concluido = false;
                 this.erros = [];
                 this.lotes = [];
                 for (let i = 0; i < files.length; i += 5) {
                     this.lotes.push(files.slice(i, i + 5));
                 }
                 this.totalLotes = this.lotes.length;
                 this.enviando = true;
                 window.dispatchEvent(new CustomEvent('foto-upload-iniciado'));
                 this.enviarProximoLote();
             },

             enviarProximoLote() {
                 const lote = this.lotes[this.loteAtual];
                 $wire.uploadMultiple('arquivos', lote,
                     () => {},
                     () => {
                         lote.forEach(f => this.erros.push(f.name));
                         this.fotosFalhas += lote.length;
                         this.loteAtual++;
                         if (!this.cancelado && this.loteAtual < this.totalLotes) {
                             this.enviarProximoLote();
                         } else {
                             this.finalizarUpload();
                         }
                     }
                 );
             },

             cancelarEnvio() {
                 this.cancelado = true;
             },

             finalizarUpload() {
                 this.enviando = false;
                 this.concluido = true;
                 window.dispatchEvent(new CustomEvent('foto-upload-concluido'));
                 setTimeout(() => {
                     this.concluido = false;
                     $wire.$refresh();
                 }, 3000);
             }
         }">

        {{-- Área de drag & drop --}}
        <label for="upload-fotos-{{ $trabalhoId }}"
               class="area-upload"
               :style="enviando ? 'opacity: 0.5; pointer-events: none; cursor: default;' : ''"
               @dragover.prevent="if (!enviando) $el.classList.add('dragover')"
               @dragleave.prevent="$el.classList.remove('dragover')"
               @drop.prevent="$el.classList.remove('dragover')">
            <i class="bi bi-cloud-arrow-up"></i>
            <p style="font-size: 16px; color: #8c6b7d; margin: 0 0 4px;">Arraste as fotos aqui ou clique para selecionar</p>
            <small style="color: #8c6b7d; font-size: 13px;">Formatos aceitos: JPG, PNG, PSD, TIF · Máx. 200MB por arquivo</small>
        </label>
        <input type="file"
               id="upload-fotos-{{ $trabalhoId }}"
               multiple
               accept=".jpg,.jpeg,.png,.psd,.tif,.tiff"
               style="display: none;"
               @change="selecionarArquivos($event)">

        {{-- Progresso durante upload --}}
        <div x-show="enviando" x-cloak style="margin-top: 16px;">
            {{-- Barra principal --}}
            <div style="width: 100%; height: 24px; background: #f0d4da; border-radius: 12px; overflow: hidden; position: relative; margin-bottom: 10px;">
                <div :style="'width: ' + porcentagem + '%; height: 100%; background: #c27a8e; border-radius: 12px; transition: width 0.3s ease;'"></div>
                <span style="position: absolute; top: 0; left: 0; right: 0; height: 100%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 13px; pointer-events: none;"
                      x-text="porcentagem + '%'"></span>
            </div>

            {{-- Texto de status + botão cancelar --}}
            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;">
                <div>
                    <p style="font-family: Inter, sans-serif; font-weight: 500; font-size: 16px; color: #4a2c3d; margin: 0 0 3px; display: flex; align-items: center; gap: 6px;">
                        <i class="bi bi-cloud-arrow-up spin-upload"></i>
                        <span>Enviando... </span><span x-text="fotosEnviadas + fotosFalhas"></span><span>&nbsp;de&nbsp;</span><span x-text="fotosTotal"></span><span>&nbsp;fotos</span>
                    </p>
                    <p style="font-family: Inter, sans-serif; font-weight: 400; font-size: 13px; color: #8c6b7d; margin: 0;">
                        Lote <span x-text="numLoteAtual"></span> de <span x-text="totalLotes"></span>
                    </p>
                </div>
                <button type="button"
                        @click="cancelarEnvio()"
                        x-show="!cancelado"
                        style="background: transparent; border: 1.5px solid #8c6b7d; color: #8c6b7d; border-radius: 8px; padding: 7px 18px; font-family: Inter, sans-serif; font-size: 14px; cursor: pointer; white-space: nowrap; flex-shrink: 0;">
                    Cancelar envio
                </button>
            </div>
        </div>

        {{-- Resultado final (visível por 3 segundos após concluir) --}}
        <div x-show="concluido && !enviando" x-cloak style="margin-top: 16px;">

            {{-- Cancelado --}}
            <template x-if="cancelado">
                <div>
                    <div style="width: 100%; height: 24px; background: #f0d4da; border-radius: 12px; overflow: hidden; position: relative; margin-bottom: 10px;">
                        <div :style="'width: ' + porcentagem + '%; height: 100%; background: #8c6b7d; border-radius: 12px;'"></div>
                        <span style="position: absolute; top: 0; left: 0; right: 0; height: 100%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 13px; pointer-events: none;"
                              x-text="porcentagem + '%'"></span>
                    </div>
                    <p style="font-family: Inter, sans-serif; font-weight: 500; font-size: 16px; color: #8c6b7d; margin: 0;">
                        Envio cancelado. <span x-text="fotosEnviadas"></span> foto<span x-show="fotosEnviadas !== 1">s</span> já enviada<span x-show="fotosEnviadas !== 1">s</span>.
                    </p>
                </div>
            </template>

            {{-- Sucesso total --}}
            <template x-if="!cancelado && erros.length === 0">
                <div>
                    <div style="width: 100%; height: 24px; background: #d4edda; border-radius: 12px; overflow: hidden; margin-bottom: 10px;">
                        <div style="width: 100%; height: 100%; background: #27ae60; border-radius: 12px;"></div>
                    </div>
                    <p style="font-family: Inter, sans-serif; font-weight: 600; font-size: 16px; color: #27ae60; margin: 0;">
                        ✓ <span x-text="fotosEnviadas"></span> foto<span x-show="fotosEnviadas !== 1">s</span> enviada<span x-show="fotosEnviadas !== 1">s</span> com sucesso!
                    </p>
                </div>
            </template>

            {{-- Sucesso parcial com erros --}}
            <template x-if="!cancelado && erros.length > 0">
                <div>
                    <div style="width: 100%; height: 24px; background: #f0d4da; border-radius: 12px; overflow: hidden; position: relative; margin-bottom: 10px;">
                        <div :style="'width: ' + (fotosTotal > 0 ? Math.round(fotosEnviadas / fotosTotal * 100) : 0) + '%; height: 100%; background: #27ae60; border-radius: 12px;'"></div>
                    </div>
                    <p style="font-family: Inter, sans-serif; font-weight: 500; font-size: 15px; color: #4a2c3d; margin: 0 0 6px;">
                        <span x-text="fotosEnviadas"></span> foto<span x-show="fotosEnviadas !== 1">s</span> enviada<span x-show="fotosEnviadas !== 1">s</span>
                        &nbsp;·&nbsp;
                        <span style="color: #c0392b;" x-text="fotosFalhas"></span>
                        <span style="color: #c0392b;"> foto<span x-show="fotosFalhas !== 1">s</span> falharam</span>
                    </p>
                    <div style="font-family: Inter, sans-serif; font-size: 13px; color: #c0392b; max-height: 80px; overflow-y: auto; background: #fdf2f2; border-radius: 6px; padding: 6px 10px;">
                        <template x-for="nome in erros" :key="nome">
                            <div x-text="'✕ ' + nome" style="line-height: 1.6;"></div>
                        </template>
                    </div>
                </div>
            </template>

        </div>
    </div>

    {{-- Grid de thumbnails (atualizado pelo Livewire após cada lote) --}}
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
                    @elseif($foto->caminho_thumbnail)
                        <img src="{{ asset('storage/' . $foto->caminho_thumbnail) }}"
                             alt="{{ $foto->nome_arquivo }}"
                             class="foto-thumb"
                             loading="lazy">
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
