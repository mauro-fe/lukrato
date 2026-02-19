<?php

declare(strict_types=1);

namespace Application\Validators;

use Application\Models\Meta;

class MetaValidator
{
    public static function validateCreate(array $data): array
    {
        $errors = [];

        // Título
        $titulo = trim($data['titulo'] ?? '');
        if (empty($titulo)) {
            $errors['titulo'] = 'O título é obrigatório.';
        } elseif (mb_strlen($titulo) > 150) {
            $errors['titulo'] = 'O título não pode ter mais de 150 caracteres.';
        }

        // Valor alvo
        $valorAlvo = $data['valor_alvo'] ?? null;
        if ($valorAlvo === null || $valorAlvo === '') {
            $errors['valor_alvo'] = 'O valor da meta é obrigatório.';
        } elseif (!is_numeric($valorAlvo) || (float) $valorAlvo <= 0) {
            $errors['valor_alvo'] = 'O valor deve ser maior que zero.';
        }

        // Tipo
        $tipo = $data['tipo'] ?? '';
        $tiposValidos = [
            Meta::TIPO_ECONOMIA,
            Meta::TIPO_QUITACAO,
            Meta::TIPO_INVESTIMENTO,
            Meta::TIPO_COMPRA,
            Meta::TIPO_EMERGENCIA,
        ];
        if (!empty($tipo) && !in_array($tipo, $tiposValidos, true)) {
            $errors['tipo'] = 'Tipo de meta inválido.';
        }

        // Prioridade
        $prioridade = $data['prioridade'] ?? '';
        $prioridadesValidas = [Meta::PRIORIDADE_BAIXA, Meta::PRIORIDADE_MEDIA, Meta::PRIORIDADE_ALTA];
        if (!empty($prioridade) && !in_array($prioridade, $prioridadesValidas, true)) {
            $errors['prioridade'] = 'Prioridade inválida.';
        }

        // Data prazo (opcional)
        $dataPrazo = $data['data_prazo'] ?? null;
        if (!empty($dataPrazo)) {
            $d = \DateTime::createFromFormat('Y-m-d', $dataPrazo);
            if (!$d || $d->format('Y-m-d') !== $dataPrazo) {
                $errors['data_prazo'] = 'Data inválida. Use o formato YYYY-MM-DD.';
            }
        }

        // Valor atual (opcional, para update)
        $valorAtual = $data['valor_atual'] ?? null;
        if ($valorAtual !== null && $valorAtual !== '' && (!is_numeric($valorAtual) || (float) $valorAtual < 0)) {
            $errors['valor_atual'] = 'O valor atual deve ser zero ou positivo.';
        }

        return $errors;
    }

    public static function validateUpdate(array $data): array
    {
        $errors = [];

        // Título (se enviado)
        if (isset($data['titulo'])) {
            $titulo = trim($data['titulo']);
            if (empty($titulo)) {
                $errors['titulo'] = 'O título é obrigatório.';
            } elseif (mb_strlen($titulo) > 150) {
                $errors['titulo'] = 'O título não pode ter mais de 150 caracteres.';
            }
        }

        // Valor alvo (se enviado)
        if (isset($data['valor_alvo'])) {
            if (!is_numeric($data['valor_alvo']) || (float) $data['valor_alvo'] <= 0) {
                $errors['valor_alvo'] = 'O valor deve ser maior que zero.';
            }
        }

        // Status
        $status = $data['status'] ?? null;
        $statusValidos = [Meta::STATUS_ATIVA, Meta::STATUS_CONCLUIDA, Meta::STATUS_PAUSADA, Meta::STATUS_CANCELADA];
        if ($status && !in_array($status, $statusValidos, true)) {
            $errors['status'] = 'Status inválido.';
        }

        return $errors;
    }

    public static function validateAporte(array $data): array
    {
        $errors = [];

        $valor = $data['valor'] ?? null;
        if ($valor === null || $valor === '') {
            $errors['valor'] = 'O valor do aporte é obrigatório.';
        } elseif (!is_numeric($valor) || (float) $valor <= 0) {
            $errors['valor'] = 'O valor deve ser maior que zero.';
        }

        return $errors;
    }
}
