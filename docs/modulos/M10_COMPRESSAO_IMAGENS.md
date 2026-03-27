# M10 — Compressão de Imagens

## Propósito

Comprimir fotos automaticamente antes do upload. Para prévias, reduz resolução e qualidade para economizar espaço. Para completos, mantém original mas gera thumbnail para exibição na galeria.

## Arquivos

| Arquivo | Papel |
|---------|-------|
| `app/Services/ImageCompressorService.php` | Toda lógica de compressão |
| `app/Livewire/Admin/PhotoUploader.php` | Usa o serviço durante upload |

## ImageCompressorService

**Arquivo**: `app/Services/ImageCompressorService.php`

```php
class ImageCompressorService
{
    // Comprime imagem: reduz resolução e qualidade
    // Retorna caminho do arquivo comprimido
    public function comprimir(
        string $caminhoOriginal,
        string $caminhoDestino,
        int $qualidade = 70,
        int $larguraMaxima = 1920
    ): string {
        $imagem = Image::read($caminhoOriginal); // Intervention Image v3

        // Redimensiona mantendo proporção se maior que largura máxima
        if ($imagem->width() > $larguraMaxima) {
            $imagem->scaleDown(width: $larguraMaxima);
        }

        // Salva como JPEG com qualidade definida
        $imagem->toJpeg($qualidade)->save($caminhoDestino);

        return $caminhoDestino;
    }

    // Gera thumbnail quadrado (cover) para exibição na galeria
    public function gerarThumbnail(
        string $caminhoOriginal,
        string $caminhoDestino,
        int $tamanho = 600,
        int $qualidade = 60
    ): string {
        $imagem = Image::read($caminhoOriginal);

        // Cover: recorta centro para quadrado
        $imagem->cover($tamanho, $tamanho);

        $imagem->toJpeg($qualidade)->save($caminhoDestino);

        return $caminhoDestino;
    }

    // Verifica se o formato suporta compressão via GD/Intervention
    public function suportaCompressao(string $extensao): bool
    {
        return in_array(strtolower($extensao), ['jpg', 'jpeg', 'png', 'webp']);
    }
}
```

## Dependência

```
intervention/image: ^3.0
```

Driver: GD (padrão PHP — sem ImageMagick necessário)

```bash
composer require intervention/image
```

## Parâmetros de compressão

| Parâmetro | Valor padrão | Descrição |
|-----------|-------------|-----------|
| `$qualidade` (prévia) | 70 | 0-100. 70 = boa qualidade visual com tamanho reduzido |
| `$larguraMaxima` (prévia) | 1920 | Reduz imagens maiores que Full HD |
| `$tamanho` (thumbnail) | 600 | 600x600px, quadrado (cover) |
| `$qualidade` (thumbnail) | 60 | Thumbnails podem ser mais comprimidos |

## Formatos suportados

| Formato | Compressão | Thumbnail |
|---------|-----------|-----------|
| JPG/JPEG | ✅ | ✅ |
| PNG | ✅ (converte para JPEG) | ✅ |
| WebP | ✅ | ✅ |
| PSD | ❌ (envia original) | ⚠️ Depende do GD |
| TIF/TIFF | ❌ (envia original) | ⚠️ Depende do GD |

Para PSD/TIF: se a geração de thumbnail falhar, usar placeholder.

## Comportamento por tipo de trabalho

### Tipo: Prévia
```
Arquivo original → comprimir() → arquivo 1920px 70% → upload Drive
                → gerarThumbnail() → 600x600px → storage local
```

### Tipo: Completo
```
Arquivo original → upload Drive (sem compressão)
                → gerarThumbnail() → 600x600px → storage local
```

## Caminhos dos arquivos temporários

```php
// Arquivo comprimido temporário (apagado após upload Drive)
$caminhoEnvio = storage_path("app/private/prev_" . uniqid() . ".jpg");

// Thumbnail salvo permanentemente
$caminhoThumb = storage_path("app/public/thumbnails/thumb_" . uniqid() . ".jpg");
$relativo = "thumbnails/thumb_" . uniqid() . ".jpg"; // salvo em fotos.caminho_thumbnail
```

## Limpeza de temporários

Após upload no Drive com sucesso, os arquivos comprimidos temporários devem ser removidos:

```php
if (file_exists($caminhoEnvio) && $caminhoEnvio !== $caminhoTemp) {
    unlink($caminhoEnvio);
}
```

## Erros comuns

| Erro | Causa | Solução |
|------|-------|---------|
| `Memory exhausted` | Foto muito grande (>50MB) para GD | Aumentar `memory_limit` no php.ini |
| `Unable to read image` | Formato não suportado pelo GD | Usar `suportaCompressao()` antes |
| Thumbnail em branco | PSD sem suporte no GD | Usar imagem placeholder |
| `JPEG encoder error` | Caminho de destino não existe | Criar diretório antes com `mkdir` |

## Diretórios necessários

```bash
# Criar antes do primeiro uso
mkdir -p storage/app/public/thumbnails
mkdir -p storage/app/private

# Garantir link simbólico
php artisan storage:link
```
