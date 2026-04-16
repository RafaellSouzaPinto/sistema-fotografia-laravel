# M16 — Alterar Senha no Painel Admin

## Propósito

A Silvia pode trocar a própria senha pelo painel admin, sem precisar de artisan ou banco. Uma página simples (`/admin/perfil`) com formulário de 3 campos: senha atual, nova senha e confirmação. Acessível pelo menu de navegação.

## Arquivos

| Arquivo | Papel |
|---------|-------|
| `app/Livewire/Admin/AlterarSenha.php` | Componente com validação e troca de senha |
| `resources/views/livewire/admin/alterar-senha.blade.php` | Formulário |
| `routes/web.php` | Rota `/admin/perfil` |
| `resources/views/layouts/admin.blade.php` | Link no menu (navbar ou dropdown) |

Nenhuma migration necessária — usa coluna `senha` já existente em `usuarios`.

## Rota

```php
// routes/web.php — dentro do grupo middleware('auth')->prefix('admin')

Route::get('/perfil', function () {
    return view('livewire.admin.alterar-senha');
})->name('admin.perfil');
```

## Componente AlterarSenha

```php
// app/Livewire/Admin/AlterarSenha.php

<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class AlterarSenha extends Component
{
    public string $senhaAtual = '';
    public string $novaSenha = '';
    public string $confirmacaoSenha = '';

    protected function rules(): array
    {
        return [
            'senhaAtual'       => 'required',
            'novaSenha'        => 'required|min:6',
            'confirmacaoSenha' => 'required|same:novaSenha',
        ];
    }

    protected function messages(): array
    {
        return [
            'senhaAtual.required'       => 'Informe a senha atual.',
            'novaSenha.required'        => 'Informe a nova senha.',
            'novaSenha.min'             => 'A nova senha deve ter pelo menos 6 caracteres.',
            'confirmacaoSenha.required' => 'Confirme a nova senha.',
            'confirmacaoSenha.same'     => 'As senhas não coincidem.',
        ];
    }

    public function salvar(): void
    {
        $this->validate();

        $usuario = Auth::user();

        // Verifica se a senha atual está correta
        // NUNCA usar Auth::attempt() — coluna é 'senha', não 'password'
        if (!Hash::check($this->senhaAtual, $usuario->senha)) {
            $this->addError('senhaAtual', 'Senha atual incorreta.');
            return;
        }

        $usuario->update([
            'senha' => Hash::make($this->novaSenha),
        ]);

        // Limpa os campos
        $this->reset(['senhaAtual', 'novaSenha', 'confirmacaoSenha']);

        $this->dispatch('notify', tipo: 'sucesso', mensagem: 'Senha alterada com sucesso!');
    }

    public function render()
    {
        return view('livewire.admin.alterar-senha')
            ->layout('layouts.admin', ['titulo' => 'Meu Perfil']);
    }
}
```

## View alterar-senha.blade.php

```blade
{{-- resources/views/livewire/admin/alterar-senha.blade.php --}}

<div class="container py-4" style="max-width:560px">

    {{-- Header --}}
    <div class="mb-4">
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm mb-3">
            <i class="bi bi-arrow-left me-1"></i> Voltar
        </a>
        <h1 style="font-family:'Playfair Display',serif; color:#4a2c3d">
            <i class="bi bi-person-circle me-2" style="color:#c27a8e"></i>
            Meu Perfil
        </h1>
        <p class="text-secondary">{{ auth()->user()->nome }}</p>
    </div>

    {{-- Card do formulário --}}
    <div class="card border-0 shadow-sm" style="border-radius:12px">
        <div class="card-body p-4">
            <h5 class="mb-4" style="color:#4a2c3d">Alterar Senha</h5>

            <div class="mb-3">
                <label class="form-label fw-semibold">Senha atual</label>
                <input type="password"
                       wire:model="senhaAtual"
                       class="form-control form-control-lg @error('senhaAtual') is-invalid @enderror"
                       placeholder="Digite sua senha atual">
                @error('senhaAtual')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Nova senha</label>
                <input type="password"
                       wire:model="novaSenha"
                       class="form-control form-control-lg @error('novaSenha') is-invalid @enderror"
                       placeholder="Mínimo 6 caracteres">
                @error('novaSenha')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Confirmar nova senha</label>
                <input type="password"
                       wire:model="confirmacaoSenha"
                       class="form-control form-control-lg @error('confirmacaoSenha') is-invalid @enderror"
                       placeholder="Repita a nova senha">
                @error('confirmacaoSenha')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button wire:click="salvar"
                    wire:loading.attr="disabled"
                    class="btn btn-lg w-100"
                    style="background:#c27a8e; color:#fff; border:none; border-radius:8px; font-weight:600">
                <span wire:loading.remove>Salvar nova senha</span>
                <span wire:loading>Salvando...</span>
            </button>
        </div>
    </div>

    {{-- Informação extra --}}
    <p class="text-center text-secondary small mt-3">
        <i class="bi bi-shield-lock me-1"></i>
        A senha é armazenada de forma segura (criptografada)
    </p>
</div>
```

## Link no menu admin

Adicionar ao layout `resources/views/layouts/admin.blade.php` no dropdown/navbar do usuário:

```blade
{{-- No navbar, ao lado do nome da usuária --}}
<div class="dropdown">
    <button class="btn btn-link dropdown-toggle text-decoration-none"
            style="color:#4a2c3d; font-weight:500"
            data-bs-toggle="dropdown">
        <i class="bi bi-person-circle me-1" style="color:#c27a8e"></i>
        {{ auth()->user()->nome }}
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
        <li>
            <a class="dropdown-item" href="{{ route('admin.perfil') }}">
                <i class="bi bi-key me-2" style="color:#c27a8e"></i>
                Alterar senha
            </a>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="dropdown-item text-danger">
                    <i class="bi bi-box-arrow-right me-2"></i>
                    Sair
                </button>
            </form>
        </li>
    </ul>
</div>
```

## Layout da tela

```
┌─────────────────────────────────────────────┐
│  [← Voltar]                                 │
│  👤 Meu Perfil                              │
│  Silvia Souza                               │
│                                             │
│  ┌─────────────────────────────────────┐    │
│  │  Alterar Senha                      │    │
│  │                                     │    │
│  │  Senha atual                        │    │
│  │  [••••••••••••••]                   │    │
│  │                                     │    │
│  │  Nova senha                         │    │
│  │  [••••••••••••••]                   │    │
│  │                                     │    │
│  │  Confirmar nova senha               │    │
│  │  [••••••••••••••]                   │    │
│  │                                     │    │
│  │  [   Salvar nova senha          ]   │    │
│  └─────────────────────────────────────┘    │
│                                             │
│  🔒 A senha é armazenada de forma segura    │
└─────────────────────────────────────────────┘
```

## Regras

- **NUNCA usar `Auth::attempt()`** para verificar a senha atual — usar `Hash::check($senhaAtual, $usuario->senha)`
- Nova senha mínimo 6 caracteres (mesmo critério da senha inicial)
- Após salvar, os 3 campos são limpos (reset)
- Formulário protegido pelo middleware `auth` via rota em `/admin/*`
- Não há "esqueci minha senha" — usuário único, recuperação manual se necessário
- O campo de confirmação usa `same:novaSenha` — o Livewire valida no servidor
