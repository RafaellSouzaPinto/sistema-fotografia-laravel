<div>
    <!-- Cabeçalho -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <h1 style="font-family: 'Playfair Display', serif; font-weight: 700; font-size: 28px; color: #4a2c3d; margin: 0;">Meus Clientes</h1>
    </div>

    <!-- Busca -->
    <div style="position: relative; margin-bottom: 24px; max-width: 400px;">
        <i class="bi bi-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #8c6b7d;"></i>
        <input type="text" wire:model.live.debounce.300ms="busca" class="input-rosa" placeholder="Buscar por nome ou telefone..." style="padding-left: 40px;">
    </div>

    @if($clientes->isEmpty())
        <div style="text-align: center; padding: 60px 20px;">
            <i class="bi bi-people" style="font-size: 48px; color: #c27a8e; opacity: 0.4; display: block; margin-bottom: 16px;"></i>
            <p style="font-size: 16px; color: #8c6b7d;">Nenhum cliente encontrado.</p>
        </div>
    @else
        <!-- Tabela Desktop -->
        <div class="d-none d-md-block card-rosa">
            <table class="tabela-clientes">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Telefone</th>
                        <th>Trabalhos</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clientes as $cliente)
                        <tr>
                            @if($editandoId === $cliente->id)
                                <td>
                                    <input type="text" wire:model="editNome" class="input-rosa" style="padding: 6px 10px; font-size: 14px;">
                                    @error('editNome') <span style="color: #c0392b; font-size: 12px;">{{ $message }}</span> @enderror
                                </td>
                                <td>
                                    <input type="text" wire:model="editTelefone" x-mask="(99) 99999-9999" class="input-rosa" style="padding: 6px 10px; font-size: 14px;">
                                </td>
                                <td>{{ $cliente->trabalhos_count }}</td>
                                <td>
                                    <button wire:click="salvarEdicao" class="btn-rosa" style="padding: 6px 12px; font-size: 13px;">Salvar</button>
                                    <button wire:click="cancelarEdicao" class="btn-outline-rosa" style="padding: 6px 12px; font-size: 13px;">Cancelar</button>
                                </td>
                            @else
                                <td>{{ $cliente->nome }}</td>
                                <td>{{ $cliente->telefone }}</td>
                                <td>{{ $cliente->trabalhos_count }} trabalho(s)</td>
                                <td>
                                    <button wire:click="editar({{ $cliente->id }})" class="btn-outline-rosa" style="padding: 6px 12px; font-size: 13px;">
                                        <i class="bi bi-pencil"></i> Editar
                                    </button>
                                    <button wire:click="excluir({{ $cliente->id }})"
                                            wire:confirm="Excluir o cliente {{ $cliente->nome }}?"
                                            class="btn-perigo" style="padding: 6px 12px; font-size: 13px;">
                                        <i class="bi bi-trash"></i> Excluir
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Cards Mobile -->
        <div class="d-md-none">
            @foreach($clientes as $cliente)
                <div class="card-rosa mb-3">
                    @if($editandoId === $cliente->id)
                        <div class="mb-2">
                            <input type="text" wire:model="editNome" class="input-rosa" placeholder="Nome">
                        </div>
                        <div class="mb-3">
                            <input type="text" wire:model="editTelefone" x-mask="(99) 99999-9999" class="input-rosa" placeholder="Telefone">
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button wire:click="salvarEdicao" class="btn-rosa btn-acao">Salvar</button>
                            <button wire:click="cancelarEdicao" class="btn-outline-rosa btn-acao">Cancelar</button>
                        </div>
                    @else
                        <div class="nome" style="font-weight: 500; font-size: 16px; color: #4a2c3d;">{{ $cliente->nome }}</div>
                        <div style="font-size: 14px; color: #8c6b7d; margin: 4px 0 8px;">{{ $cliente->telefone }}</div>
                        <div style="font-size: 13px; color: #8c6b7d; margin-bottom: 12px;">{{ $cliente->trabalhos_count }} trabalho(s)</div>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <button wire:click="editar({{ $cliente->id }})" class="btn-outline-rosa btn-acao">
                                <i class="bi bi-pencil"></i> Editar
                            </button>
                            <button wire:click="excluir({{ $cliente->id }})"
                                    wire:confirm="Excluir o cliente {{ $cliente->nome }}?"
                                    class="btn-perigo btn-acao">
                                <i class="bi bi-trash"></i> Excluir
                            </button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
