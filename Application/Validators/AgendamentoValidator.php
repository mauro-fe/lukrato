<?php

declare(strict_types=1);

namespace Application\Validators;

use Application\Enums\LancamentoTipo;
use Application\Enums\AgendamentoStatus;
use DateTimeImmutable;
use Throwable;

/**
 * Validador para agendamentos.
 */
class AgendamentoValidator
{
    /**
     * Valida dados para criação de agendamento.
     */
    public static function validateCreate(array $data): array
    {
        $errors = [];

        // Validar título
        $titulo = trim($data['titulo'] ?? '');
        if (empty($titulo)) {
            $errors['titulo'] = 'O título é obrigatório.';
        } elseif (mb_strlen($titulo) < 3) {
            $errors['titulo'] = 'O título deve ter pelo menos 3 caracteres.';
        } elseif (mb_strlen($titulo) > 160) {
            $errors['titulo'] = 'O título não pode ter mais de 160 caracteres.';
        }

        // Validar data de pagamento
        $dataPagamento = trim($data['data_pagamento'] ?? '');
        if (empty($dataPagamento)) {
            $errors['data_pagamento'] = 'A data de pagamento é obrigatória.';
        } else {
            $normalized = str_replace('T', ' ', $dataPagamento);
            try {
                new DateTimeImmutable($normalized);
            } catch (Throwable) {
                $errors['data_pagamento'] = 'Data de pagamento inválida.';
            }
        }

        // Validar tipo
        $tipo = strtolower(trim($data['tipo'] ?? ''));
        if (empty($tipo)) {
            $errors['tipo'] = 'O tipo é obrigatório.';
        } else {
            try {
                LancamentoTipo::from($tipo);
            } catch (\ValueError) {
                $errors['tipo'] = 'Tipo inválido. Use "receita" ou "despesa".';
            }
        }

        // Validar valor_centavos
        $valorCentavos = $data['valor_centavos'] ?? null;
        if ($valorCentavos === null || $valorCentavos === '') {
            $errors['valor_centavos'] = 'O valor é obrigatório.';
        } elseif (!is_numeric($valorCentavos)) {
            $errors['valor_centavos'] = 'Valor inválido.';
        } elseif ((int)$valorCentavos <= 0) {
            $errors['valor_centavos'] = 'O valor deve ser maior que zero.';
        }

        // Validar lembrar_antes_segundos (opcional)
        if (isset($data['lembrar_antes_segundos'])) {
            $lembrarSegundos = $data['lembrar_antes_segundos'];
            if (!is_numeric($lembrarSegundos)) {
                $errors['lembrar_antes_segundos'] = 'Valor inválido para lembrete.';
            } elseif ((int)$lembrarSegundos < 0) {
                $errors['lembrar_antes_segundos'] = 'O tempo de lembrete não pode ser negativo.';
            }
        }

        // Validar categoria_id (opcional)
        if (isset($data['categoria_id']) && $data['categoria_id'] !== null && $data['categoria_id'] !== '') {
            if (!is_numeric($data['categoria_id']) || (int)$data['categoria_id'] < 1) {
                $errors['categoria_id'] = 'ID de categoria inválido.';
            }
        }

        // Validar conta_id (opcional)
        if (isset($data['conta_id']) && $data['conta_id'] !== null && $data['conta_id'] !== '') {
            if (!is_numeric($data['conta_id']) || (int)$data['conta_id'] < 1) {
                $errors['conta_id'] = 'ID de conta inválido.';
            }
        }

        // Validar recorrência
        $recorrente = filter_var($data['recorrente'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($recorrente) {
            $freq = trim($data['recorrencia_freq'] ?? '');
            $validFreqs = ['diario', 'semanal', 'mensal', 'anual'];
            
            if (empty($freq)) {
                $errors['recorrencia_freq'] = 'A frequência de recorrência é obrigatória quando recorrente é verdadeiro.';
            } elseif (!in_array($freq, $validFreqs, true)) {
                $errors['recorrencia_freq'] = 'Frequência inválida. Use: diario, semanal, mensal ou anual.';
            }

            // Validar intervalo
            if (isset($data['recorrencia_intervalo'])) {
                $intervalo = $data['recorrencia_intervalo'];
                if (!is_numeric($intervalo) || (int)$intervalo < 1) {
                    $errors['recorrencia_intervalo'] = 'O intervalo de recorrência deve ser no mínimo 1.';
                }
            }

            // Validar recorrencia_fim (opcional)
            if (isset($data['recorrencia_fim']) && !empty($data['recorrencia_fim'])) {
                try {
                    new DateTimeImmutable($data['recorrencia_fim']);
                } catch (Throwable) {
                    $errors['recorrencia_fim'] = 'Data de fim de recorrência inválida.';
                }
            }
        }

        return $errors;
    }

    /**
     * Valida dados para atualização de agendamento.
     */
    public static function validateUpdate(array $data): array
    {
        $errors = [];

        // Para update, todos os campos são opcionais, mas se fornecidos, devem ser válidos

        if (isset($data['titulo'])) {
            $titulo = trim($data['titulo']);
            if (empty($titulo)) {
                $errors['titulo'] = 'O título não pode ser vazio.';
            } elseif (mb_strlen($titulo) < 3) {
                $errors['titulo'] = 'O título deve ter pelo menos 3 caracteres.';
            } elseif (mb_strlen($titulo) > 160) {
                $errors['titulo'] = 'O título não pode ter mais de 160 caracteres.';
            }
        }

        if (isset($data['data_pagamento'])) {
            $dataPagamento = trim($data['data_pagamento']);
            if (empty($dataPagamento)) {
                $errors['data_pagamento'] = 'A data de pagamento não pode ser vazia.';
            } else {
                $normalized = str_replace('T', ' ', $dataPagamento);
                try {
                    new DateTimeImmutable($normalized);
                } catch (Throwable) {
                    $errors['data_pagamento'] = 'Data de pagamento inválida.';
                }
            }
        }

        if (isset($data['tipo'])) {
            $tipo = strtolower(trim($data['tipo']));
            if (empty($tipo)) {
                $errors['tipo'] = 'O tipo não pode ser vazio.';
            } else {
                try {
                    LancamentoTipo::from($tipo);
                } catch (\ValueError) {
                    $errors['tipo'] = 'Tipo inválido. Use "receita" ou "despesa".';
                }
            }
        }

        if (isset($data['valor_centavos'])) {
            $valorCentavos = $data['valor_centavos'];
            if ($valorCentavos === null || $valorCentavos === '') {
                $errors['valor_centavos'] = 'O valor não pode ser vazio.';
            } elseif (!is_numeric($valorCentavos)) {
                $errors['valor_centavos'] = 'Valor inválido.';
            } elseif ((int)$valorCentavos <= 0) {
                $errors['valor_centavos'] = 'O valor deve ser maior que zero.';
            }
        }

        if (isset($data['lembrar_antes_segundos'])) {
            $lembrarSegundos = $data['lembrar_antes_segundos'];
            if (!is_numeric($lembrarSegundos)) {
                $errors['lembrar_antes_segundos'] = 'Valor inválido para lembrete.';
            } elseif ((int)$lembrarSegundos < 0) {
                $errors['lembrar_antes_segundos'] = 'O tempo de lembrete não pode ser negativo.';
            }
        }

        if (isset($data['categoria_id']) && $data['categoria_id'] !== null && $data['categoria_id'] !== '') {
            if (!is_numeric($data['categoria_id']) || (int)$data['categoria_id'] < 1) {
                $errors['categoria_id'] = 'ID de categoria inválido.';
            }
        }

        if (isset($data['conta_id']) && $data['conta_id'] !== null && $data['conta_id'] !== '') {
            if (!is_numeric($data['conta_id']) || (int)$data['conta_id'] < 1) {
                $errors['conta_id'] = 'ID de conta inválido.';
            }
        }

        // Validar recorrência se fornecida
        if (isset($data['recorrente'])) {
            $recorrente = filter_var($data['recorrente'], FILTER_VALIDATE_BOOLEAN);
            if ($recorrente && isset($data['recorrencia_freq'])) {
                $freq = trim($data['recorrencia_freq']);
                $validFreqs = ['diario', 'semanal', 'mensal', 'anual'];
                
                if (!empty($freq) && !in_array($freq, $validFreqs, true)) {
                    $errors['recorrencia_freq'] = 'Frequência inválida. Use: diario, semanal, mensal ou anual.';
                }
            }

            if (isset($data['recorrencia_intervalo'])) {
                $intervalo = $data['recorrencia_intervalo'];
                if (!is_numeric($intervalo) || (int)$intervalo < 1) {
                    $errors['recorrencia_intervalo'] = 'O intervalo de recorrência deve ser no mínimo 1.';
                }
            }

            if (isset($data['recorrencia_fim']) && !empty($data['recorrencia_fim'])) {
                try {
                    new DateTimeImmutable($data['recorrencia_fim']);
                } catch (Throwable) {
                    $errors['recorrencia_fim'] = 'Data de fim de recorrência inválida.';
                }
            }
        }

        return $errors;
    }
}
