<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') — Silvia Souza Fotografa</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="/css/custom.css" rel="stylesheet">
    @livewireStyles
</head>
<body style="background-color: #fdf0f2; min-height: 100vh;">

    <!-- Header -->
    <header class="admin-header">
        <a href="{{ route('admin.dashboard') }}" class="d-flex align-items-center text-decoration-none">
            <img src="{{ asset('img/img-silvia-logo.png') }}" alt="Silvia Souza Fotógrafa" style="height: 50px; width: auto;">
        </a>
        <nav class="nav-links">
            <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                Meus Trabalhos
            </a>
            <a href="{{ route('admin.clients') }}" class="nav-link {{ request()->routeIs('admin.clients') ? 'active' : '' }}">
                Meus Clientes
            </a>
            <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                @csrf
                <button type="submit" class="nav-link-logout" style="background: none; border: none; cursor: pointer; font-family: 'Inter', sans-serif; font-size: 14px;">
                    <i class="bi bi-box-arrow-right"></i> Sair
                </button>
            </form>
        </nav>
    </header>

    <!-- Toast de Notificações -->
    <div x-data="{ mensagens: [] }"
         @notify.window="mensagens.push({texto: $event.detail.message, tipo: $event.detail.type || 'success'}); setTimeout(() => mensagens.shift(), 3300)"
         style="position: fixed; top: 20px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 8px;">
        <template x-for="(msg, i) in mensagens" :key="i">
            <div class="toast-notification" :class="msg.tipo === 'error' ? 'error' : ''" x-text="msg.texto"></div>
        </template>
    </div>

    <!-- Conteúdo -->
    <main style="max-width: 1200px; margin: 0 auto; padding: 24px 16px;">
        {{ $slot }}
    </main>

    @livewireScripts
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/mask@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
