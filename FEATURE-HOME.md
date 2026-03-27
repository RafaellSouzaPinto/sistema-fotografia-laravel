# FEATURE — Página Home (Landing Page Pública)

## Objetivo

Criar uma página home pública (`/`) que funciona como landing page da Silvia. É o link que ela fixa no Instagram, WhatsApp e outras redes. O cliente abre, vê quem ela é, o que faz, fotos do trabalho, depoimentos, e tem acesso fácil ao WhatsApp pra agendar. No canto superior direito tem um ícone discreto de login (só a Silvia sabe que existe).

---

## Rota

Alterar a rota raiz em `routes/web.php`:

```php
// ANTES:
Route::redirect('/', '/login');

// DEPOIS:
Route::get('/', [HomeController::class, 'index'])->name('home');
```

A rota `/login` continua existindo normalmente.

---

## Controller

Criar `app/Http/Controllers/HomeController.php`:

```php
<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    public function index()
    {
        return view('home');
    }
}
```

---

## View

Criar `resources/views/home.blade.php` — página standalone (NÃO usa layout admin).

---

## Estrutura visual (7 seções, de cima pra baixo)

### SEÇÃO 1 — Header fixo

```
Position: fixed (ou sticky)
Top: 0
Width: 100%
Z-index: 1000
Background: #ffffff
Border-bottom: 2px solid #d4a0ad
Padding: 12px 24px
Display: flex
Justify-content: space-between
Align-items: center
```

**Lado esquerdo:**
- Ícone câmera: `bi bi-camera`, cor `#c27a8e`, 20px
- Texto: "Silvia Souza Fotografa" — Playfair Display 700, 20px, `#4a2c3d`

**Lado direito:**
- Ícone de usuário discreto: circle `36px`, borda `1.5px solid #d4a0ad`, ícone `bi bi-person` cor `#c27a8e` 18px
- É um `<a href="/login">` — sem texto, sem tooltip
- Hover: `background: #fce4ec`
- O ícone deve ser sutil — clientes não vão perceber que é um botão de login

---

### SEÇÃO 2 — Hero

```
Background: #fdf0f2
Padding: 80px 20px 60px (desktop), 60px 16px 40px (mobile)
Text-align: center
Margin-top: 60px (compensar header fixo)
```

**Foto circular da Silvia:**
```
Width: 180px (desktop), 140px (mobile)
Height: igual width
Border-radius: 50%
Border: 4px solid #c27a8e
Object-fit: cover
Box-shadow: 0 4px 20px rgba(194, 122, 142, 0.3)
Margin: 0 auto
```
- Por enquanto: placeholder com fundo `#fce4ec`, ícone `bi bi-camera` 48px `#c27a8e` centralizado, texto "Foto da Silvia" abaixo do ícone em 12px `#8c6b7d`
- A Silvia vai trocar pela foto real depois (arquivo em `public/img/silvia.jpg`)

**Textos abaixo da foto:**
- "Silvia Souza" — Playfair Display 700, 42px, `#4a2c3d`, margin-top 24px
- "Fotógrafa" — Playfair Display Italic 400, 20px, `#8c6b7d`
- "Cada foto, uma memória guardada para sempre." — Inter 400, 16px, `#8c6b7d`, margin-top 8px

**Número WhatsApp (centralizado, destaque):**
```
Margin-top: 32px
```
- Ícone WhatsApp: SVG ou `bi bi-whatsapp`, cor `#25D366`, 28px
- Número: "(11) 98765-4321" — Inter 600, 24px (desktop) / 20px (mobile), `#4a2c3d`
- Todo o bloco (ícone + número) é um `<a href="https://wa.me/5511987654321">` clicável
- Hover: cor muda pra `#c27a8e`, underline
- Texto abaixo: "Toque para agendar pelo WhatsApp" — Inter 400, 14px, `#8c6b7d`

**Botão CTA WhatsApp:**
```
Margin-top: 24px
Background: #25D366
Color: #ffffff
Border: none
Border-radius: 50px
Padding: 16px 40px
Font: Inter 600 16px
Width: auto (desktop), 100% (mobile, max-width 400px)
```
- Texto: "📱 Chamar no WhatsApp"
- Link: `https://wa.me/5511987654321?text=Olá! Gostaria de saber mais sobre seus serviços de fotografia.`
- Hover: `background: #1DA851`

**Instagram (abaixo do botão):**
```
Margin-top: 16px
```
- Ícone: `bi bi-instagram`, 20px, `#c27a8e`
- Texto: "@silviasouzafotografa" — Inter 500, 16px, `#c27a8e`
- Link: `https://instagram.com/silviasouzafotografa`
- Hover: `color: #a85d73`, underline

---

### SEÇÃO 3 — O que eu faço (serviços)

```
Background: #ffffff
Padding: 60px 20px
```

**Título:** "O que eu faço" — Playfair Display 700, 32px, `#4a2c3d`, centralizado

**Linha decorativa:** `width: 60px`, `height: 3px`, `background: #c27a8e`, `margin: 12px auto 40px`, centralizada

**Grid de 6 cards:**
```
Display: grid
Grid-template-columns: repeat(1, 1fr) mobile / repeat(2, 1fr) tablet / repeat(3, 1fr) desktop
Gap: 24px
Max-width: 1000px
Margin: 0 auto
```

Cada card:
```
Background: #fdf0f2
Border: 1px solid #f0d4da
Border-radius: 12px
Padding: 32px 24px
Text-align: center
Transition: transform 0.2s, box-shadow 0.2s
Hover: transform: translateY(-4px), box-shadow: 0 8px 24px rgba(0,0,0,0.08)
```

| # | Emoji | Título | Descrição |
|---|-------|--------|-----------|
| 1 | 🎂 | Festas Infantis | Aniversários, batizados e comemorações. Cada sorriso registrado com carinho. |
| 2 | 🎓 | Formaturas e Escolas | Colações de grau, eventos escolares e fotos de turma. |
| 3 | 💒 | Casamentos | Do making of à festa. Todos os momentos eternizados. |
| 4 | 👨‍👩‍👧‍👦 | Ensaios de Família | Sessões em estúdio ou ao ar livre. Memórias que duram para sempre. |
| 5 | 🎉 | Festas e Eventos | Confraternizações, aniversários de adultos, eventos corporativos. |
| 6 | 📸 | Ensaios Fotográficos | Gestantes, newborn, debutantes, books profissionais. |

- Emoji: 40px, centralizado (pode usar emoji nativo ou imagem)
- Título: Inter 600, 18px, `#4a2c3d`
- Descrição: Inter 400, 14px, `#8c6b7d`

---

### SEÇÃO 4 — Meu Trabalho (galeria de fotos)

```
Background: #fdf0f2
Padding: 60px 20px
```

**Título:** "Meu Trabalho" — Playfair Display 700, 32px, `#4a2c3d`, centralizado
**Linha decorativa** (igual seção anterior)
**Subtítulo:** "Alguns registros dos eventos que tive o prazer de fotografar" — Inter 400, 16px, `#8c6b7d`, centralizado

**Grid de fotos:**
```
Max-width: 1200px
Margin: 32px auto 0
Display: grid
Gap: 8px
Grid-template-columns: repeat(2, 1fr) mobile / repeat(3, 1fr) tablet / repeat(4, 1fr) desktop
```

Cada foto:
```
Border-radius: 8px
Overflow: hidden
Aspect-ratio: 1/1
Object-fit: cover
Cursor: pointer
Transition: transform 0.3s
Hover: transform: scale(1.03)
```

- Por enquanto usar 12 imagens placeholder de `https://picsum.photos/id/{ID}/600/600`
- Depois a Silvia substitui pelas fotos reais em `public/img/portfolio/`
- Imagens com `loading="lazy"`

**Lightbox (Alpine.js):**
```javascript
function portfolioGaleria() {
    return {
        aberto: false,
        atual: 0,
        fotos: [/* array de URLs */],
        abrir(i) { this.atual = i; this.aberto = true; document.body.style.overflow = 'hidden'; },
        fechar() { this.aberto = false; document.body.style.overflow = ''; },
        anterior() { if (this.atual > 0) this.atual--; },
        proxima() { if (this.atual < this.fotos.length - 1) this.atual++; }
    }
}
```
- Overlay: `rgba(74, 44, 61, 0.92)`
- Foto centralizada: max-width 90vw, max-height 85vh
- Botão fechar: X branco no canto superior direito
- Setas: esquerda/direita
- Teclado: ESC fecha, ← → navega

---

### SEÇÃO 5 — Depoimentos

```
Background: #ffffff
Padding: 60px 20px
```

**Título:** "O que dizem sobre meu trabalho" — Playfair Display 700, 32px, `#4a2c3d`, centralizado
**Linha decorativa**

**3 cards de depoimento:**
```
Display: grid
Grid-template-columns: 1fr (mobile) / repeat(3, 1fr) (desktop)
Gap: 24px
Max-width: 1000px
Margin: 40px auto 0
```

Cada card:
```
Background: #fdf0f2
Border-radius: 12px
Padding: 24px
Border-left: 4px solid #c27a8e
```

| # | Texto | Autor |
|---|-------|-------|
| 1 | "A Silvia é incrível! As fotos do aniversário da minha filha ficaram perfeitas. Super atenciosa e profissional." | Ana Silva, mãe da Beatriz |
| 2 | "Contratamos para a formatura da turma e superou todas as expectativas. Recomendo de olhos fechados!" | Prof. Carlos Mendes |
| 3 | "Já é a terceira vez que chamo a Silvia para nossos eventos. Sempre entrega um trabalho impecável." | Marcos Oliveira |

- Texto: Inter 400, 15px, `#4a2c3d`, font-style italic
- Autor: Inter 600, 14px, `#c27a8e`, margin-top 16px

---

### SEÇÃO 6 — CTA Final

```
Background: linear-gradient(135deg, #c27a8e 0%, #a85d73 100%)
Padding: 60px 20px
Text-align: center
```

- Título: "Vamos registrar seu próximo momento especial?" — Playfair Display 700, 28px, `#ffffff`
- Subtítulo: "Entre em contato e faça seu orçamento sem compromisso" — Inter 400, 16px, `rgba(255,255,255,0.85)`

**Botão WhatsApp (branco):**
```
Background: #ffffff
Color: #c27a8e
Border: none
Border-radius: 50px
Padding: 18px 48px
Font: Inter 700 18px
Margin-top: 24px
Display: inline-block
Text-decoration: none
```
- Texto: "📱 Falar com a Silvia"
- Link: `https://wa.me/5511987654321?text=Olá Silvia! Vi seu site e gostaria de fazer um orçamento.`
- Hover: `background: #fce4ec`

**Número abaixo:** "(11) 98765-4321" — Inter 500, 16px, `rgba(255,255,255,0.8)`, margin-top 12px

---

### SEÇÃO 7 — Footer

```
Background: #4a2c3d
Padding: 32px 20px
Text-align: center
```

**Linha de contato (centralizada):**
- Ícone `bi bi-whatsapp` branco 18px + "(11) 98765-4321" — link WhatsApp
- Separador: " · "
- Ícone `bi bi-instagram` branco 18px + "@silviasouzafotografa" — link Instagram
- Inter 400, 14px, `rgba(255,255,255,0.7)`, hover `#ffffff`

**Copyright:**
- "© 2026 Silvia Souza Fotografa" — Inter 400, 14px, `rgba(255,255,255,0.7)`, margin-top 12px
- "Fotografia profissional desde 1985 📸" — Inter 400, 13px, `rgba(255,255,255,0.5)`

---

## Botão WhatsApp Flutuante (fixo, todas as seções)

```
Position: fixed
Bottom: 24px
Right: 24px
Width: 56px
Height: 56px
Border-radius: 50%
Background: #25D366
Color: #ffffff
Font-size: 28px
Box-shadow: 0 4px 12px rgba(0,0,0,0.2)
Z-index: 999
Display: flex
Align-items: center
Justify-content: center
Text-decoration: none
```
- Ícone: `bi bi-chat-fill` (ou `bi bi-whatsapp`)
- Link: `https://wa.me/5511987654321`
- Hover: `background: #1DA851`, `transform: scale(1.1)`
- Animação pulse sutil a cada 3s:
```css
@keyframes pulse-whatsapp {
    0% { box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
    50% { box-shadow: 0 4px 20px rgba(37,211,102,0.4); }
    100% { box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
}
.btn-whatsapp-float { animation: pulse-whatsapp 3s infinite; }
```

---

## Dados configuráveis

Criar estas constantes em `config/site.php` para facilitar troca:

```php
<?php

return [
    'nome' => 'Silvia Souza',
    'titulo' => 'Silvia Souza Fotografa',
    'subtitulo' => 'Fotógrafa',
    'tagline' => 'Cada foto, uma memória guardada para sempre.',
    'desde' => '1985',
    'telefone' => '(11) 98765-4321',
    'whatsapp_link' => 'https://wa.me/5511987654321',
    'whatsapp_mensagem' => 'Olá! Gostaria de saber mais sobre seus serviços de fotografia.',
    'instagram' => '@silviasouzafotografa',
    'instagram_link' => 'https://instagram.com/silviasouzafotografa',
    'foto_perfil' => 'img/silvia.jpg',
];
```

Na view, usar: `{{ config('site.telefone') }}`, `{{ config('site.whatsapp_link') }}`, etc.

---

## Arquivos a criar/alterar

| Arquivo | Ação |
|---------|------|
| `app/Http/Controllers/HomeController.php` | Criar |
| `resources/views/home.blade.php` | Criar |
| `config/site.php` | Criar |
| `routes/web.php` | Alterar rota `/` de redirect para HomeController |
| `public/img/portfolio/` | Criar pasta (vazia, fotos serão adicionadas depois) |
| `public/img/silvia.jpg` | Placeholder (criar pasta, foto adicionada depois) |
| `public/css/custom.css` | Adicionar estilos da home (seções, hero, cards, lightbox, float button, CTA gradient, footer escuro) |

---

## Teste

Criar `tests/Feature/HomeTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomeTest extends TestCase
{
    public function test_home_carrega(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Silvia Souza');
        $response->assertSee('Fotografa');
    }

    public function test_home_mostra_whatsapp(): void
    {
        $response = $this->get('/');
        $response->assertSee('wa.me');
        $response->assertSee('98765-4321');
    }

    public function test_home_mostra_instagram(): void
    {
        $response = $this->get('/');
        $response->assertSee('@silviasouzafotografa');
        $response->assertSee('instagram.com');
    }

    public function test_home_tem_link_login_discreto(): void
    {
        $response = $this->get('/');
        $response->assertSee('/login');
    }

    public function test_home_nao_exige_login(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $this->assertGuest();
    }

    public function test_home_mostra_servicos(): void
    {
        $response = $this->get('/');
        $response->assertSee('Festas Infantis');
        $response->assertSee('Casamentos');
        $response->assertSee('Ensaios');
    }

    public function test_home_mostra_depoimentos(): void
    {
        $response = $this->get('/');
        $response->assertSee('Ana Silva');
        $response->assertSee('Carlos Mendes');
        $response->assertSee('Marcos Oliveira');
    }
}
```

---

## Responsividade

| Elemento | Mobile < 768px | Tablet 768-1024px | Desktop > 1024px |
|----------|---------------|-------------------|-----------------|
| Foto Silvia | 140px circle | 160px circle | 180px circle |
| Nome | 32px | 38px | 42px |
| Número | 20px | 22px | 24px |
| Botão WhatsApp | 100% width | auto | auto |
| Cards serviço | 1 coluna | 2 colunas | 3 colunas |
| Grid fotos | 2 colunas | 3 colunas | 4 colunas |
| Depoimentos | 1 coluna | 1 coluna | 3 colunas |
| Header logo | 16px | 18px | 20px |
| Header login icon | 28px | 32px | 36px |
| Botão flutuante | 48px | 52px | 56px |
