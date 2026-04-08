<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases;

use PHPUnit\Framework\TestCase;

class UseCaseDependencyCompositionGuardTest extends TestCase
{
    public function testModernizedUseCasesDoNotInstantiateDependenciesInlineInConstructors(): void
    {
        $files = [
            'Application/UseCases/Lancamentos/CreateLancamentoUseCase.php',
            'Application/UseCases/Lancamentos/UpdateLancamentoUseCase.php',
            'Application/UseCases/Lancamentos/UpdateTransferenciaUseCase.php',
            'Application/UseCases/Lancamentos/ToggleLancamentoPagoUseCase.php',
            'Application/UseCases/Lancamentos/CreateTransferenciaUseCase.php',
            'Application/UseCases/Lancamentos/DeleteLancamentoUseCase.php',
            'Application/UseCases/Lancamentos/BulkDeleteLancamentosUseCase.php',
            'Application/UseCases/Orcamentos/SaveOrcamentoUseCase.php',
            'Application/UseCases/Orcamentos/GetOrcamentoSugestoesUseCase.php',
            'Application/UseCases/Orcamentos/GetOrcamentosListUseCase.php',
            'Application/UseCases/Orcamentos/DeleteOrcamentoUseCase.php',
            'Application/UseCases/Orcamentos/CopyOrcamentosMesUseCase.php',
            'Application/UseCases/Orcamentos/BulkSaveOrcamentosUseCase.php',
            'Application/UseCases/Orcamentos/ApplyOrcamentoSugestoesUseCase.php',
            'Application/UseCases/Metas/UpdateMetaUseCase.php',
            'Application/UseCases/Metas/GetMetaTemplatesUseCase.php',
            'Application/UseCases/Metas/GetMetasListUseCase.php',
            'Application/UseCases/Metas/DeleteMetaUseCase.php',
            'Application/UseCases/Metas/CreateMetaUseCase.php',
            'Application/UseCases/Metas/AddMetaAporteUseCase.php',
            'Application/UseCases/Financas/GetFinanceiroTransactionsUseCase.php',
            'Application/UseCases/Financas/GetFinanceiroOptionsUseCase.php',
            'Application/UseCases/Financas/GetFinanceiroMetricsUseCase.php',
            'Application/UseCases/Financas/GetFinancasResumoUseCase.php',
            'Application/UseCases/Financas/GetFinancasInsightsUseCase.php',
        ];

        foreach ($files as $filePath) {
            $content = (string) file_get_contents($filePath);

            $this->assertDoesNotMatchRegularExpression(
                '/function\s+__construct\s*\((?:(?!\)\s*\{).)*=\s*new\s+[\\\w]+/s',
                $content,
                "Construtor não deve usar default inline com new: {$filePath}"
            );

            $this->assertDoesNotMatchRegularExpression(
                '/function\s+__construct\s*\([^)]*\)\s*\{[\s\S]*?\?\?\s*new\s+[\\\w]+/s',
                $content,
                "Construtor não deve montar dependência inline com ?? new: {$filePath}"
            );

            $this->assertDoesNotMatchRegularExpression(
                '/function\s+__construct\s*\([^)]*\)\s*\{[\s\S]*?resolveOrNew\s*\([\s\S]*?fn\s*\(\)\s*:\s*[\\\w]+\s*=>\s*new\s+[\\\w]+/s',
                $content,
                "Construtor não deve montar dependência inline com factory manual: {$filePath}"
            );
        }
    }
}
