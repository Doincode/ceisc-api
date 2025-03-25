<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\Stripe;
use Stripe\StripeClient;

class SandboxController extends Controller
{
    protected $stripeService;
    protected $stripeClient;
    
    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
        $this->stripeClient = new StripeClient(config('cashier.secret'));
        
        // Definir a chave API do Stripe
        Stripe::setApiKey(config('cashier.secret'));
    }
    
    /**
     * Exibe a página inicial do sandbox
     * 
     * @OA\Get(
     *     path="/sandbox",
     *     summary="Página inicial do sandbox do Stripe",
     *     description="Retorna informações sobre o ambiente sandbox do Stripe para desenvolvimento e testes (não usar em produção)",
     *     operationId="sandboxIndex",
     *     tags={"Sandbox"},
     *     security={{"oauth2":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Informações do sandbox",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Bem-vindo ao ambiente sandbox do Stripe"),
     *             @OA\Property(property="stripe_key", type="string", example="pk_test_XXXXXXXXXXXXXXXXXXXXXXXX"),
     *             @OA\Property(
     *                 property="test_cards",
     *                 type="object",
     *                 @OA\Property(property="success", type="string", example="4242 4242 4242 4242"),
     *                 @OA\Property(property="requires_auth", type="string", example="4000 0025 0000 3155"),
     *                 @OA\Property(property="declined", type="string", example="4000 0000 0000 0002")
     *             ),
     *             @OA\Property(
     *                 property="endpoints",
     *                 type="object",
     *                 @OA\Property(property="create_customer", type="string", example="/api/sandbox/create-customer"),
     *                 @OA\Property(property="create_payment_method", type="string", example="/api/sandbox/create-payment-method"),
     *                 @OA\Property(property="create_payment_intent", type="string", example="/api/sandbox/create-payment-intent"),
     *                 @OA\Property(property="create_subscription", type="string", example="/api/sandbox/create-subscription"),
     *                 @OA\Property(property="sync_plan", type="string", example="/api/sandbox/sync-plan/{plan}"),
     *                 @OA\Property(property="list_payment_methods", type="string", example="/api/sandbox/payment-methods"),
     *                 @OA\Property(property="list_invoices", type="string", example="/api/sandbox/invoices"),
     *                 @OA\Property(property="list_plans", type="string", example="/api/sandbox/plans"),
     *                 @OA\Property(property="simulate_expiration", type="string", example="/api/sandbox/simulate-expiration")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function index()
    {
        // Obter as primeiras 8 caracteres da chave pública e secreta para exibição segura
        $publicKey = config('cashier.key');
        $secretKey = config('cashier.secret');
        
        $maskedPublicKey = substr($publicKey, 0, 8) . '...' . substr($publicKey, -4);
        $maskedSecretKey = substr($secretKey, 0, 8) . '...' . substr($secretKey, -4);
        
        return response()->json([
            'message' => 'Bem-vindo ao ambiente sandbox do Stripe',
            'stripe_key' => $maskedPublicKey,
            'stripe_keys_info' => [
                'public_key' => $maskedPublicKey,
                'secret_key' => $maskedSecretKey,
                'mode' => strpos($publicKey, 'pk_test_') === 0 ? 'test' : 'live',
            ],
            'test_cards' => [
                'success' => '4242 4242 4242 4242',
                'requires_auth' => '4000 0025 0000 3155',
                'declined' => '4000 0000 0000 0002'
            ],
            'test_tokens' => [
                'visa' => 'tok_visa',
                'visa_debit' => 'tok_visa_debit',
                'mastercard' => 'tok_mastercard',
                'mastercard_debit' => 'tok_mastercard_debit',
                'mastercard_prepaid' => 'tok_mastercard_prepaid',
                'amex' => 'tok_amex',
                'discover' => 'tok_discover',
                'diners' => 'tok_diners',
                'jcb' => 'tok_jcb'
            ],
            'como_usar' => [
                'passo_1' => 'Sincronize um plano com GET /api/sandbox/sync-plan/{id_do_plano}',
                'passo_2' => 'Crie um cliente no Stripe com POST /api/sandbox/create-customer (opcional, será feito automaticamente)',
                'passo_3' => 'Crie uma assinatura com POST /api/sandbox/create-subscription informando apenas o plan_id',
                'passo_4' => 'O sistema criará automaticamente um método de pagamento com tok_visa se necessário',
                'passo_5' => 'Verifique o status da assinatura em GET /api/user/role',
                'fluxo_opcional' => 'Se preferir, você também pode criar manualmente um método de pagamento com POST /api/sandbox/create-payment-method usando um token de teste (ex: tok_visa)'
            ],
            'endpoints' => [
                'test_connection' => '/api/sandbox/test-connection',
                'create_customer' => '/api/sandbox/create-customer',
                'create_payment_method' => '/api/sandbox/create-payment-method',
                'create_payment_intent' => '/api/sandbox/create-payment-intent',
                'create_subscription' => '/api/sandbox/create-subscription',
                'validate_subscription_request' => '/api/sandbox/validate-subscription-request',
                'sync_plan' => '/api/sandbox/sync-plan/{plan}',
                'list_payment_methods' => '/api/sandbox/payment-methods',
                'list_invoices' => '/api/sandbox/invoices',
                'list_plans' => '/api/sandbox/plans',
                'simulate_expiration' => '/api/sandbox/simulate-expiration',
                'simulate_current_expiration' => '/api/sandbox/simulate-current-expiration',
                'test_tokens' => '/api/sandbox/test-tokens'
            ],
            'subscription_endpoints' => [
                'get_active_subscription' => '/api/subscription/active',
                'check_subscription_status' => 'GET /api/subscription/active',
                'check_available_plans' => 'GET /api/subscription/check-plans',
                'check_plan_expiration' => 'GET /api/subscription/check-expiration',
                'expire_current_subscription' => 'POST /api/sandbox/simulate-current-expiration'
            ],
            'problemas_comuns' => [
                'json_invalido' => 'Se estiver recebendo erro 422 na criação de assinatura, certifique-se de que seu JSON está bem formatado, sem vírgulas extras ou faltando colchetes',
                'validar_json' => 'Use o endpoint /api/sandbox/validate-subscription-request para verificar se seu JSON está correto',
                'exemplo_minimal' => '{"plan_id": 1}',
                'exemplo_completo' => '{"plan_id": 1, "coupon": "DESCONTO10", "trial_period_days": 7, "test_expiration": false}'
            ],
            'exemplos' => [
                'criar_assinatura_simples' => [
                    'método' => 'POST',
                    'endpoint' => '/api/sandbox/create-subscription',
                    'corpo' => [
                        'plan_id' => 1
                    ],
                    'descrição' => 'Cria uma assinatura com o mínimo de parâmetros possível',
                    'curl' => "curl -X 'POST' \\\n  'http://localhost:8000/api/sandbox/create-subscription' \\\n  -H 'accept: application/json' \\\n  -H 'Authorization: Bearer SEU_TOKEN' \\\n  -H 'Content-Type: application/json' \\\n  -d '{\n  \"plan_id\": 1\n}'"
                ],
                'criar_assinatura_completa' => [
                    'método' => 'POST',
                    'endpoint' => '/api/sandbox/create-subscription',
                    'corpo' => [
                        'plan_id' => 1,
                        'coupon' => 'DESCONTO10',
                        'trial_period_days' => 7,
                        'test_expiration' => false
                    ],
                    'descrição' => 'Cria uma assinatura com todos os parâmetros opcionais',
                    'curl' => "curl -X 'POST' \\\n  'http://localhost:8000/api/sandbox/create-subscription' \\\n  -H 'accept: application/json' \\\n  -H 'Authorization: Bearer SEU_TOKEN' \\\n  -H 'Content-Type: application/json' \\\n  -d '{\n  \"plan_id\": 1,\n  \"coupon\": \"DESCONTO10\",\n  \"trial_period_days\": 7,\n  \"test_expiration\": false\n}'"
                ],
                'observações' => 'Certifique-se de que o JSON esteja corretamente formatado, sem vírgulas extras ou faltando colchetes.'
            ]
        ]);
    }
    
    /**
     * Testa a conexão com o Stripe
     * 
     * @OA\Get(
     *     path="/sandbox/test-connection",
     *     summary="Testar conexão com o Stripe",
     *     description="Verifica se as chaves do Stripe estão configuradas corretamente",
     *     operationId="testStripeConnection",
     *     tags={"Sandbox"},
     *     security={{"oauth2":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Resultado do teste de conexão",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Conexão com o Stripe estabelecida com sucesso"),
     *             @OA\Property(
     *                 property="account",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="acct_1NpEUXXXXXXXXXXX"),
     *                 @OA\Property(property="business_type", type="string", example="company"),
     *                 @OA\Property(property="email", type="string", example="contato@example.com"),
     *                 @OA\Property(property="country", type="string", example="BR")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro ao conectar com o Stripe",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erro ao conectar com o Stripe: Invalid API Key provided"),
     *             @OA\Property(property="error", type="string", example="Invalid API Key provided")
     *         )
     *     )
     * )
     */
    public function testConnection()
    {
        $result = $this->stripeService->testConnection();
        
        if (!$result['success']) {
            return response()->json($result, 500);
        }
        
        return response()->json($result);
    }
    
    /**
     * Cria um cliente de teste no Stripe
     * 
     * @OA\Post(
     *     path="/sandbox/create-customer",
     *     summary="Criar cliente no Stripe",
     *     description="Cria um cliente no Stripe para o usuário autenticado",
     *     operationId="createStripeCustomer",
     *     tags={"Sandbox"},
     *     security={{"oauth2":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Cliente criado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cliente criado com sucesso no Stripe"),
     *             @OA\Property(property="customer", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function createCustomer(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->stripe_id) {
            $customer = $this->stripeClient->customers->create([
                'email' => $user->email,
                'name' => $user->name,
                'metadata' => [
                    'user_id' => $user->id
                ]
            ]);
            
            $user->stripe_id = $customer->id;
            $user->save();
            
            return response()->json([
                'message' => 'Cliente criado com sucesso no Stripe',
                'customer' => $customer
            ]);
        }
        
        return response()->json([
            'message' => 'Usuário já possui um cliente no Stripe',
            'customer_id' => $user->stripe_id
        ]);
    }
    
    /**
     * Cria um método de pagamento de teste
     * 
     * @OA\Post(
     *     path="/sandbox/create-payment-method",
     *     summary="Criar método de pagamento",
     *     description="Cria um método de pagamento de teste no Stripe usando token",
     *     operationId="createStripePaymentMethod",
     *     tags={"Sandbox"},
     *     security={{"oauth2":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token"},
     *             @OA\Property(property="token", type="string", example="tok_visa"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Método de pagamento criado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Método de pagamento criado com sucesso"),
     *             @OA\Property(property="payment_method", type="object"),
     *             @OA\Property(property="info", type="string", example="Tokens de teste disponíveis: tok_visa, tok_mastercard, tok_amex, tok_discover")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro ao criar método de pagamento"
     *     )
     * )
     */
    public function createPaymentMethod(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'customer_id' => 'nullable|string'
        ]);
        
        try {
            // Verificar se foi fornecido um customer_id ou usar o do usuário autenticado
            $customerId = $validated['customer_id'] ?? null;
            
            if (!$customerId) {
                $user = Auth::user();
                
                // Certifique-se de que o usuário tenha um stripe_id
                if (!$user->stripe_id) {
                    $customer = $this->stripeClient->customers->create([
                        'email' => $user->email,
                        'name' => $user->name,
                        'metadata' => [
                            'user_id' => $user->id
                        ]
                    ]);
                    
                    $user->stripe_id = $customer->id;
                    $user->save();
                }
                
                $customerId = $user->stripe_id;
            }
            
            // Use tokens de teste do Stripe
            // tok_visa, tok_visa_debit, tok_mastercard, etc.
            $paymentMethod = $this->stripeClient->paymentMethods->create([
                'type' => 'card',
                'card' => [
                    'token' => $validated['token'],
                ],
            ]);
            
            // Anexar o método de pagamento ao cliente
            $this->stripeClient->paymentMethods->attach(
                $paymentMethod->id,
                ['customer' => $customerId]
            );
            
            // Definir como padrão (opcional - apenas no ambiente de sandbox)
            try {
                $this->stripeClient->customers->update($customerId, [
                    'invoice_settings' => [
                        'default_payment_method' => $paymentMethod->id
                    ]
                ]);
            } catch (\Exception $e) {
                // Se falhar ao definir como padrão, apenas registrar o erro mas continuar
                // já que o método de pagamento já foi criado e anexado
                \Log::warning("Não foi possível definir o método de pagamento como padrão: " . $e->getMessage());
            }
            
            return response()->json([
                'message' => 'Método de pagamento criado com sucesso',
                'payment_method_id' => $paymentMethod->id,
                'customer_id' => $customerId,
                'payment_method' => $paymentMethod,
                'info' => 'Tokens de teste disponíveis: tok_visa, tok_mastercard, tok_amex, tok_discover'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Erro ao criar método de pagamento: ' . $e->getMessage(),
                'tokens_disponiveis' => [
                    'tok_visa' => 'Cartão Visa com sucesso',
                    'tok_visa_debit' => 'Cartão Visa de débito',
                    'tok_mastercard' => 'Cartão Mastercard',
                    'tok_mastercard_debit' => 'Cartão Mastercard de débito',
                    'tok_mastercard_prepaid' => 'Cartão Mastercard pré-pago',
                    'tok_amex' => 'Cartão American Express',
                    'tok_discover' => 'Cartão Discover',
                    'tok_diners' => 'Cartão Diners Club',
                    'tok_jcb' => 'Cartão JCB'
                ]
            ], 500);
        }
    }
    
    /**
     * @OA\Get(
     *     path="/sandbox/payment-methods",
     *     summary="Listar métodos de pagamento",
     *     description="Lista os métodos de pagamento do cliente autenticado no Stripe",
     *     operationId="listPaymentMethods",
     *     tags={"Sandbox"},
     *     security={{"oauth2":{}}},
     *     @OA\Parameter(
     *         name="customer_id",
     *         in="query",
     *         required=false,
     *         description="ID do cliente no Stripe (opcional)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de métodos de pagamento",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", example="pm_1234567890"),
     *                     @OA\Property(property="type", type="string", example="card"),
     *                     @OA\Property(property="card", type="object",
     *                         @OA\Property(property="brand", type="string", example="visa"),
     *                         @OA\Property(property="last4", type="string", example="4242"),
     *                         @OA\Property(property="exp_month", type="integer", example=12),
     *                         @OA\Property(property="exp_year", type="integer", example=2025)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erro ao listar métodos de pagamento",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erro ao listar métodos de pagamento"),
     *             @OA\Property(property="error", type="string", example="Mensagem de erro do Stripe")
     *         )
     *     )
     * )
     */
    public function listPaymentMethods()
    {
        $user = Auth::user();
        
        if (!$user->stripe_id) {
            return response()->json([
                'message' => 'Usuário não possui um cliente no Stripe'
            ], 404);
        }
        
        $methods = $user->paymentMethods();
        
        return response()->json([
            'default_payment_method' => $user->defaultPaymentMethod(),
            'payment_methods' => $methods
        ]);
    }
    
    /**
     * @OA\Post(
     *     path="/sandbox/create-payment-intent",
     *     summary="Criar intent de pagamento",
     *     description="Cria uma nova intent de pagamento no Stripe",
     *     operationId="createStripePaymentIntent",
     *     tags={"Sandbox"},
     *     security={{"oauth2":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount", "currency"},
     *             @OA\Property(property="amount", type="integer", example=1000, description="Valor em centavos"),
     *             @OA\Property(property="currency", type="string", example="brl"),
     *             @OA\Property(property="payment_method_id", type="string", example="pm_card_visa"),
     *             @OA\Property(property="customer_id", type="string", example="cus_12345"),
     *             @OA\Property(property="description", type="string", example="Pagamento de teste")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Intent de pagamento criada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Intent de pagamento criada com sucesso"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="pi_1234567890"),
     *                 @OA\Property(property="amount", type="integer", example=1000),
     *                 @OA\Property(property="status", type="string", example="requires_confirmation"),
     *                 @OA\Property(property="client_secret", type="string", example="pi_1234567890_secret_1234567890")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Dados inválidos",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erro ao criar intent de pagamento"),
     *             @OA\Property(property="error", type="string", example="Mensagem de erro do Stripe")
     *         )
     *     )
     * )
     */
    public function createPaymentIntent(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'nullable|string',
        ]);
        
        $user = Auth::user();
        
        try {
            $paymentIntent = $this->stripeClient->paymentIntents->create([
                'amount' => $validated['amount'] * 100, // Stripe usa centavos
                'currency' => 'brl',
                'customer' => $user->stripe_id,
                'payment_method' => $validated['payment_method'] ?? $user->defaultPaymentMethod()?->id,
                'confirm' => true,
                'return_url' => route('sandbox.index'),
            ]);
            
            return response()->json([
                'message' => 'Intent de pagamento criada com sucesso',
                'payment_intent' => $paymentIntent
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Erro ao criar intent de pagamento: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * @OA\Get(
     *     path="/sandbox/sync-plan/{plan}",
     *     summary="Sincronizar plano com Stripe",
     *     description="Sincroniza um plano local com o Stripe, criando ou atualizando o produto e preço correspondente",
     *     operationId="syncPlanWithStripe",
     *     tags={"Sandbox"},
     *     security={{"oauth2":{}}},
     *     @OA\Parameter(
     *         name="plan",
     *         in="path",
     *         required=true,
     *         description="ID do plano a ser sincronizado",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Plano sincronizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Plano sincronizado com sucesso"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="product_id", type="string", example="prod_12345"),
     *                 @OA\Property(property="price_id", type="string", example="price_12345"),
     *                 @OA\Property(property="plan_id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Plano Mensal"),
     *                 @OA\Property(property="amount", type="integer", example=1990)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Plano não encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Plano não encontrado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erro ao sincronizar plano",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erro ao sincronizar plano"),
     *             @OA\Property(property="error", type="string", example="Mensagem de erro do Stripe")
     *         )
     *     )
     * )
     */
    public function syncPlan(Plan $plan)
    {
        $result = $this->stripeService->syncPlanWithStripe($plan);
        
        if (isset($result['error'])) {
            return response()->json([
                'error' => true,
                'message' => $result['message']
            ], 500);
        }
        
        return response()->json([
            'message' => 'Plano sincronizado com o Stripe com sucesso',
            'plan' => $plan,
            'stripe_product' => $result['product'],
            'stripe_price' => $result['price']
        ]);
    }
    
    /**
     * @OA\Post(
     *     path="/sandbox/create-subscription",
     *     summary="Cria uma assinatura de teste no Stripe",
     *     description="Cria uma assinatura de teste para um plano no Stripe. O sistema cria automaticamente um método de pagamento usando 'tok_visa' caso não exista um método disponível, facilitando o processo de teste. Apenas o plan_id é obrigatório.",
     *     operationId="createStripeSubscription",
     *     tags={"Sandbox"},
     *     security={{"oauth2":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"plan_id"},
     *             @OA\Property(property="plan_id", type="integer", example=1, description="ID do plano (obrigatório)"),
     *             @OA\Property(property="payment_method", type="string", example="pm_card_visa", description="Opcional - ID do método de pagamento no Stripe. Se não informado, usa o método padrão do usuário ou cria automaticamente um novo com tok_visa"),
     *             @OA\Property(property="coupon", type="string", example="DESCONTO10", description="Opcional - código do cupom de desconto"),
     *             @OA\Property(property="trial_period_days", type="integer", example=7, description="Opcional - dias de período de teste"),
     *             @OA\Property(property="test_expiration", type="boolean", example=false, description="Opcional - define a data de expiração para 1 minuto após a criação (para testes)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Assinatura criada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Assinatura de teste criada com sucesso"),
     *             @OA\Property(
     *                 property="subscription",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="plan_id", type="integer", example=1),
     *                 @OA\Property(property="stripe_id", type="string", example="sub_1234567890"),
     *                 @OA\Property(property="status", type="string", example="active"),
     *                 @OA\Property(property="start_date", type="string", format="date-time"),
     *                 @OA\Property(property="end_date", type="string", format="date-time"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(
     *                     property="plan",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Plano Premium"),
     *                     @OA\Property(property="price", type="number", format="float", example=49.90)
     *                 )
     *             ),
     *             @OA\Property(property="stripe_subscription", type="object"),
     *             @OA\Property(property="payment_method_used", type="string", example="pm_card_visa"),
     *             @OA\Property(property="customer_id_used", type="string", example="cus_123456789"),
     *             @OA\Property(property="test_expiration", type="boolean", example=false),
     *             @OA\Property(property="auto_created_payment_method", type="boolean", example=true, description="Indica se o método de pagamento foi criado automaticamente"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Dados inválidos",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Erro ao criar assinatura: Mensagem de erro")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro no servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Erro ao criar assinatura no Stripe: Mensagem de erro")
     *         )
     *     )
     * )
     */
    public function createSubscription(Request $request)
    {
        // Log do corpo da requisição para debug
        \Log::info('Request de criação de assinatura recebido:', [
            'body' => $request->all(),
            'content_type' => $request->header('Content-Type')
        ]);
        
        try {
            $validated = $request->validate([
                'plan_id' => 'required|exists:plans,id',
                'payment_method' => 'nullable|string',
                'coupon' => 'nullable|string',
                'trial_period_days' => 'nullable|integer|min:1|max:365',
                'test_expiration' => 'nullable|boolean',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log detalhado do erro de validação
            \Log::warning('Erro de validação na criação de assinatura:', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
                'request_content' => $request->getContent(),
            ]);
            
            // Retorna mensagem personalizada mencionando possíveis problemas de formato JSON
            return response()->json([
                'message' => 'Erro de validação. Certifique-se de que o JSON está corretamente formatado e que o plan_id foi fornecido.',
                'errors' => $e->errors(),
                'dica' => 'Verifique se não há vírgulas extras ou ausência de chaves/colchetes no JSON enviado.'
            ], 422);
        }
        
        $user = Auth::user();
        $plan = Plan::findOrFail($validated['plan_id']);
        
        try {
            // Certifique-se de que o usuário tenha um stripe_id
            if (!$user->stripe_id) {
                $customer = $this->stripeClient->customers->create([
                    'email' => $user->email,
                    'name' => $user->name,
                    'metadata' => [
                        'user_id' => $user->id
                    ]
                ]);
                
                $user->stripe_id = $customer->id;
                $user->save();
            }
            
            // Verificar se o plano tem um preço Stripe
            if (empty($plan->stripe_price_id)) {
                // Sincronizar o plano com o Stripe se não tiver preço
                $syncResult = $this->stripeService->syncPlanWithStripe($plan);
                if (isset($syncResult['error']) && $syncResult['error']) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Erro ao sincronizar plano com Stripe: ' . $syncResult['message']
                    ], 500);
                }
                
                // Recarregar o plano para garantir que temos o stripe_price_id atualizado
                $plan->refresh();
            }
            
            // Obter ou determinar o método de pagamento
            $paymentMethod = $validated['payment_method'] ?? null;
            
            // Se não foi fornecido um método de pagamento, tenta obter o método padrão do usuário
            if (!$paymentMethod) {
                try {
                    // Tenta obter o método de pagamento padrão do cliente
                    $customerData = $this->stripeClient->customers->retrieve($user->stripe_id);
                    
                    if (!empty($customerData->invoice_settings->default_payment_method)) {
                        $paymentMethod = $customerData->invoice_settings->default_payment_method;
                    } else {
                        // Se não tiver método padrão, busca o último método de pagamento criado
                        $paymentMethods = $this->stripeClient->paymentMethods->all([
                            'customer' => $user->stripe_id,
                            'type' => 'card',
                            'limit' => 1
                        ]);
                        
                        if (!empty($paymentMethods->data) && !empty($paymentMethods->data[0]->id)) {
                            $paymentMethod = $paymentMethods->data[0]->id;
                            
                            // Define este método como padrão para uso futuro
                            $this->stripeClient->customers->update($user->stripe_id, [
                                'invoice_settings' => [
                                    'default_payment_method' => $paymentMethod
                                ]
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    // Se ocorrer algum erro ao buscar o método de pagamento, apenas registra o erro
                    \Log::warning("Erro ao buscar método de pagamento padrão: " . $e->getMessage());
                }
                
                // Controle se o método foi criado automaticamente
                $autoCreatedPaymentMethod = false;
                
                // Se ainda não tiver um método de pagamento, criar um automático usando tok_visa
                if (!$paymentMethod) {
                    try {
                        // Criar um cartão de teste usando o token tok_visa
                        $card = $this->stripeClient->paymentMethods->create([
                            'type' => 'card',
                            'card' => [
                                'token' => 'tok_visa',
                            ],
                        ]);
                        
                        // Anexar o cartão ao cliente
                        $this->stripeClient->paymentMethods->attach(
                            $card->id,
                            ['customer' => $user->stripe_id]
                        );
                        
                        // Definir como método de pagamento padrão
                        $this->stripeClient->customers->update($user->stripe_id, [
                            'invoice_settings' => [
                                'default_payment_method' => $card->id
                            ]
                        ]);
                        
                        $paymentMethod = $card->id;
                        $autoCreatedPaymentMethod = true;
                    } catch (\Exception $e) {
                        return response()->json([
                            'error' => true,
                            'message' => 'Erro ao criar método de pagamento automático: ' . $e->getMessage()
                        ], 400);
                    }
                }
            } else {
                $autoCreatedPaymentMethod = false;
            }
            
            // Verifica se encontrou um método de pagamento
            if (!$paymentMethod) {
                return response()->json([
                    'error' => true,
                    'message' => 'Não foi possível criar ou obter um método de pagamento. Por favor, tente novamente ou crie um método de pagamento com /api/sandbox/create-payment-method primeiro.'
                ], 400);
            }
            
            // Preparar os parâmetros da assinatura
            $subscriptionParams = [
                'customer' => $user->stripe_id,
                'items' => [
                    ['price' => $plan->stripe_price_id],
                ],
                'default_payment_method' => $paymentMethod,
                'metadata' => [
                    'plan_id' => $plan->id,
                    'user_id' => $user->id,
                ],
            ];
            
            // Adicionar cupom se fornecido
            if (!empty($validated['coupon'])) {
                $subscriptionParams['coupon'] = $validated['coupon'];
            }
            
            // Adicionar período de teste se fornecido
            if (!empty($validated['trial_period_days'])) {
                $subscriptionParams['trial_period_days'] = $validated['trial_period_days'];
            }
            
            // Criar a assinatura no Stripe
            try {
                $stripeSubscription = $this->stripeClient->subscriptions->create($subscriptionParams);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => true,
                    'message' => 'Erro ao criar assinatura no Stripe: ' . $e->getMessage(),
                ], 500);
            }
            
            // Determinar a duração da assinatura com base no ciclo de cobrança
            $duration = match ($plan->billing_cycle) {
                'mensal' => 30,
                'trimestral' => 90,
                'anual' => 365,
                default => 30,
            };

            // Ajustar a data de término para levar em conta o período de teste
            $startDate = now();
            $endDate = $startDate->copy()->addDays($duration);
            
            if (!empty($validated['trial_period_days'])) {
                $endDate = $endDate->addDays($validated['trial_period_days']);
            }

            // Criar a assinatura no banco de dados
            $subscription = new \App\Models\Subscription();
            $subscription->user_id = $user->id;
            $subscription->plan_id = $plan->id;
            $subscription->status = 'active';
            $subscription->stripe_id = $stripeSubscription->id;
            $subscription->start_date = $startDate;
            $subscription->end_date = $endDate;
            $subscription->last_payment_date = $startDate;
            $subscription->next_payment_date = $endDate;
            $subscription->auto_renew = true;
            $subscription->payment_method = $paymentMethod;
            $subscription->save();
            
            // Se test_expiration for true, definir a data de término para hoje + 1 minuto
            if (!empty($validated['test_expiration']) && $validated['test_expiration']) {
                $subscription->end_date = now()->addMinutes(1);
                $subscription->save();
            }
            
            return response()->json([
                'message' => 'Assinatura de teste criada com sucesso',
                'subscription' => $subscription->load('plan'),
                'stripe_subscription' => $stripeSubscription,
                'payment_method_used' => $paymentMethod,
                'customer_id_used' => $user->stripe_id,
                'test_expiration' => !empty($validated['test_expiration']) && $validated['test_expiration'],
                'auto_created_payment_method' => $autoCreatedPaymentMethod
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Erro ao criar assinatura: ' . $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * @OA\Get(
     *     path="/sandbox/invoices",
     *     summary="Listar faturas",
     *     description="Lista as faturas do cliente no Stripe",
     *     operationId="listInvoices",
     *     tags={"Sandbox"},
     *     security={{"oauth2":{}}},
     *     @OA\Parameter(
     *         name="customer_id",
     *         in="query",
     *         required=false,
     *         description="ID do cliente no Stripe (opcional)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Limite de faturas a serem retornadas",
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de faturas",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", example="in_1234567890"),
     *                     @OA\Property(property="number", type="string", example="1A2B3C4D"),
     *                     @OA\Property(property="customer_id", type="string", example="cus_12345"),
     *                     @OA\Property(property="subscription_id", type="string", example="sub_12345"),
     *                     @OA\Property(property="status", type="string", example="paid"),
     *                     @OA\Property(property="currency", type="string", example="brl"),
     *                     @OA\Property(property="amount_due", type="integer", example=1990),
     *                     @OA\Property(property="amount_paid", type="integer", example=1990),
     *                     @OA\Property(property="created", type="integer", example=1617235200),
     *                     @OA\Property(property="period_start", type="integer", example=1617235200),
     *                     @OA\Property(property="period_end", type="integer", example=1619827200),
     *                     @OA\Property(property="pdf", type="string", example="https://pay.stripe.com/invoice/example")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erro ao listar faturas",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erro ao listar faturas"),
     *             @OA\Property(property="error", type="string", example="Mensagem de erro do Stripe")
     *         )
     *     )
     * )
     */
    public function listInvoices()
    {
        $user = Auth::user();
        
        if (!$user->stripe_id) {
            return response()->json([
                'message' => 'Usuário não possui um cliente no Stripe'
            ], 404);
        }
        
        $invoices = $user->invoices();
        
        return response()->json([
            'invoices' => $invoices
        ]);
    }
    
    /**
     * Lista os planos disponíveis com informações do Stripe
     * 
     * @OA\Get(
     *     path="/sandbox/plans",
     *     summary="Listar planos com informações do Stripe",
     *     description="Retorna a lista de planos com informações de sincronização com o Stripe",
     *     operationId="listStripePlans",
     *     tags={"Sandbox"},
     *     security={{"oauth2":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de planos com informações do Stripe",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Plano Básico"),
     *                 @OA\Property(property="price", type="number", format="float", example=29.90),
     *                 @OA\Property(property="billing_cycle", type="string", example="mensal"),
     *                 @OA\Property(property="stripe_product_id", type="string", example="prod_XXXXXXXXXXXXXXXX"),
     *                 @OA\Property(property="stripe_price_id", type="string", example="price_XXXXXXXXXXXXXXXX"),
     *                 @OA\Property(property="is_synced_with_stripe", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function listPlans()
    {
        $plans = Plan::all();
        
        $plansWithStripeInfo = $plans->map(function ($plan) {
            $plan->is_synced_with_stripe = !empty($plan->stripe_product_id) && !empty($plan->stripe_price_id);
            return $plan;
        });
        
        return response()->json($plansWithStripeInfo);
    }
    
    /**
     * Simula a expiração de uma assinatura para testes
     * 
     * @OA\Post(
     *     path="/sandbox/simulate-expiration",
     *     summary="Simular expiração de assinatura",
     *     description="Simula a expiração de uma assinatura para testes",
     *     operationId="simulateSubscriptionExpiration",
     *     tags={"Sandbox"},
     *     security={{"oauth2":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"subscription_id"},
     *             @OA\Property(property="subscription_id", type="integer", example=1, description="ID da assinatura"),
     *             @OA\Property(property="should_renew", type="boolean", example=true, description="Se a assinatura deve ser renovada automaticamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Simulação realizada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Simulação de expiração realizada com sucesso"),
     *             @OA\Property(property="was_renewed", type="boolean", example=true),
     *             @OA\Property(
     *                 property="subscription",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="status", type="string", example="active"),
     *                 @OA\Property(property="start_date", type="string", format="date-time", example="2025-03-16T22:50:17.000000Z"),
     *                 @OA\Property(property="end_date", type="string", format="date-time", example="2025-04-16T22:50:17.000000Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acesso negado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Você não tem permissão para simular a expiração desta assinatura")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Assinatura não encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Assinatura não encontrada")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dados inválidos",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="subscription_id", type="array", @OA\Items(type="string", example="O campo subscription id é obrigatório."))
     *             )
     *         )
     *     )
     * )
     */
    public function simulateExpiration(Request $request)
    {
        $validated = $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
            'should_renew' => 'boolean',
        ]);
        
        $subscription = Subscription::findOrFail($validated['subscription_id']);
        $user = Auth::user();
        
        // Verificar se a assinatura pertence ao usuário ou se é admin
        if ($subscription->user_id !== $user->id && !$user->hasRole('admin')) {
            return response()->json([
                'message' => 'Você não tem permissão para simular a expiração desta assinatura'
            ], 403);
        }
        
        // Definir a data de término para hoje
        $subscription->end_date = now();
        $subscription->save();
        
        $wasRenewed = false;
        
        // Se deve renovar automaticamente
        if (isset($validated['should_renew']) && $validated['should_renew']) {
            // Simular renovação
            $plan = $subscription->plan;
            
            // Calcular nova data de término
            $newEndDate = match($plan->billing_cycle) {
                'mensal' => now()->addMonth(),
                'trimestral' => now()->addMonths(3),
                'anual' => now()->addYear(),
                default => now()->addMonth()
            };
            
            $subscription->status = 'active';
            $subscription->start_date = now();
            $subscription->end_date = $newEndDate;
            $subscription->last_payment_date = now();
            $subscription->next_payment_date = $newEndDate;
            $subscription->save();
            
            $wasRenewed = true;
        } else {
            // Marcar como expirada
            $subscription->status = 'expired';
            $subscription->save();
        }
        
        return response()->json([
            'message' => 'Simulação de expiração concluída',
            'subscription' => $subscription->fresh(),
            'was_renewed' => $wasRenewed
        ]);
    }
    
    /**
     * Lista os tokens de teste disponíveis para o Stripe
     * 
     * @OA\Get(
     *     path="/sandbox/test-tokens",
     *     summary="Listar tokens de teste do Stripe",
     *     description="Retorna a lista de tokens de teste disponíveis para o Stripe",
     *     operationId="listTestTokens",
     *     tags={"Sandbox"},
     *     security={{"oauth2":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de tokens de teste",
     *         @OA\JsonContent(
     *             @OA\Property(property="tokens", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function testTokens()
    {
        return response()->json([
            'message' => 'Tokens de teste do Stripe',
            'como_usar' => 'Use esses tokens para criar métodos de pagamento no endpoint /api/sandbox/create-payment-method',
            'tokens' => [
                'tok_visa' => 'Cartão Visa com sucesso',
                'tok_visa_debit' => 'Cartão Visa de débito',
                'tok_mastercard' => 'Cartão Mastercard',
                'tok_mastercard_debit' => 'Cartão Mastercard de débito',
                'tok_mastercard_prepaid' => 'Cartão Mastercard pré-pago',
                'tok_amex' => 'American Express',
                'tok_discover' => 'Discover',
                'tok_diners' => 'Diners Club',
                'tok_jcb' => 'JCB'
            ],
            'exemplo_requisicao' => [
                'method' => 'POST',
                'url' => '/api/sandbox/create-payment-method',
                'body' => [
                    'token' => 'tok_visa'
                ]
            ]
        ]);
    }
    
    /**
     * Teste de formatação JSON para assinatura
     * 
     * @OA\Post(
     *     path="/sandbox/validate-subscription-request",
     *     summary="Testar formatação de JSON para criação de assinatura",
     *     description="Endpoint utilitário para validar se o formato JSON utilizado está correto para criar uma assinatura",
     *     operationId="validateSubscriptionRequest",
     *     tags={"Sandbox"},
     *     security={{"oauth2":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"plan_id"},
     *             @OA\Property(property="plan_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="JSON válido",
     *         @OA\JsonContent(
     *             @OA\Property(property="valid", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="O formato JSON está correto"),
     *             @OA\Property(property="received_data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="JSON inválido",
     *         @OA\JsonContent(
     *             @OA\Property(property="valid", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erro na formatação do JSON"),
     *             @OA\Property(property="error", type="string"),
     *             @OA\Property(property="received_content", type="string")
     *         )
     *     )
     * )
     */
    public function validateSubscriptionRequest(Request $request)
    {
        try {
            // Verificar a formatação JSON
            $jsonData = $request->json()->all();
            
            return response()->json([
                'valid' => true,
                'message' => 'O formato JSON está correto',
                'received_data' => $jsonData,
                'exemplo_correto' => [
                    'plan_id' => 1,
                    'coupon' => 'DESCONTO10',  // opcional
                    'trial_period_days' => 7,  // opcional
                    'test_expiration' => false // opcional
                ],
                'dica' => 'Para criar uma assinatura, use o endpoint /api/sandbox/create-subscription'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Erro na formatação do JSON',
                'error' => $e->getMessage(),
                'received_content' => $request->getContent(),
                'exemplo_correto' => '{"plan_id": 1}',
                'dica' => 'Verifique se há vírgulas extras ou faltando colchetes/chaves'
            ], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/sandbox/simulate-current-expiration",
     *     summary="Simular expiração da assinatura atual",
     *     description="Simula a expiração da assinatura ativa atual do usuário autenticado",
     *     operationId="simulateCurrentSubscriptionExpiration",
     *     tags={"Sandbox"},
     *     security={{"oauth2":{}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="should_renew", type="boolean", example=false, description="Se a assinatura deve ser renovada automaticamente após expirar")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Resultado da simulação de expiração",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Simulação de expiração concluída"),
     *             @OA\Property(property="subscription", type="object"),
     *             @OA\Property(property="was_renewed", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Nenhuma assinatura ativa encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Nenhuma assinatura ativa encontrada")
     *         )
     *     )
     * )
     */
    public function simulateCurrentSubscriptionExpiration(Request $request)
    {
        $validated = $request->validate([
            'should_renew' => 'boolean',
        ]);
        
        $user = Auth::user();
        $subscription = $user->getActiveSubscription();
        
        if (!$subscription) {
            return response()->json([
                'message' => 'Nenhuma assinatura ativa encontrada'
            ], 404);
        }
        
        // Definir a data de término para hoje
        $subscription->end_date = now();
        $subscription->save();
        
        $wasRenewed = false;
        
        // Se deve renovar automaticamente
        if (isset($validated['should_renew']) && $validated['should_renew']) {
            // Simular renovação
            $plan = $subscription->plan;
            
            // Calcular nova data de término
            $newEndDate = match($plan->billing_cycle) {
                'mensal' => now()->addMonth(),
                'trimestral' => now()->addMonths(3),
                'anual' => now()->addYear(),
                default => now()->addMonth()
            };
            
            $subscription->status = 'active';
            $subscription->start_date = now();
            $subscription->end_date = $newEndDate;
            $subscription->last_payment_date = now();
            $subscription->next_payment_date = $newEndDate;
            $subscription->save();
            
            $wasRenewed = true;
        } else {
            // Marcar como expirada
            $subscription->status = 'expired';
            $subscription->save();
        }
        
        return response()->json([
            'message' => 'Simulação de expiração concluída',
            'subscription' => $subscription->fresh()->load('plan'),
            'was_renewed' => $wasRenewed
        ]);
    }

    /**
     * Testa o processamento de assinaturas expiradas
     * 
     * @OA\Post(
     *     path="/api/sandbox/test-expired-subscriptions",
     *     summary="Testa o processamento de assinaturas expiradas",
     *     operationId="testExpiredSubscriptionsProcessing",
     *     tags={"Sandbox"},
     *     security={{"oauth2":{}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="run_now", type="boolean", example=true, description="Se o processamento deve ser executado imediatamente ou apenas enfileirado"),
     *             @OA\Property(property="recently_expired", type="integer", example=6, description="Processar apenas assinaturas expiradas nos últimos X minutos (0 = todas)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Resultado do teste de processamento",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Job de processamento de assinaturas expiradas foi enviado"),
     *             @OA\Property(property="details", type="string")
     *         )
     *     )
     * )
     */
    public function testExpiredSubscriptionsProcessing(Request $request)
    {
        // Verificar se o usuário é administrador
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'message' => 'Apenas administradores podem executar esta ação'
            ], 403);
        }
        
        $runNow = $request->input('run_now', false);
        $recentlyExpired = (int) $request->input('recently_expired', 0);
        
        \Illuminate\Support\Facades\Log::info('Teste de processamento de assinaturas expiradas iniciado', [
            'user_id' => Auth::id(),
            'run_now' => $runNow,
            'recently_expired' => $recentlyExpired
        ]);
        
        if ($runNow) {
            // Executar o job imediatamente
            $job = new \App\Jobs\ProcessExpiredSubscriptions($recentlyExpired);
            $job->handle();
            
            \Illuminate\Support\Facades\Log::info('Processamento de assinaturas expiradas executado com sucesso (modo direto)');
            
            return response()->json([
                'message' => 'Processamento de assinaturas expiradas executado com sucesso',
                'details' => 'O job foi executado diretamente, sem usar a fila'
            ]);
        } else {
            // Enfileirar o job
            \App\Jobs\ProcessExpiredSubscriptions::dispatch($recentlyExpired);
            
            \Illuminate\Support\Facades\Log::info('Job de processamento de assinaturas expiradas enfileirado');
            
            return response()->json([
                'message' => 'Job de processamento de assinaturas expiradas foi enviado para a fila',
                'details' => 'Verifique os logs para acompanhar a execução'
            ]);
        }
    }
}
