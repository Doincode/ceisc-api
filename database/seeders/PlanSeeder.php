<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Plano Premium
        Plan::create([
            'name' => 'Premium',
            'description' => 'Acesso a todo o conteúdo com qualidade máxima',
            'price' => 49.90,
            'billing_cycle' => 'mensal',
            'discount_percentage' => 0,
            'features' => [
                'Acesso a todo o catálogo',
                'Qualidade 4K',
                '4 telas simultâneas',
                'Downloads para assistir offline'
            ],
            'is_active' => true
        ]);

        // Plano Premium Trimestral
        Plan::create([
            'name' => 'Premium Trimestral',
            'description' => 'Acesso a todo o conteúdo com qualidade máxima',
            'price' => 134.90,
            'billing_cycle' => 'trimestral',
            'discount_percentage' => 10,
            'features' => [
                'Acesso a todo o catálogo',
                'Qualidade 4K',
                '4 telas simultâneas',
                'Downloads para assistir offline'
            ],
            'is_active' => true
        ]);

        // Plano Premium Anual
        Plan::create([
            'name' => 'Premium Anual',
            'description' => 'Acesso a todo o conteúdo com qualidade máxima',
            'price' => 499.90,
            'billing_cycle' => 'anual',
            'discount_percentage' => 16.7,
            'features' => [
                'Acesso a todo o catálogo',
                'Qualidade 4K',
                '4 telas simultâneas',
                'Downloads para assistir offline'
            ],
            'is_active' => true
        ]);
    }
} 