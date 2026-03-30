<?php

declare(strict_types=1);

namespace Application\Validators;

use Application\Enums\LancamentoTipo;
use Application\Models\Lancamento;
use Application\Models\Meta;
use Application\Repositories\CategoriaRepository;
use Application\Repositories\ContaRepository;

class LancamentoValidator
{
    private const FREQ_VALIDAS = [
        'semanal',
        'quinzenal',
        'mensal',
        'bimestral',
        'trimestral',
        'semestral',
        'anual',
    ];

    private const FORMAS_PAGAMENTO_VALIDAS = [
        'pix',
        'cartao_credito',
        'cartao_debito',
        'dinheiro',
        'boleto',
        'transferencia',
        'deposito',
        'estorno_cartao',
        'cheque',
    ];

    public static function validateCreate(array $data): array
    {
        $errors = [];

        $tipo = strtolower(trim((string) ($data['tipo'] ?? '')));
        if ($tipo === '') {
            $errors['tipo'] = 'O tipo e obrigatorio.';
        } else {
            try {
                LancamentoTipo::from($tipo);
            } catch (\ValueError) {
                $errors['tipo'] = 'Tipo invalido. Use "receita" ou "despesa".';
            }
        }

        $dataValue = (string) ($data['data'] ?? '');
        if ($dataValue === '') {
            $errors['data'] = 'A data e obrigatoria.';
        } elseif (!preg_match('/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', $dataValue)) {
            $errors['data'] = 'Data invalida. Use o formato YYYY-MM-DD.';
        }

        $valorRaw = $data['valor'] ?? null;
        if ($valorRaw === null || $valorRaw === '') {
            $errors['valor'] = 'O valor e obrigatorio.';
        } else {
            $valor = self::parseMoney($valorRaw);
            if ($valor === null || !is_finite($valor)) {
                $errors['valor'] = 'Valor invalido.';
            } elseif ($valor <= 0) {
                $errors['valor'] = 'O valor deve ser maior que zero.';
            }
        }

        $descricao = trim((string) ($data['descricao'] ?? ''));
        if ($descricao === '') {
            $errors['descricao'] = 'A descricao e obrigatoria.';
        } elseif (mb_strlen($descricao) > 190) {
            $errors['descricao'] = 'A descricao nao pode ter mais de 190 caracteres.';
        }

        $observacao = trim((string) ($data['observacao'] ?? ''));
        if ($observacao !== '' && mb_strlen($observacao) > 500) {
            $errors['observacao'] = 'A observacao nao pode ter mais de 500 caracteres.';
        }

        $contaId = $data['conta_id'] ?? null;
        $cartaoCreditoId = $data['cartao_credito_id'] ?? null;
        if (empty($contaId) && empty($cartaoCreditoId)) {
            $errors['conta_id'] = 'A conta e obrigatoria.';
        }

        $formaPagamento = $data['forma_pagamento'] ?? null;
        if (!empty($formaPagamento) && !in_array($formaPagamento, self::FORMAS_PAGAMENTO_VALIDAS, true)) {
            $errors['forma_pagamento'] = 'Forma de pagamento invalida.';
        }

        $recorrente = (bool) ($data['recorrente'] ?? false);
        if ($recorrente) {
            $freq = $data['recorrencia_freq'] ?? null;
            if (empty($freq)) {
                $errors['recorrencia_freq'] = 'A frequencia da recorrencia e obrigatoria.';
            } elseif (!in_array($freq, self::FREQ_VALIDAS, true)) {
                $errors['recorrencia_freq'] = 'Frequencia invalida. Use: ' . implode(', ', self::FREQ_VALIDAS) . '.';
            }

            $fim = $data['recorrencia_fim'] ?? null;
            if ($fim !== null && $fim !== '') {
                if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', (string) $fim)) {
                    $errors['recorrencia_fim'] = 'Data de fim da recorrencia invalida. Use YYYY-MM-DD.';
                } elseif ($dataValue !== '' && $fim <= $dataValue) {
                    $errors['recorrencia_fim'] = 'A data de fim deve ser posterior a data do lancamento.';
                }
            }

            $total = $data['recorrencia_total'] ?? null;
            if ($total !== null && $total !== '') {
                $total = (int) $total;
                if ($total < 2) {
                    $errors['recorrencia_total'] = 'O numero de repeticoes deve ser pelo menos 2.';
                } elseif ($total > 120) {
                    $errors['recorrencia_total'] = 'O numero maximo de repeticoes e 120.';
                }
            }
        }

        $metaId = $data['meta_id'] ?? $data['metaId'] ?? null;
        if ($metaId !== null && $metaId !== '') {
            $metaId = (int) $metaId;
            if ($metaId <= 0) {
                $errors['meta_id'] = 'Meta invalida.';
            } else {
                self::validateMetaLinkRules($metaId, $data, $errors);
            }
        }

        $metaOperacao = strtolower(trim((string) ($data['meta_operacao'] ?? $data['metaOperacao'] ?? '')));
        $metaValor = $data['meta_valor'] ?? $data['metaValor'] ?? null;
        if (($metaOperacao !== '' || $metaValor !== null) && ($metaId === null || $metaId <= 0)) {
            $errors['meta_id'] = 'Informe uma meta para usar operacao de meta.';
        }

        if ($metaValor !== null && $metaValor !== '') {
            $valorMeta = self::parseMoney($metaValor);
            if ($valorMeta === null || $valorMeta <= 0) {
                $errors['meta_valor'] = 'O valor vinculado a meta deve ser maior que zero.';
            } else {
                $valorLanc = self::parseMoney($data['valor'] ?? null) ?? 0.0;
                if ($valorLanc > 0 && $valorMeta > $valorLanc + 0.001) {
                    $errors['meta_valor'] = 'O valor vinculado a meta nao pode ser maior que o valor do lancamento.';
                }
            }
        }

        $lembrar = $data['lembrar_antes_segundos'] ?? null;
        if ($lembrar !== null && $lembrar !== '' && (int) $lembrar < 0) {
            $errors['lembrar_antes_segundos'] = 'Antecedencia do lembrete invalida.';
        }

        return $errors;
    }

    public static function validateUpdate(array $data): array
    {
        return self::validateCreate($data);
    }

    public static function sanitizeValor(mixed $valor): float
    {
        if (is_string($valor)) {
            $valor = str_replace(['R$', ' ', '.'], '', $valor);
            $valor = str_replace(',', '.', $valor);
        }

        return round(abs((float) $valor), 2);
    }

    public static function sanitizeMetaValor(mixed $valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (is_string($valor)) {
            $valor = str_replace(['R$', ' ', '.'], '', $valor);
            $valor = str_replace(',', '.', $valor);
        }

        if (!is_numeric($valor) || !is_finite((float) $valor)) {
            return null;
        }

        return round(max(0, (float) $valor), 2);
    }

    public static function normalizeMetaOperation(mixed $raw): ?string
    {
        if (!is_scalar($raw)) {
            return null;
        }

        $op = strtolower(trim((string) $raw));
        if ($op === '') {
            return null;
        }

        if (in_array($op, [
            Lancamento::META_OPERACAO_APORTE,
            Lancamento::META_OPERACAO_RESGATE,
            Lancamento::META_OPERACAO_REALIZACAO,
        ], true)) {
            return $op;
        }

        return null;
    }

    public static function resolveMetaOperationForContext(?string $explicitOperation, array $data, ?Meta $meta = null): ?string
    {
        $normalized = self::normalizeMetaOperation($explicitOperation);
        if ($normalized !== null) {
            return $normalized;
        }

        $tipo = strtolower(trim((string) ($data['tipo'] ?? '')));
        $ehTransferencia = (bool) ($data['eh_transferencia'] ?? false)
            || $tipo === 'transferencia'
            || !empty($data['conta_id_destino'])
            || !empty($data['conta_destino_id']);

        if ($ehTransferencia || $tipo === 'receita') {
            return Lancamento::META_OPERACAO_APORTE;
        }

        if ($tipo === 'despesa') {
            $modelo = (string) ($meta?->modelo ?? Meta::MODELO_RESERVA);
            return $modelo === Meta::MODELO_REALIZACAO
                ? Lancamento::META_OPERACAO_REALIZACAO
                : Lancamento::META_OPERACAO_RESGATE;
        }

        return null;
    }

    public static function validateCategoriaOwnership(?int $id, int $userId, array &$errors): ?int
    {
        if ($id === null || $id <= 0) {
            return null;
        }

        if ((new CategoriaRepository())->belongsToUser($id, $userId)) {
            return $id;
        }

        $errors['categoria_id'] = 'Categoria invalida.';
        return null;
    }

    public static function validateSubcategoriaOwnership(?int $subcategoriaId, ?int $categoriaId, int $userId, array &$errors): ?int
    {
        if ($subcategoriaId === null || $subcategoriaId <= 0) {
            return null;
        }

        $repo = new CategoriaRepository();
        if (!$repo->belongsToUser($subcategoriaId, $userId)) {
            $errors['subcategoria_id'] = 'Subcategoria invalida.';
            return null;
        }

        $subcategoria = $repo->find($subcategoriaId);
        if (!$subcategoria || !$subcategoria->isSubcategoria()) {
            $errors['subcategoria_id'] = 'A categoria selecionada nao e uma subcategoria.';
            return null;
        }

        if ($categoriaId !== null && (int) $subcategoria->parent_id !== $categoriaId) {
            $errors['subcategoria_id'] = 'Subcategoria nao pertence a categoria selecionada.';
            return null;
        }

        return $subcategoriaId;
    }

    public static function validateContaOwnership(?int $id, int $userId, array &$errors): ?int
    {
        if ($id === null) {
            return null;
        }

        if ((new ContaRepository())->belongsToUser($id, $userId)) {
            return $id;
        }

        $errors['conta_id'] = 'Conta invalida.';
        return null;
    }

    public static function validateMetaOwnership(?int $id, int $userId, array &$errors): ?int
    {
        if ($id === null || $id <= 0) {
            return null;
        }

        $meta = Meta::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$meta) {
            $errors['meta_id'] = 'Meta invalida.';
            return null;
        }

        $status = strtolower(trim((string) ($meta->status ?? '')));
        if (in_array($status, [Meta::STATUS_CANCELADA, Meta::STATUS_REALIZADA], true)) {
            $errors['meta_id'] = 'Esta meta nao aceita novos vinculos.';
            return null;
        }

        return $id;
    }

    public static function validateMetaLinkRules(?int $metaId, array $data, array &$errors): void
    {
        if ($metaId === null || $metaId <= 0) {
            return;
        }

        $tipo = strtolower(trim((string) ($data['tipo'] ?? '')));
        $ehTransferencia = (bool) ($data['eh_transferencia'] ?? false)
            || $tipo === 'transferencia'
            || !empty($data['conta_id_destino'])
            || !empty($data['conta_destino_id']);

        $metaOperacao = self::normalizeMetaOperation($data['meta_operacao'] ?? $data['metaOperacao'] ?? null);
        $metaValorRaw = $data['meta_valor'] ?? $data['metaValor'] ?? null;

        if ($metaValorRaw !== null && $metaValorRaw !== '') {
            $metaValor = self::parseMoney($metaValorRaw);
            if ($metaValor === null || $metaValor <= 0) {
                $errors['meta_valor'] = 'O valor vinculado a meta deve ser maior que zero.';
            } else {
                $valorLancamento = self::parseMoney($data['valor'] ?? null);
                if ($valorLancamento !== null && $valorLancamento > 0 && $metaValor > $valorLancamento + 0.001) {
                    $errors['meta_valor'] = 'O valor vinculado a meta nao pode ser maior que o valor do lancamento.';
                }
            }
        }

        if ($ehTransferencia || $tipo === 'receita') {
            if ($metaOperacao !== null && $metaOperacao !== Lancamento::META_OPERACAO_APORTE) {
                $errors['meta_operacao'] = 'Receitas e transferencias aceitam apenas operacao de aporte.';
            }
            return;
        }

        if ($tipo === 'despesa') {
            $formaPagamento = strtolower(trim((string) ($data['forma_pagamento'] ?? '')));
            if ($formaPagamento === 'cartao_credito') {
                $errors['meta_id'] = 'Despesas no cartao de credito devem ser vinculadas na etapa de pagamento da fatura.';
                return;
            }

            if ($metaOperacao !== null && !in_array($metaOperacao, [
                Lancamento::META_OPERACAO_RESGATE,
                Lancamento::META_OPERACAO_REALIZACAO,
            ], true)) {
                $errors['meta_operacao'] = 'Despesas aceitam apenas operacoes de resgate ou realizacao.';
            }
            return;
        }

        $errors['meta_id'] = 'Somente receitas, despesas e transferencias podem ser vinculadas a uma meta.';
    }

    private static function parseMoney(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = str_replace(['R$', ' ', '.'], '', $value);
            $value = str_replace(',', '.', $value);
        }

        if (!is_numeric($value) || !is_finite((float) $value)) {
            return null;
        }

        return (float) $value;
    }
}
