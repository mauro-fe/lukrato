<?php

/**
 * Migration: Atualizar ícones das conquistas de emojis para nomes Lucide
 * 
 * Substitui emojis na coluna `icon` da tabela achievements por nomes de ícones Lucide
 */

use Illuminate\Database\Capsule\Manager as DB;

return new class {
    public function up(): void
    {
        $iconMap = [
            // FREE
            'FIRST_LAUNCH'       => 'target',
            'STREAK_3'           => 'flame',
            'STREAK_7'           => 'zap',
            'DAYS_30_USING'      => 'calendar',
            'TOTAL_10_LAUNCHES'  => 'bar-chart-3',
            'TOTAL_5_CATEGORIES' => 'palette',
            'PROFILE_COMPLETE'   => 'user-check',

            // ALL
            'POSITIVE_MONTH'     => 'coins',
            'TOTAL_100_LAUNCHES' => 'hash',
            'LEVEL_5'            => 'graduation-cap',

            // PRO
            'PREMIUM_USER'       => 'star',
            'MASTER_ORGANIZATION' => 'crown',
            'ECONOMIST_MASTER'   => 'gem',
            'CONSISTENCY_TOTAL'  => 'trophy',
            'META_ACHIEVED'      => 'award',
            'LEVEL_8'            => 'sparkles',

            // Lançamentos
            'TOTAL_250_LAUNCHES' => 'file-text',
            'TOTAL_500_LAUNCHES' => 'library',
            'TOTAL_1000_LAUNCHES' => 'landmark',

            // Dias ativos
            'DAYS_50_ACTIVE'     => 'sparkles',
            'DAYS_100_ACTIVE'    => 'sparkle',
            'DAYS_365_ACTIVE'    => 'orbit',

            // Economia
            'SAVER_10'           => 'banknote',
            'SAVER_20'           => 'piggy-bank',
            'SAVER_30'           => 'building-2',
            'POSITIVE_3_MONTHS'  => 'trending-up',
            'POSITIVE_6_MONTHS'  => 'crosshair',
            'POSITIVE_12_MONTHS' => 'medal',

            // Organização
            'TOTAL_15_CATEGORIES' => 'folder-open',
            'TOTAL_25_CATEGORIES' => 'folders',
            'PERFECTIONIST'      => 'check-circle',

            // Cartões
            'FIRST_CARD'         => 'credit-card',
            'FIRST_INVOICE_PAID' => 'receipt',
            'INVOICES_12_PAID'   => 'calendar-check',

            // Tempo de uso
            'ANNIVERSARY_1_YEAR' => 'cake',
            'ANNIVERSARY_2_YEARS' => 'medal',

            // Níveis
            'LEVEL_10'           => 'shield-check',
            'LEVEL_12'           => 'wand-sparkles',
            'LEVEL_15'           => 'crown',

            // Especiais
            'EARLY_BIRD'         => 'sunrise',
            'NIGHT_OWL'          => 'moon',
            'CHRISTMAS'          => 'tree-pine',
            'NEW_YEAR'           => 'party-popper',
            'WEEKEND_WARRIOR'    => 'swords',
            'SPEED_DEMON'        => 'rocket',

            // Indicação
            'FIRST_REFERRAL'     => 'handshake',
            'REFERRALS_5'        => 'users',
            'REFERRALS_10'       => 'megaphone',
            'REFERRALS_25'       => 'crown',
        ];

        $updated = 0;
        foreach ($iconMap as $code => $lucideIcon) {
            $affected = DB::table('achievements')
                ->where('code', $code)
                ->update(['icon' => $lucideIcon]);
            $updated += $affected;
        }

        echo "  ✓ {$updated} conquistas atualizadas com ícones Lucide\n";
    }
};
