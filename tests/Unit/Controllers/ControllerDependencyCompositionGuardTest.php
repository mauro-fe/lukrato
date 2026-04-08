<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;

class ControllerDependencyCompositionGuardTest extends TestCase
{
    public function testControllersDoNotInstantiateOrAssembleCoreDependenciesInline(): void
    {
        $files = [
            'Application/Controllers/BaseController.php',
            'Application/Controllers/Auth/RegistroController.php',
            'Application/Controllers/Api/Cartao/CartoesController.php',
            'Application/Controllers/Api/Financas/MetricsController.php',
            'Application/Controllers/Api/Financas/ResumoController.php',
            'Application/Controllers/Api/Lancamentos/DestroyController.php',
            'Application/Controllers/Api/Lancamentos/MarcarPagoController.php',
            'Application/Controllers/Api/Lancamentos/TransactionsController.php',
            'Application/Controllers/Api/Lancamentos/UpdateController.php',
            'Application/Controllers/Api/Metas/MetasController.php',
            'Application/Controllers/Api/Orcamentos/OrcamentosController.php',
            'Application/Controllers/Api/Report/RelatoriosController.php',
            'Application/Controllers/Api/Perfil/PerfilController.php',
        ];

        foreach ($files as $filePath) {
            $content = (string) file_get_contents($filePath);

            $this->assertDoesNotMatchRegularExpression(
                '/function\s+__construct\s*\((?:(?!\)\s*\{).)*=\s*new\s+[\\\w]+/s',
                $content,
                "Controller não deve usar default inline com new: {$filePath}"
            );

            $this->assertDoesNotMatchRegularExpression(
                '/\?\?=?\s*new\s+[\\\w]+/s',
                $content,
                "Controller não deve montar dependência inline com new: {$filePath}"
            );
        }
    }

    public function testWorkflowControllersDependOnUseCasesOrWorkflowsInsteadOfLowLevelServices(): void
    {
        $dependencies = [
            'Application/Controllers/Api/Cartao/CartoesController.php' => [
                'CartaoCreditoService',
                'CartaoFaturaService',
                'PlanLimitService',
            ],
            'Application/Controllers/Api/Financas/MetricsController.php' => [
                'LancamentoRepository',
                'CategoriaRepository',
                'ContaRepository',
            ],
            'Application/Controllers/Api/Financas/ResumoController.php' => [
                'MetaService',
                'OrcamentoService',
                'DemoPreviewService',
            ],
            'Application/Controllers/Api/Lancamentos/DestroyController.php' => [
                'LancamentoRepository',
                'LancamentoDeletionService',
            ],
            'Application/Controllers/Api/Lancamentos/MarcarPagoController.php' => [
                'LancamentoRepository',
                'LancamentoStatusService',
                'ParcelamentoRepository',
            ],
            'Application/Controllers/Api/Lancamentos/TransactionsController.php' => [
                'LancamentoLimitService',
                'TransferenciaService',
                'LancamentoRepository',
                'CategoriaRepository',
                'ContaRepository',
                'MetaProgressService',
            ],
            'Application/Controllers/Api/Lancamentos/UpdateController.php' => [
                'ContaRepository',
                'MetaProgressService',
            ],
            'Application/Controllers/Api/Metas/MetasController.php' => [
                'MetaService',
                'DemoPreviewService',
            ],
            'Application/Controllers/Api/Orcamentos/OrcamentosController.php' => [
                'OrcamentoService',
                'DemoPreviewService',
            ],
        ];

        foreach ($dependencies as $filePath => $classes) {
            $content = (string) file_get_contents($filePath);

            foreach ($classes as $class) {
                $this->assertDoesNotMatchRegularExpression(
                    '/function\s+__construct\s*\([^)]*\?' . preg_quote($class, '/') . '\s+\$/s',
                    $content,
                    "Controller não deve depender diretamente de {$class}: {$filePath}"
                );
            }
        }
    }
}
