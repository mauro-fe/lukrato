<?php

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\CartaoCreditoService;
use Application\Services\CartaoFaturaService;
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
        $contaId = isset($_GET['conta_id']) ? (int) $_GET['conta_id'] : null;
        $apenasAtivos = (int) ($_GET['only_active'] ?? 1) === 1;
        $arquivados = (int) ($_GET['archived'] ?? 0) === 1;

        if ($arquivados) {
            $cartoes = $this->service->listarCartoesArquivados($userId);
        } else {
            $cartoes = $this->service->listarCartoes($userId, $contaId, $apenasAtivos);
        }

        Response::json($cartoes);
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
            Response::json(['status' => 'error', 'message' => 'Cartão não encontrado'], 404);
            return;
        }

        Response::json($cartao);
    }

    /**
     * POST /api/cartoes
     * Criar novo cartão
     */
    public function store(): void
    {
        $userId = Auth::id();
        $data = $this->getRequestPayload();

        $dto = CreateCartaoCreditoDTO::fromArray($data, $userId);
        $resultado = $this->service->criarCartao($dto);

        if (!$resultado['success']) {
            Response::json([
                'status' => 'error',
                'message' => $resultado['message'],
                'errors' => $resultado['errors'] ?? null,
            ], 422);
            return;
        }

        // 🎮 GAMIFICAÇÃO: Verificar conquistas após criar cartão
        $gamificationResult = [];
        try {
            error_log("🎮 [GAMIFICATION] Verificando conquistas para user_id: {$userId}");
            $achievementService = new \Application\Services\AchievementService();
            $newAchievements = $achievementService->checkAndUnlockAchievements($userId, 'card_created');
            
            error_log("🎮 [GAMIFICATION] Conquistas encontradas: " . count($newAchievements));
            
            if (!empty($newAchievements)) {
                $gamificationResult['achievements'] = $newAchievements;
                error_log("🎮 [GAMIFICATION] " . count($newAchievements) . " conquistas desbloqueadas após criar cartão");
                error_log("🎮 [GAMIFICATION] Conquistas: " . json_encode($newAchievements));
            } else {
                error_log("ℹ️ [GAMIFICATION] Nenhuma conquista nova para desbloquear");
            }
        } catch (\Exception $e) {
            error_log("❌ [GAMIFICATION] Erro ao verificar conquistas: " . $e->getMessage());
            error_log("❌ [GAMIFICATION] Stack trace: " . $e->getTraceAsString());
        }

        Response::json([
            'ok' => true,
            'id' => $resultado['id'],
            'data' => $resultado['data'],
            'gamification' => $gamificationResult,
        ], 201);
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
            Response::json([
                'status' => 'error',
                'message' => $resultado['message'],
                'errors' => $resultado['errors'] ?? null,
            ], isset($resultado['message']) && str_contains($resultado['message'], 'não encontrado') ? 404 : 422);
            return;
        }

        Response::json([
            'ok' => true,
            'data' => $resultado['data'],
        ]);
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
            Response::json(['status' => 'error', 'message' => $resultado['message']], 404);
            return;
        }

        Response::json($resultado);
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
            Response::json(['status' => 'error', 'message' => $resultado['message']], 404);
            return;
        }

        Response::json($resultado);
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
            Response::json(['status' => 'error', 'message' => $resultado['message']], 404);
            return;
        }

        Response::json($resultado);
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
            Response::json(['status' => 'error', 'message' => $resultado['message']], 404);
            return;
        }

        Response::json($resultado);
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
            Response::json([
                'status' => $resultado['requires_confirmation'] ?? false ? 'confirm_delete' : 'error',
                'message' => $resultado['message'],
                'requires_confirmation' => $resultado['requires_confirmation'] ?? false,
                'total_lancamentos' => $resultado['total_lancamentos'] ?? 0,
            ], $statusCode);
            return;
        }

        Response::json($resultado);
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
            Response::json(['status' => 'error', 'message' => $resultado['message']], 404);
            return;
        }

        Response::json([
            'ok' => true,
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
        Response::json($resumo);
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
            Response::json(['status' => 'error', 'message' => 'Mês inválido'], 400);
            return;
        }

        try {
            $fatura = $this->faturaService->obterFaturaMes($id, $mes, $ano);
            Response::json($fatura);
        } catch (\Exception $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }

    /**
     * POST /api/cartoes/{id}/fatura/pagar
     * Pagar a fatura completa de um mês
     */
    public function pagarFatura(int $id): void
    {
        $userId = Auth::id();
        $data = $this->getRequestPayload();

        $mes = $data['mes'] ?? (int) date('n');
        $ano = $data['ano'] ?? (int) date('Y');

        try {
            $resultado = $this->faturaService->pagarFatura($id, (int)$mes, (int)$ano, $userId);
            
            // 🎮 GAMIFICAÇÃO: Verificar conquistas após pagar fatura
            $gamificationResult = [];
            try {
                error_log("🎮 [GAMIFICATION] Verificando conquistas para user_id: {$userId}");
                $achievementService = new \Application\Services\AchievementService();
                $newAchievements = $achievementService->checkAndUnlockAchievements($userId, 'invoice_paid');
                
                error_log("🎮 [GAMIFICATION] Conquistas encontradas: " . count($newAchievements));
                
                if (!empty($newAchievements)) {
                    $gamificationResult['achievements'] = $newAchievements;
                    $resultado['gamification'] = $gamificationResult;
                    error_log("🎮 [GAMIFICATION] " . count($newAchievements) . " conquistas desbloqueadas após pagar fatura");
                    error_log("🎮 [GAMIFICATION] Conquistas: " . json_encode($newAchievements));
                } else {
                    error_log("ℹ️ [GAMIFICATION] Nenhuma conquista nova para desbloquear");
                }
            } catch (\Exception $e) {
                error_log("❌ [GAMIFICATION] Erro ao verificar conquistas: " . $e->getMessage());
                error_log("❌ [GAMIFICATION] Stack trace: " . $e->getTraceAsString());
            }
            
            Response::json($resultado);
        } catch (\Exception $e) {
            Response::json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
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
            Response::json([
                'status' => 'error',
                'message' => 'Nenhuma parcela selecionada'
            ], 400);
            return;
        }

        try {
            $resultado = $this->faturaService->pagarParcelas($id, $parcelaIds, (int)$mes, (int)$ano, $userId);
            Response::json($resultado);
        } catch (\Exception $e) {
            Response::json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
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
            Response::json(['meses' => $meses]);
        } catch (\Exception $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 404);
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
            $historico = $this->faturaService->obterHistoricoFaturasPagas($id, $limite);
            Response::json($historico);
        } catch (\Exception $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 404);
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
            Response::json(['status' => 'error', 'message' => 'Cartão não encontrado'], 404);
            return;
        }

        $mes = isset($_GET['mes']) ? (int) $_GET['mes'] : (int) date('n');
        $ano = isset($_GET['ano']) ? (int) $_GET['ano'] : (int) date('Y');

        error_log("📊 [ParcelamentosResumo] Cartão: {$id}, Mês: {$mes}, Ano: {$ano}");

        try {
            $resumo = $this->faturaService->obterResumoParcelamentos($id, $mes, $ano);
            Response::json($resumo);
        } catch (\Exception $e) {
            error_log("❌ [ParcelamentosResumo] Erro: " . $e->getMessage());
            error_log($e->getTraceAsString());

            // Retorna dados vazios ao invés de erro 500
            Response::json([
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
                error_log("Erro ao verificar vencimentos: " . $e->getMessage());
            }

            // Buscar limites baixos com tratamento de erro
            try {
                $limitesBaixos = $this->service->verificarLimitesBaixos($userId);
            } catch (\Exception $e) {
                error_log("Erro ao verificar limites baixos: " . $e->getMessage());
            }

            $alertas = array_merge($vencimentos, $limitesBaixos);

            // Ordenar por gravidade (crítico primeiro)
            usort($alertas, function ($a, $b) {
                $ordem = ['critico' => 0, 'atencao' => 1];
                return ($ordem[$a['gravidade']] ?? 2) <=> ($ordem[$b['gravidade']] ?? 2);
            });

            Response::json([
                'total' => count($alertas),
                'alertas' => $alertas,
                'por_tipo' => [
                    'vencimentos' => count($vencimentos),
                    'limites_baixos' => count($limitesBaixos),
                ]
            ]);
        } catch (\Exception $e) {
            error_log("Erro geral em alertas: " . $e->getMessage());
            Response::json([
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
            Response::json($relatorio);
        } catch (\Exception $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 500);
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
            Response::json(['status' => 'error', 'message' => 'Mês e ano são obrigatórios'], 400);
            return;
        }

        try {
            $status = $this->faturaService->faturaEstaPaga($id, $mes, $ano, $userId);

            if ($status === null) {
                Response::json(['pago' => false]);
            } else {
                Response::json($status);
            }
        } catch (\Exception $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 500);
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

        error_log("🔍 [Controller] desfazerPagamentoFatura - ID={$id}, User={$userId}, Data=" . json_encode($data));

        $mes = isset($data['mes']) ? (int) $data['mes'] : null;
        $ano = isset($data['ano']) ? (int) $data['ano'] : null;

        error_log("📅 [Controller] Mês={$mes}, Ano={$ano}");

        if (!$mes || !$ano) {
            error_log("❌ [Controller] Mês ou ano faltando");
            Response::json(['status' => 'error', 'message' => 'Mês e ano são obrigatórios'], 400);
            return;
        }

        try {
            $resultado = $this->faturaService->desfazerPagamentoFatura($id, $mes, $ano, $userId);
            Response::json($resultado);
        } catch (\Exception $e) {
            error_log("❌ [Controller] Erro: " . $e->getMessage());
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * POST /api/cartoes/parcelas/{id}/desfazer-pagamento
     * Desfazer pagamento de uma parcela específica
     */
    public function desfazerPagamentoParcela(int $id): void
    {
        $userId = Auth::id();

        error_log("🔍 [Controller] desfazerPagamentoParcela - Parcela ID={$id}, User={$userId}");

        try {
            $resultado = $this->faturaService->desfazerPagamentoParcela($id, $userId);
            Response::json($resultado);
        } catch (\Exception $e) {
            error_log("❌ [Controller] Erro ao desfazer parcela: " . $e->getMessage());
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}