<?php

declare(strict_types=1);

namespace Application\Enums\AI;

/**
 * Canal de origem da interação com a IA.
 * Influencia formatação da resposta e limites de tokens.
 */
enum AIChannel: string
{
    /** Interface web do sistema */
    case WEB = 'web';

    /** Mensagem via WhatsApp */
    case WHATSAPP = 'whatsapp';

    /** Chamada programática via API */
    case API = 'api';

    /** Dashboard do sysadmin */
    case ADMIN = 'admin';

    /**
     * Limite máximo de tokens de resposta por canal.
     */
    public function maxResponseTokens(): int
    {
        return match ($this) {
            self::WEB       => 1500,
            self::WHATSAPP  => 300,
            self::API       => 1500,
            self::ADMIN     => 2000,
        };
    }
}
