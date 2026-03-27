# M08 — Download ZIP

## Propósito

Permite que o cliente baixe todas as fotos do trabalho de uma vez em um arquivo ZIP. O ZIP é gerado dinamicamente no servidor com streaming — não salva arquivo no disco.

## Arquivos

| Arquivo | Papel |
|---------|-------|
| `app/Http/Controllers/GalleryController.php` | Método `downloadTodas()` |
| `resources/views/gallery/show.blade.php` | Botão "Baixar todas" |

## Rota

```
GET /galeria/{token}/download → GalleryController@downloadTodas
```

## Implementação

```php
public function downloadTodas(string $token): StreamedResponse
{
    $vinculo = TrabalhoCliente::with('trabalho.fotos')
        ->where('token', $token)
        ->firstOrFail();

    if ($vinculo->estaExpirado()) {
        abort(403, 'Link expirado.');
    }

    $trabalho = $vinculo->trabalho;
    $fotos    = $trabalho->fotos;
    $nomeZip  = Str::slug($trabalho->titulo) . '_fotos.zip';

    return response()->streamDownload(function () use ($fotos) {
        $tmpZip = tempnam(sys_get_temp_dir(), 'silvia_fotos_');
        $zip = new ZipArchive();
        $zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($fotos as $foto) {
            if ($foto->drive_arquivo_id) {
                // Download do Google Drive
                try {
                    $stream = app(GoogleDriveService::class)->download($foto->drive_arquivo_id);
                    $zip->addFromString($foto->nome_arquivo, $stream->getContents());
                } catch (\Exception $e) {
                    // Pula foto com erro no Drive, continua as demais
                    continue;
                }
            } else {
                // Storage local
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
        unlink($tmpZip); // Remove arquivo temporário
    }, $nomeZip, [
        'Content-Type'        => 'application/zip',
        'Content-Disposition' => "attachment; filename=\"{$nomeZip}\"",
    ]);
}
```

## Botão na galeria

```html
<!-- resources/views/gallery/show.blade.php -->
<a href="/galeria/{{ $vinculo->token }}/download"
   class="btn btn-rosa-primary btn-lg w-100">
    <i class="bi bi-download me-2"></i>
    Baixar todas as fotos
</a>
```

## Limitações e cuidados

| Situação | Comportamento |
|----------|--------------|
| Foto com erro no Drive | Pula essa foto, continua o ZIP |
| Foto local inexistente | Pula essa foto, continua o ZIP |
| Trabalho com 0 fotos | ZIP vazio (não bloqueia) |
| Timeout PHP | Configurar `max_execution_time=300` no php.ini |
| Arquivo muito grande | Streaming via `streamDownload` — não usa RAM extra |

## Nome do arquivo ZIP

Gerado dinamicamente:
```php
$nomeZip = Str::slug($trabalho->titulo) . '_fotos.zip';
// "casamento-maria-e-joao_fotos.zip"
```

## Configuração PHP necessária

Para trabalhos com muitas fotos grandes, o php.ini deve ter:
```ini
max_execution_time = 300
memory_limit = 512M
```

Ver `docs/decisions/fix-upload-200mb.md` para configuração completa.
