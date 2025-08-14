<?php

/**
 * Controlador Base para Área Administrativa
 *
 * Este controlador abstrato fornece funcionalidades comuns para todos os
 * controladores da área administrativa, incluindo autenticação, autorização,
 * validação de propriedade de recursos e renderização de views.
 */

namespace Application\Controllers\Admin;

use Application\Models\Admin;
use Application\Controllers\BaseController;
use Application\Services\LogService;
use Application\Lib\Auth;

abstract class AdminController extends BaseController
{
    protected Admin $admin;

    public function __construct()
    {
        parent::__construct();

        // NOTA: A autenticação e validação CSRF são responsabilidade dos middlewares
        // Se este construtor é executado, o usuário já está autenticado
        $this->initializeAdmin();
    }

    // ============================================================================
    // INICIALIZAÇÃO E CONFIGURAÇÃO
    // ============================================================================

    /**
     * Inicializa e carrega os dados do administrador autenticado
     */
    protected function initializeAdmin(): void
    {
        $this->loadAdminFromSession();
        $this->validateAdminAccount();
    }

    /**
     * Carrega dados básicos do admin da sessão
     */
    protected function loadAdminFromSession(): void
    {
        $this->adminId = Auth::id();
        $this->adminUsername = Auth::username();
    }

    /**
     * Valida se a conta do admin existe e está ativa
     */
    protected function validateAdminAccount(): void
    {
        if ($this->isInvalidSession()) {
            $this->handleInvalidSession();
            return;
        }

        $this->admin = Auth::user();

        if ($this->isInactiveAccount()) {
            $this->handleInactiveAccount();
        }
    }

    /**
     * Verifica se a sessão é inválida
     */
    protected function isInvalidSession(): bool
    {
        return is_null($this->adminId);
    }

    /**
     * Verifica se a conta está inativa
     */
    protected function isInactiveAccount(): bool
    {
        return !$this->admin;
    }

    /**
     * Trata sessão inválida
     */
    protected function handleInvalidSession(): void
    {
        Auth::logout();
        $this->setError('Sessão inválida ou expirada. Faça login novamente.');
        $this->redirect('admin/login');
    }

    /**
     * Trata conta inativa
     */
    protected function handleInactiveAccount(): void
    {
        Auth::logout();
        $this->setError('Administrador não encontrado ou conta desativada.');
        $this->redirect('admin/login');
    }

    // ============================================================================
    // CONTROLE DE PROPRIEDADE DE RECURSOS
    // ============================================================================

    /**
     * Verifica se o admin é proprietário de um recurso
     *
     * @param object|null $resource O objeto do recurso
     * @param string $adminField Nome do campo que armazena o ID do admin
     * @return bool True se o admin é o proprietário
     */
    protected function checkOwnership(?object $resource, string $adminField = 'admin_id'): bool
    {
        if (!$this->isValidResource($resource)) {
            return false;
        }

        $resourceOwnerId = (int) ($resource->$adminField ?? -1);
        $currentAdminId = (int) $this->adminId;

        return $resourceOwnerId === $currentAdminId;
    }

    /**
     * Exige que o admin seja proprietário do recurso
     *
     * @param object|null $resource O recurso a ser verificado
     * @param string $errorMessage Mensagem de erro personalizada
     */
    protected function requireOwnership(?object $resource, string $errorMessage = 'Acesso negado'): void
    {
        if (!$this->checkOwnership($resource)) {
            $this->handleUnauthorizedAccess($resource, $errorMessage);
        }
    }

    /**
     * Verifica se o recurso é válido
     */
    protected function isValidResource(?object $resource): bool
    {
        return $resource && is_object($resource);
    }

    /**
     * Trata tentativa de acesso não autorizado
     */
    protected function handleUnauthorizedAccess(?object $resource, string $errorMessage): void
    {
        $this->logUnauthorizedAccess($resource);
        $this->setError($errorMessage);
        $this->redirectToAdminHome();
    }

    /**
     * Registra tentativa de acesso não autorizado
     */
    protected function logUnauthorizedAccess(?object $resource): void
    {
        LogService::error('Tentativa de acesso não autorizado', [
            'admin_id' => $this->adminId,
            'admin_username' => $this->adminUsername,
            'resource_type' => $resource ? get_class($resource) : 'N/A',
            'resource_id' => $resource->id ?? 'N/A',
            'ip' => $this->request->ip()
        ]);
    }

    // ============================================================================
    // SISTEMA DE PERMISSÕES
    // ============================================================================

    /**
     * Verifica se o admin possui uma permissão específica
     *
     * @param string $permission Nome da permissão
     * @return bool True se possui a permissão
     */
    protected function hasPermission(string $permission): bool
    {
        if (!$this->admin) {
            return false;
        }

        return $this->admin->hasPermission($permission);
    }

    /**
     * Exige que o admin possua uma permissão específica
     *
     * @param string $permission Nome da permissão exigida
     */
    protected function requirePermission(string $permission): void
    {
        if (!$this->hasPermission($permission)) {
            $this->handleInsufficientPermission($permission);
        }
    }

    /**
     * Trata permissão insuficiente
     */
    protected function handleInsufficientPermission(string $permission): void
    {
        $this->logInsufficientPermission($permission);
        $this->setError('Permissão insuficiente para realizar esta ação.');
        $this->redirectToAdminHome();
    }

    /**
     * Registra tentativa com permissão insuficiente
     */
    protected function logInsufficientPermission(string $permission): void
    {
        LogService::warning('Permissão insuficiente', [
            'admin_id' => $this->adminId,
            'admin_username' => $this->adminUsername,
            'required_permission' => $permission,
            'ip' => $this->request->ip()
        ]);
    }

    // ============================================================================
    // RENDERIZAÇÃO E DADOS DE VIEW
    // ============================================================================

    /**
     * Renderiza view do admin com dados compartilhados
     *
     * @param string $viewPath Caminho da view
     * @param array $data Dados específicos da view
     */
    protected function renderAdmin(string $viewPath, array $data = []): void
    {
        $sharedData = $this->getSharedAdminData();
        $mergedData = array_merge($sharedData, $data);

        parent::renderAdmin($viewPath, $mergedData);
    }

    /**
     * Obtém dados compartilhados do admin para as views
     *
     * @return array Dados compartilhados
     */
    protected function getSharedAdminData(): array
    {
        return [
            'admin_data' => $this->admin,
            'admin_id' => $this->admin->id,
            'admin_username' => $this->admin->username,
            'nome_clinica' => $this->admin->nome_clinica,
            'slug_clinica' => $this->admin->slug_clinica ?? '',
            'error' => $this->getError(),
            'success' => $this->getSuccess()
        ];
    }

    /**
     * Dados padrão para views (método público para compatibilidade)
     *
     * @return array Dados básicos da view
     */
    public function viewData(): array
    {
        return [
            'admin' => $this->admin,
            'csrf_token' => csrf_token()
        ];
    }

    // ============================================================================
    // NAVEGAÇÃO E REDIRECIONAMENTOS
    // ============================================================================

    /**
     * Redireciona para a home do admin logado
     */
    protected function redirectToAdminHome(): void
    {
        $currentUrl = $_SERVER['REQUEST_URI'] ?? '';

        $username = !empty($this->adminUsername)
            ? $this->adminUsername
            : (!empty($_SESSION['admin']['username']) ? $_SESSION['admin']['username'] : null);

        if (!$username) {
            // Se não conseguiu obter o username, volta pro login
            $this->redirect('admin/login');
            return;
        }

        $targetUrl = 'admin/' . $username . '/dashboard';

        if ($this->isCurrentUrl($currentUrl, $targetUrl)) {
            $this->redirect('admin/login');
        } else {
            $this->redirect($targetUrl);
        }
    }

    /**
     * Verifica se a URL atual é a mesma do redirecionamento
     */
    protected function isCurrentUrl(string $currentUrl, string $targetUrl): bool
    {
        return strpos($currentUrl, $targetUrl) !== false;
    }

    protected function userId(): int
    {
        return (int)($_SESSION['admin_id'] ?? \Application\Lib\Auth::id() ?? 0);
    }
    protected function username(): string
    {
        return (string)($_SESSION['admin_username'] ?? \Application\Lib\Auth::username() ?? '');
    }
}
