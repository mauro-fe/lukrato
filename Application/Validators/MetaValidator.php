<?php

declare(strict_types=1);

namespace Application\Validators;

use Application\Models\Meta;

class MetaValidator
{
    /**
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    public static function validateCreate(array $data): array
    {
        $errors = [];

        $titulo = trim((string) ($data['titulo'] ?? ''));
        if ($titulo === '') {
            $errors['titulo'] = 'O título é obrigatório.';
        } elseif (mb_strlen($titulo) > 150) {
            $errors['titulo'] = 'O título não pode ter mais de 150 caracteres.';
        }

        $valorAlvo = $data['valor_alvo'] ?? null;
        if ($valorAlvo === null || $valorAlvo === '') {
            $errors['valor_alvo'] = 'O valor da meta é obrigatório.';
        } elseif (!is_numeric($valorAlvo) || (float) $valorAlvo <= 0) {
            $errors['valor_alvo'] = 'O valor deve ser maior que zero.';
        }

        $tipo = $data['tipo'] ?? '';
        $tiposValidos = [
            Meta::TIPO_ECONOMIA,
            Meta::TIPO_QUITACAO,
            Meta::TIPO_INVESTIMENTO,
            Meta::TIPO_COMPRA,
            Meta::TIPO_EMERGENCIA,
            Meta::TIPO_VIAGEM,
            Meta::TIPO_EDUCACAO,
            Meta::TIPO_MORADIA,
            Meta::TIPO_VEICULO,
            Meta::TIPO_SAUDE,
            Meta::TIPO_NEGOCIO,
            Meta::TIPO_APOSENTADORIA,
            Meta::TIPO_OUTRO,
        ];
        if ($tipo !== '' && !in_array((string) $tipo, $tiposValidos, true)) {
            $errors['tipo'] = 'Tipo de meta inválido.';
        }

        $modelo = $data['modelo'] ?? null;
        $modelosValidos = [Meta::MODELO_RESERVA, Meta::MODELO_REALIZACAO];
        if ($modelo !== null && $modelo !== '' && !in_array((string) $modelo, $modelosValidos, true)) {
            $errors['modelo'] = 'Modelo de meta inválido.';
        }

        $prioridade = $data['prioridade'] ?? '';
        $prioridadesValidas = [Meta::PRIORIDADE_BAIXA, Meta::PRIORIDADE_MEDIA, Meta::PRIORIDADE_ALTA];
        if ($prioridade !== '' && !in_array((string) $prioridade, $prioridadesValidas, true)) {
            $errors['prioridade'] = 'Prioridade inválida.';
        }

        $dataPrazo = $data['data_prazo'] ?? null;
        if (!empty($dataPrazo)) {
            $d = \DateTime::createFromFormat('Y-m-d', (string) $dataPrazo);
            if (!$d || $d->format('Y-m-d') !== (string) $dataPrazo) {
                $errors['data_prazo'] = 'Data inválida. Use o formato YYYY-MM-DD.';
            }
        }

        $valorAlocado = $data['valor_alocado'] ?? $data['valor_atual'] ?? null;
        if ($valorAlocado !== null && $valorAlocado !== '' && (!is_numeric($valorAlocado) || (float) $valorAlocado < 0)) {
            $errors['valor_alocado'] = 'O valor alocado deve ser zero ou positivo.';
        }

        $valorRealizado = $data['valor_realizado'] ?? null;
        if ($valorRealizado !== null && $valorRealizado !== '' && (!is_numeric($valorRealizado) || (float) $valorRealizado < 0)) {
            $errors['valor_realizado'] = 'O valor realizado deve ser zero ou positivo.';
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    public static function validateUpdate(array $data): array
    {
        $errors = [];

        if (isset($data['titulo'])) {
            $titulo = trim((string) $data['titulo']);
            if ($titulo === '') {
                $errors['titulo'] = 'O título é obrigatório.';
            } elseif (mb_strlen($titulo) > 150) {
                $errors['titulo'] = 'O título não pode ter mais de 150 caracteres.';
            }
        }

        if (isset($data['valor_alvo']) && (!is_numeric($data['valor_alvo']) || (float) $data['valor_alvo'] <= 0)) {
            $errors['valor_alvo'] = 'O valor deve ser maior que zero.';
        }

        if (array_key_exists('valor_alocado', $data) || array_key_exists('valor_atual', $data)) {
            $valorAlocado = $data['valor_alocado'] ?? $data['valor_atual'];
            if ($valorAlocado !== null && $valorAlocado !== '' && (!is_numeric($valorAlocado) || (float) $valorAlocado < 0)) {
                $errors['valor_alocado'] = 'O valor alocado deve ser zero ou positivo.';
            }
        }

        if (array_key_exists('valor_realizado', $data)) {
            $valorRealizado = $data['valor_realizado'];
            if ($valorRealizado !== null && $valorRealizado !== '' && (!is_numeric($valorRealizado) || (float) $valorRealizado < 0)) {
                $errors['valor_realizado'] = 'O valor realizado deve ser zero ou positivo.';
            }
        }

        if (array_key_exists('modelo', $data)) {
            $modelosValidos = [Meta::MODELO_RESERVA, Meta::MODELO_REALIZACAO];
            if ($data['modelo'] !== null && $data['modelo'] !== '' && !in_array((string) $data['modelo'], $modelosValidos, true)) {
                $errors['modelo'] = 'Modelo de meta inválido.';
            }
        }

        $status = $data['status'] ?? null;
        $statusValidos = [
            Meta::STATUS_ATIVA,
            Meta::STATUS_CONCLUIDA,
            Meta::STATUS_REALIZADA,
            Meta::STATUS_PAUSADA,
            Meta::STATUS_CANCELADA,
        ];
        if ($status && !in_array((string) $status, $statusValidos, true)) {
            $errors['status'] = 'Status inválido.';
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
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

