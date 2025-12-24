<?php

namespace Application\Validators;

class ContaValidator
{
    private array $errors = [];

    public function validateCreate(array $data): bool
    {
        $this->errors = [];

        // Nome obrigatório
        if (empty(trim((string) ($data['nome'] ?? '')))) {
            $this->errors['nome'] = 'Nome da conta é obrigatório.';
        } elseif (strlen(trim($data['nome'])) > 100) {
            $this->errors['nome'] = 'Nome da conta não pode exceder 100 caracteres.';
        }

        // Tipo de conta
        $tiposPermitidos = ['conta_corrente', 'conta_poupanca', 'conta_investimento', 'carteira_digital', 'dinheiro'];
        $tipoConta = trim((string) ($data['tipo_conta'] ?? 'conta_corrente'));
        if (!in_array($tipoConta, $tiposPermitidos, true)) {
            $this->errors['tipo_conta'] = 'Tipo de conta inválido.';
        }

        // Moeda
        $moedasPermitidas = ['BRL', 'USD', 'EUR'];
        $moeda = strtoupper(trim((string) ($data['moeda'] ?? 'BRL')));
        if (!in_array($moeda, $moedasPermitidas, true)) {
            $this->errors['moeda'] = 'Moeda inválida. Use BRL, USD ou EUR.';
        }

        // Saldo inicial
        if (isset($data['saldo_inicial'])) {
            $saldo = (float) $data['saldo_inicial'];
            if ($saldo < -999999999 || $saldo > 999999999) {
                $this->errors['saldo_inicial'] = 'Saldo inicial fora do intervalo permitido.';
            }
        }

        // Instituição financeira ID (se fornecido)
        if (isset($data['instituicao_financeira_id']) && $data['instituicao_financeira_id'] !== '') {
            $id = (int) $data['instituicao_financeira_id'];
            if ($id <= 0) {
                $this->errors['instituicao_financeira_id'] = 'ID da instituição financeira inválido.';
            }
        }

        return empty($this->errors);
    }

    public function validateUpdate(array $data): bool
    {
        $this->errors = [];

        // Nome (se fornecido)
        if (isset($data['nome'])) {
            $nome = trim((string) $data['nome']);
            if ($nome === '') {
                $this->errors['nome'] = 'Nome da conta não pode ser vazio.';
            } elseif (strlen($nome) > 100) {
                $this->errors['nome'] = 'Nome da conta não pode exceder 100 caracteres.';
            }
        }

        // Tipo de conta (se fornecido)
        if (isset($data['tipo_conta'])) {
            $tiposPermitidos = ['conta_corrente', 'conta_poupanca', 'conta_investimento', 'carteira_digital', 'dinheiro'];
            $tipoConta = trim((string) $data['tipo_conta']);
            if (!in_array($tipoConta, $tiposPermitidos, true)) {
                $this->errors['tipo_conta'] = 'Tipo de conta inválido.';
            }
        }

        // Moeda (se fornecida)
        if (isset($data['moeda'])) {
            $moedasPermitidas = ['BRL', 'USD', 'EUR'];
            $moeda = strtoupper(trim((string) $data['moeda']));
            if (!in_array($moeda, $moedasPermitidas, true)) {
                $this->errors['moeda'] = 'Moeda inválida. Use BRL, USD ou EUR.';
            }
        }

        // Saldo inicial (se fornecido)
        if (isset($data['saldo_inicial'])) {
            $saldo = (float) $data['saldo_inicial'];
            if ($saldo < -999999999 || $saldo > 999999999) {
                $this->errors['saldo_inicial'] = 'Saldo inicial fora do intervalo permitido.';
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
