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

echo -e "${TEXT_YELLOW}Inicializando containers Docker...${TEXT_NC}"
docker compose up -d

echo -e "${TEXT_YELLOW}Instalando dependências do projeto...${TEXT_NC}"
docker compose exec app composer install

# Gerar a chave do Laravel
echo -e "${TEXT_YELLOW}Gerando chave da aplicação Laravel...${TEXT_NC}"
docker compose exec app php artisan key:generate

# Executar migrações
echo -e "${TEXT_YELLOW}Executando migrações do banco de dados...${TEXT_NC}"
docker compose exec app php artisan migrate

# Instalar e configurar Laravel Passport
docker compose exec app composer require laravel/passport
docker compose exec app php artisan passport:install
docker compose exec app php artisan passport:keys

# Instalar pacotes adicionais necessários
echo -e "${TEXT_YELLOW}Instalando pacotes adicionais...${TEXT_NC}"
docker compose exec app composer require spatie/laravel-permission
docker compose exec app composer require laravel/cashier

# Executar comandos adicionais do Laravel
echo -e "${TEXT_YELLOW}Executando comandos adicionais...${TEXT_NC}"
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan config:clear
docker compose exec app php artisan view:clear
docker compose exec app php artisan route:clear

echo -e "${TEXT_GREEN}Setup concluído com sucesso!${TEXT_NC}"
echo -e "${TEXT_YELLOW}A aplicação Laravel está disponível em: http://localhost:8000${TEXT_NC}" 