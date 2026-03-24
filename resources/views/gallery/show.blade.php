<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $trabalho->titulo }} — Silvia Souza Fotografa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="/css/custom.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <style>
        body { background-color: #fdf0f2; }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="galeria-header">
        <img src="{{ asset('img/img-silvia-logo.png') }}" alt="Silvia Souza Fotógrafa" style="height: 50px; width: auto;">
        <span class="brand-sub">Fotografia profissional desde 1985</span>
    </header>

    <!-- Saudação -->
    <div class="galeria-saudacao">
        <h1>Olá, {{ $cliente->nome }}! 👋</h1>
        <p class="subtitulo">Aqui estão as fotos do seu trabalho:</p>
        <p class="titulo-trabalho">{{ $trabalho->titulo }}</p>
        <p class="data-trabalho">
            <i class="bi bi-calendar3"></i>
            {{ \Carbon\Carbon::parse($trabalho->data_trabalho)->locale('pt_BR')->translatedFormat('d \d\e F \d\e Y') }}
        </p>
        <div style="margin-top: 8px;">
            @if($trabalho->tipo === 'previa')
                <span class="badge-previa">Prévia</span>
            @else
                <span class="badge-completo">Completo</span>
            @endif
        </div>

        {{-- Contador de expiração --}}
        @if($pivot->expira_em)
        <div class="mt-3" x-data="countdown('{{ $pivot->expira_em->toIso8601String() }}')" x-init="iniciar()">
            <div class="d-inline-flex align-items-center gap-2 px-3 py-2" style="background: #fce4ec; border-radius: 50px;">
                <i class="bi bi-clock" style="color: #c27a8e;"></i>
                <span style="font-size: 14px; color: #4a2c3d;">
                    Link disponível por:
                    <strong x-text="texto"></strong>
                </span>
            </div>
        </div>
        @endif
    </div>

    <!-- Botão Download Geral (só para trabalhos completos) -->
    @if($trabalho->tipo === 'completo')
        <div style="padding: 0 16px 24px; max-width: 560px; margin: 0 auto;">
            <a href="{{ route('galeria.download', $token) }}" class="btn-download-geral">
                <i class="bi bi-download"></i> Baixar todas as fotos
            </a>
            <p style="text-align: center; font-size: 14px; color: #8c6b7d; margin-top: 8px;">
                Clique para baixar todas as fotos em um arquivo ZIP
            </p>
        </div>
    @endif

    <!-- Galeria com Lightbox -->
    <div x-data="{
        aberto: false,
        fotoAtual: 0,
        fotos: {{ $fotos->map(fn($f) => ['thumb' => $f->drive_thumbnail ?? asset('storage/'.$f->drive_arquivo_id), 'id' => $f->id, 'nome' => $f->nome_arquivo])->toJson() }},
        abrirFoto(index) { this.fotoAtual = index; this.aberto = true; },
        anterior() { if (this.fotoAtual > 0) this.fotoAtual--; },
        proximo() { if (this.fotoAtual < this.fotos.length - 1) this.fotoAtual++; },
        touchStartX: 0,
        touchStartY: 0,
        onTouchStart(e) { this.touchStartX = e.touches[0].clientX; this.touchStartY = e.touches[0].clientY; },
        onTouchEnd(e) {
            let dx = e.changedTouches[0].clientX - this.touchStartX;
            let dy = e.changedTouches[0].clientY - this.touchStartY;
            if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 50) {
                if (dx < 0) this.proximo(); else this.anterior();
            }
        }
    }">

        <!-- Lightbox -->
        <div x-show="aberto"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="lightbox-overlay"
             @click.self="aberto = false"
             @keydown.escape.window="aberto = false"
             @keydown.arrow-left.window="anterior()"
             @keydown.arrow-right.window="proximo()"
             @touchstart.passive="onTouchStart($event)"
             @touchend.passive="onTouchEnd($event)"
             style="display: none;">
            
            <!-- Fechar -->
            <button class="lightbox-close" @click="aberto = false">✕</button>
            
            <!-- Seta Esquerda -->
            <button class="lightbox-arrow lightbox-arrow-left" @click="anterior()" x-show="fotoAtual > 0">
                <i class="bi bi-chevron-left"></i>
            </button>
            
            <!-- Imagem -->
            <img :src="fotos[fotoAtual]?.thumb" :alt="fotos[fotoAtual]?.nome" class="lightbox-img">
            
            <!-- Seta Direita -->
            <button class="lightbox-arrow lightbox-arrow-right" @click="proximo()" x-show="fotoAtual < fotos.length - 1">
                <i class="bi bi-chevron-right"></i>
            </button>
            
            <!-- Download desta foto -->
            <a :href="'/galeria/{{ $token }}/foto/' + fotos[fotoAtual]?.id" class="btn-rosa" style="margin-top: 8px;">
                <i class="bi bi-download"></i> Baixar esta foto
            </a>
        </div>

        <!-- Grid de fotos -->
        @if($fotos->count() === 0)
            <div style="text-align: center; padding: 60px 20px;">
                <i class="bi bi-hourglass-split" style="font-size: 48px; color: #c27a8e; opacity: 0.4; display: block; margin-bottom: 16px;"></i>
                <p style="font-size: 18px; color: #8c6b7d;">As fotos deste trabalho ainda estão sendo preparadas. Volte em breve!</p>
            </div>
        @else
            <div class="grid-galeria">
                @foreach($fotos as $i => $foto)
                    <div class="foto-wrapper" @click="abrirFoto({{ $loop->index }})">
                        @if($foto->drive_thumbnail && str_starts_with($foto->drive_thumbnail, 'http'))
                            <img src="{{ $foto->drive_thumbnail }}"
                                 alt="{{ $foto->nome_arquivo }}"
                                 class="foto-thumb"
                                 loading="lazy">
                        @elseif(str_starts_with($foto->drive_arquivo_id, 'fotos/'))
                            <img src="{{ asset('storage/' . $foto->drive_arquivo_id) }}"
                                 alt="{{ $foto->nome_arquivo }}"
                                 class="foto-thumb"
                                 loading="lazy">
                        @else
                            <div class="foto-thumb skeleton"></div>
                        @endif
                        
                        <a href="{{ route('galeria.foto', [$token, $foto->id]) }}"
                           class="foto-download-btn"
                           @click.stop
                           title="Baixar foto">
                            <i class="bi bi-download"></i>
                        </a>
                    </div>
                @endforeach
            </div>

        @endif
    </div>

    <!-- Footer -->
    <footer style="text-align: center; padding: 24px; border-top: 1px solid #f0d4da; margin-top: 40px;">
        <p style="font-size: 14px; color: #8c6b7d; margin: 0;">© {{ date('Y') }} Silvia Souza Fotografa 🤍</p>
    </footer>

    <script>
    function countdown(expiraEm) {
        return {
            texto: '',
            timer: null,
            iniciar() {
                this.atualizar();
                this.timer = setInterval(() => this.atualizar(), 60000);
            },
            atualizar() {
                const agora = new Date();
                const expira = new Date(expiraEm);
                const diff = expira - agora;

                if (diff <= 0) {
                    this.texto = 'Expirado';
                    clearInterval(this.timer);
                    setTimeout(() => window.location.reload(), 2000);
                    return;
                }

                const dias = Math.floor(diff / (1000 * 60 * 60 * 24));
                const horas = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutos = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

                if (dias > 1) {
                    this.texto = `${dias} dias e ${horas}h`;
                } else if (dias === 1) {
                    this.texto = `1 dia e ${horas}h`;
                } else if (horas > 0) {
                    this.texto = `${horas}h e ${minutos}min`;
                } else {
                    this.texto = `${minutos} minutos`;
                }
            }
        }
    }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/mask@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
