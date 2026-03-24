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
