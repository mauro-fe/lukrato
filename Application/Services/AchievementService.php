<?php

namespace Application\Services;

use Application\Models\Achievement;
use Application\Models\UserAchievement;
use Application\Models\UserProgress;
use Application\Models\Usuario;
use Application\Models\Lancamento;
use Application\Models\Categoria;
use Application\Enums\AchievementType;
use Carbon\Carbon;

/**
 * Service: AchievementService
 * 
 * Gerencia verificação e desbloqueio de conquistas
 * Diferencia conquistas Free vs Pro
 */
class AchievementService
{
    private GamificationService $gamificationService;

    public function __construct()
    {
        $this->gamificationService = new GamificationService();
    }

    /**
     * Verificar e desbloquear conquistas automaticamente para um usuário
     * 
     * @param int $userId
     * @param string|null $context Contexto da verificação (opcional)
     * @return array Conquistas desbloqueadas
     */
    public function checkAndUnlockAchievements(int $userId, ?string $context = null): array
    {
        $user = Usuario::find($userId);
        if (!$user) {
            return [];
        }

        $unlockedNow = [];

        // Obter conquistas disponíveis para o plano do usuário
        $isPro = $user->isPro();
        $planType = $isPro ? 'pro' : 'free';

        $availableAchievements = Achievement::active()
            ->where(function ($q) use ($planType) {
                $q->where('plan_type', $planType)
                    ->orWhere('plan_type', 'all');
            })
            ->get();

        foreach ($availableAchievements as $achievement) {
            // Pular se já desbloqueou
            if ($this->hasUnlocked($userId, $achievement->code)) {
                continue;
            }

            // Verificar se pode desbloquear
            if ($this->canUnlock($userId, $achievement->code, $user)) {
                $result = $this->unlockAchievement($userId, $achievement->id);
                if ($result['success']) {
                    $unlockedNow[] = $result['achievement'];
                }
            }
        }

        return $unlockedNow;
    }

    /**
     * Verificar se usuário já desbloqueou uma conquista
     * 
     * @param int $userId
     * @param string $achievementCode
     * @return bool
     */
    public function hasUnlocked(int $userId, string $achievementCode): bool
    {
        $achievement = Achievement::where('code', $achievementCode)->first();
        if (!$achievement) {
            return false;
        }

        return UserAchievement::where('user_id', $userId)
            ->where('achievement_id', $achievement->id)
            ->exists();
    }

    /**
     * Verificar se usuário pode desbloquear uma conquista específica
     * 
     * @param int $userId
     * @param string $achievementCode
     * @param Usuario|null $user
     * @return bool
     */
    private function canUnlock(int $userId, string $achievementCode, ?Usuario $user = null): bool
    {
        $user = $user ?? Usuario::find($userId);
        if (!$user) {
            return false;
        }

        $progress = UserProgress::where('user_id', $userId)->first();

        try {
            $type = AchievementType::from($achievementCode);
        } catch (\ValueError $e) {
            return false;
        }

        return match ($type) {
            // ===== FREE =====
            AchievementType::FIRST_LAUNCH => $this->checkFirstLaunch($userId),
            AchievementType::STREAK_3 => $this->checkStreak($progress, 3),
            AchievementType::STREAK_7 => $this->checkStreak($progress, 7),
            AchievementType::DAYS_30_USING => $this->checkDaysUsing($user, 30),
            AchievementType::FIRST_META => $this->checkFirstMeta($userId),
            AchievementType::TOTAL_10_LAUNCHES => $this->checkTotalLaunches($userId, 10),
            AchievementType::TOTAL_5_CATEGORIES => $this->checkTotalCategories($userId, 5),

            // ===== PRO =====
            AchievementType::PREMIUM_USER => $user->isPro(),
            AchievementType::MASTER_ORGANIZATION => $this->checkMasterOrganization($userId, $user),
            AchievementType::ECONOMIST_MASTER => $this->checkEconomistMaster($userId, $user),
            AchievementType::CONSISTENCY_TOTAL => $this->checkStreak($progress, 30) && $user->isPro(),
            AchievementType::META_ACHIEVED => $this->checkMetaAchieved($userId, $user),
            AchievementType::LEVEL_8 => $this->checkLevel($progress, 8) && $user->isPro(),

            // ===== COMUNS =====
            AchievementType::POSITIVE_MONTH => $this->checkPositiveMonth($userId),
            AchievementType::TOTAL_100_LAUNCHES => $this->checkTotalLaunches($userId, 100),
            AchievementType::LEVEL_5 => $this->checkLevel($progress, 5),

            default => false,
        };
    }

    /**
     * Desbloquear conquista para usuário
     * 
     * @param int $userId
     * @param int $achievementId
     * @return array
     */
    public function unlockAchievement(int $userId, int $achievementId): array
    {
        $achievement = Achievement::find($achievementId);
        if (!$achievement) {
            return ['success' => false, 'message' => 'Conquista não encontrada'];
        }

        // Verificar se já desbloqueou
        $existing = UserAchievement::where('user_id', $userId)
            ->where('achievement_id', $achievementId)
            ->first();

        if ($existing) {
            return ['success' => false, 'message' => 'Conquista já desbloqueada'];
        }

        // Desbloquear
        $userAchievement = UserAchievement::create([
            'user_id' => $userId,
            'achievement_id' => $achievementId,
            'unlocked_at' => Carbon::now(),
            'notification_seen' => false,
        ]);

        // Adicionar pontos de bônus
        if ($achievement->points_reward > 0) {
            $progress = UserProgress::where('user_id', $userId)->first();
            if ($progress) {
                $progress->total_points += $achievement->points_reward;
                $progress->save();
            }
        }

        return [
            'success' => true,
            'message' => 'Conquista desbloqueada!',
            'achievement' => [
                'id' => $achievement->id,
                'code' => $achievement->code,
                'name' => $achievement->name,
                'description' => $achievement->description,
                'icon' => $achievement->icon,
                'points_reward' => $achievement->points_reward,
            ],
        ];
    }

    /**
     * Listar conquistas do usuário com status
     * 
     * @param int $userId
     * @return array
     */
    public function getUserAchievements(int $userId): array
    {
        $user = Usuario::find($userId);
        if (!$user) {
            return [];
        }

        $isPro = $user->isPro();
        $planType = $isPro ? 'pro' : 'free';

        // Buscar todas as conquistas disponíveis
        $achievements = Achievement::active()
            ->where(function ($q) use ($planType) {
                $q->where('plan_type', $planType)
                    ->orWhere('plan_type', 'all');
            })
            ->orderBy('sort_order')
            ->get();

        $result = [];

        foreach ($achievements as $achievement) {
            $unlocked = UserAchievement::where('user_id', $userId)
                ->where('achievement_id', $achievement->id)
                ->first();

            $result[] = [
                'id' => $achievement->id,
                'code' => $achievement->code,
                'name' => $achievement->name,
                'description' => $achievement->description,
                'icon' => $achievement->icon,
                'points_reward' => $achievement->points_reward,
                'category' => $achievement->category,
                'plan_type' => $achievement->plan_type,
                'is_pro_only' => $achievement->plan_type === 'pro',
                'unlocked' => (bool)$unlocked,
                'unlocked_at' => $unlocked?->unlocked_at?->format('Y-m-d H:i:s'),
                'can_unlock' => !$unlocked && $this->canUnlock($userId, $achievement->code, $user),
            ];
        }

        return $result;
    }

    // ===== MÉTODOS DE VERIFICAÇÃO =====

    private function checkFirstLaunch(int $userId): bool
    {
        return Lancamento::where('user_id', $userId)->count() >= 1;
    }

    private function checkStreak(?UserProgress $progress, int $days): bool
    {
        return $progress && $progress->current_streak >= $days;
    }

    private function checkDaysUsing(Usuario $user, int $days): bool
    {
        $createdAt = Carbon::parse($user->created_at);
        return $createdAt->diffInDays(Carbon::now()) >= $days;
    }

    private function checkFirstMeta(int $userId): bool
    {
        // TODO: Implementar quando houver tabela de metas
        // return Meta::where('user_id', $userId)->count() >= 1;
        return false;
    }

    private function checkTotalLaunches(int $userId, int $total): bool
    {
        return Lancamento::where('user_id', $userId)->count() >= $total;
    }

    private function checkTotalCategories(int $userId, int $total): bool
    {
        return Categoria::where('user_id', $userId)->count() >= $total;
    }

    private function checkMasterOrganization(int $userId, Usuario $user): bool
    {
        if (!$user->isPro()) return false;

        // Critérios: 50+ lançamentos, 10+ categorias, streak de 7 dias
        $launches = Lancamento::where('user_id', $userId)->count();
        $categories = Categoria::where('user_id', $userId)->count();
        $progress = UserProgress::where('user_id', $userId)->first();

        return $launches >= 50 && $categories >= 10 && $progress && $progress->best_streak >= 7;
    }

    private function checkEconomistMaster(int $userId, Usuario $user): bool
    {
        if (!$user->isPro()) return false;

        // Critérios: 3 meses com saldo positivo
        // TODO: Implementar lógica de verificação de múltiplos meses
        return false;
    }

    private function checkMetaAchieved(int $userId, Usuario $user): bool
    {
        if (!$user->isPro()) return false;

        // TODO: Implementar quando houver tabela de metas
        return false;
    }

    private function checkLevel(?UserProgress $progress, int $level): bool
    {
        return $progress && $progress->current_level >= $level;
    }

    private function checkPositiveMonth(int $userId): bool
    {
        $currentMonth = Carbon::now()->format('Y-m');

        $receitas = Lancamento::where('user_id', $userId)
            ->where('tipo', 'receita')
            ->whereRaw("DATE_FORMAT(data, '%Y-%m') = ?", [$currentMonth])
            ->sum('valor');

        $despesas = Lancamento::where('user_id', $userId)
            ->where('tipo', 'despesa')
            ->whereRaw("DATE_FORMAT(data, '%Y-%m') = ?", [$currentMonth])
            ->sum('valor');

        return $receitas > $despesas;
    }
}
