<?php

namespace Application\Validators;

class CartaoCreditoValidator
{
    private array $errors = [];

    public function validateCreate(array $data): bool
    {
        $this->errors = [];

        // Conta ID obrigatório
        if (empty($data['conta_id']) || (int) $data['conta_id'] <= 0) {
            $this->errors['conta_id'] = 'Conta é obrigatória.';
        }

        // Nome do cartão obrigatório
        if (empty(trim((string) ($data['nome_cartao'] ?? '')))) {
            $this->errors['nome_cartao'] = 'Nome do cartão é obrigatório.';
        } elseif (strlen(trim($data['nome_cartao'])) > 100) {
            $this->errors['nome_cartao'] = 'Nome do cartão não pode exceder 100 caracteres.';
        }

        // Bandeira obrigatória
        $bandeirasPermitidas = ['visa', 'mastercard', 'elo', 'amex', 'hipercard', 'diners'];
        $bandeira = strtolower(trim((string) ($data['bandeira'] ?? '')));
        if ($bandeira === '') {
            $this->errors['bandeira'] = 'Bandeira é obrigatória.';
        } elseif (!in_array($bandeira, $bandeirasPermitidas, true)) {
            $this->errors['bandeira'] = 'Bandeira inválida. Use: visa, mastercard, elo, amex, hipercard ou diners.';
        }

        // Últimos dígitos obrigatórios e devem ter exatamente 4 caracteres
        $ultimosDigitos = trim((string) ($data['ultimos_digitos'] ?? ''));
        if ($ultimosDigitos === '') {
            $this->errors['ultimos_digitos'] = 'Últimos 4 dígitos são obrigatórios.';
        } elseif (!preg_match('/^\d{4}$/', $ultimosDigitos)) {
            $this->errors['ultimos_digitos'] = 'Últimos dígitos devem conter exatamente 4 números.';
        }

        // Limite total
        if (isset($data['limite_total'])) {
            $limite = (float) $data['limite_total'];
            if ($limite < 0) {
                $this->errors['limite_total'] = 'Limite não pode ser negativo.';
            } elseif ($limite > 999999999) {
                $this->errors['limite_total'] = 'Limite excede o valor máximo permitido.';
            }
        }

        // Dia de vencimento (1-31)
        if (isset($data['dia_vencimento']) && $data['dia_vencimento'] !== '') {
            $dia = (int) $data['dia_vencimento'];
            if ($dia < 1 || $dia > 31) {
                $this->errors['dia_vencimento'] = 'Dia de vencimento deve estar entre 1 e 31.';
            }
        }

        // Dia de fechamento (1-31)
        if (isset($data['dia_fechamento']) && $data['dia_fechamento'] !== '') {
            $dia = (int) $data['dia_fechamento'];
            if ($dia < 1 || $dia > 31) {
                $this->errors['dia_fechamento'] = 'Dia de fechamento deve estar entre 1 e 31.';
            }
        }

        // Validar cor do cartão (hex color)
        if (isset($data['cor_cartao']) && $data['cor_cartao'] !== '') {
            $cor = trim((string) $data['cor_cartao']);
            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $cor)) {
                $this->errors['cor_cartao'] = 'Cor deve estar no formato hexadecimal (#RRGGBB).';
            }
        }

        return empty($this->errors);
    }

    public function validateUpdate(array $data): bool
    {
        $this->errors = [];

        // Nome do cartão (se fornecido)
        if (isset($data['nome_cartao'])) {
            $nome = trim((string) $data['nome_cartao']);
            if ($nome === '') {
                $this->errors['nome_cartao'] = 'Nome do cartão não pode ser vazio.';
            } elseif (strlen($nome) > 100) {
                $this->errors['nome_cartao'] = 'Nome do cartão não pode exceder 100 caracteres.';
            }
        }

        // Bandeira (se fornecida)
        if (isset($data['bandeira'])) {
            $bandeirasPermitidas = ['visa', 'mastercard', 'elo', 'amex', 'hipercard', 'diners'];
            $bandeira = strtolower(trim((string) $data['bandeira']));
            if (!in_array($bandeira, $bandeirasPermitidas, true)) {
                $this->errors['bandeira'] = 'Bandeira inválida.';
            }
        }

        // Últimos dígitos (se fornecidos)
        if (isset($data['ultimos_digitos'])) {
            $ultimosDigitos = trim((string) $data['ultimos_digitos']);
            if (!preg_match('/^\d{4}$/', $ultimosDigitos)) {
                $this->errors['ultimos_digitos'] = 'Últimos dígitos devem conter exatamente 4 números.';
            }
        }

        // Limite total (se fornecido)
        if (isset($data['limite_total'])) {
            $limite = (float) $data['limite_total'];
            if ($limite < 0) {
                $this->errors['limite_total'] = 'Limite não pode ser negativo.';
            } elseif ($limite > 999999999) {
                $this->errors['limite_total'] = 'Limite excede o valor máximo permitido.';
            }
        }

        // Dia de vencimento (se fornecido)
        if (isset($data['dia_vencimento']) && $data['dia_vencimento'] !== '') {
            $dia = (int) $data['dia_vencimento'];
            if ($dia < 1 || $dia > 31) {
                $this->errors['dia_vencimento'] = 'Dia de vencimento deve estar entre 1 e 31.';
            }
        }

        // Dia de fechamento (se fornecido)
        if (isset($data['dia_fechamento']) && $data['dia_fechamento'] !== '') {
            $dia = (int) $data['dia_fechamento'];
            if ($dia < 1 || $dia > 31) {
                $this->errors['dia_fechamento'] = 'Dia de fechamento deve estar entre 1 e 31.';
            }
        }

        // Cor do cartão (se fornecida)
        if (isset($data['cor_cartao']) && $data['cor_cartao'] !== '') {
            $cor = trim((string) $data['cor_cartao']);
            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $cor)) {
                $this->errors['cor_cartao'] = 'Cor deve estar no formato hexadecimal (#RRGGBB).';
            }
        }

        return empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(): ?string
    {
        return !empty($this->errors) ? reset($this->errors) : null;
    }
}
