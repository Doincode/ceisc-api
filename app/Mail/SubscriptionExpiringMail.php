<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpiringMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Usuário para quem o email será enviado
     * 
     * @var User
     */
    public $user;

    /**
     * Assinatura que está expirando
     * 
     * @var Subscription
     */
    public $subscription;

    /**
     * Dias restantes para expiração
     * 
     * @var int
     */
    public $daysLeft;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, Subscription $subscription, int $daysLeft)
    {
        $this->user = $user;
        $this->subscription = $subscription;
        $this->daysLeft = $daysLeft;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Sua assinatura expira em {$this->daysLeft} dias",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.subscription.expiring',
            with: [
                'userName' => $this->user->name,
                'planName' => $this->subscription->plan->name,
                'expirationDate' => $this->subscription->end_date->format('d/m/Y'),
                'daysLeft' => $this->daysLeft,
                'renewUrl' => route('home'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
