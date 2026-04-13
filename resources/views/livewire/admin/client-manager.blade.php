<div class="card-rosa mb-4">
    <h2 style="font-family: 'Playfair Display', serif; font-style: italic; font-weight: 600; font-size: 20px; color: #4a2c3d; margin-bottom: 20px;">
        Clientes que vão receber este trabalho
    </h2>

    <!-- Formulário de adição -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;" class="g-2">
        <!-- Telefone -->
        <div>
            <label style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 14px; color: #4a2c3d; display: block; margin-bottom: 6px;">Telefone do cliente</label>
            <input type="tel"
                   wire:model="telefone"
                   wire:blur="buscarPorTelefone"
                   x-mask="(99) 99999-9999"
                   class="input-rosa"
                   placeholder="(11) 99999-9999">
        </div>

        <!-- Nome -->
        <div>
            <label style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 14px; color: #4a2c3d; display: block; margin-bottom: 6px;">Nome do cliente</label>
            <input type="text"
                   wire:model="nome"
                   class="input-rosa"
                   placeholder="Nome completo do cliente"
                   style="{{ $clienteEncontrado ? 'border-color: #27ae60; border-width: 2px;' : '' }}">
            @if($clienteEncontrado)
                <span style="color: #27ae60; font-size: 13px;">✓ Cliente encontrado!</span>
            @elseif($telefoneBuscado)
                <span style="color: #8c6b7d; font-size: 13px;">Novo cliente — digite o nome</span>
            @endif
        </div>
    </div>

    <!-- Validade do link -->
    <div class="mb-3">
        <label style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 14px; color: #4a2c3d; display: block; margin-bottom: 8px;">Validade do link</label>
        <div class="d-flex gap-2 flex-wrap">
            @foreach([7, 15, 30, 60, 90] as $dias)
                <button type="button"
                    wire:click="$set('diasExpiracao', {{ $dias }})"
                    class="{{ $diasExpiracao === $dias ? 'btn-rosa-ativo' : 'btn-rosa-outline' }}">
                    {{ $dias }} dias
                </button>
            @endforeach
        </div>
        <small class="text-secondary" style="font-size: 13px; color: #8c6b7d; display: block; margin-top: 6px;">
            O link expira em {{ $diasExpiracao }} dias após criação
        </small>
    </div>

    @error('nome') <span style="color: #c0392b; font-size: 13px; display: block; margin-bottom: 8px;">{{ $message }}</span> @enderror
    @error('telefone') <span style="color: #c0392b; font-size: 13px; display: block; margin-bottom: 8px;">{{ $message }}</span> @enderror

    <button wire:click="adicionar" class="btn-rosa mb-4" wire:loading.attr="disabled">
        <i class="bi bi-plus"></i> Adicionar cliente
    </button>

    <!-- Lista de clientes vinculados -->
    @if($vinculos->isNotEmpty())
        <div>
            @foreach($vinculos as $vinculo)
                <div class="cliente-row" style="flex-direction: column; align-items: stretch; gap: 0;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 8px;">
                        <div class="cliente-info">
                            <div class="nome">{{ $vinculo->cliente->nome }}</div>
                            <div class="telefone">{{ $vinculo->cliente->telefone }}</div>
                            <span class="link-galeria" title="{{ url('/galeria/' . $vinculo->token) }}">
                                {{ url('/galeria/' . $vinculo->token) }}
                            </span>

                            {{-- Badge de status --}}
                            @if($vinculo->estaExpirado())
                                <span class="badge bg-danger mt-1" style="font-size: 12px;">Expirado</span>
                            @elseif($vinculo->diasRestantes() !== null && $vinculo->diasRestantes() <= 7)
                                <span class="badge bg-warning text-dark mt-1" style="font-size: 12px;">
                                    {{ $vinculo->tempoRestanteFormatado() }}
                                </span>
                            @elseif($vinculo->expira_em)
                                <span class="badge bg-success mt-1" style="font-size: 12px;">
                                    {{ $vinculo->tempoRestanteFormatado() }}
                                </span>
                            @else
                                <span class="badge bg-secondary mt-1" style="font-size: 12px;">Sem prazo</span>
                            @endif

                            {{-- Badge de visualização --}}
                            @if($vinculo->foiVisualizado())
                                <span class="badge mt-1" style="background:#e8f5e9; color:#2e7d32; font-size: 12px;" title="{{ $vinculo->total_visualizacoes }} visualização(ões)">
                                    <i class="bi bi-eye-fill me-1"></i>Visto em {{ $vinculo->visualizado_em->format('d/m') }}
                                    @if($vinculo->total_visualizacoes > 1)
                                        · {{ $vinculo->total_visualizacoes }}x
                                    @endif
                                </span>
                            @else
                                <span class="badge mt-1" style="background:#f3f4f6; color:#6b7280; font-size: 12px;">
                                    <i class="bi bi-eye-slash me-1"></i>Não aberto
                                </span>
                            @endif
                        </div>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            {{-- Copiar link --}}
                            @if(!$vinculo->estaExpirado())
                                <button
                                    x-data="{ copiado: false }"
                                    @click="navigator.clipboard.writeText('{{ url('/galeria/' . $vinculo->token) }}').then(() => { copiado = true; setTimeout(() => copiado = false, 2000) })"
                                    class="btn-outline-rosa"
                                    :style="copiado ? 'color: #27ae60;' : ''">
                                    <i class="bi bi-clipboard"></i>
                                    <span x-text="copiado ? '✓ Copiado!' : 'Copiar link'"></span>
                                </button>
                                {{-- Enviar por WhatsApp --}}
                                @php
                                    $telefoneWa = '55' . preg_replace('/\D/', '', $vinculo->cliente->telefone);
                                    $mensagemWa = "Olá, {$vinculo->cliente->nome}! Suas fotos estão prontas. Acesse sua galeria aqui: " . url('/galeria/' . $vinculo->token);
                                @endphp
                                <a href="https://wa.me/{{ $telefoneWa }}?text={{ urlencode($mensagemWa) }}"
                                   target="_blank"
                                   rel="noopener"
                                   class="btn-outline-rosa"
                                   style="color: #25d366; border-color: #25d366;"
                                   title="Enviar link por WhatsApp">
                                    <i class="bi bi-whatsapp"></i> WhatsApp
                                </a>
                            @endif
                            {{-- Renovar (só expirados) --}}
                            @if($vinculo->estaExpirado())
                                <button wire:click="abrirRenovacao({{ $vinculo->id }})" class="btn btn-warning btn-sm">
                                    Renovar
                                </button>
                            @endif
                            {{-- Remover --}}
                            <button wire:click="remover({{ $vinculo->cliente->id }})"
                                    wire:confirm="Tem certeza que deseja remover {{ $vinculo->cliente->nome }} deste trabalho?"
                                    class="btn-perigo">
                                <i class="bi bi-trash"></i> Remover
                            </button>
                        </div>
                    </div>

                    {{-- Painel de renovação inline --}}
                    @if($renovandoVinculoId === $vinculo->id)
                    <div class="mt-3 p-3 rounded" style="background:#fff3cd; border:1px solid #ffc107">
                        <p class="fw-bold mb-2">Renovar acesso de {{ $vinculo->cliente->nome }}</p>
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <label class="fw-semibold">Conceder mais:</label>
                            <select wire:model="diasRenovacao" class="form-select form-select-sm" style="width:auto">
                                <option value="7">7 dias</option>
                                <option value="15">15 dias</option>
                                <option value="30">30 dias</option>
                                <option value="60">60 dias</option>
                                <option value="90">90 dias</option>
                            </select>
                            <button wire:click="renovar" class="btn btn-success btn-sm">
                                Confirmar renovação
                            </button>
                            <button wire:click="cancelarRenovacao" class="btn btn-outline-secondary btn-sm">
                                Cancelar
                            </button>
                        </div>
                        @error('diasRenovacao')
                            <small class="text-danger d-block mt-1">{{ $message }}</small>
                        @enderror
                    </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <p style="color: #8c6b7d; font-size: 14px; text-align: center; padding: 20px 0;">Nenhum cliente adicionado ainda.</p>
    @endif
</div>
