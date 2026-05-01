<?php

declare(strict_types=1);

namespace Application\Services\Gamification;

use Application\Models\UserProgress;
use Application\Models\Usuario;
use Application\Models\Lancamento;
use Carbon\Carbon;

/**
 * Service: StreakService
 * 
 * Gerencia o sistema de dias ativos (streak) com proteção para usuários Pro
 * 
 * Regras:
 * - Conta o total de dias únicos em que o usuário fez lançamentos
 * - Não precisa ser consecutivo - qualquer dia com lançamento conta
 * - Usuário Pro possui bônus de pontos extras
 */
class StreakService
{
    /**
     * Atualizar streak do usuário após criar um lançamento
     * Agora conta dias únicos com lançamentos, não necessariamente consecutivos
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
                'message' => 'Atividade já registrada hoje',
            ];
        }

        // Incrementar o contador de dias ativos (não precisa ser consecutivo)
        $progress->current_streak++;

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
            'message' => "Dias ativos: {$progress->current_streak} 🔥",
        ];
    }

    /**
     * Recalcular streak baseado nos lançamentos reais do usuário
     * Conta dias únicos com pelo menos 1 lançamento
     * 
     * @param int $userId
     * @return array Resultado com streak recalculado
     */
    public function recalculateStreak(int $userId): array
    {
        // Contar dias únicos com lançamentos
        $uniqueDays = Lancamento::where('user_id', $userId)
            ->distinct()
            ->count('data');

        // Buscar última data de lançamento
        $lastLancamento = Lancamento::where('user_id', $userId)
            ->orderBy('data', 'desc')
            ->first();

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

        $progress->current_streak = $uniqueDays;

        // Atualizar best streak se necessário
        if ($uniqueDays > $progress->best_streak) {
            $progress->best_streak = $uniqueDays;
        }

        // Atualizar última atividade
        if ($lastLancamento) {
            $progress->last_activity_date = Carbon::parse($lastLancamento->data);
        }

        $progress->save();

        return [
            'success' => true,
            'streak' => $uniqueDays,
            'best_streak' => $progress->best_streak,
            'message' => "Streak recalculado: {$uniqueDays} dias únicos com lançamentos",
        ];
    }
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
            'protection_available' => $isPro, // Pro tem benefícios extras
            'protection_used_this_month' => false,
            'protection_date' => null,
        ];
    }

    /**
     * Verificar se usuário tem atividade recente
     * Útil para notificações de engajamento
     * 
     * @param int $userId
     * @param int $days Número de dias para verificar
     * @return bool True se não teve atividade nos últimos X dias
     */
    public function hasBeenInactive(int $userId, int $days = 7): bool
    {
        $progress = UserProgress::where('user_id', $userId)->first();

        if (!$progress || !$progress->last_activity_date) {
            return true;
        }

        $lastActivity = Carbon::parse($progress->last_activity_date);
        $today = Carbon::now()->startOfDay();
        $daysDifference = $lastActivity->diffInDays($today);

        return $daysDifference >= $days;
    }
}
