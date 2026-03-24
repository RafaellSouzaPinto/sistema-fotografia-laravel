#!/bin/bash
# Limpa todos os caches do Laravel
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
echo "✅ Cache limpo."
