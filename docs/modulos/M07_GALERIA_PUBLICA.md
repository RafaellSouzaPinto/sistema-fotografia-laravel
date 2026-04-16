# M07 — Galeria Pública

## Propósito

O cliente acessa a galeria via link único (`/galeria/{token}`) sem precisar de login. Vê as fotos em grid responsivo, abre em lightbox, e pode baixar individualmente. Não há autenticação — o token é a chave de acesso.

## Arquivos

| Arquivo | Papel |
|---------|-------|
| `app/Http/Controllers/GalleryController.php` | Lógica da galeria pública |
| `resources/views/gallery/show.blade.php` | View da galeria |
| `resources/views/gallery/expirado.blade.php` | View de link expirado |

## GalleryController

**Arquivo**: `app/Http/Controllers/GalleryController.php`

```php
class GalleryController extends Controller
{
    public function show(string $token): View
    {
        $vinculo = TrabalhoCliente::with(['trabalho.fotos', 'cliente'])
            ->where('token', $token)
            ->whereNull('deleted_at')
            ->firstOrFail(); // 404 se token inválido

        // Verifica expiração
        if ($vinculo->estaExpirado()) {
            $vinculo->marcarComoExpirado();
            return view('gallery.expirado');
        }

        // Verifica se trabalho está publicado
        if ($vinculo->trabalho->status !== 'publicado') {
            abort(404); // Não revelar que o trabalho existe
        }

        $trabalho = $vinculo->trabalho;
        $cliente  = $vinculo->cliente;
        $fotos    = $trabalho->fotos; // ordenadas por 'ordem'

        return view('gallery.show', compact('trabalho', 'cliente', 'fotos', 'vinculo'));
    }

    public function downloadFoto(string $token, Foto $foto): Response
    {
        $vinculo = TrabalhoCliente::where('token', $token)->firstOrFail();

        if ($vinculo->estaExpirado()) {
            abort(403, 'Link expirado.');
        }

        // Valida que a foto pertence ao trabalho do token
        if ($foto->trabalho_id !== $vinculo->trabalho_id) {
            abort(403);
        }

        // Download do Drive
        if ($foto->drive_arquivo_id) {
            $stream = app(GoogleDriveService::class)->download($foto->drive_arquivo_id);
            return response()->streamDownload(function () use ($stream) {
                echo $stream->getContents();
            }, $foto->nome_arquivo, ['Content-Type' => 'image/jpeg']);
        }

        // Fallback: storage local
        return response()->download(
            Storage::disk('public')->path("fotos/{$foto->trabalho_id}/{$foto->nome_arquivo}"),
            $foto->nome_arquivo
        );
    }

    public function downloadTodas(string $token): StreamedResponse
    {
        $vinculo = TrabalhoCliente::with('trabalho.fotos')->where('token', $token)->firstOrFail();

        if ($vinculo->estaExpirado()) {
            abort(403, 'Link expirado.');
        }

        $trabalho = $vinculo->trabalho;
        $fotos    = $trabalho->fotos;
        $nomeZip  = Str::slug($trabalho->titulo) . '.zip';

        return response()->streamDownload(function () use ($fotos) {
            $zip = new ZipArchive();
            $tmpZip = tempnam(sys_get_temp_dir(), 'galeria_');
            $zip->open($tmpZip, ZipArchive::CREATE);

            foreach ($fotos as $foto) {
                if ($foto->drive_arquivo_id) {
                    $stream = app(GoogleDriveService::class)->download($foto->drive_arquivo_id);
                    $zip->addFromString($foto->nome_arquivo, $stream->getContents());
                } else {
                    $caminho = Storage::disk('public')->path(
                        "fotos/{$foto->trabalho_id}/{$foto->nome_arquivo}"
                    );
                    if (file_exists($caminho)) {
                        $zip->addFile($caminho, $foto->nome_arquivo);
                    }
                }
            }

            $zip->close();
            readfile($tmpZip);
            unlink($tmpZip);
        }, $nomeZip, ['Content-Type' => 'application/zip']);
    }
}
```

## View gallery/show.blade.php

Layout da galeria pública (sem header admin):

```
┌─────────────────────────────────────────────────────────┐
│  🌸  Silvia Souza Fotografa                             │
│                                                         │
│  Olá, Ana Lima!                                         │
│  Suas fotos estão prontas 💕                            │
│                                                         │
│  Casamento Maria e João                                 │
│  20 de março de 2026 · 45 fotos · Trabalho Completo    │
│                                                         │
│  [Baixar todas as fotos]                                │
│                                                         │
├─────────────────────────────────────────────────────────┤
│  [foto] [foto] [foto] [foto] [foto] [foto]              │
│  [foto] [foto] [foto] [foto] [foto] [foto]              │
│  [foto] [foto] [foto] [foto] [foto] [foto]              │
│                        [⬇ Download] por foto            │
└─────────────────────────────────────────────────────────┘
```

## Lightbox (Alpine.js)

```html
<!-- Abre ao clicar na foto -->
<div x-data="{ aberto: false, indice: 0, fotos: [...] }"
     @keydown.arrow-left.window="indice > 0 && indice--"
     @keydown.arrow-right.window="indice < fotos.length-1 && indice++"
     @keydown.escape.window="aberto = false">

    <!-- Grid de fotos -->
    <template x-for="(foto, i) in fotos">
        <img :src="foto.thumbnail" @click="aberto = true; indice = i" class="galeria-foto">
    </template>

    <!-- Lightbox overlay -->
    <div x-show="aberto" class="lightbox-overlay" @click.self="aberto = false">
        <button @click="indice > 0 && indice--">‹</button>
        <img :src="fotos[indice].url_original">
        <button @click="indice < fotos.length-1 && indice++">›</button>
        <button @click="aberto = false">✕</button>
    </div>
</div>
```

## View gallery/expirado.blade.php

Tela simples informando que o link expirou:
- Sem header/footer de admin
- Mensagem amigável: "Este link não está mais disponível"
- Instrução para contato com a fotógrafa
- Sem exposição de detalhes técnicos

## Regras de acesso

| Condição | Resultado |
|----------|-----------|
| Token inexistente | 404 |
| Token deletado (soft delete) | 404 |
| Link expirado por data | View `expirado` |
| Link com `status_link = expirado` | View `expirado` |
| Trabalho com `status = rascunho` | 404 |
| Tudo válido | Galeria normal |

## Grid responsivo

| Dispositivo | Colunas |
|-------------|---------|
| Mobile (<576px) | 2 colunas |
| Tablet (576-992px) | 3 colunas |
| Desktop (>992px) | 4-6 colunas |

## Botão "Baixar todas"

- Aparece no topo da galeria
- Trigger: GET `/galeria/{token}/download`
- Gera ZIP server-side com streaming
- Ver [M08_DOWNLOAD_ZIP.md](M08_DOWNLOAD_ZIP.md) para detalhes

## Performance

- Thumbnails exibidos no grid (600x600px, leve)
- Imagem original carregada apenas no lightbox
- Lazy loading nas imagens do grid: `loading="lazy"`
