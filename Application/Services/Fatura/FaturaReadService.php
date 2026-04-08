<?php

declare(strict_types=1);

namespace Application\Services\Fatura;

use Application\Container\ApplicationContainer;
use Application\Models\Fatura;
use Application\Models\FaturaCartaoItem;
use Application\Services\Infrastructure\LogService;
use Exception;

class FaturaReadService
{
    private FaturaFormatterService $formatterService;

    public function __construct(
        ?FaturaFormatterService $formatterService = null,
        ?FaturaInstallmentCalculatorService $calculatorService = null
    ) {
        $calculatorService = ApplicationContainer::resolveOrNew($calculatorService, FaturaInstallmentCalculatorService::class);
        $this->formatterService = ApplicationContainer::resolveOrNew(
            $formatterService,
            FaturaFormatterService::class,
            fn(): FaturaFormatterService => new FaturaFormatterService($calculatorService)
        );
    }

    public function listar(
        int $usuarioId,
        ?int $cartaoId = null,
        ?string $status = null,
        ?int $mes = null,
        ?int $ano = null
    ): array {
        try {
            $query = Fatura::where('user_id', $usuarioId)
                ->with(['cartaoCredito.conta']);

            if ($mes && $ano) {
                $query->with(['itens' => function ($query) use ($mes, $ano) {
                    $query->whereYear('data_vencimento', $ano)
                        ->whereMonth('data_vencimento', $mes);
                }]);
            } elseif ($ano) {
                $query->with(['itens' => function ($query) use ($ano) {
                    $query->whereYear('data_vencimento', $ano);
                }]);
            } else {
                $query->with(['itens']);
            }

            if ($cartaoId) {
                $query->where('cartao_credito_id', $cartaoId);
            }

            if ($status) {
                $query->where('status', $status);
            }

            if ($mes && $ano) {
                $query->whereHas('itens', function ($query) use ($mes, $ano) {
                    $query->whereYear('data_vencimento', $ano)
                        ->whereMonth('data_vencimento', $mes);
                });
            } elseif ($ano) {
                $query->whereHas('itens', function ($query) use ($ano) {
                    $query->whereYear('data_vencimento', $ano);
                });
            }

            $faturas = $query->orderBy('data_compra', 'desc')->get();

            return $faturas->map(function ($fatura) use ($mes, $ano) {
                return $this->formatterService->formatarFaturaListagem($fatura, $mes, $ano);
            })->toArray();
        } catch (Exception $e) {
            LogService::error("Erro ao listar faturas", [
                'usuario_id' => $usuarioId,
                'error' => $e->getMessage()
            ]);

            throw new Exception('Erro ao listar faturas.');
        }
    }

    public function obterAnosDisponiveis(int $usuarioId): array
    {
        try {
            $anos = FaturaCartaoItem::whereHas('fatura', function ($query) use ($usuarioId) {
                $query->where('user_id', $usuarioId);
            })
                ->selectRaw('YEAR(data_vencimento) as ano_vencimento')
                ->distinct()
                ->pluck('ano_vencimento')
                ->filter()
                ->sort()
                ->values()
                ->toArray();

            if (empty($anos)) {
                return [date('Y')];
            }

            return $anos;
        } catch (Exception $e) {
            LogService::error("Erro ao obter anos disponíveis", [
                'usuario_id' => $usuarioId,
                'error' => $e->getMessage()
            ]);

            return [date('Y')];
        }
    }

    public function buscar(int $faturaId, int $usuarioId): ?array
    {
        try {
            $fatura = Fatura::where('id', $faturaId)
                ->where('user_id', $usuarioId)
                ->with(['cartaoCredito', 'itens' => function ($query) {
                    $query->orderBy('ano_referencia')
                        ->orderBy('mes_referencia')
                        ->orderBy('data_compra')
                        ->orderBy('parcela_atual');
                }])
                ->first();

            if (!$fatura) {
                return null;
            }

            return $this->formatterService->formatarFaturaDetalhada($fatura);
        } catch (Exception $e) {
            LogService::error("Erro ao buscar fatura", [
                'fatura_id' => $faturaId,
                'usuario_id' => $usuarioId,
                'error' => $e->getMessage()
            ]);

            throw new Exception('Erro ao buscar fatura.');
        }
    }
}
