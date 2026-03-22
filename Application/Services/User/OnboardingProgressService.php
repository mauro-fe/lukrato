<?php

declare(strict_types=1);

namespace Application\Services\User;

use Application\Models\OnboardingProgress;
use Illuminate\Database\Capsule\Manager as DB;

class OnboardingProgressService
{
    public function getProgress(int $userId): OnboardingProgress
    {
        return $this->findProgress($userId) ?? $this->syncFromDatabase($userId);
    }

    public function syncFromDatabase(int $userId): OnboardingProgress
    {
        $attributes = $this->loadProgressAttributes($userId);

        return OnboardingProgress::query()->updateOrCreate(
            ['user_id' => $userId],
            $attributes
        );
    }

    public function resyncState(int $userId): OnboardingProgress
    {
        $progress = $this->findProgress($userId);
        $attributes = $this->loadProgressAttributes($userId);

        if ($progress instanceof OnboardingProgress) {
            $attributes['onboarding_completed_at'] = $progress->onboarding_completed_at
                ? $this->normalizeDateTime($progress->onboarding_completed_at)
                : ($attributes['onboarding_completed_at'] ?? null);
        }

        return OnboardingProgress::query()->updateOrCreate(
            ['user_id' => $userId],
            $attributes
        );
    }

    public function markContaCreated(int $userId): OnboardingProgress
    {
        $progress = $this->getProgress($userId);

        if ($progress->has_conta) {
            return $progress;
        }

        $progress->has_conta = true;
        $progress->save();

        return $progress;
    }

    public function markLancamentoCreated(int $userId, \DateTimeInterface|string|null $createdAt = null): OnboardingProgress
    {
        $progress = $this->getProgress($userId);
        $progress->has_lancamento = true;

        if ($progress->first_lancamento_at === null) {
            $progress->first_lancamento_at = $this->normalizeDateTime($createdAt) ?? now();
        }

        $progress->save();

        return $progress;
    }

    public function markCompleted(int $userId, \DateTimeInterface|string|null $completedAt = null): OnboardingProgress
    {
        $progress = $this->getProgress($userId);

        if ($progress->onboarding_completed_at !== null) {
            return $progress;
        }

        $progress->onboarding_completed_at = $this->normalizeDateTime($completedAt) ?? now();
        $progress->save();

        return $progress;
    }

    public function reset(int $userId): OnboardingProgress
    {
        $progress = $this->getProgress($userId);
        $progress->has_conta = false;
        $progress->has_lancamento = false;
        $progress->first_lancamento_at = null;
        $progress->onboarding_completed_at = null;
        $progress->save();

        return $progress;
    }

    /**
     * @return array{
     *   accounts_count:int,
     *   entries_count:int,
     *   categories_count:int,
     *   has_meta:bool,
     *   has_budget:bool
     * }
     */
    public function getChecklistMetrics(int $userId): array
    {
        $row = DB::table('usuarios as u')
            ->where('u.id', $userId)
            ->selectRaw('
                (
                    SELECT COUNT(*)
                    FROM contas c
                    WHERE c.user_id = u.id
                      AND c.deleted_at IS NULL
                ) AS accounts_count,
                (
                    SELECT COUNT(*)
                    FROM lancamentos l
                    WHERE l.user_id = u.id
                      AND l.deleted_at IS NULL
                      AND l.eh_saldo_inicial = 0
                ) AS entries_count,
                (
                    SELECT COUNT(*)
                    FROM categorias cat
                    WHERE cat.user_id = u.id
                ) AS categories_count,
                EXISTS(
                    SELECT 1
                    FROM metas m
                    WHERE m.user_id = u.id
                ) AS has_meta,
                EXISTS(
                    SELECT 1
                    FROM orcamentos_categoria oc
                    WHERE oc.user_id = u.id
                ) AS has_budget
            ')
            ->first();

        return [
            'accounts_count' => (int) ($row->accounts_count ?? 0),
            'entries_count' => (int) ($row->entries_count ?? 0),
            'categories_count' => (int) ($row->categories_count ?? 0),
            'has_meta' => (bool) ($row->has_meta ?? false),
            'has_budget' => (bool) ($row->has_budget ?? false),
        ];
    }

    protected function findProgress(int $userId): ?OnboardingProgress
    {
        $progress = OnboardingProgress::find($userId);

        return $progress instanceof OnboardingProgress ? $progress : null;
    }

    /**
     * @return array{
     *   has_conta:bool,
     *   has_lancamento:bool,
     *   first_lancamento_at:string|null,
     *   onboarding_completed_at:string|null
     * }
     */
    protected function loadProgressAttributes(int $userId): array
    {
        $row = DB::table('usuarios as u')
            ->where('u.id', $userId)
            ->selectRaw('
                u.onboarding_completed_at AS onboarding_completed_at,
                EXISTS(
                    SELECT 1
                    FROM contas c
                    WHERE c.user_id = u.id
                      AND c.deleted_at IS NULL
                ) AS has_conta,
                EXISTS(
                    SELECT 1
                    FROM lancamentos l
                    WHERE l.user_id = u.id
                      AND l.deleted_at IS NULL
                ) AS has_lancamento,
                (
                    SELECT MIN(COALESCE(l.created_at, CONCAT(l.data, " 00:00:00")))
                    FROM lancamentos l
                    WHERE l.user_id = u.id
                      AND l.deleted_at IS NULL
                ) AS first_lancamento_at
            ')
            ->first();

        return [
            'has_conta' => (bool) ($row->has_conta ?? false),
            'has_lancamento' => (bool) ($row->has_lancamento ?? false),
            'first_lancamento_at' => $row->first_lancamento_at ?? null,
            'onboarding_completed_at' => $row->onboarding_completed_at ?? null,
        ];
    }

    private function normalizeDateTime(\DateTimeInterface|string|null $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
