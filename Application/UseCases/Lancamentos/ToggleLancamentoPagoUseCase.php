<?php

declare(strict_types=1);

namespace Application\UseCases\Lancamentos;

use Application\Container\ApplicationContainer;
use Application\DTO\ServiceResultDTO;
use Application\Repositories\LancamentoRepository;
use Application\Repositories\ParcelamentoRepository;
use Application\Services\Lancamento\LancamentoStatusService;
use DomainException;

class ToggleLancamentoPagoUseCase
{
    private readonly LancamentoRepository $lancamentoRepo;
    private readonly LancamentoStatusService $statusService;
    private readonly ParcelamentoRepository $parcelamentoRepo;

    public function __construct(
        ?LancamentoRepository $lancamentoRepo = null,
        ?LancamentoStatusService $statusService = null,
        ?ParcelamentoRepository $parcelamentoRepo = null
    ) {
        $this->lancamentoRepo = ApplicationContainer::resolveOrNew($lancamentoRepo, LancamentoRepository::class);
        $this->statusService = ApplicationContainer::resolveOrNew($statusService, LancamentoStatusService::class);
        $this->parcelamentoRepo = ApplicationContainer::resolveOrNew($parcelamentoRepo, ParcelamentoRepository::class);
    }

    public function execute(int $userId, int $lancamentoId, bool $markAsPaid): ServiceResultDTO
    {
        $lancamento = $this->lancamentoRepo->findByIdAndUser($lancamentoId, $userId);
        if (!$lancamento) {
            return ServiceResultDTO::fail('Lancamento nao encontrado', 404);
        }

        try {
            $lancamento = $markAsPaid
                ? $this->statusService->marcarPago($lancamento)
                : $this->statusService->desmarcarPago($lancamento);
        } catch (DomainException $e) {
            return ServiceResultDTO::fail($this->resolveDomainMessage($e, $markAsPaid), 422);
        }

        $parcelamentoId = (int) ($lancamento->parcelamento_id ?? 0);
        if ($parcelamentoId > 0) {
            $this->parcelamentoRepo->atualizarParcelasPagas($parcelamentoId);
        }

        return ServiceResultDTO::ok(
            $markAsPaid
                ? 'Lancamento marcado como pago.'
                : 'Lancamento marcado como pendente.',
            ['lancamento' => $lancamento]
        );
    }

    private function resolveDomainMessage(DomainException $e, bool $markAsPaid): string
    {
        $message = trim($e->getMessage());
        if ($message !== '') {
            return $message;
        }

        return $markAsPaid
            ? 'Nao foi possivel marcar o lancamento como pago.'
            : 'Nao foi possivel marcar o lancamento como pendente.';
    }
}
