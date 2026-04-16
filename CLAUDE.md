# CLAUDE.md — Silvia Souza Fotografa

## Sobre o projeto
Sistema web de gestão de entregas fotográficas. Uma fotógrafa profissional (Silvia, 55+ anos, 40 anos de experiência) cadastra trabalhos (prévias ou completos), sobe fotos para o Google Drive via API, adiciona clientes por telefone e gera links únicos com token. O cliente recebe o link pelo WhatsApp, acessa sem login, visualiza as fotos em galeria e pode baixar individualmente ou todas em ZIP.

## Stack
- PHP 8.3 + Laravel 11
- Livewire 3 + Alpine.js 3
- MariaDB 10.11 (colunas em PT-BR: nome, telefone, titulo, etc.)
- Bootstrap 5.3.2 + Bootstrap Icons (CDN, sem npm)
- Google Fonts: Playfair Display + Inter
- Google Drive API via Service Account
- Storage local como fallback

## Como rodar
```bash
# Servidor (SEMPRE com php.ini customizado para aceitar upload 200MB)
php8.3 -c php.ini artisan serve --host=127.0.0.1 --port=9000

# Migrations + seed
php artisan migrate --seed

# Limpar cache
php artisan cache:clear && php artisan config:clear && php artisan view:clear

# Storage link (necessário para thumbnails locais)
php artisan storage:link

# Rodar testes
php artisan test
```

## Login
- Email: silviasouzafotografa@gmail.com
- Senha: 123456
- URL: http://127.0.0.1:9000/login

## Documentação — leia ANTES de codar
| Arquivo | O que contém |
|---------|-------------|
| `docs/architecture.md` | Modelagem de banco, rotas, regras de negócio, relacionamentos |
| `docs/frontend-spec.md` | Paleta de cores, tipografia, componentes CSS, layout de cada tela |
| `docs/runbooks/features.md` | Cada feature detalhada com código PHP/Blade/Livewire |
| `docs/decisions/` | Correções e decisões técnicas já aplicadas |

## Skills — leia ANTES de codar
| Skill | Arquivo |
|-------|---------|
| PHP 8.3 | `.claude/skills/php/SKILL.md` |
| Laravel 11 | `.claude/skills/laravel/SKILL.md` |
| Livewire 3 | `.claude/skills/livewire/SKILL.md` |
| Bootstrap 5 + CSS | `.claude/skills/bootstrap/SKILL.md` |
| Google Drive API | `.claude/skills/google-drive/SKILL.md` |
| Code Review | `.claude/skills/code-review/SKILL.md` |
| Testes | `.claude/skills/testing/SKILL.md` |

## Hooks
| Hook | Arquivo |
|------|---------|
| Pre-commit | `.claude/hooks/pre-commit.md` |

## Banco de dados — tabelas
| Tabela | Descrição |
|--------|-----------|
| `usuarios` | Login fixo (só Silvia). Colunas: nome, email, senha |
| `clientes` | Cadastrados pela Silvia. Colunas: nome, telefone |
| `trabalhos` | Prévias e completos. Colunas: titulo, data_trabalho, tipo, status, drive_pasta_id |
| `trabalho_cliente` | Pivot com token único por par trabalho+cliente |
| `fotos` | Vinculadas a trabalho. Colunas: nome_arquivo, drive_arquivo_id, drive_thumbnail, tamanho_bytes, ordem |

## Regras críticas
1. NUNCA usar `Auth::attempt()` — a coluna é `senha`, não `password`. Usar `Hash::check()` + `Auth::login()`
2. NUNCA alterar migrations já rodadas — criar nova migration
3. NUNCA subir `credentials.json` pro git
4. Colunas em PT-BR, mensagens em PT-BR, confirmações em PT-BR
5. Público alvo: adultos/idosos — botões grandes, textos claros, sem jargão
6. Toda ação destrutiva pede confirmação com `wire:confirm`
7. Toda ação bem-sucedida mostra toast de feedback
8. Upload aceita até 200MB por arquivo (php.ini customizado)
