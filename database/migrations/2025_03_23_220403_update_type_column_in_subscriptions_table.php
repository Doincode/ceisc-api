<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('type')->default('standard')->nullable()->change();
        });
        
        // Atualizar registros existentes para ter o tipo padrão
        DB::table('subscriptions')->whereNull('type')->update(['type' => 'standard']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('type')->nullable(false)->change();
        });
    }
};
