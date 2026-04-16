# Services — Contexto para Claude Code

## GoogleDriveService

Arquivo: `app/Services/GoogleDriveService.php`

Centraliza TODA interação com Google Drive API. Nenhum outro arquivo deve chamar a API do Drive diretamente.

### Métodos
- `criarPasta(nome, pastaRaizId?)` → cria pasta no Drive, retorna ID
- `upload(pastaId, nomeArquivo, caminhoLocal, mimeType)` → upload arquivo, retorna {id, thumbnailLink, size}
- `deletar(arquivoId)` → deleta arquivo do Drive
- `deletarPasta(pastaId)` → deleta pasta e conteúdo
- `download(arquivoId)` → retorna stream do arquivo

### Configuração
- Credenciais: storage/app/google/credentials.json
- Pasta raiz: env('GOOGLE_DRIVE_FOLDER_ID')
- Pacote: google/apiclient

### Fallback
Se as credenciais não existem ou GOOGLE_DRIVE_FOLDER_ID está vazio, o sistema usa storage local (disk 'public') como fallback. Isso permite desenvolvimento sem configurar o Drive.
