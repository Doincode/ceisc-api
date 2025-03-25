# Streaming API

API de gerenciamento de assinaturas com processamento assíncrono de emails usando Redis.

## Requisitos

- Docker
- Docker Compose
- Git

## Configuração Inicial

1. Clone o repositório:
```bash
git clone https://github.com/seu-usuario/streaming-api.git
cd streaming-api
```

2. Copie o arquivo de ambiente:
```bash
cp .env.example .env
```

3. Gere a chave da aplicação:
```bash
docker compose exec app php artisan key:generate
```

4. Configure as variáveis de ambiente no arquivo `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=streaming_api
DB_USERNAME=streaming_user
DB_PASSWORD=streaming_password

QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_QUEUE=subscriptions

MAIL_MAILER=log
```

5. Inicie os containers:
```bash
docker compose up -d
```

6. Execute as migrações do banco de dados:
```bash
docker compose exec app php artisan migrate
```

7. Execute os seeders para criar dados iniciais:
```bash
docker compose exec app php artisan db:seed
```

## Iniciando os Serviços

1. Inicie o worker do Redis para processar as filas:
```bash
docker compose exec -d app php artisan queue:work redis --queue=subscriptions --tries=3 --max-jobs=50
```

2. Inicie o scheduler para processar as tarefas agendadas:
```bash
docker compose exec -d app php artisan schedule:work
```

## Testando a Aplicação

1. Crie uma assinatura de teste:
```bash
curl -X POST http://localhost:8000/api/sandbox/create-subscription \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer seu-token" \
  -d '{
    "plan_id": 1,
    "payment_method": "credit_card",
    "payment_details": {
      "card_number": "4111111111111111",
      "card_holder": "Test User",
      "card_expiry": "12/25",
      "card_cvv": "123"
    }
  }'
```

2. Verifique os logs para ver os emails enviados:
```bash
docker compose exec app tail -f storage/logs/laravel.log
```

## Comandos Úteis

- Verificar status dos containers:
```bash
docker compose ps
```

- Ver logs da aplicação:
```bash
docker compose logs -f app
```

- Acessar o shell da aplicação:
```bash
docker compose exec app bash
```

- Parar os containers:
```bash
docker compose down
```

## Estrutura do Projeto

```
streaming-api/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       ├── CheckExpiredSubscriptionsCommand.php
│   │       └── ProcessNewSubscriptionsCommand.php
│   ├── Jobs/
│   │   ├── ProcessExpiredSubscriptions.php
│   │   └── ProcessNewSubscriptions.php
│   └── Notifications/
│       ├── SubscriptionExpiredNotification.php
│       └── SubscriptionPaymentConfirmed.php
├── resources/
│   └── views/
│       └── emails/
│           └── subscription/
│               ├── expired.blade.php
│               └── payment-confirmed.blade.php
└── docker-compose.yml
```

## Funcionalidades

- Gerenciamento de assinaturas
- Processamento assíncrono de emails usando Redis
- Verificação automática de assinaturas expiradas
- Envio de emails de confirmação para novas assinaturas
- Ambiente de sandbox para testes

## Contribuindo

1. Faça um fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.
