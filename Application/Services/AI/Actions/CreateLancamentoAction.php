<?php

declare(strict_types=1);

namespace Application\Services\AI\Actions;

use Application\Container\ApplicationContainer;
use Application\Services\Lancamento\LancamentoCreationService;

class CreateLancamentoAction implements ActionInterface
{
    private LancamentoCreationService $service;

    public function __construct(?LancamentoCreationService $service = null)
    {
        $this->service = ApplicationContainer::resolveOrNew($service, LancamentoCreationService::class);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int $userId, array $payload): ActionResult
    {
        $result = $this->service->createFromPayload($userId, $payload);

        if (!$result->success) {
            $errors = $result->data['errors'] ?? [];
            $message = $result->message;

            // Incluir detalhes dos erros de validação na mensagem
            if (!empty($errors)) {
                $details = implode('; ', array_map(
                    fn($field, $msg) => is_string($field) ? "{$msg}" : $msg,
                    array_keys($errors),
                    array_values($errors)
                ));
                $message = "Erro de validação: {$details}";
            }

            return ActionResult::fail($message, $errors);
        }

        $desc  = $payload['descricao'] ?? 'Sem descrição';
        $valor = 'R$ ' . number_format((float) ($payload['valor'] ?? 0), 2, ',', '.');
        $isCartao = ($payload['forma_pagamento'] ?? null) === 'cartao_credito';

        if ($isCartao) {
            $cartaoNome = $payload['_cartao_nome'] ?? 'cartão de crédito';
            $parcelaInfo = '';
            if (!empty($payload['eh_parcelado']) && ($parcelas = (int) ($payload['total_parcelas'] ?? 0)) > 0) {
                $valorParcela = (float) ($payload['valor'] ?? 0) / $parcelas;
                $parcelaFmt = 'R$ ' . number_format($valorParcela, 2, ',', '.');
                $parcelaInfo = " ({$parcelas}x de {$parcelaFmt})";
            }
            $msg = "💳 Compra registrada no {$cartaoNome}: **{$desc}** — **{$valor}**{$parcelaInfo}";
        } else {
            $msg = "✅ Lançamento criado: **{$desc}** — **{$valor}**";
        }

        return ActionResult::ok(
            $msg,
            $result->data
        );
    }
}
