<?php

namespace Application\Services\Financeiro;

use Application\Repositories\LancamentoRepository;
use Application\Repositories\OrcamentoRepository;
use Application\Repositories\MetaRepository;

class HealthScoreService
{
    private LancamentoRepository $lancamentoRepo;
    private OrcamentoRepository $orcamentoRepo;
    private MetaRepository $metaRepo;

    public function __construct(
        LancamentoRepository $lancamentoRepo,
        OrcamentoRepository $orcamentoRepo,
        MetaRepository $metaRepo
    ) {
        $this->lancamentoRepo = $lancamentoRepo;
        $this->orcamentoRepo = $orcamentoRepo;
        $this->metaRepo = $metaRepo;
    }

    public function calculateUserHealthScore(int $userId, string $month): array
    {
        $data = $this->lancamentoRepo->getResumoMes($userId, $month);
        $score = $this->calculateHealthScore($data);

        $mes = (int) date('m', strtotime($month));
        $ano = (int) date('Y', strtotime($month));

        $orcamentos = $this->orcamentoRepo->findByUserAndMonth($userId, $mes, $ano);
        $totalOrcamentos = $orcamentos->count();
        $dentroDoLimite = 0;
        foreach ($orcamentos as $orc) {
            $gasto = $this->orcamentoRepo->getGastoRealComFallback($userId, $orc->categoria_id, $mes, $ano);
            if ($gasto <= $orc->valor_limite) {
                $dentroDoLimite++;
            }
        }

        $metasAtivas = $this->metaRepo->countAtivas($userId);
        $metasConcluidas = 0;
        $metas = $this->metaRepo->findByUser($userId, 'ativa');
        foreach ($metas as $meta) {
            if ($meta->valor_atual >= $meta->valor_alvo) {
                $metasConcluidas++;
            }
        }

        $score['orcamentos'] = $totalOrcamentos;
        $score['orcamentos_ok'] = $dentroDoLimite;
        $score['metas_ativas'] = $metasAtivas;
        $score['metas_concluidas'] = $metasConcluidas;

        return $score;
    }

    private function calculateHealthScore(array $data): array
    {
        $score = 0;

        $receitas = (float)($data['receitas'] ?? 0);
        $despesas = (float)($data['despesas'] ?? 0);

        $savingsRate = 0;
        if ($receitas > 0) {
            $savingsRate = (($receitas - $despesas) / $receitas) * 100;
            if ($savingsRate >= 20) {
                $score += 35;
            } elseif ($savingsRate >= 10) {
                $score += 25;
            } elseif ($savingsRate >= 0) {
                $score += 15;
            }
        }

        $lancamentos = (int)($data['count'] ?? 0);
        if ($lancamentos >= 20) {
            $score += 30;
        } elseif ($lancamentos >= 10) {
            $score += 20;
        } elseif ($lancamentos >= 5) {
            $score += 15;
        } elseif ($lancamentos > 0) {
            $score += 8;
        }

        $categories = (int)($data['categories'] ?? 0);
        if ($categories >= 8) {
            $score += 20;
        } elseif ($categories >= 5) {
            $score += 15;
        } elseif ($categories >= 3) {
            $score += 10;
        } elseif ($categories >= 1) {
            $score += 5;
        }

        $saldoAtual = (float)($data['saldo_atual'] ?? 0);
        if ($saldoAtual > $despesas * 2) {
            $score += 15;
        } elseif ($saldoAtual > $despesas) {
            $score += 10;
        } elseif ($saldoAtual > 0) {
            $score += 5;
        }

        $score = min($score, 100);
        $score = max($score, 0);

        $consistency = 'Regular';
        if ($lancamentos >= 15) {
            $consistency = 'Excelente';
        } elseif ($lancamentos >= 10) {
            $consistency = 'Ótima';
        } elseif ($lancamentos >= 5) {
            $consistency = 'Boa';
        }

        return [
            'score' => (int)$score,
            'savingsRate' => (int)round($savingsRate ?? 0),
            'consistency' => $consistency,
            'categories' => $categories,
            'lancamentos' => $lancamentos,
        ];
    }

    public function generateHealthScoreInsights(int $userId, string $month): array
    {
        $data = $this->lancamentoRepo->getResumoMes($userId, $month);
        $insights = [];

        $receitas = (float)($data['receitas'] ?? 0);
        $despesas = (float)($data['despesas'] ?? 0);
        $saldoAtual = (float)($data['saldo_atual'] ?? 0);
        $lancamentos = (int)($data['count'] ?? 0);
        $categories = (int)($data['categories'] ?? 0);

        if ($receitas > 0) {
            $savingsRate = (($receitas - $despesas) / $receitas) * 100;
            if ($savingsRate < 10) {
                $needed = $receitas * 0.10 - ($receitas - $despesas);
                $insights[] = [
                    'type' => 'savings_rate',
                    'priority' => 'high',
                    'title' => '💰 Aumente sua poupança',
                    'message' => 'Sua taxa de poupança está baixa (< 10%)',
                    'suggestion' => 'Reduza suas despesas em R$ ' . number_format($needed, 2, ',', '.') . ' para atingir 10%',
                    'action' => [
                        'label' => 'Ver gastos por categoria',
                        'url' => 'relatorios'
                    ],
                    'impact' => '+15 pontos no score',
                    'impact_points' => 15,
                ];
            }
        } else if ($receitas == 0 && $despesas > 0) {
            $insights[] = [
                'type' => 'no_income',
                'priority' => 'critical',
                'title' => '⚠️ Registre suas receitas',
                'message' => 'Você só tem despesas registradas',
                'suggestion' => 'Adicione suas receitas para calcular a taxa de poupança corretamente',
                'action' => [
                    'label' => 'Adicionar receita',
                    'url' => 'lancamentos?tipo=receita'
                ],
                'impact' => '+20 pontos no score',
                'impact_points' => 20,
            ];
        }

        if ($lancamentos < 5) {
            $needed = 5 - $lancamentos;
            $insights[] = [
                'type' => 'consistency',
                'priority' => 'high',
                'title' => '📊 Registre mais transações',
                'message' => 'Você tem apenas ' . $lancamentos . ' transação(ões)',
                'suggestion' => 'Registre ' . $needed . ' mais transação(ões) para melhorar a consistência',
                'action' => [
                    'label' => 'Adicionar transação',
                    'url' => 'lancamentos'
                ],
                'impact' => '+10 pontos no score',
                'impact_points' => 10,
            ];
        }

        if ($categories < 3) {
            $needed = 3 - $categories;
            $insights[] = [
                'type' => 'diversification',
                'priority' => 'medium',
                'title' => '🎨 Organize em categorias',
                'message' => 'Você usa apenas ' . $categories . ' categoria(ias)',
                'suggestion' => 'Crie ' . $needed . ' categoria(ias) adicional(is) para melhor organização',
                'action' => [
                    'label' => 'Gerenciar categorias',
                    'url' => 'categorias'
                ],
                'impact' => '+10 pontos no score',
                'impact_points' => 10,
            ];
        }

        if ($saldoAtual < 0) {
            $insights[] = [
                'type' => 'negative_balance',
                'priority' => 'critical',
                'title' => '🚨 Saldo negativo',
                'message' => 'Seu saldo está negativo em R$ ' . number_format(abs($saldoAtual), 2, ',', '.'),
                'suggestion' => 'Aumente suas receitas ou reduza despesas urgentemente',
                'action' => [
                    'label' => 'Adicionar receita',
                    'url' => 'lancamentos?tipo=receita'
                ],
                'impact' => '+15 pontos no score',
                'impact_points' => 15,
            ];
        } else if ($saldoAtual < $despesas) {
            $insights[] = [
                'type' => 'low_balance',
                'priority' => 'medium',
                'title' => '💡 Fortaleça seu saldo',
                'message' => 'Seu saldo é inferior ao gasto mensal',
                'suggestion' => 'Economize para ter uma reserva de segurança (idealmente 2-3x suas despesas)',
                'action' => [
                    'label' => 'Ver evolução',
                    'url' => 'dashboard'
                ],
                'impact' => '+15 pontos no score',
                'impact_points' => 15,
            ];
        }

        $metasAtivas = $this->metaRepo->countAtivas($userId);
        if ($metasAtivas === 0) {
            $insights[] = [
                'type' => 'no_goals',
                'priority' => 'medium',
                'title' => '🎯 Defina suas metas',
                'message' => 'Você não tem nenhuma meta financeira ativa',
                'suggestion' => 'Crie metas para economizar, quitar dívidas ou investir',
                'action' => [
                    'label' => 'Criar meta',
                    'url' => 'financas#metas'
                ],
                'impact' => '+10 pontos no score',
                'impact_points' => 10,
            ];
        }

        $priorityOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
        usort($insights, fn($a, $b) => $priorityOrder[$a['priority']] <=> $priorityOrder[$b['priority']]);

        $topInsights = array_slice($insights, 0, 3);
        $totalPoints = array_sum(array_column($topInsights, 'impact_points'));

        return [
            'insights' => $topInsights,
            'total_possible_improvement' => $totalPoints > 0 ? '+' . $totalPoints . ' pontos' : 'Parabéns! Tudo certo',
        ];
    }
}
