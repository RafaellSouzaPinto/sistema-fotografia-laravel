<div>
    <!-- Cabeçalho -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <h1 style="font-family: 'Playfair Display', serif; font-weight: 700; font-size: 28px; color: #4a2c3d; margin: 0;">Meus Trabalhos</h1>
        <a href="{{ route('admin.jobs.create') }}" class="btn-rosa">
            <i class="bi bi-plus"></i> Novo Trabalho
        </a>
    </div>

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
                <div class="card-rosa {{ $todosExpirados ? 'card-expirado' : '' }}">
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
                        @if($trabalho->status === 'publicado')
                            <a href="{{ route('admin.jobs.edit', $trabalho->id) }}#clientes" class="btn-acao" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; background: #27ae60; color: #fff; border-radius: 8px; text-decoration: none; font-family: 'Inter', sans-serif; font-weight: 500; font-size: 14px;">
                                <i class="bi bi-link-45deg"></i> Ver Links
                            </a>
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
                </div>
            @endforeach
        </div>
    @endif
</div>
