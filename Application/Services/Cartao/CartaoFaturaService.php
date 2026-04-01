<?php

declare(strict_types=1);

namespace Application\Services\Cartao;

/**
 * Service para gerenciar faturas de cartão de crédito.
 *
 * Mantém a interface pública original e delega responsabilidades para
 * serviços especializados de leitura e pagamento.
 */
class CartaoFaturaService
{
    public function __construct(
        private ?CartaoFaturaReadService $readService = null,
        private ?CartaoFaturaPaymentService $paymentService = null
    ) {
        $this->readService ??= new CartaoFaturaReadService();
        $this->paymentService ??= new CartaoFaturaPaymentService($this->readService);
    }

    public function obterHistoricoFaturasPagas(int $cartaoId, int $userId, int $limite = 12): array
    {
        return $this->readService->obterHistoricoFaturasPagas($cartaoId, $userId, $limite);
    }

    public function obterFaturaMes(int $cartaoId, int $mes, int $ano, int $userId): array
    {
        return $this->readService->obterFaturaMes($cartaoId, $mes, $ano, $userId);
    }

    public function pagarFatura(
        int $cartaoId,
        int $mes,
        int $ano,
        int $userId,
        ?int $contaIdOverride = null,
        ?float $valorParcial = null
    ): array {
        return $this->paymentService->pagarFatura(
            $cartaoId,
            $mes,
            $ano,
            $userId,
            $contaIdOverride,
            $valorParcial
        );
    }

    public function pagarParcelas(int $cartaoId, array $parcelaIds, int $mes, int $ano, int $userId): array
    {
        return $this->paymentService->pagarParcelas($cartaoId, $parcelaIds, $mes, $ano, $userId);
    }

    public function desfazerPagamentoParcela(int $parcelaId, int $userId): array
    {
        return $this->paymentService->desfazerPagamentoParcela($parcelaId, $userId);
    }

    public function obterMesesComFaturasPendentes(int $cartaoId, int $userId): array
    {
        return $this->readService->obterMesesComFaturasPendentes($cartaoId, $userId);
    }

    public function faturaEstaPaga(int $cartaoId, int $mes, int $ano, int $userId): ?array
    {
        return $this->readService->faturaEstaPaga($cartaoId, $mes, $ano, $userId);
    }

    public function desfazerPagamentoFatura(int $cartaoId, int $mes, int $ano, int $userId): array
    {
        return $this->paymentService->desfazerPagamentoFatura($cartaoId, $mes, $ano, $userId);
    }

    public function verificarVencimentosProximos(int $userId, int $diasAlerta = 7): array
    {
        return $this->readService->verificarVencimentosProximos($userId, $diasAlerta);
    }

    public function obterResumoParcelamentos(int $cartaoId, int $mes, int $ano, ?int $userId = null): array
    {
        return $this->readService->obterResumoParcelamentos($cartaoId, $mes, $ano, $userId);
    }
}
