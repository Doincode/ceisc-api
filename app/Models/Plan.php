<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="Plan",
 *     title="Plano de Assinatura",
 *     description="Modelo de plano de assinatura",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="ID único do plano",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Nome do plano",
 *         example="Plano Premium"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         description="Descrição do plano",
 *         example="Acesso a todo o conteúdo com qualidade máxima"
 *     ),
 *     @OA\Property(
 *         property="price",
 *         type="number",
 *         format="float",
 *         description="Preço do plano",
 *         example=49.90
 *     ),
 *     @OA\Property(
 *         property="billing_cycle",
 *         type="string",
 *         description="Ciclo de cobrança",
 *         enum={"mensal", "trimestral", "anual"},
 *         example="mensal"
 *     ),
 *     @OA\Property(
 *         property="discount_percentage",
 *         type="number",
 *         format="float",
 *         description="Percentual de desconto",
 *         example=10
 *     ),
 *     @OA\Property(
 *         property="features",
 *         type="array",
 *         description="Características do plano",
 *         @OA\Items(type="string"),
 *         example={"Acesso a todo o catálogo", "Qualidade 4K", "4 telas simultâneas"}
 *     ),
 *     @OA\Property(
 *         property="is_active",
 *         type="boolean",
 *         description="Indica se o plano está ativo",
 *         example=true
 *     ),
 *     @OA\Property(
 *         property="stripe_product_id",
 *         type="string",
 *         description="ID do produto no Stripe",
 *         example="prod_RzuMd1ctiDAiYT"
 *     ),
 *     @OA\Property(
 *         property="stripe_price_id",
 *         type="string",
 *         description="ID do preço no Stripe",
 *         example="price_1R5uXrFAYBaQmiLav8gNcnfK"
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
 *     )
 * )
 */
class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'billing_cycle', // mensal, trimestral, anual
        'discount_percentage', // desconto para pagamento antecipado
        'features',
        'is_active',
        'stripe_product_id',
        'stripe_price_id'
    ];

    protected $casts = [
        'price' => 'float',
        'discount_percentage' => 'float',
        'features' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Obter todas as assinaturas deste plano
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
    
    /**
     * Calcula o preço com desconto
     */
    public function getDiscountedPrice(): float
    {
        if ($this->discount_percentage <= 0) {
            return $this->price;
        }
        
        return $this->price * (1 - ($this->discount_percentage / 100));
    }
    
    /**
     * Retorna o preço formatado em BRL
     */
    public function getFormattedPriceAttribute(): string
    {
        return 'R$ ' . number_format($this->price, 2, ',', '.');
    }
    
    /**
     * Retorna o preço com desconto formatado em BRL
     */
    public function getFormattedDiscountedPriceAttribute(): string
    {
        return 'R$ ' . number_format($this->getDiscountedPrice(), 2, ',', '.');
    }
} 