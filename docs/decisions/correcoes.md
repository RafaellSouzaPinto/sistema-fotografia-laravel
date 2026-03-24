# CORREÇÕES E FEATURES FALTANTES

## BUG 1 — Falta botão "Publicar trabalho" na tela de edição

Na tela `/admin/jobs/{id}/edit`, após adicionar pelo menos 1 cliente e 1 foto, deve aparecer um botão verde grande:

**Botão**: "✓ Publicar trabalho e liberar links"
- Cor: `background: #27ae60`, `color: #ffffff`
- Largura: 100% do card
- Padding: 16px
- Border-radius: 8px
- Font: Inter 600 16px
- Posição: seção separada, abaixo da seção de fotos
- Texto de apoio abaixo: "Após publicar, os clientes poderão acessar as fotos pelos links gerados"

**Lógica Livewire:**
```php
public function publicar()
{
    $trabalho = Trabalho::findOrFail($this->trabalhoId);
    
    if ($trabalho->fotos()->count() === 0) {
        $this->dispatch('notify', message: 'Adicione pelo menos 1 foto.', type: 'error');
        return;
    }
    
    if ($trabalho->clientes()->count() === 0) {
        $this->dispatch('notify', message: 'Adicione pelo menos 1 cliente.', type: 'error');
        return;
    }
    
    $trabalho->update(['status' => 'publicado']);
    $this->dispatch('notify', message: 'Trabalho publicado! Links liberados.');
}
```

**Condição de exibição no Blade:**
```php
@if($this->trabalhoId)
    @php
        $trabalho = \App\Models\Trabalho::withCount(['fotos', 'clientes'])->find($this->trabalhoId);
    @endphp
    
    @if($trabalho && $trabalho->status === 'rascunho')
        <div class="card mt-4">
            <div class="card-body text-center">
                @if($trabalho->fotos_count > 0 && $trabalho->clientes_count > 0)
                    <button wire:click="publicar" class="btn-publicar">
                        ✓ Publicar trabalho e liberar links
                    </button>
                    <p class="text-secondary mt-2">Após publicar, os clientes poderão acessar as fotos pelos links gerados</p>
                @else
                    <p class="text-secondary">
                        Para publicar, adicione pelo menos 
                        @if($trabalho->fotos_count === 0) 1 foto @endif
                        @if($trabalho->fotos_count === 0 && $trabalho->clientes_count === 0) e @endif
                        @if($trabalho->clientes_count === 0) 1 cliente @endif
                    </p>
                @endif
            </div>
        </div>
    @elseif($trabalho && $trabalho->status === 'publicado')
        <div class="card mt-4">
            <div class="card-body text-center">
                <span class="text-success fw-bold"><i class="bi bi-check-circle"></i> Trabalho publicado — links ativos</span>
            </div>
        </div>
    @endif
@endif
```

CSS do botão:
```css
.btn-publicar {
    background: #27ae60;
    color: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 16px;
    width: 100%;
    font-family: 'Inter', sans-serif;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.2s;
}
.btn-publicar:hover {
    background: #219a52;
}
```

---

## BUG 2 — Links dos clientes não aparecem / Galeria pública não existe

Cada cliente vinculado a um trabalho tem um token único na tabela `trabalho_cliente`. Esse token gera uma URL pública:

```
http://127.0.0.1:9000/galeria/{token}
```

### 2.1 — Exibir links na tela de edição do trabalho

No componente `ClientManager`, a lista de clientes vinculados DEVE mostrar o link de cada um:

```html
@foreach($clientesVinculados as $cliente)
<div class="cliente-row">
    <div>
        <strong>{{ $cliente->nome }}</strong><br>
        <small>{{ $cliente->telefone }}</small><br>
        <small class="text-muted">{{ url('/galeria/' . $cliente->pivot->token) }}</small>
    </div>
    <div>
        <button 
            x-data 
            @click="
                navigator.clipboard.writeText('{{ url('/galeria/' . $cliente->pivot->token) }}');
                $el.innerHTML = '<i class=\'bi bi-check\'></i> Copiado!';
                setTimeout(() => $el.innerHTML = '<i class=\'bi bi-clipboard\'></i> Copiar link', 2000)
            "
            class="btn-secundario">
            <i class="bi bi-clipboard"></i> Copiar link
        </button>
        <button wire:click="remover({{ $cliente->id }})" wire:confirm="Remover este cliente do trabalho?" class="btn-perigo">
            <i class="bi bi-trash"></i> Remover
        </button>
    </div>
</div>
@endforeach
```

### 2.2 — Criar rota e controller da galeria pública

**Rotas** em `routes/web.php` (SEM middleware auth — é pública):
```php
Route::get('/galeria/{token}', [GalleryController::class, 'show'])->name('galeria.show');
Route::get('/galeria/{token}/download', [GalleryController::class, 'downloadTodas'])->name('galeria.download');
Route::get('/galeria/{token}/foto/{foto}', [GalleryController::class, 'downloadFoto'])->name('galeria.foto');
```

**Controller** `app/Http/Controllers/GalleryController.php`:
```php
<?php

namespace App\Http\Controllers;

use App\Models\TrabalhoCliente;
use App\Models\Foto;
use Illuminate\Support\Str;

class GalleryController extends Controller
{
    public function show(string $token)
    {
        $pivot = TrabalhoCliente::where('token', $token)->firstOrFail();
        $trabalho = $pivot->trabalho;
        $cliente = $pivot->cliente;
        $fotos = $trabalho->fotos()->orderBy('ordem')->get();

        return view('gallery.show', compact('trabalho', 'cliente', 'fotos', 'token'));
    }

    public function downloadFoto(string $token, Foto $foto)
    {
        $pivot = TrabalhoCliente::where('token', $token)->firstOrFail();
        abort_if($foto->trabalho_id !== $pivot->trabalho_id, 403);

        // Se usando storage local:
        $path = storage_path('app/public/' . $foto->drive_arquivo_id);
        if (!file_exists($path)) abort(404);
        
        return response()->download($path, $foto->nome_arquivo);
    }

    public function downloadTodas(string $token)
    {
        $pivot = TrabalhoCliente::where('token', $token)->firstOrFail();
        $trabalho = $pivot->trabalho;
        
        // Só permite download ZIP se tipo = completo
        abort_if($trabalho->tipo !== 'completo', 403);

        $fotos = $trabalho->fotos;
        $zipName = Str::slug($trabalho->titulo) . '.zip';
        $zipPath = storage_path('app/temp/' . $zipName);

        // Criar diretório temp se não existir
        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($fotos as $foto) {
            $filePath = storage_path('app/public/' . $foto->drive_arquivo_id);
            if (file_exists($filePath)) {
                $zip->addFile($filePath, $foto->nome_arquivo);
            }
        }

        $zip->close();

        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }
}
```

### 2.3 — View da galeria pública

Criar `resources/views/gallery/show.blade.php`:

```html
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $trabalho->titulo }} — Silvia Souza Fotografa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">
    <style>
        body { background: #fdf0f2; }
        .gallery-header { background: #fff; padding: 20px; text-align: center; border-bottom: 1px solid #f0d4da; }
        .gallery-header .logo { font-family: 'Playfair Display', serif; font-weight: 700; font-size: 22px; color: #c27a8e; }
        .gallery-header .subtitle { font-family: 'Playfair Display', serif; font-style: italic; font-size: 14px; color: #8c6b7d; }
        .greeting { text-align: center; padding: 40px 20px; }
        .greeting h1 { font-family: 'Playfair Display', serif; font-weight: 700; font-size: 36px; color: #4a2c3d; }
        .greeting .trabalho-nome { font-family: 'Playfair Display', serif; font-weight: 700; font-size: 28px; color: #4a2c3d; }
        .greeting .trabalho-data { font-size: 16px; color: #8c6b7d; }
        .btn-download-all { background: #c27a8e; color: #fff; border: none; border-radius: 8px; padding: 16px; width: 100%; max-width: 500px; font-family: 'Inter', sans-serif; font-weight: 600; font-size: 16px; display: block; margin: 0 auto; text-decoration: none; text-align: center; }
        .btn-download-all:hover { background: #a85d73; color: #fff; }
        .foto-grid { display: grid; gap: 8px; max-width: 1300px; margin: 24px auto; padding: 0 16px; grid-template-columns: repeat(2, 1fr); }
        @media (min-width: 768px) { .foto-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (min-width: 1025px) { .foto-grid { grid-template-columns: repeat(4, 1fr); } }
        .foto-item { position: relative; border-radius: 8px; overflow: hidden; cursor: pointer; }
        .foto-item img { width: 100%; aspect-ratio: 1/1; object-fit: cover; transition: transform 0.2s; }
        .foto-item:hover img { transform: scale(1.02); }
        .foto-item .btn-download-individual { position: absolute; bottom: 8px; right: 8px; background: rgba(255,255,255,0.85); border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; text-decoration: none; color: #c27a8e; border: none; }
        .lightbox-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(74,44,61,0.92); z-index: 9999; display: flex; align-items: center; justify-content: center; flex-direction: column; }
        .lightbox-overlay img { max-width: 90vw; max-height: 80vh; object-fit: contain; border-radius: 4px; }
        .lightbox-close { position: absolute; top: 16px; right: 16px; background: none; border: none; color: #fff; font-size: 32px; cursor: pointer; }
        .lightbox-nav { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.2); border: none; color: #fff; width: 48px; height: 48px; border-radius: 50%; font-size: 24px; cursor: pointer; }
        .lightbox-nav.prev { left: 16px; }
        .lightbox-nav.next { right: 16px; }
        .lightbox-download { margin-top: 16px; background: #c27a8e; color: #fff; border: none; border-radius: 8px; padding: 10px 24px; text-decoration: none; font-family: 'Inter', sans-serif; font-weight: 500; }
        .lightbox-download:hover { background: #a85d73; color: #fff; }
        .gallery-footer { text-align: center; padding: 24px; border-top: 1px solid #f0d4da; margin-top: 40px; color: #8c6b7d; font-size: 14px; }
        .badge-tipo-previa { background: #fce4ec; color: #c27a8e; border-radius: 50px; padding: 4px 12px; font-size: 12px; font-weight: 500; }
        .badge-tipo-completo { background: #d4f5e9; color: #27ae60; border-radius: 50px; padding: 4px 12px; font-size: 12px; font-weight: 500; }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="gallery-header">
        <div class="logo"><i class="bi bi-camera"></i> Silvia Souza Fotografa</div>
        <div class="subtitle">Fotografia profissional desde 1985</div>
    </div>

    <!-- Saudação -->
    <div class="greeting">
        <h1>Olá, {{ $cliente->nome }}! 👋</h1>
        <p class="text-secondary">Aqui estão as fotos do seu trabalho:</p>
        <div class="trabalho-nome">{{ $trabalho->titulo }}</div>
        <div class="trabalho-data"><i class="bi bi-calendar3"></i> {{ $trabalho->data_trabalho->translatedFormat('d \d\e F \d\e Y') }}</div>
        <div class="mt-2">
            <span class="{{ $trabalho->tipo === 'previa' ? 'badge-tipo-previa' : 'badge-tipo-completo' }}">
                {{ $trabalho->tipo === 'previa' ? 'Prévia' : 'Completo' }}
            </span>
        </div>
    </div>

    <!-- Botão baixar todas (só completo) -->
    @if($trabalho->tipo === 'completo' && $fotos->count() > 0)
    <div class="px-3 mb-4">
        <a href="{{ route('galeria.download', $token) }}" class="btn-download-all">
            <i class="bi bi-download"></i> Baixar todas as fotos
        </a>
        <p class="text-center text-secondary mt-2" style="font-size:14px;">Clique para baixar todas as fotos em um arquivo ZIP</p>
    </div>
    @endif

    <!-- Grid de fotos -->
    <div class="foto-grid" x-data="galeria()" @keydown.escape.window="fecharLightbox()" @keydown.arrow-left.window="anterior()" @keydown.arrow-right.window="proxima()">
        
        @foreach($fotos as $index => $foto)
        <div class="foto-item" @click="abrirLightbox({{ $index }})">
            <img loading="lazy" src="{{ asset('storage/' . $foto->drive_arquivo_id) }}" alt="{{ $foto->nome_arquivo }}">
            <a href="{{ route('galeria.foto', [$token, $foto->id]) }}" class="btn-download-individual" @click.stop title="Baixar esta foto">
                <i class="bi bi-download"></i>
            </a>
        </div>
        @endforeach

        <!-- Lightbox -->
        <template x-if="lightboxAberto">
            <div class="lightbox-overlay" @click.self="fecharLightbox()">
                <button class="lightbox-close" @click="fecharLightbox()">✕</button>
                <button class="lightbox-nav prev" x-show="fotoAtual > 0" @click="anterior()">‹</button>
                <img :src="fotos[fotoAtual].src" :alt="fotos[fotoAtual].nome">
                <button class="lightbox-nav next" x-show="fotoAtual < fotos.length - 1" @click="proxima()">›</button>
                <a :href="fotos[fotoAtual].downloadUrl" class="lightbox-download">
                    <i class="bi bi-download"></i> Baixar esta foto
                </a>
            </div>
        </template>
    </div>

    <!-- Footer -->
    <div class="gallery-footer">
        © {{ date('Y') }} Silvia Souza Fotografa 🤍
    </div>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        function galeria() {
            return {
                lightboxAberto: false,
                fotoAtual: 0,
                fotos: [
                    @foreach($fotos as $foto)
                    {
                        src: '{{ asset("storage/" . $foto->drive_arquivo_id) }}',
                        nome: '{{ $foto->nome_arquivo }}',
                        downloadUrl: '{{ route("galeria.foto", [$token, $foto->id]) }}'
                    },
                    @endforeach
                ],
                abrirLightbox(index) {
                    this.fotoAtual = index;
                    this.lightboxAberto = true;
                    document.body.style.overflow = 'hidden';
                },
                fecharLightbox() {
                    this.lightboxAberto = false;
                    document.body.style.overflow = '';
                },
                anterior() {
                    if (this.fotoAtual > 0) this.fotoAtual--;
                },
                proxima() {
                    if (this.fotoAtual < this.fotos.length - 1) this.fotoAtual++;
                }
            }
        }
    </script>
</body>
</html>
```

### 2.4 — Model TrabalhoCliente (se não existir)

Garantir que existe `app/Models/TrabalhoCliente.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrabalhoCliente extends Model
{
    use SoftDeletes;

    protected $table = 'trabalho_cliente';
    protected $fillable = ['trabalho_id', 'cliente_id', 'token'];

    public function trabalho()
    {
        return $this->belongsTo(Trabalho::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
```

---

## RESUMO DO QUE FALTA

| Item | Status |
|------|--------|
| Botão "Publicar trabalho" na tela de edição | FALTA |
| Mudar status de `rascunho` para `publicado` | FALTA |
| Exibir link de cada cliente na tela de edição | FALTA |
| Botão "Copiar link" funcional | FALTA |
| Rota pública `/galeria/{token}` | FALTA |
| GalleryController com show/downloadFoto/downloadTodas | FALTA |
| View da galeria com saudação, grid, lightbox, download | FALTA |
| Model TrabalhoCliente (verificar se existe) | VERIFICAR |
| Botão "Ver Links" no card do dashboard | FALTA |
