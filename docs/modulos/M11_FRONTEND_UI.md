# M11 — Frontend e UI

## Propósito

Padrão visual de todo o sistema. Paleta de cores rosa/vinho da marca da Silvia, tipografia elegante, componentes Bootstrap customizados. Público-alvo são adultos/idosos — acessibilidade e clareza são prioridade.

## Stack frontend

| Tecnologia | Versão | Como incluída |
|-----------|--------|--------------|
| Bootstrap | 5.3.2 | CDN |
| Bootstrap Icons | latest | CDN |
| Alpine.js | 3 | Embutido no Livewire 3 |
| Livewire | 3 | Composer |
| Playfair Display | — | Google Fonts CDN |
| Inter | — | Google Fonts CDN |

**Sem npm/Node.js** — tudo via CDN ou PHP.

## Paleta de cores

```css
:root {
    --rosa-primary:     #c27a8e;   /* botão principal, links, destaques */
    --rosa-hover:       #a85d73;   /* hover do botão principal */
    --rosa-claro:       #fce4ec;   /* badges, fundos suaves */
    --rosa-bg:          #fdf0f2;   /* fundo de seções, cards */
    --branco:           #ffffff;
    --texto-escuro:     #4a2c3d;   /* títulos, texto principal */
    --texto-secundario: #8c6b7d;   /* legendas, metadados */
    --verde-badge:      #27ae60;   /* status publicado, sucesso */
    --verde-hover:      #219a52;
    --vermelho:         #c0392b;   /* excluir, erro */
    --vermelho-hover:   #a93226;
    --cinza-badge:      #95a5a6;   /* status rascunho */
    --cinza-borda:      #e0d0d6;
}
```

## Tipografia

```css
/* Títulos, headers, nome da fotógrafa */
font-family: 'Playfair Display', Georgia, serif;

/* Corpo, inputs, botões, labels */
font-family: 'Inter', -apple-system, sans-serif;
```

Tamanhos mínimos para acessibilidade (idosos):
- Corpo: 16px (nunca abaixo de 14px)
- Labels de form: 16px, negrito
- Botões: 16px+, padding generoso

## Componentes — Botões

```html
<!-- Primário (rosa) -->
<button class="btn btn-rosa-primary">
    Salvar Trabalho
</button>

<!-- Secundário (outline) -->
<button class="btn btn-outline-rosa">
    Cancelar
</button>

<!-- Perigo (excluir) -->
<button class="btn btn-danger"
        wire:confirm="Deseja excluir?">
    Excluir
</button>

<!-- Publicar (verde) -->
<button class="btn btn-success btn-lg w-100">
    <i class="bi bi-check-circle me-2"></i>
    Publicar Trabalho
</button>
```

```css
.btn-rosa-primary {
    background-color: var(--rosa-primary);
    border-color: var(--rosa-primary);
    color: white;
    padding: 0.6rem 1.5rem;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 500;
}
.btn-rosa-primary:hover {
    background-color: var(--rosa-hover);
    border-color: var(--rosa-hover);
}
.btn-outline-rosa {
    border-color: var(--rosa-primary);
    color: var(--rosa-primary);
}
.btn-outline-rosa:hover {
    background-color: var(--rosa-claro);
}
```

## Componentes — Cards (trabalhos)

```html
<div class="card trabalho-card shadow-sm h-100">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <span class="badge bg-previa">Prévia</span>
            <span class="badge bg-publicado">Publicado</span>
        </div>
        <h5 class="card-title">Casamento Maria e João</h5>
        <p class="card-text text-muted">20 de março de 2026</p>
        <div class="d-flex gap-2 text-muted small">
            <span><i class="bi bi-images"></i> 45 fotos</span>
            <span><i class="bi bi-people"></i> 2 clientes</span>
        </div>
    </div>
    <div class="card-footer d-flex gap-2">
        <a href="..." class="btn btn-sm btn-outline-rosa flex-fill">Editar</a>
        <button wire:confirm="..." class="btn btn-sm btn-outline-danger">
            <i class="bi bi-trash"></i>
        </button>
    </div>
</div>
```

```css
.trabalho-card {
    border-radius: 12px;
    border-color: var(--cinza-borda);
    transition: transform 0.2s, box-shadow 0.2s;
}
.trabalho-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(194, 122, 142, 0.15) !important;
}
```

## Componentes — Badges

```css
.badge.bg-previa      { background-color: #9b59b6; }  /* roxo */
.badge.bg-completo    { background-color: #3498db; }  /* azul */
.badge.bg-publicado   { background-color: var(--verde-badge); }
.badge.bg-rascunho    { background-color: var(--cinza-badge); }
.badge.bg-expirado    { background-color: var(--vermelho); }
```

## Componentes — Toast de notificação

```html
<!-- Layout do toast (canto superior direito) -->
<div id="toast-container"
     class="position-fixed top-0 end-0 p-3"
     style="z-index: 1100"
     x-data="{ toasts: [] }"
     @notify.window="toasts.push($event.detail); setTimeout(() => toasts.shift(), 3000)">

    <template x-for="toast in toasts">
        <div class="toast show align-items-center"
             :class="{
                'text-bg-success': toast.tipo === 'sucesso',
                'text-bg-danger':  toast.tipo === 'erro',
                'text-bg-warning': toast.tipo === 'aviso',
             }">
            <div class="d-flex">
                <div class="toast-body" x-text="toast.mensagem"></div>
                <button class="btn-close me-2 m-auto" @click="toasts.shift()"></button>
            </div>
        </div>
    </template>
</div>
```

Disparado via Livewire: `$this->dispatch('notify', tipo: 'sucesso', mensagem: 'Salvo!')`.

## Layout Admin (layout/admin.blade.php)

```
┌─────────────────────────────────────────────────────────┐
│  HEADER (fixo, rosa claro)                              │
│  🌸 Silvia Souza Fotografa    [Dashboard] [Clientes]    │
│                               [Sair]                    │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  CONTEÚDO PRINCIPAL                                     │
│  (yield 'content')                                      │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

## Layout Galeria Pública (sem header admin)

```
┌─────────────────────────────────────────────────────────┐
│  🌸 Silvia Souza Fotografa (cabeçalho simples, rosa)    │
├─────────────────────────────────────────────────────────┤
│  Olá, Ana Lima!                                         │
│  Saudação + nome do trabalho + data                     │
│  [Baixar todas as fotos]                                │
├─────────────────────────────────────────────────────────┤
│  GRID DE FOTOS                                          │
└─────────────────────────────────────────────────────────┘
```

## Telas e seus layouts

| Tela | URL | Layout | Componente principal |
|------|-----|--------|---------------------|
| Login | `/login` | centralizado | — |
| Dashboard | `/admin/dashboard` | admin | JobList |
| Criar/Editar Trabalho | `/admin/jobs/*` | admin | JobForm |
| Clientes | `/admin/clients` | admin | ClientList |
| Galeria pública | `/galeria/{token}` | público simples | GalleryController |
| Link expirado | `/galeria/{token}` | público simples | — |

## Acessibilidade (público-alvo: adultos/idosos)

- Fonte mínima 16px no corpo
- Botões com padding mínimo de 12px vertical, 24px horizontal
- Espaçamento generoso entre elementos (não sobrecarregar visualmente)
- Texto dos botões claro: "Salvar Trabalho", "Adicionar Cliente" (não só ícones)
- Ícones sempre acompanhados de texto (exceto botões de excluir onde espaço é limitado)
- Confirmações explícitas antes de ações destrutivas

## Responsividade

| Breakpoint | Comportamento |
|-----------|--------------|
| < 576px (mobile) | Cards empilhados, galeria 2 colunas, botões fullwidth |
| 576-992px (tablet) | Grid 2 colunas, galeria 3 colunas |
| > 992px (desktop) | Grid 3-4 colunas, galeria 4-6 colunas, sidebar se aplicável |
