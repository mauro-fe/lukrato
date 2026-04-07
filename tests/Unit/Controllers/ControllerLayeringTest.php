<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;

class ControllerLayeringTest extends TestCase
{
    public function testApiNamespaceControllersExtendApiController(): void
    {
        foreach ($this->controllerFilesIn('Application/Controllers/Api') as $filePath) {
            $extends = $this->extractExtendedClassShortName($filePath);

            if ($extends === null) {
                continue;
            }

            $this->assertSame(
                'ApiController',
                $extends,
                "Controller API deve estender ApiController: {$filePath}"
            );
        }
    }

    public function testWebNamespaceControllersExtendWebController(): void
    {
        $webNamespaces = [
            'Application/Controllers/Admin',
            'Application/Controllers/Auth',
            'Application/Controllers/Site',
            'Application/Controllers/Settings',
        ];

        foreach ($webNamespaces as $namespacePath) {
            foreach ($this->controllerFilesIn($namespacePath) as $filePath) {
                $extends = $this->extractExtendedClassShortName($filePath);

                if ($extends === null) {
                    continue;
                }

                $this->assertSame(
                    'WebController',
                    $extends,
                    "Controller Web deve estender WebController: {$filePath}"
                );
            }
        }
    }

    public function testSysAdminApiAndViewControllersFollowNamingConvention(): void
    {
        foreach ($this->controllerFilesIn('Application/Controllers/SysAdmin') as $filePath) {
            $extends = $this->extractExtendedClassShortName($filePath);

            if ($extends === null) {
                continue;
            }

            $fileName = basename($filePath);

            if (str_contains($fileName, 'ApiController')) {
                $this->assertSame(
                    'ApiController',
                    $extends,
                    "Controller SysAdmin de API deve estender ApiController: {$filePath}"
                );
            }

            if (str_contains($fileName, 'ViewController')) {
                $this->assertSame(
                    'WebController',
                    $extends,
                    "Controller SysAdmin de view deve estender WebController: {$filePath}"
                );
            }
        }
    }

    public function testTopLevelControllersFollowExpectedLayering(): void
    {
        $expectations = [
            'Application/Controllers/PremiumController.php' => 'ApiController',
            'Application/Controllers/GamificationController.php' => 'WebController',
        ];

        foreach ($expectations as $filePath => $expectedParent) {
            $extends = $this->extractExtendedClassShortName($filePath);

            $this->assertSame(
                $expectedParent,
                $extends,
                "Controller fora de namespace esperado: {$filePath}"
            );
        }
    }

    public function testControllerConcernTraitsDoNotDependOnParentCalls(): void
    {
        $traitFiles = [
            'Application/Controllers/Concerns/HandlesAdminLayoutData.php',
            'Application/Controllers/Concerns/HandlesApiResponses.php',
            'Application/Controllers/Concerns/HandlesAuthGuards.php',
            'Application/Controllers/Concerns/HandlesRequestUtilities.php',
            'Application/Controllers/Concerns/HandlesWebPresentation.php',
        ];

        foreach ($traitFiles as $filePath) {
            $content = (string) file_get_contents($filePath);

            $this->assertStringNotContainsString(
                'parent::',
                $content,
                "Trait deve ser autocontido, sem chamadas parent:: {$filePath}"
            );
        }
    }

    public function testModernizedConstructorsDoNotUseInlineInstantiationDefaults(): void
    {
        $files = [
            'Application/Controllers/PremiumController.php',
            'Application/Controllers/SysAdmin/AiLogsApiController.php',
            'Application/Controllers/Api/Cartao/CartoesController.php',
            'Application/Controllers/Api/Metas/MetasController.php',
            'Application/Controllers/Api/Orcamentos/OrcamentosController.php',
            'Application/Controllers/Api/Financas/ResumoController.php',
            'Application/Controllers/Api/Financas/MetricsController.php',
            'Application/Services/Cartao/CartaoApiWorkflowService.php',
            'Application/UseCases/Financas/GetFinancasResumoUseCase.php',
            'Application/UseCases/Financas/GetFinancasInsightsUseCase.php',
            'Application/UseCases/Financas/GetFinanceiroMetricsUseCase.php',
            'Application/UseCases/Financas/GetFinanceiroTransactionsUseCase.php',
            'Application/UseCases/Financas/GetFinanceiroOptionsUseCase.php',
            'Application/UseCases/Metas/CreateMetaUseCase.php',
            'Application/UseCases/Metas/UpdateMetaUseCase.php',
            'Application/UseCases/Metas/AddMetaAporteUseCase.php',
            'Application/UseCases/Metas/DeleteMetaUseCase.php',
            'Application/UseCases/Metas/GetMetaTemplatesUseCase.php',
            'Application/UseCases/Metas/GetMetasListUseCase.php',
            'Application/UseCases/Orcamentos/SaveOrcamentoUseCase.php',
            'Application/UseCases/Orcamentos/BulkSaveOrcamentosUseCase.php',
            'Application/UseCases/Orcamentos/DeleteOrcamentoUseCase.php',
            'Application/UseCases/Orcamentos/GetOrcamentoSugestoesUseCase.php',
            'Application/UseCases/Orcamentos/ApplyOrcamentoSugestoesUseCase.php',
            'Application/UseCases/Orcamentos/CopyOrcamentosMesUseCase.php',
            'Application/UseCases/Orcamentos/GetOrcamentosListUseCase.php',
        ];

        foreach ($files as $filePath) {
            $this->assertConstructorDoesNotUseInlineInstantiationDefault($filePath);
        }
    }

    /**
     * @return list<string>
     */
    private function controllerFilesIn(string $basePath): array
    {
        if (!is_dir($basePath)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($basePath));

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }

            $path = str_replace('\\', '/', $fileInfo->getPathname());

            if (!str_ends_with($path, '.php')) {
                continue;
            }

            if (str_ends_with($path, 'BaseController.php')) {
                continue;
            }

            if (str_ends_with($path, 'ApiController.php') || str_ends_with($path, 'WebController.php')) {
                continue;
            }

            $files[] = $path;
        }

        sort($files);

        return $files;
    }

    private function extractExtendedClassShortName(string $filePath): ?string
    {
        $content = (string) file_get_contents($filePath);

        if (preg_match('/\btrait\s+\w+/', $content) === 1) {
            return null;
        }

        if (preg_match('/\bclass\s+\w+\s+extends\s+([\\\\\w]+)/', $content, $matches) !== 1) {
            return null;
        }

        $extendedClass = $matches[1];
        $parts = explode('\\', $extendedClass);

        return end($parts) ?: null;
    }

    private function assertConstructorDoesNotUseInlineInstantiationDefault(string $filePath): void
    {
        $content = (string) file_get_contents($filePath);

        $this->assertDoesNotMatchRegularExpression(
            '/function\s+__construct\s*\((?:(?!\)\s*\{).)*=\s*new\s+[\\\w]+/s',
            $content,
            "Construtor não deve instanciar dependência por default: {$filePath}"
        );
    }
}
