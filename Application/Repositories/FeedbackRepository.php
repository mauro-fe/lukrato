<?php

declare(strict_types=1);

namespace Application\Repositories;

use Application\Models\Feedback;
use Carbon\Carbon;

class FeedbackRepository extends BaseRepository
{
    protected function getModelClass(): string
    {
        return Feedback::class;
    }

    /**
     * Conta feedbacks do usuário por tipo (e contexto) no dia.
     */
    public function countTodayByUserAndContext(int $userId, string $tipo, ?string $contexto = null): int
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->where('tipo_feedback', $tipo)
            ->whereDate('created_at', now()->toDateString());

        if ($contexto !== null) {
            $query->where('contexto', $contexto);
        }

        return $query->count();
    }

    /**
     * Data do último NPS do usuário.
     */
    public function lastNpsFeedbackDate(int $userId): ?Carbon
    {
        $feedback = $this->query()
            ->where('user_id', $userId)
            ->where('tipo_feedback', Feedback::TIPO_NPS)
            ->orderBy('created_at', 'desc')
            ->first();

        return $feedback?->created_at;
    }

    /**
     * Stats agregados por tipo para dashboard sysadmin.
     */
    public function getStatsByTipo(): array
    {
        return $this->query()
            ->selectRaw('tipo_feedback, COUNT(*) as total, AVG(rating) as avg_rating')
            ->groupBy('tipo_feedback')
            ->get()
            ->keyBy('tipo_feedback')
            ->toArray();
    }

    /**
     * Calcula NPS score: % promotores (9-10) - % detratores (0-6).
     */
    public function calculateNpsScore(?string $startDate = null, ?string $endDate = null): array
    {
        $query = $this->query()
            ->where('tipo_feedback', Feedback::TIPO_NPS)
            ->whereNotNull('rating');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $total = $query->count();

        if ($total === 0) {
            return ['score' => 0, 'promoters' => 0, 'passives' => 0, 'detractors' => 0, 'total' => 0];
        }

        $promoters  = (clone $query)->where('rating', '>=', 9)->count();
        $detractors = (clone $query)->where('rating', '<=', 6)->count();
        $passives   = $total - $promoters - $detractors;

        $score = (int) round((($promoters - $detractors) / $total) * 100);

        return compact('score', 'promoters', 'passives', 'detractors', 'total');
    }

    /**
     * Listagem paginada com filtros para sysadmin.
     */
    public function getPaginated(array $filters, int $perPage = 15, int $page = 1): array
    {
        $query = $this->query()->with('user')->orderBy('created_at', 'desc');

        if (!empty($filters['tipo_feedback'])) {
            $query->where('tipo_feedback', $filters['tipo_feedback']);
        }
        if (!empty($filters['rating_min'])) {
            $query->where('rating', '>=', (int) $filters['rating_min']);
        }
        if (!empty($filters['rating_max'])) {
            $query->where('rating', '<=', (int) $filters['rating_max']);
        }
        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        $total = $query->count();
        $items = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        return [
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => (int) ceil($total / max($perPage, 1)),
            'items'      => $items,
        ];
    }
}
