# M03 — Clientes

## Propósito

Silvia cadastra clientes pelo telefone. Um cliente pode ser vinculado a vários trabalhos. A tela `/admin/clients` lista todos os clientes com edição inline.

## Arquivos

| Arquivo | Papel |
|---------|-------|
| `app/Models/Cliente.php` | Model com relacionamento a trabalhos |
| `app/Livewire/Admin/ClientList.php` | Listagem e edição inline em /admin/clients |
| `resources/views/livewire/admin/client-list.blade.php` | View da lista |

> Cadastro de clientes durante associação ao trabalho: ver [M04_VINCULOS_TOKENS.md](M04_VINCULOS_TOKENS.md)

## Tabela `clientes`

```sql
id           bigint unsigned, PK
nome         varchar(255)
telefone     varchar(20)
created_at   timestamp
updated_at   timestamp
deleted_at   timestamp, nullable   ← SoftDeletes
```

## Model Cliente

```php
class Cliente extends Model
{
    use SoftDeletes;

    protected $table = 'clientes';
    protected $fillable = ['nome', 'telefone'];

    public function trabalhos(): BelongsToMany
    {
        return $this->belongsToMany(Trabalho::class, 'trabalho_cliente')
                    ->using(TrabalhoCliente::class)
                    ->withPivot(['token', 'expira_em', 'status_link'])
                    ->withTimestamps();
    }
}
```

## Componente ClientList

**Arquivo**: `app/Livewire/Admin/ClientList.php`

```php
class ClientList extends Component
{
    public string $busca = '';
    public ?int $editandoId = null;
    public string $editNome = '';
    public string $editTelefone = '';

    public function editar(int $id): void
    {
        $cliente = Cliente::findOrFail($id);
        $this->editandoId = $id;
        $this->editNome = $cliente->nome;
        $this->editTelefone = $cliente->telefone;
    }

    public function cancelarEdicao(): void
    {
        $this->editandoId = null;
        $this->editNome = '';
        $this->editTelefone = '';
    }

    public function salvarEdicao(): void
    {
        $this->validate([
            'editNome'      => 'required|min:2',
            'editTelefone'  => 'required|min:8',
        ]);

        Cliente::findOrFail($this->editandoId)->update([
            'nome'      => $this->editNome,
            'telefone'  => $this->editTelefone,
        ]);

        $this->cancelarEdicao();
        $this->dispatch('notify', tipo: 'sucesso', mensagem: 'Cliente atualizado!');
    }

    public function excluir(int $id): void
    {
        // wire:confirm na view
        Cliente::findOrFail($id)->delete();
        $this->dispatch('notify', tipo: 'sucesso', mensagem: 'Cliente removido!');
    }

    public function render(): View
    {
        $clientes = Cliente::query()
            ->withCount('trabalhos')
            ->when($this->busca, fn($q) => $q->where('nome', 'like', "%{$this->busca}%")
                                             ->orWhere('telefone', 'like', "%{$this->busca}%"))
            ->latest()
            ->paginate(20);

        return view('livewire.admin.client-list', compact('clientes'));
    }
}
```

## Layout da tela de clientes

```
┌──────────────────────────────────────────────────────────┐
│  Meus Clientes                                           │
│  [Buscar por nome ou telefone...]                        │
├──────────────────────────────────────────────────────────┤
│  Nome          │ Telefone        │ Trabalhos │ Ações      │
├──────────────────────────────────────────────────────────┤
│  Ana Lima      │ (11) 99999-0001 │     3     │ [✏] [🗑]  │
│  José Silva    │ (21) 98888-0002 │     1     │ [✏] [🗑]  │
│  ─────────────────── modo edição ────────────────────    │
│  [Ana Lima    ] │ [(11) 99999-0001] │       │ [✓] [✕]   │
└──────────────────────────────────────────────────────────┘
```

## Comportamentos

- **Busca**: filtra por nome OU telefone em tempo real (debounce 300ms)
- **Edição inline**: clica no ícone de lápis, campos ficam editáveis na mesma linha
- **Excluir**: `wire:confirm="Deseja excluir este cliente?"` antes de remover
- **Contagem de trabalhos**: badge com número de trabalhos vinculados (incluindo soft-deleted? — só ativos)
- **Responsivo**: em mobile vira cards em vez de tabela

## Formato de telefone

- Armazenado sem máscara: `11999990001`
- Exibido com máscara: `(11) 99999-0001`
- Busca normaliza: remove parênteses, espaços, traços antes de comparar
- Na busca por telefone no ClientManager, usa `REGEXP_REPLACE` no MySQL para normalizar

## Reutilização de clientes

Um mesmo cliente (pelo telefone) é reutilizado entre trabalhos. Não se cria duplicata. Ao vincular um cliente a um trabalho, o ClientManager busca por telefone primeiro (ver M04).
