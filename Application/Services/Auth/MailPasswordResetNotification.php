<?php

namespace Application\Services\Auth;

use Application\Contracts\Auth\PasswordResetNotificationInterface;
use Application\Services\Communication\MailService;

class MailPasswordResetNotification implements PasswordResetNotificationInterface
{
    private MailService $mail;

    public function __construct(?MailService $mailService = null)
    {
        // Se não passar config, ele já pega do .env pelas defaults
        $this->mail = $mailService ?? new MailService();
    }

    public function send(string $email, string $name, string $resetLink): void
    {
        $this->mail->sendPasswordReset($email, $name, $resetLink);
    }
}