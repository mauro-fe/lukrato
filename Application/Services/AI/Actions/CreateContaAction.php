<?php

declare(strict_types=1);

namespace Application\Services\AI\Actions;

use Application\DTO\CreateContaDTO;
use Application\Services\Conta\ContaService;

class CreateContaAction implements ActionInterface
{
    public function execute(int $userId, array $payload): ActionResult
    {
        $dto = CreateContaDTO::fromArray($payload, $userId);
        $service = new ContaService();
        $result = $service->criarConta($dto);

        if (!($result['success'] ?? false)) {
            $errors = $result['errors'] ?? [];
            $message = $result['message'] ?? 'Erro ao criar conta';

            if (!empty($errors)) {
                $details = implode('; ', array_values($errors));
                $message = "Erro de validação: {$details}";
            }

            return ActionResult::fail($message, $errors);
        }

        $nome = $payload['nome'] ?? 'Conta';
        $instituicao = $payload['instituicao'] ?? '';
        $saldo = 'R$ ' . number_format((float) ($payload['saldo_inicial'] ?? 0), 2, ',', '.');

        $msg = "🏦 Conta criada: **{$nome}**";
        if ($instituicao) {
            $msg .= " ({$instituicao})";
        }
        $msg .= "\n💰 Saldo inicial: {$saldo}";

        return ActionResult::ok($msg, $result['data'] ?? []);
    }
}
