# Pre-commit — Checklist obrigatório

Antes de finalizar qualquer feature:

1. `php artisan route:list` → sem rotas duplicadas ou quebradas
2. Todas as views Blade compilam sem erro
3. Models consistentes com migrations ($table, $fillable, relacionamentos)
4. Login funciona: silviasouzafotografa@gmail.com / 123456
5. Servidor roda: `php8.3 -c php.ini artisan serve --port=9000`
6. `php artisan test` → todos os testes passam
7. Sem dd(), dump(), console.log() esquecidos
8. Sem código morto ou comentado
9. CSRF presente em todos os forms
10. Middleware auth em todas as rotas /admin/*
