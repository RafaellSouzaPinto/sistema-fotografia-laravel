# Visão Geral da Arquitetura

## Stack tecnológica

| Camada | Tecnologia |
|--------|-----------|
| Backend | PHP 8.3 + Laravel 11 |
| Frontend reativo | Livewire 3 + Alpine.js 3 |
| CSS | Bootstrap 5.3.2 via CDN + Bootstrap Icons |
| Fontes | Google Fonts: Playfair Display + Inter |
| Banco de dados | MariaDB 10.11 |
| Armazenamento | Google Drive API (principal) + storage local (fallback) |
| Compressão | Intervention/Image com driver GD |

## Modelagem do banco de dados

```
usuarios
  id, nome, email(unique), senha, remember_token, timestamps, deleted_at

clientes
  id, nome, telefone(varchar 20), timestamps, deleted_at

trabalhos
  id, titulo, data_trabalho(date), tipo(enum: previa|completo),
  status(enum: rascunho|publicado, default: rascunho),
  drive_pasta_id(nullable), timestamps, deleted_at

trabalho_cliente  ← pivot com dados extras
  id, trabalho_id(FK→trabalhos), cliente_id(FK→clientes),
  token(varchar 64, unique), expira_em(dateTime nullable),
  status_link(enum: disponivel|expirado, default: disponivel),
  timestamps, deleted_at

fotos
  id, trabalho_id(FK→trabalhos), nome_arquivo, drive_arquivo_id,
  drive_thumbnail(text nullable), caminho_thumbnail(string nullable),
  tamanho_bytes(bigint), ordem(int), timestamps, deleted_at
```

### Relacionamentos

```
Trabalho  hasMany      Fotos
Trabalho  belongsToMany  Clientes  (via trabalho_cliente, using TrabalhoCliente)
Cliente   belongsToMany  Trabalhos (via trabalho_cliente)
TrabalhoCliente  belongsTo  Trabalho
TrabalhoCliente  belongsTo  Cliente
```

## Estrutura de arquivos principais

```
app/
  Http/Controllers/
    Auth/LoginController.php      ← login/logout
    GalleryController.php         ← galeria pública + downloads
    HomeController.php            ← página inicial
  Livewire/Admin/
    JobList.php                   ← dashboard /admin/dashboard
    JobForm.php                   ← criar/editar trabalho
    ClientManager.php             ← vincular clientes ao trabalho
    PhotoUploader.php             ← upload de fotos
    ClientList.php                ← /admin/clients
  Models/
    Usuario.php                   ← autenticação customizada
    Cliente.php
    Trabalho.php
    TrabalhoCliente.php           ← pivot model com lógica de expiração
    Foto.php
  Services/
    GoogleDriveService.php        ← toda interação com Drive API
    ImageCompressorService.php    ← compressão e thumbnails

resources/views/
  auth/login.blade.php
  layouts/admin.blade.php         ← layout base das telas admin
  livewire/admin/
    job-list.blade.php
    job-form.blade.php
    client-manager.blade.php
    photo-uploader.blade.php
    client-list.blade.php
  gallery/
    show.blade.php                ← galeria pública
    expirado.blade.php            ← link expirado
  home.blade.php
```

## Fluxo: Silvia cadastra e publica um trabalho

```
1. Login → /admin/dashboard
2. Clicar "Novo Trabalho" → JobForm::salvar()
   - Cria registro em `trabalhos` (status: rascunho)
   - Cria pasta no Google Drive (GoogleDriveService::criarPasta)
3. Adicionar clientes → ClientManager::adicionar()
   - Busca cliente por telefone ou cria novo
   - Cria registro em `trabalho_cliente` com token único (Str::random(64))
4. Fazer upload de fotos → PhotoUploader::updatedArquivos()
   - Para tipo=previa: comprime imagem, gera thumbnail
   - Para tipo=completo: mantém original + gera thumbnail
   - Tenta upload no Drive, fallback storage local
5. Publicar → JobForm::publicar()
   - Valida: ≥1 cliente vinculado, ≥1 foto
   - Muda status de `rascunho` para `publicado`
6. Copiar link → ClientManager exibe /galeria/{token}
7. Enviar via WhatsApp
```

## Fluxo: Cliente acessa a galeria

```
1. Abre link /galeria/{token} (sem login)
2. GalleryController::show()
   - Busca TrabalhoCliente pelo token
   - Verifica expiração (estaExpirado())
   - Se expirado → view gallery/expirado.blade.php
   - Se válido → view gallery/show.blade.php com fotos
3. Visualiza grid de fotos
4. Clica foto → lightbox Alpine.js (navegação anterior/próximo)
5. Baixa foto individual → GalleryController::downloadFoto()
6. Baixa todas em ZIP → GalleryController::downloadTodas()
```

## Usuário único do sistema

- **Silvia Souza** — única usuária admin
- Email: silviasouzafotografa@gmail.com
- Senha: 123456 (hash bcrypt na coluna `senha`)
- Não existe tela de registro — login fixo via seeder

## Credenciais e configuração sensível

| Arquivo | Propósito | Git? |
|---------|-----------|------|
| `storage/app/google/credentials.json` | Service Account Google Drive | **NUNCA** |
| `.env` | Variáveis de ambiente | **NUNCA** |
| `php.ini` (raiz do projeto) | Upload 200MB | Pode commitar |

## Particularidades críticas

- Coluna de senha é `senha`, não `password` → usar `Hash::check()` + `Auth::login()`
- O Model `Usuario` sobrescreve `getAuthPasswordName()` retornando `'senha'`
- Todas as colunas e mensagens estão em PT-BR
- SoftDeletes em todas as tabelas
- `TrabalhoCliente` é pivot model com lógica própria (não é pivot simples)
