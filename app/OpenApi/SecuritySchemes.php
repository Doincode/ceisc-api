<?php

namespace App\OpenApi;

/**
 * @OA\SecurityScheme(
 *      securityScheme="bearerAuth",
 *      type="http",
 *      scheme="bearer",
 *      bearerFormat="JWT",
 *      description="Autenticação usando JWT Bearer Token. Obtenha seu token através do endpoint /login."
 * )
 */

/**
 * @OA\SecurityScheme(
 *      securityScheme="oauth2",
 *      type="oauth2",
 *      description="Autenticação OAuth2 usando Password Grant Flow. Necessário para acessar as rotas protegidas.",
 *      flows={
 *          @OA\Flow(
 *              tokenUrl="/oauth/token",
 *              flow="password",
 *              refreshUrl="/oauth/token/refresh",
 *              scopes={}
 *          )
 *      }
 * )
 */ 