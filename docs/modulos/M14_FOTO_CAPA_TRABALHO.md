# M14 — Foto de Capa do Trabalho

## Propósito

Cada card de trabalho no dashboard exibe a thumbnail da primeira foto como imagem de capa. Hoje os cards são só texto — com a foto de capa fica visualmente mais fácil de identificar cada trabalho de relance. Se o trabalho não tiver fotos, exibe um placeholder com ícone de câmera.

## Arquivos

| Arquivo | Papel |
|---------|-------|
| `app/Models/Trabalho.php` | Método `fotoCapa()` |
| `resources/views/livewire/admin/job-list.blade.php` | Exibir imagem no card |

Nenhuma migration necessária — usa `caminho_thumbnail` e `drive_thumbnail` da tabela `fotos`.

## Método no Model Trabalho

```php
// app/Models/Trabalho.php

/**
 * Retorna a URL da thumbnail da primeira foto do trabalho.
 * Prioridade: thumbnail local > drive thumbnail > null
 */
public function fotoCapa(): ?string
{
    $foto = $this->fotos()->orderBy('ordem')->first();

    if (!$foto) {
        return null;
    }

    if ($foto->caminho_thumbnail && \Storage::disk('public')->exists($foto->caminho_thumbnail)) {
        return asset('storage/' . $foto->caminho_thumbnail);
    }

    if ($foto->drive_thumbnail) {
        return $foto->drive_thumbnail;
    }

    return null;
}
```

## Query no JobList — incluir thumbnail na listagem

Adicionar `with('fotos')` na query do `render()` para evitar N+1:

```php
// app/Livewire/Admin/JobList.php — dentro do render()

$trabalhos = Trabalho::query()
    ->withCount(['fotos', 'clientes'])
    ->with([
        'clientes' => fn($q) => $q->withPivot(['token', 'expira_em', 'status_link']),
        'fotos'    => fn($q) => $q->orderBy('ordem')->limit(1), // só a primeira foto
    ])
    ->when(...)
    ->latest()
    ->paginate(12);
```

> **Atenção**: `->limit(1)` dentro do `with()` carrega só a primeira foto por trabalho, evitando carregar todas as fotos de todos os trabalhos desnecessariamente.

## Layout do card com capa

```
┌──────────────────────────────────────────┐
│  [  foto de capa 16:9 ou placeholder   ] │
│  ──────────────────────────────────────  │
│  Casamento Maria e João                  │
│  20/03/2026 · [Prévia] [Publicado]       │
│  📷 45 fotos   👥 3 clientes             │
│  ─────────────────────────────────────   │
│  [✏ Editar]                [🗑 Excluir]  │
└──────────────────────────────────────────┘
```

## View — trecho do card em job-list.blade.php

```blade
@foreach($trabalhos as $trabalho)
<div class="card h-100 border-0 shadow-sm" style="border-radius:12px; overflow:hidden">

    {{-- Foto de capa --}}
    @php $urlCapa = $trabalho->fotoCapa() @endphp
    @if($urlCapa)
        <img src="{{ $urlCapa }}"
             alt="Capa — {{ $trabalho->titulo }}"
             loading="lazy"
             style="width:100%; height:180px; object-fit:cover">
    @else
        <div class="d-flex align-items-center justify-content-center"
             style="width:100%; height:180px; background:#fce4ec">
            <div class="text-center">
                <i class="bi bi-camera" style="font-size:2.5rem; color:#c27a8e; opacity:0.5"></i>
                <div class="small mt-1" style="color:#c27a8e; opacity:0.7">Sem fotos</div>
            </div>
        </div>
    @endif

    {{-- Corpo do card --}}
    <div class="card-body">
        <h5 class="card-title mb-1" style="font-family:'Playfair Display',serif; color:#4a2c3d">
            {{ $trabalho->titulo }}
        </h5>
        <div class="text-secondary small mb-2">
            {{ $trabalho->data_trabalho->format('d/m/Y') }}
        </div>

        {{-- Badges tipo + status --}}
        <div class="d-flex gap-2 flex-wrap mb-2">
            <span class="badge" style="background:#fce4ec; color:#c27a8e">
                {{ $trabalho->tipo === 'previa' ? 'Prévia' : 'Completo' }}
            </span>
            <span class="badge" style="background:{{ $trabalho->status === 'publicado' ? '#d4f5e9' : '#ecf0f1' }}; color:{{ $trabalho->status === 'publicado' ? '#27ae60' : '#95a5a6' }}">
                {{ $trabalho->status === 'publicado' ? 'Publicado' : 'Rascunho' }}
            </span>
        </div>

        {{-- Contadores --}}
        <div class="text-secondary small">
            <i class="bi bi-images me-1"></i>{{ $trabalho->fotos_count }} fotos
            &nbsp;·&nbsp;
            <i class="bi bi-people me-1"></i>{{ $trabalho->clientes_count }} clientes
        </div>
    </div>

    {{-- Ações --}}
    <div class="card-footer bg-transparent border-top d-flex gap-2 justify-content-end">
        <a href="{{ route('admin.jobs.edit', $trabalho->id) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-pencil"></i> Editar
        </a>
        <button wire:click="excluir({{ $trabalho->id }})"
            wire:confirm="Excluir o trabalho '{{ $trabalho->titulo }}'? Esta ação não pode ser desfeita."
            class="btn btn-sm btn-outline-danger">
            <i class="bi bi-trash"></i> Excluir
        </button>
    </div>
</div>
@endforeach
```

## Fallback hierarchy para a imagem

```
1. caminho_thumbnail (thumbnail local no storage/public/thumbnails/)
   ↓ se não existir ou arquivo ausente
2. drive_thumbnail (URL direta do Google Drive)
   ↓ se não existir
3. Placeholder rosa com ícone de câmera
```

## Regras

- A imagem de capa é sempre a foto com menor `ordem` do trabalho
- Trabalhos sem fotos exibem o placeholder — não quebram o layout
- A altura da capa é fixa em `180px`, `object-fit: cover` — não distorce fotos verticais/horizontais
- A query usa `limit(1)` no eager loading para não carregar todas as fotos na listagem
- O método `fotoCapa()` verifica se o arquivo local existe no disco antes de retornar o caminho (evita imagem quebrada)
