<?php

declare(strict_types=1);

namespace Application\DTO;

/**
 * Objeto imutável para garantir integridade dos dados do relatório.
 */
readonly class ReportData
{
    public function __construct(
        public string $title,
        public array $headers,
        public array $rows,
        public ?string $subtitle = null,
    ) {}
}