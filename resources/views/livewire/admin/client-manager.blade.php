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
    @if($clientesVinculados->isNotEmpty())
        <div>
            @foreach($clientesVinculados as $cliente)
                @php
                    $pivot = $cliente->pivot;
                    $expirado = $pivot->expira_em && \Carbon\Carbon::parse($pivot->expira_em)->isPast();
                @endphp
                <div class="cliente-row">
                    <div class="cliente-info">
                        <div class="nome">{{ $cliente->nome }}</div>
                        <div class="telefone">{{ $cliente->telefone }}</div>
                        <span class="link-galeria" title="{{ url('/galeria/' . $pivot->token) }}">
                            {{ url('/galeria/' . $pivot->token) }}
                        </span>

                        {{-- Status de expiração --}}
                        @if($expirado || $pivot->status_link === 'expirado')
                            <span class="badge bg-danger mt-1" style="font-size: 12px;">Expirado</span>
                        @elseif($pivot->expira_em)
                            @php
                                $diasRestantes = (int) now()->diffInDays(\Carbon\Carbon::parse($pivot->expira_em), false);
                            @endphp
                            @if($diasRestantes <= 3)
                                <span class="badge bg-warning text-dark mt-1" style="font-size: 12px;">Expira em {{ $diasRestantes }} dia(s)</span>
                            @else
                                <span class="badge bg-success mt-1" style="font-size: 12px;">{{ $diasRestantes }} dias restantes</span>
                            @endif
                        @else
                            <span class="badge bg-secondary mt-1" style="font-size: 12px;">Sem prazo</span>
                        @endif
                    </div>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        @if(!$expirado && $pivot->status_link !== 'expirado')
                            <button
                                x-data="{ copiado: false }"
                                @click="navigator.clipboard.writeText('{{ url('/galeria/' . $pivot->token) }}').then(() => { copiado = true; setTimeout(() => copiado = false, 2000) })"
                                class="btn-outline-rosa"
                                :style="copiado ? 'color: #27ae60;' : ''">
                                <i class="bi bi-clipboard"></i>
                                <span x-text="copiado ? '✓ Copiado!' : 'Copiar link'"></span>
                            </button>
                        @endif
                        <button wire:click="remover({{ $cliente->id }})"
                                wire:confirm="Tem certeza que deseja remover {{ $cliente->nome }} deste trabalho?"
                                class="btn-perigo">
                            <i class="bi bi-trash"></i> Remover
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p style="color: #8c6b7d; font-size: 14px; text-align: center; padding: 20px 0;">Nenhum cliente adicionado ainda.</p>
    @endif
</div>
