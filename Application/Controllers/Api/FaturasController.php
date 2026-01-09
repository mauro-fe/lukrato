<?php

declare(strict_types=1);

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\FaturaService;
use Application\Services\LogService;
use Exception;
use InvalidArgumentException;

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
            $usuarioId = $this->getAuthenticatedUserId();

            // Sanitizar e validar parâmetros de entrada
            $cartaoId = $this->getIntOrNull($_GET['cartao_id'] ?? null);
            $status = $this->sanitizeString($_GET['status'] ?? null);
            $mes = $this->getIntOrNull($_GET['mes'] ?? null);
            $ano = $this->getIntOrNull($_GET['ano'] ?? null);

            // Validar mês se fornecido
            if ($mes !== null && ($mes < 1 || $mes > 12)) {
                Response::json(['error' => 'Mês inválido. Deve estar entre 1 e 12'], 400);
                return;
            }

            // Validar ano se fornecido
            if ($ano !== null && ($ano < 2000 || $ano > 2100)) {
                Response::json(['error' => 'Ano inválido'], 400);
                return;
            }

            // Validar status se fornecido
            if ($status !== null && !in_array($status, ['pendente', 'parcial', 'paga', 'cancelado'], true)) {
                Response::json(['error' => 'Status inválido'], 400);
                return;
            }

            $faturas = $this->faturaService->listar(
                $usuarioId,
                $cartaoId,
                $status,
                $mes,
                $ano
            );

            Response::json([
                'success' => true,
                'data' => [
                    'faturas' => $faturas,
                    'total' => count($faturas)
                ],
            ]);
        } catch (InvalidArgumentException $e) {
            Response::json(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            $this->logError('Erro ao listar faturas', $e);
            Response::json(['error' => 'Erro ao listar faturas'], 500);
        }
    }

    /**
     * Buscar fatura por ID
     */
    public function show(int $id): void
    {
        try {
            $usuarioId = $this->getAuthenticatedUserId();

            // Validar ID
            if ($id <= 0) {
                Response::json(['error' => 'ID inválido'], 400);
                return;
            }

            $fatura = $this->faturaService->buscar($id, $usuarioId);

            if (!$fatura) {
                Response::json(['error' => 'Fatura não encontrada'], 404);
                return;
            }

            Response::json([
                'success' => true,
                'data' => $fatura,
            ]);
        } catch (InvalidArgumentException $e) {
            Response::json(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            $this->logError("Erro ao buscar fatura {$id}", $e);
            Response::json(['error' => 'Erro ao buscar fatura'], 500);
        }
    }

    /**
     * Criar nova fatura
     */
    public function store(): void
    {
        try {
            $usuarioId = $this->getAuthenticatedUserId();

            $data = $this->getJsonInput();

            if (!$data) {
                Response::json(['error' => 'Dados inválidos ou ausentes'], 400);
                return;
            }

            // Validar campos obrigatórios
            $requiredFields = ['cartao_id', 'descricao', 'valor_total', 'data_vencimento'];
            $missingFields = array_diff($requiredFields, array_keys($data));

            if (!empty($missingFields)) {
                Response::json([
                    'error' => 'Campos obrigatórios ausentes',
                    'missing_fields' => array_values($missingFields)
                ], 400);
                return;
            }

            // Validar tipos e valores
            if (!is_numeric($data['cartao_id']) || $data['cartao_id'] <= 0) {
                Response::json(['error' => 'ID do cartão inválido'], 400);
                return;
            }

            if (!is_numeric($data['valor_total']) || $data['valor_total'] <= 0) {
                Response::json(['error' => 'Valor total inválido'], 400);
                return;
            }

            if (!$this->isValidDate($data['data_vencimento'])) {
                Response::json(['error' => 'Data de vencimento inválida'], 400);
                return;
            }

            // Adicionar user_id aos dados
            $data['user_id'] = $usuarioId;

            // Sanitizar descrição
            $data['descricao'] = $this->sanitizeString($data['descricao']);

            // Criar fatura
            $faturaId = $this->faturaService->criar($data);

            if (!$faturaId) {
                Response::json(['error' => 'Erro ao criar fatura'], 500);
                return;
            }

            // Buscar fatura criada
            $fatura = $this->faturaService->buscar($faturaId, $usuarioId);

            Response::json([
                'success' => true,
                'message' => 'Fatura criada com sucesso',
                'data' => $fatura,
            ], 201);
        } catch (InvalidArgumentException $e) {
            Response::json(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            $this->logError('Erro ao criar fatura', $e);
            Response::json(['error' => 'Erro ao criar fatura. Tente novamente.'], 500);
        }
    }

    /**
     * Cancelar fatura
     */
    public function destroy(int $id): void
    {
        try {
            $usuarioId = $this->getAuthenticatedUserId();

            // Validar ID
            if ($id <= 0) {
                Response::json(['error' => 'ID inválido'], 400);
                return;
            }

            // Verificar se fatura existe e pertence ao usuário
            $fatura = $this->faturaService->buscar($id, $usuarioId);
            if (!$fatura) {
                Response::json(['error' => 'Fatura não encontrada'], 404);
                return;
            }

            // Verificar se fatura já está cancelada
            if (isset($fatura['status']) && $fatura['status'] === 'cancelado') {
                Response::json(['error' => 'Fatura já está cancelada'], 400);
                return;
            }

            $success = $this->faturaService->cancelar($id, $usuarioId);

            if (!$success) {
                Response::json(['error' => 'Erro ao cancelar fatura'], 500);
                return;
            }

            Response::json([
                'success' => true,
                'message' => 'Fatura cancelada com sucesso',
            ]);
        } catch (InvalidArgumentException $e) {
            Response::json(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            $this->logError("Erro ao cancelar fatura {$id}", $e);
            Response::json(['error' => 'Erro ao cancelar fatura'], 500);
        }
    }

    /**
     * Marcar item da fatura como pago/pendente
     */
    public function toggleItemPago(int $faturaId, int $itemId): void
    {
        try {
            $usuarioId = $this->getAuthenticatedUserId();

            // Validar IDs
            if ($faturaId <= 0 || $itemId <= 0) {
                Response::json(['error' => 'IDs inválidos'], 400);
                return;
            }

            $data = $this->getJsonInput();

            if (!isset($data['pago'])) {
                Response::json(['error' => 'Campo "pago" é obrigatório'], 400);
                return;
            }

            $pago = (bool)$data['pago'];

            // Verificar se fatura existe e pertence ao usuário
            $fatura = $this->faturaService->buscar($faturaId, $usuarioId);
            if (!$fatura) {
                Response::json(['error' => 'Fatura não encontrada'], 404);
                return;
            }

            // Verificar se fatura está cancelada
            if (isset($fatura['status']) && $fatura['status'] === 'cancelado') {
                Response::json(['error' => 'Não é possível modificar uma fatura cancelada'], 400);
                return;
            }

            $success = $this->faturaService->toggleItemPago($faturaId, $itemId, $usuarioId, $pago);

            if (!$success) {
                Response::json(['error' => 'Item não encontrado ou não pertence a esta fatura'], 404);
                return;
            }

            Response::json([
                'success' => true,
                'message' => $pago ? 'Item marcado como pago' : 'Pagamento desfeito',
            ]);
        } catch (InvalidArgumentException $e) {
            Response::json(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            $this->logError("Erro ao atualizar item {$itemId} da fatura {$faturaId}", $e);
            Response::json(['error' => 'Erro ao atualizar item'], 500);
        }
    }

    /**
     * Obter ID do usuário autenticado
     * 
     * @throws InvalidArgumentException Se usuário não está autenticado
     */
    private function getAuthenticatedUserId(): int
    {
        $usuarioId = Auth::id();

        if (!$usuarioId) {
            throw new InvalidArgumentException('Usuário não autenticado');
        }

        return (int)$usuarioId;
    }

    /**
     * Obter dados JSON do corpo da requisição
     * 
     * @return array|null
     */
    private function getJsonInput(): ?array
    {
        $json = file_get_contents('php://input');

        if (empty($json)) {
            return null;
        }

        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $data;
    }

    /**
     * Converter valor para inteiro ou retornar null
     * 
     * @param mixed $value
     * @return int|null
     */
    private function getIntOrNull($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $intValue = (int)$value;

        return $intValue > 0 ? $intValue : null;
    }

    /**
     * Sanitizar string
     * 
     * @param mixed $value
     * @return string|null
     */
    private function sanitizeString($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return trim(strip_tags((string)$value));
    }

    /**
     * Validar formato de data (Y-m-d)
     * 
     * @param mixed $date
     * @return bool
     */
    private function isValidDate($date): bool
    {
        if (!is_string($date)) {
            return false;
        }

        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Log de erro com contexto
     * 
     * @param string $message
     * @param Exception $e
     */
    private function logError(string $message, Exception $e): void
    {
        LogService::error($message, [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}
