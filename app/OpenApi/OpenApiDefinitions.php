<?php

namespace App\OpenApi;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="API de Streaming de Cursos",
 *     description="API para gerenciamento de cursos, usuários e assinaturas. 
 *
 * ## Autenticação
 * Para acessar rotas protegidas, você precisa autenticar-se:
 * 1. Clique no botão Authorize (cadeado) no topo da página
 * 2. Selecione oauth2
 * 3. Informe: 
 *    - username: admin@example.com
 *    - password: password
 *    - client_id: 1 (já preenchido)
 *    - client_secret: jCjgGb46hKS4HrdjxL8ATuedNRteuV9O7zNLOPEl (já preenchido)
 * 4. Clique em Authorize e depois em Close
 * 
 * Após isso, todas as requisições incluirão o token automaticamente.
 * ",
 *     @OA\Contact(
 *         email="contato@example.com",
 *         name="Suporte API"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Servidor Local - Base Path Correto"
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Servidor Local (Legacy)"
 * )
 * 
 * @OA\ExternalDocumentation(
 *     description="Documentação adicional",
 *     url="https://github.com/sua-organizacao/seu-repositorio"
 * )
 * 
 * @OA\Components(
 *     @OA\SecurityScheme(
 *         securityScheme="bearerAuth",
 *         type="http",
 *         scheme="bearer",
 *         bearerFormat="JWT",
 *         description="Use o token obtido no endpoint /api/login. Formato: Bearer {seu_token}"
 *     ),
 *     @OA\SecurityScheme(
 *         securityScheme="oauth2",
 *         type="oauth2",
 *         description="Autenticação OAuth2 usando fluxo Password Grant",
 *         flows={
 *             @OA\Flow(
 *                 flow="password",
 *                 tokenUrl="http://localhost:8000/oauth/token",
 *                 scopes={}
 *             )
 *         }
 *     )
 * )
 * 
 * @OA\Tag(
 *     name="Autenticação",
 *     description="Endpoints para autenticação, registro e gerenciamento de sessão"
 * )
 * 
 * @OA\Tag(
 *     name="Usuários",
 *     description="Gerenciamento de usuários e perfis"
 * )
 * 
 * @OA\Tag(
 *     name="Conteúdos",
 *     description="Endpoints para manipulação de conteúdos e cursos"
 * )
 * 
 * @OA\Tag(
 *     name="Planos",
 *     description="Gerenciamento de planos de assinatura"
 * )
 * 
 * @OA\Tag(
 *     name="Admin",
 *     description="Endpoints administrativos restritos"
 * )
 * 
 * @OA\Tag(
 *     name="Sandbox",
 *     description="Ambiente de desenvolvimento para testes com a API do Stripe"
 * )
 */
class OpenApiDefinitions
{
    // Esta classe serve apenas para conter as anotações OpenAPI
} 