# Silvia Souza Fotografa — Arquitetura do Sistema

## 1. Visão Geral

Sistema web para gestão de entregas fotográficas. A fotógrafa (Silvia) cadastra trabalhos (prévias ou completos), faz upload das fotos para o Google Drive via API, cadastra clientes com nome e telefone, e gera links únicos por cliente. O cliente acessa o link (sem login), visualiza a galeria e pode baixar as fotos.

---

## 2. Stack Tecnológica

| Camada              | Tecnologia                              |
|---------------------|-----------------------------------------|
| Framework           | Laravel 11                              |
| Linguagem           | PHP 8.2+                                |
| Banco de dados      | MariaDB 10.11                           |
| Cache/Session/Queue | Redis 7 ou File/Sync (local)            |
| Componentes reativos| Livewire 3 (inclui Alpine.js)           |
| CSS                 | Bootstrap 5.3.2                         |
| Storage de fotos    | Google Drive API (conta paga da Silvia) |
| Servidor web        | Nginx ou Apache (local)                 |
| Infraestrutura      | Ambiente local (PHP + MariaDB + Redis instalados na máquina) |

---

## 3. Regras de Negócio

### 3.1 Usuários do sistema

- **Único login**: Silvia (admin). Email: `silviasouzafotografa@gmail.com`, senha: `123456`.
- Não existe cadastro de novos usuários no sistema. Login fixo (seed).
- **Clientes não fazem login**. Acessam por link com token.

### 3.2 Tipos de trabalho

| Tipo               | Descrição                                                         |
|--------------------|-------------------------------------------------------------------|
| **Prévia**         | Amostra de fotos editadas. Cliente só visualiza.                  |
| **Trabalho Completo** | Entrega final. Todas as fotos em alta resolução (Photoshop). Cliente visualiza e baixa. |

- Um evento/festa pode ter **dois trabalhos**: uma prévia e um completo.
- Cada trabalho é independente (upload, clientes, links separados).

### 3.3 Fluxo principal

```
1. Silvia faz login
2. Clica em "Novo Trabalho"
3. Preenche:
   - Nome do trabalho (ex: "Casamento Ana e João")
   - Data do trabalho (ex: 2026-03-15)
   - Tipo: Prévia ou Trabalho Completo
4. Adiciona clientes ao trabalho:
   a. Silvia digita o TELEFONE primeiro
   b. Sistema busca no banco (clientes.telefone):
      - ENCONTROU → preenche o nome automaticamente.
        Campo nome fica editável (caso precise corrigir/atualizar).
      - NÃO ENCONTROU → campo nome aparece vazio, Silvia digita.
        Sistema cria novo client no banco.
   c. Cada cliente adicionado gera um link único (token)
5. Faz upload da pasta de fotos
   - Fotos sobem para o Google Drive via API
6. Trabalho concluído → links ficam disponíveis
7. Silvia copia o link de cada cliente e envia (WhatsApp, etc.)
```

> **Comportamento do Livewire (`ClientManager`):** o campo telefone dispara busca em tempo real (`wire:model.blur` ou debounce). Se achar, preenche o nome via `$set`. Se Silvia editar o nome de um cliente existente, o sistema **atualiza** o cadastro do cliente no banco.

### 3.4 Fluxo do cliente

```
1. Cliente recebe o link (ex: https://dominio.com/galeria/abc123token)
2. Acessa sem login
3. Vê todas as fotos do trabalho em galeria
4. Pode baixar:
   - PC/Desktop → botão "Baixar todas" gera ZIP
   - Mobile → botão por foto individual (salva na galeria)
5. Não pode editar, deletar ou comentar nada
```

### 3.5 Gestão de trabalhos (Silvia)

- **Listar** todos os trabalhos (com filtro por tipo e busca por nome)
- **Editar** trabalho (nome, tipo)
- **Excluir** trabalho (remove do sistema + remove pasta do Google Drive)
- **Adicionar/remover clientes** de um trabalho existente
- Cada cliente adicionado gera automaticamente um novo link com token

---

## 4. Modelagem do Banco de Dados

### 4.1 Tabelas

#### `usuarios`
Login fixo da Silvia (seed). Baseada na tabela padrão `users` do Laravel (renomeada).

| Coluna      | Tipo         | Obs                    |
|-------------|--------------|------------------------|
| id          | bigint PK    | auto increment         |
| nome        | varchar(255) | "Silvia Souza"         |
| email       | varchar(255) | unique                 |
| senha       | varchar(255) | bcrypt                 |
| created_at  | timestamp    |                        |
| updated_at  | timestamp    |                        |
| deleted_at  | timestamp nullable | soft delete       |

#### `clientes`
Clientes cadastrados pela Silvia.

| Coluna      | Tipo         | Obs                    |
|-------------|--------------|------------------------|
| id          | bigint PK    | auto increment         |
| nome        | varchar(255) | Nome do cliente        |
| telefone    | varchar(20)  | Telefone               |
| created_at  | timestamp    |                        |
| updated_at  | timestamp    |                        |
| deleted_at  | timestamp nullable | soft delete       |

> Cliente é **reutilizável**. Se a mesma pessoa contrata outro evento, Silvia não precisa cadastrar de novo.

#### `trabalhos`

| Coluna              | Tipo                          | Obs                                  |
|---------------------|-------------------------------|--------------------------------------|
| id                  | bigint PK                     | auto increment                       |
| titulo              | varchar(255)                  | "Casamento Ana e João"               |
| data_trabalho       | date                          | Data do evento/sessão fotográfica    |
| tipo                | enum('previa', 'completo')    | Prévia ou Trabalho Completo          |
| status              | enum('rascunho', 'publicado') | rascunho = em upload, publicado = links ativos |
| drive_pasta_id      | varchar(255) nullable         | ID da pasta no Google Drive          |
| created_at          | timestamp                     |                                      |
| updated_at          | timestamp                     |                                      |
| deleted_at          | timestamp nullable            | soft delete                          |

#### `trabalho_cliente` (pivot — quais clientes veem qual trabalho)

| Coluna        | Tipo         | Obs                              |
|---------------|--------------|----------------------------------|
| id            | bigint PK    | auto increment                   |
| trabalho_id   | bigint FK    | → trabalhos.id                   |
| cliente_id    | bigint FK    | → clientes.id                    |
| token         | varchar(64)  | unique, gerado automaticamente (Str::random(64)) |
| created_at    | timestamp    |                                  |
| updated_at    | timestamp    |                                  |
| deleted_at    | timestamp nullable | soft delete                |

> **Cada par trabalho+cliente gera um token único.** É esse token que vai na URL do cliente.

#### `fotos`

| Coluna              | Tipo         | Obs                                    |
|---------------------|--------------|----------------------------------------|
| id                  | bigint PK    | auto increment                         |
| trabalho_id         | bigint FK    | → trabalhos.id                         |
| nome_arquivo        | varchar(255) | Nome original do arquivo               |
| drive_arquivo_id    | varchar(255) | ID do arquivo no Google Drive          |
| drive_thumbnail     | text nullable| URL de thumbnail gerada pelo Drive     |
| tamanho_bytes       | bigint       | Peso do arquivo em bytes               |
| ordem               | int default 0| Ordem de exibição                      |
| created_at          | timestamp    |                                        |
| updated_at          | timestamp    |                                        |
| deleted_at          | timestamp nullable | soft delete                      |

### 4.2 Diagrama de relacionamentos

```
usuarios (1) ─── só Silvia, sem FK com nada

clientes (1) ──── (N) trabalho_cliente (N) ──── (1) trabalhos
                        └── token (único por par)

trabalhos (1) ──── (N) fotos
```

---

## 5. Rotas

### 5.1 Rotas autenticadas (Silvia)

```
Prefixo: /admin (middleware: auth)

GET    /admin/dashboard              → Página principal (listagem de trabalhos)
GET    /admin/jobs/create            → Form novo trabalho
POST   /admin/jobs                   → Salvar trabalho
GET    /admin/jobs/{job}/edit        → Editar trabalho
PUT    /admin/jobs/{job}             → Atualizar trabalho
DELETE /admin/jobs/{job}             → Excluir trabalho (+ remove do Drive)
POST   /admin/jobs/{job}/photos      → Upload de fotos
DELETE /admin/photos/{photo}         → Remover foto individual

POST   /admin/jobs/{job}/clients     → Adicionar cliente ao trabalho
DELETE /admin/jobs/{job}/clients/{client} → Remover cliente do trabalho

GET    /admin/clients                → Listar todos os clientes
POST   /admin/clients               → Cadastrar novo cliente
PUT    /admin/clients/{client}       → Editar cliente
DELETE /admin/clients/{client}       → Excluir cliente
```

### 5.2 Rotas públicas (cliente)

```
GET /galeria/{token}                → Galeria do trabalho (via token)
GET /galeria/{token}/download       → Baixar todas em ZIP (PC)
GET /galeria/{token}/photo/{photo}  → Baixar foto individual (mobile)
```

### 5.3 Rota de login

```
GET  /login                         → Tela de login
POST /login                         → Autenticar
POST /logout                        → Sair
```

---

## 6. Google Drive API — Integração

### 6.1 Setup

- Criar projeto no Google Cloud Console
- Habilitar Google Drive API
- Criar Service Account (ou OAuth2 com a conta da Silvia)
- Salvar credenciais em `storage/app/google/credentials.json`
- Pacote recomendado: `google/apiclient` (SDK oficial PHP)

### 6.2 Estrutura no Drive

```
📁 SilviaSouzaFotografa (raiz)
├── 📁 Casamento Ana e João - Prévia
│   ├── foto1.jpg
│   ├── foto2.jpg
│   └── ...
├── 📁 Casamento Ana e João - Completo
│   ├── foto1.psd
│   ├── foto2.jpg
│   └── ...
└── 📁 Aniversário Maria - Prévia
    └── ...
```

### 6.3 Fluxo de upload

```
1. Silvia cria trabalho → sistema cria pasta no Drive (nome = título do trabalho + tipo)
2. Silvia faz upload → arquivos sobem para essa pasta via API
3. Sistema salva drive_file_id de cada foto na tabela photos
4. Para thumbnail: usa a API do Drive para gerar link de visualização (thumbnailLink)
```

### 6.4 Fluxo de download (cliente)

```
1. Cliente clica "Baixar foto"
2. Sistema usa drive_file_id para pegar o arquivo via API
3. Retorna o stream pro navegador (não expõe URL do Drive)
4. "Baixar todas" → sistema baixa todos os arquivos, compacta em ZIP, envia ao cliente
```

### 6.5 Variáveis de ambiente (.env)

```env
GOOGLE_DRIVE_FOLDER_ID=id_da_pasta_raiz_no_drive
GOOGLE_CREDENTIALS_PATH=storage/app/google/credentials.json
```

---

## 7. Estrutura de Pastas Laravel

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   ├── DashboardController.php
│   │   │   ├── JobController.php
│   │   │   ├── PhotoController.php
│   │   │   └── ClientController.php
│   │   └── GalleryController.php          ← rota pública do cliente
│   ├── Middleware/
│   └── Requests/
│       ├── StoreJobRequest.php
│       ├── UpdateJobRequest.php
│       └── StoreClientRequest.php
├── Models/
│   ├── User.php
│   ├── Client.php
│   ├── Job.php
│   ├── JobClient.php                      ← pivot model (tem token)
│   └── Photo.php
├── Services/
│   └── GoogleDriveService.php             ← toda lógica de Drive aqui
├── Livewire/
│   ├── Admin/
│   │   ├── JobList.php                    ← listagem com filtro/busca
│   │   ├── JobForm.php                    ← criar/editar trabalho
│   │   ├── PhotoUploader.php              ← upload com progresso
│   │   └── ClientManager.php             ← adicionar/remover clientes do trabalho
│   └── Gallery.php                        ← galeria pública
└── ...

resources/views/
├── layouts/
│   └── admin.blade.php                    ← layout Bootstrap 5
├── livewire/
│   ├── admin/
│   │   ├── job-list.blade.php
│   │   ├── job-form.blade.php
│   │   ├── photo-uploader.blade.php
│   │   └── client-manager.blade.php
│   └── gallery.blade.php                  ← view pública, responsiva
├── admin/
│   └── dashboard.blade.php
├── gallery/
│   └── show.blade.php
└── auth/
    └── login.blade.php
```

---

## 8. Componentes Livewire (comportamento)

### 8.1 `JobList`
- Lista todos os trabalhos em tabela
- Filtro por tipo (prévia/completo)
- Busca por nome
- Botões: editar, excluir, ver links

### 8.2 `JobForm`
- Campos: título, data do trabalho (date picker), tipo (radio: prévia ou completo)
- Ao salvar, cria pasta no Google Drive

### 8.3 `PhotoUploader`
- Drag & drop ou seleção de arquivos
- Upload múltiplo com barra de progresso (Livewire file upload)
- Cada arquivo sobe pro Drive e salva na tabela photos
- Preview de thumbnail após upload

### 8.4 `ClientManager`
- Dentro da página do trabalho
- Campo **telefone** com máscara `(99) 99999-9999`
  - `wire:model.blur` ou `wire:model.live.debounce.500ms` → dispara busca no banco
  - **Achou**: preenche campo nome automaticamente, campo fica editável
  - **Não achou**: campo nome aparece vazio pra Silvia digitar (novo cliente)
- Se Silvia editar o nome de um cliente existente → atualiza `clientes.nome` no banco
- Botão "Adicionar" → vincula client ao job, gera token, exibe link copiável
- Botão de copiar link (clipboard)
- Botão de remover cliente do trabalho
- Lista dos clientes já adicionados com seus links

### 8.5 `Gallery` (pública)
- Recebe token via URL
- Grid responsivo de fotos (thumbnails do Drive)
- Lightbox ao clicar na foto (tela cheia)
- Botão "Baixar foto" por foto (mobile-friendly)
- Botão "Baixar todas" (ZIP, só desktop)

---

## 9. Ambiente Local — Setup

### 9.1 Requisitos na máquina

- PHP 8.2+ com extensões: `gd`, `zip`, `pdo_mysql`, `redis`, `mbstring`, `curl`, `openssl`
- Composer
- MariaDB 10.11
- Redis 7 (opcional, pode usar `QUEUE_CONNECTION=sync` e `CACHE_DRIVER=file`)
- Node.js + NPM (para compilar assets se necessário)
- Nginx ou Apache (ou `php artisan serve` para dev)

### 9.2 .env relevante

```env
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=silvia_fotos
DB_USERNAME=root
DB_PASSWORD=sua_senha

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

GOOGLE_DRIVE_FOLDER_ID=id_da_pasta_raiz_no_drive
GOOGLE_CREDENTIALS_PATH=storage/app/google/credentials.json
```

### 9.3 Subir o projeto

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
# Acessa em http://localhost:8000
```

### 9.4 php.ini (ajustar para uploads pesados)

```ini
upload_max_filesize = 200M
post_max_size = 250M
max_execution_time = 300
memory_limit = 512M
```

---

## 10. Seed Inicial

```php
// database/seeders/UsuarioSeeder.php
Usuario::create([
    'nome'   => 'Silvia Souza',
    'email'  => 'silviasouzafotografa@gmail.com',
    'senha'  => bcrypt('123456'),
]);
```

---

## 11. Migrations (ordem)

```
1. create_usuarios_table
2. create_clientes_table
3. create_trabalhos_table
4. create_trabalho_cliente_table
5. create_fotos_table
```

---

## 12. Riscos e Armadilhas

| Risco | Mitigação |
|-------|-----------|
| Upload de fotos pesadas (PSD, TIF 100MB+) trava | Usar upload chunked ou queue job para subir pro Drive em background |
| Token do cliente vaza | Token de 64 chars com `Str::random()`. Sem dados sensíveis na galeria |
| Google Drive API tem rate limit (300 req/min) | Upload em fila (Redis queue), um arquivo por vez |
| ZIP de trabalho completo pode ser gigante (5GB+) | Gerar ZIP via streaming (`ZipStream-PHP`), não acumular em memória |
| `php.ini` bloqueia upload grande | Configurar `upload_max_filesize=200M`, `post_max_size=250M` no php.ini local |
| Foto deletada no sistema mas não no Drive | Sempre deletar no Drive primeiro, depois no banco (ou usar soft delete + job de limpeza) |
| Colunas em pt-br no Laravel | Sobrescrever `$table`, casts e `$fillable` nos Models. Auth exige mapear `nome`→`name` e `senha`→`password` via accessors ou custom guard |

---

## 13. MVP — O que fazer primeiro

**Fase 1 (MVP):**
1. Ambiente local rodando (PHP + MariaDB + `php artisan serve`)
2. Login fixo (seed)
3. CRUD de trabalhos
4. CRUD de clientes (busca por telefone)
5. Vincular clientes a trabalhos (gerar token/link)
6. Upload de fotos para Google Drive
7. Galeria pública (visualizar + baixar)

**Fase 2 (pós-MVP):**
- Compactação automática de thumbnails
- Portfólio público da Silvia
- Integração com gateway de pagamento
- Notificação por WhatsApp (API)
- Histórico/relatórios
