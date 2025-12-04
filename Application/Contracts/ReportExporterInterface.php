<?php

declare(strict_types=1);

namespace Application\Contracts;

use Application\DTO\ReportData;

interface ReportExporterInterface
{
    public function export(ReportData $data): string;
}

