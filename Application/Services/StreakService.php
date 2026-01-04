<?php

namespace Application\Services;

use Application\Models\UserProgress;
use Application\Models\Usuario;
use Carbon\Carbon;

/**
 * Service: StreakService
 * 
 * Gerencia o sistema de dias consecutivos (streak) com proteção para usuários Pro
 * 
 * Regras:
 * - Incrementa se o usuário criar ao menos 1 lançamento no dia
 * - Se ficar 1 dia sem lançar → streak volta para 1
 * - Usuário Pro possui 1 dia de proteção por mês
 */
class StreakService
{
    /**
     * Atualizar streak do usuário após criar um lançamento
     * 
     * @param int $userId
     * @param Carbon|null $date Data do lançamento (padrão: hoje)
     * @return array Resultado com streak atualizado
     */
    public function updateStreak(int $userId, ?Carbon $date = null): array
    {
        $date = $date ?? Carbon::now();
        $progress = UserProgress::firstOrCreate(
            ['user_id' => $userId],
            [
                'total_points' => 0,
                'current_level' => 1,
                'points_to_next_level' => 300,
                'current_streak' => 0,
                'best_streak' => 0,
                'last_activity_date' => null,
            ]
        );

        $lastActivity = $progress->last_activity_date ? Carbon::parse($progress->last_activity_date) : null;
        $today = $date->startOfDay();

        // Se já registrou atividade hoje, não fazer nada
        if ($lastActivity && $lastActivity->isSameDay($today)) {
            return [
                'success' => true,
                'streak' => $progress->current_streak,
                'best_streak' => $progress->best_streak,
                'updated' => false,
                'message' => 'Streak já registrado hoje',
            ];
        }

        $wasConsecutive = false;
        $usedProtection = false;

        // Verificar se é o primeiro dia ou se é consecutivo
        if (!$lastActivity) {
            // Primeiro dia
            $progress->current_streak = 1;
            $wasConsecutive = false;
        } else {
            $daysDifference = $lastActivity->diffInDays($today);

            if ($daysDifference === 1) {
                // Consecutivo
                $progress->current_streak++;
                $wasConsecutive = true;
            } elseif ($daysDifference === 2) {
                // 1 dia de diferença - verificar proteção Pro
                $user = Usuario::find($userId);
                $isPro = $user ? $user->isPro() : false;

                if ($isPro && $this->canUseStreakProtection($progress)) {
                    // Usar proteção
                    $this->useStreakProtection($progress);
                    $progress->current_streak++;
                    $wasConsecutive = true;
                    $usedProtection = true;
                } else {
                    // Perdeu o streak
                    $progress->current_streak = 1;
                }
            } else {
                // Perdeu o streak
                $progress->current_streak = 1;
            }
        }

        // Atualizar best streak
        if ($progress->current_streak > $progress->best_streak) {
            $progress->best_streak = $progress->current_streak;
        }

        $progress->last_activity_date = $today;
        $progress->save();

        return [
            'success' => true,
            'streak' => $progress->current_streak,
            'best_streak' => $progress->best_streak,
            'updated' => true,
            'was_consecutive' => $wasConsecutive,
            'used_protection' => $usedProtection,
            'message' => $usedProtection
                ? "Proteção Pro usada! Streak mantido em {$progress->current_streak} dias 🛡️"
                : "Streak atualizado para {$progress->current_streak} dias 🔥",
        ];
    }

    /**
     * Verificar se o usuário pode usar a proteção de streak este mês
     * 
     * @param UserProgress $progress
     * @return bool
     */
    private function canUseStreakProtection(UserProgress $progress): bool
    {
        $currentMonth = Carbon::now()->format('Y-m');

        // Se nunca usou ou usou em outro mês
        if (!$progress->streak_freeze_month || $progress->streak_freeze_month !== $currentMonth) {
            return true;
        }

        return false;
    }

    /**
     * Usar a proteção de streak (1x por mês para Pro)
     * 
     * @param UserProgress $progress
     * @return void
     */
    private function useStreakProtection(UserProgress $progress): void
    {
        $now = Carbon::now();
        $progress->streak_freeze_used_this_month = true;
        $progress->streak_freeze_date = $now;
        $progress->streak_freeze_month = $now->format('Y-m');
    }

    /**
     * Resetar proteção de streak no início do mês (executar via cron)
     * 
     * @return int Quantidade de usuários resetados
     */
    public function resetMonthlyProtections(): int
    {
        $currentMonth = Carbon::now()->format('Y-m');

        $count = UserProgress::where('streak_freeze_month', '!=', $currentMonth)
            ->where('streak_freeze_used_this_month', true)
            ->update([
                'streak_freeze_used_this_month' => false,
            ]);

        return $count;
    }

    /**
     * Obter informações de streak de um usuário
     * 
     * @param int $userId
     * @return array
     */
    public function getStreakInfo(int $userId): array
    {
        $progress = UserProgress::where('user_id', $userId)->first();

        if (!$progress) {
            return [
                'current_streak' => 0,
                'best_streak' => 0,
                'last_activity' => null,
                'protection_available' => false,
                'protection_used_this_month' => false,
            ];
        }

        $user = Usuario::find($userId);
        $isPro = $user ? $user->isPro() : false;

        return [
            'current_streak' => $progress->current_streak,
            'best_streak' => $progress->best_streak,
            'last_activity' => $progress->last_activity_date?->format('Y-m-d'),
            'protection_available' => $isPro && $this->canUseStreakProtection($progress),
            'protection_used_this_month' => $progress->streak_freeze_used_this_month ?? false,
            'protection_date' => $progress->streak_freeze_date?->format('Y-m-d'),
        ];
    }

    /**
     * Verificar streaks pendentes (se usuário perdeu o streak)
     * Útil para notificações
     * 
     * @param int $userId
     * @return bool True se perdeu o streak
     */
    public function hasLostStreak(int $userId): bool
    {
        $progress = UserProgress::where('user_id', $userId)->first();

        if (!$progress || !$progress->last_activity_date) {
            return false;
        }

        $lastActivity = Carbon::parse($progress->last_activity_date);
        $today = Carbon::now()->startOfDay();
        $daysDifference = $lastActivity->diffInDays($today);

        // Se passou mais de 2 dias (considerando proteção Pro)
        return $daysDifference > 2;
    }
}
