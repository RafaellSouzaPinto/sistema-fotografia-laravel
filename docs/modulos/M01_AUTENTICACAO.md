# M01 — Autenticação

## Propósito

Login exclusivo da fotógrafa Silvia. Não há registro de novos usuários. A autenticação é manual via `Hash::check()` + `Auth::login()` porque a coluna de senha chama `senha`, não `password`.

## Arquivos

| Arquivo | Papel |
|---------|-------|
| `app/Http/Controllers/Auth/LoginController.php` | Lógica de login/logout |
| `app/Models/Usuario.php` | Model de autenticação customizado |
| `resources/views/auth/login.blade.php` | Tela de login |
| `database/migrations/2026_03_17_221501_create_usuarios_table.php` | Estrutura da tabela |
| `database/seeders/` | Seed que cria a Silvia |

## Tabela `usuarios`

```sql
id               bigint unsigned, PK
nome             varchar(255)
email            varchar(255), unique
senha            varchar(255)         ← bcrypt hash
remember_token   varchar(100), nullable
created_at       timestamp
updated_at       timestamp
deleted_at       timestamp, nullable  ← SoftDeletes
```

## Model Usuario

```php
// app/Models/Usuario.php
class Usuario extends Authenticatable
{
    use SoftDeletes;

    protected $table = 'usuarios';
    protected $fillable = ['nome', 'email', 'senha'];
    protected $hidden = ['senha', 'remember_token'];

    // CRÍTICO: informa ao Laravel que a senha está em 'senha', não 'password'
    public function getAuthPasswordName(): string
    {
        return 'senha';
    }

    public function getAuthPassword(): string
    {
        return $this->senha;
    }
}
```

## LoginController

```php
// app/Http/Controllers/Auth/LoginController.php

public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'senha' => 'required',
    ], [
        'email.required' => 'O e-mail é obrigatório.',
        'senha.required' => 'A senha é obrigatória.',
    ]);

    $usuario = Usuario::where('email', $request->email)->first();

    // NUNCA usar Auth::attempt() — coluna é 'senha', não 'password'
    if ($usuario && Hash::check($request->senha, $usuario->senha)) {
        Auth::login($usuario, $request->boolean('lembrar'));
        return redirect()->intended('/admin/dashboard');
    }

    return back()->withErrors(['email' => 'Credenciais inválidas.']);
}

public function logout(Request $request)
{
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
}
```

## Tela de login

- URL: `/login`
- Centralizada vertical e horizontalmente
- Ícone de câmera no topo
- Nome "Silvia Souza Fotografa" em Playfair Display
- Campos: e-mail + senha
- Checkbox "Lembrar-me"
- Botão rosa primário (#c27a8e) em largura total
- Sem link "Esqueci a senha" (usuário único, senha conhecida)

## Proteção das rotas admin

Todas as rotas em `/admin/*` usam middleware `auth`. Configurado em `routes/web.php`:

```php
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/dashboard', ...);
    // ...
});
```

Redirecionamento não autenticado: Laravel redireciona automaticamente para `/login`.

## Regras críticas

- **NUNCA usar `Auth::attempt()`** — não funciona com coluna `senha`
- Sempre usar `Hash::check($senhaDigitada, $usuario->senha)` + `Auth::login($usuario)`
- O seeder cria a Silvia com `Hash::make('123456')` na coluna `senha`
- Não há middleware de perfil/permissão — só existe uma usuária
