<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Admin;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Services\Feedback\FeedbackService;

class FeedbackAdminController extends BaseController
{
    private FeedbackService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new FeedbackService();
    }

    /**
     * GET /api/sysadmin/feedback/stats
     */
    public function stats(): void
    {
        $this->requireAuthApi();

        $statsByTipo = $this->service->getStatsByTipo();
        $npsScore    = $this->service->getNpsScore(
            $this->getQuery('start_date'),
            $this->getQuery('end_date')
        );

        Response::success([
            'by_tipo' => $statsByTipo,
            'nps'     => $npsScore,
        ]);
    }

    /**
     * GET /api/sysadmin/feedback
     */
    public function index(): void
    {
        $this->requireAuthApi();

        $filters = [
            'tipo_feedback' => $this->getQuery('tipo_feedback'),
            'rating_min'    => $this->getQuery('rating_min'),
            'rating_max'    => $this->getQuery('rating_max'),
            'start_date'    => $this->getQuery('start_date'),
            'end_date'      => $this->getQuery('end_date'),
        ];
        $perPage = (int) ($this->getQuery('per_page', '15'));
        $page    = (int) ($this->getQuery('page', '1'));

        $result = $this->service->getPaginated($filters, $perPage, $page);

        $result['items'] = $result['items']->map(function ($f) {
            return [
                'id'            => $f->id,
                'user_id'       => $f->user_id,
                'user_nome'     => $f->user->nome ?? 'Removido',
                'tipo_feedback' => $f->tipo_feedback,
                'contexto'      => $f->contexto,
                'rating'        => $f->rating,
                'comentario'    => $f->comentario,
                'pagina'        => $f->pagina,
                'created_at'    => $f->created_at?->format('Y-m-d H:i:s'),
            ];
        });

        Response::success($result);
    }

    /**
     * GET /api/sysadmin/feedback/export
     */
    public function export(): void
    {
        $this->requireAuthApi();

        $filters = [
            'tipo_feedback' => $this->getQuery('tipo_feedback'),
            'start_date'    => $this->getQuery('start_date'),
            'end_date'      => $this->getQuery('end_date'),
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

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="feedbacks_export.csv"');
        echo "\xEF\xBB\xBF" . $csv;
        exit;
    }
}
