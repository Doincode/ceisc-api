# Endpoints da API

Esta documentação descreve os principais endpoints da API com exemplos de requisição e resposta no formato JSON padronizado.

## Autenticação

### Login (Obter Token OAuth)

```http
POST /oauth/token
Content-Type: application/json

{
  "grant_type": "password",
  "client_id": "1",
  "client_secret": "XQAn46nH8RHaKSES2LAsAeMzrAadCYzBnQrDxwgC",
  "username": "teste@example.com",
  "password": "senha123",
  "scope": ""
}
```

Resposta:

```json
{
  "token_type": "Bearer",
  "expires_in": 1296000,
  "access_token": "eyJ0eXAiOiJKV1QiLCJ...",
  "refresh_token": "def5020..."
}
```

### Verificar Papel/Permissões

```http
GET /api/user/role
Authorization: Bearer {seu_token}
```

Resposta:

```json
{
  "success": true,
  "message": "Operação realizada com sucesso.",
  "user": {
    "id": 1,
    "name": "Usuário Teste",
    "email": "teste@example.com"
  },
  "roles": [
    "admin"
  ],
  "permissions": [
    "view contents",
    "create contents",
    "edit contents",
    "delete contents",
    "view users",
    "create users",
    "edit users",
    "delete users"
  ]
}
```

## Usuários

### Listar Usuários (Admin)

```http
GET /api/admin/users
Authorization: Bearer {seu_token}
```

Resposta:

```json
{
  "success": true,
  "message": "Operação realizada com sucesso.",
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "name": "Usuário Teste",
      "email": "teste@example.com",
      "role": "admin",
      "permissions": ["create_content", "edit_content", "delete_content", "manage_users"],
      "created_at": "2025-03-18T00:00:00.000000Z"
    }
  ],
  "total": 1
}
```

### Atualizar Papel de Usuário

```http
PUT /api/admin/users/2/role
Authorization: Bearer {seu_token}
Content-Type: application/json

{
  "role": "manager"
}
```

Resposta:

```json
{
  "success": true,
  "message": "Papel atualizado com sucesso",
  "user": {
    "id": 2,
    "name": "Outro Usuário",
    "email": "outro@example.com",
    "role": "manager"
  }
}
```

## Planos

### Listar Planos

```http
GET /api/plans
```

Resposta:

```json
{
  "success": true,
  "message": "Operação realizada com sucesso.",
  "plans": [
    {
      "id": 1,
      "name": "Plano Básico",
      "description": "Acesso a conteúdo básico",
      "price": 29.90,
      "is_active": true,
      "features": ["Acesso a videoaulas", "Material em PDF"]
    },
    {
      "id": 2,
      "name": "Plano Premium",
      "description": "Acesso a todo conteúdo",
      "price": 49.90,
      "is_active": true,
      "features": ["Acesso a videoaulas", "Material em PDF", "Simulados", "Suporte"]
    }
  ]
}
```

## Assinaturas

### Criar Assinatura

```http
POST /api/payments/subscribe
Authorization: Bearer {seu_token}
Content-Type: application/json

{
  "plan_id": 1,
  "payment_method": "pm_card_visa"
}
```

Resposta:

```json
{
  "success": true,
  "message": "Assinatura criada com sucesso",
  "subscription": {
    "id": 1,
    "user_id": 1,
    "plan_id": 1,
    "status": "active",
    "start_date": "2025-03-18T00:00:00.000000Z",
    "end_date": "2025-04-18T00:00:00.000000Z",
    "plan": {
      "id": 1,
      "name": "Plano Básico",
      "price": 29.90
    }
  }
}
```

### Listar Assinaturas

```http
GET /api/subscriptions
Authorization: Bearer {seu_token}
```

Resposta:

```json
{
  "success": true,
  "message": "Operação realizada com sucesso.",
  "subscriptions": [
    {
      "id": 1,
      "user_id": 1,
      "plan_id": 1,
      "status": "active",
      "start_date": "2025-03-18T00:00:00.000000Z",
      "end_date": "2025-04-18T00:00:00.000000Z",
      "plan": {
        "id": 1,
        "name": "Plano Básico",
        "price": 29.90
      }
    }
  ]
}
```

## Conteúdos

### Listar Conteúdos

```http
GET /api/contents
```

Resposta:

```json
{
  "success": true,
  "message": "Operação realizada com sucesso.",
  "contents": [
    {
      "id": 1,
      "title": "Introdução ao Direito Civil",
      "description": "Aula introdutória sobre Direito Civil",
      "type": "video",
      "url": "https://example.com/videos/intro-direito-civil.mp4",
      "featured": true,
      "created_at": "2025-03-18T00:00:00.000000Z"
    },
    {
      "id": 2,
      "title": "Direito Constitucional",
      "description": "Apostila completa de Direito Constitucional",
      "type": "pdf",
      "url": "https://example.com/pdfs/direito-constitucional.pdf",
      "featured": false,
      "created_at": "2025-03-18T00:00:00.000000Z"
    }
  ]
}
```

## Tratamento de Erros

### Erro de Validação

```http
POST /api/register
Content-Type: application/json

{
  "name": "Teste",
  "email": "email-invalido"
}
```

Resposta:

```json
{
  "success": false,
  "message": "Os dados fornecidos são inválidos.",
  "errors": {
    "email": [
      "O campo email deve ser um endereço de e-mail válido."
    ],
    "password": [
      "O campo password é obrigatório."
    ]
  }
}
```

### Erro de Autenticação

```http
GET /api/user/role
Authorization: Bearer token-invalido
```

Resposta:

```json
{
  "success": false,
  "message": "Não autenticado ou token inválido."
}
```

### Recurso Não Encontrado

```http
GET /api/subscriptions/999
Authorization: Bearer {seu_token}
```

Resposta:

```json
{
  "success": false,
  "message": "Recurso subscription não encontrado."
}
``` 