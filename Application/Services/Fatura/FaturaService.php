<?php

declare(strict_types=1);

namespace Application\Services\Fatura;

use Application\Container\ApplicationContainer;

/**
 * Facade para gerenciamento de faturas de cartão de crédito.
 */
class FaturaService
{
    private FaturaReadService $readService;
    private FaturaItemPaymentService $itemPaymentService;
    private FaturaCreationService $creationService;
    private FaturaItemManagementService $itemManagementService;
    private FaturaCancellationService $cancellationService;

    public function __construct(
        ?FaturaInstallmentCalculatorService $calculatorService = null,
        ?FaturaFormatterService $formatterService = null,
        ?FaturaItemPaymentStateService $itemPaymentStateService = null,
        ?FaturaCreationService $creationService = null,
        ?FaturaItemManagementService $itemManagementService = null,
        ?FaturaReadService $readService = null,
        ?FaturaCancellationService $cancellationService = null,
        ?FaturaItemPaymentService $itemPaymentService = null
    ) {
        $calculatorService = ApplicationContainer::resolveOrNew($calculatorService, FaturaInstallmentCalculatorService::class);
        $formatterService = ApplicationContainer::resolveOrNew(
            $formatterService,
            FaturaFormatterService::class,
            fn(): FaturaFormatterService => new FaturaFormatterService($calculatorService)
        );
        $itemPaymentStateService = ApplicationContainer::resolveOrNew($itemPaymentStateService, FaturaItemPaymentStateService::class);

        $this->readService = ApplicationContainer::resolveOrNew(
            $readService,
            FaturaReadService::class,
            fn(): FaturaReadService => new FaturaReadService($formatterService, $calculatorService)
        );
        $this->itemPaymentService = ApplicationContainer::resolveOrNew(
            $itemPaymentService,
            FaturaItemPaymentService::class,
            fn(): FaturaItemPaymentService => new FaturaItemPaymentService($itemPaymentStateService)
        );
        $this->creationService = ApplicationContainer::resolveOrNew(
            $creationService,
            FaturaCreationService::class,
            fn(): FaturaCreationService => new FaturaCreationService($calculatorService)
        );
        $this->itemManagementService = ApplicationContainer::resolveOrNew($itemManagementService, FaturaItemManagementService::class);
        $this->cancellationService = ApplicationContainer::resolveOrNew($cancellationService, FaturaCancellationService::class);
    }

    public function listar(
        int $usuarioId,
        ?int $cartaoId = null,
        ?string $status = null,
        ?int $mes = null,
        ?int $ano = null
    ): array {
        return $this->readService->listar($usuarioId, $cartaoId, $status, $mes, $ano);
    }

    public function obterAnosDisponiveis(int $usuarioId): array
    {
        return $this->readService->obterAnosDisponiveis($usuarioId);
    }

    public function buscar(int $faturaId, int $usuarioId): ?array
    {
        return $this->readService->buscar($faturaId, $usuarioId);
    }

    public function criar(array $dados): ?int
    {
        return $this->creationService->criar($dados);
    }

    public function cancelar(int $faturaId, int $usuarioId): bool
    {
        return $this->cancellationService->cancelar($faturaId, $usuarioId);
    }

    public function toggleItemPago(int $faturaId, int $itemId, int $usuarioId, bool $pago): bool
    {
        return $this->itemPaymentService->toggleItemPago($faturaId, $itemId, $usuarioId, $pago);
    }

    public function atualizarItem(int $faturaId, int $itemId, int $usuarioId, array $dados): bool
    {
        return $this->itemManagementService->atualizarItem($faturaId, $itemId, $usuarioId, $dados);
    }

    public function buscarItem(int $itemId, int $usuarioId): ?array
    {
        return $this->itemManagementService->buscarItem($itemId, $usuarioId);
    }

    public function excluirItem(int $faturaId, int $itemId, int $usuarioId): array
    {
        return $this->itemManagementService->excluirItem($faturaId, $itemId, $usuarioId);
    }

    public function excluirParcelamento(int $itemId, int $usuarioId): array
    {
        return $this->itemManagementService->excluirParcelamento($itemId, $usuarioId);
    }
}
