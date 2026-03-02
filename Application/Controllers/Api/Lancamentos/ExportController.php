<?php

namespace Application\Controllers\Api\Lancamentos;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Models\Usuario;
use Application\Services\Lancamento\LancamentoExportService;
use InvalidArgumentException;

class ExportController extends BaseController
{
    private LancamentoExportService $exportService;

    public function __construct()
    {
        parent::__construct();
        $this->exportService = new LancamentoExportService();
    }

    public function __invoke(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Nao autenticado', 401);
            return;
        }

        $user = Usuario::find($userId);
        if (!$user || !$user->isPro()) {
            Response::error('Exportação de lançamentos é um recurso exclusivo do plano PRO.', 403);
            return;
        }

        $filters = [
            'month'              => $_GET['month'] ?? null,
            'start_date'         => $_GET['start_date'] ?? null,
            'end_date'           => $_GET['end_date'] ?? null,
            'tipo'               => $_GET['tipo'] ?? null,
            'categoria_id'       => $_GET['categoria_id'] ?? null,
            'account_id'         => $_GET['account_id'] ?? null,
            'include_transfers'  => $_GET['include_transfers'] ?? null,
            'format'             => $_GET['format'] ?? null,
        ];

        try {
            $result = $this->exportService->export($userId, $filters);
        } catch (InvalidArgumentException $e) {
            Response::validationError(['export' => $e->getMessage()]);
            return;
        } catch (\Throwable) {
            Response::error('Erro ao gerar exportacao.', 500);
            return;
        }

        if (ob_get_length() > 0) {
            ob_end_clean();
        }

        header('Content-Type: ' . $result['mime']);
        header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
        header('Content-Length: ' . (string) mb_strlen($result['binary'], '8bit'));
        echo $result['binary'];
        exit;
    }
}
