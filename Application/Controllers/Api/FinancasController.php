<?php

declare(strict_types=1);

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Services\MetaService;
use Application\Services\OrcamentoService;
use Application\Validators\MetaValidator;
use Application\Validators\OrcamentoValidator;

/**
 * Controller API para o módulo Finanças (Metas + Orçamentos)
 */
class FinancasController extends BaseController
{
    private MetaService $metaService;
    private OrcamentoService $orcamentoService;

    public function __construct()
    {
        parent::__construct();
        $this->metaService = new MetaService();
        $this->orcamentoService = new OrcamentoService();
    }

    // ============================================================
    // DASHBOARD FINANÇAS
    // ============================================================

    /**
     * GET /api/financas/resumo — Dashboard geral
     */
    public function resumo(): void
    {
        $this->requireAuthApi();

        try {
            $mes = (int) ($this->getQuery('mes') ?: date('m'));
            $ano = (int) ($this->getQuery('ano') ?: date('Y'));

            $orcamentoResumo = $this->orcamentoService->resumo($this->userId, $mes, $ano);
            $metasResumo = $this->metaService->resumo($this->userId);
            $insights = $this->orcamentoService->getInsights($this->userId, $mes, $ano);

            Response::success([
                'orcamento' => $orcamentoResumo,
                'metas'     => $metasResumo,
                'insights'  => $insights,
                'mes'       => $mes,
                'ano'       => $ano,
            ], 'Resumo financeiro carregado');
        } catch (\Throwable $e) {
            $this->failAndLog($e, 'Erro ao carregar resumo financeiro.');
        }
    }

    // ============================================================
    // METAS
    // ============================================================

    /**
     * GET /api/financas/metas — Listar metas
     */
    public function metasIndex(): void
    {
        $this->requireAuthApi();

        try {
            $status = $this->getQuery('status');
            $metas = $this->metaService->listar($this->userId, $status);

            Response::success($metas, 'Metas carregadas');
        } catch (\Throwable $e) {
            $this->failAndLog($e, 'Erro ao carregar metas.');
        }
    }

    /**
     * POST /api/financas/metas — Criar meta
     */
    public function metasStore(): void
    {
        $this->requireAuthApi();

        try {
            $payload = $this->getRequestPayload();
            $errors = MetaValidator::validateCreate($payload);

            if (!empty($errors)) {
                Response::validationError($errors);
                return;
            }

            $meta = $this->metaService->criar($this->userId, $payload);

            // Verificar conquistas desbloqueadas
            $achievementService = new \Application\Services\AchievementService();
            $newAchievements = $achievementService->checkAndUnlockAchievements($this->userId, 'meta_criada');
            $gamification = [];
            if (!empty($newAchievements)) {
                $gamification['achievements'] = $newAchievements;
            }

            Response::success(array_merge(['meta' => $meta], $gamification ? ['gamification' => $gamification] : []), 'Meta criada com sucesso!', 201);
        } catch (\DomainException $e) {
            $this->fail($e->getMessage(), 403);
        } catch (\Throwable $e) {
            $this->failAndLog($e, 'Erro ao criar meta.');
        }
    }

    /**
     * PUT /api/financas/metas/{id} — Atualizar meta
     */
    public function metasUpdate(mixed $id = null): void
    {
        $this->requireAuthApi();

        try {
            $id = (int) $id;
            $payload = $this->getRequestPayload();
            $errors = MetaValidator::validateUpdate($payload);

            if (!empty($errors)) {
                Response::validationError($errors);
                return;
            }

            $meta = $this->metaService->atualizar($this->userId, $id, $payload);
            if (!$meta) {
                Response::notFound('Meta não encontrada.');
                return;
            }

            Response::success($meta, 'Meta atualizada com sucesso!');
        } catch (\Throwable $e) {
            $this->failAndLog($e, 'Erro ao atualizar meta.');
        }
    }

    /**
     * POST /api/financas/metas/{id}/aporte — Adicionar aporte
     */
    public function metasAporte(mixed $id = null): void
    {
        $this->requireAuthApi();

        try {
            $id = (int) $id;
            $payload = $this->getRequestPayload();
            $errors = MetaValidator::validateAporte($payload);

            if (!empty($errors)) {
                Response::validationError($errors);
                return;
            }

            $meta = $this->metaService->adicionarAporte($this->userId, $id, (float) $payload['valor']);
            if (!$meta) {
                Response::notFound('Meta não encontrada.');
                return;
            }

            // Verificar conquistas desbloqueadas (meta pode ter sido concluída)
            $achievementService = new \Application\Services\AchievementService();
            $newAchievements = $achievementService->checkAndUnlockAchievements($this->userId, 'meta_aporte');
            $gamification = [];
            if (!empty($newAchievements)) {
                $gamification['achievements'] = $newAchievements;
            }

            Response::success(array_merge(['meta' => $meta], $gamification ? ['gamification' => $gamification] : []), 'Aporte registrado com sucesso!');
        } catch (\DomainException $e) {
            $this->fail($e->getMessage(), 400);
        } catch (\Throwable $e) {
            $this->failAndLog($e, 'Erro ao registrar aporte.');
        }
    }

    /**
     * DELETE /api/financas/metas/{id} — Remover meta
     */
    public function metasDestroy(mixed $id = null): void
    {
        $this->requireAuthApi();

        try {
            $id = (int) $id;
            $deleted = $this->metaService->remover($this->userId, $id);

            if (!$deleted) {
                Response::notFound('Meta não encontrada.');
                return;
            }

            Response::success(null, 'Meta removida com sucesso!');
        } catch (\Throwable $e) {
            $this->failAndLog($e, 'Erro ao remover meta.');
        }
    }

    /**
     * GET /api/financas/metas/templates — Templates de metas
     */
    public function metasTemplates(): void
    {
        $this->requireAuthApi();

        try {
            $templates = $this->metaService->getTemplates();
            $sugestaoEmergencia = $this->metaService->sugerirReservaEmergencia($this->userId);

            // Adicionar sugestão de valor ao template de emergência
            foreach ($templates as &$t) {
                if ($t['tipo'] === 'emergencia' && $sugestaoEmergencia > 0) {
                    $t['valor_sugerido'] = $sugestaoEmergencia;
                }
            }

            Response::success($templates, 'Templates carregados');
        } catch (\Throwable $e) {
            $this->failAndLog($e, 'Erro ao carregar templates.');
        }
    }

    // ============================================================
    // ORÇAMENTOS
    // ============================================================

    /**
     * GET /api/financas/orcamentos — Listar orçamentos do mês
     */
    public function orcamentosIndex(): void
    {
        $this->requireAuthApi();

        try {
            $mes = (int) ($this->getQuery('mes') ?: date('m'));
            $ano = (int) ($this->getQuery('ano') ?: date('Y'));

            $orcamentos = $this->orcamentoService->listarComProgresso($this->userId, $mes, $ano);
            Response::success($orcamentos, 'Orçamentos carregados');
        } catch (\Throwable $e) {
            $this->failAndLog($e, 'Erro ao carregar orçamentos.');
        }
    }

    /**
     * POST /api/financas/orcamentos — Salvar orçamento individual
     */
    public function orcamentosStore(): void
    {
        $this->requireAuthApi();

        try {
            $payload = $this->getRequestPayload();
            $errors = OrcamentoValidator::validateSave($payload);

            if (!empty($errors)) {
                Response::validationError($errors);
                return;
            }

            $mes = (int) ($payload['mes'] ?? date('m'));
            $ano = (int) ($payload['ano'] ?? date('Y'));

            $orcamentos = $this->orcamentoService->salvar(
                $this->userId,
                (int) $payload['categoria_id'],
                $mes,
                $ano,
                $payload
            );

            Response::success($orcamentos, 'Orçamento salvo com sucesso!');
        } catch (\DomainException $e) {
            $this->fail($e->getMessage(), 403);
        } catch (\Throwable $e) {
            $this->failAndLog($e, 'Erro ao salvar orçamento.');
        }
    }

    /**
     * POST /api/financas/orcamentos/bulk — Salvar múltiplos orçamentos
     */
    public function orcamentosBulk(): void
    {
        $this->requireAuthApi();

        try {
            $payload = $this->getRequestPayload();
            $errors = OrcamentoValidator::validateBulk($payload);

            if (!empty($errors)) {
                Response::validationError($errors);
                return;
            }

            $mes = (int) ($payload['mes'] ?? date('m'));
            $ano = (int) ($payload['ano'] ?? date('Y'));

            $result = $this->orcamentoService->salvarMultiplos(
                $this->userId,
                $mes,
                $ano,
                $payload['orcamentos']
            );

            Response::success($result, 'Orçamentos salvos com sucesso!');
        } catch (\DomainException $e) {
            $this->fail($e->getMessage(), 403);
        } catch (\Throwable $e) {
            $this->failAndLog($e, 'Erro ao salvar orçamentos.');
        }
    }

    /**
     * DELETE /api/financas/orcamentos/{id} — Remover orçamento
     */
    public function orcamentosDestroy(mixed $id = null): void
    {
        $this->requireAuthApi();

        try {
            $id = (int) $id;
            $deleted = $this->orcamentoService->remover($this->userId, $id);

            if (!$deleted) {
                Response::notFound('Orçamento não encontrado.');
                return;
            }

            Response::success(null, 'Orçamento removido com sucesso!');
        } catch (\Throwable $e) {
            $this->failAndLog($e, 'Erro ao remover orçamento.');
        }
    }

    /**
     * GET /api/financas/orcamentos/sugestoes — Auto-sugestão inteligente
     */
    public function orcamentosSugestoes(): void
    {
        $this->requireAuthApi();

        try {
            $sugestoes = $this->orcamentoService->autoSugerir($this->userId);
            Response::success($sugestoes, 'Sugestões calculadas');
        } catch (\Throwable $e) {
            $this->failAndLog($e, 'Erro ao gerar sugestões.');
        }
    }

    /**
     * POST /api/financas/orcamentos/aplicar-sugestoes — Aplicar sugestões de uma vez
     */
    public function orcamentosAplicarSugestoes(): void
    {
        $this->requireAuthApi();

        try {
            $payload = $this->getRequestPayload();
            $mes = (int) ($payload['mes'] ?? date('m'));
            $ano = (int) ($payload['ano'] ?? date('Y'));
            $sugestoes = $payload['sugestoes'] ?? [];

            $result = $this->orcamentoService->aplicarSugestoes($this->userId, $mes, $ano, $sugestoes);
            Response::success($result, 'Sugestões aplicadas com sucesso!');
        } catch (\Throwable $e) {
            $this->failAndLog($e, 'Erro ao aplicar sugestões.');
        }
    }

    /**
     * POST /api/financas/orcamentos/copiar-mes — Copiar do mês anterior
     */
    public function orcamentosCopiarMes(): void
    {
        $this->requireAuthApi();

        try {
            $payload = $this->getRequestPayload();
            $mes = (int) ($payload['mes'] ?? date('m'));
            $ano = (int) ($payload['ano'] ?? date('Y'));

            $result = $this->orcamentoService->copiarMesAnterior($this->userId, $mes, $ano);
            Response::success($result, "{$result['copiados']} orçamentos copiados!");
        } catch (\Throwable $e) {
            $this->failAndLog($e, 'Erro ao copiar orçamentos.');
        }
    }

    /**
     * GET /api/financas/insights — Insights automáticos
     */
    public function insights(): void
    {
        $this->requireAuthApi();

        try {
            $mes = (int) ($this->getQuery('mes') ?: date('m'));
            $ano = (int) ($this->getQuery('ano') ?: date('Y'));

            $insights = $this->orcamentoService->getInsights($this->userId, $mes, $ano);
            Response::success($insights, 'Insights carregados');
        } catch (\Throwable $e) {
            $this->failAndLog($e, 'Erro ao gerar insights.');
        }
    }
}
