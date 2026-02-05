<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration: Completar conquistas faltantes
 * 
 * Esta migration adiciona as conquistas que estão faltando no servidor hospedado.
 * Total esperado: 48 conquistas
 */

return new class
{
    public function up(): void
    {
        // Garantir que a categoria 'social' existe no enum
        try {
            Capsule::statement("ALTER TABLE achievements MODIFY COLUMN category ENUM('streak','financial','level','usage','premium','cards','milestone','special','social') DEFAULT 'usage'");
            echo "✓ Categoria 'social' adicionada ao enum\n";
        } catch (\Exception $e) {
            echo "• Enum já está correto\n";
        }

        // TODAS as 48 conquistas do sistema
        $achievements = [
            // ========== CONQUISTAS FREE (6) ==========
            ['code' => 'FIRST_LAUNCH', 'name' => 'Início', 'description' => 'Registre seu primeiro lançamento financeiro', 'icon' => '🎯', 'points_reward' => 20, 'category' => 'usage', 'plan_type' => 'free', 'sort_order' => 1],
            ['code' => 'STREAK_3', 'name' => '3 Dias Ativos', 'description' => 'Alcance 3 dias ativos com lançamentos', 'icon' => '🔥', 'points_reward' => 30, 'category' => 'streak', 'plan_type' => 'free', 'sort_order' => 2],
            ['code' => 'STREAK_7', 'name' => '7 Dias Ativos', 'description' => 'Alcance 7 dias ativos com lançamentos', 'icon' => '⚡', 'points_reward' => 50, 'category' => 'streak', 'plan_type' => 'free', 'sort_order' => 3],
            ['code' => 'DAYS_30_USING', 'name' => '30 Dias Usando', 'description' => 'Use o sistema por 30 dias', 'icon' => '📅', 'points_reward' => 100, 'category' => 'usage', 'plan_type' => 'free', 'sort_order' => 4],
            ['code' => 'TOTAL_10_LAUNCHES', 'name' => '10 Lançamentos', 'description' => 'Registre 10 lançamentos no total', 'icon' => '📊', 'points_reward' => 30, 'category' => 'usage', 'plan_type' => 'free', 'sort_order' => 5],
            ['code' => 'TOTAL_5_CATEGORIES', 'name' => '5 Categorias', 'description' => 'Crie 5 categorias personalizadas', 'icon' => '🎨', 'points_reward' => 25, 'category' => 'usage', 'plan_type' => 'free', 'sort_order' => 6],
            ['code' => 'PROFILE_COMPLETE', 'name' => 'Perfil Completo', 'description' => 'Complete todas as informações do seu perfil', 'icon' => '👤', 'points_reward' => 50, 'category' => 'usage', 'plan_type' => 'free', 'sort_order' => 7],

            // ========== CONQUISTAS COMUNS - ALL (3) ==========
            ['code' => 'POSITIVE_MONTH', 'name' => 'Mês Vitorioso', 'description' => 'Finalize um mês com saldo positivo', 'icon' => '💰', 'points_reward' => 75, 'category' => 'financial', 'plan_type' => 'all', 'sort_order' => 10],
            ['code' => 'TOTAL_100_LAUNCHES', 'name' => 'Centenário', 'description' => 'Registre 100 lançamentos no total', 'icon' => '💯', 'points_reward' => 150, 'category' => 'usage', 'plan_type' => 'all', 'sort_order' => 11],
            ['code' => 'LEVEL_5', 'name' => 'Expert Financeiro', 'description' => 'Alcance o nível 5', 'icon' => '🎓', 'points_reward' => 200, 'category' => 'level', 'plan_type' => 'all', 'sort_order' => 12],

            // ========== CONQUISTAS PRO (6) ==========
            ['code' => 'PREMIUM_USER', 'name' => 'Usuário Premium', 'description' => 'Torne-se um assinante Pro', 'icon' => '⭐', 'points_reward' => 100, 'category' => 'premium', 'plan_type' => 'pro', 'sort_order' => 20],
            ['code' => 'MASTER_ORGANIZATION', 'name' => 'Mestre da Organização', 'description' => 'Tenha 50+ lançamentos categorizados corretamente', 'icon' => '👑', 'points_reward' => 200, 'category' => 'usage', 'plan_type' => 'pro', 'sort_order' => 21],
            ['code' => 'ECONOMIST_MASTER', 'name' => 'Economista Nato', 'description' => 'Economize 25% da receita em um mês', 'icon' => '💎', 'points_reward' => 250, 'category' => 'financial', 'plan_type' => 'pro', 'sort_order' => 22],
            ['code' => 'CONSISTENCY_TOTAL', 'name' => 'Consistência Total', 'description' => 'Alcance 30 dias ativos com lançamentos', 'icon' => '🏆', 'points_reward' => 300, 'category' => 'streak', 'plan_type' => 'pro', 'sort_order' => 23],
            ['code' => 'META_ACHIEVED', 'name' => 'Meta Batida', 'description' => 'Bata uma meta financeira', 'icon' => '🎖️', 'points_reward' => 150, 'category' => 'financial', 'plan_type' => 'pro', 'sort_order' => 24],
            ['code' => 'LEVEL_8', 'name' => 'Nível 8', 'description' => 'Alcance o nível 8', 'icon' => '🌟', 'points_reward' => 500, 'category' => 'level', 'plan_type' => 'pro', 'sort_order' => 25],

            // ========== LANÇAMENTOS (3) ==========
            ['code' => 'TOTAL_250_LAUNCHES', 'name' => 'Produtivo', 'description' => 'Registre 250 lançamentos no total', 'icon' => '📝', 'points_reward' => 200, 'category' => 'usage', 'plan_type' => 'all', 'sort_order' => 30],
            ['code' => 'TOTAL_500_LAUNCHES', 'name' => 'Historiador', 'description' => 'Registre 500 lançamentos no total', 'icon' => '📚', 'points_reward' => 350, 'category' => 'usage', 'plan_type' => 'all', 'sort_order' => 31],
            ['code' => 'TOTAL_1000_LAUNCHES', 'name' => 'Arquivista', 'description' => 'Registre 1.000 lançamentos no total', 'icon' => '🏛️', 'points_reward' => 750, 'category' => 'usage', 'plan_type' => 'pro', 'sort_order' => 32],

            // ========== DIAS ATIVOS (3) ==========
            ['code' => 'DAYS_50_ACTIVE', 'name' => 'Dedicado', 'description' => 'Alcance 50 dias ativos com lançamentos', 'icon' => '🌟', 'points_reward' => 100, 'category' => 'streak', 'plan_type' => 'all', 'sort_order' => 33],
            ['code' => 'DAYS_100_ACTIVE', 'name' => 'Comprometido', 'description' => 'Alcance 100 dias ativos com lançamentos', 'icon' => '💫', 'points_reward' => 250, 'category' => 'streak', 'plan_type' => 'all', 'sort_order' => 34],
            ['code' => 'DAYS_365_ACTIVE', 'name' => 'Veterano Anual', 'description' => 'Alcance 365 dias ativos (1 ano de dedicação!)', 'icon' => '🌠', 'points_reward' => 1000, 'category' => 'streak', 'plan_type' => 'pro', 'sort_order' => 35],

            // ========== ECONOMIA (6) ==========
            ['code' => 'SAVER_10', 'name' => 'Poupador', 'description' => 'Economize 10% da receita em um mês', 'icon' => '💵', 'points_reward' => 50, 'category' => 'financial', 'plan_type' => 'all', 'sort_order' => 36],
            ['code' => 'SAVER_20', 'name' => 'Investidor', 'description' => 'Economize 20% da receita em um mês', 'icon' => '💰', 'points_reward' => 100, 'category' => 'financial', 'plan_type' => 'all', 'sort_order' => 37],
            ['code' => 'SAVER_30', 'name' => 'Milionário', 'description' => 'Economize 30% da receita em um mês', 'icon' => '🏦', 'points_reward' => 200, 'category' => 'financial', 'plan_type' => 'pro', 'sort_order' => 38],
            ['code' => 'POSITIVE_3_MONTHS', 'name' => 'Consistente', 'description' => '3 meses seguidos com saldo positivo', 'icon' => '📈', 'points_reward' => 150, 'category' => 'financial', 'plan_type' => 'all', 'sort_order' => 39],
            ['code' => 'POSITIVE_6_MONTHS', 'name' => 'Focado', 'description' => '6 meses seguidos com saldo positivo', 'icon' => '🎯', 'points_reward' => 300, 'category' => 'financial', 'plan_type' => 'pro', 'sort_order' => 40],
            ['code' => 'POSITIVE_12_MONTHS', 'name' => 'Imbatível', 'description' => '12 meses seguidos com saldo positivo', 'icon' => '🏅', 'points_reward' => 600, 'category' => 'financial', 'plan_type' => 'pro', 'sort_order' => 41],

            // ========== ORGANIZAÇÃO (3) ==========
            ['code' => 'TOTAL_15_CATEGORIES', 'name' => 'Categorizador', 'description' => 'Crie 15 categorias personalizadas', 'icon' => '🗂️', 'points_reward' => 50, 'category' => 'usage', 'plan_type' => 'all', 'sort_order' => 42],
            ['code' => 'TOTAL_25_CATEGORIES', 'name' => 'Organizador Master', 'description' => 'Crie 25 categorias personalizadas', 'icon' => '📁', 'points_reward' => 100, 'category' => 'usage', 'plan_type' => 'all', 'sort_order' => 43],
            ['code' => 'PERFECTIONIST', 'name' => 'Perfeccionista', 'description' => 'Categorize todas despesas em um mês', 'icon' => '✅', 'points_reward' => 75, 'category' => 'usage', 'plan_type' => 'all', 'sort_order' => 44],

            // ========== CARTÕES (3) ==========
            ['code' => 'FIRST_CARD', 'name' => 'Primeiro Cartão', 'description' => 'Cadastre seu primeiro cartão de crédito', 'icon' => '💳', 'points_reward' => 30, 'category' => 'cards', 'plan_type' => 'all', 'sort_order' => 45],
            ['code' => 'FIRST_INVOICE_PAID', 'name' => 'Fatura Paga', 'description' => 'Pague sua primeira fatura de cartão', 'icon' => '🧾', 'points_reward' => 50, 'category' => 'cards', 'plan_type' => 'all', 'sort_order' => 46],
            ['code' => 'INVOICES_12_PAID', 'name' => 'Controle Total', 'description' => 'Pague 12 faturas de cartão no ano', 'icon' => '📆', 'points_reward' => 300, 'category' => 'cards', 'plan_type' => 'pro', 'sort_order' => 47],

            // ========== TEMPO DE USO (2) ==========
            ['code' => 'ANNIVERSARY_1_YEAR', 'name' => 'Aniversário', 'description' => 'Complete 1 ano usando o Lukrato', 'icon' => '🎂', 'points_reward' => 500, 'category' => 'milestone', 'plan_type' => 'all', 'sort_order' => 48],
            ['code' => 'ANNIVERSARY_2_YEARS', 'name' => 'Fiel', 'description' => 'Complete 2 anos usando o Lukrato', 'icon' => '🏅', 'points_reward' => 1000, 'category' => 'milestone', 'plan_type' => 'pro', 'sort_order' => 49],

            // ========== NÍVEIS (3) ==========
            ['code' => 'LEVEL_10', 'name' => 'Veterano', 'description' => 'Alcance o nível 10', 'icon' => '🎖️', 'points_reward' => 750, 'category' => 'level', 'plan_type' => 'all', 'sort_order' => 50],
            ['code' => 'LEVEL_12', 'name' => 'Guru Financeiro', 'description' => 'Alcance o nível 12', 'icon' => '🧙', 'points_reward' => 1000, 'category' => 'level', 'plan_type' => 'pro', 'sort_order' => 51],
            ['code' => 'LEVEL_15', 'name' => 'Imperador', 'description' => 'Alcance o nível máximo 15', 'icon' => '👑', 'points_reward' => 2000, 'category' => 'level', 'plan_type' => 'pro', 'sort_order' => 52],

            // ========== ESPECIAIS (6) ==========
            ['code' => 'EARLY_BIRD', 'name' => 'Madrugador', 'description' => 'Faça um lançamento antes das 6h da manhã', 'icon' => '🌅', 'points_reward' => 25, 'category' => 'special', 'plan_type' => 'all', 'sort_order' => 53],
            ['code' => 'NIGHT_OWL', 'name' => 'Coruja', 'description' => 'Faça um lançamento após as 23h', 'icon' => '🌙', 'points_reward' => 25, 'category' => 'special', 'plan_type' => 'all', 'sort_order' => 54],
            ['code' => 'CHRISTMAS', 'name' => 'Natalino', 'description' => 'Faça um lançamento no dia de Natal (25/12)', 'icon' => '🎄', 'points_reward' => 100, 'category' => 'special', 'plan_type' => 'all', 'sort_order' => 55],
            ['code' => 'NEW_YEAR', 'name' => 'Ano Novo', 'description' => 'Faça um lançamento no Ano Novo (01/01)', 'icon' => '🎆', 'points_reward' => 100, 'category' => 'special', 'plan_type' => 'all', 'sort_order' => 56],
            ['code' => 'WEEKEND_WARRIOR', 'name' => 'Guerreiro de Fim de Semana', 'description' => 'Faça 10 lançamentos em fins de semana', 'icon' => '⚔️', 'points_reward' => 50, 'category' => 'special', 'plan_type' => 'all', 'sort_order' => 57],
            ['code' => 'SPEED_DEMON', 'name' => 'Velocista', 'description' => 'Faça 5 lançamentos em um único dia', 'icon' => '🚀', 'points_reward' => 40, 'category' => 'special', 'plan_type' => 'all', 'sort_order' => 58],

            // ========== INDICAÇÃO/SOCIAL (4) ==========
            ['code' => 'FIRST_REFERRAL', 'name' => 'Primeira Indicação', 'description' => 'Indique seu primeiro amigo para o Lukrato', 'icon' => '🤝', 'points_reward' => 100, 'category' => 'social', 'plan_type' => 'free', 'sort_order' => 60],
            ['code' => 'REFERRALS_5', 'name' => 'Embaixador', 'description' => 'Indique 5 amigos para o Lukrato', 'icon' => '🌟', 'points_reward' => 250, 'category' => 'social', 'plan_type' => 'all', 'sort_order' => 61],
            ['code' => 'REFERRALS_10', 'name' => 'Evangelista', 'description' => 'Indique 10 amigos para o Lukrato', 'icon' => '📢', 'points_reward' => 500, 'category' => 'social', 'plan_type' => 'all', 'sort_order' => 62],
            ['code' => 'REFERRALS_25', 'name' => 'Influenciador', 'description' => 'Indique 25 amigos para o Lukrato', 'icon' => '👑', 'points_reward' => 1000, 'category' => 'social', 'plan_type' => 'pro', 'sort_order' => 63],
        ];

        echo "\n📊 Total de conquistas definidas: " . count($achievements) . "\n\n";

        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($achievements as $achievement) {
            $exists = Capsule::table('achievements')
                ->where('code', $achievement['code'])
                ->first();

            if (!$exists) {
                Capsule::table('achievements')->insert(array_merge($achievement, [
                    'active' => true,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]));
                $inserted++;
                echo "✅ INSERIDA: {$achievement['name']} ({$achievement['code']})\n";
            } else {
                $skipped++;
                echo "⏭️  Já existe: {$achievement['name']}\n";
            }
        }

        // Contar total no banco
        $totalNoBanco = Capsule::table('achievements')->count();

        echo "\n" . str_repeat('=', 50) . "\n";
        echo "📈 RESUMO DA MIGRATION\n";
        echo str_repeat('=', 50) . "\n";
        echo "   ✅ Inseridas: {$inserted}\n";
        echo "   ⏭️  Já existiam: {$skipped}\n";
        echo "   📊 Total no banco agora: {$totalNoBanco}\n";
        echo str_repeat('=', 50) . "\n";

        if ($totalNoBanco < 48) {
            echo "\n⚠️  ATENÇÃO: Ainda faltam conquistas! Esperado: 48, Atual: {$totalNoBanco}\n";
        } else {
            echo "\n✅ Todas as 48 conquistas estão no banco!\n";
        }
    }

    public function down(): void
    {
        echo "⚠️  Esta migration não remove conquistas automaticamente.\n";
    }
};
