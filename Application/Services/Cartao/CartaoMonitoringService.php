<?php

declare(strict_types=1);

namespace Application\Services\Cartao;

use Application\Enums\LogCategory;
use Application\Models\CartaoCredito;
use Application\Models\FaturaCartaoItem;
use Application\Models\Lancamento;
use Application\Services\Infrastructure\LogService;
use Throwable;

class CartaoMonitoringService
{
    public function atualizarLimiteDisponivel(int $cartaoId, int $userId): array
    {
        $cartao = CartaoCredito::forUser($userId)->find($cartaoId);

        if (!$cartao) {
            return ['success' => false, 'message' => 'Cartão não encontrado.'];
        }

        try {
            $cartao->atualizarLimiteDisponivel();

            return [
                'success' => true,
                'limite_disponivel' => $cartao->limite_disponivel,
                'limite_utilizado' => $cartao->limite_utilizado,
                'percentual_uso' => $cartao->percentual_uso,
            ];
        } catch (Throwable $e) {
            LogService::captureException($e, LogCategory::CARTAO, [
                'action' => 'atualizar_limite_disponivel',
                'cartao_id' => $cartaoId,
                'user_id' => $userId,
            ]);
            return [
                'success' => false,
                'message' => 'Erro ao atualizar limite.',
            ];
        }
    }

    public function obterResumo(int $userId): array
    {
        $cartoes = CartaoCredito::forUser($userId)->ativos()->get();

        $totalLimite = $cartoes->sum('limite_total');
        $totalDisponivel = $cartoes->sum('limite_disponivel');
        $totalUtilizado = $totalLimite - $totalDisponivel;
        $percentualUsoGeral = $totalLimite > 0
            ? round(($totalUtilizado / $totalLimite) * 100, 2)
            : 0;

        $mes = (int) date('n');
        $ano = (int) date('Y');
        $faturaAberta = (float) FaturaCartaoItem::where('user_id', $userId)
            ->where('pago', false)
            ->whereNull('cancelado_em')
            ->whereMonth('data_vencimento', $mes)
            ->whereYear('data_vencimento', $ano)
            ->sum('valor');

        return [
            'total_cartoes' => $cartoes->count(),
            'limite_total' => $totalLimite,
            'limite_disponivel' => $totalDisponivel,
            'limite_utilizado' => $totalUtilizado,
            'percentual_uso' => $percentualUsoGeral,
            'fatura_aberta' => $faturaAberta,
            'cartoes' => $cartoes->toArray(),
        ];
    }

    public function verificarLimitesBaixos(int $userId): array
    {
        try {
            $cartoes = CartaoCredito::forUser($userId)->ativos()->get();
            $alertas = [];

            foreach ($cartoes as $cartao) {
                try {
                    $percentualDisponivel = $cartao->limite_total > 0
                        ? ($cartao->limite_disponivel / $cartao->limite_total) * 100
                        : 0;

                    if ($percentualDisponivel < 20) {
                        $alertas[] = [
                            'cartao_id' => $cartao->id,
                            'nome_cartao' => $cartao->nome_cartao,
                            'limite_total' => (float) $cartao->limite_total,
                            'limite_disponivel' => (float) $cartao->limite_disponivel,
                            'percentual_disponivel' => round($percentualDisponivel, 2),
                            'tipo' => 'limite_baixo',
                            'gravidade' => $percentualDisponivel < 10 ? 'critico' : 'atencao',
                        ];
                    }
                } catch (Throwable $e) {
                    LogService::captureException($e, LogCategory::CARTAO, [
                        'action' => 'verificar_limite_cartao',
                        'cartao_id' => $cartao->id,
                    ]);
                }
            }

            return $alertas;
        } catch (Throwable $e) {
            LogService::captureException($e, LogCategory::CARTAO, [
                'action' => 'verificar_limites_baixos',
            ]);
            return [];
        }
    }

    public function validarIntegridadeLimites(int $userId, bool $corrigirAutomaticamente = false): array
    {
        $cartoes = CartaoCredito::forUser($userId)->get();
        $relatorio = [
            'total_cartoes' => $cartoes->count(),
            'cartoes_ok' => 0,
            'cartoes_com_divergencia' => 0,
            'divergencias' => [],
            'corrigidos' => 0,
        ];

        foreach ($cartoes as $cartao) {
            $limiteUtilizadoReal = Lancamento::where('cartao_credito_id', $cartao->id)
                ->where('pago', false)
                ->where(function ($query) {
                    $query->where('eh_parcelado', false)
                        ->orWhere(function ($subQuery) {
                            $subQuery->where('eh_parcelado', true)
                                ->whereNotNull('parcela_atual');
                        });
                })
                ->sum('valor');

            $limiteUtilizadoAtual = $cartao->limite_total - $cartao->limite_disponivel;
            $diferenca = abs($limiteUtilizadoReal - $limiteUtilizadoAtual);

            if ($diferenca > 0.01) {
                $divergencia = [
                    'cartao_id' => $cartao->id,
                    'nome_cartao' => $cartao->nome_cartao,
                    'limite_total' => $cartao->limite_total,
                    'limite_disponivel_atual' => $cartao->limite_disponivel,
                    'limite_utilizado_registrado' => $limiteUtilizadoAtual,
                    'limite_utilizado_real' => $limiteUtilizadoReal,
                    'diferenca' => $diferenca,
                    'limite_disponivel_correto' => $cartao->limite_total - $limiteUtilizadoReal,
                ];

                $relatorio['divergencias'][] = $divergencia;
                $relatorio['cartoes_com_divergencia']++;

                if ($corrigirAutomaticamente) {
                    try {
                        $novoLimiteDisponivel = max(
                            0,
                            min($cartao->limite_total, $cartao->limite_total - $limiteUtilizadoReal)
                        );

                        $cartao->limite_disponivel = $novoLimiteDisponivel;
                        $cartao->save();

                        $relatorio['corrigidos']++;
                    } catch (Throwable $e) {
                        $relatorio['divergencias'][array_key_last($relatorio['divergencias'])]['erro_correcao'] = $e->getMessage();
                    }
                }
            } else {
                $relatorio['cartoes_ok']++;
            }
        }

        return $relatorio;
    }
}
