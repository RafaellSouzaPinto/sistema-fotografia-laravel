#!/bin/bash
# Reseta o banco completo (migrate fresh + seed)
echo "⚠️  Resetando banco de dados..."
php artisan migrate:fresh --seed
php artisan storage:link
echo "✅ Banco resetado e seed rodado."
