# FEATURE — Links Temporários com Expiração

## Objetivo

Todo link de cliente (prévia e completo) tem prazo de validade. Quando expira, o link para de funcionar, o sistema mostra aviso de expirado, e a Silvia pode liberar espaço no Google Drive deletando trabalhos expirados.

---

## Alterações no banco de dados

### Nova migration: `add_expiracao_to_trabalho_cliente_table`

```php
Schema::table('trabalho_cliente', function (Blueprint $table) {
    $table->dateTime('expira_em')->nullable()->after('token');
    $table->enum('status_link', ['disponivel', 'expirado'])->default('disponivel')->after('expira_em');
});
```

**NÃO alterar a migration original** — criar migration nova.

### Colunas adicionadas na tabela `trabalho_cliente`

| Coluna | Tipo | Obs |
|--------|------|-----|
| expira_em | datetime nullable | Data/hora que o link expira |
| status_link | enum('disponivel', 'expirado') | Default: disponivel |

---

## Regra de negócio

### Criação do link
- Quando a Silvia adiciona um cliente ao trabalho, o sistema pede **quantos dias** o link fica ativo
- Default: 30 dias
- Opções rápidas: 7 dias, 15 dias, 30 dias, 60 dias, 90 dias
- O campo `expira_em` é calculado: `now()->addDays($dias)`
- `status_link` começa como `'disponivel'`

### Expiração automática
- Quando o cliente acessa `/galeria/{token}`, o sistema verifica:
  1. Se `expira_em` não é null E `expira_em < now()` → link expirado
  2. Se expirado: atualiza `status_link` para `'expirado'` no banco
  3. Mostra página de expirado (não a galeria)
- A verificação é feita no controller, não precisa de cron/scheduler

### Visualização pelo cliente
- Link válido: galeria normal + **contador regressivo** mostrando quando expira
- Link expirado: página de aviso elegante dizendo que expirou

### Admin — Dashboard de expirados
- Nova aba/filtro no dashboard: "Expirados"
- Lista trabalhos que têm TODOS os links expirados
- Botão: "Liberar espaço" → deleta fotos do Google Drive + marca trabalho como arquivado

---

## Alterações no Model

### TrabalhoCliente — adicionar ao $fillable e cast

```php
protected $fillable = ['trabalho_id', 'cliente_id', 'token', 'expira_em', 'status_link'];

protected $casts = [
    'expira_em' => 'datetime',
];

// Método helper
public function estaExpirado(): bool
{
    if (is_null($this->expira_em)) return false;
    return $this->expira_em->isPast();
}

public function marcarComoExpirado(): void
{
    if ($this->status_link !== 'expirado') {
        $this->update(['status_link' => 'expirado']);
    }
}

public function diasRestantes(): ?int
{
    if (is_null($this->expira_em)) return null;
    if ($this->expira_em->isPast()) return 0;
    return (int) now()->diffInDays($this->expira_em, false);
}

public function tempoRestanteFormatado(): string
{
    if (is_null($this->expira_em)) return 'Sem prazo';
    if ($this->expira_em->isPast()) return 'Expirado';

    $dias = $this->diasRestantes();
    $horas = (int) now()->diffInHours($this->expira_em) % 24;

    if ($dias > 1) return "{$dias} dias restantes";
    if ($dias === 1) return "1 dia e {$horas}h restantes";
    if ($dias === 0) {
        $horas = (int) now()->diffInHours($this->expira_em);
        if ($horas > 0) return "{$horas} horas restantes";
        $minutos = (int) now()->diffInMinutes($this->expira_em);
        return "{$minutos} minutos restantes";
    }
    return 'Expirado';
}
```

### Trabalho — método para verificar se todos os links expiraram

```php
public function todosLinksExpirados(): bool
{
    if ($this->clientes()->count() === 0) return false;

    return $this->clientes()
        ->wherePivot('status_link', 'disponivel')
        ->wherePivot('expira_em', '>', now())
        ->count() === 0;
}

public function temLinksExpirados(): bool
{
    return $this->clientes()
        ->where(function ($q) {
            $q->wherePivot('status_link', 'expirado')
              ->orWhere(function ($q2) {
                  $q2->wherePivotNotNull('expira_em')
                     ->wherePivot('expira_em', '<', now());
              });
        })
        ->count() > 0;
}
```

---

## Alterações no ClientManager (Livewire)

### Adicionar campo de dias de expiração

Novas propriedades:
```php
public int $diasExpiracao = 30;
```

No método `adicionar()`, ao vincular cliente:
```php
$expiraEm = now()->addDays($this->diasExpiracao);

$trabalho->clientes()->attach($cliente->id, [
    'token' => $token,
    'expira_em' => $expiraEm,
    'status_link' => 'disponivel',
]);
```

### Alterar a view do ClientManager

Após os campos de telefone e nome, adicionar campo de dias:

```html
<div class="mb-3">
    <label>Validade do link</label>
    <div class="d-flex gap-2 flex-wrap">
        @foreach([7, 15, 30, 60, 90] as $dias)
            <button type="button"
                wire:click="$set('diasExpiracao', {{ $dias }})"
                class="btn {{ $diasExpiracao === $dias ? 'btn-rosa-ativo' : 'btn-rosa-outline' }}">
                {{ $dias }} dias
            </button>
        @endforeach
    </div>
    <small class="text-secondary">
        O link expira em {{ $diasExpiracao }} dias após criação
    </small>
</div>
```

CSS dos botões de dias:
```css
.btn-rosa-ativo {
    background: #c27a8e;
    color: #fff;
    border: 2px solid #c27a8e;
    border-radius: 50px;
    padding: 6px 16px;
    font-size: 14px;
}
.btn-rosa-outline {
    background: transparent;
    color: #c27a8e;
    border: 2px solid #f0d4da;
    border-radius: 50px;
    padding: 6px 16px;
    font-size: 14px;
}
.btn-rosa-outline:hover {
    border-color: #c27a8e;
    background: #fce4ec;
}
```

### Na lista de clientes vinculados, mostrar status do link:

```html
@foreach($clientesVinculados as $cliente)
<div class="cliente-row">
    <div>
        <strong>{{ $cliente->nome }}</strong>
        <small class="d-block text-muted">{{ $cliente->telefone }}</small>
        <small class="d-block text-muted">{{ url('/galeria/' . $cliente->pivot->token) }}</small>

        {{-- Status de expiração --}}
        @php
            $pivot = $cliente->pivot;
            $expirado = $pivot->expira_em && \Carbon\Carbon::parse($pivot->expira_em)->isPast();
        @endphp

        @if($expirado || $pivot->status_link === 'expirado')
            <span class="badge bg-danger mt-1">Expirado</span>
        @elseif($pivot->expira_em)
            @php
                $diasRestantes = (int) now()->diffInDays(\Carbon\Carbon::parse($pivot->expira_em), false);
            @endphp
            @if($diasRestantes <= 3)
                <span class="badge bg-warning text-dark mt-1">Expira em {{ $diasRestantes }} dia(s)</span>
            @else
                <span class="badge bg-success mt-1">{{ $diasRestantes }} dias restantes</span>
            @endif
        @else
            <span class="badge bg-secondary mt-1">Sem prazo</span>
        @endif
    </div>
    <div class="d-flex gap-2">
        @if(!$expirado && $pivot->status_link !== 'expirado')
            <button @click="navigator.clipboard.writeText('{{ url('/galeria/' . $cliente->pivot->token) }}')" class="btn-secundario">
                <i class="bi bi-clipboard"></i> Copiar link
            </button>
        @endif
        <button wire:click="remover({{ $cliente->id }})" wire:confirm="Remover este cliente do trabalho?" class="btn-perigo">
            <i class="bi bi-trash"></i> Remover
        </button>
    </div>
</div>
@endforeach
```

---

## Alterações no GalleryController

### Método show — verificar expiração

```php
public function show(string $token)
{
    $pivot = TrabalhoCliente::where('token', $token)->firstOrFail();

    // Verificar expiração
    if ($pivot->expira_em && $pivot->expira_em->isPast()) {
        $pivot->marcarComoExpirado();
        return view('gallery.expirado', [
            'nomeTrabalho' => $pivot->trabalho->titulo,
            'nomeFotografa' => config('site.nome', 'Silvia Souza'),
            'telefone' => config('site.telefone', '(11) 98765-4321'),
            'whatsappLink' => config('site.whatsapp_link', 'https://wa.me/5511987654321'),
        ]);
    }

    $trabalho = $pivot->trabalho;
    $cliente = $pivot->cliente;
    $fotos = $trabalho->fotos()->orderBy('ordem')->get();

    return view('gallery.show', compact('trabalho', 'cliente', 'fotos', 'token', 'pivot'));
}
```

### Métodos de download — também verificar expiração

```php
public function downloadFoto(string $token, Foto $foto)
{
    $pivot = TrabalhoCliente::where('token', $token)->firstOrFail();

    if ($pivot->expira_em && $pivot->expira_em->isPast()) {
        $pivot->marcarComoExpirado();
        abort(403, 'Link expirado.');
    }

    abort_if($foto->trabalho_id !== $pivot->trabalho_id, 403);

    $path = storage_path('app/public/' . $foto->drive_arquivo_id);
    if (!file_exists($path)) abort(404);
    return response()->download($path, $foto->nome_arquivo);
}

public function downloadTodas(string $token)
{
    $pivot = TrabalhoCliente::where('token', $token)->firstOrFail();

    if ($pivot->expira_em && $pivot->expira_em->isPast()) {
        $pivot->marcarComoExpirado();
        abort(403, 'Link expirado.');
    }

    $trabalho = $pivot->trabalho;
    abort_if($trabalho->tipo !== 'completo', 403);

    // ... resto do download ZIP igual antes
}
```

---

## View: Página de link expirado

Criar `resources/views/gallery/expirado.blade.php`:

```html
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Expirado — {{ $nomeFotografa }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { background: #fdf0f2; font-family: 'Inter', sans-serif; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px; }
        .expirado-card { background: #fff; border-radius: 16px; padding: 48px 32px; text-align: center; max-width: 480px; width: 100%; box-shadow: 0 4px 24px rgba(0,0,0,0.06); }
        .expirado-icon { font-size: 64px; color: #c27a8e; opacity: 0.6; }
        .expirado-titulo { font-family: 'Playfair Display', serif; font-weight: 700; font-size: 28px; color: #4a2c3d; margin-top: 24px; }
        .expirado-texto { color: #8c6b7d; font-size: 16px; margin-top: 12px; line-height: 1.6; }
        .expirado-trabalho { font-family: 'Playfair Display', serif; font-weight: 600; font-size: 18px; color: #c27a8e; margin-top: 8px; }
        .btn-whatsapp-expirado { display: inline-flex; align-items: center; gap: 8px; background: #25D366; color: #fff; border: none; border-radius: 50px; padding: 14px 32px; font-size: 16px; font-weight: 600; text-decoration: none; margin-top: 24px; transition: background 0.2s; }
        .btn-whatsapp-expirado:hover { background: #1DA851; color: #fff; }
        .footer-expirado { margin-top: 32px; color: #8c6b7d; font-size: 13px; }
    </style>
</head>
<body>
    <div class="expirado-card">
        <div class="expirado-icon"><i class="bi bi-clock-history"></i></div>
        <h1 class="expirado-titulo">Link expirado</h1>
        <p class="expirado-texto">
            O prazo para visualização das fotos deste trabalho já acabou.
        </p>
        <div class="expirado-trabalho">{{ $nomeTrabalho }}</div>
        <p class="expirado-texto">
            Se precisar de acesso novamente, entre em contato:
        </p>
        <a href="{{ $whatsappLink }}?text=Olá! O link das fotos do trabalho '{{ $nomeTrabalho }}' expirou. Poderia liberar novamente?" class="btn-whatsapp-expirado">
            <i class="bi bi-whatsapp"></i> Falar com {{ $nomeFotografa }}
        </a>
        <div class="footer-expirado">
            {{ $nomeFotografa }} · {{ $telefone }}
        </div>
    </div>
</body>
</html>
```

---

## View: Contador regressivo na galeria

Na `gallery/show.blade.php`, adicionar no bloco de saudação, após o badge de tipo:

```html
{{-- Contador de expiração --}}
@if($pivot->expira_em)
<div class="mt-3" x-data="countdown('{{ $pivot->expira_em->toIso8601String() }}')" x-init="iniciar()">
    <div class="d-inline-flex align-items-center gap-2 px-3 py-2" style="background: #fce4ec; border-radius: 50px;">
        <i class="bi bi-clock" style="color: #c27a8e;"></i>
        <span style="font-size: 14px; color: #4a2c3d;">
            Link disponível por:
            <strong x-text="texto"></strong>
        </span>
    </div>
</div>
@endif
```

JavaScript do contador (Alpine.js):
```html
<script>
function countdown(expiraEm) {
    return {
        texto: '',
        timer: null,
        iniciar() {
            this.atualizar();
            this.timer = setInterval(() => this.atualizar(), 60000); // atualiza a cada 1 min
        },
        atualizar() {
            const agora = new Date();
            const expira = new Date(expiraEm);
            const diff = expira - agora;

            if (diff <= 0) {
                this.texto = 'Expirado';
                clearInterval(this.timer);
                // Recarregar página para mostrar tela de expirado
                setTimeout(() => window.location.reload(), 2000);
                return;
            }

            const dias = Math.floor(diff / (1000 * 60 * 60 * 24));
            const horas = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutos = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

            if (dias > 1) {
                this.texto = `${dias} dias e ${horas}h`;
            } else if (dias === 1) {
                this.texto = `1 dia e ${horas}h`;
            } else if (horas > 0) {
                this.texto = `${horas}h e ${minutos}min`;
            } else {
                this.texto = `${minutos} minutos`;
            }
        }
    }
}
</script>
```

---

## Dashboard — Filtro de expirados

### Alteração no JobList (Livewire)

Adicionar novo filtro:
```php
public string $filtroTipo = 'todos'; // todos, previa, completo, expirados
```

Na query:
```php
if ($this->filtroTipo === 'expirados') {
    $query->whereHas('clientes', function ($q) {
        $q->where(function ($q2) {
            $q2->where('trabalho_cliente.status_link', 'expirado')
               ->orWhere(function ($q3) {
                   $q3->whereNotNull('trabalho_cliente.expira_em')
                      ->where('trabalho_cliente.expira_em', '<', now());
               });
        });
    });
}
```

### Na view job-list.blade.php, adicionar botão de filtro:

```html
<button wire:click="$set('filtroTipo', 'expirados')"
    class="{{ $filtroTipo === 'expirados' ? 'filtro-ativo' : 'filtro-inativo' }}">
    Expirados
</button>
```

### Card de trabalho expirado — visual diferente:

```html
@php
    $todosExpirados = $trabalho->clientes->every(function ($c) {
        return $c->pivot->status_link === 'expirado' || ($c->pivot->expira_em && \Carbon\Carbon::parse($c->pivot->expira_em)->isPast());
    });
@endphp

<div class="card-trabalho {{ $todosExpirados && $trabalho->clientes->count() > 0 ? 'card-expirado' : '' }}">
    {{-- conteúdo do card --}}

    @if($todosExpirados && $trabalho->clientes->count() > 0)
        <span class="badge" style="background: #fdecea; color: #c0392b;">Todos os links expirados</span>
    @endif
</div>
```

CSS:
```css
.card-expirado {
    opacity: 0.7;
    border-color: #e0c4cc;
}
.card-expirado:hover {
    opacity: 1;
}
```

### Botão "Liberar espaço" no card expirado

No card de trabalho, quando todos os links expiraram:

```html
@if($todosExpirados && $trabalho->clientes->count() > 0)
    <button wire:click="liberarEspaco({{ $trabalho->id }})"
        wire:confirm="Isso vai deletar TODAS as fotos do Google Drive deste trabalho. Tem certeza?"
        class="btn-perigo mt-2" style="width: 100%;">
        <i class="bi bi-trash3"></i> Liberar espaço no Drive
    </button>
@endif
```

Método no JobList:
```php
public function liberarEspaco($id)
{
    $trabalho = Trabalho::with('fotos')->findOrFail($id);

    // Deletar fotos do Drive
    if ($trabalho->drive_pasta_id) {
        try {
            $driveService = app(\App\Services\GoogleDriveService::class);
            $driveService->deletarPasta($trabalho->drive_pasta_id);
        } catch (\Exception $e) {
            // Se Drive falhar, deletar local
        }
    }

    // Deletar fotos locais
    foreach ($trabalho->fotos as $foto) {
        \Storage::disk('public')->delete($foto->drive_arquivo_id);
    }

    // Deletar fotos do banco
    $trabalho->fotos()->delete();

    // Limpar referência do Drive
    $trabalho->update(['drive_pasta_id' => null]);

    $this->dispatch('notify', message: 'Espaço liberado! Fotos removidas do Drive.');
}
```

---

## Arquivos a criar/alterar

| Arquivo | Ação |
|---------|------|
| `database/migrations/xxxx_add_expiracao_to_trabalho_cliente_table.php` | Criar |
| `app/Models/TrabalhoCliente.php` | Alterar — adicionar expira_em, status_link, métodos helper |
| `app/Models/Trabalho.php` | Alterar — adicionar todosLinksExpirados(), temLinksExpirados() |
| `app/Livewire/Admin/ClientManager.php` | Alterar — campo dias, calcular expira_em |
| `resources/views/livewire/admin/client-manager.blade.php` | Alterar — botões de dias, badge status |
| `app/Http/Controllers/GalleryController.php` | Alterar — verificar expiração no show/download |
| `resources/views/gallery/expirado.blade.php` | Criar |
| `resources/views/gallery/show.blade.php` | Alterar — adicionar contador regressivo |
| `app/Livewire/Admin/JobList.php` | Alterar — filtro expirados, método liberarEspaco |
| `resources/views/livewire/admin/job-list.blade.php` | Alterar — botão expirados, badge, botão liberar espaço |
| `public/css/custom.css` | Alterar — adicionar estilos dos botões dias, card expirado |

---

## Rodar migration

```bash
php artisan migrate
```
