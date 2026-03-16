<?php

namespace Application\Controllers\Api\Lancamentos;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Enums\LancamentoTipo;
use Application\Repositories\LancamentoRepository;
use Application\Formatters\LancamentoResponseFormatter;
use Illuminate\Database\Capsule\Manager as DB;
use ValueError;

class IndexController extends BaseController
{
    use LancamentoHelpersTrait;

    private LancamentoRepository $lancamentoRepo;

    public function __construct()
    {
        parent::__construct();
        $this->lancamentoRepo = new LancamentoRepository();
    }

    public function __invoke(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Nao autenticado', 401);
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        if (!DB::schema()->hasTable('lancamentos')) {
            Response::success([]);
            return;
        }

        $startDate = trim((string) ($_GET['start_date'] ?? ''));
        $endDate = trim((string) ($_GET['end_date'] ?? ''));
        $hasCustomRange = $startDate !== '' || $endDate !== '';

        if ($hasCustomRange && ($startDate === '' || $endDate === '')) {
            Response::validationError(['period' => 'Informe data inicial e final para usar periodo personalizado']);
            return;
        }

        if ($hasCustomRange) {
            $from = $this->parseDateParam($startDate);
            $to = $this->parseDateParam($endDate);

            if ($from === null || $to === null) {
                Response::validationError(['period' => 'Formato invalido de data (YYYY-MM-DD)']);
                return;
            }

            if ($to < $from) {
                Response::validationError(['period' => 'A data final deve ser posterior ou igual a inicial']);
                return;
            }
        } else {
            $month = $_GET['month'] ?? date('Y-m');
            if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
                Response::validationError(['month' => 'Formato invalido (YYYY-MM)']);
                return;
            }

            [$y, $m] = array_map('intval', explode('-', $month));
            $from = sprintf('%04d-%02d-01', $y, $m);
            $to   = date('Y-m-t', strtotime($from));
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
            'account_id'     => (int) ($_GET['account_id'] ?? 0) ?: null,
            'categoria_id'   => $categoriaParams['id'],
            'categoria_null' => $categoriaParams['isNull'],
            'tipo'           => $tipo,
            'status'         => $status,
            'search'         => $search !== '' ? $search : null,
            'limit'          => (int) ($_GET['limit'] ?? 500),
        ]);

        Response::success(LancamentoResponseFormatter::formatCollection($lancamentos));
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
