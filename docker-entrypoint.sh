#!/bin/bash

# Esperar pelo MySQL
echo "Aguardando MySQL..."
while ! mysqladmin ping -h"db" -P"3306" --silent; do
    sleep 1
done
echo "MySQL está pronto!"

# Instalar dependências do Composer
if [ ! -d "vendor" ]; then
    echo "Instalando dependências do Composer..."
    composer install --no-interaction --no-progress
fi

# Gerar chave da aplicação se não existir
if [ ! -f ".env" ]; then
    echo "Criando arquivo .env..."
    cp .env.example .env
fi

if [ -z "$(grep APP_KEY .env)" ]; then
    echo "Gerando chave da aplicação..."
    php artisan key:generate
fi

# Executar migrações
echo "Executando migrações..."
php artisan migrate --force

# Configurar permissões
echo "Configurando permissões..."
chmod -R 777 storage bootstrap/cache

# Iniciar o PHP-FPM
echo "Iniciando PHP-FPM..."
php-fpm 