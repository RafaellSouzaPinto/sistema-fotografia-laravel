# Google Drive API

- Pacote: google/apiclient
- Credenciais: storage/app/google/credentials.json → .gitignore
- Service centralizado: app/Services/GoogleDriveService.php
- .env: GOOGLE_DRIVE_FOLDER_ID (pasta raiz no Drive)
- Cada trabalho cria pasta: "{titulo} - Prévia" ou "{titulo} - Completo"
- Upload retorna: id, thumbnailLink, size
- Download: stream (não expor URL do Drive ao cliente)
- Deletar: arquivo primeiro, depois banco. Pasta ao excluir trabalho.
- thumbnailLink expira → usar proxy do servidor ou storage local
- Rate limit: 300 req/min → fila se necessário
- Fallback: se Drive não configurado, usar storage local
