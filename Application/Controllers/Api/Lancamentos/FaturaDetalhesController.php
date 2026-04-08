<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Lancamentos;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Repositories\FaturaCartaoRepository;
use Application\Repositories\LancamentoRepository;
use Application\Support\FaturaHelper;

class FaturaDetalhesController extends ApiController
{
    private LancamentoRepository $lancamentoRepo;
    private FaturaCartaoRepository $faturaCartaoRepo;

    public function __construct(
        ?LancamentoRepository $lancamentoRepo = null,
        ?FaturaCartaoRepository $faturaCartaoRepo = null
    ) {
        parent::__construct();
        $this->lancamentoRepo = $this->resolveOrCreate($lancamentoRepo, LancamentoRepository::class);
        $this->faturaCartaoRepo = $this->resolveOrCreate($faturaCartaoRepo, FaturaCartaoRepository::class);
    }

    public function __invoke(int $id): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        $lancamento = $this->lancamentoRepo->findByIdAndUser($id, $userId);
        if (!$lancamento) {
            return Response::errorResponse('Lancamento nao encontrado', 404);
        }

        if ($lancamento->origem_tipo !== 'pagamento_fatura' || !$lancamento->cartao_credito_id) {
            return Response::errorResponse('Este lançamento não é um pagamento de fatura.', 422);
        }

        $faturaData = FaturaHelper::parseMonthYearFromObservacao($lancamento->observacao);
        if (!$faturaData) {
            return Response::errorResponse('Não foi possível identificar a fatura.', 422);
        }

        $itens = $this->faturaCartaoRepo->getCategoryBreakdown(
            $lancamento->cartao_credito_id,
            $userId,
            $faturaData['ano'],
            $faturaData['mes']
        );

        $totalGeral = $itens->sum('total');

        $categorias = $itens->map(fn($row) => [
            'categoria_id' => (int) $row->categoria_id,
            'categoria_nome' => (string) $row->categoria_nome,
            'categoria_icone' => (string) $row->categoria_icone,
            'total' => round((float) $row->total, 2),
            'qtd_itens' => (int) $row->qtd_itens,
            'percentual' => $totalGeral > 0 ? round(((float) $row->total / $totalGeral) * 100, 1) : 0,
        ])->values()->all();

        return Response::successResponse([
            'lancamento_id' => $lancamento->id,
            'cartao_credito_id' => $lancamento->cartao_credito_id,
            'mes' => $faturaData['mes'],
            'ano' => $faturaData['ano'],
            'total' => round($totalGeral, 2),
            'categorias' => $categorias,
        ]);
    }
}
