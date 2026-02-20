<?php

namespace Application\Enums;

/**
 * Enum PaymentMethod - Formas de pagamento e recebimento
 * 
 * Para DESPESAS: pix, cartao_credito, cartao_debito, dinheiro, boleto
 * Para RECEITAS: pix, deposito, dinheiro, transferencia, estorno_cartao
 */
enum PaymentMethod: string
{
    // Formas de pagamento (despesas)
    case PIX = 'pix';
    case CARTAO_CREDITO = 'cartao_credito';
    case CARTAO_DEBITO = 'cartao_debito';
    case DINHEIRO = 'dinheiro';
    case BOLETO = 'boleto';

        // Formas de recebimento (receitas)
    case DEPOSITO = 'deposito';
    case TRANSFERENCIA = 'transferencia';
    case ESTORNO_CARTAO = 'estorno_cartao';

    /**
     * Retorna label amigável para exibição
     */
    public function label(): string
    {
        return match ($this) {
            self::PIX => 'PIX',
            self::CARTAO_CREDITO => 'Cartão de Crédito',
            self::CARTAO_DEBITO => 'Cartão de Débito',
            self::DINHEIRO => 'Dinheiro',
            self::BOLETO => 'Boleto',
            self::DEPOSITO => 'Depósito',
            self::TRANSFERENCIA => 'Transferência',
            self::ESTORNO_CARTAO => 'Estorno/Cashback',
        };
    }

    /**
     * Retorna ícone Lucide
     */
    public function icon(): string
    {
        return match ($this) {
            self::PIX => 'qr-code',
            self::CARTAO_CREDITO => 'credit-card',
            self::CARTAO_DEBITO => 'credit-card',
            self::DINHEIRO => 'banknote',
            self::BOLETO => 'scan-line',
            self::DEPOSITO => 'landmark',
            self::TRANSFERENCIA => 'arrow-left-right',
            self::ESTORNO_CARTAO => 'rotate-ccw',
        };
    }

    /**
     * Retorna cor do badge
     */
    public function color(): string
    {
        return match ($this) {
            self::PIX => '#32BCAD',
            self::CARTAO_CREDITO => '#8B5CF6',
            self::CARTAO_DEBITO => '#3B82F6',
            self::DINHEIRO => '#10B981',
            self::BOLETO => '#F59E0B',
            self::DEPOSITO => '#6366F1',
            self::TRANSFERENCIA => '#EC4899',
            self::ESTORNO_CARTAO => '#14B8A6',
        };
    }

    /**
     * Retorna opções para despesas
     */
    public static function forDespesa(): array
    {
        return [
            self::PIX,
            self::CARTAO_CREDITO,
            self::CARTAO_DEBITO,
            self::DINHEIRO,
            self::BOLETO,
        ];
    }

    /**
     * Retorna opções para receitas
     */
    public static function forReceita(): array
    {
        return [
            self::PIX,
            self::DEPOSITO,
            self::DINHEIRO,
            self::TRANSFERENCIA,
            self::ESTORNO_CARTAO,
        ];
    }

    /**
     * Verifica se afeta o cartão de crédito
     */
    public function afetaCartao(): bool
    {
        return in_array($this, [self::CARTAO_CREDITO, self::ESTORNO_CARTAO]);
    }
}
