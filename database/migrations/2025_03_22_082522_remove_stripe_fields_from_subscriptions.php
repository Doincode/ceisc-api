<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Removemos os campos relacionados ao Stripe na tabela de assinaturas
        // Mas mantemos a estrutura da tabela para suportar sandbox
        
        // Nota: Para o ambiente de sandbox, poderemos usar esses campos
        // temporariamente durante os testes
        
        Schema::table('subscriptions', function (Blueprint $table) {
            // Remover os campos existentes relacionados ao Stripe
            if (Schema::hasColumn('subscriptions', 'stripe_id')) {
                $table->dropColumn('stripe_id');
            }
            
            if (Schema::hasColumn('subscriptions', 'stripe_status')) {
                $table->dropColumn('stripe_status');
            }
            
            if (Schema::hasColumn('subscriptions', 'stripe_price')) {
                $table->dropColumn('stripe_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Readicionar os campos caso seja necessário reverter
            $table->string('stripe_id')->nullable();
            $table->string('stripe_status')->nullable();
            $table->string('stripe_price')->nullable();
        });
    }
};
