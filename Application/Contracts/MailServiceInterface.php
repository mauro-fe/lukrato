<?php

declare(strict_types=1);

namespace Application\Contracts;

use Application\Models\Agendamento;
use Application\Models\Usuario;

/**
 * Interface para serviços de envio de email.
 */
interface MailServiceInterface
{
    /**
     * Verifica se o serviço está configurado.
     */
    public function isConfigured(): bool;

    /**
     * Envia um email genérico.
     */
    public function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        ?string $textBody = null,
        array $replyTo = [],
        array $attachments = []
    ): bool;

    /**
     * Envia email de recuperação de senha.
     */
    public function sendPasswordReset(string $toEmail, string $toName, string $resetLink): bool;

    /**
     * Envia lembrete de agendamento.
     */
    public function sendAgendamentoReminder(Agendamento $agendamento, Usuario $usuario): bool;

    /**
     * Envia mensagem de suporte.
     */
    public function sendSupportMessage(
        string $fromEmail,
        string $name,
        string $message,
        ?string $phone = null,
        ?string $preferredContact = null
    ): bool;
}
