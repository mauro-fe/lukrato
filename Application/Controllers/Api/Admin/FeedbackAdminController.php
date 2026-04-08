<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Admin;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Services\Feedback\FeedbackService;

class FeedbackAdminController extends ApiController
{
    private FeedbackService $service;

    public function __construct(?FeedbackService $service = null)
    {
        parent::__construct();
        $this->service = $this->resolveOrCreate($service, FeedbackService::class);
    }

    /**
     * GET /api/sysadmin/feedback/stats
     */
    public function stats(): Response
    {
        $this->requireAuthenticatedUserId();

        $statsByTipo = $this->service->getStatsByTipo();
        $npsScore = $this->service->getNpsScore(
            $this->getQuery('start_date'),
            $this->getQuery('end_date')
        );

        return Response::successResponse([
            'by_tipo' => $statsByTipo,
            'nps' => $npsScore,
        ]);
    }

    /**
     * GET /api/sysadmin/feedback
     */
    public function index(): Response
    {
        $this->requireAuthenticatedUserId();

        $filters = $this->feedbackFilters(includeRatingRange: true);
        $perPage = $this->getIntQuery('per_page', 15);
        $page = $this->getIntQuery('page', 1);

        $result = $this->service->getPaginated($filters, $perPage, $page);

        $result['items'] = $result['items']->map(fn($f): array => $this->serializeFeedback($f));

        return Response::successResponse($result);
    }

    /**
     * GET /api/sysadmin/feedback/export
     */
    public function export(): Response
    {
        $this->requireAuthenticatedUserId();

        $filters = $this->feedbackFilters();

        $items = $this->service->getExportData($filters);

        $csv = $this->csvHeader();

        foreach ($items as $f) {
            $csv .= $this->feedbackCsvRow($f);
        }

        return (new Response())
            ->setStatusCode(200)
            ->withHeaders([
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="feedbacks_export.csv"',
            ])
            ->setContent("\xEF\xBB\xBF" . $csv);
    }

    private function requireAuthenticatedUserId(): int
    {
        return $this->requireApiUserIdOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function feedbackFilters(bool $includeRatingRange = false): array
    {
        $filters = [
            'tipo_feedback' => $this->getQuery('tipo_feedback'),
            'start_date' => $this->getQuery('start_date'),
            'end_date' => $this->getQuery('end_date'),
        ];

        if ($includeRatingRange) {
            $filters['rating_min'] = $this->getQuery('rating_min');
            $filters['rating_max'] = $this->getQuery('rating_max');
        }

        return $filters;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeFeedback(object $feedback): array
    {
        return [
            'id' => $feedback->id,
            'user_id' => $feedback->user_id,
            'user_nome' => $feedback->user->nome ?? 'Removido',
            'tipo_feedback' => $feedback->tipo_feedback,
            'contexto' => $feedback->contexto,
            'rating' => $feedback->rating,
            'comentario' => $feedback->comentario,
            'pagina' => $feedback->pagina,
            'created_at' => $feedback->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function csvHeader(): string
    {
        return "ID,Usuario,Tipo,Contexto,Rating,Comentario,Pagina,Data\n";
    }

    private function feedbackCsvRow(object $feedback): string
    {
        return sprintf(
            "%d,\"%s\",\"%s\",\"%s\",%s,\"%s\",\"%s\",\"%s\"\n",
            $feedback->id,
            $this->escapeCsv((string) ($feedback->user->nome ?? '')),
            (string) $feedback->tipo_feedback,
            (string) ($feedback->contexto ?? ''),
            (string) ($feedback->rating ?? ''),
            $this->escapeCsv((string) ($feedback->comentario ?? '')),
            (string) ($feedback->pagina ?? ''),
            (string) ($feedback->created_at?->format('Y-m-d H:i:s') ?? '')
        );
    }

    private function escapeCsv(string $value): string
    {
        return str_replace('"', '""', $value);
    }
}
