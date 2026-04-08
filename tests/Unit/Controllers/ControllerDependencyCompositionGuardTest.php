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
            'Application/Controllers/Auth/LoginController.php',
            'Application/Controllers/Auth/ForgotPasswordController.php',
            'Application/Controllers/Api/Cartao/CartoesController.php',
            'Application/Controllers/SysAdmin/BlogController.php',
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
            'Application/Controllers/Api/Conta/ContasController.php' => [
                'ContaService',
                'PlanLimitService',
            ],
            'Application/Controllers/Api/Fatura/FaturasController.php' => [
                'FaturaService',
            ],
            'Application/Controllers/Api/Notification/NotificacaoController.php' => [
                'CartaoCreditoService',
                'CartaoFaturaService',
                'NotificationInboxService',
            ],
            'Application/Controllers/Api/Notification/CampaignController.php' => [
                'NotificationService',
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

        $reportController = (string) file_get_contents('Application/Controllers/Api/Report/RelatoriosController.php');

        $this->assertStringNotContainsString(
            'fn(): ReportApiWorkflowService => new ReportApiWorkflowService(',
            $reportController,
            'RelatoriosController não deve montar ReportApiWorkflowService inline.'
        );
        $this->assertStringNotContainsString(
            '?\\Application\\Services\\Report\\ReportService $reportService',
            $reportController,
            'RelatoriosController não deve depender diretamente de ReportService.'
        );
        $this->assertStringNotContainsString(
            '?\\Application\\Builders\\ReportExportBuilder $exportBuilder',
            $reportController,
            'RelatoriosController não deve depender diretamente de ReportExportBuilder.'
        );
        $this->assertStringNotContainsString(
            '?\\Application\\Services\\Report\\PdfExportService $pdfExport',
            $reportController,
            'RelatoriosController não deve depender diretamente de PdfExportService.'
        );
        $this->assertStringNotContainsString(
            '?\\Application\\Services\\Report\\ExcelExportService $excelExport',
            $reportController,
            'RelatoriosController não deve depender diretamente de ExcelExportService.'
        );
        $this->assertStringNotContainsString(
            '?\\Application\\Services\\Report\\InsightsService $insightsService',
            $reportController,
            'RelatoriosController não deve depender diretamente de InsightsService.'
        );
        $this->assertStringNotContainsString(
            '?\\Application\\Services\\Report\\ComparativesService $comparativesService',
            $reportController,
            'RelatoriosController não deve depender diretamente de ComparativesService.'
        );

        $contasController = (string) file_get_contents('Application/Controllers/Api/Conta/ContasController.php');
        $this->assertStringNotContainsString(
            'fn(): ContaApiWorkflowService => new ContaApiWorkflowService(',
            $contasController,
            'ContasController não deve montar ContaApiWorkflowService inline.'
        );

        $faturasController = (string) file_get_contents('Application/Controllers/Api/Fatura/FaturasController.php');
        $this->assertStringNotContainsString(
            'fn(): FaturaApiWorkflowService => new FaturaApiWorkflowService(',
            $faturasController,
            'FaturasController não deve montar FaturaApiWorkflowService inline.'
        );

        $notificacaoController = (string) file_get_contents('Application/Controllers/Api/Notification/NotificacaoController.php');
        $this->assertStringNotContainsString(
            'fn(): NotificationInboxService => new NotificationInboxService(',
            $notificacaoController,
            'NotificacaoController não deve montar NotificationInboxService inline.'
        );
        $this->assertStringNotContainsString(
            'fn(): NotificationApiWorkflowService => new NotificationApiWorkflowService(',
            $notificacaoController,
            'NotificacaoController não deve montar NotificationApiWorkflowService inline.'
        );

        $campaignController = (string) file_get_contents('Application/Controllers/Api/Notification/CampaignController.php');
        $this->assertStringNotContainsString(
            'fn(): CampaignApiWorkflowService => new CampaignApiWorkflowService(',
            $campaignController,
            'CampaignController não deve montar CampaignApiWorkflowService inline.'
        );

        $blogController = (string) file_get_contents('Application/Controllers/SysAdmin/BlogController.php');
        $this->assertStringNotContainsString(
            'fn(): BlogAdminWorkflowService => new BlogAdminWorkflowService(',
            $blogController,
            'BlogController não deve montar BlogAdminWorkflowService inline.'
        );
        $this->assertStringNotContainsString(
            '?BlogPostRepository $repo',
            $blogController,
            'BlogController não deve depender diretamente de BlogPostRepository.'
        );

        $registroController = (string) file_get_contents('Application/Controllers/Auth/RegistroController.php');
        $this->assertStringNotContainsString(
            'fn(): AuthService => new AuthService($this->request, $this->cache)',
            $registroController,
            'RegistroController não deve montar AuthService inline.'
        );
        $this->assertStringNotContainsString(
            'fn(): RegistrationResponseHandler => new RegistrationResponseHandler($this->request)',
            $registroController,
            'RegistroController não deve montar RegistrationResponseHandler inline.'
        );
        $this->assertStringNotContainsString(
            'fn(): TurnstileService => new TurnstileService($this->cache)',
            $registroController,
            'RegistroController não deve montar TurnstileService inline.'
        );

        $loginController = (string) file_get_contents('Application/Controllers/Auth/LoginController.php');
        $this->assertStringNotContainsString(
            'fn(): AuthService => new AuthService($this->request, $this->cache)',
            $loginController,
            'LoginController não deve montar AuthService inline.'
        );
        $this->assertStringNotContainsString(
            'fn(): TurnstileService => new TurnstileService($this->cache)',
            $loginController,
            'LoginController não deve montar TurnstileService inline.'
        );

        $forgotPasswordController = (string) file_get_contents('Application/Controllers/Auth/ForgotPasswordController.php');
        $this->assertStringNotContainsString(
            'function (): PasswordResetService {',
            $forgotPasswordController,
            'ForgotPasswordController não deve montar PasswordResetService inline.'
        );
        $this->assertStringNotContainsString(
            'fn(): MailPasswordResetNotification => new MailPasswordResetNotification($mailService)',
            $forgotPasswordController,
            'ForgotPasswordController não deve montar MailPasswordResetNotification inline.'
        );

        $perfilController = (string) file_get_contents('Application/Controllers/Api/Perfil/PerfilController.php');
        $this->assertStringNotContainsString(
            'PerfilControllerFactory::buildDependencies()',
            $perfilController,
            'PerfilController não deve recorrer à PerfilControllerFactory.'
        );
        $this->assertStringNotContainsString(
            '?PerfilService $perfilService',
            $perfilController,
            'PerfilController não deve depender diretamente de PerfilService.'
        );
        $this->assertStringNotContainsString(
            '?PerfilValidator $validator',
            $perfilController,
            'PerfilController não deve depender diretamente de PerfilValidator.'
        );
        $this->assertStringNotContainsString(
            '?PerfilAvatarService $avatarService',
            $perfilController,
            'PerfilController não deve depender diretamente de PerfilAvatarService.'
        );
        $this->assertStringNotContainsString(
            'fn(): PerfilApiWorkflowService => new PerfilApiWorkflowService(',
            $perfilController,
            'PerfilController não deve montar PerfilApiWorkflowService inline.'
        );
    }
}
