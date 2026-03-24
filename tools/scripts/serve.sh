#!/bin/bash
# Sobe o servidor com php.ini customizado
echo "🚀 Iniciando servidor na porta 9000..."
php8.3 -c php.ini artisan serve --host=127.0.0.1 --port=9000
