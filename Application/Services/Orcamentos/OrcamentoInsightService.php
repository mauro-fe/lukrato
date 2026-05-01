<?php

declare(strict_types=1);

namespace Application\Services\Orcamentos;

use Application\Container\ApplicationContainer;
use Application\Repositories\OrcamentoRepository;

class OrcamentoInsightService
{
    private OrcamentoRepository $repo;

    public function __construct(?OrcamentoRepository $repo = null)
    {
        $this->repo = ApplicationContainer::resolveOrNew($repo, OrcamentoRepository::class);
    }

    public function generate(int $userId, int $mes, int $ano, array $orcamentos): array
    {
        $insights = [];

        $mesAnterior = $mes === 1 ? 12 : $mes - 1;
        $anoAnterior = $mes === 1 ? $ano - 1 : $ano;

        foreach ($orcamentos as $orc) {
            $gastoAnterior = $this->repo->getGastoRealComFallback(
                $userId,
                $orc['categoria_id'],
                $mesAnterior,
                $anoAnterior
            );

            if ($orc['percentual'] >= 80 && $orc['percentual'] < 100) {
                $insights[] = [
                    'tipo' => 'alerta',
                    'categoria_id' => $orc['categoria_id'],
                    'icone' => 'triangle-alert',
                    'cor' => '#f59e0b',
                    'titulo' => "{$orc['categoria_nome']} está em {$orc['percentual']}%",
                    'mensagem' => 'Restam R$ ' . number_format((float) $orc['disponivel'], 2, ',', '.') . ' nesta categoria',
                ];
            }

            if ($orc['percentual'] > 100) {
                $insights[] = [
                    'tipo' => 'perigo',
                    'categoria_id' => $orc['categoria_id'],
                    'icone' => 'circle-alert',
                    'cor' => '#ef4444',
                    'titulo' => "{$orc['categoria_nome']} estourou o orçamento!",
                    'mensagem' => 'Excedido em R$ ' . number_format((float) $orc['excedido'], 2, ',', '.'),
                ];
            }

            if ($gastoAnterior > 0 && $orc['gasto_real'] > 0) {
                $variacao = (($orc['gasto_real'] - $gastoAnterior) / $gastoAnterior) * 100;
                if (abs($variacao) >= 20) {
                    $direcao = $variacao > 0 ? 'mais' : 'menos';
                    $insights[] = [
                        'tipo' => $variacao > 0 ? 'info' : 'positivo',
                        'categoria_id' => $orc['categoria_id'],
                        'icone' => $variacao > 0 ? 'trending-up' : 'trending-down',
                        'cor' => $variacao > 0 ? '#f97316' : '#10b981',
                        'titulo' => "{$orc['categoria_nome']}: " . abs(round($variacao)) . "% {$direcao}",
                        'mensagem' => 'Comparado ao mês anterior',
                    ];
                }
            }

            if ($orc['percentual'] <= 30 && $orc['gasto_real'] > 0) {
                $insights[] = [
                    'tipo' => 'positivo',
                    'categoria_id' => $orc['categoria_id'],
                    'icone' => 'circle-check',
                    'cor' => '#10b981',
                    'titulo' => "{$orc['categoria_nome']} está sob controle",
                    'mensagem' => "Apenas {$orc['percentual']}% utilizado",
                ];
            }
        }

        $prioridade = ['perigo' => 0, 'alerta' => 1, 'info' => 2, 'positivo' => 3];
        usort(
            $insights,
            static fn(array $a, array $b): int => $prioridade[$a['tipo']] <=> $prioridade[$b['tipo']]
        );

        return array_slice($insights, 0, 8);
    }
}
