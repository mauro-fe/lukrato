<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Admin;

use Application\Support\Admin\AdminModuleRegistry;
use PHPUnit\Framework\TestCase;

class AdminModuleRegistryTest extends TestCase
{
    public function testCoreModulesExposeRequiredKeys(): void
    {
        $modules = AdminModuleRegistry::all();
        $required = ['key', 'label', 'icon', 'group', 'route', 'menu', 'view_prefix', 'vite_entry', 'css_entry', 'breadcrumbs'];

        foreach (['dashboard', 'lancamentos', 'contas', 'cartoes', 'faturas', 'importacoes', 'relatorios', 'categorias', 'perfil', 'configuracoes', 'super_admin'] as $moduleKey) {
            $this->assertArrayHasKey($moduleKey, $modules, "Modulo ausente no registro: {$moduleKey}");

            $module = $modules[$moduleKey];
            foreach ($required as $requiredKey) {
                $this->assertArrayHasKey($requiredKey, $module, "Chave obrigatoria ausente em {$moduleKey}: {$requiredKey}");
            }
        }
    }

    public function testInferMenuFromViewPathUsesConfiguredPrefixes(): void
    {
        $this->assertSame('faturas', AdminModuleRegistry::inferMenuFromViewPath('admin/parcelamentos/index'));
        $this->assertSame('super_admin', AdminModuleRegistry::inferMenuFromViewPath('admin/sysadmin/ai'));
        $this->assertSame('configuracoes', AdminModuleRegistry::inferMenuFromViewPath('admin/configuracoes/index'));
        $this->assertSame('perfil', AdminModuleRegistry::inferMenuFromViewPath('admin/perfil/index'));
        $this->assertSame('importacoes', AdminModuleRegistry::inferMenuFromViewPath('admin/importacoes/configuracoes/index'));
        $this->assertNull(AdminModuleRegistry::inferMenuFromViewPath('site/home'));
    }

    public function testResolveMenuByViewContextResolvesRegisteredViews(): void
    {
        $this->assertSame('configuracoes', AdminModuleRegistry::resolveMenuByViewContext('admin/configuracoes/index'));
        $this->assertSame('perfil', AdminModuleRegistry::resolveMenuByViewContext('admin/perfil/index'));
        $this->assertSame('super_admin', AdminModuleRegistry::resolveMenuByViewContext('admin/sysadmin/cupons'));
        $this->assertSame('importacoes', AdminModuleRegistry::resolveMenuByViewContext('admin/importacoes/historico/index'));
    }

    public function testResolveViteEntryByViewIdUsesRegisteredViews(): void
    {
        $this->assertSame('admin/lancamentos/index.js', AdminModuleRegistry::resolveViteEntryByViewId('admin-lancamentos-index'));
        $this->assertSame('admin/configuracoes/index.js', AdminModuleRegistry::resolveViteEntryByViewId('admin-configuracoes-index'));
        $this->assertSame('admin/importacoes/index.js', AdminModuleRegistry::resolveViteEntryByViewId('admin-importacoes-index'));
        $this->assertSame('admin/importacoes/configuracoes.js', AdminModuleRegistry::resolveViteEntryByViewId('admin-importacoes-configuracoes-index'));
        $this->assertSame('admin/sysadmin/ai-logs.js', AdminModuleRegistry::resolveViteEntryByViewId('admin-sysadmin-ai-logs'));
        $this->assertNull(AdminModuleRegistry::resolveViteEntryByViewId('admin-view-inexistente'));
    }

    public function testResolveCssEntryByViewIdUsesRegisteredMetadata(): void
    {
        $this->assertSame('auth-login-style', AdminModuleRegistry::resolveCssEntryByViewId('admin-auth-login'));
        $this->assertSame('auth-shared-style', AdminModuleRegistry::resolveCssEntryByViewId('admin-auth-forgot-password'));
        $this->assertNull(AdminModuleRegistry::resolveCssEntryByViewId('admin-dashboard-index'));
    }

    public function testResolveBreadcrumbsByViewContextUsesModuleDefinitions(): void
    {
        $lancamentosBreadcrumbs = AdminModuleRegistry::resolveBreadcrumbsByViewContext('admin/lancamentos/index');

        $this->assertNotSame([], $lancamentosBreadcrumbs);
        $this->assertSame(
            $lancamentosBreadcrumbs,
            AdminModuleRegistry::resolveBreadcrumbsByViewContext('admin/importacoes/index')
        );

        $this->assertSame(
            [['label' => 'Perfil', 'icon' => 'user']],
            AdminModuleRegistry::resolveBreadcrumbsByViewContext('admin/configuracoes/index')
        );

        $this->assertSame([], AdminModuleRegistry::resolveBreadcrumbsByViewContext('admin/perfil/index'));
    }

    public function testResolvePageJsViewIdUsesDedicatedPerfilAndConfiguracoesViewIds(): void
    {
        $this->assertSame('admin-configuracoes-index', AdminModuleRegistry::resolvePageJsViewId('admin-configuracoes-index'));
        $this->assertSame('admin-perfil-index', AdminModuleRegistry::resolvePageJsViewId('admin-perfil-index'));
    }

    public function testFooterVisibilityRespectsSysadminFlag(): void
    {
        $footerRegularUser = AdminModuleRegistry::footerModules(false, false);
        $regularKeys = array_column($footerRegularUser, 'key');
        $this->assertSame(['configuracoes', 'perfil'], $regularKeys);

        $footerSysAdmin = AdminModuleRegistry::footerModules(true, false);
        $sysadminKeys = array_column($footerSysAdmin, 'key');
        $this->assertSame(['configuracoes', 'perfil', 'super_admin'], $sysadminKeys);
    }

    public function testSidebarGroupsKeepExpectedNavigationStructure(): void
    {
        $groups = AdminModuleRegistry::groupedSidebarModules(false, false);
        $modules = AdminModuleRegistry::all();

        $principalGroup = (string) ($modules['dashboard']['group'] ?? '');
        $financeGroup = (string) ($modules['lancamentos']['group'] ?? '');
        $planningGroup = (string) ($modules['orcamento']['group'] ?? '');
        $analysisGroup = (string) ($modules['relatorios']['group'] ?? '');
        $organizationGroup = (string) ($modules['categorias']['group'] ?? '');
        $extrasGroup = (string) ($modules['gamification']['group'] ?? '');

        $this->assertSame(['dashboard'], array_column($groups[$principalGroup] ?? [], 'key'));
        $this->assertSame(['lancamentos', 'contas', 'cartoes', 'faturas', 'importacoes'], array_column($groups[$financeGroup] ?? [], 'key'));
        $this->assertSame(['orcamento', 'metas'], array_column($groups[$planningGroup] ?? [], 'key'));
        $this->assertSame(['relatorios'], array_column($groups[$analysisGroup] ?? [], 'key'));
        $this->assertSame(['categorias'], array_column($groups[$organizationGroup] ?? [], 'key'));
        $this->assertSame(['gamification'], array_column($groups[$extrasGroup] ?? [], 'key'));
    }
}
