<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SubscriptionExpiredNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * A assinatura que expirou
     *
     * @var Subscription
     */
    public $subscription;

    /**
     * ID para rastreamento de logs
     * 
     * @var string
     */
    protected $logTrackingId;

    /**
     * Create a new message instance.
     *
     * @param  Subscription  $subscription
     * @return void
     */
    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
        $this->logTrackingId = uniqid('mail_');
        $this->connection = 'redis';
        $this->queue = 'emails';
        
        Log::info("Email de notificação de assinatura expirada criado", [
            'tracking_id' => $this->logTrackingId,
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'plan_id' => $subscription->plan_id
        ]);
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        Log::info("Preparando envelope do email", [
            'tracking_id' => $this->logTrackingId,
            'subscription_id' => $this->subscription->id,
            'subject' => 'Sua assinatura expirou - ' . config('app.name')
        ]);
        
        return new Envelope(
            subject: 'Sua assinatura expirou - ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        Log::info("Preparando conteúdo do email", [
            'tracking_id' => $this->logTrackingId,
            'subscription_id' => $this->subscription->id,
            'template' => 'emails.subscription.expired'
        ]);
        
        return new Content(
            view: 'emails.subscription.expired',
            with: [
                'userName' => $this->subscription->user->name,
                'planName' => $this->subscription->plan->name,
                'expirationDate' => $this->subscription->end_date->format('d/m/Y'),
                'renewUrl' => url('/renew-subscription/' . $this->subscription->id),
                'plansUrl' => url('/plans'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
    
    /**
     * Método chamado quando o email é enviado.
     * 
     * @param  \Illuminate\Mail\SentMessage  $message
     * @return void
     */
    public function sent($message)
    {
        Log::info("Email de notificação de assinatura expirada enviado com sucesso", [
            'tracking_id' => $this->logTrackingId,
            'subscription_id' => $this->subscription->id,
            'message_id' => $message->getMessageId() ?? 'unknown'
        ]);
    }
} 