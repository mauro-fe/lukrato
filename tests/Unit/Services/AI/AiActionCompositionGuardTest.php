<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use PHPUnit\Framework\TestCase;

class AiActionCompositionGuardTest extends TestCase
{
    public function testAiActionsDoNotInstantiateDependenciesInline(): void
    {
        $actionRegistry = (string) file_get_contents('Application/Services/AI/Actions/ActionRegistry.php');
        $createLancamentoAction = (string) file_get_contents('Application/Services/AI/Actions/CreateLancamentoAction.php');
        $createMetaAction = (string) file_get_contents('Application/Services/AI/Actions/CreateMetaAction.php');
        $createOrcamentoAction = (string) file_get_contents('Application/Services/AI/Actions/CreateOrcamentoAction.php');
        $createContaAction = (string) file_get_contents('Application/Services/AI/Actions/CreateContaAction.php');
        $createCategoriaAction = (string) file_get_contents('Application/Services/AI/Actions/CreateCategoriaAction.php');
        $createSubcategoriaAction = (string) file_get_contents('Application/Services/AI/Actions/CreateSubcategoriaAction.php');
        $payFaturaAction = (string) file_get_contents('Application/Services/AI/Actions/PayFaturaAction.php');

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+CreateLancamentoAction\s*\(/',
            $actionRegistry,
            'ActionRegistry não deve instanciar CreateLancamentoAction diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+CreateMetaAction\s*\(/',
            $actionRegistry,
            'ActionRegistry não deve instanciar CreateMetaAction diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+CreateOrcamentoAction\s*\(/',
            $actionRegistry,
            'ActionRegistry não deve instanciar CreateOrcamentoAction diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+CreateCategoriaAction\s*\(/',
            $actionRegistry,
            'ActionRegistry não deve instanciar CreateCategoriaAction diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+CreateSubcategoriaAction\s*\(/',
            $actionRegistry,
            'ActionRegistry não deve instanciar CreateSubcategoriaAction diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+CreateContaAction\s*\(/',
            $actionRegistry,
            'ActionRegistry não deve instanciar CreateContaAction diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+PayFaturaAction\s*\(/',
            $actionRegistry,
            'ActionRegistry não deve instanciar PayFaturaAction diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+LancamentoCreationService\s*\(/',
            $createLancamentoAction,
            'CreateLancamentoAction não deve instanciar LancamentoCreationService diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+MetaService\s*\(/',
            $createMetaAction,
            'CreateMetaAction não deve instanciar MetaService diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+OrcamentoService\s*\(/',
            $createOrcamentoAction,
            'CreateOrcamentoAction não deve instanciar OrcamentoService diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+ContaService\s*\(/',
            $createContaAction,
            'CreateContaAction não deve instanciar ContaService diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+CategoriaRepository\s*\(/',
            $createCategoriaAction,
            'CreateCategoriaAction não deve instanciar CategoriaRepository diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+SubcategoriaService\s*\(/',
            $createSubcategoriaAction,
            'CreateSubcategoriaAction não deve instanciar SubcategoriaService diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+CartaoFaturaService\s*\(/',
            $payFaturaAction,
            'PayFaturaAction não deve instanciar CartaoFaturaService diretamente.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+AchievementService\s*\(/',
            $payFaturaAction,
            'PayFaturaAction não deve instanciar AchievementService diretamente.'
        );
    }
}
