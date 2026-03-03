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

        $month = $_GET['month'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            Response::validationError(['month' => 'Formato invalido (YYYY-MM)']);
            return;
        }

        [$y, $m] = array_map('intval', explode('-', $month));
        $from = sprintf('%04d-%02d-01', $y, $m);
        $to   = date('Y-m-t', strtotime($from));

        $categoriaParams = $this->parseCategoriaParam((string) ($_GET['categoria_id'] ?? ''));

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
            'limit'          => (int) ($_GET['limit'] ?? 500),
        ]);

        Response::success(LancamentoResponseFormatter::formatCollection($lancamentos));
    }
}
