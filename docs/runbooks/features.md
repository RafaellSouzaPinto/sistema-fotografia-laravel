# Silvia Souza Fotografa — Features para Claude Code

Documento de execução sequencial. Cada feature é uma unidade isolada. Execute na ordem. Não pule etapas.

**Documentos de referência (leia antes de começar):**
- `ARCHITECTURE.md` → modelagem, rotas, regras de negócio
- `FRONTEND-SPEC.md` → cores, tipografia, componentes visuais, layout de cada tela

---

## FEATURE 0 — Criar projeto Laravel e configurar ambiente

### Objetivo
Criar o projeto Laravel 11 do zero, instalar dependências, configurar banco MariaDB, e garantir que `php artisan serve` funcione na porta 8000.

### Passos

1. Criar projeto Laravel 11:
```bash
composer create-project laravel/laravel silvia-souza-fotografa "11.*"
cd silvia-souza-fotografa
```

2. Instalar dependências do projeto:
```bash
composer require livewire/livewire "^3.0"
composer require google/apiclient "^2.0"
composer require masbug/flysystem-google-drive-ext "^2.0"
composer require staudenmeir/eloquent-has-many-deep "^1.0"
```

3. Instalar Bootstrap 5.3.2, Bootstrap Icons, Google Fonts:
- Não usar npm/Vite para CSS. Usar CDN direto no layout Blade:
```html
<!-- Bootstrap 5.3.2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
```

4. Configurar `.env`:
```env
APP_NAME="Silvia Souza Fotografa"
APP_URL=http://localhost:8000
APP_LOCALE=pt_BR
APP_TIMEZONE=America/Sao_Paulo

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=silvia_fotos
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

GOOGLE_DRIVE_FOLDER_ID=
GOOGLE_CREDENTIALS_PATH=storage/app/google/credentials.json
```

5. Criar banco de dados MariaDB:
```sql
CREATE DATABASE silvia_fotos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

6. Criar arquivo CSS customizado `public/css/custom.css` com as variáveis de cor do `FRONTEND-SPEC.md`:
```css
:root {
    --rosa-principal: #c27a8e;
    --rosa-hover: #a85d73;
    --rosa-claro: #fce4ec;
    --rosa-bg: #fdf0f2;
    --rosa-borda: #f0d4da;
    --branco: #ffffff;
    --texto-escuro: #4a2c3d;
    --texto-secundario: #8c6b7d;
    --verde-badge: #27ae60;
    --verde-claro: #d4f5e9;
    --cinza-badge: #95a5a6;
    --cinza-claro: #ecf0f1;
    --vermelho: #c0392b;
    --vermelho-claro: #fdecea;
    --header-border: #d4a0ad;
}

body {
    background-color: var(--rosa-bg);
    font-family: 'Inter', sans-serif;
    color: var(--texto-escuro);
}

h1, h2, h3, h4, h5, h6 {
    font-family: 'Playfair Display', serif;
}
```
Continuar com TODOS os estilos dos componentes globais descritos na seção 4 do `FRONTEND-SPEC.md` (botão primário, secundário, perigo, cards, badges, inputs, toast, modal).

7. Testar:
```bash
php artisan key:generate
php artisan serve
# Acessar http://localhost:8000 — deve mostrar página padrão Laravel
```

### Critério de conclusão
- `php artisan serve` roda sem erro
- Banco `silvia_fotos` existe e conecta
- Livewire instalado e publicado (`php artisan livewire:publish --config`)

---

## FEATURE 1 — Migrations e Models (banco de dados completo)

### Objetivo
Criar todas as 5 migrations e 5 models com colunas em pt-br, soft delete, e relacionamentos corretos.

### Passos

1. **Migration `create_usuarios_table`**:
```php
Schema::create('usuarios', function (Blueprint $table) {
    $table->id();
    $table->string('nome');
    $table->string('email')->unique();
    $table->string('senha');
    $table->timestamps();
    $table->softDeletes();
});
```

2. **Migration `create_clientes_table`**:
```php
Schema::create('clientes', function (Blueprint $table) {
    $table->id();
    $table->string('nome');
    $table->string('telefone', 20);
    $table->timestamps();
    $table->softDeletes();
});
```

3. **Migration `create_trabalhos_table`**:
```php
Schema::create('trabalhos', function (Blueprint $table) {
    $table->id();
    $table->string('titulo');
    $table->date('data_trabalho');
    $table->enum('tipo', ['previa', 'completo']);
    $table->enum('status', ['rascunho', 'publicado'])->default('rascunho');
    $table->string('drive_pasta_id')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

4. **Migration `create_trabalho_cliente_table`**:
```php
Schema::create('trabalho_cliente', function (Blueprint $table) {
    $table->id();
    $table->foreignId('trabalho_id')->constrained('trabalhos')->cascadeOnDelete();
    $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
    $table->string('token', 64)->unique();
    $table->timestamps();
    $table->softDeletes();
});
```

5. **Migration `create_fotos_table`**:
```php
Schema::create('fotos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('trabalho_id')->constrained('trabalhos')->cascadeOnDelete();
    $table->string('nome_arquivo');
    $table->string('drive_arquivo_id');
    $table->text('drive_thumbnail')->nullable();
    $table->bigInteger('tamanho_bytes')->default(0);
    $table->integer('ordem')->default(0);
    $table->timestamps();
    $table->softDeletes();
});
```

6. **Model `Usuario`** (`app/Models/Usuario.php`):
```php
- $table = 'usuarios'
- $fillable = ['nome', 'email', 'senha']
- SoftDeletes
- Sobrescrever para Auth funcionar:
  - getAuthPassword() → return $this->senha;
  - Mutator: setSenhaAttribute($value) → bcrypt
  - Ou usar cast personalizado
- No config/auth.php: providers.users.model → App\Models\Usuario
```

**ARMADILHA CRÍTICA**: O Laravel Auth usa `password` internamente. O model `Usuario` PRECISA mapear `senha` para funcionar com `Auth::attempt()`. Duas opções:
- **Opção A (recomendada):** Sobrescrever `getAuthPassword()` para retornar `$this->senha`
- **Opção B:** Criar accessor `getPasswordAttribute()` e mutator `setPasswordAttribute()`

Além disso, o `Auth::attempt(['email' => $email, 'password' => $senha])` procura a coluna `password`. Precisa de custom UserProvider ou sobrescrever `getAuthIdentifierName()` e usar `credentials` correto.

**Solução mais limpa:** No `LoginController`, fazer a verificação manual:
```php
$usuario = Usuario::where('email', $request->email)->first();
if ($usuario && Hash::check($request->senha, $usuario->senha)) {
    Auth::login($usuario);
    return redirect('/admin/dashboard');
}
```

7. **Model `Cliente`** (`app/Models/Cliente.php`):
```php
- $table = 'clientes'
- $fillable = ['nome', 'telefone']
- SoftDeletes
- Relacionamento: trabalhos() → belongsToMany(Trabalho::class, 'trabalho_cliente', 'cliente_id', 'trabalho_id')->withPivot('token')->withTimestamps()
```

8. **Model `Trabalho`** (`app/Models/Trabalho.php`):
```php
- $table = 'trabalhos'
- $fillable = ['titulo', 'data_trabalho', 'tipo', 'status', 'drive_pasta_id']
- $casts = ['data_trabalho' => 'date']
- SoftDeletes
- Relacionamentos:
  - clientes() → belongsToMany(Cliente::class, 'trabalho_cliente', 'trabalho_id', 'cliente_id')->withPivot('token')->withTimestamps()
  - fotos() → hasMany(Foto::class, 'trabalho_id')
```

9. **Model `TrabalhoCliente`** (`app/Models/TrabalhoCliente.php`):
```php
- $table = 'trabalho_cliente'
- $fillable = ['trabalho_id', 'cliente_id', 'token']
- SoftDeletes
- Relacionamentos:
  - trabalho() → belongsTo(Trabalho::class)
  - cliente() → belongsTo(Cliente::class)
```

10. **Model `Foto`** (`app/Models/Foto.php`):
```php
- $table = 'fotos'
- $fillable = ['trabalho_id', 'nome_arquivo', 'drive_arquivo_id', 'drive_thumbnail', 'tamanho_bytes', 'ordem']
- SoftDeletes
- Relacionamento: trabalho() → belongsTo(Trabalho::class)
```

11. Rodar migrations:
```bash
php artisan migrate
```

### Critério de conclusão
- `php artisan migrate` roda sem erro
- 5 tabelas criadas no banco `silvia_fotos`
- Todos os models com relacionamentos testáveis via `php artisan tinker`

---

## FEATURE 2 — Seed e sistema de autenticação (Login)

### Objetivo
Criar seed da Silvia, tela de login funcional com visual do `FRONTEND-SPEC.md` (Tela 1).

### Passos

1. **Seeder `UsuarioSeeder`**:
```php
Usuario::create([
    'nome'  => 'Silvia Souza',
    'email' => 'silviasouzafotografa@gmail.com',
    'senha' => bcrypt('123456'),
]);
```
Registrar no `DatabaseSeeder.php`.

2. **Tela de login** (`resources/views/auth/login.blade.php`):
- NÃO usar layout admin (tela standalone)
- Seguir EXATAMENTE a Tela 1 do `FRONTEND-SPEC.md`:
  - Fundo `#fdf0f2`
  - Card centralizado `max-width: 420px`, branco, border-radius 12px, sombra suave
  - Ícone câmera `bi bi-camera` rosa, 40px
  - Nome "Silvia Souza" — Playfair Display 700 32px
  - Subtítulo "Fotógrafa" — Playfair Display Italic 400 16px
  - Campo email com label "Email"
  - Campo senha com label "Senha" + botão olhinho (toggle show/hide via Alpine.js)
  - Botão "Entrar" rosa `#c27a8e` largura 100%
  - Sem links de "Esqueci senha" ou "Cadastrar"

3. **LoginController** (`app/Http/Controllers/Auth/LoginController.php`):
```php
public function showLoginForm()
{
    return view('auth.login');
}

public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'senha' => 'required',
    ]);

    $usuario = Usuario::where('email', $request->email)->first();

    if ($usuario && Hash::check($request->senha, $usuario->senha)) {
        Auth::login($usuario);
        return redirect('/admin/dashboard');
    }

    return back()->withErrors(['email' => 'Email ou senha incorretos.']);
}

public function logout()
{
    Auth::logout();
    return redirect('/login');
}
```

4. **Rotas de auth** em `routes/web.php`:
```php
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::redirect('/', '/login');
```

5. **Configurar Auth** em `config/auth.php`:
```php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\Usuario::class,
    ],
],
```

6. Rodar seed:
```bash
php artisan db:seed
```

7. Testar:
- Acessar `http://localhost:8000/login`
- Login com `silviasouzafotografa@gmail.com` / `123456`
- Redireciona para `/admin/dashboard` (pode dar 404 por enquanto, ok)
- Acessar `/admin/dashboard` sem login redireciona para `/login`

### Critério de conclusão
- Login funciona com email e senha corretos
- Redireciona para `/admin/dashboard` após login
- Mostra erro "Email ou senha incorretos" se errar
- Visual idêntico ao print da Tela 1 do Lovable

---

## FEATURE 3 — Layout admin e Dashboard (Meus Trabalhos)

### Objetivo
Criar o layout base do admin (header, navegação, fundo rosa) e a página "Meus Trabalhos" com listagem de cards, busca e filtro.

### Passos

1. **Layout admin** (`resources/views/layouts/admin.blade.php`):
- Seguir EXATAMENTE a especificação do header na Tela 2 do `FRONTEND-SPEC.md`:
  - Header branco fixo no topo
  - Border-bottom `2px solid #d4a0ad`
  - Esquerda: ícone câmera rosa + "Silvia Souza Fotografa" (Playfair Display 700 20px)
  - Direita: links "Meus Trabalhos", "Meus Clientes", "Sair" (com ícone `bi bi-box-arrow-right`)
  - No mobile: links em row abaixo (nunca hamburger)
- Fundo `#fdf0f2`
- `@livewireStyles` no head
- `@livewireScripts` antes do `</body>`
- Slot `{{ $slot }}` para conteúdo Livewire ou `@yield('content')` para Blade puro

2. **Componente Livewire `JobList`** (`app/Livewire/Admin/JobList.php`):

Propriedades:
```php
public string $busca = '';
public string $filtroTipo = 'todos'; // todos, previa, completo
```

Query:
```php
public function render()
{
    $query = Trabalho::withCount(['fotos', 'clientes']);

    if ($this->busca) {
        $query->where('titulo', 'like', "%{$this->busca}%");
    }

    if ($this->filtroTipo !== 'todos') {
        $query->where('tipo', $this->filtroTipo);
    }

    $trabalhos = $query->orderBy('created_at', 'desc')->get();

    return view('livewire.admin.job-list', compact('trabalhos'))
        ->layout('layouts.admin');
}
```

Métodos:
```php
public function excluir($id)
{
    $trabalho = Trabalho::findOrFail($id);
    // TODO Feature 7: deletar pasta do Google Drive
    $trabalho->fotos()->delete();
    $trabalho->clientes()->detach();
    $trabalho->delete();
}
```

3. **View `livewire/admin/job-list.blade.php`**:
- Seguir EXATAMENTE a Tela 2 do `FRONTEND-SPEC.md`
- Título "Meus Trabalhos" (Playfair Display 700 28px) à esquerda
- Botão "+ Novo Trabalho" (rosa, `wire:navigate` para `/admin/jobs/create`) à direita
- Input de busca com `wire:model.live.debounce.300ms="busca"`
- 3 botões de filtro: "Todos" | "Prévias" | "Completos" com `wire:click="$set('filtroTipo', 'todos')"` etc.
- Grid de cards (`display: grid`, 1/2/3 colunas responsivo)
- Cada card:
  - Título (Playfair Display 600 18px)
  - Data formatada (`$trabalho->data_trabalho->format('d/m/Y')`)
  - Badge tipo (rosa claro para prévia, verde claro para completo)
  - Badge status (verde para publicado, cinza para rascunho)
  - Contadores: ícone `bi bi-image` + `{fotos_count} fotos` · ícone `bi bi-people` + `{clientes_count} clientes`
  - Botões: "Editar" (link para `/admin/jobs/{id}/edit`), "Ver Links" (modal ou redirect), "Excluir" (vermelho, `wire:click="excluir({{ $trabalho->id }})"` com `wire:confirm`)
- Estado vazio quando 0 trabalhos

4. **Rota**:
```php
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/dashboard', JobList::class)->name('admin.dashboard');
});
```

5. Seed de dados fake para testar (opcional, pode criar `TrabalhoSeeder`):
```php
Trabalho::create(['titulo' => 'Casamento Ana e João', 'data_trabalho' => '2026-03-15', 'tipo' => 'completo', 'status' => 'publicado']);
Trabalho::create(['titulo' => 'Aniversário 15 anos Maria', 'data_trabalho' => '2026-02-22', 'tipo' => 'previa', 'status' => 'publicado']);
Trabalho::create(['titulo' => 'Ensaio Família Santos', 'data_trabalho' => '2026-04-10', 'tipo' => 'completo', 'status' => 'rascunho']);
```

### Critério de conclusão
- Após login, redireciona para dashboard com cards visíveis
- Busca filtra por nome em tempo real
- Filtro "Prévias" e "Completos" funcionam
- Botão "Excluir" remove o trabalho (com confirmação)
- Visual idêntico aos prints da Tela 2 do Lovable

---

## FEATURE 4 — CRUD de Trabalhos (Novo + Editar)

### Objetivo
Tela de criar e editar trabalho com os campos título, data, tipo. Corresponde às Telas 3 e 5 do `FRONTEND-SPEC.md`.

### Passos

1. **Componente Livewire `JobForm`** (`app/Livewire/Admin/JobForm.php`):

Propriedades:
```php
public ?int $trabalhoId = null;
public string $titulo = '';
public string $data_trabalho = '';
public string $tipo = 'previa';

// Controle
public bool $salvo = false;
```

Mount (para edição):
```php
public function mount($id = null)
{
    if ($id) {
        $trabalho = Trabalho::findOrFail($id);
        $this->trabalhoId = $trabalho->id;
        $this->titulo = $trabalho->titulo;
        $this->data_trabalho = $trabalho->data_trabalho->format('Y-m-d');
        $this->tipo = $trabalho->tipo;
        $this->salvo = true; // permite mostrar seções de clientes e fotos
    }
}
```

Rules:
```php
protected $rules = [
    'titulo' => 'required|string|max:255',
    'data_trabalho' => 'required|date',
    'tipo' => 'required|in:previa,completo',
];
```

Salvar:
```php
public function salvar()
{
    $this->validate();

    $trabalho = Trabalho::updateOrCreate(
        ['id' => $this->trabalhoId],
        [
            'titulo' => $this->titulo,
            'data_trabalho' => $this->data_trabalho,
            'tipo' => $this->tipo,
        ]
    );

    $this->trabalhoId = $trabalho->id;
    $this->salvo = true;

    // TODO Feature 7: criar pasta no Google Drive se não existir

    $this->dispatch('notify', message: 'Trabalho salvo!');

    // Se era novo, redirecionar para edição com ID
    if (!$this->trabalhoId) {
        return redirect()->route('admin.jobs.edit', $trabalho->id);
    }
}
```

2. **View `livewire/admin/job-form.blade.php`**:
- Link de retorno: "← Voltar para Meus Trabalhos" (Inter 400 14px rosa)
- Título da página: "Novo Trabalho" ou "Editar Trabalho" (Playfair Display 700 28px)

- **Seção 1 — Card "Informações do Trabalho"**:
  - Título seção: "Informações do Trabalho" (Playfair Display Italic 600 20px)
  - Campo "Nome do trabalho": `wire:model="titulo"`
  - Campo "Data do trabalho": `wire:model="data_trabalho"`, input type date
  - Campo "Tipo": dois radio buttons estilizados como botões card (seguir `FRONTEND-SPEC.md` Tela 3):
    - "Prévia (amostra de fotos)"
    - "Trabalho Completo (entrega final)"
    - `wire:model="tipo"`
  - Botão "Salvar alterações" (rosa, `wire:click="salvar"`)

- **Seção 2 — Clientes** (só aparece se `$salvo === true`):
  - Incluir componente Livewire `ClientManager` aqui: `@livewire('admin.client-manager', ['trabalhoId' => $trabalhoId])`

- **Seção 3 — Fotos** (só aparece se `$salvo === true`):
  - Incluir componente Livewire `PhotoUploader` aqui: `@livewire('admin.photo-uploader', ['trabalhoId' => $trabalhoId])`

- **Seção 4 — Publicar** (só aparece se `$salvo` e tem ≥1 foto e ≥1 cliente):
  - Botão "Publicar trabalho e liberar links" (verde `#27ae60`)
  - `wire:click="publicar"`

3. **Método publicar**:
```php
public function publicar()
{
    $trabalho = Trabalho::findOrFail($this->trabalhoId);

    if ($trabalho->fotos()->count() === 0 || $trabalho->clientes()->count() === 0) {
        $this->dispatch('notify', message: 'Adicione pelo menos 1 foto e 1 cliente antes de publicar.', type: 'error');
        return;
    }

    $trabalho->update(['status' => 'publicado']);
    $this->dispatch('notify', message: 'Trabalho publicado! Links liberados.');
}
```

4. **Rotas**:
```php
Route::get('/jobs/create', JobForm::class)->name('admin.jobs.create');
Route::get('/jobs/{id}/edit', JobForm::class)->name('admin.jobs.edit');
```

### Critério de conclusão
- Criar trabalho com título, data, tipo funciona
- Editar trabalho carrega dados existentes
- Seções de clientes e fotos só aparecem após salvar
- Botão publicar muda status para "publicado"
- Visual idêntico às Telas 3 e 5 do Lovable

---

## FEATURE 5 — Gerenciamento de Clientes no Trabalho (ClientManager)
##
### Objetivo
Componente Livewire dentro da tela de trabalho. Busca por telefone, auto-preenche nome, adiciona cliente, gera token, exibe link copiável. Corresponde à Tela 6 do `FRONTEND-SPEC.md`.

### Passos

1. **Componente Livewire `ClientManager`** (`app/Livewire/Admin/ClientManager.php`):

Propriedades:
```php
public int $trabalhoId;
public string $telefone = '';
public string $nome = '';
public ?int $clienteExistenteId = null;
public bool $clienteEncontrado = false;
```

Busca por telefone (dispara ao sair do campo):
```php
public function buscarPorTelefone()
{
    $this->clienteEncontrado = false;
    $this->clienteExistenteId = null;
    $this->nome = '';

    if (strlen($this->telefone) < 10) return;

    // Limpar máscara para buscar
    $telefoneLimpo = preg_replace('/\D/', '', $this->telefone);

    $cliente = Cliente::whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(telefone, '(', ''), ')', ''), '-', ''), ' ', '') = ?", [$telefoneLimpo])->first();

    if ($cliente) {
        $this->nome = $cliente->nome;
        $this->clienteExistenteId = $cliente->id;
        $this->clienteEncontrado = true;
    }
}
```

Adicionar cliente ao trabalho:
```php
public function adicionar()
{
    $this->validate([
        'telefone' => 'required|min:10',
        'nome' => 'required|string|max:255',
    ]);

    // Criar ou atualizar cliente
    if ($this->clienteExistenteId) {
        $cliente = Cliente::find($this->clienteExistenteId);
        $cliente->update(['nome' => $this->nome]); // atualiza nome se editou
    } else {
        $cliente = Cliente::create([
            'nome' => $this->nome,
            'telefone' => $this->telefone,
        ]);
    }

    // Verificar se já está vinculado a este trabalho
    $trabalho = Trabalho::findOrFail($this->trabalhoId);
    if ($trabalho->clientes()->where('cliente_id', $cliente->id)->exists()) {
        $this->dispatch('notify', message: 'Este cliente já está vinculado a este trabalho.', type: 'error');
        return;
    }

    // Vincular com token
    $token = Str::random(64);
    $trabalho->clientes()->attach($cliente->id, ['token' => $token]);

    // Limpar campos
    $this->telefone = '';
    $this->nome = '';
    $this->clienteExistenteId = null;
    $this->clienteEncontrado = false;

    $this->dispatch('notify', message: 'Cliente adicionado!');
}
```

Remover cliente do trabalho:
```php
public function remover($clienteId)
{
    $trabalho = Trabalho::findOrFail($this->trabalhoId);
    $trabalho->clientes()->detach($clienteId);
    $this->dispatch('notify', message: 'Cliente removido.');
}
```

Render:
```php
public function render()
{
    $clientesVinculados = Trabalho::findOrFail($this->trabalhoId)
        ->clientes()
        ->withPivot('token')
        ->get();

    return view('livewire.admin.client-manager', compact('clientesVinculados'));
}
```

2. **View `livewire/admin/client-manager.blade.php`**:
- Card com título "Clientes que vão receber este trabalho" (Playfair Display Italic 600 20px)
- Campo "Telefone do cliente" com máscara `(99) 99999-9999`:
  - Máscara via Alpine.js: `x-mask="(99) 99999-9999"` (precisa do plugin `@alpinejs/mask`)
  - OU máscara manual via JS
  - `wire:model="telefone"` + `wire:blur="buscarPorTelefone"`
- Campo "Nome do cliente":
  - `wire:model="nome"`
  - Se `$clienteEncontrado`: borda verde `#27ae60`, texto "✓ Cliente encontrado!" abaixo em verde
  - Se não encontrado e telefone digitado: texto "Novo cliente — digite o nome" em cinza
- Botão "+ Adicionar cliente" (rosa, `wire:click="adicionar"`)

- **Lista de clientes vinculados**:
  - Cada cliente em row com fundo `#fce4ec`, border-radius 8px
  - Nome (Inter 500 16px)
  - Telefone (Inter 400 14px cinza)
  - Link truncado: `{{ url('/galeria/' . $cliente->pivot->token) }}`
  - Botão "Copiar link" com Alpine.js:
    ```html
    <button x-data @click="navigator.clipboard.writeText('{{ url('/galeria/' . $cliente->pivot->token) }}'); $el.textContent = '✓ Copiado!'; setTimeout(() => $el.textContent = 'Copiar link', 2000)">
        Copiar link
    </button>
    ```
  - Botão "Remover" (vermelho, `wire:click="remover({{ $cliente->id }})"` com `wire:confirm`)

3. **Instalar Alpine.js Mask** (para máscara de telefone):
- Via CDN no layout: `<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/mask@3.x.x/dist/cdn.min.js"></script>`
- Carregar ANTES do Alpine.js principal (que o Livewire já inclui)

### Critério de conclusão
- Digitar telefone existente preenche nome automaticamente
- Digitar telefone novo permite criar cliente
- Token gerado e link exibido corretamente
- Botão "Copiar link" funciona (clipboard)
- Remover cliente funciona com confirmação
- Visual idêntico à Tela 6 do Lovable

---

## FEATURE 6 — Upload de Fotos (PhotoUploader) — sem Google Drive

### Objetivo
Componente Livewire para upload de múltiplas fotos com preview de thumbnails e remoção. Nesta feature, as fotos são salvas LOCALMENTE em `storage/app/public/fotos/{trabalho_id}/`. A integração com Google Drive é feita na Feature 7.

### Passos

1. **Componente Livewire `PhotoUploader`** (`app/Livewire/Admin/PhotoUploader.php`):

Propriedades:
```php
public int $trabalhoId;
public $arquivos = []; // temporário do upload Livewire
```

Upload:
```php
public function updatedArquivos()
{
    $this->validate([
        'arquivos.*' => 'file|mimes:jpg,jpeg,png,psd,tif,tiff|max:204800', // 200MB
    ]);

    foreach ($this->arquivos as $arquivo) {
        $nomeOriginal = $arquivo->getClientOriginalName();
        $tamanho = $arquivo->getSize();

        // Salvar localmente por enquanto
        $path = $arquivo->store("fotos/{$this->trabalhoId}", 'public');

        Foto::create([
            'trabalho_id' => $this->trabalhoId,
            'nome_arquivo' => $nomeOriginal,
            'drive_arquivo_id' => $path, // por enquanto é path local
            'drive_thumbnail' => asset("storage/{$path}"), // URL local
            'tamanho_bytes' => $tamanho,
            'ordem' => Foto::where('trabalho_id', $this->trabalhoId)->count(),
        ]);
    }

    $this->arquivos = [];
    $this->dispatch('notify', message: 'Fotos enviadas!');
}
```

Remover foto:
```php
public function removerFoto($fotoId)
{
    $foto = Foto::findOrFail($fotoId);
    Storage::disk('public')->delete($foto->drive_arquivo_id);
    $foto->delete();
    $this->dispatch('notify', message: 'Foto removida.');
}
```

Render:
```php
public function render()
{
    $fotos = Foto::where('trabalho_id', $this->trabalhoId)->orderBy('ordem')->get();
    return view('livewire.admin.photo-uploader', compact('fotos'));
}
```

2. **View `livewire/admin/photo-uploader.blade.php`**:
- Card com título "Fotos do trabalho" (Playfair Display Italic 600 20px)
- Área de upload (drag & drop):
  ```html
  <div wire:loading.class="opacity-50">
      <label for="upload-fotos" class="area-upload">
          <i class="bi bi-cloud-arrow-up"></i>
          <p>Arraste as fotos aqui ou clique para selecionar</p>
          <small>Formatos aceitos: JPG, PNG, PSD, TIF</small>
      </label>
      <input type="file" id="upload-fotos" wire:model="arquivos" multiple accept=".jpg,.jpeg,.png,.psd,.tif,.tiff" hidden>
  </div>
  ```
  - Estilizar com CSS do `FRONTEND-SPEC.md` (borda dashed `#e0c4cc`, 2px, border-radius 12px)

- Barra de progresso Livewire:
  ```html
  <div wire:loading wire:target="arquivos">
      <div class="progress">
          <div class="progress-bar" style="width: 100%; background: #c27a8e;"></div>
      </div>
      <small>Enviando fotos...</small>
  </div>
  ```

- Grid de thumbnails:
  - `display: grid`, 3 colunas mobile, 5 desktop, gap 8px
  - Cada thumbnail: `aspect-ratio: 1/1`, `object-fit: cover`, `border-radius: 8px`
  - Botão X vermelho no canto (absolute, circle 24px `#c0392b`):
    ```html
    <button wire:click="removerFoto({{ $foto->id }})" wire:confirm="Remover esta foto?">✕</button>
    ```

3. **Storage link**:
```bash
php artisan storage:link
```

4. **Livewire upload config** — em `config/livewire.php`:
```php
'temporary_file_upload' => [
    'disk' => 'local',
    'rules' => ['required', 'file', 'max:204800'], // 200MB
    'directory' => 'livewire-tmp',
],
```

### Critério de conclusão
- Upload de múltiplas fotos funciona
- Thumbnails aparecem em grid após upload
- Remover foto funciona (deleta do storage e do banco)
- Barra de progresso aparece durante upload
- Fotos persistem ao recarregar página
- Visual idêntico às Telas 4 e 6 do Lovable

---

## FEATURE 7 — Integração Google Drive API

### Objetivo
Substituir o storage local pela Google Drive API. Criar pasta por trabalho, upload de fotos pro Drive, thumbnails via API, download via stream.

### Passos

1. **Service `GoogleDriveService`** (`app/Services/GoogleDriveService.php`):
```php
class GoogleDriveService
{
    protected $service;

    public function __construct()
    {
        $client = new \Google\Client();
        $client->setAuthConfig(storage_path('app/google/credentials.json'));
        $client->addScope(\Google\Service\Drive::DRIVE);
        $this->service = new \Google\Service\Drive($client);
    }

    // Criar pasta no Drive
    public function criarPasta(string $nome, ?string $pastaRaizId = null): string
    {
        $metadata = new \Google\Service\Drive\DriveFile([
            'name' => $nome,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$pastaRaizId ?? config('services.google.drive_folder_id')],
        ]);
        $pasta = $this->service->files->create($metadata, ['fields' => 'id']);
        return $pasta->id;
    }

    // Upload de arquivo
    public function upload(string $pastaId, string $nomeArquivo, string $caminhoLocal, string $mimeType): array
    {
        $metadata = new \Google\Service\Drive\DriveFile([
            'name' => $nomeArquivo,
            'parents' => [$pastaId],
        ]);
        $conteudo = file_get_contents($caminhoLocal);
        $arquivo = $this->service->files->create($metadata, [
            'data' => $conteudo,
            'mimeType' => $mimeType,
            'uploadType' => 'multipart',
            'fields' => 'id, thumbnailLink, size',
        ]);
        return [
            'id' => $arquivo->id,
            'thumbnailLink' => $arquivo->thumbnailLink,
            'size' => $arquivo->size,
        ];
    }

    // Deletar arquivo
    public function deletar(string $arquivoId): void
    {
        $this->service->files->delete($arquivoId);
    }

    // Deletar pasta (e tudo dentro)
    public function deletarPasta(string $pastaId): void
    {
        $this->service->files->delete($pastaId);
    }

    // Download stream
    public function download(string $arquivoId)
    {
        $response = $this->service->files->get($arquivoId, ['alt' => 'media']);
        return $response->getBody();
    }

    // Obter metadados
    public function obterArquivo(string $arquivoId)
    {
        return $this->service->files->get($arquivoId, ['fields' => 'id, name, mimeType, size, thumbnailLink']);
    }
}
```

2. **Config** em `config/services.php`:
```php
'google' => [
    'drive_folder_id' => env('GOOGLE_DRIVE_FOLDER_ID'),
    'credentials_path' => env('GOOGLE_CREDENTIALS_PATH', 'storage/app/google/credentials.json'),
],
```

3. **Atualizar `JobForm`** — ao salvar, criar pasta no Drive:
```php
if (!$trabalho->drive_pasta_id) {
    $driveService = app(GoogleDriveService::class);
    $nomePasta = "{$trabalho->titulo} - " . ($trabalho->tipo === 'previa' ? 'Prévia' : 'Completo');
    $pastaId = $driveService->criarPasta($nomePasta);
    $trabalho->update(['drive_pasta_id' => $pastaId]);
}
```

4. **Atualizar `PhotoUploader`** — upload pro Drive em vez de local:
```php
foreach ($this->arquivos as $arquivo) {
    $driveService = app(GoogleDriveService::class);
    $trabalho = Trabalho::findOrFail($this->trabalhoId);

    $resultado = $driveService->upload(
        $trabalho->drive_pasta_id,
        $arquivo->getClientOriginalName(),
        $arquivo->getRealPath(),
        $arquivo->getMimeType()
    );

    Foto::create([
        'trabalho_id' => $this->trabalhoId,
        'nome_arquivo' => $arquivo->getClientOriginalName(),
        'drive_arquivo_id' => $resultado['id'],
        'drive_thumbnail' => $resultado['thumbnailLink'],
        'tamanho_bytes' => $resultado['size'] ?? $arquivo->getSize(),
        'ordem' => Foto::where('trabalho_id', $this->trabalhoId)->count(),
    ]);
}
```

5. **Atualizar exclusão** — deletar do Drive:
- `PhotoUploader::removerFoto()`: chamar `$driveService->deletar($foto->drive_arquivo_id)` antes de deletar do banco
- `JobList::excluir()`: chamar `$driveService->deletarPasta($trabalho->drive_pasta_id)` antes de deletar

6. **Thumbnail**: o Google Drive retorna `thumbnailLink` no upload. Salvar na coluna `drive_thumbnail`. Usar esse link nas views.

**ARMADILHA**: `thumbnailLink` do Google Drive expira e requer autenticação. Alternativa: usar a URL `https://drive.google.com/thumbnail?id={FILE_ID}&sz=w400` (funciona para arquivos com permissão pública) OU fazer proxy pelo servidor (route que faz stream do thumbnail).

Se a conta é paga e as fotos são privadas, criar rota de proxy:
```php
Route::get('/admin/thumbnail/{foto}', function (Foto $foto) {
    $driveService = app(GoogleDriveService::class);
    $stream = $driveService->download($foto->drive_arquivo_id);
    return response($stream)->header('Content-Type', 'image/jpeg');
});
```

### Critério de conclusão
- Upload de foto vai pro Google Drive (verificável na interface do Drive)
- Thumbnails aparecem na grid
- Excluir foto remove do Drive
- Excluir trabalho remove pasta do Drive
- Credenciais configuradas corretamente

---

## FEATURE 8 — Listagem e CRUD de Clientes (`/admin/clients`)

### Objetivo
Tela separada para listar, buscar, editar e excluir clientes (independente dos trabalhos).

### Passos

1. **Componente Livewire `ClientList`** (`app/Livewire/Admin/ClientList.php`):

Propriedades:
```php
public string $busca = '';
public ?int $editandoId = null;
public string $editNome = '';
public string $editTelefone = '';
```

Render:
```php
public function render()
{
    $clientes = Cliente::withCount('trabalhos')
        ->when($this->busca, fn($q) => $q->where('nome', 'like', "%{$this->busca}%")
            ->orWhere('telefone', 'like', "%{$this->busca}%"))
        ->orderBy('nome')
        ->get();

    return view('livewire.admin.client-list', compact('clientes'))
        ->layout('layouts.admin');
}
```

Editar inline:
```php
public function editar($id)
{
    $cliente = Cliente::findOrFail($id);
    $this->editandoId = $id;
    $this->editNome = $cliente->nome;
    $this->editTelefone = $cliente->telefone;
}

public function salvarEdicao()
{
    $this->validate([
        'editNome' => 'required|string|max:255',
        'editTelefone' => 'required|min:10',
    ]);

    Cliente::findOrFail($this->editandoId)->update([
        'nome' => $this->editNome,
        'telefone' => $this->editTelefone,
    ]);

    $this->editandoId = null;
    $this->dispatch('notify', message: 'Cliente atualizado!');
}

public function excluir($id)
{
    Cliente::findOrFail($id)->delete();
    $this->dispatch('notify', message: 'Cliente excluído.');
}
```

2. **View**: tabela responsiva (desktop) / cards (mobile) com nome, telefone, contagem de trabalhos, botões editar/excluir. Seguir Tela "Meus Clientes" do `FRONTEND-SPEC.md`.

3. **Rota**:
```php
Route::get('/clients', ClientList::class)->name('admin.clients');
```

### Critério de conclusão
- Lista todos os clientes com busca
- Edição inline funciona
- Excluir com confirmação funciona
- Contagem de trabalhos vinculados aparece

---

## FEATURE 9 — Galeria Pública do Cliente

### Objetivo
Página pública que o cliente acessa via link com token. Mostra saudação personalizada, fotos em grid, lightbox, e download. Corresponde às Telas 9, 10 e 11 do `FRONTEND-SPEC.md`.

### Passos

1. **Controller `GalleryController`** (`app/Http/Controllers/GalleryController.php`):
```php
public function show(string $token)
{
    $pivot = TrabalhoCliente::where('token', $token)->firstOrFail();
    $trabalho = $pivot->trabalho;
    $cliente = $pivot->cliente;
    $fotos = $trabalho->fotos()->orderBy('ordem')->paginate(20);

    return view('gallery.show', compact('trabalho', 'cliente', 'fotos', 'token'));
}

public function downloadFoto(string $token, Foto $foto)
{
    // Validar que o token pertence ao trabalho da foto
    $pivot = TrabalhoCliente::where('token', $token)->firstOrFail();
    abort_if($foto->trabalho_id !== $pivot->trabalho_id, 403);

    $driveService = app(GoogleDriveService::class);
    $stream = $driveService->download($foto->drive_arquivo_id);

    return response($stream)
        ->header('Content-Type', 'application/octet-stream')
        ->header('Content-Disposition', "attachment; filename=\"{$foto->nome_arquivo}\"");
}

public function downloadTodas(string $token)
{
    $pivot = TrabalhoCliente::where('token', $token)->firstOrFail();
    $trabalho = $pivot->trabalho;

    // Usar ZipStream para não acumular em memória
    // composer require maennchen/zipstream-php
    return response()->streamDownload(function () use ($trabalho) {
        $zip = new \ZipStream\ZipStream(
            outputName: Str::slug($trabalho->titulo) . '.zip',
        );

        $driveService = app(GoogleDriveService::class);

        foreach ($trabalho->fotos as $foto) {
            $stream = $driveService->download($foto->drive_arquivo_id);
            $zip->addFile($foto->nome_arquivo, $stream);
        }

        $zip->finish();
    }, Str::slug($trabalho->titulo) . '.zip');
}
```

2. **Instalar ZipStream**:
```bash
composer require maennchen/zipstream-php "^3.0"
```

3. **View `gallery/show.blade.php`**:
- Layout standalone (NÃO usa layout admin)
- Seguir EXATAMENTE as Telas 9-11 do `FRONTEND-SPEC.md`:

**Header:**
- Centralizado, fundo branco, border-bottom `#f0d4da`
- Ícone câmera rosa + "Silvia Souza Fotografa" (Playfair Display 700 22px rosa)
- "Fotografia profissional desde 1985" (Playfair Display Italic 14px cinza)

**Saudação:**
- "Olá, **{{ $cliente->nome }}**! 👋" (Playfair Display 700 36px)
- "Aqui estão as fotos do seu trabalho:" (Inter 400 16px cinza)
- Título do trabalho (Playfair Display 700 28px)
- Data formatada por extenso: `{{ $trabalho->data_trabalho->translatedFormat('d \d\e F \d\e Y') }}`
- Badge tipo

**Botão download geral (só se tipo = completo):**
- Botão rosa grande, largura total no mobile
- `<a href="{{ url("/galeria/{$token}/download") }}">📥 Baixar todas as fotos</a>`
- Texto de apoio: "Clique para baixar todas as fotos em um arquivo ZIP"

**Grid de fotos:**
- Grid responsivo: 2 cols mobile, 3 tablet, 4 desktop
- Cada foto: thumbnail com `object-fit: cover`, border-radius 8px
- Ícone download no canto inferior direito (absolute, circle, `bi bi-download`)
- Ao clicar: abre lightbox

**Lightbox (Alpine.js):**
```html
<div x-data="{ aberto: false, fotoAtual: 0, fotos: {{ $fotos->pluck('drive_thumbnail')->toJson() }} }">
    <!-- Overlay -->
    <div x-show="aberto" x-transition class="lightbox-overlay" @click.self="aberto = false" @keydown.escape.window="aberto = false" @keydown.arrow-left.window="fotoAtual = Math.max(0, fotoAtual - 1)" @keydown.arrow-right.window="fotoAtual = Math.min(fotos.length - 1, fotoAtual + 1)">
        <!-- Fechar -->
        <button @click="aberto = false">✕</button>
        <!-- Setas -->
        <button @click="fotoAtual--" x-show="fotoAtual > 0">‹</button>
        <img :src="fotos[fotoAtual]" />
        <button @click="fotoAtual++" x-show="fotoAtual < fotos.length - 1">›</button>
        <!-- Download -->
        <a :href="'/galeria/{{ $token }}/photo/' + fotoIds[fotoAtual]">Baixar esta foto</a>
    </div>
</div>
```
- Overlay: `rgba(74,44,61,0.92)`
- Swipe no mobile: usar touch events com Alpine.js

**Paginação:**
- Botão "Carregar mais fotos" (Botão Secundário, centralizado)
- OU usar `$fotos->links()` com estilo customizado

**Footer:**
- "© 2026 Silvia Souza Fotografa 🤍" (Inter 400 14px cinza)

4. **Rotas públicas** (sem middleware auth):
```php
Route::get('/galeria/{token}', [GalleryController::class, 'show'])->name('galeria.show');
Route::get('/galeria/{token}/download', [GalleryController::class, 'downloadTodas'])->name('galeria.download');
Route::get('/galeria/{token}/photo/{foto}', [GalleryController::class, 'downloadFoto'])->name('galeria.foto');
```

5. **Lazy loading** de imagens:
```html
<img loading="lazy" src="{{ $foto->drive_thumbnail }}" alt="{{ $foto->nome_arquivo }}">
```

### Critério de conclusão
- Acessar `/galeria/{token}` mostra saudação com nome do cliente
- Grid de fotos carrega com thumbnails
- Lightbox abre ao clicar na foto (navegação por seta e swipe)
- Download individual funciona
- Download ZIP funciona (só para tipo completo)
- Botão "Baixar todas" não aparece se tipo = prévia
- Token inválido retorna 404
- Visual idêntico às Telas 9-11 do Lovable

---

## FEATURE 10 — Toast de notificações e Modal de confirmação

### Objetivo
Sistema global de notificações (toast) e modal de confirmação para ações destrutivas.

### Passos

1. **Toast** — componente Alpine.js global no layout admin:
```html
<div x-data="{ mensagens: [] }"
     @notify.window="mensagens.push({texto: $event.detail.message, tipo: $event.detail.type || 'success'}); setTimeout(() => mensagens.shift(), 3000)">
    <template x-for="(msg, i) in mensagens" :key="i">
        <div class="toast-notification" :class="msg.tipo">
            <span x-text="msg.texto"></span>
        </div>
    </template>
</div>
```
- Estilizar conforme `FRONTEND-SPEC.md` seção 4.8 (posição fixed, top 20px, right 20px, fundo rosa, texto branco)

2. **Confirmação** — usar `wire:confirm` nativo do Livewire 3:
```html
<button wire:click="excluir({{ $id }})" wire:confirm="Tem certeza que deseja excluir este trabalho? Esta ação não pode ser desfeita.">
    Excluir
</button>
```
Linguagem: sempre clara, sem jargão. Ex: "Tem certeza que deseja remover este cliente do trabalho?"

### Critério de conclusão
- Toasts aparecem e somem em 3s
- Confirmação aparece antes de excluir/remover

---

## FEATURE 11 — Responsividade e Polish Final

### Objetivo
Garantir que todas as telas funcionam em mobile, tablet e desktop. Ajustar espaçamentos, fontes, touch targets.

### Checklist

1. **Login**: card centralizado, inputs largura total, botão largura total
2. **Dashboard**: cards em 1 col (mobile), 2 col (tablet), 3 col (desktop)
3. **Novo/Editar trabalho**: tudo em coluna única, botões largura total no mobile
4. **ClientManager**: lista de clientes com botões empilhados no mobile
5. **PhotoUploader**: grid 3 col (mobile), 5 col (desktop)
6. **Galeria pública**: grid 2 col (mobile), 3 col (tablet), 4 col (desktop)
7. **Lightbox**: swipe funciona, botão fechar acessível, download acessível
8. **Header admin**: navegação em row (nunca hamburger), empilha no mobile se necessário
9. **Touch targets**: todos os botões com `min-height: 44px`
10. **Inputs**: `font-size: 16px` mínimo (evitar zoom iOS)
11. **Focus visible**: todos os elementos interativos com outline visível ao usar Tab

### CSS media queries principais
```css
/* Mobile */
@media (max-width: 767px) {
    .grid-trabalhos { grid-template-columns: 1fr; }
    .grid-fotos-admin { grid-template-columns: repeat(3, 1fr); }
    .grid-galeria { grid-template-columns: repeat(2, 1fr); }
    .btn-acao { width: 100%; margin-bottom: 8px; }
}

/* Tablet */
@media (min-width: 768px) and (max-width: 1024px) {
    .grid-trabalhos { grid-template-columns: repeat(2, 1fr); }
    .grid-galeria { grid-template-columns: repeat(3, 1fr); }
}

/* Desktop */
@media (min-width: 1025px) {
    .grid-trabalhos { grid-template-columns: repeat(3, 1fr); }
    .grid-fotos-admin { grid-template-columns: repeat(5, 1fr); }
    .grid-galeria { grid-template-columns: repeat(4, 1fr); }
}
```

### Critério de conclusão
- Todas as telas testadas em viewport 375px (mobile), 768px (tablet), 1280px (desktop)
- Nenhum overflow horizontal
- Touch targets ≥ 44px
- Nenhum texto menor que 14px
