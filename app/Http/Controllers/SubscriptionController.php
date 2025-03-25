<?php

namespace App\Http\Controllers;

/**
 * AVISO: Todas as rotas deste controlador estão desativadas.
 * Estas rotas são destinadas apenas para ambiente de produção e foram
 * desativadas no ambiente de desenvolvimento/teste. 
 * Para operações de teste, utilize as rotas em SandboxController.
 * 
 * NOTA: Todas as anotações OpenAPI foram removidas para que essas rotas
 * não apareçam na documentação Swagger.
 */

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $subscriptions = $request->user()->subscriptions()->with('plan')->get();
        return response()->json($subscriptions);
    }

    public function all()
    {
        $subscriptions = Subscription::with(['user', 'plan'])->get();
        return response()->json($subscriptions);
    }

    public function show(Subscription $subscription)
    {
        // Verificar se a assinatura pertence ao usuário autenticado
        if ($subscription->user_id !== Auth::id()) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $subscription->load('plan');
        return response()->json($subscription);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'payment_method' => 'required|string',
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);
        
        // Determinar a duração da assinatura com base no ciclo de cobrança
        $duration = match ($plan->billing_cycle) {
            'mensal' => 30,
            'trimestral' => 90,
            'anual' => 365,
            default => 30,
        };

        $subscription = new Subscription();
        $subscription->user_id = $request->user()->id;
        $subscription->plan_id = $plan->id;
        $subscription->status = 'pending'; // Será atualizado após o pagamento
        $subscription->start_date = now();
        $subscription->end_date = now()->addDays($duration);
        $subscription->auto_renew = true;
        $subscription->save();

        // Aqui seria o lugar para integrar com o gateway de pagamento
        // Por enquanto, apenas retornamos a assinatura criada

        return response()->json($subscription, 201);
    }

    public function update(Request $request, Subscription $subscription)
    {
        // Verificar se a assinatura pertence ao usuário autenticado
        if ($subscription->user_id !== Auth::id()) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $validated = $request->validate([
            'auto_renew' => 'sometimes|boolean',
        ]);

        $subscription->update($validated);
        return response()->json($subscription);
    }

    public function cancel(Subscription $subscription)
    {
        // Verificar se a assinatura pertence ao usuário autenticado
        if ($subscription->user_id !== Auth::id()) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $subscription->status = 'canceled';
        $subscription->canceled_at = now();
        $subscription->auto_renew = false;
        $subscription->save();

        return response()->json([
            'message' => 'Assinatura cancelada com sucesso',
            'subscription' => $subscription
        ]);
    }

    public function renew(Subscription $subscription)
    {
        $plan = $subscription->plan;
        
        // Determinar a duração da assinatura com base no ciclo de cobrança
        $duration = match ($plan->billing_cycle) {
            'mensal' => 30,
            'trimestral' => 90,
            'anual' => 365,
            default => 30,
        };

        $subscription->status = 'active';
        $subscription->start_date = now();
        $subscription->end_date = now()->addDays($duration);
        $subscription->last_payment_date = now();
        $subscription->next_payment_date = now()->addDays($duration);
        $subscription->canceled_at = null;
        $subscription->auto_renew = true;
        $subscription->save();

        return response()->json([
            'message' => 'Assinatura renovada com sucesso',
            'subscription' => $subscription
        ]);
    }

    /**
     * Verifica se o usuário tem acesso à assinatura
     * 
     * @param Subscription $subscription
     * @return bool
     */
    private function authorizeSubscriptionAccess(Subscription $subscription): bool
    {
        return Auth::user()->id === $subscription->user_id || Auth::user()->hasRole('admin');
    }

    /**
     * Calcula a data de término com base no ciclo de cobrança
     * 
     * @param \Carbon\Carbon $startDate
     * @param string $billingCycle
     * @return \Carbon\Carbon
     */
    private function calculateEndDate(Carbon $startDate, string $billingCycle): Carbon
    {
        return match ($billingCycle) {
            'mensal' => $startDate->copy()->addMonth(),
            'trimestral' => $startDate->copy()->addMonths(3),
            'anual' => $startDate->copy()->addYear(),
            default => $startDate->copy()->addMonth(),
        };
    }

    public function createCheckout(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'payment_method' => 'required|string'
        ]);

        $user = Auth::user();
        $plan = Plan::findOrFail($request->plan_id);
        $paymentMethod = $request->payment_method;

        try {
            // Criar a assinatura no banco de dados
            $subscription = new Subscription();
            $subscription->user_id = $user->id;
            $subscription->plan_id = $plan->id;
            $subscription->status = 'pending';
            $subscription->start_date = now();
            
            // Calcular a data de término com base no ciclo de cobrança
            $subscription->end_date = $this->calculateEndDate(now(), $plan->billing_cycle);
            $subscription->next_payment_date = $subscription->end_date;
            $subscription->auto_renew = true;
            $subscription->save();
            
            // Atualizar o método de pagamento na assinatura
            $subscription->updatePaymentMethod($paymentMethod);
            
            if ($plan->type === 'recurrent') {
                // Criar assinatura recorrente no Stripe
                $stripeSubscriptionId = $subscription->createStripeSubscription();
                
                return response()->json([
                    'subscription_id' => $subscription->id,
                    'stripe_subscription_id' => $stripeSubscriptionId,
                    'status' => 'success',
                    'message' => 'Assinatura recorrente criada com sucesso'
                ]);
            } else {
                // Processar pagamento único
                $paymentIntentId = $subscription->processPayment();
                
                return response()->json([
                    'subscription_id' => $subscription->id,
                    'payment_intent_id' => $paymentIntentId,
                    'status' => 'success',
                    'message' => 'Assinatura única processada com sucesso'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Erro ao criar checkout: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao processar pagamento: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updatePaymentMethod(Request $request)
    {
        $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
            'payment_method' => 'required|string'
        ]);

        $subscription = Subscription::findOrFail($request->subscription_id);
        
        // Verificar se a assinatura pertence ao usuário autenticado
        if (!$this->authorizeSubscriptionAccess($subscription)) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }
        
        try {
            $subscription->updatePaymentMethod($request->payment_method);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Método de pagamento atualizado com sucesso'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar método de pagamento: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao atualizar método de pagamento: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function cancelStripeSubscription(Subscription $subscription)
    {
        // Verificar se a assinatura pertence ao usuário autenticado
        if (!$this->authorizeSubscriptionAccess($subscription)) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }
        
        try {
            if (!$subscription->stripe_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Esta assinatura não tem ID Stripe associado'
                ], 400);
            }
            
            $subscription->cancel();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Assinatura cancelada com sucesso'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao cancelar assinatura no Stripe: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao cancelar assinatura: ' . $e->getMessage()
            ], 500);
        }
    }

    public function createSetupIntent()
    {
        $user = Auth::user();
        
        try {
            $stripe = new StripeClient(config('cashier.secret'));
            
            // Criar cliente no Stripe se não existir
            if (!$user->stripe_id) {
                $customer = $stripe->customers->create([
                    'email' => $user->email,
                    'name' => $user->name,
                    'metadata' => [
                        'user_id' => $user->id
                    ]
                ]);
                
                $user->stripe_id = $customer->id;
                $user->save();
            }
            
            // Criar setup intent
            $setupIntent = $stripe->setupIntents->create([
                'customer' => $user->stripe_id,
                'usage' => 'off_session',
            ]);
            
            return response()->json([
                'client_secret' => $setupIntent->client_secret
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao criar setup intent: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao criar setup intent: ' . $e->getMessage()
            ], 500);
        }
    }

    public function allSubscriptions()
    {
        $subscriptions = Subscription::with(['user', 'plan'])->get();
        return response()->json($subscriptions);
    }

    /**
     * @OA\Get(
     *     path="/subscription/active",
     *     summary="Obter assinatura ativa",
     *     description="Retorna os detalhes da assinatura ativa do usuário autenticado",
     *     operationId="getActiveSubscription",
     *     tags={"Subscriptions"},
     *     security={{"oauth2":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Detalhes da assinatura ativa",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="subscription",
     *                 ref="#/components/schemas/Subscription"
     *             ),
     *             @OA\Property(
     *                 property="has_active_subscription",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="days_remaining",
     *                 type="integer",
     *                 example=15
     *             ),
     *             @OA\Property(
     *                 property="on_trial",
     *                 type="boolean",
     *                 example=false
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Nenhuma assinatura ativa encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Nenhuma assinatura ativa encontrada"),
     *             @OA\Property(property="has_active_subscription", type="boolean", example=false)
     *         )
     *     )
     * )
     */
    public function getActiveSubscription()
    {
        $user = auth()->user();
        $subscription = $user->getActiveSubscription();
        
        if (!$subscription) {
            return response()->json([
                'message' => 'Nenhuma assinatura ativa encontrada',
                'has_active_subscription' => false
            ], 404);
        }
        
        // Calcular dias restantes
        $daysRemaining = now()->diffInDays($subscription->end_date, false);
        
        return response()->json([
            'subscription' => $subscription->load('plan'),
            'has_active_subscription' => true,
            'days_remaining' => $daysRemaining,
            'on_trial' => $subscription->onTrial()
        ]);
    }
    
    /**
     * @OA\Get(
     *     path="/subscription/check-plans",
     *     summary="Verificar planos disponíveis",
     *     description="Retorna informações sobre os planos disponíveis para o usuário",
     *     operationId="checkAvailablePlans",
     *     tags={"Subscriptions"},
     *     security={{"oauth2":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Informações dos planos disponíveis",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="plans",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Plan")
     *             ),
     *             @OA\Property(
     *                 property="has_active_subscription",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="current_plan",
     *                 nullable=true,
     *                 ref="#/components/schemas/Plan"
     *             ),
     *             @OA\Property(
     *                 property="recommended_plan",
     *                 nullable=true,
     *                 ref="#/components/schemas/Plan"
     *             )
     *         )
     *     )
     * )
     */
    public function checkAvailablePlans()
    {
        $user = auth()->user();
        $subscription = $user->getActiveSubscription();
        $plans = \App\Models\Plan::where('is_active', true)->get();
        
        $response = [
            'plans' => $plans,
            'has_active_subscription' => $subscription ? true : false,
            'current_plan' => $subscription ? $subscription->plan : null,
            'recommended_plan' => null
        ];
        
        // Lógica para determinar o plano recomendado
        // Por exemplo, o plano com mais recursos ou melhor custo-benefício
        if (!$subscription && count($plans) > 0) {
            $response['recommended_plan'] = $plans->where('price', $plans->min('price'))->first();
        } elseif ($subscription) {
            // Sugerir upgrade se tiver um plano com mais recursos
            $betterPlans = $plans->where('price', '>', $subscription->plan->price);
            if ($betterPlans->count() > 0) {
                $response['recommended_plan'] = $betterPlans->sortBy('price')->first();
            }
        }
        
        return response()->json($response);
    }
    
    /**
     * @OA\Get(
     *     path="/subscription/check-expiration",
     *     summary="Verificar expiração do plano",
     *     description="Verifica se a assinatura atual do usuário está próxima da expiração",
     *     operationId="checkPlanExpiration",
     *     tags={"Subscriptions"},
     *     security={{"oauth2":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Status de expiração do plano",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="has_active_subscription",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="subscription",
     *                 nullable=true,
     *                 ref="#/components/schemas/Subscription"
     *             ),
     *             @OA\Property(
     *                 property="days_remaining",
     *                 type="integer",
     *                 example=5
     *             ),
     *             @OA\Property(
     *                 property="expires_soon",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="auto_renew",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="expiration_date",
     *                 type="string",
     *                 format="date-time",
     *                 example="2025-04-15T23:59:59.000000Z"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Nenhuma assinatura ativa encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Nenhuma assinatura ativa encontrada"),
     *             @OA\Property(property="has_active_subscription", type="boolean", example=false)
     *         )
     *     )
     * )
     */
    public function checkPlanExpiration()
    {
        $user = auth()->user();
        $subscription = $user->getActiveSubscription();
        
        if (!$subscription) {
            return response()->json([
                'message' => 'Nenhuma assinatura ativa encontrada',
                'has_active_subscription' => false
            ], 404);
        }
        
        // Calcular dias restantes
        $daysRemaining = now()->diffInDays($subscription->end_date, false);
        
        // Consideramos que está próximo da expiração se faltar menos de 7 dias
        $expiresSoon = $daysRemaining <= 7;
        
        return response()->json([
            'has_active_subscription' => true,
            'subscription' => $subscription->load('plan'),
            'days_remaining' => $daysRemaining,
            'expires_soon' => $expiresSoon,
            'auto_renew' => $subscription->auto_renew,
            'expiration_date' => $subscription->end_date->toDateTimeString()
        ]);
    }
} 