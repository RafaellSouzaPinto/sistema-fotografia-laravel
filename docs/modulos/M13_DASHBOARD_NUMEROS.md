# M13 — Dashboard com Números Simples

## Propósito

Adicionar 4 cards de resumo no topo do dashboard (`/admin/dashboard`) com métricas simples e visuais. A Silvia vê de relance: quantos trabalhos publicados, quantos clientes, quantas fotos armazenadas e quantos links estão expirando em breve. Sem gráficos, sem complexidade — números grandes e claros.

## Arquivos

| Arquivo | Papel |
|---------|-------|
| `app/Livewire/Admin/JobList.php` | Computed properties com os totais |
| `resources/views/livewire/admin/job-list.blade.php` | Cards de resumo no topo |

Nenhuma migration necessária.

## Computed properties no JobList

```php
// app/Livewire/Admin/JobList.php

use Livewire\Attributes\Computed;

#[Computed]
public function totalPublicados(): int
{
    return Trabalho::where('status', 'publicado')->count();
}

#[Computed]
public function totalClientes(): int
{
    return Cliente::count();
}

#[Computed]
public function totalFotos(): int
{
    return Foto::count();
}

#[Computed]
public function linksExpirandoEmBreve(): int
{
    return TrabalhoCliente::where('status_link', 'disponivel')
        ->whereNotNull('expira_em')
        ->where('expira_em', '<=', now()->addDays(7))
        ->where('expira_em', '>', now())
        ->count();
}
```

## Layout dos cards de resumo

```
┌──────────────────────────────────────────────────────────────────┐
│  [📸 Trabalhos    ]  [👥 Clientes     ]  [🖼 Fotos       ]      │
│  [  Publicados    ]  [  Cadastrados   ]  [  Armazenadas  ]      │
│  [                ]  [                ]  [               ]      │
│  [      42        ]  [      118       ]  [     1.340     ]      │
│                                                                  │
│  [⚠ Links expirando em 7 dias: 3   Ver trabalhos →]             │
└──────────────────────────────────────────────────────────────────┘
```

## View — trecho a inserir em job-list.blade.php (acima dos filtros)

```blade
{{-- Cards de resumo --}}
<div class="row g-3 mb-4">
    {{-- Trabalhos publicados --}}
    <div class="col-6 col-md-4">
        <div class="card text-center h-100 border-0 shadow-sm" style="background:#fdf0f2">
            <div class="card-body py-4">
                <i class="bi bi-camera fs-2" style="color:#c27a8e"></i>
                <div class="mt-2" style="font-size:2rem; font-weight:700; color:#4a2c3d; font-family:'Playfair Display',serif">
                    {{ $this->totalPublicados }}
                </div>
                <div class="text-secondary small mt-1">Trabalhos publicados</div>
            </div>
        </div>
    </div>

    {{-- Clientes --}}
    <div class="col-6 col-md-4">
        <div class="card text-center h-100 border-0 shadow-sm" style="background:#fdf0f2">
            <div class="card-body py-4">
                <i class="bi bi-people fs-2" style="color:#c27a8e"></i>
                <div class="mt-2" style="font-size:2rem; font-weight:700; color:#4a2c3d; font-family:'Playfair Display',serif">
                    {{ $this->totalClientes }}
                </div>
                <div class="text-secondary small mt-1">Clientes cadastrados</div>
            </div>
        </div>
    </div>

    {{-- Fotos --}}
    <div class="col-12 col-md-4">
        <div class="card text-center h-100 border-0 shadow-sm" style="background:#fdf0f2">
            <div class="card-body py-4">
                <i class="bi bi-images fs-2" style="color:#c27a8e"></i>
                <div class="mt-2" style="font-size:2rem; font-weight:700; color:#4a2c3d; font-family:'Playfair Display',serif">
                    {{ number_format($this->totalFotos, 0, ',', '.') }}
                </div>
                <div class="text-secondary small mt-1">Fotos armazenadas</div>
            </div>
        </div>
    </div>
</div>

{{-- Alerta de links expirando --}}
@if($this->linksExpirandoEmBreve > 0)
<div class="alert d-flex align-items-center justify-content-between mb-4"
     style="background:#fff3cd; border:1px solid #ffc107; border-radius:8px">
    <div>
        <i class="bi bi-exclamation-triangle-fill me-2" style="color:#856404"></i>
        <strong style="color:#856404">
            {{ $this->linksExpirandoEmBreve }}
            {{ $this->linksExpirandoEmBreve === 1 ? 'link expira' : 'links expiram' }}
            nos próximos 7 dias
        </strong>
    </div>
    <button wire:click="$set('filtroTipo', 'expirados')" class="btn btn-sm btn-warning">
        Ver trabalhos
    </button>
</div>
@endif
```

## Responsividade

| Dispositivo | Layout dos cards |
|-------------|-----------------|
| Mobile < 576px | 2 colunas (trabalhos + clientes) + 1 linha (fotos) |
| Tablet 576px+ | 3 colunas em linha |
| Desktop | 3 colunas em linha |

O alerta de links expirando aparece em linha única no desktop e empilhado no mobile.

## Performance

Os computed properties do Livewire fazem cache durante o ciclo de renderização — as 4 queries só executam uma vez por render. São queries simples de `count()`, sem JOIN, então negligenciáveis mesmo com muitos registros.

## Regras

- O número de fotos usa `number_format` com ponto como separador de milhar (padrão BR)
- O card "Trabalhos publicados" conta só `status = publicado`, não rascunhos
- O alerta de "links expirando" só aparece se houver pelo menos 1 link nessa situação
- Clicar em "Ver trabalhos" no alerta ativa o filtro `expirados` automaticamente
- Links já expirados (`status_link = expirado` ou `expira_em` no passado) não entram na contagem do alerta — só os que ainda estão disponíveis mas expiram nos próximos 7 dias
