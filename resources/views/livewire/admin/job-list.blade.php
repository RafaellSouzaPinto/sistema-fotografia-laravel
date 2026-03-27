<div x-data>
    <!-- Cabeçalho -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <h1 style="font-family: 'Playfair Display', serif; font-weight: 700; font-size: 28px; color: #4a2c3d; margin: 0;">Meus Trabalhos</h1>
        <a href="{{ route('admin.jobs.create') }}" class="btn-rosa">
            <i class="bi bi-plus"></i> Novo Trabalho
        </a>
    </div>

    {{-- Cards de resumo --}}
    <div class="row g-3 mb-4">
        {{-- Trabalhos publicados --}}
        <div class="col-6 col-md-4">
            <div class="card text-center h-100 border-0 shadow-sm" style="background:#fdf0f2">
                <div class="card-body py-4">
                    <i class="bi bi-camera fs-2" style="color:#c27a8e"></i>
                    <div class="mt-2" style="font-size:2rem; font-weight:700; color:#4a2c3d; font-family:'Playfair Display',serif">
                        {{ $totalPublicados }}
                    </div>
                    <div class="text-secondary small mt-1">Trabalhos publicados</div>
                </div>
            </div>
        </div>

        {{-- Clientes --}}
        <div class="col-6 col-md-4">
            <div class="card text-center h-100 border-0 shadow-sm" style="background:#fdf0f2">
                <div class="card-body py-4">
                    <i class="bi bi-people fs-2" style="color:#c27a8e"></i>
                    <div class="mt-2" style="font-size:2rem; font-weight:700; color:#4a2c3d; font-family:'Playfair Display',serif">
                        {{ $totalClientes }}
                    </div>
                    <div class="text-secondary small mt-1">Clientes cadastrados</div>
                </div>
            </div>
        </div>

        {{-- Fotos --}}
        <div class="col-12 col-md-4">
            <div class="card text-center h-100 border-0 shadow-sm" style="background:#fdf0f2">
                <div class="card-body py-4">
                    <i class="bi bi-images fs-2" style="color:#c27a8e"></i>
                    <div class="mt-2" style="font-size:2rem; font-weight:700; color:#4a2c3d; font-family:'Playfair Display',serif">
                        {{ number_format($totalFotos, 0, ',', '.') }}
                    </div>
                    <div class="text-secondary small mt-1">Fotos armazenadas</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Alerta de links expirando --}}
    @if($linksExpirandoEmBreve > 0)
    <div class="alert d-flex align-items-center justify-content-between mb-4"
         style="background:#fff3cd; border:1px solid #ffc107; border-radius:8px">
        <div>
            <i class="bi bi-exclamation-triangle-fill me-2" style="color:#856404"></i>
            <strong style="color:#856404">
                {{ $linksExpirandoEmBreve }}
                {{ $linksExpirandoEmBreve === 1 ? 'link expira' : 'links expiram' }}
                nos próximos 7 dias
            </strong>
        </div>
        <button wire:click="$set('filtroTipo', 'expirados')" class="btn btn-sm btn-warning">
            Ver trabalhos
        </button>
    </div>
    @endif

    <!-- Busca e Filtros -->
    <div style="display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; align-items: center;">
        <div style="flex: 1; min-width: 200px; position: relative;">
            <i class="bi bi-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #8c6b7d;"></i>
            <input type="text" wire:model.live.debounce.300ms="busca" class="input-rosa" placeholder="Buscar por nome do trabalho..." style="padding-left: 40px;">
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button wire:click="$set('filtroTipo', 'todos')" class="filtro-tipo {{ $filtroTipo === 'todos' ? 'active' : '' }}">Todos</button>
            <button wire:click="$set('filtroTipo', 'previa')" class="filtro-tipo {{ $filtroTipo === 'previa' ? 'active' : '' }}">Prévias</button>
            <button wire:click="$set('filtroTipo', 'completo')" class="filtro-tipo {{ $filtroTipo === 'completo' ? 'active' : '' }}">Completos</button>
            <button wire:click="$set('filtroTipo', 'expirados')" class="filtro-tipo {{ $filtroTipo === 'expirados' ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i> Expirados
            </button>
        </div>
    </div>

    <!-- Grid de Cards -->
    @if($trabalhos->isEmpty())
        <div style="text-align: center; padding: 80px 20px;">
            <i class="bi bi-camera" style="font-size: 64px; color: #c27a8e; opacity: 0.4; display: block; margin-bottom: 16px;"></i>
            <p style="font-size: 18px; color: #8c6b7d; margin-bottom: 24px;">
                @if($filtroTipo === 'expirados')
                    Nenhum trabalho com links expirados
                @else
                    Você ainda não tem trabalhos cadastrados
                @endif
            </p>
            @if($filtroTipo !== 'expirados')
                <a href="{{ route('admin.jobs.create') }}" class="btn-rosa">
                    <i class="bi bi-plus"></i> Criar meu primeiro trabalho
                </a>
            @endif
        </div>
    @else
        <div class="grid-trabalhos">
            @foreach($trabalhos as $trabalho)
                @php
                    $todosExpirados = $trabalho->clientes->count() > 0 && $trabalho->clientes->every(function ($c) {
                        return $c->pivot->status_link === 'expirado'
                            || ($c->pivot->expira_em && \Carbon\Carbon::parse($c->pivot->expira_em)->isPast());
                    });
                @endphp
                <div class="card-rosa {{ $todosExpirados ? 'card-expirado' : '' }}" style="padding: 0; overflow: hidden;">
                    {{-- Foto de capa --}}
                    @php $urlCapa = $trabalho->fotoCapa() @endphp
                    @if($urlCapa)
                        <img src="{{ $urlCapa }}"
                             alt="Capa — {{ $trabalho->titulo }}"
                             loading="lazy"
                             style="width:100%; height:180px; object-fit:cover; display:block">
                    @else
                        <div class="d-flex align-items-center justify-content-center"
                             style="width:100%; height:180px; background:#fce4ec">
                            <div class="text-center">
                                <i class="bi bi-camera" style="font-size:2.5rem; color:#c27a8e; opacity:0.5"></i>
                                <div class="small mt-1" style="color:#c27a8e; opacity:0.7">Sem fotos</div>
                            </div>
                        </div>
                    @endif

                    <div style="padding: 16px;">
                    <!-- Título -->
                    <h2 style="font-family: 'Playfair Display', serif; font-weight: 600; font-size: 18px; color: #4a2c3d; margin: 0 0 6px;">{{ $trabalho->titulo }}</h2>

                    <!-- Data -->
                    <p style="font-size: 14px; color: #8c6b7d; margin: 0 0 12px;">{{ $trabalho->data_trabalho->format('d/m/Y') }}</p>

                    <!-- Badges -->
                    <div style="display: flex; gap: 8px; margin-bottom: 12px; flex-wrap: wrap;">
                        @if($trabalho->tipo === 'previa')
                            <span class="badge-previa">Prévia</span>
                        @else
                            <span class="badge-completo">Completo</span>
                        @endif

                        @if($trabalho->status === 'publicado')
                            <span class="badge-publicado">Publicado</span>
                        @else
                            <span class="badge-rascunho">Rascunho</span>
                        @endif

                        @if($todosExpirados)
                            <span class="badge" style="background: #fdecea; color: #c0392b; border-radius: 50px; padding: 4px 12px; font-size: 12px;">
                                Todos os links expirados
                            </span>
                        @endif
                    </div>

                    <hr style="border-color: #f0d4da; margin: 12px 0;">

                    <!-- Contadores -->
                    <div style="display: flex; gap: 16px; margin-bottom: 16px;">
                        <span style="font-size: 14px; color: #8c6b7d;">
                            <i class="bi bi-image"></i> {{ $trabalho->fotos_count }} fotos
                        </span>
                        <span style="font-size: 14px; color: #8c6b7d;">
                            <i class="bi bi-people"></i> {{ $trabalho->clientes_count }} clientes
                        </span>
                    </div>

                    <!-- Ações -->
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        @if($trabalho->status === 'publicado' && !$todosExpirados)
                            <a href="{{ route('admin.jobs.edit', $trabalho->id) }}#clientes" class="btn-acao" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; background: #27ae60; color: #fff; border-radius: 8px; text-decoration: none; font-family: 'Inter', sans-serif; font-weight: 500; font-size: 14px;">
                                <i class="bi bi-link-45deg"></i> Ver Links
                            </a>
                        @endif
                        @if($todosExpirados)
                            <button
                                wire:click="abrirModalRenovar({{ $trabalho->id }})"
                                @click="$nextTick(() => bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-renovar-links')).show())"
                                class="btn-acao" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; background: #e67e22; color: #fff; border: none; border-radius: 8px; font-family: 'Inter', sans-serif; font-weight: 500; font-size: 14px; cursor: pointer;">
                                <i class="bi bi-arrow-clockwise"></i> Renovar Links
                            </button>
                        @endif
                        <a href="{{ route('admin.jobs.edit', $trabalho->id) }}" class="btn-outline-rosa btn-acao">
                            <i class="bi bi-pencil"></i> Editar
                        </a>
                        <button wire:click="excluir({{ $trabalho->id }})"
                                wire:confirm="Tem certeza que deseja excluir '{{ $trabalho->titulo }}'? Esta ação não pode ser desfeita."
                                class="btn-perigo btn-acao">
                            <i class="bi bi-trash"></i> Excluir
                        </button>
                    </div>

                    @if($todosExpirados)
                        <button wire:click="liberarEspaco({{ $trabalho->id }})"
                            wire:confirm="Isso vai deletar TODAS as fotos do Google Drive deste trabalho. Tem certeza?"
                            class="btn-perigo mt-2" style="width: 100%;">
                            <i class="bi bi-trash3"></i> Liberar espaço no Drive
                        </button>
                    @endif
                    </div>{{-- fim padding 16px --}}
                </div>
            @endforeach
        </div>
    @endif

    {{-- Modal: Renovar Links Expirados --}}
    <div class="modal fade" id="modal-renovar-links" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 16px; border: none;">
                <div class="modal-header" style="border-bottom: 1px solid #f0d4da; padding: 20px 24px;">
                    <h5 class="modal-title" style="font-family: 'Playfair Display', serif; color: #4a2c3d; font-size: 20px;">
                        <i class="bi bi-arrow-clockwise me-2" style="color: #e67e22;"></i>
                        Renovar Links Expirados
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body" style="padding: 24px;">
                    {{-- Lista de clientes com links expirados --}}
                    @if($this->vinculos_expirados->isNotEmpty())
                        <p class="text-secondary mb-3" style="font-size: 14px;">
                            Os clientes abaixo estão sem acesso às fotos. Escolha um prazo e renove todos de uma vez:
                        </p>

                        <div class="mb-4" style="background: #fdf0f2; border-radius: 10px; padding: 12px 16px;">
                            @foreach($this->vinculos_expirados as $vinculo)
                            <div class="d-flex align-items-center justify-content-between py-2 {{ !$loop->last ? 'border-bottom' : '' }}" style="border-color: #f0d4da !important;">
                                <div>
                                    <strong style="color: #4a2c3d; font-size: 15px;">{{ $vinculo->cliente->nome }}</strong>
                                    <div class="text-secondary" style="font-size: 12px;">{{ $vinculo->cliente->telefone }}</div>
                                </div>
                                <span class="badge" style="background: #fdecea; color: #c0392b; font-size: 12px; padding: 4px 10px;">
                                    <i class="bi bi-clock-history me-1"></i>Expirado
                                </span>
                            </div>
                            @endforeach
                        </div>

                        {{-- Seletor de prazo --}}
                        <div class="mb-1">
                            <label class="form-label fw-semibold" style="color: #4a2c3d;">
                                Renovar por quantos dias?
                            </label>
                            <select wire:model="diasRenovacao" class="form-select form-select-lg" style="border-color: #d4a0ad; border-radius: 8px;">
                                <option value="7">7 dias</option>
                                <option value="15">15 dias</option>
                                <option value="30" selected>30 dias</option>
                                <option value="60">60 dias</option>
                                <option value="90">90 dias</option>
                            </select>
                        </div>
                        @error('diasRenovacao')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    @else
                        <p class="text-secondary text-center py-3">Nenhum link expirado encontrado.</p>
                    @endif
                </div>

                <div class="modal-footer" style="border-top: 1px solid #f0d4da; padding: 16px 24px; gap: 8px;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    @if($this->vinculos_expirados->isNotEmpty())
                        <button wire:click="renovarTodosExpirados"
                                wire:loading.attr="disabled"
                                class="btn btn-lg"
                                style="background: #e67e22; color: #fff; border: none; border-radius: 8px; font-weight: 600; padding: 10px 28px;">
                            <span wire:loading.remove wire:target="renovarTodosExpirados">
                                <i class="bi bi-arrow-clockwise me-1"></i>
                                Renovar {{ $this->vinculos_expirados->count() }}
                                {{ $this->vinculos_expirados->count() === 1 ? 'link' : 'links' }}
                            </span>
                            <span wire:loading wire:target="renovarTodosExpirados">
                                Renovando...
                            </span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>
