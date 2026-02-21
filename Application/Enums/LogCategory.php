<?php

declare(strict_types=1);

namespace Application\Enums;

/**
 * Categorias de contexto dos error logs — identifica a área do sistema.
 */
enum LogCategory: string
{
    case PAYMENT       = 'payment';
    case FATURA        = 'fatura';
    case AGENDAMENTO   = 'agendamento';
    case LANCAMENTO    = 'lancamento';
    case AUTH          = 'auth';
    case WEBHOOK       = 'webhook';
    case SUBSCRIPTION  = 'subscription';
    case GAMIFICATION  = 'gamification';
    case CARTAO        = 'cartao';
    case EXPORT        = 'export';
    case MIGRATION     = 'migration';
    case NOTIFICATION  = 'notification';
    case GENERAL       = 'general';

    public function label(): string
    {
        return match ($this) {
            self::PAYMENT      => 'Pagamentos',
            self::FATURA       => 'Faturas',
            self::AGENDAMENTO  => 'Agendamentos',
            self::LANCAMENTO   => 'Lançamentos',
            self::AUTH         => 'Autenticação',
            self::WEBHOOK      => 'Webhooks',
            self::SUBSCRIPTION => 'Assinaturas',
            self::GAMIFICATION => 'Gamificação',
            self::CARTAO       => 'Cartões',
            self::EXPORT       => 'Exportação',
            self::MIGRATION    => 'Migrações',
            self::NOTIFICATION => 'Notificações',
            self::GENERAL      => 'Geral',
        };
    }
}
