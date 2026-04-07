<?php

declare(strict_types=1);

namespace Application\Services\Fatura;

use Application\Container\ApplicationContainer;

class FaturaApiWorkflowService
{
    private readonly FaturaService $faturaService;

    public function __construct(
        ?FaturaService $faturaService = null
    ) {
        $this->faturaService = ApplicationContainer::resolveOrNew($faturaService, FaturaService::class);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function listInvoices(int $userId, array $filters): array
    {
        $cardId = $this->getIntOrNull($filters['cartao_id'] ?? null);
        $status = $this->sanitizeString($filters['status'] ?? null);
        $month = $this->getIntOrNull($filters['mes'] ?? null);
        $year = $this->getIntOrNull($filters['ano'] ?? null);

        if ($month !== null && ($month < 1 || $month > 12)) {
            return $this->failure('Mês inválido. Deve estar entre 1 e 12');
        }

        if ($year !== null && ($year < 2000 || $year > 2100)) {
            return $this->failure('Ano inválido');
        }

        if ($status !== null && !in_array($status, ['pendente', 'parcial', 'paga', 'cancelado'], true)) {
            return $this->failure('Status inválido');
        }

        $invoices = $this->faturaService->listar($userId, $cardId, $status, $month, $year);

        return [
            'success' => true,
            'data' => [
                'faturas' => $invoices,
                'total' => count($invoices),
                'anos_disponiveis' => $this->faturaService->obterAnosDisponiveis($userId),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function showInvoice(int $invoiceId, int $userId): array
    {
        if ($invoiceId <= 0) {
            return $this->failure('ID inválido');
        }

        $invoice = $this->findOwnedInvoice($invoiceId, $userId);
        if ($invoice === null) {
            return $this->failure('Fatura não encontrada', 404);
        }

        return [
            'success' => true,
            'data' => $invoice,
        ];
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array<string, mixed>
     */
    public function createInvoice(int $userId, ?array $payload): array
    {
        if (!$payload) {
            return $this->failure('Dados inválidos ou ausentes');
        }

        $requiredFields = ['cartao_id', 'descricao', 'valor_total', 'data_vencimento'];
        $missingFields = array_diff($requiredFields, array_keys($payload));
        if (!empty($missingFields)) {
            return [
                'success' => false,
                'status' => 400,
                'message' => 'Campos obrigatórios ausentes',
                'errors' => [
                    'missing_fields' => array_values($missingFields),
                ],
            ];
        }

        if (!is_numeric($payload['cartao_id']) || (float) $payload['cartao_id'] <= 0) {
            return $this->failure('ID do cartão inválido');
        }

        if (!is_numeric($payload['valor_total']) || (float) $payload['valor_total'] <= 0) {
            return $this->failure('Valor total inválido');
        }

        if (!$this->isValidDate($payload['data_vencimento'])) {
            return $this->failure('Data de vencimento inválida');
        }

        $payload['user_id'] = $userId;
        $payload['descricao'] = $this->sanitizeString($payload['descricao']);

        $invoiceId = $this->faturaService->criar($payload);
        if (!$invoiceId) {
            return $this->failure('Erro ao criar fatura', 500);
        }

        return [
            'success' => true,
            'status' => 201,
            'message' => 'Fatura criada com sucesso',
            'data' => $this->faturaService->buscar($invoiceId, $userId),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteInvoice(int $invoiceId, int $userId): array
    {
        if ($invoiceId <= 0) {
            return $this->failure('ID inválido');
        }

        $invoice = $this->findOwnedInvoice($invoiceId, $userId);
        if ($invoice === null) {
            return $this->failure('Fatura não encontrada', 404);
        }

        if (($invoice['status'] ?? null) === 'cancelado') {
            return $this->failure('Fatura já está cancelada');
        }

        if (!$this->faturaService->cancelar($invoiceId, $userId)) {
            return $this->failure('Erro ao cancelar fatura', 500);
        }

        return [
            'success' => true,
            'message' => 'Fatura cancelada com sucesso',
            'data' => null,
        ];
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array<string, mixed>
     */
    public function updateInvoiceItem(int $invoiceId, int $itemId, int $userId, ?array $payload): array
    {
        if ($invoiceId <= 0 || $itemId <= 0) {
            return $this->failure('IDs inválidos');
        }

        $payload ??= [];
        if (empty($payload['descricao']) && !isset($payload['valor'])) {
            return $this->failure('Informe a descrição ou valor para atualizar');
        }

        if (isset($payload['valor']) && (!is_numeric($payload['valor']) || (float) $payload['valor'] <= 0)) {
            return $this->failure('Valor deve ser maior que zero');
        }

        $invoice = $this->findOwnedInvoice($invoiceId, $userId);
        if ($invoice === null) {
            return $this->failure('Fatura não encontrada', 404);
        }

        if (($invoice['status'] ?? null) === 'cancelado') {
            return $this->failure('Não é possível modificar uma fatura cancelada');
        }

        if (!$this->faturaService->atualizarItem($invoiceId, $itemId, $userId, $payload)) {
            return $this->failure('Item não encontrado ou não pertence a esta fatura', 404);
        }

        return [
            'success' => true,
            'message' => 'Item atualizado com sucesso',
            'data' => null,
        ];
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array<string, mixed>
     */
    public function toggleInvoiceItemPaid(int $invoiceId, int $itemId, int $userId, ?array $payload): array
    {
        if ($invoiceId <= 0 || $itemId <= 0) {
            return $this->failure('IDs inválidos');
        }

        $payload ??= [];
        if (!isset($payload['pago'])) {
            return $this->failure('Campo "pago" é obrigatório');
        }

        $invoice = $this->findOwnedInvoice($invoiceId, $userId);
        if ($invoice === null) {
            return $this->failure('Fatura não encontrada', 404);
        }

        if (($invoice['status'] ?? null) === 'cancelado') {
            return $this->failure('Não é possível modificar uma fatura cancelada');
        }

        $paid = (bool) $payload['pago'];
        if (!$this->faturaService->toggleItemPago($invoiceId, $itemId, $userId, $paid)) {
            return $this->failure('Item não encontrado ou não pertence a esta fatura', 404);
        }

        return [
            'success' => true,
            'message' => $paid ? 'Item marcado como pago' : 'Pagamento desfeito',
            'data' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteInvoiceItem(int $invoiceId, int $itemId, int $userId): array
    {
        if ($invoiceId <= 0 || $itemId <= 0) {
            return $this->failure('IDs inválidos');
        }

        $invoice = $this->findOwnedInvoice($invoiceId, $userId);
        if ($invoice === null) {
            return $this->failure('Fatura não encontrada', 404);
        }

        $result = $this->faturaService->excluirItem($invoiceId, $itemId, $userId);
        if (!$result['success']) {
            return $this->failure($result['message']);
        }

        return [
            'success' => true,
            'message' => $result['message'],
            'data' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteInstallment(int $invoiceId, int $itemId, int $userId): array
    {
        if ($invoiceId <= 0 || $itemId <= 0) {
            return $this->failure('IDs inválidos');
        }

        $invoice = $this->findOwnedInvoice($invoiceId, $userId);
        if ($invoice === null) {
            return $this->failure('Fatura não encontrada', 404);
        }

        $result = $this->faturaService->excluirParcelamento($itemId, $userId);
        if (!$result['success']) {
            return $this->failure($result['message']);
        }

        return [
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'itens_excluidos' => $result['itens_excluidos'] ?? 0,
            ],
        ];
    }

    private function findOwnedInvoice(int $invoiceId, int $userId): ?array
    {
        return $this->faturaService->buscar($invoiceId, $userId);
    }

    private function getIntOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        $intValue = (int) $value;

        return $intValue > 0 ? $intValue : null;
    }

    private function sanitizeString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return trim(strip_tags((string) $value));
    }

    private function isValidDate(mixed $date): bool
    {
        if (!is_string($date)) {
            return false;
        }

        $parsed = \DateTime::createFromFormat('Y-m-d', $date);

        return $parsed && $parsed->format('Y-m-d') === $date;
    }

    /**
     * @param array<string, mixed> $errors
     * @return array<string, mixed>
     */
    private function failure(string $message, int $status = 400, array $errors = []): array
    {
        return [
            'success' => false,
            'status' => $status,
            'message' => $message,
            'errors' => $errors,
        ];
    }
}
