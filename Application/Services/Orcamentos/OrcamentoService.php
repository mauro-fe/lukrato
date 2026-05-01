<?php

declare(strict_types=1);

namespace Application\Services\Orcamentos;

use Application\Container\ApplicationContainer;
use Application\Models\Categoria;
use Application\Models\OrcamentoCategoria;
use Application\Repositories\OrcamentoRepository;
use Application\Services\Plan\PlanLimitService;

/**
 * Serviço de Orçamento por Categoria
 * 
 * Gerencia orçamentos mensais com automações: auto-sugestão baseada
 * em histórico, detecção de tendências, insights inteligentes.
 */
class OrcamentoService
{
    protected OrcamentoRepository $repo;
    protected PlanLimitService $planLimit;
    protected OrcamentoMetricsService $metricsService;
    private ?OrcamentoInsightService $insightService = null;

    public function __construct(
        ?OrcamentoRepository $repo = null,
        ?PlanLimitService $planLimit = null,
        ?OrcamentoMetricsService $metricsService = null,
        ?OrcamentoInsightService $insightService = null
    ) {
        $this->repo = ApplicationContainer::resolveOrNew($repo, OrcamentoRepository::class);
        $this->planLimit = ApplicationContainer::resolveOrNew($planLimit, PlanLimitService::class);
        $this->metricsService = ApplicationContainer::resolveOrNew($metricsService, OrcamentoMetricsService::class);
        $this->insightService = $insightService !== null
            ? $insightService
            : ApplicationContainer::tryMake(OrcamentoInsightService::class);
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
                'categoria_nome'    => $orc->categoria->nome ?? 'Sem categoria',
                'categoria'         => [
                    'id'    => $orc->categoria_id,
                    'nome'  => $orc->categoria->nome ?? 'Sem categoria',
                    'icone' => $orc->categoria->icone ?? null,
                ],
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
     * 
     * @throws \DomainException Se limite do plano for atingido ao criar novo
     */
    public function salvar(int $userId, int $categoriaId, int $mes, int $ano, array $data): array
    {
        // Verificar se já existe orçamento para esta categoria/mês/ano
        $existente = $this->repo->existsByCategoriaAndMonth($userId, $categoriaId, $mes, $ano);

        // Se não existe, validar limite do plano antes de criar
        if (!$existente) {
            $this->planLimit->assertCanCreateOrcamento($userId);
        }

        $this->repo->upsert($userId, $categoriaId, $mes, $ano, $this->buildUpsertPayload($data));

        return $this->listarComProgresso($userId, $mes, $ano);
    }

    /**
     * Salvar múltiplos orçamentos de uma vez (bulk save)
     * 
     * @throws \DomainException Se limite do plano for excedido
     */
    public function salvarMultiplos(int $userId, int $mes, int $ano, array $orcamentos): array
    {
        // Contar quantos novos orçamentos serão criados (que não existem ainda)
        $orcamentosNormalizados = $this->normalizeBulkOrcamentos($orcamentos);
        $categoriaIds = array_keys($orcamentosNormalizados);
        $existingIds = $this->repo->getExistingCategoriaIdsForMonth($userId, $mes, $ano, $categoriaIds);
        $novosCount = count(array_diff($categoriaIds, $existingIds));

        // Verificar se o limite será excedido
        if ($novosCount > 0) {
            $canCreate = $this->planLimit->canCreateOrcamento($userId);
            if (!$canCreate['allowed']) {
                throw new \DomainException($canCreate['message']);
            }

            // Verificar se os novos orçamentos excedem o limite restante
            if ($canCreate['limit'] !== null && $novosCount > $canCreate['remaining']) {
                $restantes = $canCreate['remaining'];
                throw new \DomainException(
                    "Você só pode criar mais {$restantes} orçamento(s) no plano gratuito. " .
                        "Faça upgrade para orçamentos ilimitados."
                );
            }
        }

        foreach ($orcamentosNormalizados as $item) {
            $this->repo->upsert(
                $userId,
                $item['categoria_id'],
                $mes,
                $ano,
                $this->buildUpsertPayload($item)
            );
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
                $tendencia = $this->calcularTendencia($userId, $cat->id);

                // Sugere um limite ABAIXO da média para incentivar economia:
                // - tendência subindo:  corte de 15% (maior disciplina necessária)
                // - tendência estável:  corte de 10%
                // - tendência descendo: corte de  5% (já está melhorando)
                $fator = match ($tendencia) {
                    'subindo'  => 0.85,
                    'descendo' => 0.95,
                    default    => 0.90,
                };

                // Arredondar para cima para o múltiplo de 10 mais próximo
                $sugerido = ceil(($media * $fator) / 10) * 10;
                $economiaSugerida = round($media - $sugerido, 2);

                $sugestoes[] = [
                    'categoria_id'     => $cat->id,
                    'categoria_nome'   => $cat->nome,
                    'categoria'        => [
                        'id'    => $cat->id,
                        'nome'  => $cat->nome,
                        'icone' => $cat->icone ?? null,
                    ],
                    'media_gastos'     => round($media, 2),
                    'media_3_meses'    => round($media, 2),
                    'valor_sugerido'   => $sugerido,
                    'economia_sugerida' => $economiaSugerida,
                    'tendencia'        => $tendencia,
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

            $this->repo->upsert($userId, $catId, $mes, $ano, $this->buildUpsertPayload([
                'valor_limite' => $valor,
                'rollover'     => false,
                'alerta_80'    => true,
                'alerta_100'   => true,
            ]));
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
        return $this->insightService()->generate(
            $userId,
            $mes,
            $ano,
            $this->listarComProgresso($userId, $mes, $ano)
        );
    }

    // ============================================================
    // MÉTODOS PRIVADOS
    // ============================================================

    /**
     * Calcula rollover (sobra do mês anterior)
     */
    protected function calcularRollover(int $userId, int $categoriaId, int $mes, int $ano): float
    {
        return $this->metricsService->calcularRollover($this->repo, $userId, $categoriaId, $mes, $ano);
    }

    /**
     * Calcula tendência de gasto (subindo/descendo/estável)
     */
    protected function calcularTendencia(int $userId, int $categoriaId): string
    {
        return $this->metricsService->calcularTendencia($this->repo, $userId, $categoriaId);
    }

    /**
     * Determina o status visual do orçamento
     */
    protected function getStatusOrcamento(float $percentual): string
    {
        return $this->metricsService->getStatusOrcamento($percentual);
    }

    /**
     * Calcula score de saúde financeira (0-100)
     */
    protected function calcularSaudeFinanceira(array $orcamentos): array
    {
        return $this->metricsService->calcularSaudeFinanceira($orcamentos);
    }

    private function insightService(): OrcamentoInsightService
    {
        if ($this->insightService instanceof OrcamentoInsightService) {
            return $this->insightService;
        }

        $container = ApplicationContainer::getInstance() ?? ApplicationContainer::bootstrap();

        return $this->insightService = $container->makeWith(
            OrcamentoInsightService::class,
            ['repo' => $this->repo]
        );
    }

    /**
     * @param array<string, mixed> $data
     * @return array{valor_limite: float, rollover: bool, alerta_80: bool, alerta_100: bool}
     */
    private function buildUpsertPayload(array $data): array
    {
        return [
            'valor_limite' => round((float) ($data['valor_limite'] ?? 0), 2),
            'rollover'     => (bool) ($data['rollover'] ?? false),
            'alerta_80'    => (bool) ($data['alerta_80'] ?? true),
            'alerta_100'   => (bool) ($data['alerta_100'] ?? true),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $orcamentos
     * @return array<int, array{categoria_id:int, valor_limite:float, rollover:bool, alerta_80:bool, alerta_100:bool}>
     */
    private function normalizeBulkOrcamentos(array $orcamentos): array
    {
        $normalized = [];

        foreach ($orcamentos as $item) {
            $catId = (int) ($item['categoria_id'] ?? 0);
            $valor = round((float) ($item['valor_limite'] ?? 0), 2);

            if ($catId <= 0 || $valor <= 0) {
                continue;
            }

            // Mantem o ultimo payload para a categoria sem contar duplicados no limite.
            $normalized[$catId] = [
                'categoria_id' => $catId,
                'valor_limite' => $valor,
                'rollover'     => (bool) ($item['rollover'] ?? false),
                'alerta_80'    => (bool) ($item['alerta_80'] ?? true),
                'alerta_100'   => (bool) ($item['alerta_100'] ?? true),
            ];
        }

        return $normalized;
    }
}
