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
