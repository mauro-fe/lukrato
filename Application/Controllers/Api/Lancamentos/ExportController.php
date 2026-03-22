<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Lancamentos;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Usuario;
use Application\Services\Lancamento\LancamentoExportService;
use InvalidArgumentException;

class ExportController extends BaseController
{
    private LancamentoExportService $exportService;

    public function __construct(?LancamentoExportService $exportService = null)
    {
        parent::__construct();
        $this->exportService = $exportService ?? new LancamentoExportService();
    }

    public function __invoke(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        $user = Usuario::find($userId);
        if (!$user || !$user->isPro()) {
            return Response::errorResponse('Exportação de lançamentos é um recurso exclusivo do plano PRO.', 403);
        }

        $filters = [
            'month' => $_GET['month'] ?? null,
            'start_date' => $_GET['start_date'] ?? null,
            'end_date' => $_GET['end_date'] ?? null,
            'tipo' => $_GET['tipo'] ?? null,
            'categoria_id' => $_GET['categoria_id'] ?? null,
            'account_id' => $_GET['account_id'] ?? null,
            'include_transfers' => $_GET['include_transfers'] ?? null,
            'format' => $_GET['format'] ?? null,
        ];

        try {
            $result = $this->exportService->export($userId, $filters);
        } catch (InvalidArgumentException $e) {
            return $this->domainErrorResponse($e, 'Parametros de exportacao invalidos.', 422);
        } catch (\Throwable) {
            return Response::errorResponse('Erro ao gerar exportacao.', 500);
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
