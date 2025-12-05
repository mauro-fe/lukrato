<?php

namespace Application\Contracts\Auth;

interface PasswordResetNotificationInterface
{
    public function send(string $email, string $name, string $resetLink): void;
}