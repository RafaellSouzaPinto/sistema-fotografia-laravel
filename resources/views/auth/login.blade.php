<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Silvia Souza Fotografa</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="/css/custom.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #fdf0f2;
        }
    </style>
</head>
<body>
    <div x-data="{ mostrarSenha: false }" style="width: 100%; max-width: 420px; padding: 16px;">
        <!-- Logo -->
        <div class="text-center mb-4">
            <img src="{{ asset('img/img-silvia-logo.png') }}" alt="Silvia Souza Fotógrafa" style="max-width: 300px; height: auto; margin: 0 auto 32px; display: block;">
        </div>

        <!-- Card -->
        <div style="background: #ffffff; border-radius: 12px; padding: 32px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
            <form method="POST" action="{{ route('login.post') }}">
                @csrf

                @if ($errors->any())
                    <div class="alert alert-danger" style="border-radius: 8px; font-size: 14px; margin-bottom: 16px;">
                        {{ $errors->first() }}
                    </div>
                @endif

                <!-- Email -->
                <div class="mb-3">
                    <label style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 14px; color: #4a2c3d; display: block; margin-bottom: 6px;">Email</label>
                    <input type="email" name="email" class="input-rosa" placeholder="seu@email.com" value="{{ old('email') }}" required autocomplete="email">
                </div>

                <!-- Senha -->
                <div class="mb-4">
                    <label style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 14px; color: #4a2c3d; display: block; margin-bottom: 6px;">Senha</label>
                    <div style="position: relative;">
                        <input :type="mostrarSenha ? 'text' : 'password'" name="senha" class="input-rosa" placeholder="Digite sua senha" required autocomplete="current-password" style="padding-right: 48px;">
                        <button type="button" @click="mostrarSenha = !mostrarSenha" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #8c6b7d; font-size: 18px; padding: 0; line-height: 1;">
                            <i :class="mostrarSenha ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                        </button>
                    </div>
                </div>

                <!-- Botão -->
                <button type="submit" class="btn-rosa" style="width: 100%; justify-content: center; font-size: 16px; padding: 14px;">
                    Entrar
                </button>
            </form>
        </div>
    </div>

    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/mask@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
