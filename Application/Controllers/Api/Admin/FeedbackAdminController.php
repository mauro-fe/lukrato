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
        $this->service = $service ?? new FeedbackService();
    }

    /**
     * GET /api/sysadmin/feedback/stats
     */
    public function stats(): Response
    {
        $this->requireApiUserIdOrFail();

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
        $this->requireApiUserIdOrFail();

        $filters = [
            'tipo_feedback' => $this->getQuery('tipo_feedback'),
            'rating_min' => $this->getQuery('rating_min'),
            'rating_max' => $this->getQuery('rating_max'),
            'start_date' => $this->getQuery('start_date'),
            'end_date' => $this->getQuery('end_date'),
        ];
        $perPage = $this->getIntQuery('per_page', 15);
        $page = $this->getIntQuery('page', 1);

        $result = $this->service->getPaginated($filters, $perPage, $page);

        $result['items'] = $result['items']->map(function ($f) {
            return [
                'id' => $f->id,
                'user_id' => $f->user_id,
                'user_nome' => $f->user->nome ?? 'Removido',
                'tipo_feedback' => $f->tipo_feedback,
                'contexto' => $f->contexto,
                'rating' => $f->rating,
                'comentario' => $f->comentario,
                'pagina' => $f->pagina,
                'created_at' => $f->created_at?->format('Y-m-d H:i:s'),
            ];
        });

        return Response::successResponse($result);
    }

    /**
     * GET /api/sysadmin/feedback/export
     */
    public function export(): Response
    {
        $this->requireApiUserIdOrFail();

        $filters = [
            'tipo_feedback' => $this->getQuery('tipo_feedback'),
            'start_date' => $this->getQuery('start_date'),
            'end_date' => $this->getQuery('end_date'),
        ];

        $items = $this->service->getExportData($filters);

        $csv = "ID,Usuario,Tipo,Contexto,Rating,Comentario,Pagina,Data\n";

        foreach ($items as $f) {
            $csv .= sprintf(
                "%d,\"%s\",\"%s\",\"%s\",%s,\"%s\",\"%s\",\"%s\"\n",
                $f->id,
                str_replace('"', '""', $f->user->nome ?? ''),
                $f->tipo_feedback,
                $f->contexto ?? '',
                $f->rating ?? '',
                str_replace('"', '""', $f->comentario ?? ''),
                $f->pagina ?? '',
                $f->created_at?->format('Y-m-d H:i:s') ?? ''
            );
        }

        return (new Response())
            ->setStatusCode(200)
            ->withHeaders([
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="feedbacks_export.csv"',
            ])
            ->setContent("\xEF\xBB\xBF" . $csv);
    }
}
