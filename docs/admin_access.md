# Acesso ao Usuário Administrador

## Credenciais de Acesso

O sistema cria automaticamente um usuário administrador durante a execução do seeder. As credenciais são:

**Email:** teste@example.com  
**Senha:** senha123  
**Papel:** admin

## Autenticação OAuth2

### Informações do Cliente OAuth

O sistema utiliza Laravel Passport para autenticação OAuth2. Um cliente do tipo "Password Grant" foi criado com as seguintes informações:

**Client ID:** 1  
**Client Secret:** XQAn46nH8RHaKSES2LAsAeMzrAadCYzBnQrDxwgC

### Como Obter um Token de Acesso

Você pode obter um token de acesso usando o método "Password Grant" com uma requisição POST para o endpoint `/oauth/token` com os seguintes parâmetros:

```json
{
  "grant_type": "password",
  "client_id": 1,
  "client_secret": "XQAn46nH8RHaKSES2LAsAeMzrAadCYzBnQrDxwgC",
  "username": "teste@example.com",
  "password": "senha123",
  "scope": ""
}
```

### Usando o Token

Após obter o token, inclua-o no cabeçalho das requisições que exigem autenticação:

```
Authorization: Bearer {seu_token_aqui}
```

## Endpoints Administrativos

Os endpoints a seguir exigem autenticação de administrador:

- GET `/admin/users` - Listar todos os usuários
- PUT `/admin/users/{user}/role` - Atualizar papel do usuário
- PUT `/admin/users/{user}/permissions` - Atualizar permissões do usuário
- POST `/admin/users/{user}/promote-to-admin` - Promover usuário a administrador

## Verificação de Papel

Use o endpoint `/user/role` para verificar o papel e as permissões do usuário autenticado:

```
GET /user/role
Header: Authorization: Bearer {seu_token_aqui}
```

## Reset de Banco de Dados

Para limpar o banco de dados e recriar todas as tabelas com os seeders:

```bash
docker compose exec app php artisan migrate:fresh --seed
``` 