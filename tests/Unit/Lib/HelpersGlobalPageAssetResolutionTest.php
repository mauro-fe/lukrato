<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Application\Support\Admin\AdminModuleRegistry;
use PHPUnit\Framework\TestCase;

class HelpersGlobalPageAssetResolutionTest extends TestCase
{
    public function testResolvePageJsViteEntryUsesAdminModuleRegistry(): void
    {
        $this->assertTrue(function_exists('resolvePageJsViteEntry'));

        $this->assertSame('admin/lancamentos/index.js', resolvePageJsViteEntry('admin-lancamentos-index'));
        $this->assertSame('admin/configuracoes/index.js', resolvePageJsViteEntry('admin-configuracoes-index'));
        $this->assertSame('admin/importacoes/index.js', resolvePageJsViteEntry('admin-importacoes-index'));
        $this->assertSame('admin/importacoes/configuracoes.js', resolvePageJsViteEntry('admin-importacoes-configuracoes-index'));
        $this->assertSame('admin/importacoes/historico.js', resolvePageJsViteEntry('admin-importacoes-historico-index'));
        $this->assertSame('admin/sysadmin/cupons.js', resolvePageJsViteEntry('admin-sysadmin-cupons'));
        $this->assertNull(resolvePageJsViteEntry('admin-pagina-inexistente'));
    }

    public function testResolvePageCssViteEntryUsesRegistryAndLegacyFallbacks(): void
    {
        $this->assertTrue(function_exists('resolvePageCssViteEntry'));

        $this->assertSame('auth-login-style', resolvePageCssViteEntry('admin-auth-login'));
        $this->assertSame('auth-shared-style', resolvePageCssViteEntry('admin-auth-reset-password'));
        $this->assertSame('site-legal', resolvePageCssViteEntry('site-legal-privacy'));
        $this->assertNull(resolvePageCssViteEntry('admin-dashboard-index'));
    }

    public function testConfiguracoesViewIdResolvesToConfiguracoesBundle(): void
    {
        $resolvedViewId = AdminModuleRegistry::resolvePageJsViewId('admin-configuracoes-index');

        $this->assertSame('admin-configuracoes-index', $resolvedViewId);
        $this->assertSame('admin/configuracoes/index.js', resolvePageJsViteEntry($resolvedViewId));
    }
}
