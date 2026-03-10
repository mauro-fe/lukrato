<?php

declare(strict_types=1);

namespace Application\Services\AI\Actions;

use Application\Services\LancamentoCreationService;

class CreateLancamentoAction implements ActionInterface
{
    public function execute(int $userId, array $payload): ActionResult
    {
        $service = new LancamentoCreationService();
        $result = $service->createFromPayload($userId, $payload);

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

        return ActionResult::ok(
            "Lançamento criado: **{$desc}** — **{$valor}**",
            $result->data ?? []
        );
    }
}
