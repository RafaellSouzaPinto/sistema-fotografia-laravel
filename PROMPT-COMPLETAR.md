Faltam arquivos na estrutura do projeto. Crie tudo abaixo sem alterar nada que já existe.

## 1. README.md na raiz

```markdown
# 📸 Silvia Souza Fotografa

Sistema de gestão de entregas fotográficas para fotógrafa profissional.

## O que faz

- Fotógrafa cadastra trabalhos (prévias ou completos)
- Sobe fotos para Google Drive via API
- Adiciona clientes por telefone (busca automática)
- Gera link único por cliente com token
- Cliente acessa o link, visualiza galeria e baixa fotos

## Stack

| Camada | Tecnologia |
|--------|-----------|
| Backend | PHP 8.3 + Laravel 11 |
| Frontend | Livewire 3 + Alpine.js 3 + Bootstrap 5.3.2 |
| Banco | MariaDB 10.11 |
| Storage | Google Drive API (Service Account) |
| CSS | Bootstrap 5 via CDN + custom.css |
| Fontes | Playfair Display + Inter (Google Fonts) |

## Requisitos

- PHP 8.3+ com extensões: gd, zip, pdo_mysql, mbstring, curl, openssl
- Composer
- MariaDB 10.11+
- Node.js (opcional, não usa npm pro CSS)

## Instalação

```bash
# Clonar
git clone <repo-url>
cd sistema-fotografia

# Dependências
composer install

# Configurar
cp .env.example .env
php artisan key:generate

# Banco
mysql -u root -e "CREATE DATABASE silvia_fotos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
# Editar .env com credenciais do banco

# Migrations + seed
php artisan migrate --seed

# Storage link
php artisan storage:link

# Google Drive (opcional)
# Colocar credentials.json em storage/app/google/credentials.json
# Configurar GOOGLE_DRIVE_FOLDER_ID no .env

# Rodar
php8.3 -c php.ini artisan serve --host=127.0.0.1 --port=9000
```

## Acesso

- URL: http://127.0.0.1:9000
- Email: silviasouzafotografa@gmail.com
- Senha: 123456

## Testes

```bash
php artisan test
```

## Estrutura do projeto

```
├── CLAUDE.md                  # Contexto principal para Claude Code
├── README.md                  # Este arquivo
│
├── .claude/                   # Configuração Claude Code
│   ├── settings.json
│   ├── hooks/
│   │   └── pre-commit.md      # Checklist antes de finalizar feature
│   └── skills/                # Habilidades por tecnologia
│       ├── php/SKILL.md
│       ├── laravel/SKILL.md
│       ├── livewire/SKILL.md
│       ├── bootstrap/SKILL.md
│       ├── google-drive/SKILL.md
│       ├── code-review/SKILL.md
│       ├── testing/SKILL.md
│       ├── refactor/SKILL.md
│       └── release/SKILL.md
│
├── docs/                      # Documentação do projeto
│   ├── architecture.md        # Modelagem, rotas, regras de negócio
│   ├── frontend-spec.md       # Cores, tipografia, layout de cada tela
│   ├── decisions/             # Decisões técnicas e correções
│   └── runbooks/              # Features detalhadas com código
│
├── tools/                     # Scripts e prompts auxiliares
│   ├── scripts/               # Shell scripts (serve, reset-db, etc.)
│   └── prompts/               # Templates reutilizáveis para IA
│
├── app/                       # Código da aplicação Laravel
│   ├── Http/Controllers/      # Controllers (Admin + Gallery)
│   │   └── CLAUDE.md          # Contexto: rotas, responsabilidades dos controllers
│   ├── Models/                # Eloquent Models
│   │   └── CLAUDE.md          # Contexto: tabelas PT-BR, relacionamentos, auth especial
│   ├── Services/              # Services (GoogleDriveService)
│   │   └── CLAUDE.md          # Contexto: integração Drive, fallback local
│   └── Livewire/Admin/        # Componentes Livewire
│       └── CLAUDE.md          # Contexto: padrões Livewire, componentes existentes
│
├── database/                  # Migrations e seeders
│   └── CLAUDE.md              # Contexto: tabelas PT-BR, ordem de migrations, seed
│
├── resources/views/           # Blade templates
├── routes/                    # Rotas web
├── tests/                     # Testes PHPUnit
├── public/css/custom.css      # CSS customizado (paleta rosa)
└── storage/app/google/        # Credentials Google Drive (.gitignore)
```

## Módulos da aplicação

| Módulo | Pasta | Responsabilidade |
|--------|-------|-----------------|
| Controllers | `app/Http/Controllers/` | Rotas admin (auth) e galeria pública |
| Models | `app/Models/` | Entidades: Usuario, Cliente, Trabalho, TrabalhoCliente, Foto |
| Services | `app/Services/` | GoogleDriveService (upload, download, deletar) |
| Livewire | `app/Livewire/Admin/` | Componentes reativos: JobList, JobForm, ClientManager, PhotoUploader, ClientList |
| Database | `database/` | Migrations (tabelas PT-BR), seeders |

## Público alvo

- **Admin (fotógrafa):** mulher 55+ anos, interface intuitiva, botões grandes, sem jargão
- **Clientes:** adultos/idosos 40-70+, acessam pelo celular, galeria simples e bonita

## Licença

Projeto privado — uso exclusivo Silvia Souza Fotografa.
```

## 1.5. CLAUDE.md dos módulos internos (equivalente ao src/api/CLAUDE.md e src/persistence/CLAUDE.md da referência)

### app/Http/Controllers/CLAUDE.md
```markdown
# Controllers — Contexto para Claude Code

## Estrutura
- `Admin/` → Controllers protegidos por middleware auth
- `GalleryController.php` → Rota pública da galeria (sem auth)
- `Auth/LoginController.php` → Login manual (Hash::check + Auth::login)

## Rotas admin (middleware: auth, prefix: /admin)
| Método | Rota | Controller | Ação |
|--------|------|-----------|------|
| GET | /admin/dashboard | Livewire JobList | Listar trabalhos |
| GET | /admin/jobs/create | Livewire JobForm | Novo trabalho |
| GET | /admin/jobs/{id}/edit | Livewire JobForm | Editar trabalho |
| GET | /admin/clients | Livewire ClientList | Listar clientes |

## Rotas públicas (sem auth)
| Método | Rota | Controller | Ação |
|--------|------|-----------|------|
| GET | /galeria/{token} | GalleryController@show | Galeria do cliente |
| GET | /galeria/{token}/download | GalleryController@downloadTodas | ZIP com todas as fotos |
| GET | /galeria/{token}/foto/{foto} | GalleryController@downloadFoto | Download foto individual |

## Regras
- GalleryController SEMPRE valida que o token pertence ao trabalho da foto
- Download ZIP só permitido se trabalho.tipo = 'completo'
- Login usa Hash::check() manual (coluna 'senha', não 'password')
```

### app/Models/CLAUDE.md
```markdown
# Models — Contexto para Claude Code

## Tabelas (TODAS com colunas em PT-BR e SoftDeletes)

### Usuario ($table = 'usuarios')
- Colunas: nome, email, senha
- Auth especial: getAuthPassword() retorna $this->senha
- Sem relacionamentos com outras tabelas
- Login fixo via seed (só Silvia)

### Cliente ($table = 'clientes')
- Colunas: nome, telefone
- Relacionamento: trabalhos() → belongsToMany via trabalho_cliente com pivot 'token'
- Reutilizável: mesmo cliente pode estar em vários trabalhos
- Busca por telefone: limpar máscara antes de comparar

### Trabalho ($table = 'trabalhos')
- Colunas: titulo, data_trabalho (date), tipo (enum: previa/completo), status (enum: rascunho/publicado), drive_pasta_id
- Relacionamentos: clientes() → belongsToMany com pivot 'token', fotos() → hasMany
- $casts: data_trabalho → date

### TrabalhoCliente ($table = 'trabalho_cliente')
- Pivot model com colunas próprias: trabalho_id, cliente_id, token (64 chars unique)
- Relacionamentos: trabalho() → belongsTo, cliente() → belongsTo
- Token gerado com Str::random(64) ao vincular cliente

### Foto ($table = 'fotos')
- Colunas: trabalho_id, nome_arquivo, drive_arquivo_id, drive_thumbnail, tamanho_bytes, ordem
- Relacionamento: trabalho() → belongsTo
- drive_arquivo_id pode ser path local (storage) ou ID do Google Drive
```

### app/Services/CLAUDE.md
```markdown
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
```

### app/Livewire/Admin/CLAUDE.md
```markdown
# Livewire Components — Contexto para Claude Code

## Componentes existentes

### JobList (dashboard — /admin/dashboard)
- Lista trabalhos em cards com busca e filtro (todos/prévias/completos)
- withCount('fotos', 'clientes') para contadores
- Excluir com wire:confirm

### JobForm (novo/editar — /admin/jobs/create e /admin/jobs/{id}/edit)
- Campos: titulo, data_trabalho, tipo (radio)
- Após salvar: mostra seções ClientManager e PhotoUploader
- Botão publicar (verde) quando tem ≥1 foto e ≥1 cliente

### ClientManager (dentro do JobForm)
- Campo telefone com máscara (99) 99999-9999
- wire:model.blur no telefone → busca no banco
- Encontrou: preenche nome + borda verde + "Cliente encontrado!"
- Não encontrou: campo nome vazio + "Novo cliente"
- Gera token Str::random(64) ao vincular
- Exibe link copiável por cliente
- Botão copiar link com Alpine.js (clipboard)

### PhotoUploader (dentro do JobForm)
- Upload múltiplo com drag & drop
- Validação: mimes jpg,jpeg,png,psd,tif,tiff | max:204800 (200MB)
- Grid de thumbnails com botão X para remover
- Salva em storage local ou Google Drive

### ClientList (/admin/clients)
- Lista todos os clientes com busca
- Edição inline (nome + telefone)
- Contagem de trabalhos vinculados

## Padrões
- Todos usam ->layout('layouts.admin')
- Toast via $dispatch('notify', message: 'texto')
- Confirmação via wire:confirm="mensagem em português"
- Busca com wire:model.live.debounce.300ms
```

### database/CLAUDE.md
```markdown
# Database — Contexto para Claude Code

## Regras críticas
1. Colunas em PT-BR (nome, telefone, titulo, data_trabalho, etc.)
2. TODAS as tabelas têm SoftDeletes (deleted_at)
3. NUNCA alterar migration já rodada — criar nova migration para mudanças
4. Timestamps: created_at, updated_at, deleted_at (em inglês)

## Tabelas (ordem de criação)
1. usuarios (nome, email, senha)
2. clientes (nome, telefone)
3. trabalhos (titulo, data_trabalho, tipo, status, drive_pasta_id)
4. trabalho_cliente (trabalho_id FK, cliente_id FK, token unique)
5. fotos (trabalho_id FK, nome_arquivo, drive_arquivo_id, drive_thumbnail, tamanho_bytes, ordem)

## Seeders
- UsuarioSeeder: cria Silvia (email: silviasouzafotografa@gmail.com, senha: 123456)
- Pode ter seeder de dados fake para teste (trabalhos, clientes, fotos)

## Relacionamentos
- trabalhos ←→ clientes: N:N via trabalho_cliente (com token)
- trabalhos → fotos: 1:N
- usuarios: isolado (só login)
```

## 2. Pasta tools/ com scripts e prompts

Criar:

### tools/scripts/serve.sh
```bash
#!/bin/bash
# Sobe o servidor com php.ini customizado
echo "🚀 Iniciando servidor na porta 9000..."
php8.3 -c php.ini artisan serve --host=127.0.0.1 --port=9000
```

### tools/scripts/reset-db.sh
```bash
#!/bin/bash
# Reseta o banco completo (migrate fresh + seed)
echo "⚠️  Resetando banco de dados..."
php artisan migrate:fresh --seed
php artisan storage:link
echo "✅ Banco resetado e seed rodado."
```

### tools/scripts/clear-cache.sh
```bash
#!/bin/bash
# Limpa todos os caches do Laravel
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
echo "✅ Cache limpo."
```

### tools/scripts/run-tests.sh
```bash
#!/bin/bash
# Roda todos os testes
echo "🧪 Rodando testes..."
php artisan test --verbose
```

Tornar todos executáveis:
```bash
chmod +x tools/scripts/*.sh
```

### tools/prompts/nova-feature.md
```markdown
# Prompt para criar nova feature

Use este template ao pedir para o Claude Code criar uma feature nova:

---

Leia o CLAUDE.md e as skills em .claude/skills/ antes de começar.

## Feature: [NOME DA FEATURE]

### Contexto
[Descreva o que precisa ser feito e por quê]

### Regras de negócio
[Liste as regras específicas]

### Arquivos que devem ser criados/alterados
- [ ] Migration (se necessário)
- [ ] Model (se necessário)
- [ ] Controller ou Livewire component
- [ ] View Blade
- [ ] Teste em tests/Feature/

### Critério de conclusão
- [ ] Funciona visualmente igual ao FRONTEND-SPEC.md
- [ ] Teste passa: php artisan test
- [ ] Checklist do .claude/hooks/pre-commit.md ok

### Não fazer
- Não alterar migrations existentes
- Não mexer em funcionalidades que já funcionam
- Não remover testes existentes
```

### tools/prompts/corrigir-bug.md
```markdown
# Prompt para corrigir bug

Use este template ao pedir para o Claude Code corrigir um bug:

---

Leia o CLAUDE.md e as skills em .claude/skills/ antes de começar.

## Bug: [DESCRIÇÃO CURTA]

### Comportamento atual
[O que está acontecendo de errado]

### Comportamento esperado
[O que deveria acontecer]

### Como reproduzir
1. [Passo 1]
2. [Passo 2]
3. [Resultado errado]

### Arquivos suspeitos
- [arquivo 1]
- [arquivo 2]

### Após corrigir
- [ ] Testar manualmente o fluxo
- [ ] Rodar php artisan test
- [ ] Verificar que não quebrou outra coisa
```

## 3. Skills faltantes

### .claude/skills/refactor/SKILL.md
```markdown
# Skill: Refatoração

Ao refatorar código neste projeto:

## Regras
- Nunca alterar o comportamento externo (mesma entrada → mesma saída)
- Rodar `php artisan test` antes E depois de refatorar
- Se o teste falhar depois, reverter
- Um refactor por commit (não misturar com feature nova)
- Manter nomes de variáveis/métodos em português onde já está em português

## Padrões de refactor comuns
- Query N+1 → usar with() ou withCount()
- Lógica duplicada → extrair para método privado ou Service
- Controller gordo → mover lógica para Livewire component ou Service
- Validação inline → extrair para Form Request
- Strings hardcoded → extrair para config ou constante
- SQL raw → converter para Eloquent quando possível

## Não refatorar
- Migrations já rodadas (criar nova migration)
- Código que está coberto por teste e funciona — a menos que tenha motivo claro
- Nomes de tabelas/colunas (são em PT-BR por design)
```

### .claude/skills/release/SKILL.md
```markdown
# Skill: Release / Deploy

## Checklist antes de fazer release

### Código
- [ ] Todos os testes passam: `php artisan test`
- [ ] Sem dd(), dump(), console.log() no código
- [ ] Sem TODO ou FIXME crítico pendente
- [ ] .env.example atualizado com todas as variáveis necessárias
- [ ] .gitignore inclui: credentials.json, php.ini, .claude/settings.json

### Banco
- [ ] Todas as migrations rodam sem erro: `php artisan migrate:fresh --seed`
- [ ] Seed cria dados mínimos para o sistema funcionar (usuário Silvia)
- [ ] Nenhuma migration altera tabela já existente em produção (criar nova)

### Funcionalidades
- [ ] Login funciona
- [ ] Criar trabalho funciona
- [ ] Adicionar cliente com busca por telefone funciona
- [ ] Upload de foto funciona (testar com arquivo de pelo menos 10MB)
- [ ] Link do cliente gerado e copiável
- [ ] Galeria pública carrega com token válido
- [ ] Download individual funciona
- [ ] Download ZIP funciona (só trabalho completo)
- [ ] Publicar trabalho muda status
- [ ] Excluir trabalho com confirmação funciona

### Servidor
- [ ] php.ini configurado: upload_max_filesize=200M, post_max_size=250M
- [ ] storage:link criado
- [ ] Permissões de storage/app corretas (775)
- [ ] Google Drive credentials configurado (se usando Drive)

## Comando de deploy
```bash
# Em produção
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
```
```

## 4. Dar chmod nos scripts

```bash
chmod +x tools/scripts/*.sh
```

Crie tudo agora. Não altere nenhum arquivo existente.
