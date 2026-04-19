<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Lancamentos;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Models\Usuario;
use Application\Services\Lancamento\LancamentoExportService;
use InvalidArgumentException;

class ExportController extends ApiController
{
    private LancamentoExportService $exportService;

    public function __construct(?LancamentoExportService $exportService = null)
    {
        parent::__construct();
        $this->exportService = $this->resolveOrCreate($exportService, LancamentoExportService::class);
    }

    public function __invoke(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        $user = Usuario::find($userId);
        if (!$user || !$user->isPro()) {
            return Response::errorResponse('Exportação de lançamentos é um recurso exclusivo do plano PRO.', 403);
        }

        $filters = [
            'month' => $this->getQuery('month'),
            'start_date' => $this->getQuery('start_date'),
            'end_date' => $this->getQuery('end_date'),
            'tipo' => $this->getQuery('tipo'),
            'categoria_id' => $this->getQuery('categoria_id'),
            'account_id' => $this->getQuery('account_id'),
            'include_transfers' => $this->getQuery('include_transfers'),
            'format' => $this->getQuery('format'),
        ];

        try {
            $result = $this->exportService->export($userId, $filters);
        } catch (InvalidArgumentException $e) {
            return $this->domainErrorResponse($e, 'Parâmetros de exportação inválidos.', 422);
        } catch (\Throwable) {
            return Response::errorResponse('Erro ao gerar exportação.', 500);
        }

        return (new Response())
            ->setStatusCode(200)
            ->header('Content-Type', $result['mime'])
            ->header('Content-Disposition', 'attachment; filename="' . $result['filename'] . '"')
            ->header('Content-Length', (string) mb_strlen($result['binary'], '8bit'))
            ->setContent($result['binary'])
            ->clearOutputBuffer();
    }
}
