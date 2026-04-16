<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fotos em preparação — {{ $nomeFotografa }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <style>
        body { background: #fdf0f2; font-family: 'Inter', sans-serif; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px; }
        .card { background: #fff; border-radius: 16px; padding: 48px 32px; text-align: center; max-width: 480px; width: 100%; box-shadow: 0 4px 24px rgba(0,0,0,0.06); border: none; }
        .icon { font-size: 64px; color: #c27a8e; opacity: 0.6; }
        .titulo { font-family: 'Playfair Display', serif; font-weight: 700; font-size: 28px; color: #4a2c3d; margin-top: 24px; }
        .texto { color: #8c6b7d; font-size: 16px; margin-top: 12px; line-height: 1.6; }
        .nome-trabalho { font-family: 'Playfair Display', serif; font-weight: 600; font-size: 18px; color: #c27a8e; margin-top: 8px; }
        .btn-whatsapp { display: inline-flex; align-items: center; gap: 8px; background: #25D366; color: #fff; border: none; border-radius: 50px; padding: 14px 32px; font-size: 16px; font-weight: 600; text-decoration: none; margin-top: 24px; transition: background 0.2s; }
        .btn-whatsapp:hover { background: #1DA851; color: #fff; }
        .footer { margin-top: 32px; color: #8c6b7d; font-size: 13px; }
    </style>
</head>
<body>
    <div class="card">
        <img src="{{ asset('img/img-silvia-logo.png') }}" alt="Silvia Souza Fotógrafa" style="max-width: 180px; height: auto; margin: 0 auto 16px; display: block;">
        <div class="icon"><i class="bi bi-camera"></i></div>
        <h1 class="titulo">Fotos em preparação</h1>
        <p class="texto">
            As fotos ainda estão sendo organizadas pela fotógrafa.
        </p>
        <div class="nome-trabalho">{{ $nomeTrabalho }}</div>
        <p class="texto">
            Em breve você receberá o link definitivo para visualizar as fotos. Aguarde!
        </p>
        <a href="{{ $whatsappLink }}?text={{ urlencode("Olá! Gostaria de saber quando as fotos do trabalho '{$nomeTrabalho}' estarão disponíveis.") }}"
           class="btn-whatsapp">
            <i class="bi bi-whatsapp"></i> Falar com {{ $nomeFotografa }}
        </a>
        <div class="footer">
            {{ $nomeFotografa }} · {{ $telefone }}
        </div>
    </div>
</body>
</html>
