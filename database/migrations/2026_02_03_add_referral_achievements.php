<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration: Adiciona conquistas de indicação
 */

return new class
{
    public function up(): void
    {
        // Primeiro, alterar ENUM para incluir 'social'
        Capsule::statement("ALTER TABLE achievements MODIFY COLUMN category ENUM('streak','financial','level','usage','premium','cards','milestone','special','social') DEFAULT 'usage'");

        $achievements = [
            [
                'code' => 'FIRST_REFERRAL',
                'name' => 'Boca a Boca',
                'description' => 'Fez sua primeira indicação',
                'icon' => '🗣️',
                'points_reward' => 100,
                'category' => 'social',
                'plan_type' => 'free',
                'sort_order' => 80,
                'active' => true,
            ],
            [
                'code' => 'REFERRALS_5',
                'name' => 'Amigo da Vizinhança',
                'description' => 'Indicou 5 amigos para o Lukrato',
                'icon' => '👥',
                'points_reward' => 250,
                'category' => 'social',
                'plan_type' => 'free',
                'sort_order' => 81,
                'active' => true,
            ],
            [
                'code' => 'REFERRALS_10',
                'name' => 'Embaixador',
                'description' => 'Indicou 10 amigos para o Lukrato',
                'icon' => '🎖️',
                'points_reward' => 500,
                'category' => 'social',
                'plan_type' => 'free',
                'sort_order' => 82,
                'active' => true,
            ],
            [
                'code' => 'REFERRALS_25',
                'name' => 'Influenciador',
                'description' => 'Indicou 25 amigos para o Lukrato',
                'icon' => '🌟',
                'points_reward' => 1000,
                'category' => 'social',
                'plan_type' => 'pro',
                'sort_order' => 83,
                'active' => true,
            ],
        ];

        foreach ($achievements as $achievement) {
            // Verifica se já existe
            $exists = Capsule::table('achievements')
                ->where('code', $achievement['code'])
                ->exists();

            if (!$exists) {
                Capsule::table('achievements')->insert(array_merge($achievement, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    public function down(): void
    {
        $codes = ['FIRST_REFERRAL', 'REFERRALS_5', 'REFERRALS_10', 'REFERRALS_25'];

        Capsule::table('achievements')
            ->whereIn('code', $codes)
            ->delete();
    }
};
