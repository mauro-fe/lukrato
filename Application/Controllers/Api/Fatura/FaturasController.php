<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Fatura;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\Fatura\FaturaService;
use Application\Services\Infrastructure\LogService;
use Exception;
use InvalidArgumentException;

/**
 * Controller para gerenciar faturas de cartão de crédito via API
 */
class FaturasController extends BaseController
{
    private FaturaService $faturaService;

    public function __construct()
    {
        parent::__construct();
        $this->faturaService = new FaturaService();
    }

    /**
     * Listar faturas do usuário
     */
    public function index(): void
    {
        try {
            $usuarioId = $this->getAuthenticatedUserId();
            $this->releaseSession();

            $cartaoId = $this->getIntOrNull($this->getQuery('cartao_id'));
            $status = $this->sanitizeString($this->getQuery('status'));
            $mes = $this->getIntOrNull($this->getQuery('mes'));
            $ano = $this->getIntOrNull($this->getQuery('ano'));

            if ($mes !== null && ($mes < 1 || $mes > 12)) {
                Response::error('Mês inválido. Deve estar entre 1 e 12', 400);
                return;
            }

            if ($ano !== null && ($ano < 2000 || $ano > 2100)) {
                Response::error('Ano inválido', 400);
                return;
            }

            if ($status !== null && !in_array($status, ['pendente', 'parcial', 'paga', 'cancelado'], true)) {
                Response::error('Status inválido', 400);
                return;
            }

            $faturas = $this->faturaService->listar(
                $usuarioId,
                $cartaoId,
                $status,
                $mes,
                $ano
            );

            $anosDisponiveis = $this->faturaService->obterAnosDisponiveis($usuarioId);

            Response::success([
                'faturas' => $faturas,
                'total' => count($faturas),
                'anos_disponiveis' => $anosDisponiveis,
            ]);
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (Exception $e) {
            $this->logError('Erro ao listar faturas', $e);
            Response::error('Erro ao listar faturas', 500);
        }
    }

    /**
     * Buscar fatura por ID
     */
    public function show(int $id): void
    {
        try {
            $usuarioId = $this->getAuthenticatedUserId();
            $this->releaseSession();

            if (!$this->ensurePositiveId($id)) {
                return;
            }

            $fatura = $this->findOwnedFaturaOrRespond($id, $usuarioId);
            if ($fatura === null) {
                return;
            }

            Response::success($fatura);
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (Exception $e) {
            $this->logError("Erro ao buscar fatura {$id}", $e);
            Response::error('Erro ao buscar fatura', 500);
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
                Response::error('Dados inválidos ou ausentes', 400);
                return;
            }

            $requiredFields = ['cartao_id', 'descricao', 'valor_total', 'data_vencimento'];
            $missingFields = array_diff($requiredFields, array_keys($data));

            if (!empty($missingFields)) {
                Response::error('Campos obrigatórios ausentes', 400, ['missing_fields' => array_values($missingFields)]);
                return;
            }

            if (!is_numeric($data['cartao_id']) || $data['cartao_id'] <= 0) {
                Response::error('ID do cartão inválido', 400);
                return;
            }

            if (!is_numeric($data['valor_total']) || $data['valor_total'] <= 0) {
                Response::error('Valor total inválido', 400);
                return;
            }

            if (!$this->isValidDate($data['data_vencimento'])) {
                Response::error('Data de vencimento inválida', 400);
                return;
            }

            $data['user_id'] = $usuarioId;
            $data['descricao'] = $this->sanitizeString($data['descricao']);

            $faturaId = $this->faturaService->criar($data);

            if (!$faturaId) {
                Response::error('Erro ao criar fatura', 500);
                return;
            }

            $fatura = $this->faturaService->buscar($faturaId, $usuarioId);

            Response::success($fatura, 'Fatura criada com sucesso', 201);
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (Exception $e) {
            $this->logError('Erro ao criar fatura', $e);
            Response::error('Erro ao criar fatura. Tente novamente.', 500);
        }
    }

    /**
     * Cancelar fatura
     */
    public function destroy(int $id): void
    {
        try {
            $usuarioId = $this->getAuthenticatedUserId();

            if (!$this->ensurePositiveId($id)) {
                return;
            }

            $fatura = $this->findOwnedFaturaOrRespond($id, $usuarioId);
            if ($fatura === null || !$this->ensureNotCancelled($fatura)) {
                return;
            }

            $success = $this->faturaService->cancelar($id, $usuarioId);

            if (!$success) {
                Response::error('Erro ao cancelar fatura', 500);
                return;
            }

            Response::success(null, 'Fatura cancelada com sucesso');
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (Exception $e) {
            $this->logError("Erro ao cancelar fatura {$id}", $e);
            Response::error('Erro ao cancelar fatura', 500);
        }
    }

    /**
     * Atualizar item individual da fatura (descrição e valor)
     */
    public function updateItem(int $faturaId, int $itemId): void
    {
        try {
            $usuarioId = $this->getAuthenticatedUserId();

            if (!$this->ensurePositiveIds($faturaId, $itemId)) {
                return;
            }

            $data = $this->getJsonInput();

            if (empty($data['descricao']) && !isset($data['valor'])) {
                Response::error('Informe a descrição ou valor para atualizar', 400);
                return;
            }

            if (isset($data['valor']) && (!is_numeric($data['valor']) || $data['valor'] <= 0)) {
                Response::error('Valor deve ser maior que zero', 400);
                return;
            }

            $fatura = $this->findOwnedFaturaOrRespond($faturaId, $usuarioId);
            if ($fatura === null || !$this->ensureEditableFatura($fatura)) {
                return;
            }

            $success = $this->faturaService->atualizarItem($faturaId, $itemId, $usuarioId, $data);

            if (!$success) {
                Response::error('Item não encontrado ou não pertence a esta fatura', 404);
                return;
            }

            Response::success(null, 'Item atualizado com sucesso');
        } catch (InvalidArgumentException $e) {
            LogService::error('Erro de validação ao atualizar item da fatura', [
                'item_id' => $itemId,
                'fatura_id' => $faturaId,
                'error' => $e->getMessage(),
            ]);
            Response::error($e->getMessage(), 400);
        } catch (Exception $e) {
            $this->logError("Erro ao atualizar item {$itemId} da fatura {$faturaId}", $e);
            LogService::error('Erro geral ao atualizar item da fatura', [
                'item_id' => $itemId,
                'fatura_id' => $faturaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            Response::error('Erro ao atualizar item: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Marcar item da fatura como pago/pendente
     */
    public function toggleItemPago(int $faturaId, int $itemId): void
    {
        try {
            $usuarioId = $this->getAuthenticatedUserId();

            if (!$this->ensurePositiveIds($faturaId, $itemId)) {
                return;
            }

            $data = $this->getJsonInput();

            if (!isset($data['pago'])) {
                Response::error('Campo "pago" é obrigatório', 400);
                return;
            }

            $pago = (bool) $data['pago'];

            $fatura = $this->findOwnedFaturaOrRespond($faturaId, $usuarioId);
            if ($fatura === null || !$this->ensureEditableFatura($fatura)) {
                return;
            }

            $success = $this->faturaService->toggleItemPago($faturaId, $itemId, $usuarioId, $pago);

            if (!$success) {
                Response::error('Item não encontrado ou não pertence a esta fatura', 404);
                return;
            }

            Response::success(null, $pago ? 'Item marcado como pago' : 'Pagamento desfeito');
        } catch (InvalidArgumentException $e) {
            LogService::error('Erro de validação ao atualizar item da fatura', [
                'item_id' => $itemId,
                'fatura_id' => $faturaId,
                'error' => $e->getMessage(),
            ]);
            Response::error($e->getMessage(), 400);
        } catch (Exception $e) {
            $this->logError("Erro ao atualizar item {$itemId} da fatura {$faturaId}", $e);
            LogService::error('Erro geral ao atualizar item da fatura', [
                'item_id' => $itemId,
                'fatura_id' => $faturaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            Response::error('Erro ao atualizar item: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Excluir item individual da fatura
     */
    public function destroyItem(int $faturaId, int $itemId): void
    {
        try {
            $usuarioId = $this->getAuthenticatedUserId();

            if (!$this->ensurePositiveIds($faturaId, $itemId)) {
                return;
            }

            $fatura = $this->findOwnedFaturaOrRespond($faturaId, $usuarioId);
            if ($fatura === null) {
                return;
            }

            $resultado = $this->faturaService->excluirItem($faturaId, $itemId, $usuarioId);

            if (!$resultado['success']) {
                Response::error($resultado['message'], 400);
                return;
            }

            Response::success(null, $resultado['message']);
        } catch (InvalidArgumentException $e) {
            LogService::error('Erro de validação ao excluir item da fatura', [
                'item_id' => $itemId,
                'fatura_id' => $faturaId,
                'error' => $e->getMessage(),
            ]);
            Response::error($e->getMessage(), 400);
        } catch (Exception $e) {
            $this->logError("Erro ao excluir item {$itemId} da fatura {$faturaId}", $e);
            Response::error('Erro ao excluir item: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Excluir parcelamento completo (todas as parcelas)
     */
    public function deleteParcelamento(int $faturaId, int $itemId): void
    {
        try {
            $usuarioId = $this->getAuthenticatedUserId();

            LogService::info('deleteParcelamento chamado', [
                'fatura_id' => $faturaId,
                'item_id' => $itemId,
                'usuario_id' => $usuarioId,
            ]);

            if (!$this->ensurePositiveIds($faturaId, $itemId)) {
                LogService::warning('deleteParcelamento - IDs inválidos', [
                    'fatura_id' => $faturaId,
                    'item_id' => $itemId,
                ]);
                return;
            }

            $fatura = $this->findOwnedFaturaOrRespond($faturaId, $usuarioId);
            if ($fatura === null) {
                LogService::warning('deleteParcelamento - Fatura não encontrada', [
                    'fatura_id' => $faturaId,
                    'usuario_id' => $usuarioId,
                ]);
                return;
            }

            $resultado = $this->faturaService->excluirParcelamento($itemId, $usuarioId);

            if (!$resultado['success']) {
                LogService::warning('deleteParcelamento - Falha no serviço', [
                    'item_id' => $itemId,
                    'resultado' => $resultado,
                ]);
                Response::error($resultado['message'], 400);
                return;
            }

            Response::success(['itens_excluidos' => $resultado['itens_excluidos'] ?? 0], $resultado['message']);
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (Exception $e) {
            $this->logError("Erro ao excluir parcelamento do item {$itemId}", $e);
            Response::error('Erro ao excluir parcelamento', 500);
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

        return (int) $usuarioId;
    }

    /**
     * Obter dados JSON do corpo da requisição
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
     */
    private function getIntOrNull($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $intValue = (int) $value;

        return $intValue > 0 ? $intValue : null;
    }

    private function ensurePositiveId(int $id, string $message = 'ID inválido'): bool
    {
        if ($id <= 0) {
            Response::error($message, 400);
            return false;
        }

        return true;
    }

    private function ensurePositiveIds(int ...$ids): bool
    {
        foreach ($ids as $id) {
            if ($id <= 0) {
                Response::error('IDs inválidos', 400);
                return false;
            }
        }

        return true;
    }

    private function findOwnedFaturaOrRespond(int $faturaId, int $usuarioId): ?array
    {
        $fatura = $this->faturaService->buscar($faturaId, $usuarioId);

        if ($fatura === null) {
            Response::error('Fatura não encontrada', 404);
            return null;
        }

        return $fatura;
    }

    private function ensureNotCancelled(array $fatura): bool
    {
        if (($fatura['status'] ?? null) === 'cancelado') {
            Response::error('Fatura já está cancelada', 400);
            return false;
        }

        return true;
    }

    private function ensureEditableFatura(array $fatura): bool
    {
        if (($fatura['status'] ?? null) === 'cancelado') {
            Response::error('Não é possível modificar uma fatura cancelada', 400);
            return false;
        }

        return true;
    }

    /**
     * Sanitizar string
     */
    private function sanitizeString($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return trim(strip_tags((string) $value));
    }

    /**
     * Validar formato de data (Y-m-d)
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
     */
    private function logError(string $message, Exception $e): void
    {
        LogService::error($message, [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
