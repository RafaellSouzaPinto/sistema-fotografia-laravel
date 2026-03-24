# Testes

## Filosofia
- Testar cada feature após criar
- Testar o fluxo completo após todas as features
- Testes em português nos nomes (test_usuario_pode_fazer_login)
- Usar RefreshDatabase em todos os testes

## Ferramentas
- PHPUnit (já vem com Laravel)
- php artisan test
- Factories para gerar dados fake
- actingAs() para simular login

## O que testar por feature
Cada feature deve ter no mínimo:
1. Teste de rota (status code correto, redirect se não autenticado)
2. Teste de validação (campos obrigatórios, formatos)
3. Teste de lógica (criou no banco? deletou? atualizou?)
4. Teste de permissão (rota admin sem login redireciona pra /login, galeria pública funciona sem login)
