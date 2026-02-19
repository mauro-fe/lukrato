<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Models\Categoria;
use Application\Models\Lancamento;
use Application\Models\OrcamentoCategoria;
use Application\Repositories\OrcamentoRepository;

/**
 * Serviço de Orçamento por Categoria
 * 
 * Gerencia orçamentos mensais com automações: auto-sugestão baseada
 * em histórico, detecção de tendências, insights inteligentes.
 */
class OrcamentoService
{
    private OrcamentoRepository $repo;

    public function __construct()
    {
        $this->repo = new OrcamentoRepository();
    }

    /**
     * Listar orçamentos do mês com gastos reais e progresso
     */
    public function listarComProgresso(int $userId, int $mes, int $ano): array
    {
        $orcamentos = $this->repo->findByUserAndMonth($userId, $mes, $ano);

        return $orcamentos->map(function (OrcamentoCategoria $orc) use ($userId, $mes, $ano) {
            $gastoReal = $this->repo->getGastoRealComFallback($userId, $orc->categoria_id, $mes, $ano);
            $percentual = $orc->valor_limite > 0 ? round(($gastoReal / $orc->valor_limite) * 100, 1) : 0;

            // Calcular rollover (sobra do mês anterior)
            $rolloverValor = 0;
            if ($orc->rollover) {
                $rolloverValor = $this->calcularRollover($userId, $orc->categoria_id, $mes, $ano);
            }

            $limiteEfetivo = $orc->valor_limite + $rolloverValor;
            $percentualEfetivo = $limiteEfetivo > 0 ? round(($gastoReal / $limiteEfetivo) * 100, 1) : 0;

            return [
                'id'                => $orc->id,
                'categoria_id'      => $orc->categoria_id,
                'categoria_nome'    => $orc->categoria?->nome ?? 'Sem categoria',
                'valor_limite'      => $orc->valor_limite,
                'rollover'          => $orc->rollover,
                'rollover_valor'    => $rolloverValor,
                'limite_efetivo'    => $limiteEfetivo,
                'gasto_real'        => round($gastoReal, 2),
                'disponivel'        => round(max(0, $limiteEfetivo - $gastoReal), 2),
                'excedido'          => round(max(0, $gastoReal - $limiteEfetivo), 2),
                'percentual'        => min($percentualEfetivo, 999),
                'status'            => $this->getStatusOrcamento($percentualEfetivo),
                'alerta_80'         => $orc->alerta_80,
                'alerta_100'        => $orc->alerta_100,
                'mes'               => $orc->mes,
                'ano'               => $orc->ano,
            ];
        })->toArray();
    }

    /**
     * Criar ou atualizar orçamento
     */
    public function salvar(int $userId, int $categoriaId, int $mes, int $ano, array $data): array
    {
        $orc = $this->repo->upsert($userId, $categoriaId, $mes, $ano, [
            'valor_limite' => (float) ($data['valor_limite'] ?? 0),
            'rollover'     => (bool) ($data['rollover'] ?? false),
            'alerta_80'    => (bool) ($data['alerta_80'] ?? true),
            'alerta_100'   => (bool) ($data['alerta_100'] ?? true),
        ]);

        return $this->listarComProgresso($userId, $mes, $ano);
    }

    /**
     * Salvar múltiplos orçamentos de uma vez (bulk save)
     */
    public function salvarMultiplos(int $userId, int $mes, int $ano, array $orcamentos): array
    {
        foreach ($orcamentos as $item) {
            $catId = (int) ($item['categoria_id'] ?? 0);
            $valor = (float) ($item['valor_limite'] ?? 0);

            if ($catId <= 0 || $valor <= 0) continue;

            $this->repo->upsert($userId, $catId, $mes, $ano, [
                'valor_limite' => $valor,
                'rollover'     => (bool) ($item['rollover'] ?? false),
                'alerta_80'    => (bool) ($item['alerta_80'] ?? true),
                'alerta_100'   => (bool) ($item['alerta_100'] ?? true),
            ]);
        }

        return $this->listarComProgresso($userId, $mes, $ano);
    }

    /**
     * Remover orçamento
     */
    public function remover(int $userId, int $id): bool
    {
        return $this->repo->deleteForUser($id, $userId);
    }

    /**
     * Auto-sugestão inteligente: analisa últimos 3 meses e sugere limites
     */
    public function autoSugerir(int $userId): array
    {
        $categorias = Categoria::where(function ($q) use ($userId) {
            $q->where('user_id', $userId)->orWhereNull('user_id');
        })->where('tipo', 'despesa')->get();

        $sugestoes = [];

        foreach ($categorias as $cat) {
            $media = $this->repo->getMediaGastos($userId, $cat->id, 3);

            if ($media > 0) {
                // Sugere +10% acima da média (margem de segurança)
                $sugerido = ceil($media * 1.10);
                // Arredondar para múltiplo de 10
                $sugerido = ceil($sugerido / 10) * 10;

                $tendencia = $this->calcularTendencia($userId, $cat->id);

                $sugestoes[] = [
                    'categoria_id'   => $cat->id,
                    'categoria_nome' => $cat->nome,
                    'media_3_meses'  => $media,
                    'valor_sugerido' => $sugerido,
                    'tendencia'      => $tendencia,
                ];
            }
        }

        // Ordenar por valor sugerido (maior primeiro)
        usort($sugestoes, fn($a, $b) => $b['valor_sugerido'] <=> $a['valor_sugerido']);

        return $sugestoes;
    }

    /**
     * Aplicar todas as sugestões de uma vez (setup rápido)
     */
    public function aplicarSugestoes(int $userId, int $mes, int $ano, array $sugestoes): array
    {
        foreach ($sugestoes as $s) {
            $catId = (int) ($s['categoria_id'] ?? 0);
            $valor = (float) ($s['valor_sugerido'] ?? $s['valor_limite'] ?? 0);

            if ($catId <= 0 || $valor <= 0) continue;

            $this->repo->upsert($userId, $catId, $mes, $ano, [
                'valor_limite' => $valor,
                'rollover'     => false,
                'alerta_80'    => true,
                'alerta_100'   => true,
            ]);
        }

        return $this->listarComProgresso($userId, $mes, $ano);
    }

    /**
     * Copiar orçamentos do mês anterior
     */
    public function copiarMesAnterior(int $userId, int $mes, int $ano): array
    {
        $mesAnterior = $mes === 1 ? 12 : $mes - 1;
        $anoAnterior = $mes === 1 ? $ano - 1 : $ano;

        $copiados = $this->repo->copiarMes($userId, $mesAnterior, $anoAnterior, $mes, $ano);

        return [
            'copiados'   => $copiados,
            'orcamentos' => $this->listarComProgresso($userId, $mes, $ano),
        ];
    }

    /**
     * Resumo geral dos orçamentos do mês
     */
    public function resumo(int $userId, int $mes, int $ano): array
    {
        $orcamentos = $this->listarComProgresso($userId, $mes, $ano);

        $totalLimite = array_sum(array_column($orcamentos, 'valor_limite'));
        $totalGasto = array_sum(array_column($orcamentos, 'gasto_real'));
        $totalDisponivel = array_sum(array_column($orcamentos, 'disponivel'));

        $emAlerta = count(array_filter($orcamentos, fn($o) => $o['status'] === 'alerta'));
        $estourados = count(array_filter($orcamentos, fn($o) => $o['status'] === 'estourado'));

        $saudeFinanceira = $this->calcularSaudeFinanceira($orcamentos);

        return [
            'total_categorias'  => count($orcamentos),
            'total_limite'      => round($totalLimite, 2),
            'total_gasto'       => round($totalGasto, 2),
            'total_disponivel'  => round($totalDisponivel, 2),
            'percentual_geral'  => $totalLimite > 0 ? round(($totalGasto / $totalLimite) * 100, 1) : 0,
            'em_alerta'         => $emAlerta,
            'estourados'        => $estourados,
            'saude_financeira'  => $saudeFinanceira,
            'orcamentos'        => $orcamentos,
        ];
    }

    /**
     * Insights automáticos — comparativo com mês anterior
     */
    public function getInsights(int $userId, int $mes, int $ano): array
    {
        $insights = [];
        $orcamentos = $this->listarComProgresso($userId, $mes, $ano);

        // Dados do mês anterior
        $mesAnterior = $mes === 1 ? 12 : $mes - 1;
        $anoAnterior = $mes === 1 ? $ano - 1 : $ano;

        foreach ($orcamentos as $orc) {
            $gastoAnterior = $this->repo->getGastoRealComFallback(
                $userId,
                $orc['categoria_id'],
                $mesAnterior,
                $anoAnterior
            );

            // Insight: categoria perto de estourar
            if ($orc['percentual'] >= 80 && $orc['percentual'] < 100) {
                $disponivel = $orc['disponivel'];
                $insights[] = [
                    'tipo'      => 'alerta',
                    'icone'     => 'fa-triangle-exclamation',
                    'cor'       => '#f59e0b',
                    'titulo'    => "{$orc['categoria_nome']} está em {$orc['percentual']}%",
                    'mensagem'  => "Restam R$ " . number_format($disponivel, 2, ',', '.') . " nesta categoria",
                ];
            }

            // Insight: categoria estourada
            if ($orc['percentual'] > 100) {
                $insights[] = [
                    'tipo'      => 'perigo',
                    'icone'     => 'fa-circle-exclamation',
                    'cor'       => '#ef4444',
                    'titulo'    => "{$orc['categoria_nome']} estourou o orçamento!",
                    'mensagem'  => "Excedido em R$ " . number_format($orc['excedido'], 2, ',', '.'),
                ];
            }

            // Insight: comparativo com mês anterior
            if ($gastoAnterior > 0 && $orc['gasto_real'] > 0) {
                $variacao = (($orc['gasto_real'] - $gastoAnterior) / $gastoAnterior) * 100;
                if (abs($variacao) >= 20) {
                    $direcao = $variacao > 0 ? 'mais' : 'menos';
                    $insights[] = [
                        'tipo'      => $variacao > 0 ? 'info' : 'positivo',
                        'icone'     => $variacao > 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down',
                        'cor'       => $variacao > 0 ? '#f97316' : '#10b981',
                        'titulo'    => "{$orc['categoria_nome']}: " . abs(round($variacao)) . "% {$direcao}",
                        'mensagem'  => "Comparado ao mês anterior",
                    ];
                }
            }

            // Insight: categoria com bastante folga
            if ($orc['percentual'] <= 30 && $orc['gasto_real'] > 0) {
                $insights[] = [
                    'tipo'      => 'positivo',
                    'icone'     => 'fa-circle-check',
                    'cor'       => '#10b981',
                    'titulo'    => "{$orc['categoria_nome']} está sob controle",
                    'mensagem'  => "Apenas {$orc['percentual']}% utilizado",
                ];
            }
        }

        // Ordenar: perigos primeiro, alertas depois, positivos por último
        $prioridade = ['perigo' => 0, 'alerta' => 1, 'info' => 2, 'positivo' => 3];
        usort($insights, fn($a, $b) => ($prioridade[$a['tipo']] ?? 9) <=> ($prioridade[$b['tipo']] ?? 9));

        return array_slice($insights, 0, 8); // Máximo 8 insights
    }

    // ============================================================
    // MÉTODOS PRIVADOS
    // ============================================================

    /**
     * Calcula rollover (sobra do mês anterior)
     */
    private function calcularRollover(int $userId, int $categoriaId, int $mes, int $ano): float
    {
        $mesAnterior = $mes === 1 ? 12 : $mes - 1;
        $anoAnterior = $mes === 1 ? $ano - 1 : $ano;

        $orcAnterior = $this->repo->findByCategoriaAndMonth($userId, $categoriaId, $mesAnterior, $anoAnterior);
        if (!$orcAnterior) return 0;

        $gastoAnterior = $this->repo->getGastoRealComFallback($userId, $categoriaId, $mesAnterior, $anoAnterior);
        $sobra = $orcAnterior->valor_limite - $gastoAnterior;

        return max(0, round($sobra, 2));
    }

    /**
     * Calcula tendência de gasto (subindo/descendo/estável)
     */
    private function calcularTendencia(int $userId, int $categoriaId): string
    {
        $gastos = [];
        for ($i = 3; $i >= 1; $i--) {
            $date = new \DateTime();
            $date->modify("-{$i} months");
            $gastos[] = $this->repo->getGastoRealComFallback(
                $userId,
                $categoriaId,
                (int)$date->format('m'),
                (int)$date->format('Y')
            );
        }

        if (count(array_filter($gastos)) < 2) return 'insuficiente';

        $ultimos = array_values(array_filter($gastos));
        if (count($ultimos) < 2) return 'estavel';

        $ultimo = end($ultimos);
        $penultimo = prev($ultimos);

        if ($penultimo == 0) return 'estavel';

        $variacao = (($ultimo - $penultimo) / $penultimo) * 100;

        if ($variacao > 15) return 'subindo';
        if ($variacao < -15) return 'descendo';
        return 'estavel';
    }

    /**
     * Determina o status visual do orçamento
     */
    private function getStatusOrcamento(float $percentual): string
    {
        if ($percentual >= 100) return 'estourado';
        if ($percentual >= 80) return 'alerta';
        if ($percentual >= 50) return 'atencao';
        return 'ok';
    }

    /**
     * Calcula score de saúde financeira (0-100)
     */
    private function calcularSaudeFinanceira(array $orcamentos): array
    {
        if (empty($orcamentos)) {
            return ['score' => 100, 'label' => 'Excelente', 'cor' => '#10b981'];
        }

        $scores = [];
        foreach ($orcamentos as $orc) {
            if ($orc['percentual'] <= 50) $scores[] = 100;
            elseif ($orc['percentual'] <= 80) $scores[] = 70;
            elseif ($orc['percentual'] <= 100) $scores[] = 40;
            else $scores[] = max(0, 20 - ($orc['percentual'] - 100));
        }

        $score = round(array_sum($scores) / count($scores));

        if ($score >= 80) return ['score' => $score, 'label' => 'Excelente', 'cor' => '#10b981'];
        if ($score >= 60) return ['score' => $score, 'label' => 'Bom', 'cor' => '#3b82f6'];
        if ($score >= 40) return ['score' => $score, 'label' => 'Atenção', 'cor' => '#f59e0b'];
        return ['score' => $score, 'label' => 'Crítico', 'cor' => '#ef4444'];
    }
}
