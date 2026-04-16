# M12 — Renovar Link Expirado

## Propósito

Quando um link de galeria expira, a Silvia pode renovar o acesso diretamente no painel, sem precisar remover e re-adicionar o cliente. Um botão "Renovar" aparece ao lado de cada link expirado no ClientManager. Ela escolhe quantos dias extras conceder e o link volta a funcionar com novo prazo.

## Arquivos

| Arquivo | Papel |
|---------|-------|
| `app/Livewire/Admin/ClientManager.php` | Adicionar método `renovar()` |
| `resources/views/livewire/admin/client-manager.blade.php` | Botão e modal de renovação |

Nenhuma migration necessária — usa colunas `expira_em` e `status_link` já existentes.

## Lógica no ClientManager

```php
// app/Livewire/Admin/ClientManager.php

public int $renovandoVinculoId = 0;
public int $diasRenovacao = 30;

public function abrirRenovacao(int $vinculoId): void
{
    $this->renovandoVinculoId = $vinculoId;
    $this->diasRenovacao = 30;
}

public function cancelarRenovacao(): void
{
    $this->renovandoVinculoId = 0;
    $this->diasRenovacao = 30;
}

public function renovar(): void
{
    $this->validate([
        'diasRenovacao' => 'required|integer|min:1|max:365',
    ], [
        'diasRenovacao.min' => 'Informe pelo menos 1 dia.',
        'diasRenovacao.max' => 'Máximo de 365 dias.',
    ]);

    $vinculo = TrabalhoCliente::findOrFail($this->renovandoVinculoId);

    $vinculo->update([
        'expira_em'   => now()->addDays($this->diasRenovacao),
        'status_link' => 'disponivel',
    ]);

    $this->cancelarRenovacao();
    $this->dispatch('notify', tipo: 'sucesso', mensagem: "Link renovado por {$this->diasRenovacao} dias!");
}
```

## Layout do ClientManager com botão Renovar

```
┌────────────────────────────────────────────────────────────┐
│ CLIENTES VINCULADOS                                        │
│                                                            │
│  Ana Lima   (11) 99999-0001                                │
│  🔗 https://site.com/galeria/abc123  [📋 Copiar]           │
│  ❌ Expirado há 5 dias  [Renovar]  [Remover]               │
│                                                            │
│  João Souza  (21) 98888-0002                               │
│  🔗 https://site.com/galeria/xyz456  [📋 Copiar]           │
│  ✅ Expira em 20 dias               [Remover]              │
└────────────────────────────────────────────────────────────┘
```

## Painel de renovação (inline, abaixo do cliente)

Quando a Silvia clica em "Renovar", um painel inline aparece abaixo do cliente expirado (sem modal separado — mais simples para o público-alvo):

```
│  Ana Lima   (11) 99999-0001                                │
│  ❌ Expirado há 5 dias  [Renovar]  [Remover]               │
│  ┌───────────────────────────────────────────────────┐     │
│  │  Renovar acesso de Ana Lima                       │     │
│  │  Conceder mais: [30 ▼] dias                       │     │
│  │               [✓ Confirmar]  [Cancelar]           │     │
│  └───────────────────────────────────────────────────┘     │
```

## View — trecho do client-manager.blade.php

```blade
@foreach($clientesVinculados as $vinculo)
<div class="cliente-vinculo-row p-3 mb-2 rounded border">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <strong>{{ $vinculo->cliente->nome }}</strong>
            <small class="text-secondary d-block">{{ $vinculo->cliente->telefone }}</small>
            <small class="text-secondary d-block text-truncate" style="max-width:300px">
                {{ url('/galeria/' . $vinculo->token) }}
            </small>
            {{-- Badge de status --}}
            @if($vinculo->estaExpirado())
                <span class="badge bg-danger mt-1">Expirado</span>
            @elseif($vinculo->diasRestantes() !== null && $vinculo->diasRestantes() <= 7)
                <span class="badge bg-warning text-dark mt-1">
                    {{ $vinculo->tempoRestanteFormatado() }}
                </span>
            @else
                <span class="badge bg-success mt-1">
                    {{ $vinculo->tempoRestanteFormatado() }}
                </span>
            @endif
        </div>
        <div class="d-flex gap-2 flex-wrap">
            {{-- Copiar link --}}
            <button x-data
                @click="
                    navigator.clipboard.writeText('{{ url('/galeria/' . $vinculo->token) }}');
                    $el.textContent = 'Copiado!';
                    setTimeout(() => $el.textContent = 'Copiar link', 2000)
                "
                class="btn btn-outline-secondary btn-sm">
                Copiar link
            </button>
            {{-- Renovar (só expirados) --}}
            @if($vinculo->estaExpirado())
                <button wire:click="abrirRenovacao({{ $vinculo->id }})" class="btn btn-warning btn-sm">
                    Renovar
                </button>
            @endif
            {{-- Remover --}}
            <button wire:click="remover({{ $vinculo->id }})"
                wire:confirm="Remover {{ $vinculo->cliente->nome }} deste trabalho?"
                class="btn btn-danger btn-sm">
                Remover
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
                <option value="30" selected>30 dias</option>
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
```

## Regras

- Só aparece o botão "Renovar" se `estaExpirado() === true`
- Após renovar: `expira_em = now() + diasRenovacao`, `status_link = 'disponivel'`
- O novo prazo é contado a partir do momento da renovação (não da data original)
- O mesmo link/token é mantido — o cliente não precisa de um novo link
- Renovação com `diasRenovacao = 0` não é permitida (validação min:1)
- Exibe toast de sucesso com o número de dias concedidos

## Estados do link no ClientManager

| Condição | Badge | Botão Renovar |
|----------|-------|--------------|
| `status_link = disponivel`, sem data | Cinza "Sem expiração" | Não aparece |
| `status_link = disponivel`, > 7 dias | Verde "Expira em X dias" | Não aparece |
| `status_link = disponivel`, 1–7 dias | Amarelo "Expira em X dias" | Não aparece |
| `estaExpirado() = true` | Vermelho "Expirado" | **Aparece** |
