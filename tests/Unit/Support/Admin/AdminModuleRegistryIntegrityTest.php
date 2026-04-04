<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Admin;

use Application\Support\Admin\AdminModuleRegistry;
use PHPUnit\Framework\TestCase;

class AdminModuleRegistryIntegrityTest extends TestCase
{
    public function testCriticalAdminRoutesRegisteredInRegistryExistInRouteFiles(): void
    {
        $routesFromFiles = $this->adminGetRoutesFromFiles();
        $modules = AdminModuleRegistry::all();

        foreach (['dashboard', 'lancamentos', 'contas', 'cartoes', 'faturas', 'importacoes', 'relatorios', 'categorias', 'perfil', 'configuracoes', 'super_admin'] as $moduleKey) {
            $this->assertArrayHasKey($moduleKey, $modules);

            $route = (string) ($modules[$moduleKey]['route'] ?? '');
            $this->assertNotSame('', $route, "Route vazia para modulo {$moduleKey}");
            $this->assertContains($route, $routesFromFiles, "Rota do modulo {$moduleKey} nao encontrada em routes/admin/*.php");
        }
    }

    public function testCriticalModulesKeepViewPrefixAndViewIdsConsistent(): void
    {
        $modules = AdminModuleRegistry::all();

        foreach (['dashboard', 'lancamentos', 'contas', 'cartoes', 'faturas', 'importacoes', 'relatorios', 'categorias', 'perfil', 'configuracoes', 'super_admin'] as $moduleKey) {
            $module = $modules[$moduleKey] ?? [];

            $viewPrefix = (string) ($module['view_prefix'] ?? '');
            $viewIds = $module['view_ids'] ?? [];

            $this->assertNotSame('', $viewPrefix, "view_prefix vazio para modulo {$moduleKey}");
            $this->assertTrue(is_array($viewIds) && $viewIds !== [], "view_ids vazio para modulo {$moduleKey}");

            foreach ($viewIds as $viewId) {
                $this->assertNotNull(
                    AdminModuleRegistry::resolveViteEntryByViewId((string) $viewId),
                    "vite_entry nao resolvido para {$moduleKey} ({$viewId})"
                );
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function adminGetRoutesFromFiles(): array
    {
        $files = glob(BASE_PATH . '/routes/admin/*.php') ?: [];
        $paths = [];

        foreach ($files as $file) {
            $content = (string) file_get_contents($file);
            if ($content === '') {
                continue;
            }

            if (preg_match_all("/Router::add\\(\\s*'GET'\\s*,\\s*'\\/([^']+)'/m", $content, $matches) < 1) {
                continue;
            }

            foreach ($matches[1] as $path) {
                $trimmed = trim((string) $path);
                if ($trimmed !== '') {
                    $paths[] = $trimmed;
                }
            }
        }

        $paths = array_values(array_unique($paths));
        sort($paths);

        return $paths;
    }
}
