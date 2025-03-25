<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Support\Str;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Stripe\Stripe;

class StripeService
{
    /**
     * Construtor do serviço
     */
    public function __construct()
    {
        // Inicializa a API do Stripe com a chave secreta
        Stripe::setApiKey(config('cashier.secret'));
    }

    /**
     * Testa a conexão com o Stripe
     */
    public function testConnection()
    {
        try {
            // Tenta buscar a conta do Stripe para verificar se a conexão está funcionando
            $account = \Stripe\Account::retrieve();
            
            return [
                'success' => true,
                'message' => 'Conexão com o Stripe estabelecida com sucesso',
                'account' => [
                    'id' => $account->id,
                    'business_type' => $account->business_type,
                    'email' => $account->email,
                    'country' => $account->country,
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao conectar com o Stripe: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Cria ou atualiza um produto no Stripe para o plano
     */
    public function syncPlanWithStripe(Plan $plan)
    {
        try {
            // Gera identificadores únicos para o Stripe
            $stripeProductId = 'prod_' . Str::slug($plan->name) . '_' . $plan->id;
            $stripePriceId = 'price_' . Str::slug($plan->name) . '_' . $plan->billing_cycle . '_' . $plan->id;
            
            // Determina o intervalo de cobrança
            $interval = match($plan->billing_cycle) {
                'mensal' => 'month',
                'trimestral' => 'month',
                'anual' => 'year',
                default => 'month'
            };
            
            // Determina a quantidade de intervalos
            $intervalCount = match($plan->billing_cycle) {
                'mensal' => 1,
                'trimestral' => 3,
                'anual' => 1,
                default => 1
            };
            
            // Cria o produto no Stripe
            $stripeProduct = \Stripe\Product::create([
                'name' => $plan->name,
                'description' => $plan->description,
                'active' => $plan->is_active,
                'metadata' => [
                    'plan_id' => $plan->id,
                    'features' => json_encode($plan->features)
                ]
            ]);
            
            // Cria o preço no Stripe
            $stripePrice = \Stripe\Price::create([
                'product' => $stripeProduct->id,
                'unit_amount' => (int)($plan->price * 100), // Stripe usa centavos
                'currency' => 'brl',
                'recurring' => [
                    'interval' => $interval,
                    'interval_count' => $intervalCount
                ],
                'metadata' => [
                    'plan_id' => $plan->id,
                    'billing_cycle' => $plan->billing_cycle
                ]
            ]);
            
            // Atualiza o plano com os IDs do Stripe
            $plan->stripe_product_id = $stripeProduct->id;
            $plan->stripe_price_id = $stripePrice->id;
            $plan->save();
            
            return [
                'product' => $stripeProduct,
                'price' => $stripePrice
            ];
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Cria uma assinatura para o usuário
     */
    public function createSubscription(User $user, Plan $plan, string $paymentMethod)
    {
        try {
            // Verifica se o plano tem um ID de preço do Stripe
            if (empty($plan->stripe_price_id)) {
                $this->syncPlanWithStripe($plan);
            }
            
            // Adiciona o método de pagamento ao usuário
            $user->updateDefaultPaymentMethod($paymentMethod);
            
            // Cria a assinatura no Stripe
            $stripeSubscription = $user->newSubscription('default', $plan->stripe_price_id)
                ->create($paymentMethod);
            
            // Cria a assinatura local
            $subscription = new Subscription([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'start_date' => now(),
                'end_date' => $this->calculateEndDate($plan),
                'last_payment_date' => now(),
                'next_payment_date' => $this->calculateEndDate($plan),
                'auto_renew' => true,
            ]);
            
            $subscription->save();
            
            return [
                'stripe_subscription' => $stripeSubscription,
                'subscription' => $subscription
            ];
        } catch (IncompletePayment $exception) {
            // Captura exceções de pagamento incompleto (como necessidade de autenticação adicional)
            return [
                'error' => true,
                'payment_intent' => $exception->payment->id,
                'message' => 'O pagamento requer confirmação adicional.',
                'payment_url' => route('cashier.payment', [$exception->payment->id])
            ];
        }
    }
    
    /**
     * Cancela uma assinatura
     */
    public function cancelSubscription(User $user, Subscription $subscription)
    {
        // Cancela a assinatura no Stripe
        $user->subscription('default')->cancel();
        
        // Atualiza a assinatura local
        $subscription->cancel();
        
        return $subscription;
    }
    
    /**
     * Calcula a data de término com base no ciclo de cobrança
     */
    private function calculateEndDate(Plan $plan)
    {
        $startDate = now();
        
        return match($plan->billing_cycle) {
            'mensal' => $startDate->copy()->addMonth(),
            'trimestral' => $startDate->copy()->addMonths(3),
            'anual' => $startDate->copy()->addYear(),
            default => $startDate->copy()->addMonth()
        };
    }
} 