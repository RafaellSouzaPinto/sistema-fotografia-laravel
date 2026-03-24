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
