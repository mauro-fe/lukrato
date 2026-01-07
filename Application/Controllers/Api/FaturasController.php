<?php

declare(strict_types=1);

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\FaturaService;
use Application\Services\LogService;
use Exception;

/**
 * Controller para gerenciar faturas de cartão de crédito via API
 */
class FaturasController
{
    private FaturaService $faturaService;

    public function __construct()
    {
        $this->faturaService = new FaturaService();
    }

    /**
     * Listar faturas do usuário
     */
    public function index(): void
    {
        try {
            $usuarioId = Auth::id();
            if (!$usuarioId) {
                Response::json(['error' => 'Usuário não autenticado'], 401);
                return;
            }

            $cartaoId = $_GET['cartao_id'] ?? null;
            $status = $_GET['status'] ?? null;
            $mes = $_GET['mes'] ?? null;
            $ano = $_GET['ano'] ?? null;

            $faturas = $this->faturaService->listar(
                (int) $usuarioId,
                $cartaoId ? (int) $cartaoId : null,
                $status,
                $mes ? (int) $mes : null,
                $ano ? (int) $ano : null
            );

            Response::json([
                'success' => true,
                'data' => [
                    'faturas' => $faturas,
                ],
            ]);
        } catch (Exception $e) {
            LogService::error("Erro ao listar faturas", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            Response::json(['error' => 'Erro ao listar faturas'], 500);
        }
    }

    /**
     * Buscar fatura por ID
     */
    public function show(int $id): void
    {
        try {
            $usuarioId = Auth::id();
            if (!$usuarioId) {
                Response::json(['error' => 'Usuário não autenticado'], 401);
                return;
            }

            $fatura = $this->faturaService->buscar($id, (int) $usuarioId);

            if (!$fatura) {
                Response::json(['error' => 'Fatura não encontrada'], 404);
                return;
            }

            Response::json([
                'success' => true,
                'data' => $fatura,
            ]);
        } catch (Exception $e) {
            LogService::error("Erro ao buscar fatura {$id}", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            Response::json(['error' => 'Erro ao buscar fatura'], 500);
        }
    }

    /**
     * Criar nova fatura
     */
    public function store(): void
    {
        try {
            $usuarioId = Auth::id();
            if (!$usuarioId) {
                Response::json(['error' => 'Usuário não autenticado'], 401);
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                Response::json(['error' => 'Dados inválidos'], 400);
                return;
            }

            // Adicionar user_id aos dados
            $data['user_id'] = (int) $usuarioId;

            // Criar fatura
            $faturaId = $this->faturaService->criar($data);

            if (!$faturaId) {
                Response::json(['error' => 'Erro ao criar fatura'], 500);
                return;
            }

            // Buscar fatura criada
            $fatura = $this->faturaService->buscar($faturaId, (int) $usuarioId);

            Response::json([
                'success' => true,
                'message' => 'Fatura criada com sucesso',
                'data' => $fatura,
            ], 201);
        } catch (Exception $e) {
            LogService::error("Erro ao criar fatura", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Cancelar fatura
     */
    public function destroy(int $id): void
    {
        try {
            $usuarioId = Auth::id();
            if (!$usuarioId) {
                Response::json(['error' => 'Usuário não autenticado'], 401);
                return;
            }

            $success = $this->faturaService->cancelar($id, (int) $usuarioId);

            if (!$success) {
                Response::json(['error' => 'Erro ao cancelar fatura'], 500);
                return;
            }

            Response::json([
                'success' => true,
                'message' => 'Fatura cancelada com sucesso',
            ]);
        } catch (Exception $e) {
            LogService::error("Erro ao cancelar fatura {$id}", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            Response::json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Marcar item da fatura como pago/pendente
     */
    public function toggleItemPago(int $faturaId, int $itemId): void
    {
        try {
            $usuarioId = Auth::id();
            if (!$usuarioId) {
                Response::json(['error' => 'Usuário não autenticado'], 401);
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $pago = (bool)($data['pago'] ?? false);

            $success = $this->faturaService->toggleItemPago($faturaId, $itemId, (int)$usuarioId, $pago);

            if (!$success) {
                Response::json(['error' => 'Item não encontrado'], 404);
                return;
            }

            Response::json([
                'success' => true,
                'message' => $pago ? 'Item marcado como pago' : 'Pagamento desfeito',
            ]);
        } catch (Exception $e) {
            LogService::error("Erro ao atualizar item {$itemId} da fatura {$faturaId}", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            Response::json(['error' => $e->getMessage()], 500);
        }
    }
}
