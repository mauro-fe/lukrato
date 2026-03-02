<?php

namespace Application\Controllers\Api\Lancamentos;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Repositories\LancamentoRepository;
use Application\Repositories\FaturaCartaoRepository;
use Application\Support\FaturaHelper;

class FaturaDetalhesController extends BaseController
{
    private LancamentoRepository $lancamentoRepo;
    private FaturaCartaoRepository $faturaCartaoRepo;

    public function __construct()
    {
        parent::__construct();
        $this->lancamentoRepo = new LancamentoRepository();
        $this->faturaCartaoRepo = new FaturaCartaoRepository();
    }

    public function __invoke(int $id): void
    {
        $userId = Auth::id();
        if (!$userId) {
            Response::error('Nao autenticado', 401);
            return;
        }

        $lancamento = $this->lancamentoRepo->findByIdAndUser($id, $userId);
        if (!$lancamento) {
            Response::error('Lancamento nao encontrado', 404);
            return;
        }

        if ($lancamento->origem_tipo !== 'pagamento_fatura' || !$lancamento->cartao_credito_id) {
            Response::error('Este lançamento não é um pagamento de fatura.', 422);
            return;
        }

        $faturaData = FaturaHelper::parseMonthYearFromObservacao($lancamento->observacao);
        if (!$faturaData) {
            Response::error('Não foi possível identificar a fatura.', 422);
            return;
        }

        $itens = $this->faturaCartaoRepo->getCategoryBreakdown(
            $lancamento->cartao_credito_id,
            $userId,
            $faturaData['ano'],
            $faturaData['mes']
        );

        $totalGeral = $itens->sum('total');

        $categorias = $itens->map(fn($row) => [
            'categoria_id'    => (int) $row->categoria_id,
            'categoria_nome'  => (string) $row->categoria_nome,
            'categoria_icone' => (string) $row->categoria_icone,
            'total'           => round((float) $row->total, 2),
            'qtd_itens'       => (int) $row->qtd_itens,
            'percentual'      => $totalGeral > 0 ? round(((float) $row->total / $totalGeral) * 100, 1) : 0,
        ])->values()->all();

        Response::success([
            'lancamento_id'     => $lancamento->id,
            'cartao_credito_id' => $lancamento->cartao_credito_id,
            'mes'               => $faturaData['mes'],
            'ano'               => $faturaData['ano'],
            'total'             => round($totalGeral, 2),
            'categorias'        => $categorias,
        ]);
    }
}
