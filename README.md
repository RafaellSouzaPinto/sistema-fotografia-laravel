# Silvia Souza Fotografa

> Sistema web de gestão de entregas fotográficas para fotógrafa profissional.

![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?logo=laravel&logoColor=white)
![Livewire](https://img.shields.io/badge/Livewire-3-FB70A9)
![MariaDB](https://img.shields.io/badge/MariaDB-10.11-003545?logo=mariadb&logoColor=white)
![Google Drive](https://img.shields.io/badge/Google%20Drive-API-4285F4?logo=googledrive&logoColor=white)
[![Produção](https://img.shields.io/badge/Produção-silviasouzafotografa.com.br-c27a8e?logo=google-chrome&logoColor=white)](https://silviasouzafotografa.com.br/)
[![Homologação](https://img.shields.io/badge/Homologação-homolog.silviasouzafotografa.com.br-6c757d?logo=google-chrome&logoColor=white)](https://homolog.silviasouzafotografa.com.br/)

---

## Sobre o projeto

**Silvia Souza** é fotógrafa profissional com mais de 40 anos de experiência. Este sistema foi desenvolvido para resolver um problema real do dia a dia dela: entregar fotos para clientes de forma simples, elegante e sem dependência de plataformas de terceiros.

O fluxo é direto:

1. Silvia cadastra um trabalho (prévia ou álbum completo) e faz upload das fotos
2. Adiciona os clientes por telefone — o sistema busca automaticamente ou cria novo
3. Cada par trabalho+cliente gera um link único com token
4. Silvia envia o link pelo WhatsApp
5. O cliente abre no celular ou no computador, visualiza a galeria e baixa as fotos — sem precisar criar conta ou fazer login

O público alvo inclui clientes adultos e idosos, então a interface prioriza textos claros, botões grandes e experiência sem fricção.

---

## Funcionalidades

### Painel administrativo (Silvia)
- Dashboard com todos os trabalhos, filtro por tipo (prévia / completo) e busca por título
- Criação e edição de trabalhos com campos título, data e tipo
- Upload de fotos com suporte a até 200MB por arquivo, drag & drop e reordenação
- Definição de foto de capa para cada trabalho
- Compressão automática de imagens antes do upload
- Estatísticas do dashboard: total de trabalhos, clientes, fotos e espaço em disco

### Gestão de clientes e links
- Busca de cliente por telefone com máscara automática
- Criação inline caso o cliente não exista
- Geração de token único por par trabalho+cliente (64 caracteres)
- Botão "Copiar link" direto no painel
- Renovação de links expirados
- Expiração configurável de links
- Rastreamento de visualização: sabe quando o cliente acessou

### Galeria pública (clientes)
- Acesso sem login via link com token
- Saudação personalizada com nome do cliente
- Grid responsivo com lightbox para visualização ampliada
- Download de foto individual
- Download de todas as fotos em ZIP (apenas para álbuns completos)
- Bloqueio de acesso enquanto o trabalho não for publicado

### Outras features
- Arquivamento de trabalhos: download local para HD antes de finalizar
- Alteração de senha pelo painel
- Fallback automático para storage local caso Google Drive não esteja configurado
- Interface responsiva para mobile e desktop

---

## Stack tecnológica

| Camada | Tecnologia | Motivo da escolha |
|--------|-----------|-------------------|
| Backend | PHP 8.3 + Laravel 11 | Maturidade, ecosistema robusto, ótima integração com IA |
| Frontend reativo | Livewire 3 + Alpine.js 3 | Reatividade sem build step, sem npm |
| UI | Bootstrap 5.3.2 via CDN | Sem compilação, componentes prontos, responsivo |
| CSS | `public/css/custom.css` | Paleta rosa personalizada com CSS variables |
| Fontes | Playfair Display + Inter (Google Fonts) | Elegância editorial + legibilidade |
| Banco | MariaDB 10.11 | Estabilidade, colunas e mensagens em PT-BR |
| Storage | Google Drive API (Service Account) | Armazenamento ilimitado sem custo extra |

Sem Redis, sem filas, sem build tools. A stack foi escolhida para ser simples de manter e fácil de implementar com IA.

---

## Como foi planejado

Este projeto foi concebido e arquitetado por mim **antes de escrever uma linha de código**. A abordagem foi de documentação-first: toda a lógica de negócio, visual e arquitetura foi definida em arquivos Markdown estruturados que serviram como contrato para a implementação.

### Camadas de documentação criadas

```
docs/
├── architecture.md       → Modelagem de banco, rotas, fluxos e integrações
├── frontend-spec.md      → Paleta de cores, tipografia e layout de cada tela
├── runbooks/features.md  → Cada feature com ordem de implementação e código de referência
└── decisions/            → Registro de decisões técnicas e correções (previne repetição)
```

Cada documento tem uma responsabilidade clara:

- **[architecture.md](docs/architecture.md)** — Define as 5 tabelas PT-BR, seus relacionamentos, as rotas autenticadas e públicas, e o fluxo completo de Silvia e dos clientes. É o "contrato técnico" do sistema.

- **[frontend-spec.md](docs/frontend-spec.md)** — Especifica pixel a pixel: variáveis CSS da paleta rosa (`--rosa-principal: #c27a8e`), tipografia (Playfair Display para títulos, Inter para corpo), e o layout de cada tela (login, dashboard, novo trabalho, galeria pública).

- **[runbooks/features.md](docs/runbooks/features.md)** — Roteiro sequencial de 7+ features, cada uma com passos ordenados e trechos de código PHP/Blade/Livewire de referência. A IA implementou seguindo esse roteiro.

- **[docs/decisions/](docs/decisions/)** — Decisões tomadas ao longo do desenvolvimento: como resolver o upload de 200MB, a correção do botão publicar, a galeria pública. Garante que o mesmo problema não precise ser resolvido duas vezes.

### CLAUDE.md — guia executivo

Na raiz do projeto há um `CLAUDE.md` que funciona como briefing permanente: stack, tabelas, regras críticas (como a coluna `senha` que quebra a autenticação padrão do Laravel), comandos para rodar o servidor, e referências para toda a documentação. Qualquer IA que abrir o projeto parte desse contexto.

Além do raiz, há CLAUDE.md específicos por camada da aplicação:

```
app/Http/Controllers/CLAUDE.md   → Rotas, responsabilidades dos controllers
app/Models/CLAUDE.md             → Tabelas PT-BR, relacionamentos, tokens
app/Livewire/Admin/CLAUDE.md     → 7 componentes, padrões Livewire
app/Services/CLAUDE.md           → GoogleDriveService, fallback local
database/CLAUDE.md               → Ordem de migrations, seed
```

---

## Como a IA foi utilizada

Claude Code foi utilizado como **co-desenvolvedor**, não como gerador de código aleatório. A diferença está na estrutura criada para guiá-lo.

### Skills por domínio

Cada tecnologia do projeto tem um arquivo `SKILL.md` em `.claude/skills/` que codifica os padrões específicos deste projeto — não padrões genéricos da tecnologia:

```
.claude/skills/
├── php/SKILL.md          → PHP 8.3 (strict_types, match, typed properties)
├── laravel/SKILL.md      → Tabelas PT-BR, auth especial com Hash::check(), SoftDeletes em todos os models
├── livewire/SKILL.md     → wire:model, wire:confirm, $dispatch notify, máscaras Alpine
├── bootstrap/SKILL.md    → Paleta rosa, CSS variables, mobile-first, botões 44px touch
├── google-drive/SKILL.md → Service Account, criarPasta, upload, download stream, fallback
├── code-review/SKILL.md  → Segurança, PT-BR everywhere, UX para adultos/idosos
├── testing/SKILL.md      → RefreshDatabase, factories, actingAs(), nomes em português
├── refactor/SKILL.md     → N+1 → with/withCount, controller gordo → Livewire/Service
└── release/SKILL.md      → Checklist deploy: 200MB php.ini, credentials, migrations
```

A IA carrega a skill relevante antes de implementar qualquer feature. Isso garantiu consistência em todo o código — a IA não "esquecia" que a coluna de senha se chama `senha`, ou que mensagens devem estar em PT-BR.

### Templates de requisição

A pasta `tools/prompts/` contém templates padronizados para pedir features e correções à IA. Cada template garante que o contexto mínimo necessário sempre acompanha a requisição, evitando implementações que ignoram as regras do projeto.

### Checklist de qualidade (hook pré-commit)

O arquivo `.claude/hooks/pre-commit.md` define 10 itens que a IA precisa verificar antes de declarar uma feature concluída:

1. `php artisan route:list` sem rotas quebradas
2. Views Blade compilam sem erro
3. Models consistentes com migrations
4. Login funciona com as credenciais corretas
5. Servidor sobe normalmente
6. `php artisan test` — todos os testes passam
7. Sem `dd()`, `dump()`, `console.log()` esquecidos
8. Sem código morto ou comentado
9. CSRF em todos os formulários
10. Middleware `auth` em todas as rotas `/admin/*`

### Divisão de responsabilidades

| Papel | Responsabilidade |
|-------|-----------------|
| Desenvolvedor (humano) | Regras de negócio, decisões de UX, arquitetura, paleta visual, planejamento das features |
| IA (Claude Code) | Implementação de controllers, models, migrations, Livewire components, views, testes, refatorações |
| IA (validação) | Roda testes, verifica rotas, testa login, confirma ausência de debug code |

---

## Arquitetura do banco de dados

Cinco tabelas em PT-BR, todas com SoftDeletes:

```
usuarios          → Login fixo da Silvia (email + senha bcrypt)
  |
clientes          → Cadastrados pela Silvia (nome + telefone)
  |                         |
trabalhos ────────── trabalho_cliente  → Pivot com token único por par
  |                   (token 64 chars, expiracao, visualizado_em)
  |
fotos             → Vinculadas ao trabalho (drive_arquivo_id, thumbnail, ordem)
```

A tabela `trabalho_cliente` é o coração do sistema: cada linha representa o acesso de um cliente específico a um trabalho específico, com token único para o link e rastreamento de visualização.

---

## Estrutura do projeto

```
├── CLAUDE.md                        # Briefing executivo para IA
├── docs/
│   ├── architecture.md              # Modelagem, rotas, regras de negócio
│   ├── frontend-spec.md             # Especificação visual completa
│   ├── runbooks/features.md         # Roteiro sequencial de features
│   └── decisions/                   # Decisões técnicas e correções
│
├── .claude/
│   ├── settings.json                # test_after_feature, auto_review
│   ├── hooks/pre-commit.md          # Checklist de qualidade (10 itens)
│   └── skills/                      # 9 skills por tecnologia
│
├── tools/
│   ├── scripts/                     # serve.sh, reset-db.sh, run-tests.sh
│   └── prompts/                     # Templates nova-feature.md, corrigir-bug.md
│
├── app/
│   ├── Http/Controllers/            # LoginController, GalleryController, HomeController
│   ├── Models/                      # Usuario, Cliente, Trabalho, TrabalhoCliente, Foto
│   ├── Services/                    # GoogleDriveService
│   └── Livewire/Admin/              # JobList, JobForm, ClientManager, PhotoUploader, ClientList, AlterarSenha
│
├── database/migrations/             # 9 migrations (tabelas PT-BR)
├── resources/views/                 # Blade templates
├── tests/                           # Testes PHPUnit (Feature + Unit)
├── public/css/custom.css            # Paleta rosa com CSS variables
├── php.ini                          # upload_max_filesize=200M
└── storage/app/google/              # credentials.json (.gitignore)
```

---

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
# Editar .env com as credenciais do banco

# Migrations + seed
php artisan migrate --seed

# Storage link (necessário para thumbnails locais)
php artisan storage:link

# Google Drive (opcional)
# Colocar credentials.json em storage/app/google/credentials.json
# Configurar GOOGLE_DRIVE_FOLDER_ID no .env

# Rodar (SEMPRE com php.ini customizado para upload de 200MB)
php8.3 -c php.ini artisan serve --host=127.0.0.1 --port=9000
```

## Requisitos

- PHP 8.3+ com extensões: `gd`, `zip`, `pdo_mysql`, `mbstring`, `curl`, `openssl`
- Composer
- MariaDB 10.11+

## Acesso

### Produção
- URL: [https://silviasouzafotografa.com.br](https://silviasouzafotografa.com.br/)
- Email: `silviasouzafotografa@gmail.com`

### Homologação
- URL: [https://homolog.silviasouzafotografa.com.br](https://homolog.silviasouzafotografa.com.br/)
- Email: `silviasouzafotografa@gmail.com`

### Local
- URL: `http://127.0.0.1:9000`
- Email: `silviasouzafotografa@gmail.com`
- Senha: `123456`

## Testes

```bash
php artisan test
```

---

## Licença

Projeto privado — uso exclusivo Silvia Souza Fotografa.
