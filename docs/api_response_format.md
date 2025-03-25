# Formato de Respostas da API

## Estrutura Padrão

Todas as respostas da API seguem um formato JSON padronizado com a seguinte estrutura:

```json
{
  "success": true|false,
  "message": "Mensagem descritiva sobre o resultado da operação",
  ...outros dados específicos do endpoint...
}
```

## Campos Obrigatórios

1. **success**: Valor booleano que indica se a requisição foi bem-sucedida ou não
   - `true` para requisições bem-sucedidas (códigos 2xx)
   - `false` para requisições que resultaram em erro (códigos 4xx e 5xx)

2. **message**: Uma mensagem descritiva sobre o resultado da operação
   - Para sucesso: "Operação realizada com sucesso." ou mensagem específica
   - Para erro: Descrição detalhada do erro ocorrido

## Exemplos

### Sucesso

```json
{
  "success": true,
  "message": "Usuário criado com sucesso",
  "user": {
    "id": 1,
    "name": "João Silva",
    "email": "joao@example.com",
    "created_at": "2025-03-18T12:00:00.000000Z",
    "updated_at": "2025-03-18T12:00:00.000000Z"
  }
}
```

### Erro de Validação

```json
{
  "success": false,
  "message": "Os dados fornecidos são inválidos.",
  "errors": {
    "email": [
      "O campo email é obrigatório."
    ],
    "password": [
      "A senha deve ter pelo menos 8 caracteres."
    ]
  }
}
```

### Erro de Autenticação

```json
{
  "success": false,
  "message": "Não autenticado ou token inválido."
}
```

### Erro de Permissão

```json
{
  "success": false,
  "message": "Você não tem permissão para realizar esta ação."
}
```

### Recurso Não Encontrado

```json
{
  "success": false,
  "message": "Recurso user não encontrado."
}
```

### Erro de Servidor

```json
{
  "success": false,
  "message": "Erro interno do servidor.",
  "error": "Detalhes do erro (apenas em ambiente de desenvolvimento)"
}
```

## Códigos de Status HTTP

A API utiliza os seguintes códigos de status HTTP:

- **200 OK**: Requisição processada com sucesso
- **201 Created**: Recurso criado com sucesso
- **400 Bad Request**: Requisição com parâmetros inválidos
- **401 Unauthorized**: Autenticação necessária ou falha na autenticação
- **403 Forbidden**: Usuário não tem permissão para acessar o recurso
- **404 Not Found**: Recurso não encontrado
- **405 Method Not Allowed**: Método HTTP não permitido para a rota
- **409 Conflict**: Conflito ao processar a requisição (ex: recurso duplicado)
- **422 Unprocessable Entity**: Erro de validação de dados
- **500 Internal Server Error**: Erro interno do servidor 