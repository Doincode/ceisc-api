<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\SandboxController;
use App\Http\Middleware\ForceJsonResponse;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Rota de teste para verificar o formato JSON
////Route::get('/test-json', function () {
////    return response()->json([
////        'data' => 'Teste de resposta JSON',
////        'timestamp' => now()->toDateTimeString()
////    ]);
////});

// Rotas de sistema e diagnóstico
//Route::prefix('system')->group(function () {
//    Route::get('/status', [TestController::class, 'status']);
//    Route::prefix('test')->group(function () {
//        Route::get('/database', [TestController::class, 'checkDatabase']);
//        Route::get('/redis', [TestController::class, 'checkRedis']);
//    });
//});


// Autenticação - rotas públicas
Route::middleware([ForceJsonResponse::class])->group(function () {
    Route::post('/login', [UserController::class, 'login']);
    Route::post('/register', [UserController::class, 'register']);
});

// Autenticação - rotas protegidas
Route::middleware(['auth:api', ForceJsonResponse::class])->group(function () {
    Route::post('/logout', [UserController::class, 'logout']);
    Route::get('/user/role', [UserController::class, 'role']);
});

// Planos de assinatura públicos
Route::prefix('plans')->group(function () {
    Route::get('/', [PlanController::class, 'index']);
    Route::get('/{plan}', [PlanController::class, 'show']);
});

// Rotas de usuário
Route::middleware(['auth:api'])->prefix('user')->group(function () {
    Route::get('/', function (Request $request) {
        return $request->user();
    });
});

// Rotas de conteúdo que exigem assinatura ativa
Route::middleware(['auth:api', \App\Http\Middleware\CheckActiveSubscription::class])->group(function () {
    // Visualização de conteúdo
    Route::get('/contents', [ContentController::class, 'index']);
    
    // Acesso a conteúdo premium
    Route::get('/premium-content', [ContentController::class, 'premium']);
});

// Assinaturas do usuário (desativado - apenas para ambiente de produção)
/*
Route::middleware(['auth:api'])->prefix('subscriptions')->group(function () {
    Route::get('/', [SubscriptionController::class, 'index']);
    Route::post('/', [SubscriptionController::class, 'store']);
    Route::get('/{subscription}', [SubscriptionController::class, 'show']);
    Route::put('/{subscription}', [SubscriptionController::class, 'update']);
    Route::delete('/{subscription}', [SubscriptionController::class, 'cancel']);
});
*/

// Rotas de administrador
Route::middleware(['auth:api', \App\Http\Middleware\CheckAdminRole::class])->prefix('admin')->group(function () {
    // Gerenciamento de usuários
    Route::prefix('users')->group(function () {
        Route::get('/', [UserManagementController::class, 'index']);
        Route::put('/{user}', [UserController::class, 'update']);
        Route::delete('/{user}', [UserController::class, 'destroy']);
    });
    
    // Gerenciamento de conteúdo
    Route::prefix('contents')->group(function () {
        Route::post('/', [ContentController::class, 'store']);
        Route::put('/{content}', [ContentController::class, 'update']);
        Route::delete('/{content}', [ContentController::class, 'destroy']);
    });
    
    // Gerenciamento de planos
    Route::prefix('plans')->group(function () {
        Route::get('/', [PlanController::class, 'index'])->defaults('all', true);
        Route::post('/', [PlanController::class, 'store']);
        Route::put('/{plan}', [PlanController::class, 'update']);
        Route::delete('/{plan}', [PlanController::class, 'destroy']);
    });
    
    // Gerenciamento de assinaturas (desativado - apenas para ambiente de produção)
    /*
    Route::prefix('subscriptions')->group(function () {
        Route::get('/', [SubscriptionController::class, 'all']);
        Route::post('/{subscription}/renew', [SubscriptionController::class, 'renew']);
    });
    */
    
    // Gerenciamento de usuários
    Route::get('/users', [UserManagementController::class, 'index']);
    Route::put('/users/{user}/role', [UserManagementController::class, 'updateRole']);
    Route::put('/users/{user}/permissions', [UserManagementController::class, 'updatePermissions']);
    Route::post('/users/{user}/promote-to-admin', [UserManagementController::class, 'promoteToAdmin']);
});

// Webhook do Stripe
//Route::post('/stripe/webhook', [WebhookController::class, 'handleWebhook']);

// Rotas para o sandbox do Stripe
Route::prefix('sandbox')->middleware(['auth:api'])->group(function () {
    Route::get('/', [SandboxController::class, 'index']);
    Route::get('/test-connection', [SandboxController::class, 'testConnection']);
    Route::post('/create-customer', [SandboxController::class, 'createCustomer']);
    Route::post('/create-payment-method', [SandboxController::class, 'createPaymentMethod']);
    Route::post('/create-payment-intent', [SandboxController::class, 'createPaymentIntent']);
    Route::post('/create-subscription', [SandboxController::class, 'createSubscription']);
    Route::post('/validate-subscription-request', [SandboxController::class, 'validateSubscriptionRequest']);
    Route::get('/sync-plan/{plan}', [SandboxController::class, 'syncPlan'])->name('sandbox.sync-plan');
    Route::get('/payment-methods', [SandboxController::class, 'listPaymentMethods']);
    Route::get('/invoices', [SandboxController::class, 'listInvoices']);
    Route::get('/plans', [SandboxController::class, 'listPlans']);
    Route::post('/simulate-expiration', [SandboxController::class, 'simulateExpiration']);
    Route::post('/simulate-current-expiration', [SandboxController::class, 'simulateCurrentSubscriptionExpiration']);
    Route::get('/test-tokens', [SandboxController::class, 'testTokens']);
    Route::post('/test-expired-subscriptions', [SandboxController::class, 'testExpiredSubscriptionsProcessing']);
});

// Rotas para assinaturas
Route::prefix('subscription')->middleware(['auth:api'])->group(function () {
    Route::get('/', [SubscriptionController::class, 'index']);
    Route::get('/active', [SubscriptionController::class, 'getActiveSubscription']);
    Route::get('/check-plans', [SubscriptionController::class, 'checkAvailablePlans']);
    Route::get('/check-expiration', [SubscriptionController::class, 'checkPlanExpiration']);
    Route::post('/', [SubscriptionController::class, 'store']);
    Route::get('/{subscription}', [SubscriptionController::class, 'show']);
    Route::put('/{subscription}', [SubscriptionController::class, 'update']);
    Route::delete('/{subscription}', [SubscriptionController::class, 'destroy']);
});
