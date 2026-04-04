<?php

declare(strict_types=1);

namespace Application\Services\Feedback;

use Application\Models\Feedback;
use Application\Repositories\FeedbackRepository;
use Application\Services\Infrastructure\LogService;
use Exception;

class FeedbackService
{
    private FeedbackRepository $repo;

    public function __construct()
    {
        $this->repo = new FeedbackRepository();
    }

    /**
     * Salva feedback com validação e anti-spam.
     */
    public function store(int $userId, array $data): array
    {
        $tipo     = $data['tipo_feedback'] ?? '';
        $contexto = $data['contexto'] ?? null;
        $rating   = isset($data['rating']) ? (int) $data['rating'] : null;
        $comment  = $data['comentario'] ?? null;
        $pagina   = $data['pagina'] ?? null;

        $tiposValidos = [
            Feedback::TIPO_ACAO,
            Feedback::TIPO_ASSISTENTE_IA,
            Feedback::TIPO_NPS,
            Feedback::TIPO_SUGESTAO,
        ];

        if (!in_array($tipo, $tiposValidos, true)) {
            return ['success' => false, 'message' => 'Tipo de feedback invalido.'];
        }

        if (!$this->validateRating($tipo, $rating)) {
            return ['success' => false, 'message' => 'Rating invalido para este tipo de feedback.'];
        }

        $spamCheck = $this->checkAntiSpam($userId, $tipo, $contexto);
        if (!$spamCheck['allowed']) {
            return ['success' => false, 'message' => $spamCheck['message']];
        }

        try {
            $feedback = $this->repo->create([
                'user_id'       => $userId,
                'tipo_feedback' => $tipo,
                'contexto'      => $contexto,
                'rating'        => $rating,
                'comentario'    => $comment ? mb_substr(trim($comment), 0, 2000) : null,
                'pagina'        => $pagina ? mb_substr($pagina, 0, 255) : null,
            ]);

            return ['success' => true, 'data' => $feedback];
        } catch (Exception $e) {
            LogService::error('Erro ao salvar feedback', [
                'user_id' => $userId,
                'tipo'    => $tipo,
                'error'   => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => 'Erro ao salvar feedback.'];
        }
    }

    /**
     * Verifica se NPS deve ser exibido (ultimo NPS > 30 dias).
     */
    public function shouldShowNps(int $userId): bool
    {
        $lastNps = $this->repo->lastNpsFeedbackDate($userId);
        if ($lastNps === null) {
            return true;
        }
        return $lastNps->diffInDays(now()) >= 30;
    }

    /**
     * Verifica se micro feedback pode ser exibido para o contexto.
     */
    public function canShowMicroFeedback(int $userId, string $contexto): bool
    {
        return $this->repo->countTodayByUserAndContext($userId, Feedback::TIPO_ACAO, $contexto) === 0;
    }

    // --- Metodos SysAdmin ---

    public function getStatsByTipo(): array
    {
        return $this->repo->getStatsByTipo();
    }

    public function getNpsScore(?string $startDate = null, ?string $endDate = null): array
    {
        return $this->repo->calculateNpsScore($startDate, $endDate);
    }

    public function getPaginated(array $filters, int $perPage = 15, int $page = 1): array
    {
        return $this->repo->getPaginated($filters, $perPage, $page);
    }

    public function getExportData(array $filters): \Illuminate\Database\Eloquent\Collection
    {
        $query = Feedback::with('user')->orderBy('created_at', 'desc');

        if (!empty($filters['tipo_feedback'])) {
            $query->where('tipo_feedback', $filters['tipo_feedback']);
        }
        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        return $query->get();
    }

    // --- Validação Privada ---

    private function validateRating(string $tipo, ?int $rating): bool
    {
        return match ($tipo) {
            Feedback::TIPO_ACAO          => $rating === null || ($rating >= 0 && $rating <= 1),
            Feedback::TIPO_ASSISTENTE_IA => $rating === null || ($rating >= 0 && $rating <= 2),
            Feedback::TIPO_NPS           => $rating !== null && $rating >= 0 && $rating <= 10,
            Feedback::TIPO_SUGESTAO      => $rating === null || ($rating >= 1 && $rating <= 5),
            default => false,
        };
    }

    private function checkAntiSpam(int $userId, string $tipo, ?string $contexto): array
    {
        return match ($tipo) {
            Feedback::TIPO_ACAO     => $this->checkMicroSpam($userId, $contexto),
            Feedback::TIPO_NPS      => $this->checkNpsSpam($userId),
            Feedback::TIPO_SUGESTAO => $this->checkSugestaoSpam($userId),
            default                 => ['allowed' => true],
        };
    }

    private function checkMicroSpam(int $userId, ?string $contexto): array
    {
        $count = $this->repo->countTodayByUserAndContext($userId, Feedback::TIPO_ACAO, $contexto);
        return $count >= 1
            ? ['allowed' => false, 'message' => 'você ja enviou feedback para esta acao hoje.']
            : ['allowed' => true];
    }

    private function checkNpsSpam(int $userId): array
    {
        $lastNps = $this->repo->lastNpsFeedbackDate($userId);
        if ($lastNps !== null && $lastNps->diffInDays(now()) < 30) {
            return ['allowed' => false, 'message' => 'NPS ja enviado recentemente.'];
        }
        return ['allowed' => true];
    }

    private function checkSugestaoSpam(int $userId): array
    {
        $count = $this->repo->countTodayByUserAndContext($userId, Feedback::TIPO_SUGESTAO);
        return $count >= 3
            ? ['allowed' => false, 'message' => 'Limite de sugestoes atingido hoje.']
            : ['allowed' => true];
    }
}
