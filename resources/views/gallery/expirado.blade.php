<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Expirado — {{ $nomeFotografa }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <style>
        body { background: #fdf0f2; font-family: 'Inter', sans-serif; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px; }
        .expirado-card { background: #fff; border-radius: 16px; padding: 48px 32px; text-align: center; max-width: 480px; width: 100%; box-shadow: 0 4px 24px rgba(0,0,0,0.06); }
        .expirado-icon { font-size: 64px; color: #c27a8e; opacity: 0.6; }
        .expirado-titulo { font-family: 'Playfair Display', serif; font-weight: 700; font-size: 28px; color: #4a2c3d; margin-top: 24px; }
        .expirado-texto { color: #8c6b7d; font-size: 16px; margin-top: 12px; line-height: 1.6; }
        .expirado-trabalho { font-family: 'Playfair Display', serif; font-weight: 600; font-size: 18px; color: #c27a8e; margin-top: 8px; }
        .btn-whatsapp-expirado { display: inline-flex; align-items: center; gap: 8px; background: #25D366; color: #fff; border: none; border-radius: 50px; padding: 14px 32px; font-size: 16px; font-weight: 600; text-decoration: none; margin-top: 24px; transition: background 0.2s; }
        .btn-whatsapp-expirado:hover { background: #1DA851; color: #fff; }
        .footer-expirado { margin-top: 32px; color: #8c6b7d; font-size: 13px; }
    </style>
</head>
<body>
    <div class="expirado-card">
        <img src="{{ asset('img/img-silvia-logo.png') }}" alt="Silvia Souza Fotógrafa" style="max-width: 180px; height: auto; margin: 0 auto 16px; display: block;">
        <div class="expirado-icon"><i class="bi bi-clock-history"></i></div>
        <h1 class="expirado-titulo">Link expirado</h1>
        <p class="expirado-texto">
            O prazo para visualização das fotos deste trabalho já acabou.
        </p>
        <div class="expirado-trabalho">{{ $nomeTrabalho }}</div>
        <p class="expirado-texto">
            Se precisar de acesso novamente, entre em contato:
        </p>
        <a href="{{ $whatsappLink }}?text={{ urlencode("Olá! O link das fotos do trabalho '{$nomeTrabalho}' expirou. Poderia liberar novamente?") }}"
           class="btn-whatsapp-expirado">
            <i class="bi bi-whatsapp"></i> Falar com {{ $nomeFotografa }}
        </a>
        <div class="footer-expirado">
            {{ $nomeFotografa }} · {{ $telefone }}
        </div>
    </div>
</body>
</html>
