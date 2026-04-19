<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Lancamentos;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Enums\LancamentoTipo;
use Application\Formatters\LancamentoResponseFormatter;
use Application\Repositories\LancamentoRepository;
use Illuminate\Database\Capsule\Manager as DB;
use ValueError;

class IndexController extends ApiController
{
    use LancamentoHelpersTrait;

    private LancamentoRepository $lancamentoRepo;

    public function __construct(?LancamentoRepository $lancamentoRepo = null)
    {
        parent::__construct();
        $this->lancamentoRepo = $this->resolveOrCreate($lancamentoRepo, LancamentoRepository::class);
    }

    public function __invoke(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        if (!DB::schema()->hasTable('lancamentos')) {
            return Response::successResponse([]);
        }

        $startDate = $this->getStringQuery('start_date', '');
        $endDate = $this->getStringQuery('end_date', '');
        $hasCustomRange = $startDate !== '' || $endDate !== '';

        if ($hasCustomRange && ($startDate === '' || $endDate === '')) {
            return Response::validationErrorResponse(['period' => 'Informe as datas inicial e final para usar o período personalizado']);
        }

        if ($hasCustomRange) {
            $from = $this->parseDateParam($startDate);
            $to = $this->parseDateParam($endDate);

            if ($from === null || $to === null) {
                return Response::validationErrorResponse(['period' => 'Formato inválido de data (YYYY-MM-DD)']);
            }

            if ($to < $from) {
                return Response::validationErrorResponse(['period' => 'A data final deve ser posterior ou igual à inicial']);
            }
        } else {
            $month = $this->getStringQuery('month', date('Y-m'));
            if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
                return Response::validationErrorResponse(['month' => 'Formato inválido (YYYY-MM)']);
            }

            [$y, $m] = array_map('intval', explode('-', $month));
            $from = sprintf('%04d-%02d-01', $y, $m);
            $to = date('Y-m-t', strtotime($from));
        }

        $categoriaParams = $this->parseCategoriaParam($this->getStringQuery('categoria_id', ''));
        $search = $this->getStringQuery('q', '');
        $status = strtolower($this->getStringQuery('status', ''));

        if (!in_array($status, ['pago', 'pendente'], true)) {
            $status = null;
        }

        try {
            $tipo = LancamentoTipo::from(strtolower($this->getStringQuery('tipo', '')))->value;
        } catch (ValueError) {
            $tipo = null;
        }

        $lancamentos = $this->lancamentoRepo->findByFilters($userId, $from, $to, [
            'account_id' => $this->getIntQuery('account_id', 0) ?: null,
            'categoria_id' => $categoriaParams['id'],
            'categoria_null' => $categoriaParams['isNull'],
            'tipo' => $tipo,
            'status' => $status,
            'search' => $search !== '' ? $search : null,
            'limit' => $this->getIntQuery('limit', 500),
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
