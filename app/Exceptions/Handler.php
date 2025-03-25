<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Throwable $e)
    {
        // Forçar todas as respostas da API como JSON
        if ($request->is('api/*') || $request->wantsJson()) {
            if ($e instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Os dados fornecidos são inválidos.',
                    'errors' => $e->errors(),
                ], 422);
            }

            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não autenticado ou token inválido.',
                ], 401);
            }

            if ($e instanceof AuthorizationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para realizar esta ação.',
                ], 403);
            }

            if ($e instanceof ModelNotFoundException) {
                $modelName = strtolower(class_basename($e->getModel()));
                return response()->json([
                    'success' => false,
                    'message' => "Recurso {$modelName} não encontrado.",
                ], 404);
            }

            if ($e instanceof NotFoundHttpException) {
                return response()->json([
                    'success' => false,
                    'message' => 'A URL solicitada não foi encontrada.',
                ], 404);
            }

            // Tratar especificamente erros do pacote de permissões
            if (strpos($e->getMessage(), 'spatie') !== false || 
                strpos($e->getFile(), 'spatie/laravel-permission') !== false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro no sistema de permissões.',
                    'error' => config('app.debug') ? $e->getMessage() : 'Contate o administrador do sistema.',
                ], 500);
            }

            if ($e instanceof MethodNotAllowedHttpException) {
                return response()->json([
                    'success' => false,
                    'message' => 'O método HTTP não é permitido para esta rota.',
                ], 405);
            }

            if ($e instanceof HttpException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Erro de HTTP.',
                ], $e->getStatusCode());
            }

            if ($e instanceof QueryException) {
                $errorCode = $e->errorInfo[1] ?? null;
                
                if ($errorCode == 1062) { // Erro de chave duplicada no MySQL
                    return response()->json([
                        'success' => false,
                        'message' => 'Registro duplicado.',
                        'error' => config('app.debug') ? $e->getMessage() : 'O registro já existe.',
                    ], 409);
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Erro de banco de dados.',
                    'error' => config('app.debug') ? $e->getMessage() : 'Erro ao processar o banco de dados.',
                ], 500);
            }

            // Captura qualquer outra exceção
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor.',
                'error' => config('app.debug') ? $e->getMessage() : 'Entre em contato com o administrador.',
            ], 500);
        }

        return parent::render($request, $e);
    }
} 