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
