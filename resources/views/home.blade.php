<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('site.titulo') }} — Fotografia Profissional</title>
    <meta name="description" content="{{ config('site.tagline') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="/css/custom.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
</head>
<body class="home-body">

    {{-- ======================================================== --}}
    {{-- SEÇÃO 1 — Header fixo --}}
    {{-- ======================================================== --}}
    <header class="home-header">
        <a href="{{ url('/') }}" class="text-decoration-none">
            <img src="{{ asset('img/img-silvia-logo.png') }}" alt="Silvia Souza Fotógrafa" class="home-header-logo">
        </a>
        <a href="/login" class="home-header-login" aria-label="Área restrita">
            <i class="bi bi-person"></i>
        </a>
    </header>

    {{-- ======================================================== --}}
    {{-- SEÇÃO 2 — Hero --}}
    {{-- ======================================================== --}}
    <section class="home-hero">
        <div class="home-hero-inner">

            {{-- Coluna esquerda: foto --}}
            <div class="home-hero-foto-col">
                <div class="home-hero-foto-moldura">
                    @if(file_exists(public_path('img/silvia-foto.jpg')))
                        <img src="{{ asset('img/silvia-foto.jpg') }}" alt="Silvia Souza Fotógrafa" class="home-hero-foto">
                    @else
                        <div class="home-hero-foto-placeholder">
                            <i class="bi bi-camera"></i>
                            <small>Foto da Silvia</small>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Coluna direita: texto --}}
            <div class="home-hero-texto-col">
                <span class="ornamento-secao">Fotógrafa Profissional</span>
                <h1 class="home-hero-nome">Silvia<br>Souza</h1>
                <p class="home-hero-tagline">{{ config('site.tagline') }}</p>

                <div class="home-hero-contatos">
                    <a href="{{ config('site.whatsapp_link') }}" target="_blank" rel="noopener" class="home-hero-whatsapp-link">
                        <i class="bi bi-whatsapp"></i>
                        <span class="home-hero-telefone">{{ config('site.telefone') }}</span>
                    </a>
                    <p class="home-hero-whatsapp-hint">Toque para agendar pelo WhatsApp</p>
                </div>

                <a href="{{ config('site.whatsapp_link') }}?text={{ urlencode(config('site.whatsapp_mensagem')) }}"
                   target="_blank" rel="noopener"
                   class="home-btn-whatsapp">
                    <i class="bi bi-whatsapp"></i> Chamar no WhatsApp
                </a>

                <div class="home-hero-instagram">
                    <a href="{{ config('site.instagram_link') }}" target="_blank" rel="noopener" class="home-instagram-link">
                        <i class="bi bi-instagram"></i>
                        <span>{{ config('site.instagram') }}</span>
                    </a>
                </div>
            </div>

        </div>
        {{-- Scroll indicator --}}
        <div class="home-hero-scroll-hint" aria-hidden="true">
            <i class="bi bi-chevron-down"></i>
        </div>

    </section>

    {{-- ======================================================== --}}
    {{-- SEÇÃO 2.5 — Stats Strip --}}
    {{-- ======================================================== --}}
    <div class="home-stats-strip">
        <div class="home-stats-inner">
            <div class="home-stat-item">
                <span class="home-stat-quote" style="font-size:15px; line-height:1.35;">Milhares de momentos eternizados</span>
            </div>
            <div class="home-stat-divisor" aria-hidden="true"></div>
            <div class="home-stat-item">
                <span class="home-stat-numero">500<span class="home-stat-plus">+</span></span>
                <span class="home-stat-label">eventos fotografados</span>
            </div>
            <div class="home-stat-divisor" aria-hidden="true"></div>
            <div class="home-stat-item home-stat-frase">
                <span class="home-stat-quote">Sua história merece ser contada</span>
            </div>
        </div>
    </div>

    {{-- ======================================================== --}}
    {{-- SEÇÃO 3 — O que eu faço (serviços) --}}
    {{-- ======================================================== --}}
    <section class="home-servicos reveal-section">
        <span class="ornamento-secao">Especialidades</span>
        <h2 class="home-section-titulo">O que eu faço</h2>
        <div class="home-section-linha"></div>

        <div class="home-servicos-grid">
            @foreach([
                ['icone' => 'bi-balloon-heart',  'titulo' => 'Festas Infantis',       'desc' => 'Aniversários, batizados e comemorações. Cada sorriso registrado com carinho.'],
                ['icone' => 'bi-mortarboard',    'titulo' => 'Formaturas e Escolas',  'desc' => 'Colações de grau, eventos escolares e fotos de turma.'],
                ['icone' => 'bi-heart',          'titulo' => 'Casamentos',            'desc' => 'Do making of à festa. Todos os momentos eternizados.'],
                ['icone' => 'bi-people',         'titulo' => 'Ensaios de Família',    'desc' => 'Sessões em estúdio ou ao ar livre. Memórias que duram para sempre.'],
                ['icone' => 'bi-stars',          'titulo' => 'Festas e Eventos',      'desc' => 'Confraternizações, aniversários de adultos, eventos corporativos.'],
                ['icone' => 'bi-camera',         'titulo' => 'Ensaios Fotográficos',  'desc' => 'Gestantes, newborn, debutantes, books profissionais.'],
            ] as $servico)
                <div class="home-servico-card reveal-item" style="--reveal-delay: {{ $loop->index * 80 }}ms">
                    <div class="home-servico-card-header">
                        <div class="home-servico-icone-wrap">
                            <i class="bi {{ $servico['icone'] }} home-servico-icone"></i>
                        </div>
                        <span class="home-servico-num" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <h3 class="home-servico-titulo">{{ $servico['titulo'] }}</h3>
                    <p class="home-servico-desc">{{ $servico['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ======================================================== --}}
    {{-- SEÇÃO 4 — Meu Trabalho (galeria / portfolio) --}}
    {{-- ======================================================== --}}
    @php
        $portfolioIds = [10, 20, 30, 40, 50, 60, 70, 80, 90, 100, 110, 120];
        $portfolioFotos = array_map(fn($id) => "https://picsum.photos/id/{$id}/600/600", $portfolioIds);
    @endphp

    <section class="home-portfolio" x-data="portfolioGaleria({{ json_encode($portfolioFotos) }})">
        <span class="ornamento-secao">Portfólio</span>
        <h2 class="home-section-titulo">Meu Trabalho</h2>
        <div class="home-section-linha"></div>
        <p class="home-portfolio-subtitulo">Alguns registros dos eventos que tive o prazer de fotografar</p>

        {{-- Lightbox --}}
        <div class="home-lightbox-overlay"
             x-show="aberto"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click.self="fechar()"
             @keydown.escape.window="fechar()"
             @keydown.arrow-left.window="anterior()"
             @keydown.arrow-right.window="proxima()"
             style="display:none;">
            <button class="home-lightbox-fechar" @click="fechar()">✕</button>
            <button class="home-lightbox-seta home-lightbox-seta-esq" @click="anterior()" x-show="atual > 0">
                <i class="bi bi-chevron-left"></i>
            </button>
            <img :src="fotos[atual]" :alt="'Foto ' + (atual + 1)" class="home-lightbox-img">
            <button class="home-lightbox-seta home-lightbox-seta-dir" @click="proxima()" x-show="atual < fotos.length - 1">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>

        {{-- Grid de fotos --}}
        @php
            $categorias = ['Casamento','Formatura','Festa Infantil','Ensaio','Família','Evento','Casamento','Debutante','Newborn','Ensaio','Família','Festa'];
        @endphp
        <div class="home-portfolio-grid">
            @foreach($portfolioFotos as $i => $foto)
                <div class="home-portfolio-item home-portfolio-item-{{ $i + 1 }}"
                     data-categoria="{{ $categorias[$i] }}"
                     @click="abrir({{ $i }})">
                    <img src="{{ $foto }}"
                         alt="Foto do portfolio {{ $i + 1 }}"
                         class="home-portfolio-img"
                         loading="lazy">
                </div>
            @endforeach
        </div>
    </section>

    {{-- ======================================================== --}}
    {{-- SEÇÃO 4.5 — Marquee editorial --}}
    {{-- ======================================================== --}}
    @php
        $marqueeBase = ['Festas Infantis', 'Casamentos', 'Formaturas', 'Ensaios Fotográficos', 'Ensaios de Família', 'Eventos', 'Debutantes', 'Newborn'];
        $marqueeItems = array_merge($marqueeBase, $marqueeBase, $marqueeBase, $marqueeBase);
    @endphp
    <div class="home-marquee-strip" aria-hidden="true">
        <div class="home-marquee-track">
            @foreach($marqueeItems as $item)
                <span class="home-marquee-item">{{ $item }}</span>
                <span class="home-marquee-sep">·</span>
            @endforeach
        </div>
        <div class="home-marquee-track">
            @foreach($marqueeItems as $item)
                <span class="home-marquee-item">{{ $item }}</span>
                <span class="home-marquee-sep">·</span>
            @endforeach
        </div>
    </div>

    {{-- ======================================================== --}}
    {{-- SEÇÃO 5 — Depoimentos --}}
    {{-- ======================================================== --}}
    <section class="home-depoimentos reveal-section">
        <span class="ornamento-secao">Depoimentos</span>
        <h2 class="home-section-titulo">O que dizem sobre meu trabalho</h2>
        <div class="home-section-linha"></div>

        <div class="home-depoimentos-grid">
            @foreach([
                [
                    'texto' => 'A Silvia é incrível! As fotos do aniversário da minha filha ficaram perfeitas. Super atenciosa e profissional.',
                    'autor'  => 'Ana Silva, mãe da Beatriz',
                ],
                [
                    'texto' => 'Contratamos para a formatura da turma e superou todas as expectativas. Recomendo de olhos fechados!',
                    'autor'  => 'Prof. Carlos Mendes',
                ],
                [
                    'texto' => 'Já é a terceira vez que chamo a Silvia para nossos eventos. Sempre entrega um trabalho impecável.',
                    'autor'  => 'Marcos Oliveira',
                ],
            ] as $dep)
                <div class="home-depoimento-card reveal-item" style="--reveal-delay: {{ $loop->index * 120 }}ms">
                    <div class="home-depoimento-aspas" aria-hidden="true">"</div>
                    <p class="home-depoimento-texto">{{ $dep['texto'] }}</p>
                    <footer class="home-depoimento-rodape">
                        <div class="home-depoimento-linha-decorativa"></div>
                        <cite class="home-depoimento-autor">{{ $dep['autor'] }}</cite>
                    </footer>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ======================================================== --}}
    {{-- SEÇÃO 6 — CTA Final --}}
    {{-- ======================================================== --}}
    <section class="home-cta reveal-section">
        <h2 class="home-cta-titulo">Vamos registrar seu próximo momento especial?</h2>
        <p class="home-cta-subtitulo">Entre em contato e faça seu orçamento sem compromisso</p>

        <a href="{{ config('site.whatsapp_link') }}?text={{ urlencode('Olá Silvia! Vi seu site e gostaria de fazer um orçamento.') }}"
           target="_blank" rel="noopener"
           class="home-cta-btn">
            <i class="bi bi-whatsapp"></i> Falar com a Silvia
        </a>

        <p class="home-cta-telefone">{{ config('site.telefone') }}</p>
    </section>

    {{-- ======================================================== --}}
    {{-- SEÇÃO 7 — Footer --}}
    {{-- ======================================================== --}}
    <footer class="home-footer">
        <div class="home-footer-contatos">
            <a href="{{ config('site.whatsapp_link') }}" target="_blank" rel="noopener" class="home-footer-link">
                <i class="bi bi-whatsapp"></i> {{ config('site.telefone') }}
            </a>
            <span class="home-footer-sep"> · </span>
            <a href="{{ config('site.instagram_link') }}" target="_blank" rel="noopener" class="home-footer-link">
                <i class="bi bi-instagram"></i> {{ config('site.instagram') }}
            </a>
        </div>
        <p class="home-footer-copy">© {{ date('Y') }} {{ config('site.titulo') }}</p>
        <p class="home-footer-sub">São Paulo e região <i class="bi bi-camera" aria-hidden="true"></i></p>
    </footer>

    {{-- ======================================================== --}}
    {{-- Botão WhatsApp flutuante --}}
    {{-- ======================================================== --}}
    <a href="{{ config('site.whatsapp_link') }}"
       target="_blank" rel="noopener"
       class="btn-whatsapp-float"
       aria-label="Falar pelo WhatsApp">
        <i class="bi bi-whatsapp"></i>
    </a>

    <script>
        function portfolioGaleria(fotos) {
            return {
                aberto: false,
                atual: 0,
                fotos: fotos,
                abrir(i) {
                    this.atual = i;
                    this.aberto = true;
                    document.body.style.overflow = 'hidden';
                },
                fechar() {
                    this.aberto = false;
                    document.body.style.overflow = '';
                },
                anterior() {
                    if (this.atual > 0) this.atual--;
                },
                proxima() {
                    if (this.atual < this.fotos.length - 1) this.atual++;
                }
            }
        }

        // Scroll Reveal com Intersection Observer
        (function() {
            var items = document.querySelectorAll('.reveal-item, .reveal-section');
            if (!items.length || !('IntersectionObserver' in window)) {
                items.forEach(function(el) { el.classList.add('revealed'); });
                return;
            }
            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        var el = entry.target;
                        var delay = el.style.getPropertyValue('--reveal-delay') || '0ms';
                        setTimeout(function() { el.classList.add('revealed'); }, parseInt(delay) || 0);
                        observer.unobserve(el);
                    }
                });
            }, { threshold: 0.12 });
            items.forEach(function(el) { observer.observe(el); });
        })();
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/mask@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
