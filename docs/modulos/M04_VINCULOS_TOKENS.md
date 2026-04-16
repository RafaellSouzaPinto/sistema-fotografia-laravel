# M04 — Vínculos e Tokens

## Propósito

Associar um cliente a um trabalho, gerando um token único que vira o link da galeria pública. Cada par (trabalho + cliente) tem seu próprio token — o mesmo cliente pode ter tokens diferentes para trabalhos diferentes.

## Arquivos

| Arquivo | Papel |
|---------|-------|
| `app/Models/TrabalhoCliente.php` | Pivot model com lógica de expiração |
| `app/Livewire/Admin/ClientManager.php` | UI de gerenciamento de clientes no trabalho |
| `resources/views/livewire/admin/client-manager.blade.php` | View |
| `database/migrations/2026_03_17_221504_create_trabalho_cliente_table.php` | Estrutura base |
| `database/migrations/2026_03_19_000001_add_expiracao_to_trabalho_cliente_table.php` | Adiciona expiração |

## Tabela `trabalho_cliente`

```sql
id            bigint unsigned, PK
trabalho_id   bigint unsigned, FK → trabalhos (cascadeOnDelete)
cliente_id    bigint unsigned, FK → clientes (cascadeOnDelete)
token         varchar(64), unique         ← 64 chars aleatórios
expira_em     datetime, nullable          ← null = sem expiração
status_link   enum('disponivel','expirado') default: disponivel
created_at    timestamp
updated_at    timestamp
deleted_at    timestamp, nullable          ← SoftDeletes
```

## Model TrabalhoCliente (pivot model)

```php
class TrabalhoCliente extends Pivot
{
    use SoftDeletes;

    protected $table = 'trabalho_cliente';
    protected $fillable = ['trabalho_id', 'cliente_id', 'token', 'expira_em', 'status_link'];
    protected $casts = ['expira_em' => 'datetime'];

    public function trabalho(): BelongsTo
    {
        return $this->belongsTo(Trabalho::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    // Verifica expiração: status_link OU data passada
    public function estaExpirado(): bool
    {
        if ($this->status_link === 'expirado') return true;
        if ($this->expira_em && $this->expira_em->isPast()) return true;
        return false;
    }

    public function marcarComoExpirado(): void
    {
        $this->update(['status_link' => 'expirado']);
    }

    public function diasRestantes(): ?int
    {
        if (!$this->expira_em) return null;
        if ($this->estaExpirado()) return 0;
        return (int) now()->diffInDays($this->expira_em);
    }

    public function tempoRestanteFormatado(): string
    {
        $dias = $this->diasRestantes();
        if ($dias === null) return 'Sem expiração';
        if ($dias === 0) return 'Expirado';
        if ($dias === 1) return 'Expira amanhã';
        return "Expira em {$dias} dias";
    }
}
```

## Componente ClientManager

**Arquivo**: `app/Livewire/Admin/ClientManager.php`

Embutido dentro da tela de edição de trabalho (JobForm).

```php
class ClientManager extends Component
{
    public int $trabalhoId;
    public string $telefone = '';
    public string $nome = '';
    public ?int $clienteExistenteId = null;
    public ?Cliente $clienteEncontrado = null;
    public string $telefoneBuscado = '';
    public int $diasExpiracao = 30;

    // Busca cliente pelo telefone (normalizado)
    public function buscarPorTelefone(): void
    {
        $tel = preg_replace('/\D/', '', $this->telefone);

        // REGEXP_REPLACE para ignorar formatação no banco
        $cliente = Cliente::whereRaw(
            "REGEXP_REPLACE(telefone, '[^0-9]', '') = ?",
            [$tel]
        )->first();

        if ($cliente) {
            $this->clienteEncontrado = $cliente;
            $this->clienteExistenteId = $cliente->id;
            $this->nome = $cliente->nome;
        } else {
            $this->clienteEncontrado = null;
            $this->clienteExistenteId = null;
            // nome fica em branco para o usuário preencher
        }

        $this->telefoneBuscado = $tel;
    }

    public function adicionar(): void
    {
        $this->validate([
            'telefone' => 'required|min:8',
            'nome'     => 'required|min:2',
        ]);

        // Reutiliza ou cria cliente
        if ($this->clienteExistenteId) {
            $cliente = Cliente::findOrFail($this->clienteExistenteId);
        } else {
            $cliente = Cliente::create([
                'nome'     => $this->nome,
                'telefone' => $this->telefone,
            ]);
        }

        // Verifica se já está vinculado
        $jaVinculado = TrabalhoCliente::where('trabalho_id', $this->trabalhoId)
            ->where('cliente_id', $cliente->id)
            ->whereNull('deleted_at')
            ->exists();

        if ($jaVinculado) {
            $this->dispatch('notify', tipo: 'aviso', mensagem: 'Cliente já vinculado a este trabalho.');
            return;
        }

        // Cria vínculo com token único
        TrabalhoCliente::create([
            'trabalho_id' => $this->trabalhoId,
            'cliente_id'  => $cliente->id,
            'token'       => Str::random(64),
            'expira_em'   => $this->diasExpiracao > 0
                                ? now()->addDays($this->diasExpiracao)
                                : null,
            'status_link' => 'disponivel',
        ]);

        $this->reset(['telefone', 'nome', 'clienteExistenteId', 'clienteEncontrado', 'telefoneBuscado']);
        $this->dispatch('notify', tipo: 'sucesso', mensagem: 'Cliente adicionado!');
    }

    public function remover(int $vincuoId): void
    {
        // wire:confirm na view
        TrabalhoCliente::findOrFail($vincuoId)->delete();
        $this->dispatch('notify', tipo: 'sucesso', mensagem: 'Cliente removido do trabalho.');
    }
}
```

## Layout do ClientManager

```
┌─────────────────────────────────────────────┐
│ CLIENTES VINCULADOS                         │
│                                             │
│ Telefone: [(11) 99999-____]  [Buscar]       │
│ ✓ Cliente encontrado: Ana Lima              │
│ Nome: [Ana Lima            ]                │
│ Expira em: [30] dias                        │
│                        [Adicionar Cliente]  │
│                                             │
│ ─────────────────────────────────────────  │
│  Ana Lima   (11)99999-0001                  │
│  Link: https://...../galeria/abc123  [📋]   │
│  Expira em 28 dias                [Remover] │
└─────────────────────────────────────────────┘
```

## Link gerado

O link para o cliente é composto por:
```
{APP_URL}/galeria/{token}
```

Exibido no ClientManager com botão de copiar (clipboard API via Alpine.js).

## Lógica de busca por telefone

1. Silvia digita o telefone
2. Ao clicar "Buscar" → `buscarPorTelefone()`
3. Normaliza: remove tudo que não for dígito com `preg_replace('/\D/', '', $tel)`
4. Query com `REGEXP_REPLACE(telefone, '[^0-9]', '')` no banco
5. Se encontrado: preenche nome automaticamente (editável)
6. Se não encontrado: nome em branco para Silvia digitar

## Regras

- Um cliente pode estar em múltiplos trabalhos com tokens diferentes
- Não criar cliente duplicado: busca pelo telefone normalizado antes
- Token é gerado com `Str::random(64)` — 64 chars alfanuméricos
- `diasExpiracao = 0` → sem expiração (`expira_em = null`)
- Expiração automática verificada no `GalleryController` e no Model
