<?php

namespace Application\Controllers\Api\Cartao;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\Cartao\CartaoCreditoService;
use Application\Services\Cartao\CartaoFaturaService;
use Application\Services\Infrastructure\LogService;
use Application\Services\Plan\PlanLimitService;
use Application\Enums\LogCategory;
use Application\DTO\CreateCartaoCreditoDTO;
use Application\DTO\UpdateCartaoCreditoDTO;

class CartoesController
{
    private CartaoCreditoService $service;
    private CartaoFaturaService $faturaService;

    public function __construct()
    {
        $this->service = new CartaoCreditoService();
        $this->faturaService = new CartaoFaturaService();
    }

    private function getRequestPayload(): array
    {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        if (empty($data) && strtolower($_SERVER['REQUEST_METHOD'] ?? '') === 'post') {
            $data = $_POST;
        }
        return $data;
    }

    /**
     * GET /api/cartoes
     * Listar cartões do usuário
     */
    public function index(): void
    {
        $userId = Auth::id();

        // Liberar lock da sessão para permitir requisições paralelas
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $contaId = isset($_GET['conta_id']) ? (int) $_GET['conta_id'] : null;
        $apenasAtivos = (int) ($_GET['only_active'] ?? 1) === 1;
        $arquivados = (int) ($_GET['archived'] ?? 0) === 1;

        if ($arquivados) {
            $cartoes = $this->service->listarCartoesArquivados($userId);
        } else {
            $cartoes = $this->service->listarCartoes($userId, $contaId, $apenasAtivos);
        }

        Response::success($cartoes);
    }

    /**
     * GET /api/cartoes/{id}
     * Buscar cartão específico
     */
    public function show(int $id): void
    {
        $userId = Auth::id();
        $cartao = $this->service->buscarCartao($id, $userId);

        if (!$cartao) {
            Response::error('Cartão não encontrado', 404);
            return;
        }

        Response::success($cartao);
    }

    /**
     * POST /api/cartoes
     * Criar novo cartão
     */
    public function store(): void
    {
        $userId = Auth::id();
        $data = $this->getRequestPayload();

        // 🔒 VERIFICAR LIMITE DO PLANO
        $planLimitService = new PlanLimitService();
        $limitCheck = $planLimitService->canCreateCartao($userId);

        if (!$limitCheck['allowed']) {
            Response::error($limitCheck['message'], 403, [
                'limit_reached' => true,
                'upgrade_url' => $limitCheck['upgrade_url'],
                'limit_info' => [
                    'limit' => $limitCheck['limit'],
                    'used' => $limitCheck['used'],
                    'remaining' => $limitCheck['remaining']
                ]
            ]);
            return;
        }

        $dto = CreateCartaoCreditoDTO::fromArray($data, $userId);
        $resultado = $this->service->criarCartao($dto);

        if (!$resultado['success']) {
            Response::error($resultado['message'], 422, $resultado['errors'] ?? null);
            return;
        }

        // 🎮 GAMIFICAÇÃO: Verificar conquistas após criar cartão
        $gamificationResult = [];
        try {
            $achievementService = new \Application\Services\Gamification\AchievementService();
            $newAchievements = $achievementService->checkAndUnlockAchievements($userId, 'card_created');

            if (!empty($newAchievements)) {
                $gamificationResult['achievements'] = $newAchievements;
            }
        } catch (\Exception $e) {
            LogService::captureException($e, LogCategory::GAMIFICATION, [
                'action' => 'check_achievements_card_created',
                'user_id' => $userId,
            ]);
        }

        Response::success([
            'id' => $resultado['id'],
            'data' => $resultado['data'],
            'gamification' => $gamificationResult,
        ], 'Success', 201);
    }

    /**
     * PUT /api/cartoes/{id}
     * Atualizar cartão
     */
    public function update(int $id): void
    {
        $userId = Auth::id();
        $data = $this->getRequestPayload();

        $dto = UpdateCartaoCreditoDTO::fromArray($data);
        $resultado = $this->service->atualizarCartao($id, $userId, $dto);

        if (!$resultado['success']) {
            Response::error(
                $resultado['message'],
                isset($resultado['message']) && str_contains($resultado['message'], 'não encontrado') ? 404 : 422,
                $resultado['errors'] ?? null
            );
            return;
        }

        Response::success(['data' => $resultado['data']]);
    }

    /**
     * POST /api/cartoes/{id}/desativar
     * Desativar cartão
     */
    public function deactivate(int $id): void
    {
        $userId = Auth::id();
        $resultado = $this->service->desativarCartao($id, $userId);

        if (!$resultado['success']) {
            Response::error($resultado['message'], 404);
            return;
        }

        Response::success($resultado);
    }

    /**
     * POST /api/cartoes/{id}/reactivate
     * Reativar cartão
     */
    public function reactivate(int $id): void
    {
        $userId = Auth::id();
        $resultado = $this->service->reativarCartao($id, $userId);

        if (!$resultado['success']) {
            Response::error($resultado['message'], 404);
            return;
        }

        Response::success($resultado);
    }

    /**
     * POST /api/cartoes/{id}/archive
     * Arquivar cartão
     */
    public function archive(int $id): void
    {
        $userId = Auth::id();
        $resultado = $this->service->arquivarCartao($id, $userId);

        if (!$resultado['success']) {
            Response::error($resultado['message'], 404);
            return;
        }

        Response::success($resultado);
    }

    /**
     * POST /api/cartoes/{id}/restore
     * Restaurar cartão arquivado
     */
    public function restore(int $id): void
    {
        $userId = Auth::id();
        $resultado = $this->service->restaurarCartao($id, $userId);

        if (!$resultado['success']) {
            Response::error($resultado['message'], 404);
            return;
        }

        Response::success($resultado);
    }

    /**
     * POST /api/cartoes/{id}/delete
     * Excluir cartão permanentemente
     */
    public function delete(int $id): void
    {
        $userId = Auth::id();
        $data = $this->getRequestPayload();
        $force = filter_var($data['force'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $resultado = $this->service->excluirCartaoPermanente($id, $userId, $force);

        if (!$resultado['success']) {
            $statusCode = isset($resultado['requires_confirmation']) && $resultado['requires_confirmation'] ? 422 : 404;
            Response::error($resultado['message'], $statusCode, [
                'status' => $resultado['requires_confirmation'] ?? false ? 'confirm_delete' : 'error',
                'requires_confirmation' => $resultado['requires_confirmation'] ?? false,
                'total_lancamentos' => $resultado['total_lancamentos'] ?? 0,
            ]);
            return;
        }

        Response::success($resultado);
    }

    /**
     * DELETE /api/cartoes/{id}
     * Excluir cartão (agora arquiva em vez de excluir)
     */
    public function destroy(int $id): void
    {
        // Agora redireciona para arquivar
        $this->archive($id);
    }

    /**
     * POST /api/cartoes/{id}/atualizar-limite
     * Atualizar limite disponível do cartão
     */
    public function updateLimit(int $id): void
    {
        $userId = Auth::id();
        $resultado = $this->service->atualizarLimiteDisponivel($id, $userId);

        if (!$resultado['success']) {
            Response::error($resultado['message'], 404);
            return;
        }

        Response::success([
            'limite_disponivel' => $resultado['limite_disponivel'],
            'limite_utilizado' => $resultado['limite_utilizado'],
            'percentual_uso' => $resultado['percentual_uso'],
        ]);
    }

    /**
     * GET /api/cartoes/resumo
     * Obter resumo geral dos cartões
     */
    public function summary(): void
    {
        $userId = Auth::id();
        $resumo = $this->service->obterResumo($userId);
        Response::success($resumo);
    }

    /**
     * GET /api/cartoes/{id}/fatura?mes=1&ano=2025
     * Obter fatura do mês de um cartão
     */
    public function fatura(int $id): void
    {
        $userId = Auth::id();

        $mes = isset($_GET['mes']) ? (int) $_GET['mes'] : (int) date('n');
        $ano = isset($_GET['ano']) ? (int) $_GET['ano'] : (int) date('Y');

        if ($mes < 1 || $mes > 12) {
            Response::error('Mês inválido', 400);
            return;
        }

        try {
            $fatura = $this->faturaService->obterFaturaMes($id, $mes, $ano, $userId);
            Response::success($fatura);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 404);
        }
    }

    /**
     * POST /api/cartoes/{id}/fatura/pagar
     * Pagar a fatura completa ou parcial de um mês
     */
    public function pagarFatura(int $id): void
    {
        $userId = Auth::id();
        $data = $this->getRequestPayload();

        $mes = $data['mes'] ?? (int) date('n');
        $ano = $data['ano'] ?? (int) date('Y');
        $contaId = isset($data['conta_id']) ? (int)$data['conta_id'] : null;
        $valorParcial = isset($data['valor_parcial']) ? (float)$data['valor_parcial'] : null;

        try {
            $resultado = $this->faturaService->pagarFatura($id, (int)$mes, (int)$ano, $userId, $contaId, $valorParcial);

            // 🎮 GAMIFICAÇÃO: Verificar conquistas após pagar fatura
            $gamificationResult = [];
            try {
                $achievementService = new \Application\Services\Gamification\AchievementService();
                $newAchievements = $achievementService->checkAndUnlockAchievements($userId, 'invoice_paid');

                if (!empty($newAchievements)) {
                    $gamificationResult['achievements'] = $newAchievements;
                    $resultado['gamification'] = $gamificationResult;
                }
            } catch (\Exception $e) {
                LogService::captureException($e, LogCategory::GAMIFICATION, [
                    'action' => 'check_achievements_invoice_paid',
                    'user_id' => $userId,
                    'cartao_id' => $id,
                ]);
            }

            Response::success($resultado);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * POST /api/cartoes/{id}/parcelas/pagar
     * Pagar parcelas individuais selecionadas
     */
    public function pagarParcelas(int $id): void
    {
        $userId = Auth::id();
        $data = $this->getRequestPayload();

        $parcelaIds = $data['parcela_ids'] ?? [];
        $mes = $data['mes'] ?? (int) date('n');
        $ano = $data['ano'] ?? (int) date('Y');

        if (empty($parcelaIds)) {
            Response::error('Nenhuma parcela selecionada', 400);
            return;
        }

        try {
            $resultado = $this->faturaService->pagarParcelas($id, $parcelaIds, (int)$mes, (int)$ano, $userId);
            Response::success($resultado);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * GET /api/cartoes/{id}/faturas-pendentes
     * Listar meses que têm faturas pendentes de pagamento
     */
    public function faturasPendentes(int $id): void
    {
        $userId = Auth::id();

        try {
            $meses = $this->faturaService->obterMesesComFaturasPendentes($id, $userId);
            Response::success(['meses' => $meses]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 404);
        }
    }

    /**
     * GET /api/cartoes/{id}/faturas-historico?limite=12
     * Obter histórico de faturas pagas
     */
    public function faturasHistorico(int $id): void
    {
        $userId = Auth::id();
        $limite = (int) ($_GET['limite'] ?? 12);

        try {
            $historico = $this->faturaService->obterHistoricoFaturasPagas($id, $userId, $limite);
            Response::success($historico);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 404);
        }
    }

    /**
     * GET /api/cartoes/{id}/parcelamentos-resumo?mes=1&ano=2026
     * Obter resumo dos parcelamentos ativos do cartão
     */
    public function parcelamentosResumo(int $id): void
    {
        $userId = Auth::id();

        // Verifica se o cartão pertence ao usuário
        $cartao = $this->service->buscarCartao($id, $userId);
        if (!$cartao) {
            Response::error('Cartão não encontrado', 404);
            return;
        }

        $mes = isset($_GET['mes']) ? (int) $_GET['mes'] : (int) date('n');
        $ano = isset($_GET['ano']) ? (int) $_GET['ano'] : (int) date('Y');

        try {
            $resumo = $this->faturaService->obterResumoParcelamentos($id, $mes, $ano, $userId);
            Response::success($resumo);
        } catch (\Exception $e) {
            LogService::captureException($e, LogCategory::CARTAO, [
                'action' => 'parcelamentos_resumo',
                'cartao_id' => $id,
                'mes' => $mes,
                'ano' => $ano,
            ]);

            // Retorna dados vazios ao invés de erro 500
            Response::success([
                'total_parcelamentos' => 0,
                'parcelamentos' => [],
                'projecao' => [
                    'tres_meses' => 0.0,
                    'seis_meses' => 0.0,
                ],
            ]);
        }
    }

    /**
     * GET /api/cartoes/alertas
     * Obter alertas de vencimentos próximos e limites baixos
     */
    public function alertas(): void
    {
        $userId = Auth::id();

        try {
            $vencimentos = [];
            $limitesBaixos = [];

            // Buscar vencimentos com tratamento de erro
            try {
                $vencimentos = $this->faturaService->verificarVencimentosProximos($userId, 7);
            } catch (\Exception $e) {
                LogService::captureException($e, LogCategory::CARTAO, [
                    'action' => 'verificar_vencimentos',
                    'user_id' => $userId,
                ]);
            }

            // Buscar limites baixos com tratamento de erro
            try {
                $limitesBaixos = $this->service->verificarLimitesBaixos($userId);
            } catch (\Exception $e) {
                LogService::captureException($e, LogCategory::CARTAO, [
                    'action' => 'verificar_limites_baixos',
                    'user_id' => $userId,
                ]);
            }

            $alertas = array_merge($vencimentos, $limitesBaixos);

            // Ordenar por gravidade (crítico primeiro)
            usort($alertas, function ($a, $b) {
                $ordem = ['critico' => 0, 'atencao' => 1];
                return ($ordem[$a['gravidade']] ?? 2) <=> ($ordem[$b['gravidade']] ?? 2);
            });

            Response::success([
                'total' => count($alertas),
                'alertas' => $alertas,
                'por_tipo' => [
                    'vencimentos' => count($vencimentos),
                    'limites_baixos' => count($limitesBaixos),
                ]
            ]);
        } catch (\Exception $e) {
            LogService::captureException($e, LogCategory::CARTAO, [
                'action' => 'alertas',
                'user_id' => $userId,
            ]);
            Response::success([
                'total' => 0,
                'alertas' => [],
                'por_tipo' => [
                    'vencimentos' => 0,
                    'limites_baixos' => 0,
                ],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * GET /api/cartoes/validar-integridade?corrigir=false
     * Validar integridade dos limites dos cartões
     */
    public function validarIntegridade(): void
    {
        $userId = Auth::id();
        $corrigir = isset($_GET['corrigir']) && $_GET['corrigir'] === 'true';

        try {
            $relatorio = $this->service->validarIntegridadeLimites($userId, $corrigir);
            Response::success($relatorio);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/cartoes/{id}/fatura/status?mes=X&ano=Y
     * Verificar se a fatura de um mês está paga
     */
    public function statusFatura(int $id): void
    {
        $userId = Auth::id();
        $mes = isset($_GET['mes']) ? (int) $_GET['mes'] : null;
        $ano = isset($_GET['ano']) ? (int) $_GET['ano'] : null;

        if (!$mes || !$ano) {
            Response::error('Mês e ano são obrigatórios', 400);
            return;
        }

        try {
            $status = $this->faturaService->faturaEstaPaga($id, $mes, $ano, $userId);

            if ($status === null) {
                Response::success(['pago' => false]);
            } else {
                Response::success($status);
            }
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * POST /api/cartoes/{id}/fatura/desfazer-pagamento
     * Desfazer pagamento de uma fatura
     */
    public function desfazerPagamentoFatura(int $id): void
    {
        $userId = Auth::id();
        $data = $this->getRequestPayload();

        $mes = isset($data['mes']) ? (int) $data['mes'] : null;
        $ano = isset($data['ano']) ? (int) $data['ano'] : null;

        if (!$mes || !$ano) {
            Response::error('Mês e ano são obrigatórios', 400);
            return;
        }

        try {
            $resultado = $this->faturaService->desfazerPagamentoFatura($id, $mes, $ano, $userId);
            Response::success($resultado);
        } catch (\Exception $e) {
            LogService::captureException($e, LogCategory::FATURA, [
                'action' => 'desfazer_pagamento_fatura',
                'cartao_id' => $id,
                'mes' => $mes,
                'ano' => $ano,
                'user_id' => $userId,
            ]);
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * POST /api/cartoes/parcelas/{id}/desfazer-pagamento
     * Desfazer pagamento de uma parcela específica
     */
    public function desfazerPagamentoParcela(int $id): void
    {
        $userId = Auth::id();

        try {
            $resultado = $this->faturaService->desfazerPagamentoParcela($id, $userId);
            Response::success($resultado);
        } catch (\Exception $e) {
            LogService::captureException($e, LogCategory::FATURA, [
                'action' => 'desfazer_pagamento_parcela',
                'parcela_id' => $id,
                'user_id' => $userId,
            ]);
            Response::error($e->getMessage(), 400);
        }
    }

    // ─── Recorrências / Assinaturas ───────────────────────────

    /**
     * GET /api/cartoes/recorrencias
     * Listar todas as assinaturas/recorrências ativas do usuário
     */
    public function recorrencias(): void
    {
        $userId = Auth::id();
        try {
            $service = new \Application\Services\Cartao\RecorrenciaCartaoService();
            $itens = $service->listarRecorrenciasAtivas($userId);
            Response::success($itens);
        } catch (\Exception $e) {
            LogService::captureException($e, LogCategory::CARTAO, [
                'action' => 'listar_recorrencias',
                'user_id' => $userId,
            ]);
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/cartoes/{id}/recorrencias
     * Listar recorrências ativas de um cartão específico
     */
    public function recorrenciasCartao(int $id): void
    {
        $userId = Auth::id();
        try {
            $service = new \Application\Services\Cartao\RecorrenciaCartaoService();
            $itens = $service->listarRecorrenciasAtivas($userId, $id);
            Response::success($itens);
        } catch (\Exception $e) {
            LogService::captureException($e, LogCategory::CARTAO, [
                'action' => 'listar_recorrencias_cartao',
                'cartao_id' => $id,
                'user_id' => $userId,
            ]);
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * POST /api/cartoes/recorrencias/{id}/cancelar
     * Cancelar uma assinatura/recorrência
     */
    public function cancelarRecorrencia(int $id): void
    {
        $userId = Auth::id();
        try {
            $service = new \Application\Services\Cartao\RecorrenciaCartaoService();
            $resultado = $service->cancelarRecorrencia($id, $userId);

            if ($resultado['success']) {
                Response::success($resultado);
            } else {
                Response::error($resultado['message'] ?? 'Erro ao cancelar recorrência', 400);
            }
        } catch (\Exception $e) {
            LogService::captureException($e, LogCategory::CARTAO, [
                'action' => 'cancelar_recorrencia',
                'item_pai_id' => $id,
                'user_id' => $userId,
            ]);
            Response::error($e->getMessage(), 500);
        }
    }
}
