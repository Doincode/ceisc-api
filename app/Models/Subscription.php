<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Exception;

/**
 * @OA\Schema(
 *     schema="Subscription",
 *     title="Assinatura",
 *     description="Modelo de assinatura de plano",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="ID único da assinatura",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="user_id",
 *         type="integer",
 *         description="ID do usuário",
 *         example=3
 *     ),
 *     @OA\Property(
 *         property="plan_id",
 *         type="integer",
 *         description="ID do plano",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         description="Status da assinatura",
 *         enum={"active", "canceled", "expired", "pending"},
 *         example="active"
 *     ),
 *     @OA\Property(
 *         property="start_date",
 *         type="string",
 *         format="date-time",
 *         description="Data de início da assinatura"
 *     ),
 *     @OA\Property(
 *         property="end_date",
 *         type="string",
 *         format="date-time",
 *         description="Data de término da assinatura"
 *     ),
 *     @OA\Property(
 *         property="canceled_at",
 *         type="string",
 *         format="date-time",
 *         description="Data de cancelamento",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="trial_ends_at",
 *         type="string",
 *         format="date-time",
 *         description="Data de término do período de teste",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="last_payment_date",
 *         type="string",
 *         format="date-time",
 *         description="Data do último pagamento",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="next_payment_date",
 *         type="string",
 *         format="date-time",
 *         description="Data do próximo pagamento",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="auto_renew",
 *         type="boolean",
 *         description="Indica se a assinatura renova automaticamente",
 *         example=true
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Data de criação do registro"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Data da última atualização do registro"
 *     ),
 *     @OA\Property(
 *         property="plan",
 *         type="object",
 *         description="Plano associado à assinatura",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Plano Premium"),
 *         @OA\Property(property="price", type="number", format="float", example=29.90)
 *     )
 * )
 */
class Subscription extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'subscriptions';

    protected $fillable = [
        'user_id',
        'plan_id',
        'status', // active, canceled, expired, pending
        'start_date',
        'end_date',
        'canceled_at',
        'trial_ends_at',
        'last_payment_date',
        'next_payment_date',
        'auto_renew',
        'payment_method',
        'quantity'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'canceled_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'last_payment_date' => 'datetime',
        'next_payment_date' => 'datetime',
        'auto_renew' => 'boolean',
    ];

    /**
     * Obter o usuário desta assinatura
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obter o plano desta assinatura
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Escopo para assinaturas ativas
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
                     ->where('end_date', '>', now());
    }

    /**
     * Escopo para assinaturas expiradas
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', 'active')
                     ->where('end_date', '<', now());
    }

    /**
     * Escopo para assinaturas que expiram em breve
     */
    public function scopeExpiringInDays(Builder $query, int $days): Builder
    {
        $date = Carbon::now()->addDays($days)->toDateString();
        return $query->where('status', 'active')
                     ->whereDate('end_date', $date);
    }

    /**
     * Verificar se a assinatura está ativa
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->end_date > now();
    }

    /**
     * Verificar se a assinatura está em período de teste
     */
    public function onTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at > now();
    }

    /**
     * Cancelar a assinatura
     */
    public function cancel(): void
    {
        $this->status = 'canceled';
        $this->canceled_at = now();
        $this->auto_renew = false;
        $this->save();
    }

    /**
     * Renovar a assinatura
     */
    public function renew(): void
    {
        // Calcula a nova data de término com base no ciclo de cobrança do plano
        $billingCycle = $this->plan->billing_cycle;
        
        // Usar match para código mais limpo
        $newEndDate = match ($billingCycle) {
            'mensal' => $this->end_date->copy()->addMonth(),
            'trimestral' => $this->end_date->copy()->addMonths(3),
            'anual' => $this->end_date->copy()->addYear(),
            default => $this->end_date->copy()->addMonth(),
        };

        $this->start_date = $this->end_date;
        $this->end_date = $newEndDate;
        $this->last_payment_date = now();
        $this->next_payment_date = $newEndDate;
        $this->status = 'active';
        $this->save();
    }

    /**
     * Calcular o preço com desconto
     */
    public function getDiscountedPrice(): float
    {
        if (!$this->plan) {
            return 0;
        }
        
        $price = $this->plan->price;
        $discount = $this->plan->discount_percentage;
        
        if ($discount > 0) {
            return $price * (1 - ($discount / 100));
        }
        
        return $price;
    }
} 