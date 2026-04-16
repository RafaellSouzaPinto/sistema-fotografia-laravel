# Silvia Souza Fotografa — Especificação Visual & Frontend

Documento de referência para reconstrução fiel do frontend. Baseado nas telas aprovadas.

---

## 1. Stack Frontend

| Item | Tecnologia |
|------|-----------|
| CSS Framework | Bootstrap 5.3.2 |
| Interatividade | Alpine.js 3.x |
| Componentes reativos | Livewire 3 (Laravel) |
| Ícones | Bootstrap Icons (`bi bi-*`) |
| Fontes | Playfair Display (títulos/serif) + Inter (corpo/sans-serif) — Google Fonts |
| Abordagem | Mobile-first, responsivo, sem frameworks JS pesados |

---

## 2. Paleta de Cores

| Token | Hex | Uso |
|-------|-----|-----|
| `--rosa-principal` | `#c27a8e` | Botões primários, links, ícone câmera, badges |
| `--rosa-hover` | `#a85d73` | Hover de botões primários |
| `--rosa-claro` | `#fce4ec` | Fundo de cards de clientes vinculados, badges prévia |
| `--rosa-bg` | `#fdf0f2` | Fundo geral de todas as páginas |
| `--rosa-borda` | `#f0d4da` | Borda dos cards, separadores |
| `--branco` | `#ffffff` | Fundo de cards, inputs, modais |
| `--texto-escuro` | `#4a2c3d` | Títulos, texto principal |
| `--texto-secundario` | `#8c6b7d` | Subtítulos, labels, placeholders, datas |
| `--verde-badge` | `#27ae60` | Badge "Publicado", badge "Completo" |
| `--verde-claro` | `#d4f5e9` | Fundo da badge "Publicado" e "Completo" |
| `--cinza-badge` | `#95a5a6` | Badge "Rascunho" |
| `--cinza-claro` | `#ecf0f1` | Fundo badge "Rascunho" |
| `--vermelho` | `#c0392b` | Texto e ícone "Excluir", "Remover" |
| `--vermelho-claro` | `#fdecea` | Fundo hover do botão excluir |
| `--header-border` | `#d4a0ad` | Linha inferior do header admin |

---

## 3. Tipografia

| Elemento | Fonte | Peso | Tamanho | Cor |
|----------|-------|------|---------|-----|
| Logo "Silvia Souza Fotografa" | Playfair Display | 700 | 20px (header), 32px (login) | `#4a2c3d` (admin), `#c27a8e` (galeria) |
| Subtítulo "Fotógrafa" (login) | Playfair Display Italic | 400 | 16px | `#8c6b7d` |
| Subtítulo "Fotografia profissional desde 1985" (galeria) | Playfair Display Italic | 400 | 14px | `#8c6b7d` |
| Títulos de página ("Meus Trabalhos", "Novo Trabalho") | Playfair Display | 700 | 28px | `#4a2c3d` |
| Títulos de seção dentro de card ("Informações do Trabalho") | Playfair Display | 600 | 20px | `#4a2c3d` |
| Título do card de trabalho ("Casamento Ana e João") | Playfair Display | 600 | 18px | `#4a2c3d` |
| Labels de formulário | Inter | 500 | 14px | `#4a2c3d` |
| Texto de input / placeholder | Inter | 400 | 16px | `#8c6b7d` (placeholder), `#4a2c3d` (valor) |
| Datas nos cards | Inter | 400 | 14px | `#8c6b7d` |
| Contadores ("128 fotos", "2 clientes") | Inter | 400 | 14px | `#8c6b7d` |
| Texto dos botões | Inter | 500 | 14px | Depende do botão |
| Saudação galeria ("Olá, Ana!") | Playfair Display | 700 | 36px | `#4a2c3d` |
| Nome do trabalho na galeria | Playfair Display | 700 | 28px | `#4a2c3d` |
| Data na galeria | Inter | 400 | 16px | `#8c6b7d` |
| Footer galeria | Inter | 400 | 14px | `#8c6b7d` |
| Navegação header ("Meus Trabalhos", "Meus Clientes") | Inter | 500 | 15px | `#4a2c3d` |

---

## 4. Componentes Globais

### 4.1 Botão Primário (rosa)
```
background: #c27a8e
color: #ffffff
border: none
border-radius: 8px
padding: 12px 28px
font: Inter 500 14px
cursor: pointer
transition: background 0.2s
hover → background: #a85d73
```
Usado em: "Entrar", "+ Novo Trabalho", "Salvar alterações", "+ Adicionar cliente", "Baixar todas as fotos", "Publicar trabalho"

### 4.2 Botão Secundário (outline)
```
background: transparent
color: #4a2c3d
border: 1px solid #d4a0ad
border-radius: 8px
padding: 8px 16px
font: Inter 500 14px
hover → background: #fce4ec
```
Usado em: "Editar", "Ver Links", "Copiar link", "Carregar mais fotos"

### 4.3 Botão Perigo (excluir/remover)
```
background: transparent
color: #c0392b
border: none
padding: 8px 16px
font: Inter 500 14px
hover → background: #fdecea
border-radius: 8px
```
Usado em: "Excluir", "Remover"

### 4.4 Card
```
background: #ffffff
border: 1px solid #f0d4da
border-radius: 12px
padding: 20px
box-shadow: 0 1px 3px rgba(0,0,0,0.04)
hover → box-shadow: 0 4px 12px rgba(0,0,0,0.08)
transition: box-shadow 0.2s
```

### 4.5 Badge Tipo
```
/* Prévia */
background: #fce4ec
color: #c27a8e
border-radius: 50px
padding: 4px 12px
font: Inter 500 12px

/* Completo */
background: #d4f5e9
color: #27ae60
border-radius: 50px
padding: 4px 12px
font: Inter 500 12px
```

### 4.6 Badge Status
```
/* Publicado */
background: #d4f5e9
color: #27ae60
border-radius: 50px
padding: 4px 12px
font: Inter 500 12px

/* Rascunho */
background: #ecf0f1
color: #95a5a6
border-radius: 50px
padding: 4px 12px
font: Inter 500 12px
```

### 4.7 Campo de Formulário (Input)
```
background: #ffffff
border: 1px solid #e0c4cc
border-radius: 8px
padding: 12px 16px
font: Inter 400 16px
color: #4a2c3d
placeholder-color: #8c6b7d
focus → border-color: #c27a8e
focus → box-shadow: 0 0 0 3px rgba(194,122,142,0.15)
```
Label sempre acima do campo, nunca flutuante. `font-size: 16px` mínimo para evitar zoom no iOS.

### 4.8 Toast de Feedback
```
position: fixed
top: 20px
right: 20px
background: #c27a8e
color: #ffffff
border-radius: 8px
padding: 12px 20px
font: Inter 500 14px
box-shadow: 0 4px 12px rgba(0,0,0,0.15)
animation: fade-in 0.3s, fade-out 0.3s (após 3s)
```

### 4.9 Modal de Confirmação
```
overlay → background: rgba(74,44,61,0.5)
modal → background: #ffffff
         border-radius: 12px
         padding: 24px
         max-width: 420px
         text-align: center
título → Playfair Display 600 20px #4a2c3d
texto → Inter 400 16px #8c6b7d
botão cancelar → Botão Secundário
botão confirmar → Botão Perigo (se excluir) ou Botão Primário
```

---

## 5. Telas — Especificação Individual

---

### TELA 1 — Login (`/login`)

**Layout:** centralizado vertical e horizontal, coluna única.

**Fundo da página:** `#fdf0f2` (rosa bg)

**Conteúdo centralizado:**
- Ícone de câmera: `bi bi-camera` — cor `#c27a8e`, tamanho 40px, centralizado
- Nome: "Silvia Souza" — Playfair Display 700, 32px, `#4a2c3d`, centralizado
- Subtítulo: "Fotógrafa" — Playfair Display Italic 400, 16px, `#8c6b7d`, centralizado
- Espaço: 32px

**Card de formulário:**
- Largura: `max-width: 420px`, centralizado
- Background: `#ffffff`
- Border-radius: 12px
- Padding: 32px
- Box-shadow: `0 2px 8px rgba(0,0,0,0.06)`

**Campos dentro do card:**
1. Label "Email" — Inter 500 14px `#4a2c3d`
   - Input tipo email, placeholder "seu@email.com"
2. Label "Senha" — Inter 500 14px `#4a2c3d`
   - Input tipo password, placeholder "Digite sua senha"
   - Botão olhinho (`bi bi-eye`) à direita dentro do input, cor `#8c6b7d`
3. Botão "Entrar" — Botão Primário, largura 100%, margin-top 16px

Sem links de "Esqueci senha" ou "Cadastrar".

---

### TELA 2 — Meus Trabalhos / Dashboard (`/admin/dashboard`)

**Header (fixo no topo, todas as páginas admin):**
```
background: #ffffff
border-bottom: 2px solid #d4a0ad
padding: 12px 24px
display: flex
justify-content: space-between
align-items: center
```
- Lado esquerdo: ícone câmera (`bi bi-camera`, `#c27a8e`, 20px) + "Silvia Souza Fotografa" (Playfair Display 700, 20px, `#4a2c3d`)
- Lado direito: links "Meus Trabalhos" e "Meus Clientes" (Inter 500, 15px, `#4a2c3d`, hover `#c27a8e`) + "Sair" com ícone `bi bi-box-arrow-right` (`#8c6b7d`)
- No mobile: links viram uma row abaixo do logo (sem hamburger)

**Fundo da página:** `#fdf0f2`

**Área de conteúdo:** `max-width: 1200px`, centralizado, `padding: 24px`

**Linha do título:**
- Esquerda: "Meus Trabalhos" (Playfair Display 700, 28px, `#4a2c3d`)
- Direita: botão "+ Novo Trabalho" (Botão Primário, com ícone `bi bi-plus`)

**Barra de busca + filtro (abaixo do título):**
- Input de busca: ícone `bi bi-search` à esquerda, placeholder "Buscar por nome do trabalho...", largura ~70%
- Filtros (à direita): 3 botões tipo tab/toggle:
  - "Todos" | "Prévias" | "Completos"
  - Ativo: `background: #ffffff`, `border: 1px solid #d4a0ad`, `font-weight: 600`
  - Inativo: `background: transparent`, `border: 1px solid #e0c4cc`, `color: #8c6b7d`

**Grid de cards:**
- `display: grid`
- `grid-template-columns`: 1fr (mobile), repeat(2, 1fr) (tablet), repeat(3, 1fr) (desktop)
- `gap: 20px`

**Cada card de trabalho:**
- Componente Card (seção 4.4)
- Conteúdo (de cima pra baixo):
  1. Título: Playfair Display 600 18px `#4a2c3d` (ex: "Casamento Ana e João")
  2. Data: Inter 400 14px `#8c6b7d` (ex: "15/03/2026")
  3. Linha de badges: Badge Tipo + Badge Status, gap 8px
  4. Separador: `<hr>` com `border-color: #f0d4da`, margin 12px 0
  5. Contadores: ícone `bi bi-image` + "128 fotos" · ícone `bi bi-people` + "2 clientes" — Inter 400 14px `#8c6b7d`
  6. Linha de botões (margin-top 12px):
     - "Editar" (Botão Secundário, ícone `bi bi-pencil`)
     - "Ver Links" (Botão Secundário, ícone `bi bi-link-45deg`)
     - "Excluir" (Botão Perigo, ícone `bi bi-trash`)

**Estado vazio (0 trabalhos):**
- Centralizado, ícone `bi bi-camera` 64px `#c27a8e` com opacidade 0.4
- Texto: "Você ainda não tem trabalhos cadastrados" — Inter 400 18px `#8c6b7d`
- Botão: "+ Criar meu primeiro trabalho" (Botão Primário)

---

### TELA 3 — Novo Trabalho (`/admin/jobs/create`)

**Header:** igual Tela 2

**Navegação de retorno:**
- "← Voltar para Meus Trabalhos" — Inter 400 14px `#c27a8e`, ícone `bi bi-arrow-left`, hover underline

**Título da página:** "Novo Trabalho" — Playfair Display 700 28px `#4a2c3d`

**Seção 1 — Card "Informações do Trabalho":**
- Título da seção: "Informações do Trabalho" — Playfair Display Italic 600 20px `#4a2c3d`
- Campo "Nome do trabalho": input text, placeholder "Ex: Casamento Ana e João"
  - Quando focado: borda azul-lilás clara (como na tela: o campo ativo tem fundo levemente azulado `#eef2ff` com borda `#a0b4f0`)
- Campo "Data do trabalho": input type date, formato dd/mm/aaaa
- Campo "Tipo": dois radio buttons estilizados como botões card lado a lado
  - Cada opção: borda `1px solid #e0c4cc`, border-radius 8px, padding 12px 20px
  - Selecionado: borda `2px solid #c27a8e`, fundo `#fff5f7`
  - Texto: "Prévia (amostra de fotos)" e "Trabalho Completo (entrega final)"
  - Radio circle: rosa `#c27a8e` quando ativo
- Botão "Salvar alterações" (Botão Primário)

**Seção 2 — Card "Clientes que vão receber este trabalho":**
(aparece após o trabalho ser salvo)
- Título: "Clientes que vão receber este trabalho" — Playfair Display Italic 600 20px `#4a2c3d`
- Campo "Telefone do cliente": input com máscara `(99) 99999-9999`
- Campo "Nome do cliente": input text, placeholder "Nome completo do cliente"
  - Quando telefone encontra cliente existente: campo preenche automaticamente, borda verde `#27ae60`, texto de apoio "Cliente encontrado!" em verde
  - Quando não encontra: campo vazio, texto de apoio "Novo cliente — digite o nome" em `#8c6b7d`
- Botão "+ Adicionar cliente" (Botão Primário, ícone `bi bi-plus`)

**Lista de clientes vinculados (abaixo):**
- Cada cliente em uma row com fundo `#fce4ec`, border-radius 8px, padding 16px
- Lado esquerdo:
  - Nome: Inter 500 16px `#4a2c3d` (ex: "Ana Silva")
  - Telefone: Inter 400 14px `#8c6b7d` (ex: "(11) 98765-4321")
  - Link: Inter 400 13px `#8c6b7d` truncado (ex: "https://silviafotos.com/galeria/abc123")
- Lado direito:
  - Botão "Copiar link" (Botão Secundário, ícone `bi bi-clipboard`)
  - Botão "Remover" (Botão Perigo, ícone `bi bi-trash`)

**Seção 3 — Card "Fotos do trabalho":**
- Título: "Fotos do trabalho" — Playfair Display Italic 600 20px `#4a2c3d`
- Área de upload:
  ```
  border: 2px dashed #e0c4cc
  border-radius: 12px
  padding: 48px
  text-align: center
  background: #ffffff
  hover/dragover → border-color: #c27a8e, background: #fff5f7
  ```
  - Ícone: `bi bi-cloud-arrow-up` 40px `#c27a8e`
  - Texto principal: "Arraste as fotos aqui ou clique para selecionar" — Inter 400 16px `#8c6b7d`
  - Texto secundário: "Formatos aceitos: JPG, PNG, PSD, TIF" — Inter 400 13px `#8c6b7d`

- Grid de thumbnails (fotos já enviadas):
  - `display: grid`
  - `grid-template-columns`: repeat(3, 1fr) mobile, repeat(5, 1fr) desktop
  - `gap: 8px`
  - Cada thumbnail:
    ```
    width: 100%
    aspect-ratio: 1/1
    object-fit: cover
    border-radius: 8px
    ```
  - Botão X no canto superior direito: circle 24px, background `#c0392b`, color `#fff`, hover scale(1.1)

**Seção 4 — Publicar (quando tem ≥1 foto e ≥1 cliente):**
- Botão grande: "Publicar trabalho e liberar links" — `background: #27ae60`, `color: #fff`, largura 100%, padding 16px, border-radius 8px, font-size 16px
- Texto de apoio: "Após publicar, os clientes poderão acessar as fotos pelos links gerados" — Inter 400 14px `#8c6b7d`

---

### TELA 4 — Editar Trabalho (`/admin/jobs/{id}/edit`)

Idêntica à Tela 3, com as seguintes diferenças:
- Título da página: "Editar Trabalho" (em vez de "Novo Trabalho")
- Campos vêm preenchidos com dados existentes
- Seções de clientes e fotos já visíveis (não precisa salvar antes)
- Se já publicado: botão de publicar muda para "Trabalho publicado ✓" (desabilitado, verde)

---

### TELA 5 — Meus Clientes (`/admin/clients`)

**Header:** igual Tela 2

**Título:** "Meus Clientes" — Playfair Display 700 28px `#4a2c3d`

**Busca:** input com ícone `bi bi-search`, placeholder "Buscar por nome ou telefone..."

**Tabela (desktop) / Cards (mobile):**

Desktop — tabela simples:
```
border-collapse: separate
border-spacing: 0
th → Inter 600 14px #8c6b7d, padding 12px, border-bottom 2px solid #f0d4da
td → Inter 400 15px #4a2c3d, padding 12px, border-bottom 1px solid #f0d4da
tr:hover → background: #fff5f7
```
Colunas: Nome | Telefone | Trabalhos vinculados | Ações (Editar, Excluir)

Mobile — cada cliente vira card:
- Card (componente 4.4) com nome, telefone, contagem de trabalhos, botões

---

### TELA 6 — Galeria do Cliente (`/galeria/{token}`)

**Fundo da página:** `#fdf0f2`

**Header:**
```
background: #ffffff
padding: 20px
text-align: center
border-bottom: 1px solid #f0d4da
```
- Ícone câmera: `bi bi-camera` 24px `#c27a8e`
- Nome: "Silvia Souza Fotografa" — Playfair Display 700 22px `#c27a8e`
- Subtítulo: "Fotografia profissional desde 1985" — Playfair Display Italic 400 14px `#8c6b7d`

**Bloco de saudação (centralizado, padding 40px):**
- "Olá, **Ana**! 👋" — Playfair Display 700 36px `#4a2c3d` (emoji mão acenando nativo)
- "Aqui estão as fotos do seu trabalho:" — Inter 400 16px `#8c6b7d`
- Nome do trabalho: "Casamento Ana e João" — Playfair Display 700 28px `#4a2c3d`
- Data: "15 de março de 2026" — Inter 400 16px `#8c6b7d` (com ícone `bi bi-calendar3` antes)
- Badge de tipo: Badge Tipo (centralizado)

**Botão "Baixar todas as fotos" (só se tipo = completo):**
```
max-width: 500px
margin: 0 auto
width: 100%
background: #c27a8e
color: #ffffff
border-radius: 8px
padding: 16px
font: Inter 600 16px
text-align: center
ícone: bi bi-download à esquerda
```
- Texto de apoio abaixo: "Clique para baixar todas as fotos em um arquivo ZIP" — Inter 400 14px `#8c6b7d`, centralizado

**Grid de fotos:**
```
max-width: 1300px
margin: 0 auto
padding: 0 16px
display: grid
gap: 8px
grid-template-columns:
  mobile (< 768px): repeat(2, 1fr)
  tablet (768-1024px): repeat(3, 1fr)
  desktop (> 1024px): repeat(4, 1fr)
```

Cada foto:
```
width: 100%
aspect-ratio: auto (manter proporção original da foto — as telas mostram fotos com alturas variadas, tipo masonry simplificado, mas pode usar aspect-ratio fixo 4/3 ou 1/1 para uniformidade)
object-fit: cover
border-radius: 8px
cursor: pointer
transition: transform 0.2s, box-shadow 0.2s
hover → transform: scale(1.02), box-shadow: 0 4px 16px rgba(0,0,0,0.12)
```

Ícone de download individual (canto inferior direito de cada foto):
```
position: absolute
bottom: 8px
right: 8px
background: rgba(255,255,255,0.85)
border-radius: 50%
width: 32px
height: 32px
display: flex
align-items: center
justify-content: center
ícone: bi bi-download 16px #c27a8e
opacity: 0 (desktop, aparece no hover)
opacity: 1 (mobile, sempre visível)
```

**Botão "Carregar mais fotos" (paginação):**
```
display: block
margin: 32px auto
Botão Secundário com texto "Carregar mais fotos"
```

**Lightbox (ao clicar na foto):**
```
position: fixed
top: 0; left: 0; right: 0; bottom: 0
z-index: 9999
background: rgba(74,44,61,0.92)
display: flex
align-items: center
justify-content: center
```
- Foto centralizada: max-width 90vw, max-height 85vh, object-fit contain, border-radius 4px
- Botão fechar (X): canto superior direito, circle 40px, `#ffffff`, font-size 24px
- Setas de navegação: esquerda e direita, circles 48px, `background: rgba(255,255,255,0.2)`, `color: #fff`, ícones `bi bi-chevron-left` / `bi bi-chevron-right`
- Botão "Baixar esta foto": abaixo da foto, Botão Primário com ícone `bi bi-download`
- Mobile: suportar swipe (left/right) via Alpine.js ou touch events

**Footer:**
```
text-align: center
padding: 24px
border-top: 1px solid #f0d4da
margin-top: 40px
```
- Texto: "© 2026 Silvia Souza Fotografa 🤍" — Inter 400 14px `#8c6b7d`
- Emoji coração branco nativo

---

## 6. Breakpoints Responsivos

| Breakpoint | Largura | Grid cards admin | Grid fotos galeria |
|------------|---------|------------------|-------------------|
| Mobile | < 768px | 1 coluna | 2 colunas |
| Tablet | 768px – 1024px | 2 colunas | 3 colunas |
| Desktop | > 1024px | 3 colunas | 4 colunas |

Regras mobile adicionais:
- Botões de ação nos cards viram largura total empilhados
- Header: navegação em row abaixo do logo (nunca hamburger)
- Inputs: `font-size: 16px` mínimo (evitar zoom iOS)
- Touch targets: `min-height: 44px` em todos os botões e links

---

## 7. Animações e Transições

| Elemento | Animação |
|----------|----------|
| Cards (hover) | `box-shadow` transição 0.2s |
| Botões (hover) | `background` transição 0.2s |
| Fotos galeria (hover) | `transform: scale(1.02)` transição 0.2s |
| Fotos galeria (load) | `opacity: 0 → 1` fade-in 0.3s (lazy load) |
| Toast | fade-in 0.3s, permanece 3s, fade-out 0.3s |
| Lightbox (abrir) | overlay fade-in 0.2s, foto scale 0.95→1 em 0.2s |
| Lightbox (fechar) | inverso do abrir |
| Skeleton loading (thumbnails) | shimmer horizontal (gradient animado `#f0d4da` → `#fce4ec` → `#f0d4da`) |

---

## 8. Estados Especiais

### Upload em progresso
- Barra de progresso dentro da área de upload
- `background: #fce4ec`, preenchimento `#c27a8e`
- Texto: "Enviando... 45%" — Inter 400 14px `#4a2c3d`

### Campo telefone — cliente encontrado
- Borda do input nome: `2px solid #27ae60`
- Texto abaixo: "✓ Cliente encontrado!" — Inter 400 13px `#27ae60`

### Campo telefone — novo cliente
- Borda do input nome: padrão
- Texto abaixo: "Novo cliente — digite o nome" — Inter 400 13px `#8c6b7d`

### Link copiado (feedback)
- Botão "Copiar link" muda para "✓ Copiado!" por 2s
- Cor do texto muda para `#27ae60`

### Galeria sem fotos
- Centralizado, padding 60px
- Ícone `bi bi-hourglass-split` 48px `#c27a8e` opacidade 0.4
- Texto: "As fotos deste trabalho ainda estão sendo preparadas. Volte em breve!"
- Inter 400 18px `#8c6b7d`
