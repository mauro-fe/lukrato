<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Lancamentos;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Enums\LancamentoTipo;
use Application\Formatters\LancamentoResponseFormatter;
use Application\Repositories\LancamentoRepository;
use Illuminate\Database\Capsule\Manager as DB;
use ValueError;

class IndexController extends BaseController
{
    use LancamentoHelpersTrait;

    private LancamentoRepository $lancamentoRepo;

    public function __construct(?LancamentoRepository $lancamentoRepo = null)
    {
        parent::__construct();
        $this->lancamentoRepo = $lancamentoRepo ?? new LancamentoRepository();
    }

    public function __invoke(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        if (!DB::schema()->hasTable('lancamentos')) {
            return Response::successResponse([]);
        }

        $startDate = trim((string) ($_GET['start_date'] ?? ''));
        $endDate = trim((string) ($_GET['end_date'] ?? ''));
        $hasCustomRange = $startDate !== '' || $endDate !== '';

        if ($hasCustomRange && ($startDate === '' || $endDate === '')) {
            return Response::validationErrorResponse(['period' => 'Informe data inicial e final para usar periodo personalizado']);
        }

        if ($hasCustomRange) {
            $from = $this->parseDateParam($startDate);
            $to = $this->parseDateParam($endDate);

            if ($from === null || $to === null) {
                return Response::validationErrorResponse(['period' => 'Formato invalido de data (YYYY-MM-DD)']);
            }

            if ($to < $from) {
                return Response::validationErrorResponse(['period' => 'A data final deve ser posterior ou igual a inicial']);
            }
        } else {
            $month = $_GET['month'] ?? date('Y-m');
            if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
                return Response::validationErrorResponse(['month' => 'Formato invalido (YYYY-MM)']);
            }

            [$y, $m] = array_map('intval', explode('-', $month));
            $from = sprintf('%04d-%02d-01', $y, $m);
            $to = date('Y-m-t', strtotime($from));
        }

        $categoriaParams = $this->parseCategoriaParam((string) ($_GET['categoria_id'] ?? ''));
        $search = trim((string) ($_GET['q'] ?? ''));
        $status = strtolower(trim((string) ($_GET['status'] ?? '')));
        if (!in_array($status, ['pago', 'pendente'], true)) {
            $status = null;
        }

        try {
            $tipo = LancamentoTipo::from(strtolower($_GET['tipo'] ?? ''))->value;
        } catch (ValueError) {
            $tipo = null;
        }

        $lancamentos = $this->lancamentoRepo->findByFilters($userId, $from, $to, [
            'account_id' => (int) ($_GET['account_id'] ?? 0) ?: null,
            'categoria_id' => $categoriaParams['id'],
            'categoria_null' => $categoriaParams['isNull'],
            'tipo' => $tipo,
            'status' => $status,
            'search' => $search !== '' ? $search : null,
            'limit' => (int) ($_GET['limit'] ?? 500),
        ]);

        return Response::successResponse(LancamentoResponseFormatter::formatCollection($lancamentos));
    }

    private function parseDateParam(string $value): ?string
    {
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', $value)) {
            return null;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp) === $value ? $value : null;
    }
}
