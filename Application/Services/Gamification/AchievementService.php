<?php

declare(strict_types=1);

namespace Application\Services\Gamification;

use Application\Models\Achievement;
use Application\Models\UserAchievement;
use Application\Models\UserProgress;
use Application\Models\Usuario;
use Application\Models\Lancamento;
use Application\Models\Categoria;
use Application\Models\CartaoCredito;
use Application\Models\Fatura;
use Application\Models\PointsLog;
use Application\Models\Meta;
use Illuminate\Database\Eloquent\Builder;

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
        \Application\Services\Infrastructure\LogService::safeErrorLog("🔍 [ACHIEVEMENT] Iniciando verificação para user_id: {$userId}, context: {$context}");

        $user = Usuario::find($userId);
        if (!$user) {
            \Application\Services\Infrastructure\LogService::safeErrorLog("❌ [ACHIEVEMENT] Usuário não encontrado: {$userId}");
            return [];
        }

        $unlockedNow = [];

        // Obter conquistas disponíveis para o plano do usuário
        // Pro: pode desbloquear todas (free + pro + all)
        // Free: pode desbloquear apenas free + all
        $isPro = $user->isPro();
        \Application\Services\Infrastructure\LogService::safeErrorLog("👤 [ACHIEVEMENT] Usuário é Pro? " . ($isPro ? 'Sim' : 'Não'));

        $availableQuery = Achievement::active();

        if (!$isPro) {
            // Usuário Free: apenas conquistas free e all
            $availableQuery->where(function ($q) {
                $q->where('plan_type', 'free')
                    ->orWhere('plan_type', 'all');
            });
        }
        // Usuário Pro: não filtra, pode desbloquear todas

        $availableAchievements = $availableQuery->get();
        \Application\Services\Infrastructure\LogService::safeErrorLog("📋 [ACHIEVEMENT] Total de conquistas disponíveis: " . $availableAchievements->count());

        foreach ($availableAchievements as $achievement) {
            // Pular se já desbloqueou
            if ($this->hasUnlocked($userId, $achievement->code)) {
                continue;
            }

            // Verificar se pode desbloquear
            $canUnlock = $this->canUnlock($userId, $achievement->code, $user);
            if ($canUnlock) {
                \Application\Services\Infrastructure\LogService::safeErrorLog("✅ [ACHIEVEMENT] Pode desbloquear: {$achievement->code}");
                $result = $this->unlockAchievement($userId, $achievement->id);
                if ($result['success']) {
                    $unlockedNow[] = $result['achievement'];
                    \Application\Services\Infrastructure\LogService::safeErrorLog("🎉 [ACHIEVEMENT] Desbloqueada: {$achievement->code}");
                }
            }
        }

        \Application\Services\Infrastructure\LogService::safeErrorLog("🏆 [ACHIEVEMENT] Total desbloqueadas: " . count($unlockedNow));
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

            // ===== NOVAS - LANÇAMENTOS =====
            AchievementType::TOTAL_250_LAUNCHES => $this->checkTotalLaunches($userId, 250),
            AchievementType::TOTAL_500_LAUNCHES => $this->checkTotalLaunches($userId, 500),
            AchievementType::TOTAL_1000_LAUNCHES => $this->checkTotalLaunches($userId, 1000) && $user->isPro(),

            // ===== NOVAS - DIAS ATIVOS =====
            AchievementType::DAYS_50_ACTIVE => $this->checkStreak($progress, 50),
            AchievementType::DAYS_100_ACTIVE => $this->checkStreak($progress, 100),
            AchievementType::DAYS_365_ACTIVE => $this->checkStreak($progress, 365) && $user->isPro(),

            // ===== NOVAS - ECONOMIA =====
            AchievementType::SAVER_10 => $this->checkSavingsPercentage($userId, 10),
            AchievementType::SAVER_20 => $this->checkSavingsPercentage($userId, 20),
            AchievementType::SAVER_30 => $this->checkSavingsPercentage($userId, 30) && $user->isPro(),
            AchievementType::POSITIVE_3_MONTHS => $this->checkConsecutivePositiveMonths($userId, 3),
            AchievementType::POSITIVE_6_MONTHS => $this->checkConsecutivePositiveMonths($userId, 6) && $user->isPro(),
            AchievementType::POSITIVE_12_MONTHS => $this->checkConsecutivePositiveMonths($userId, 12) && $user->isPro(),

            // ===== NOVAS - ORGANIZAÇÃO =====
            AchievementType::TOTAL_15_CATEGORIES => $this->checkTotalCategories($userId, 15),
            AchievementType::TOTAL_25_CATEGORIES => $this->checkTotalCategories($userId, 25),
            AchievementType::PERFECTIONIST => $this->checkPerfectionist($userId),

            // ===== NOVAS - CARTÕES =====
            AchievementType::FIRST_CARD => $this->checkFirstCard($userId),
            AchievementType::FIRST_INVOICE_PAID => $this->checkFirstInvoicePaid($userId),
            AchievementType::INVOICES_12_PAID => $this->checkInvoicesPaidInYear($userId, 12) && $user->isPro(),

            // ===== NOVAS - TEMPO DE USO =====
            AchievementType::ANNIVERSARY_1_YEAR => $this->checkDaysUsing($user, 365),
            AchievementType::ANNIVERSARY_2_YEARS => $this->checkDaysUsing($user, 730) && $user->isPro(),

            // ===== NOVAS - NÍVEIS =====
            AchievementType::LEVEL_10 => $this->checkLevel($progress, 10),
            AchievementType::LEVEL_12 => $this->checkLevel($progress, 12) && $user->isPro(),
            AchievementType::LEVEL_15 => $this->checkLevel($progress, 15) && $user->isPro(),

            // ===== NOVAS - ESPECIAIS =====
            AchievementType::EARLY_BIRD => $this->checkEarlyBird($userId),
            AchievementType::NIGHT_OWL => $this->checkNightOwl($userId),
            AchievementType::CHRISTMAS => $this->checkHoliday($userId, 12, 25),
            AchievementType::NEW_YEAR => $this->checkHoliday($userId, 1, 1),
            AchievementType::WEEKEND_WARRIOR => $this->checkWeekendWarrior($userId),
            AchievementType::SPEED_DEMON => $this->checkSpeedDemon($userId),

            // ===== PERFIL =====
            AchievementType::PROFILE_COMPLETE => $this->checkProfileComplete($user),

            // ===== INDICAÇÃO =====
            AchievementType::FIRST_REFERRAL => $this->checkReferrals($userId, 1),
            AchievementType::REFERRALS_5 => $this->checkReferrals($userId, 5),
            AchievementType::REFERRALS_10 => $this->checkReferrals($userId, 10),
            AchievementType::REFERRALS_25 => $this->checkReferrals($userId, 25),

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

                // 🐛 FIX: Registrar pontos no log para evitar divergências
                PointsLog::create([
                    'user_id' => $userId,
                    'action' => 'achievement_unlock',
                    'points' => $achievement->points_reward,
                    'description' => "Conquista desbloqueada: {$achievement->name}",
                    'metadata' => [
                        'achievement_code' => $achievement->code,
                        'achievement_id' => $achievement->id,
                    ],
                    'related_id' => $achievementId,
                    'related_type' => 'achievement',
                ]);

                // 🔄 RECALCULAR NÍVEL após adicionar pontos da conquista
                $gamificationService = new GamificationService();
                $gamificationService->recalculateLevel($userId);

                \Application\Services\Infrastructure\LogService::safeErrorLog("🏆 [ACHIEVEMENT] User {$userId} desbloqueou '{$achievement->name}' (+{$achievement->points_reward} pts)");
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
     * @param string|null $month Filtro por mês (formato YYYY-MM)
     * @return array
     */
    public function getUserAchievements(int $userId, ?string $month = null): array
    {
        $user = Usuario::find($userId);
        if (!$user) {
            return [];
        }

        $isPro = $user->isPro();

        // Buscar conquistas baseado no plano:
        // - Pro: vê TODAS as conquistas (free + pro + all)
        // - Free: vê apenas conquistas free + all
        $achievementsQuery = Achievement::active()->orderBy('sort_order');

        if (!$isPro) {
            // Usuário Free: apenas conquistas free e all
            $achievementsQuery->where(function ($q) {
                $q->where('plan_type', 'free')
                    ->orWhere('plan_type', 'all');
            });
        }
        // Usuário Pro: não filtra, vê todas

        $achievements = $achievementsQuery->get();

        $result = [];

        // Calcular intervalo do mês se fornecido
        $monthStart = null;
        $monthEnd = null;
        if ($month && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $monthEnd = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
        }

        foreach ($achievements as $achievement) {
            $unlockedQuery = UserAchievement::where('user_id', $userId)
                ->where('achievement_id', $achievement->id);

            // Se tiver filtro de mês, só considera desbloqueadas naquele mês
            if ($monthStart && $monthEnd) {
                $unlockedInMonth = (clone $unlockedQuery)
                    ->whereBetween('unlocked_at', [$monthStart, $monthEnd])
                    ->first();

                // Verifica se foi desbloqueada em qualquer momento (para o status geral)
                $unlockedEver = $unlockedQuery->first();

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
                    'unlocked' => (bool)$unlockedInMonth, // Desbloqueada neste mês
                    'unlocked_ever' => (bool)$unlockedEver, // Desbloqueada em algum momento
                    'unlocked_at' => $unlockedInMonth?->unlocked_at?->format('Y-m-d H:i:s'),
                    'can_unlock' => !$unlockedEver && $this->canUnlock($userId, $achievement->code, $user),
                    'progress' => !$unlockedEver ? $this->getAchievementProgress($userId, $achievement->code) : null,
                ];
            } else {
                $unlocked = $unlockedQuery->first();

                $item = [
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
                    'unlocked_ever' => (bool)$unlocked,
                    'unlocked_at' => $unlocked?->unlocked_at?->format('Y-m-d H:i:s'),
                    'can_unlock' => !$unlocked && $this->canUnlock($userId, $achievement->code, $user),
                ];

                if (!$unlocked) {
                    $progress = $this->getAchievementProgress($userId, $achievement->code);
                    if ($progress) {
                        $item['progress'] = $progress;
                    }
                }

                $result[] = $item;
            }
        }

        return $result;
    }

    // ===== PROGRESSO DE CONQUISTAS =====

    private function getAchievementProgress(int $userId, string $code): ?array
    {
        try {
            $type = AchievementType::from($code);
        } catch (\ValueError $e) {
            return null;
        }

        $progress = UserProgress::where('user_id', $userId)->first();
        $launches = null;
        $categories = null;

        return match ($type) {
            // Lançamentos
            AchievementType::FIRST_LAUNCH => $this->progressCount($userId, 'launches', 1),
            AchievementType::TOTAL_10_LAUNCHES => $this->progressCount($userId, 'launches', 10),
            AchievementType::TOTAL_100_LAUNCHES => $this->progressCount($userId, 'launches', 100),
            AchievementType::TOTAL_250_LAUNCHES => $this->progressCount($userId, 'launches', 250),
            AchievementType::TOTAL_500_LAUNCHES => $this->progressCount($userId, 'launches', 500),
            AchievementType::TOTAL_1000_LAUNCHES => $this->progressCount($userId, 'launches', 1000),

            // Streak / dias ativos
            AchievementType::STREAK_3 => ['current' => min($progress?->current_streak ?? 0, 3), 'target' => 3],
            AchievementType::STREAK_7 => ['current' => min($progress?->current_streak ?? 0, 7), 'target' => 7],
            AchievementType::DAYS_50_ACTIVE => ['current' => min($progress?->current_streak ?? 0, 50), 'target' => 50],
            AchievementType::DAYS_100_ACTIVE => ['current' => min($progress?->current_streak ?? 0, 100), 'target' => 100],
            AchievementType::DAYS_365_ACTIVE => ['current' => min($progress?->current_streak ?? 0, 365), 'target' => 365],
            AchievementType::CONSISTENCY_TOTAL => ['current' => min($progress?->current_streak ?? 0, 30), 'target' => 30],

            // Categorias
            AchievementType::TOTAL_5_CATEGORIES => $this->progressCount($userId, 'categories', 5),
            AchievementType::TOTAL_15_CATEGORIES => $this->progressCount($userId, 'categories', 15),
            AchievementType::TOTAL_25_CATEGORIES => $this->progressCount($userId, 'categories', 25),

            // Níveis
            AchievementType::LEVEL_5 => ['current' => min($progress?->current_level ?? 1, 5), 'target' => 5],
            AchievementType::LEVEL_8 => ['current' => min($progress?->current_level ?? 1, 8), 'target' => 8],
            AchievementType::LEVEL_10 => ['current' => min($progress?->current_level ?? 1, 10), 'target' => 10],
            AchievementType::LEVEL_12 => ['current' => min($progress?->current_level ?? 1, 12), 'target' => 12],
            AchievementType::LEVEL_15 => ['current' => min($progress?->current_level ?? 1, 15), 'target' => 15],

            // Referências
            AchievementType::FIRST_REFERRAL => $this->progressCount($userId, 'referrals', 1),
            AchievementType::REFERRALS_5 => $this->progressCount($userId, 'referrals', 5),
            AchievementType::REFERRALS_10 => $this->progressCount($userId, 'referrals', 10),
            AchievementType::REFERRALS_25 => $this->progressCount($userId, 'referrals', 25),

            default => null,
        };
    }

    private function progressCount(int $userId, string $type, int $target): array
    {
        $current = match ($type) {
            'launches' => Lancamento::where('user_id', $userId)->count(),
            'categories' => $this->countCustomCategories($userId),
            default => 0,
        };

        return ['current' => min($current, $target), 'target' => $target];
    }

    private function countCustomCategories(int $userId): int
    {
        return $this->customRootCategoriesQuery($userId)->count();
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
        // Verificar se a conta existe há pelo menos $days dias
        $createdAt = Carbon::parse($user->created_at);
        $accountAge = $createdAt->diffInDays(Carbon::now());
        if ($accountAge < $days) {
            return false;
        }

        // Para DAYS_30_USING, também verificar atividade real (pelo menos 5 dias com lançamentos)
        if ($days <= 30) {
            $activeDays = Lancamento::where('user_id', $user->id)
                ->selectRaw('DATE(data) as dia')
                ->groupBy('dia')
                ->get()
                ->count();
            return $activeDays >= 5;
        }

        return true;
    }

    private function checkFirstMeta(int $userId): bool
    {
        return Meta::where('user_id', $userId)->exists();
    }

    private function checkTotalLaunches(int $userId, int $total): bool
    {
        return Lancamento::where('user_id', $userId)->count() >= $total;
    }

    private function checkTotalCategories(int $userId, int $total): bool
    {
        return $this->countCustomCategories($userId) >= $total;
    }

    /**
     * Conta apenas categorias raiz realmente criadas pelo usuário.
     * Ignora categorias e subcategorias seedadas no cadastro.
     */
    private function customRootCategoriesQuery(int $userId)
    {
        return Categoria::where('user_id', $userId)
            ->whereNull('parent_id')
            ->where(function ($q) {
                $q->where('is_seeded', false)
                    ->orWhereNull('is_seeded');
            });
    }

    private function checkMasterOrganization(int $userId, Usuario $user): bool
    {
        if (!$user->isPro()) return false;

        // Critérios: 50+ lançamentos, 10+ categorias personalizadas, streak de 7 dias
        $launches = Lancamento::where('user_id', $userId)->count();
        // Usar mesma lógica de categorias personalizadas de checkTotalCategories
        $hasEnoughCategories = $this->checkTotalCategories($userId, 10);
        $progress = UserProgress::where('user_id', $userId)->first();

        return $launches >= 50 && $hasEnoughCategories && $progress && $progress->best_streak >= 7;
    }

    private function checkEconomistMaster(int $userId, Usuario $user): bool
    {
        if (!$user->isPro()) return false;

        // Critérios: economizar 25% da receita em pelo menos 3 meses
        // Reutiliza checkConsecutivePositiveMonths para 3 meses + checkSavingsPercentage para 25%
        return $this->checkConsecutivePositiveMonths($userId, 3) && $this->checkSavingsPercentage($userId, 25);
    }

    private function checkMetaAchieved(int $userId, Usuario $user): bool
    {
        if (!$user->isPro()) return false;

        // Verifica se o usuário tem ao menos 1 meta concluída
        return \Application\Models\Meta::where('user_id', $userId)
            ->where('status', 'concluida')
            ->exists();
    }

    private function checkLevel(?UserProgress $progress, int $level): bool
    {
        return $progress && $progress->current_level >= $level;
    }

    /**
     * Verifica se o usuário terminou algum mês com saldo positivo
     * IMPORTANTE: Só verifica meses FECHADOS (anteriores ao atual)
     * Critério: saldo final do mês > saldo inicial do mês
     */
    private function checkPositiveMonth(int $userId): bool
    {
        // Verificar os últimos 12 meses FECHADOS (excluindo o mês atual)
        for ($i = 1; $i <= 12; $i++) {
            $month = Carbon::now()->subMonths($i);
            $monthStr = $month->format('Y-m');
            $startOfMonth = $month->copy()->startOfMonth()->format('Y-m-d');
            $endOfMonth = $month->copy()->endOfMonth()->format('Y-m-d');

            // Precisa ter pelo menos 3 lançamentos no mês para validar
            $totalLancamentos = $this->lancamentosByMonthQuery($userId, $monthStr)->count();

            if ($totalLancamentos < 3) {
                continue;
            }

            // Calcular saldo inicial do mês (saldo acumulado até o dia anterior ao início)
            $dayBeforeMonth = $month->copy()->startOfMonth()->subDay()->format('Y-m-d');

            $saldoInicialReceitas = Lancamento::where('user_id', $userId)
                ->where('tipo', 'receita')
                ->where('data', '<=', $dayBeforeMonth)
                ->sum('valor');

            $saldoInicialDespesas = Lancamento::where('user_id', $userId)
                ->where('tipo', 'despesa')
                ->where('data', '<=', $dayBeforeMonth)
                ->sum('valor');

            $saldoInicial = $saldoInicialReceitas - $saldoInicialDespesas;

            // Calcular saldo final do mês (saldo acumulado até o fim do mês)
            $saldoFinalReceitas = Lancamento::where('user_id', $userId)
                ->where('tipo', 'receita')
                ->where('data', '<=', $endOfMonth)
                ->sum('valor');

            $saldoFinalDespesas = Lancamento::where('user_id', $userId)
                ->where('tipo', 'despesa')
                ->where('data', '<=', $endOfMonth)
                ->sum('valor');

            $saldoFinal = $saldoFinalReceitas - $saldoFinalDespesas;

            // Mês vitorioso: saldo final > saldo inicial
            if ($saldoFinal > $saldoInicial) {
                return true;
            }
        }

        return false;
    }

    // ===== NOVAS VERIFICAÇÕES - ECONOMIA =====

    /**
     * Verifica se o usuário economizou X% da receita em algum mês FECHADO
     * IMPORTANTE: Só verifica meses FECHADOS (anteriores ao atual)
     * Economia = (receitas - despesas) / receitas * 100
     */
    private function checkSavingsPercentage(int $userId, int $percentage): bool
    {
        // Verificar os últimos 12 meses FECHADOS (começando do mês anterior, excluindo atual)
        for ($i = 1; $i <= 12; $i++) {
            $month = Carbon::now()->subMonths($i)->format('Y-m');

            // Precisa ter pelo menos 5 lançamentos no mês para validar economia
            $totalLancamentos = $this->lancamentosByMonthQuery($userId, $month)->count();

            if ($totalLancamentos < 5) continue;

            $receitas = $this->lancamentosByMonthQuery($userId, $month)
                ->where('tipo', 'receita')
                ->sum('valor');

            if ($receitas <= 0) continue;

            $despesas = $this->lancamentosByMonthQuery($userId, $month)
                ->where('tipo', 'despesa')
                ->sum('valor');

            // Precisa ter despesas registradas para validar economia
            if ($despesas <= 0) continue;

            $savings = $receitas - $despesas;
            $savingsPercentage = ($savings / $receitas) * 100;

            if ($savingsPercentage >= $percentage) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica se o usuário teve X meses consecutivos com saldo positivo
     */
    private function checkConsecutivePositiveMonths(int $userId, int $months): bool
    {
        $consecutiveCount = 0;
        $maxConsecutive = 0;

        // Verificar os últimos 24 meses FECHADOS (excluindo o mês atual)
        for ($i = 1; $i <= 24; $i++) {
            $month = Carbon::now()->subMonths($i)->format('Y-m');

            $receitas = $this->lancamentosByMonthQuery($userId, $month)
                ->where('tipo', 'receita')
                ->sum('valor');

            $despesas = $this->lancamentosByMonthQuery($userId, $month)
                ->where('tipo', 'despesa')
                ->sum('valor');

            if ($receitas > $despesas) {
                $consecutiveCount++;
                $maxConsecutive = max($maxConsecutive, $consecutiveCount);
            } else {
                $consecutiveCount = 0;
            }
        }

        return $maxConsecutive >= $months;
    }

    // ===== NOVAS VERIFICAÇÕES - ORGANIZAÇÃO =====

    /**
     * Verifica se todas as despesas de um mês FECHADO estão categorizadas
     * IMPORTANTE: Só verifica meses FECHADOS (anteriores ao atual)
     * Critério: TODAS as despesas do mês devem ter categoria_id válido
     */
    private function checkPerfectionist(int $userId): bool
    {
        // Verificar os últimos 12 meses FECHADOS (excluindo o mês atual)
        for ($i = 1; $i <= 12; $i++) {
            $month = Carbon::now()->subMonths($i)->format('Y-m');

            $totalDespesas = $this->lancamentosByMonthQuery($userId, $month)
                ->where('tipo', 'despesa')
                ->count();

            // Precisa ter pelo menos 5 despesas no mês para validar
            if ($totalDespesas < 5) {
                continue;
            }

            $despesasSemCategoria = $this->lancamentosByMonthQuery($userId, $month)
                ->where('tipo', 'despesa')
                ->where(function ($q) {
                    $q->whereNull('categoria_id')
                        ->orWhere('categoria_id', 0);
                })
                ->count();

            // Se todas as despesas estão categorizadas neste mês
            if ($despesasSemCategoria === 0) {
                return true;
            }
        }

        return false;
    }

    // ===== NOVAS VERIFICAÇÕES - CARTÕES =====

    /**
     * Verifica se o usuário cadastrou pelo menos um cartão
     */
    private function checkFirstCard(int $userId): bool
    {
        return CartaoCredito::where('user_id', $userId)->count() >= 1;
    }

    /**
     * Verifica se o usuário pagou pelo menos uma fatura
     * (status = 'paga')
     */
    private function checkFirstInvoicePaid(int $userId): bool
    {
        $userCards = CartaoCredito::where('user_id', $userId)->pluck('id');

        \Application\Services\Infrastructure\LogService::safeErrorLog("🔍 [ACHIEVEMENT] checkFirstInvoicePaid - userId: {$userId}");
        \Application\Services\Infrastructure\LogService::safeErrorLog("🔍 [ACHIEVEMENT] checkFirstInvoicePaid - cartões do usuário: " . $userCards->count());

        if ($userCards->isEmpty()) {
            \Application\Services\Infrastructure\LogService::safeErrorLog("❌ [ACHIEVEMENT] checkFirstInvoicePaid - Usuário não tem cartões");
            return false;
        }

        $faturaPaga = Fatura::whereIn('cartao_credito_id', $userCards)
            ->where('status', 'paga')
            ->count();

        \Application\Services\Infrastructure\LogService::safeErrorLog("🔍 [ACHIEVEMENT] checkFirstInvoicePaid - faturas pagas: {$faturaPaga}");

        return $faturaPaga >= 1;
    }

    /**
     * Verifica se o usuário pagou X faturas no ano atual
     */
    private function checkInvoicesPaidInYear(int $userId, int $count): bool
    {
        $userCards = CartaoCredito::where('user_id', $userId)->pluck('id');

        if ($userCards->isEmpty()) {
            return false;
        }

        $currentYear = Carbon::now()->year;

        return Fatura::whereIn('cartao_credito_id', $userCards)
            ->where('status', 'paga')
            ->whereYear('updated_at', $currentYear)
            ->count() >= $count;
    }

    // ===== NOVAS VERIFICAÇÕES - ESPECIAIS =====

    /**
     * Verifica se o usuário fez lançamento antes das 6h
     */
    private function checkEarlyBird(int $userId): bool
    {
        return Lancamento::where('user_id', $userId)
            ->whereTime('created_at', '<', '06:00:00')
            ->exists();
    }

    /**
     * Verifica se o usuário fez lançamento após as 23h
     */
    private function checkNightOwl(int $userId): bool
    {
        return Lancamento::where('user_id', $userId)
            ->whereTime('created_at', '>=', '23:00:00')
            ->exists();
    }

    /**
     * Verifica se o usuário fez lançamento em um feriado específico
     */
    private function checkHoliday(int $userId, int $month, int $day): bool
    {
        return Lancamento::where('user_id', $userId)
            ->whereMonth('created_at', $month)
            ->whereDay('created_at', $day)
            ->exists();
    }

    /**
     * Verifica se o usuário fez 10+ lançamentos em fins de semana
     */
    private function checkWeekendWarrior(int $userId): bool
    {
        return $this->applyWeekendFilter(
            Lancamento::where('user_id', $userId),
            'created_at'
        )->count() >= 10;
    }

    /**
     * Verifica se o usuário fez 5+ lançamentos em um único dia
     */
    private function checkSpeedDemon(int $userId): bool
    {
        $result = Lancamento::where('user_id', $userId)
            ->selectRaw("DATE(created_at) as day, COUNT(*) as count")
            ->groupBy('day')
            ->havingRaw("count >= 5")
            ->first();

        return $result !== null;
    }

    private function lancamentosByMonthQuery(int $userId, string $yearMonth): Builder
    {
        $month = Carbon::createFromFormat('Y-m', $yearMonth);

        return Lancamento::where('user_id', $userId)
            ->whereBetween('data', [
                $month->copy()->startOfMonth()->toDateString(),
                $month->copy()->endOfMonth()->toDateString(),
            ]);
    }

    private function applyWeekendFilter(Builder $query, string $column): Builder
    {
        $driver = $query->getModel()->getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            return $query->whereRaw("CAST(strftime('%w', {$column}) AS INTEGER) IN (0, 6)");
        }

        if ($driver === 'pgsql') {
            return $query->whereRaw("EXTRACT(DOW FROM {$column}) IN (0, 6)");
        }

        return $query->whereRaw("DAYOFWEEK({$column}) IN (1, 7)");
    }

    /**
     * Verifica se o usuário completou todos os dados do perfil
     * Campos necessários: nome, email, data_nascimento, id_sexo
     */
    private function checkProfileComplete(Usuario $user): bool
    {
        // Verificar se todos os campos obrigatórios estão preenchidos
        $nome = trim((string)$user->nome);
        $email = trim((string)$user->email);
        $dataNascimento = $user->data_nascimento;
        $idSexo = $user->id_sexo;

        // Nome deve ter pelo menos 3 caracteres
        if (strlen($nome) < 3) {
            return false;
        }

        // Email deve ser válido
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Data de nascimento deve estar preenchida
        if (empty($dataNascimento)) {
            return false;
        }

        // Sexo deve estar selecionado (1 = masculino, 2 = feminino, etc.)
        if (empty($idSexo) || $idSexo < 1) {
            return false;
        }

        return true;
    }

    /**
     * Verifica se o usuário tem N indicações completadas
     */
    private function checkReferrals(int $userId, int $minReferrals): bool
    {
        $count = \Application\Models\Indicacao::where('referrer_id', $userId)
            ->where('status', \Application\Models\Indicacao::STATUS_COMPLETED)
            ->count();

        return $count >= $minReferrals;
    }
}
