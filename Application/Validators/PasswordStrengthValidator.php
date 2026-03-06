<?php

namespace Application\Validators;

class PasswordStrengthValidator
{
    private const MIN_LENGTH = 8;
    private const MAX_LENGTH = 72;

    /**
     * Valida a força da senha conforme as regras de complexidade.
     *
     * @return array Lista de mensagens de erro (vazio = senha válida)
     */
    public static function validate(string $senha): array
    {
        $errors = [];

        if (strlen($senha) < self::MIN_LENGTH) {
            $errors[] = 'A senha deve ter no mínimo ' . self::MIN_LENGTH . ' caracteres.';
        }

        if (strlen($senha) > self::MAX_LENGTH) {
            $errors[] = 'A senha deve ter no máximo ' . self::MAX_LENGTH . ' caracteres.';
        }

        $missing = [];

        if (!preg_match('/[a-z]/', $senha)) {
            $missing[] = 'uma letra minúscula';
        }
        if (!preg_match('/[A-Z]/', $senha)) {
            $missing[] = 'uma letra maiúscula';
        }
        if (!preg_match('/[0-9]/', $senha)) {
            $missing[] = 'um número';
        }
        if (!preg_match('/[^a-zA-Z0-9]/', $senha)) {
            $missing[] = 'um caractere especial (!@#$%&*)';
        }

        if (!empty($missing)) {
            $errors[] = 'A senha deve conter: ' . implode(', ', $missing) . '.';
        }

        return $errors;
    }

    /**
     * Gera uma senha aleatória que atende todas as regras de complexidade.
     */
    public static function generateSecureRandom(int $length = 32): string
    {
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $digits    = '0123456789';
        $special   = '!@#$%&*()-_=+';
        $all       = $lowercase . $uppercase . $digits . $special;

        // Garante pelo menos um de cada tipo
        $password  = $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $digits[random_int(0, strlen($digits) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];

        // Preenche o restante com caracteres aleatórios
        for ($i = 4; $i < $length; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }

        // Embaralha para não ter padrão previsível na posição
        return str_shuffle($password);
    }
}
