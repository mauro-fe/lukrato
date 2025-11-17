<?php

namespace Application\Formatters;

/**
 * Formatter responsável por formatar e validar documentos (CPF, etc).
 */
class DocumentFormatter
{
    /**
     * Remove todos os caracteres não-dígitos de uma string.
     */
    public function digits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    /**
     * Valida um CPF usando o algoritmo padrão.
     */
    public function isValidCpf(string $cpf): bool
    {
        $cpf = $this->digits($cpf);

        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += (int) $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            
            if ((int) $cpf[$t] !== $d) {
                return false;
            }
        }

        return true;
    }

    /**
     * Formata CPF no padrão XXX.XXX.XXX-XX.
     */
    public function formatCpf(?string $cpf): string
    {
        $digits = $this->digits($cpf ?? '');

        if (strlen($digits) !== 11) {
            return '';
        }

        return sprintf(
            '%s.%s.%s-%s',
            substr($digits, 0, 3),
            substr($digits, 3, 3),
            substr($digits, 6, 3),
            substr($digits, 9, 2)
        );
    }
}