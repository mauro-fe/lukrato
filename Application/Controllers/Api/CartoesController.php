<?php

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\CartaoCreditoService;
use Application\DTO\CreateCartaoCreditoDTO;
use Application\DTO\UpdateCartaoCreditoDTO;

class CartoesController
{
    private CartaoCreditoService $service;

    public function __construct()
    {
        $this->service = new CartaoCreditoService();
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

        $cartoes = $this->service->listarCartoes($userId, $contaId, $apenasAtivos);
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

        Response::json([
            'ok' => true,
            'id' => $resultado['id'],
            'data' => $resultado['data'],
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
     * POST /api/cartoes/{id}/reativar
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
     * DELETE /api/cartoes/{id}
     * Excluir cartão
     */
    public function destroy(int $id): void
    {
        $userId = Auth::id();
        $data = $this->getRequestPayload();
        $force = (int) ($_GET['force'] ?? 0) === 1 || filter_var($data['force'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $resultado = $this->service->excluirCartao($id, $userId, $force);

        if (!$resultado['success']) {
            $statusCode = isset($resultado['requires_confirmation']) && $resultado['requires_confirmation'] ? 422 : 404;
            Response::json([
                'status' => $resultado['requires_confirmation'] ?? false ? 'confirm_delete' : 'error',
                'message' => $resultado['message'],
                'total_lancamentos' => $resultado['total_lancamentos'] ?? 0,
                'suggestion' => $resultado['requires_confirmation'] ?? false 
                    ? 'Reenvie a requisição com ?force=1 ou JSON {"force": true} para confirmar a exclusão permanente.'
                    : null,
            ], $statusCode);
            return;
        }

        Response::json($resultado);
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
}
