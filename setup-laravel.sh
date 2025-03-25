#!/bin/bash

# Cores do texto
TEXT_GREEN='\033[0;32m'
TEXT_YELLOW='\033[1;33m'
TEXT_RED='\033[0;31m'
TEXT_NC='\033[0m' # No Color

echo -e "${TEXT_GREEN}Iniciando setup do projeto Laravel...${TEXT_NC}"

# Verificar se o Docker está instalado
if ! command -v docker &> /dev/null; then
    echo -e "${TEXT_RED}Docker não encontrado. Por favor, instale o Docker antes de continuar.${TEXT_NC}"
    exit 1
fi

# Verificar se o Docker Compose está instalado
if ! command -v docker compose &> /dev/null; then
    echo -e "${TEXT_RED}Docker Compose não encontrado. Por favor, instale o Docker Compose antes de continuar.${TEXT_NC}"
    exit 1
fi

# Parar containers existentes se estiverem rodando
if [ "$(docker ps -q -f name=streaming-api)" ]; then
    echo -e "${TEXT_YELLOW}Parando containers existentes...${TEXT_NC}"
    docker compose down
fi

echo -e "${TEXT_YELLOW}Inicializando containers Docker...${TEXT_NC}"
docker compose up -d --build

# Verificar se o diretório vendor existe
if [ ! -d "vendor" ]; then
    echo -e "${TEXT_YELLOW}Instalando dependências do projeto...${TEXT_NC}"
    docker compose exec app composer install
else
    echo -e "${TEXT_YELLOW}Atualizando dependências do projeto...${TEXT_NC}"
    docker compose exec app composer update
fi

# Verificar se o arquivo .env existe
if [ ! -f ".env" ]; then
    echo -e "${TEXT_YELLOW}Criando arquivo .env...${TEXT_NC}"
    cp .env.example .env
    # Atualizar configurações do banco de dados no .env
    sed -i 's/DB_HOST=127.0.0.1/DB_HOST=db/g' .env
    sed -i 's/DB_DATABASE=laravel/DB_DATABASE=streaming_api/g' .env
    sed -i 's/DB_USERNAME=root/DB_USERNAME=streaming_user/g' .env
    sed -i 's/DB_PASSWORD=/DB_PASSWORD=streaming_password/g' .env
fi

# Gerar a chave do Laravel se não existir
if ! grep -q "APP_KEY=" .env || grep -q "APP_KEY=base64:.*" .env; then
    echo -e "${TEXT_YELLOW}Gerando chave da aplicação Laravel...${TEXT_NC}"
    docker compose exec app php artisan key:generate
fi

# Executar migrações
echo -e "${TEXT_YELLOW}Executando migrações do banco de dados...${TEXT_NC}"
docker compose exec app php artisan migrate

# Instalar pacotes necessários
echo -e "${TEXT_YELLOW}Instalando pacotes adicionais...${TEXT_NC}"
docker compose exec app composer require laravel/passport
docker compose exec app composer require spatie/laravel-permission
docker compose exec app composer require darkaonline/l5-swagger
docker compose exec app composer require stripe/stripe-php

# Configurar Laravel Passport
echo -e "${TEXT_YELLOW}Configurando Laravel Passport...${TEXT_NC}"
docker compose exec app php artisan passport:install
docker compose exec app php artisan passport:keys

# Configurar permissões de diretórios
echo -e "${TEXT_YELLOW}Configurando permissões de diretórios...${TEXT_NC}"
docker compose exec app chmod -R 777 storage bootstrap/cache

# Limpar cache
echo -e "${TEXT_YELLOW}Limpando cache...${TEXT_NC}"
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:clear
docker compose exec app php artisan view:clear

echo -e "${TEXT_GREEN}Setup concluído com sucesso!${TEXT_NC}"
echo -e "${TEXT_YELLOW}A aplicação Laravel está disponível em: http://localhost:8000${TEXT_NC}" 