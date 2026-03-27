# M06 — Google Drive API

## Propósito

Armazenar as fotos na conta Google Drive da Silvia via Service Account. Cada trabalho tem sua própria pasta no Drive. O serviço faz upload, download, exclusão e gerenciamento de pastas.

## Arquivos

| Arquivo | Papel |
|---------|-------|
| `app/Services/GoogleDriveService.php` | Toda interação com a Drive API |
| `storage/app/google/credentials.json` | Credenciais da Service Account (não versionar) |
| `.env` | `GOOGLE_DRIVE_FOLDER_ID` (pasta raiz) |

## Configuração

### Credenciais

1. Criar projeto no Google Cloud Console
2. Habilitar Google Drive API
3. Criar Service Account
4. Baixar JSON de credenciais → salvar em `storage/app/google/credentials.json`
5. Compartilhar pasta raiz do Drive com o e-mail da Service Account

### .env necessário

```env
GOOGLE_DRIVE_FOLDER_ID=1AbCdEfGhIjKlMnOpQrStUvWxYz  # ID da pasta raiz no Drive
```

## GoogleDriveService

**Arquivo**: `app/Services/GoogleDriveService.php`

```php
class GoogleDriveService
{
    private Google_Client $client;
    private Google_Service_Drive $drive;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setAuthConfig(storage_path('app/google/credentials.json'));
        $this->client->setScopes([Google_Service_Drive::DRIVE]);
        $this->drive = new Google_Service_Drive($this->client);
    }

    // Cria pasta dentro da pasta raiz (ou dentro de outra pasta)
    public function criarPasta(string $nome, ?string $pastaRaizId = null): string
    {
        $pastaRaizId = $pastaRaizId ?? config('services.google.drive_folder_id');

        $metadata = new Google_Service_Drive_DriveFile([
            'name'     => $nome,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents'  => [$pastaRaizId],
        ]);

        $pasta = $this->drive->files->create($metadata, ['fields' => 'id']);
        return $pasta->getId();
    }

    // Faz upload de arquivo para uma pasta
    public function upload(string $pastaId, string $nomeArquivo, string $caminhoLocal, string $mimeType): array
    {
        $metadata = new Google_Service_Drive_DriveFile([
            'name'    => $nomeArquivo,
            'parents' => [$pastaId],
        ]);

        $conteudo = file_get_contents($caminhoLocal);

        $arquivo = $this->drive->files->create($metadata, [
            'data'       => $conteudo,
            'mimeType'   => $mimeType,
            'uploadType' => 'multipart',
            'fields'     => 'id,thumbnailLink,size',
        ]);

        return [
            'id'            => $arquivo->getId(),
            'thumbnailLink' => $arquivo->getThumbnailLink(),
            'size'          => $arquivo->getSize(),
        ];
    }

    // Remove um arquivo do Drive
    public function deletar(string $arquivoId): void
    {
        $this->drive->files->delete($arquivoId);
    }

    // Remove uma pasta (e todo seu conteúdo) do Drive
    public function deletarPasta(string $pastaId): void
    {
        $this->drive->files->delete($pastaId);
    }

    // Retorna stream para download
    public function download(string $arquivoId)
    {
        $response = $this->drive->files->get($arquivoId, ['alt' => 'media']);
        return $response->getBody();
    }

    // Retorna metadados do arquivo
    public function obterArquivo(string $arquivoId): Google_Service_Drive_DriveFile
    {
        return $this->drive->files->get($arquivoId, [
            'fields' => 'id,name,size,mimeType,thumbnailLink,webContentLink',
        ]);
    }
}
```

## Estrutura de pastas no Drive

```
📁 Silvia Souza Fotografa (pasta raiz — GOOGLE_DRIVE_FOLDER_ID)
  └── 📁 Casamento Maria e João - 2026-03-20  (drive_pasta_id do trabalho)
        ├── 🖼 foto_001.jpg
        ├── 🖼 foto_002.jpg
        └── 🖼 foto_003.jpg
  └── 📁 Aniversário 50 anos Claudia - 2026-03-15
        └── 🖼 ...
```

## Fallback local

Se o upload no Drive falhar (exceção), a foto é salva em:
```
storage/app/public/fotos/{trabalho_id}/{nome_arquivo}
```

Nesse caso:
- `drive_arquivo_id` = null
- `caminho_thumbnail` = caminho local do thumbnail

## Download do Drive

No `GalleryController`, para download:
```php
$stream = app(GoogleDriveService::class)->download($foto->drive_arquivo_id);
return response()->streamDownload(function () use ($stream) {
    echo $stream->getContents();
}, $foto->nome_arquivo);
```

## Pacote composer

```
google/apiclient: ^2.0
```

Instalado via:
```bash
composer require google/apiclient
```

## Erros comuns

| Erro | Causa | Solução |
|------|-------|---------|
| `credentials.json not found` | Arquivo não criado | Copiar para `storage/app/google/` |
| `403 Forbidden` | Service Account sem acesso | Compartilhar pasta raiz com e-mail da SA |
| `GOOGLE_DRIVE_FOLDER_ID não definido` | .env não configurado | Adicionar variável no .env |
| Upload falha silenciosamente | Arquivo muito grande para RAM | Usar `uploadType=resumable` para >5MB |

## Segurança

- `storage/app/google/credentials.json` está no `.gitignore`
- Service Account tem acesso SOMENTE à pasta raiz compartilhada
- Não expõe tokens do Drive para o cliente — download é feito server-side
