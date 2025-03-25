<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WebhookController extends CashierWebhookController
{
    /**
     * Handle subscription updated.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleCustomerSubscriptionUpdated(array $payload)
    {
        $user = $this->getUserByStripeId($payload['data']['object']['customer']);
        
        if ($user) {
            // Processar dados da assinatura
            $stripeSubscription = $payload['data']['object'];
            
            // Verificar se a assinatura está ativa e prestes a expirar
            if ($stripeSubscription['status'] === 'active') {
                $this->checkForSubscriptionExpiration($user, $stripeSubscription);
            }
            
            // Atualiza a assinatura local
            $stripeSubscriptionId = $stripeSubscription['id'];
            $stripeStatus = $stripeSubscription['status'];
            
            // Encontra a assinatura do usuário
            $subscription = Subscription::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->first();
            
            if ($subscription) {
                // Atualiza o status da assinatura
                if ($stripeStatus === 'active') {
                    $subscription->status = 'active';
                } elseif ($stripeStatus === 'canceled') {
                    $subscription->status = 'canceled';
                    $subscription->canceled_at = now();
                    $subscription->auto_renew = false;
                } elseif ($stripeStatus === 'past_due') {
                    $subscription->status = 'expired';
                }
                
                $subscription->save();
            }
        }
        
        return $this->successMethod();
    }
    
    /**
     * Handle a canceled customer from a Stripe subscription.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleCustomerSubscriptionDeleted(array $payload)
    {
        $user = $this->getUserByStripeId($payload['data']['object']['customer']);
        
        if ($user) {
            // Cancela a assinatura local
            $subscription = Subscription::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->first();
            
            if ($subscription) {
                $subscription->status = 'canceled';
                $subscription->canceled_at = now();
                $subscription->auto_renew = false;
                $subscription->save();
            }
        }
        
        return $this->successMethod();
    }
    
    /**
     * Verificar se a assinatura está próxima da expiração e enviar email
     * 
     * @param User $user
     * @param array $stripeSubscription
     * @return void
     */
    protected function checkForSubscriptionExpiration(User $user, array $stripeSubscription)
    {
        // Data de término do período atual
        $endDate = Carbon::createFromTimestamp($stripeSubscription['current_period_end']);
        $now = Carbon::now();
        
        // Calcular quantos dias faltam para o fim do período
        $daysLeft = $now->diffInDays($endDate);
        
        // Se faltar 3 dias ou menos, enviar email de notificação
        if ($daysLeft <= 3) {
            Log::info("Assinatura do usuário {$user->id} irá expirar em {$daysLeft} dias. Enviando email.");
            
            // Buscar a assinatura local
            $subscription = Subscription::where('user_id', $user->id)
                ->where('stripe_id', $stripeSubscription['id'])
                ->first();
                
            if ($subscription) {
                // Enviar o email usando a classe que criamos (será processado pela fila)
                Mail::to($user->email)
                    ->send(new \App\Mail\SubscriptionExpiringMail($user, $subscription, $daysLeft));
                
                Log::info("Email de expiração enfileirado para {$user->email}");
            } else {
                Log::warning("Não foi possível encontrar a assinatura local para o stripe_id: {$stripeSubscription['id']}");
            }
        }
    }
    
    /**
     * Get the user by Stripe ID.
     *
     * @param  string  $stripeId
     * @return \App\Models\User|null
     */
    protected function getUserByStripeId($stripeId)
    {
        return User::where('stripe_id', $stripeId)->first();
    }
} 