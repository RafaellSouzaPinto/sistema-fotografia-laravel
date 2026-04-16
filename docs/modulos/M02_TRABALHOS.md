# M02 — Trabalhos

## Propósito

Núcleo do sistema. Um "trabalho" representa uma sessão fotográfica entregue (prévia ou completa). A Silvia cria, edita e publica trabalhos. Cada trabalho tem fotos e clientes vinculados.

## Arquivos

| Arquivo | Papel |
|---------|-------|
| `app/Models/Trabalho.php` | Model com relacionamentos e métodos auxiliares |
| `app/Livewire/Admin/JobList.php` | Lista do dashboard com filtros |
| `app/Livewire/Admin/JobForm.php` | Criar/editar trabalho |
| `resources/views/livewire/admin/job-list.blade.php` | View da listagem |
| `resources/views/livewire/admin/job-form.blade.php` | View do formulário |
| `database/migrations/2026_03_17_221503_create_trabalhos_table.php` | Estrutura |

## Tabela `trabalhos`

```sql
id               bigint unsigned, PK
titulo           varchar(255)
data_trabalho    date
tipo             enum('previa', 'completo')
status           enum('rascunho', 'publicado')  default: rascunho
drive_pasta_id   varchar(255), nullable          ← ID da pasta no Google Drive
created_at       timestamp
updated_at       timestamp
deleted_at       timestamp, nullable              ← SoftDeletes
```

## Model Trabalho

```php
class Trabalho extends Model
{
    use SoftDeletes;

    protected $table = 'trabalhos';
    protected $fillable = ['titulo', 'data_trabalho', 'tipo', 'status', 'drive_pasta_id'];
    protected $casts = ['data_trabalho' => 'date'];

    public function clientes(): BelongsToMany
    {
        return $this->belongsToMany(Cliente::class, 'trabalho_cliente')
                    ->using(TrabalhoCliente::class)
                    ->withPivot(['token', 'expira_em', 'status_link'])
                    ->withTimestamps();
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(Foto::class)->orderBy('ordem');
    }

    // Verifica se TODOS os links do trabalho estão expirados
    public function todosLinksExpirados(): bool
    {
        return $this->clientes->isNotEmpty()
            && $this->clientes->every(fn($c) => $c->pivot->estaExpirado());
    }

    // Verifica se ALGUM link está expirado
    public function temLinksExpirados(): bool
    {
        return $this->clientes->contains(fn($c) => $c->pivot->estaExpirado());
    }
}
```

## Tipos e status

| Campo | Valores | Significado |
|-------|---------|-------------|
| `tipo` | `previa` | Amostra — fotos comprimidas, menor resolução |
| `tipo` | `completo` | Entrega final — fotos originais + thumbnail |
| `status` | `rascunho` | Só Silvia vê; cliente NÃO consegue acessar |
| `status` | `publicado` | Cliente consegue acessar via token |

## Componente JobList (dashboard)

**Arquivo**: `app/Livewire/Admin/JobList.php`

```php
class JobList extends Component
{
    public string $busca = '';
    public string $filtroTipo = 'todos'; // todos|previa|completo|expirados

    public function excluir(int $id): void
    {
        // wire:confirm antes de chamar
        $trabalho = Trabalho::findOrFail($id);
        // Exclui pasta no Drive se existir
        // SoftDelete no trabalho (cascade para fotos e work_cliente)
    }

    public function render(): View
    {
        $trabalhos = Trabalho::query()
            ->withCount(['fotos', 'clientes'])
            ->with(['clientes' => fn($q) => $q->withPivot(['token', 'expira_em', 'status_link'])])
            ->when($this->busca, fn($q) => $q->where('titulo', 'like', "%{$this->busca}%"))
            ->when($this->filtroTipo === 'previa', fn($q) => $q->where('tipo', 'previa'))
            ->when($this->filtroTipo === 'completo', fn($q) => $q->where('tipo', 'completo'))
            ->when($this->filtroTipo === 'expirados', ...)
            ->latest()
            ->paginate(12);

        return view('livewire.admin.job-list', compact('trabalhos'));
    }
}
```

**Filtros disponíveis na UI**:
- "Todos"
- "Prévias" (tipo = previa)
- "Completos" (tipo = completo)
- "Com links expirados"

## Componente JobForm (criar/editar)

**Arquivo**: `app/Livewire/Admin/JobForm.php`

```php
class JobForm extends Component
{
    public ?int $trabalhoId = null;
    public string $titulo = '';
    public string $data_trabalho = '';
    public string $tipo = 'previa';
    public bool $salvo = false;
    public string $statusAtual = 'rascunho';

    public function mount(?int $trabalhoId = null): void
    {
        if ($trabalhoId) {
            $t = Trabalho::findOrFail($trabalhoId);
            $this->trabalhoId = $t->id;
            $this->titulo = $t->titulo;
            $this->data_trabalho = $t->data_trabalho->format('Y-m-d');
            $this->tipo = $t->tipo;
            $this->statusAtual = $t->status;
        }
    }

    public function salvar(): void
    {
        $this->validate([
            'titulo'        => 'required|min:3',
            'data_trabalho' => 'required|date',
            'tipo'          => 'required|in:previa,completo',
        ]);

        $dados = ['titulo' => $this->titulo, 'data_trabalho' => $this->data_trabalho, 'tipo' => $this->tipo];

        if ($this->trabalhoId) {
            Trabalho::findOrFail($this->trabalhoId)->update($dados);
        } else {
            // Cria pasta no Google Drive
            $pasta = app(GoogleDriveService::class)->criarPasta($this->titulo);
            $trabalho = Trabalho::create(array_merge($dados, ['drive_pasta_id' => $pasta]));
            $this->trabalhoId = $trabalho->id;
            // Redireciona para edit
        }

        $this->salvo = true;
        $this->dispatch('notify', tipo: 'sucesso', mensagem: 'Trabalho salvo!');
    }

    public function publicar(): void
    {
        $trabalho = Trabalho::with(['clientes', 'fotos'])->findOrFail($this->trabalhoId);

        if ($trabalho->clientes->isEmpty()) {
            $this->dispatch('notify', tipo: 'erro', mensagem: 'Adicione pelo menos um cliente.');
            return;
        }
        if ($trabalho->fotos->isEmpty()) {
            $this->dispatch('notify', tipo: 'erro', mensagem: 'Adicione pelo menos uma foto.');
            return;
        }

        $trabalho->update(['status' => 'publicado']);
        $this->statusAtual = 'publicado';
        $this->dispatch('notify', tipo: 'sucesso', mensagem: 'Trabalho publicado!');
    }
}
```

## Layout da tela de trabalho (JobForm)

```
┌─────────────────────────────────────────┐
│ [← Voltar]   Novo/Editar Trabalho       │
├─────────────────────────────────────────┤
│ Título: [___________________________]   │
│ Data:   [_______________]               │
│ Tipo:   ( ) Prévia  ( ) Completo        │
│                     [Salvar Trabalho]   │
├─────────────────────────────────────────┤
│ CLIENTES VINCULADOS                     │
│ (componente ClientManager)              │
├─────────────────────────────────────────┤
│ FOTOS                                   │
│ (componente PhotoUploader)              │
├─────────────────────────────────────────┤
│ [Publicar Trabalho] ← só se rascunho   │
└─────────────────────────────────────────┘
```

## Botão Publicar

- Só aparece se `status === 'rascunho'`
- Valida: pelo menos 1 cliente E 1 foto
- Cor: verde (#27ae60), largura 100%, padding 16px
- Após publicar: botão some, badge "Publicado" aparece

## Cards no dashboard

Cada card exibe:
- Título do trabalho
- Data formatada (d/m/Y)
- Badge tipo: "Prévia" (roxo) ou "Completo" (azul)
- Badge status: "Rascunho" (cinza) ou "Publicado" (verde)
- Contagem de fotos e clientes
- Ações: Editar, Excluir (com wire:confirm)
