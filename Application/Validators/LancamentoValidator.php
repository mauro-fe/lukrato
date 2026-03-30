<?php

declare(strict_types=1);

namespace Application\Validators;

use Application\Enums\LancamentoTipo;
use Application\Enums\Recorrencia;
use Application\Models\Meta;
use Application\Repositories\CategoriaRepository;
use Application\Repositories\ContaRepository;

/**
 * Validador para lançamentos.
 */
class LancamentoValidator
{
    /** Frequências válidas de recorrência */
    private const FREQ_VALIDAS = [
        'semanal',
        'quinzenal',
        'mensal',
        'bimestral',
        'trimestral',
        'semestral',
        'anual',
    ];

    /** Formas de pagamento válidas */
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

    /**
     * Valida dados para criação de lançamento.
     */
    public static function validateCreate(array $data): array
    {
        $errors = [];

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

        // Validar data
        $data_value = $data['data'] ?? '';
        if (empty($data_value)) {
            $errors['data'] = 'A data é obrigatória.';
        } elseif (!preg_match('/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', $data_value)) {
            $errors['data'] = 'Data inválida. Use o formato YYYY-MM-DD.';
        }

        // Validar valor
        $valor = $data['valor'] ?? null;
        if ($valor === null || $valor === '') {
            $errors['valor'] = 'O valor é obrigatório.';
        } else {
            // Sanitizar valor
            if (is_string($valor)) {
                $valor = str_replace(['R$', ' ', '.'], '', $valor);
                $valor = str_replace(',', '.', $valor);
            }

            if (!is_numeric($valor) || !is_finite((float)$valor)) {
                $errors['valor'] = 'Valor inválido.';
            } elseif ((float)$valor <= 0) {
                $errors['valor'] = 'O valor deve ser maior que zero.';
            }
        }

        // Validar descrição
        $descricao = trim($data['descricao'] ?? '');
        if (empty($descricao)) {
            $errors['descricao'] = 'A descrição é obrigatória.';
        } elseif (mb_strlen($descricao) > 190) {
            $errors['descricao'] = 'A descrição não pode ter mais de 190 caracteres.';
        }

        // Validar observação (opcional)
        $observacao = trim($data['observacao'] ?? '');
        if (!empty($observacao) && mb_strlen($observacao) > 500) {
            $errors['observacao'] = 'A observação não pode ter mais de 500 caracteres.';
        }

        // Validar conta_id (obrigatório para lançamentos sem cartão)
        $contaId = $data['conta_id'] ?? null;
        $cartaoCreditoId = $data['cartao_credito_id'] ?? null;
        if (empty($contaId) && empty($cartaoCreditoId)) {
            $errors['conta_id'] = 'A conta é obrigatória.';
        }

        // Validar forma de pagamento (opcional, mas se informada deve ser válida)
        $formaPagamento = $data['forma_pagamento'] ?? null;
        if (!empty($formaPagamento) && !in_array($formaPagamento, self::FORMAS_PAGAMENTO_VALIDAS, true)) {
            $errors['forma_pagamento'] = 'Forma de pagamento inválida.';
        }

        // Validar recorrência
        $recorrente = (bool)($data['recorrente'] ?? false);
        if ($recorrente) {
            $freq = $data['recorrencia_freq'] ?? null;
            if (empty($freq)) {
                $errors['recorrencia_freq'] = 'A frequência da recorrência é obrigatória.';
            } elseif (!in_array($freq, self::FREQ_VALIDAS, true)) {
                $errors['recorrencia_freq'] = 'Frequência inválida. Use: ' . implode(', ', self::FREQ_VALIDAS) . '.';
            }

            $fim = $data['recorrencia_fim'] ?? null;
            if ($fim !== null && $fim !== '') {
                if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', $fim)) {
                    $errors['recorrencia_fim'] = 'Data de fim da recorrência inválida. Use YYYY-MM-DD.';
                } elseif (!empty($data_value) && $fim <= $data_value) {
                    $errors['recorrencia_fim'] = 'A data de fim deve ser posterior à data do lançamento.';
                }
            }

            // Validar total de repetições
            $total = $data['recorrencia_total'] ?? null;
            if ($total !== null && $total !== '') {
                $total = (int)$total;
                if ($total < 2) {
                    $errors['recorrencia_total'] = 'O número de repetições deve ser pelo menos 2.';
                } elseif ($total > 120) {
                    $errors['recorrencia_total'] = 'O número máximo de repetições é 120.';
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

        // Validar lembrete
        $lembrar = $data['lembrar_antes_segundos'] ?? null;
        if ($lembrar !== null && $lembrar !== '') {
            $lembrar = (int)$lembrar;
            if ($lembrar < 0) {
                $errors['lembrar_antes_segundos'] = 'Antecedência do lembrete inválida.';
            }
        }

        return $errors;
    }

    /**
     * Valida dados para atualização de lançamento.
     */
    public static function validateUpdate(array $data): array
    {
        return self::validateCreate($data);
    }

    /**
     * Sanitiza o valor do lançamento.
     */
    public static function sanitizeValor(mixed $valor): float
    {
        if (is_string($valor)) {
            $valor = str_replace(['R$', ' ', '.'], '', $valor);
            $valor = str_replace(',', '.', $valor);
        }

        return round(abs((float)$valor), 2);
    }

    // ─── Validação de pertencimento ────────────────────────

    /**
     * Valida que a categoria pertence ao usuário.
     *
     * @param int|null $id ID da categoria
     * @param int $userId ID do usuário
     * @param array &$errors Array de erros para append
     * @return int|null ID validado ou null
     */
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

    /**
     * Valida que a subcategoria pertence ao usuário e à categoria informada.
     *
     * @param int|null $subcategoriaId ID da subcategoria
     * @param int|null $categoriaId ID da categoria pai
     * @param int $userId ID do usuário
     * @param array &$errors Array de erros para append
     * @return int|null ID validado ou null
     */
    public static function validateSubcategoriaOwnership(?int $subcategoriaId, ?int $categoriaId, int $userId, array &$errors): ?int
    {
        if ($subcategoriaId === null || $subcategoriaId <= 0) {
            return null;
        }

        $repo = new CategoriaRepository();

        // Verificar se a subcategoria existe e pertence ao usuário
        if (!$repo->belongsToUser($subcategoriaId, $userId)) {
            $errors['subcategoria_id'] = 'Subcategoria inválida.';
            return null;
        }

        // Verificar se é realmente uma subcategoria (tem parent_id)
        $subcategoria = $repo->find($subcategoriaId);
        if (!$subcategoria || !$subcategoria->isSubcategoria()) {
            $errors['subcategoria_id'] = 'A categoria selecionada não é uma subcategoria.';
            return null;
        }

        // Verificar se pertence à categoria pai informada
        if ($categoriaId !== null && (int) $subcategoria->parent_id !== $categoriaId) {
            $errors['subcategoria_id'] = 'Subcategoria não pertence à categoria selecionada.';
            return null;
        }

        return $subcategoriaId;
    }

    /**
     * Valida que a conta pertence ao usuário.
     *
     * @param int|null $id ID da conta
     * @param int $userId ID do usuário
     * @param array &$errors Array de erros para append
     * @return int|null ID validado ou null
     */
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

        $exists = Meta::where('id', $id)
            ->where('user_id', $userId)
            ->exists();

        if ($exists) {
            return $id;
        }

        $errors['meta_id'] = 'Meta invalida.';
        return null;
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

        if (!$ehTransferencia && $tipo !== 'receita') {
            $errors['meta_id'] = 'Somente receitas e transferencias podem ser vinculadas a uma meta.';
        }
    }
}
