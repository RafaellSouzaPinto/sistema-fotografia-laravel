# FEATURE — Compressão automática de imagens na prévia

## Lib escolhida

**intervention/image v3** — padrão do ecossistema Laravel, usa GD (já vem com PHP), zero dependência externa.

```bash
composer require intervention/image "^3.0"
```

Não precisa instalar jpegoptim, pngquant ou qualquer binário. GD já está instalado com PHP.

---

## Regra de negócio

| Tipo de trabalho | Upload | O que o cliente vê | Download |
|-----------------|--------|-------------------|----------|
| **Prévia** | Original sobe pro storage | Sistema comprime automaticamente (JPG 70%, max 1920px largura) | Cliente baixa a versão comprimida |
| **Completo** | Original sobe pro storage | Sistema gera thumbnail comprimido pra galeria | Cliente baixa o ORIGINAL em alta |

Ou seja:
- **Prévia**: comprime e SUBSTITUI — o cliente nunca vê o original
- **Completo**: guarda original + gera thumbnail separado pra exibição na galeria

---

## Alteração no banco

Nova migration `add_caminho_thumbnail_to_fotos_table`:

```php
Schema::table('fotos', function (Blueprint $table) {
    $table->string('caminho_thumbnail')->nullable()->after('drive_thumbnail');
});
```

| Coluna | Uso |
|--------|-----|
| `drive_arquivo_id` | Caminho do arquivo ORIGINAL (alta resolução) |
| `caminho_thumbnail` | Caminho do thumbnail comprimido (pra exibição na galeria) |

Para **prévia**: `drive_arquivo_id` e `caminho_thumbnail` apontam pro MESMO arquivo (o comprimido).
Para **completo**: `drive_arquivo_id` = original, `caminho_thumbnail` = versão comprimida.

---

## Service de compressão

Criar `app/Services/ImageCompressorService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageCompressorService
{
    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Comprime imagem para exibição web.
     * Reduz qualidade para 70% e limita largura a 1920px.
     * Retorna o caminho do arquivo comprimido.
     */
    public function comprimir(string $caminhoOriginal, string $caminhoDestino, int $qualidade = 70, int $larguraMaxima = 1920): string
    {
        $imagem = $this->manager->read($caminhoOriginal);

        // Redimensionar se maior que largura máxima (mantém proporção)
        $larguraAtual = $imagem->width();
        if ($larguraAtual > $larguraMaxima) {
            $imagem->scaleDown(width: $larguraMaxima);
        }

        // Salvar como JPG com qualidade reduzida
        $imagem->toJpeg($qualidade)->save($caminhoDestino);

        return $caminhoDestino;
    }

    /**
     * Gera thumbnail pequeno para grid da galeria.
     * 600x600px, qualidade 60%.
     */
    public function gerarThumbnail(string $caminhoOriginal, string $caminhoDestino, int $tamanho = 600, int $qualidade = 60): string
    {
        $imagem = $this->manager->read($caminhoOriginal);

        // Cover: redimensiona e corta pra ficar quadrado
        $imagem->cover($tamanho, $tamanho);

        $imagem->toJpeg($qualidade)->save($caminhoDestino);

        return $caminhoDestino;
    }

    /**
     * Verifica se o arquivo é uma imagem suportada para compressão.
     * PSD e TIF não são comprimidos — ficam como estão.
     */
    public function suportaCompressao(string $extensao): bool
    {
        return in_array(strtolower($extensao), ['jpg', 'jpeg', 'png', 'webp']);
    }
}
```

---

## Alteração no PhotoUploader (Livewire)

No método de upload, após salvar o arquivo original:

```php
use App\Services\ImageCompressorService;

public function updatedArquivos()
{
    $this->validate([
        'arquivos.*' => 'file|mimes:jpg,jpeg,png,psd,tif,tiff|max:204800',
    ]);

    $compressor = app(ImageCompressorService::class);
    $trabalho = \App\Models\Trabalho::findOrFail($this->trabalhoId);

    foreach ($this->arquivos as $arquivo) {
        $nomeOriginal = $arquivo->getClientOriginalName();
        $extensao = strtolower($arquivo->getClientOriginalExtension());
        $tamanho = $arquivo->getSize();

        // Salvar original
        $pathOriginal = $arquivo->store("fotos/{$this->trabalhoId}/originais", 'public');
        $caminhoAbsolutoOriginal = storage_path("app/public/{$pathOriginal}");

        $pathComprimido = null;
        $pathThumbnail = null;

        if ($compressor->suportaCompressao($extensao)) {

            if ($trabalho->tipo === 'previa') {
                // PRÉVIA: comprimir e usar como arquivo principal
                $nomeComprimido = pathinfo($nomeOriginal, PATHINFO_FILENAME) . '_compressed.jpg';
                $pathComprimidoRelativo = "fotos/{$this->trabalhoId}/{$nomeComprimido}";
                $caminhoAbsolutoComprimido = storage_path("app/public/{$pathComprimidoRelativo}");

                // Criar diretório se não existir
                $dir = dirname($caminhoAbsolutoComprimido);
                if (!is_dir($dir)) mkdir($dir, 0755, true);

                $compressor->comprimir($caminhoAbsolutoOriginal, $caminhoAbsolutoComprimido, 70, 1920);

                // Na prévia, o arquivo principal É o comprimido
                $pathFinal = $pathComprimidoRelativo;
                $pathThumbnail = $pathComprimidoRelativo;

                // Deletar original (não precisa mais)
                \Storage::disk('public')->delete($pathOriginal);
                $tamanho = filesize($caminhoAbsolutoComprimido);

            } else {
                // COMPLETO: manter original, gerar thumbnail separado
                $nomeThumbnail = pathinfo($nomeOriginal, PATHINFO_FILENAME) . '_thumb.jpg';
                $pathThumbnailRelativo = "fotos/{$this->trabalhoId}/thumbnails/{$nomeThumbnail}";
                $caminhoAbsolutoThumbnail = storage_path("app/public/{$pathThumbnailRelativo}");

                $dir = dirname($caminhoAbsolutoThumbnail);
                if (!is_dir($dir)) mkdir($dir, 0755, true);

                $compressor->gerarThumbnail($caminhoAbsolutoOriginal, $caminhoAbsolutoThumbnail, 600, 60);

                $pathFinal = $pathOriginal;
                $pathThumbnail = $pathThumbnailRelativo;
            }
        } else {
            // PSD, TIF: não comprime, sem thumbnail
            $pathFinal = $pathOriginal;
            $pathThumbnail = null;
        }

        \App\Models\Foto::create([
            'trabalho_id' => $this->trabalhoId,
            'nome_arquivo' => $nomeOriginal,
            'drive_arquivo_id' => $pathFinal,
            'caminho_thumbnail' => $pathThumbnail,
            'drive_thumbnail' => $pathThumbnail ? asset("storage/{$pathThumbnail}") : null,
            'tamanho_bytes' => $tamanho,
            'ordem' => \App\Models\Foto::where('trabalho_id', $this->trabalhoId)->count(),
        ]);
    }

    $this->arquivos = [];
    $this->dispatch('notify', message: 'Fotos enviadas!');
}
```

---

## Alteração nas views (galeria e admin)

### Galeria pública (`gallery/show.blade.php`)

Usar thumbnail pra exibição, original pra download:

```html
{{-- Grid: usa thumbnail se existir --}}
<img loading="lazy"
     src="{{ asset('storage/' . ($foto->caminho_thumbnail ?? $foto->drive_arquivo_id)) }}"
     alt="{{ $foto->nome_arquivo }}">

{{-- Download: SEMPRE usa o original --}}
<a href="{{ route('galeria.foto', [$token, $foto->id]) }}">
    <i class="bi bi-download"></i>
</a>
```

### Admin — grid de thumbnails no PhotoUploader

```html
<img src="{{ asset('storage/' . ($foto->caminho_thumbnail ?? $foto->drive_arquivo_id)) }}"
     alt="{{ $foto->nome_arquivo }}">
```

---

## Alteração no GalleryController (download)

O download SEMPRE entrega o original (não o thumbnail):

```php
public function downloadFoto(string $token, Foto $foto)
{
    $pivot = TrabalhoCliente::where('token', $token)->firstOrFail();

    // Verificar expiração
    if ($pivot->expira_em && $pivot->expira_em->isPast()) {
        $pivot->marcarComoExpirado();
        abort(403, 'Link expirado.');
    }

    abort_if($foto->trabalho_id !== $pivot->trabalho_id, 403);

    // Usar o arquivo original (drive_arquivo_id), não o thumbnail
    $path = storage_path('app/public/' . $foto->drive_arquivo_id);
    if (!file_exists($path)) abort(404);

    return response()->download($path, $foto->nome_arquivo);
}
```

---

## Estrutura de pastas no storage

```
storage/app/public/fotos/
└── {trabalho_id}/
    ├── originais/           ← arquivos originais (só pra trabalho completo)
    │   ├── foto_001.jpg
    │   └── foto_002.jpg
    ├── thumbnails/          ← thumbnails 600x600 (só pra trabalho completo)
    │   ├── foto_001_thumb.jpg
    │   └── foto_002_thumb.jpg
    ├── foto_001_compressed.jpg  ← comprimido (só pra prévia)
    └── foto_002_compressed.jpg
```

---

## Comparativo de tamanho esperado

| Original | Após compressão (prévia) | Thumbnail (completo) |
|----------|-------------------------|---------------------|
| 7 MB (JPG 5000x3000) | ~400 KB (JPG 1920px 70%) | ~80 KB (JPG 600x600 60%) |
| 15 MB (PNG 6000x4000) | ~600 KB (JPG 1920px 70%) | ~90 KB (JPG 600x600 60%) |
| 3 MB (JPG 3000x2000) | ~250 KB (JPG 1920px 70%) | ~60 KB (JPG 600x600 60%) |

---

## Model Foto — atualizar $fillable

```php
protected $fillable = [
    'trabalho_id', 'nome_arquivo', 'drive_arquivo_id',
    'drive_thumbnail', 'caminho_thumbnail', 'tamanho_bytes', 'ordem'
];
```

---

## Deletar fotos — limpar thumbnails também

No `PhotoUploader::removerFoto()`:

```php
public function removerFoto($fotoId)
{
    $foto = Foto::findOrFail($fotoId);

    // Deletar arquivo principal
    \Storage::disk('public')->delete($foto->drive_arquivo_id);

    // Deletar thumbnail se existir e for diferente do principal
    if ($foto->caminho_thumbnail && $foto->caminho_thumbnail !== $foto->drive_arquivo_id) {
        \Storage::disk('public')->delete($foto->caminho_thumbnail);
    }

    $foto->delete();
    $this->dispatch('notify', message: 'Foto removida.');
}
```

---

## Arquivos a criar/alterar

| Arquivo | Ação |
|---------|------|
| `composer.json` | Adicionar `intervention/image ^3.0` |
| `app/Services/ImageCompressorService.php` | Criar |
| `database/migrations/xxxx_add_caminho_thumbnail_to_fotos_table.php` | Criar |
| `app/Models/Foto.php` | Alterar $fillable |
| `app/Livewire/Admin/PhotoUploader.php` | Alterar lógica de upload |
| `resources/views/gallery/show.blade.php` | Usar thumbnail na exibição |
| `resources/views/livewire/admin/photo-uploader.blade.php` | Usar thumbnail na grid |
| `app/Http/Controllers/GalleryController.php` | Download usa original |

---

## Rodar

```bash
composer require intervention/image "^3.0"
php artisan migrate
```
