<?php

declare(strict_types=1);

namespace Application\Services\Financeiro;

use Application\Services\Orcamentos\OrcamentoService as OrcamentoDomainService;

/**
 * @deprecated Use Application\Services\Orcamentos\OrcamentoService.
 * Mantido para compatibilidade durante a migração.
 */
class OrcamentoService extends OrcamentoDomainService
{
}
