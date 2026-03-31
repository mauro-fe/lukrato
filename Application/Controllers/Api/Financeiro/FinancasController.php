<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Financeiro;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Services\Demo\DemoPreviewService;
use Application\Services\Financeiro\MetaService;
use Application\Services\Financeiro\OrcamentoService;
use Application\Validators\MetaValidator;
use Application\Validators\OrcamentoValidator;

class FinancasController extends BaseController
{
    private MetaService $metaService;
    private OrcamentoService $orcamentoService;
    private DemoPreviewService $demoPreviewService;

    public function __construct(
        ?MetaService $metaService = null,
        ?OrcamentoService $orcamentoService = null,
        ?DemoPreviewService $demoPreviewService = null
    ) {
        parent::__construct();
        $this->metaService = $metaService ?? new MetaService();
        $this->orcamentoService = $orcamentoService ?? new OrcamentoService();
        $this->demoPreviewService = $demoPreviewService ?? new DemoPreviewService();
    }

    public function resumo(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $mes = $this->getIntQuery('mes', (int) date('m'));
            $ano = $this->getIntQuery('ano', (int) date('Y'));

            if ($this->demoPreviewService->shouldUsePreview($userId)) {
                return Response::successResponse(
                    $this->demoPreviewService->financeSummary($mes, $ano),
                    'Resumo financeiro carregado'
                );
            }

            $orcamentoResumo = $this->orcamentoService->resumo($userId, $mes, $ano);
            $metasResumo = $this->metaService->resumo($userId);
            $insights = $this->orcamentoService->getInsights($userId, $mes, $ano);

            return Response::successResponse([
                'orcamento' => $orcamentoResumo,
                'metas' => $metasResumo,
                'insights' => $insights,
                'mes' => $mes,
                'ano' => $ano,
            ], 'Resumo financeiro carregado');
        } catch (\Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao carregar resumo financeiro.');
        }
    }

    public function metasIndex(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $status = $this->getQuery('status');

            if ($this->demoPreviewService->shouldUsePreview($userId)) {
                return Response::successResponse(
                    $this->demoPreviewService->metas(is_string($status) ? $status : null),
                    'Metas carregadas'
                );
            }

            $metas = $this->metaService->listar($userId, $status);

            return Response::successResponse($metas, 'Metas carregadas');
        } catch (\Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao carregar metas.');
        }
    }

    public function metasStore(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $payload = $this->getRequestPayload();
            $errors = MetaValidator::validateCreate($payload);

            if (!empty($errors)) {
                return Response::validationErrorResponse($errors);
            }

            $meta = $this->metaService->criar($userId, $payload);

            $achievementService = new \Application\Services\Gamification\AchievementService();
            $newAchievements = $achievementService->checkAndUnlockAchievements($userId, 'meta_criada');
            $gamification = [];
            if (!empty($newAchievements)) {
                $gamification['achievements'] = $newAchievements;
            }

            return Response::successResponse(
                array_merge(['meta' => $meta], $gamification ? ['gamification' => $gamification] : []),
                'Meta criada com sucesso!',
                201
            );
        } catch (\DomainException $e) {
            return $this->domainErrorResponse($e, 'Não foi possivel criar a meta.', 403);
        } catch (\Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao criar meta.');
        }
    }

    public function metasUpdate(mixed $id = null): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $id = (int) $id;
            $payload = $this->getRequestPayload();
            $errors = MetaValidator::validateUpdate($payload);

            if (!empty($errors)) {
                return Response::validationErrorResponse($errors);
            }

            $meta = $this->metaService->atualizar($userId, $id, $payload);
            if (!$meta) {
                return Response::notFoundResponse('Meta não encontrada.');
            }

            return Response::successResponse($meta, 'Meta atualizada com sucesso!');
        } catch (\Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao atualizar meta.');
        }
    }

    public function metasAporte(mixed $id = null): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $id = (int) $id;
            $payload = $this->getRequestPayload();
            $errors = MetaValidator::validateAporte($payload);

            if (!empty($errors)) {
                return Response::validationErrorResponse($errors);
            }

            $meta = $this->metaService->adicionarAporte($userId, $id, (float) $payload['valor']);
            if (!$meta) {
                return Response::notFoundResponse('Meta não encontrada.');
            }

            $achievementService = new \Application\Services\Gamification\AchievementService();
            $newAchievements = $achievementService->checkAndUnlockAchievements($userId, 'meta_aporte');
            $gamification = [];
            if (!empty($newAchievements)) {
                $gamification['achievements'] = $newAchievements;
            }

            return Response::successResponse(
                array_merge(['meta' => $meta], $gamification ? ['gamification' => $gamification] : []),
                'Aporte registrado com sucesso!'
            );
        } catch (\DomainException $e) {
            return $this->domainErrorResponse($e, 'Não foi possivel registrar o aporte.', 400);
        } catch (\Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao registrar aporte.');
        }
    }

    public function metasDestroy(mixed $id = null): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $id = (int) $id;
            $deleted = $this->metaService->remover($userId, $id);

            if (!$deleted) {
                return Response::notFoundResponse('Meta não encontrada.');
            }

            return Response::successResponse(null, 'Meta removida com sucesso!');
        } catch (\Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao remover meta.');
        }
    }

    public function metasTemplates(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $templates = $this->metaService->getTemplates();
            $sugestaoEmergencia = $this->metaService->sugerirReservaEmergencia($userId);

            foreach ($templates as &$template) {
                if (($template['tipo'] ?? null) === 'emergencia' && $sugestaoEmergencia > 0) {
                    $template['valor_sugerido'] = $sugestaoEmergencia;
                }
            }
            unset($template);

            return Response::successResponse($templates, 'Templates carregados');
        } catch (\Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao carregar templates.');
        }
    }

    public function orcamentosIndex(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $mes = $this->getIntQuery('mes', (int) date('m'));
            $ano = $this->getIntQuery('ano', (int) date('Y'));

            if ($this->demoPreviewService->shouldUsePreview($userId)) {
                return Response::successResponse(
                    $this->demoPreviewService->orcamentos($mes, $ano),
                    'Orçamentos carregados'
                );
            }

            $orcamentos = $this->orcamentoService->listarComProgresso($userId, $mes, $ano);

            return Response::successResponse($orcamentos, 'Orçamentos carregados');
        } catch (\Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao carregar orçamentos.');
        }
    }

    public function orcamentosStore(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $payload = $this->getRequestPayload();
            $errors = OrcamentoValidator::validateSave($payload);

            if (!empty($errors)) {
                return Response::validationErrorResponse($errors);
            }

            $mes = (int) ($payload['mes'] ?? date('m'));
            $ano = (int) ($payload['ano'] ?? date('Y'));

            $orcamentos = $this->orcamentoService->salvar(
                $userId,
                (int) $payload['categoria_id'],
                $mes,
                $ano,
                $payload
            );

            return Response::successResponse($orcamentos, 'Orçamento salvo com sucesso!');
        } catch (\DomainException $e) {
            return $this->domainErrorResponse($e, 'Não foi possivel salvar o orcamento.', 403);
        } catch (\Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao salvar orçamento.');
        }
    }

    public function orcamentosBulk(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $payload = $this->getRequestPayload();
            $errors = OrcamentoValidator::validateBulk($payload);

            if (!empty($errors)) {
                return Response::validationErrorResponse($errors);
            }

            $mes = (int) ($payload['mes'] ?? date('m'));
            $ano = (int) ($payload['ano'] ?? date('Y'));

            $result = $this->orcamentoService->salvarMultiplos(
                $userId,
                $mes,
                $ano,
                $payload['orcamentos']
            );

            return Response::successResponse($result, 'Orçamentos salvos com sucesso!');
        } catch (\DomainException $e) {
            return $this->domainErrorResponse($e, 'Não foi possivel salvar os orçamentos.', 403);
        } catch (\Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao salvar orçamentos.');
        }
    }

    public function orcamentosDestroy(mixed $id = null): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $id = (int) $id;
            $deleted = $this->orcamentoService->remover($userId, $id);

            if (!$deleted) {
                return Response::notFoundResponse('Orçamento não encontrado.');
            }

            return Response::successResponse(null, 'Orçamento removido com sucesso!');
        } catch (\Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao remover orçamento.');
        }
    }

    public function orcamentosSugestoes(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $sugestoes = $this->orcamentoService->autoSugerir($userId);

            return Response::successResponse($sugestoes, 'Sugestões calculadas');
        } catch (\Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao gerar sugestões.');
        }
    }

    public function orcamentosAplicarSugestoes(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $payload = $this->getRequestPayload();
            $mes = (int) ($payload['mes'] ?? date('m'));
            $ano = (int) ($payload['ano'] ?? date('Y'));
            $sugestoes = $payload['sugestoes'] ?? [];

            $result = $this->orcamentoService->aplicarSugestoes($userId, $mes, $ano, $sugestoes);

            return Response::successResponse($result, 'Sugestões aplicadas com sucesso!');
        } catch (\Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao aplicar sugestões.');
        }
    }

    public function orcamentosCopiarMes(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $payload = $this->getRequestPayload();
            $mes = (int) ($payload['mes'] ?? date('m'));
            $ano = (int) ($payload['ano'] ?? date('Y'));

            $result = $this->orcamentoService->copiarMesAnterior($userId, $mes, $ano);

            return Response::successResponse($result, "{$result['copiados']} orçamentos copiados!");
        } catch (\Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao copiar orçamentos.');
        }
    }

    public function insights(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        try {
            $mes = $this->getIntQuery('mes', (int) date('m'));
            $ano = $this->getIntQuery('ano', (int) date('Y'));

            if ($this->demoPreviewService->shouldUsePreview($userId)) {
                return Response::successResponse(
                    $this->demoPreviewService->financeInsights($mes, $ano),
                    'Insights carregados'
                );
            }

            $insights = $this->orcamentoService->getInsights($userId, $mes, $ano);

            return Response::successResponse($insights, 'Insights carregados');
        } catch (\Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao gerar insights.');
        }
    }
}
