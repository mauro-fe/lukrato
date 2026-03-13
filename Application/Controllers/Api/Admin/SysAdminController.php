<?php

namespace Application\Controllers\Api\Admin;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Usuario;
use Application\Models\AssinaturaUsuario;
use Application\Models\Endereco;
use Application\Models\Plano;
use Application\Services\Infrastructure\MaintenanceService;
use Application\Services\Infrastructure\LogService;
use Application\Enums\LogCategory;
use Application\Enums\LogLevel;
use Application\Validators\PasswordStrengthValidator;
use Carbon\Carbon;
use Exception;

class SysAdminController extends BaseController
{
    /**
     * POST /api/sysadmin/maintenance
     * Ativar/desativar modo manutenção
     */
    public function toggleMaintenance(): void
    {
        $this->requireAuthApi();

        try {
            $payload = $this->getRequestPayload();
            $action = $payload['action'] ?? 'toggle'; // 'activate', 'deactivate', 'toggle'
            $reason = trim($payload['reason'] ?? '');
            $estimatedMinutes = isset($payload['estimated_minutes']) ? (int) $payload['estimated_minutes'] : null;

            if ($action === 'toggle') {
                $action = MaintenanceService::isActive() ? 'deactivate' : 'activate';
            }

            if ($action === 'activate') {
                MaintenanceService::activate($reason, $estimatedMinutes);
                Response::json([
                    'success' => true,
                    'active' => true,
                    'message' => 'Modo manutenção ativado com sucesso.',
                    'data' => MaintenanceService::getData(),
                ]);
            } else {
                MaintenanceService::deactivate();
                Response::json([
                    'success' => true,
                    'active' => false,
                    'message' => 'Modo manutenção desativado. Sistema online.',
                ]);
            }
        } catch (\Throwable $e) {
            Response::json(['success' => false, 'message' => 'Erro: ' . $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/sysadmin/maintenance
     * Status atual do modo manutenção
     */
    public function maintenanceStatus(): void
    {
        $this->requireAuthApi();

        Response::json([
            'success' => true,
            'active' => MaintenanceService::isActive(),
            'data' => MaintenanceService::getData(),
        ]);
    }
    /**
     * POST /api/sysadmin/grant-access
     * Liberar acesso PRO temporário para um usuário
     */
    public function grantAccess(): void
    {
        $this->requireAuthApi();

        try {
            $user = \Application\Lib\Auth::user();

            // Verificar se é SysAdmin
            if (!$user || (int)$user->is_admin !== 1) {
                Response::error('Acesso negado. Apenas administradores podem executar esta ação.', 403);
                return;
            }

            $payload = $this->getRequestPayload();
            $userIdOrEmail = $payload['userId'] ?? null;
            $days = (int)($payload['days'] ?? 0);
            $planType = $payload['planType'] ?? 'pro';

            // Validações
            if (empty($userIdOrEmail)) {
                Response::error('Email ou ID do usuário é obrigatório', 400);
                return;
            }

            if ($days < 1) {
                Response::error('Número de dias inválido', 400);
                return;
            }

            if (!in_array($planType, ['pro', 'ultra'], true)) {
                Response::error('Tipo de plano inválido. Use "pro" ou "ultra".', 400);
                return;
            }

            // Resolver plano pelo código
            $plano = Plano::where('code', $planType)->first();
            if (!$plano) {
                Response::error('Plano não encontrado no sistema', 500);
                return;
            }

            // Buscar usuário por ID ou email
            $targetUser = is_numeric($userIdOrEmail)
                ? Usuario::find($userIdOrEmail)
                : Usuario::where('email', $userIdOrEmail)->first();

            if (!$targetUser) {
                Response::error('Usuário não encontrado', 404);
                return;
            }

            // Verificar se já tem assinatura ativa
            $existingSubscription = AssinaturaUsuario::where('user_id', $targetUser->id)
                ->whereIn('status', [AssinaturaUsuario::ST_ACTIVE, AssinaturaUsuario::ST_CANCELED])
                ->where('renova_em', '>', Carbon::now())
                ->first();

            $expiresAt = Carbon::now()->addDays($days);

            $planLabel = strtoupper($planType);

            if ($existingSubscription) {
                // Atualizar assinatura existente
                $existingSubscription->plano_id = $plano->id;
                $existingSubscription->status = AssinaturaUsuario::ST_ACTIVE;
                $existingSubscription->renova_em = $expiresAt;
                $existingSubscription->cancelada_em = null;
                $existingSubscription->save();
            } else {
                // Criar nova assinatura
                AssinaturaUsuario::create([
                    'user_id' => $targetUser->id,
                    'plano_id' => $plano->id,
                    'gateway' => 'admin',
                    'status' => AssinaturaUsuario::ST_ACTIVE,
                    'renova_em' => $expiresAt,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'version' => 1,
                ]);
            }

            // Log da ação
            error_log("🎁 [SYSADMIN] Acesso {$planLabel} liberado por {$user->nome} (ID {$user->id}) para {$targetUser->nome} (ID {$targetUser->id}) por {$days} dias");

            Response::success([
                'userId' => $targetUser->id,
                'userName' => $targetUser->nome,
                'userEmail' => $targetUser->email,
                'planType' => $planType,
                'planName' => $plano->nome,
                'days' => $days,
                'expiresAt' => $expiresAt->format('d/m/Y H:i'),
            ], "Acesso {$planLabel} liberado com sucesso");
        } catch (Exception $e) {
            error_log("🚨 [SYSADMIN] Erro ao liberar acesso: " . $e->getMessage());
            Response::error('Erro ao processar solicitação: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/sysadmin/revoke-access
     * Remover acesso premium de um usuário
     */
    public function revokeAccess(): void
    {
        $this->requireAuthApi();

        try {
            $user = \Application\Lib\Auth::user();

            // Verificar se é SysAdmin
            if (!$user || (int)$user->is_admin !== 1) {
                Response::error('Acesso negado. Apenas administradores podem executar esta ação.', 403);
                return;
            }

            $payload = $this->getRequestPayload();
            $userIdOrEmail = $payload['userId'] ?? null;

            // Validações
            if (empty($userIdOrEmail)) {
                Response::error('Email ou ID do usuário é obrigatório', 400);
                return;
            }

            // Buscar usuário por ID ou email
            $targetUser = is_numeric($userIdOrEmail)
                ? Usuario::find($userIdOrEmail)
                : Usuario::where('email', $userIdOrEmail)->first();

            if (!$targetUser) {
                Response::error('Usuário não encontrado', 404);
                return;
            }

            // Buscar assinaturas ativas
            $activeSubscriptions = AssinaturaUsuario::where('user_id', $targetUser->id)
                ->whereIn('status', [AssinaturaUsuario::ST_ACTIVE])
                ->get();

            if ($activeSubscriptions->isEmpty()) {
                Response::error('Usuário não possui assinatura premium ativa', 400);
                return;
            }

            // Cancelar todas as assinaturas ativas
            foreach ($activeSubscriptions as $subscription) {
                $subscription->status = AssinaturaUsuario::ST_CANCELED;
                $subscription->cancelada_em = Carbon::now();
                $subscription->renova_em = Carbon::now(); // Expira imediatamente
                $subscription->save();
            }

            // Log da ação
            error_log("🚫 [SYSADMIN] Acesso premium removido por {$user->nome} (ID {$user->id}) de {$targetUser->nome} (ID {$targetUser->id})");

            Response::success([
                'userId' => $targetUser->id,
                'userName' => $targetUser->nome,
                'userEmail' => $targetUser->email,
                'subscriptionsCanceled' => $activeSubscriptions->count(),
            ], 'Acesso premium removido com sucesso');
        } catch (Exception $e) {
            error_log("🚨 [SYSADMIN] Erro ao remover acesso: " . $e->getMessage());
            Response::error('Erro ao processar solicitação: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/sysadmin/users
     * Listar usuários com filtros e paginação
     */
    public function listUsers(): void
    {
        $this->requireAuthApi();

        try {
            $user = \Application\Lib\Auth::user();

            // Verificar se é SysAdmin
            if (!$user || $user->is_admin != 1) {
                Response::error('Acesso negado', 403);
                return;
            }

            $query = $_GET['query'] ?? '';
            $status = $_GET['status'] ?? '';
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = min(100, max(1, (int)($_GET['perPage'] ?? 10)));
            $offset = ($page - 1) * $perPage;

            $usuarios = Usuario::query();

            if ($query) {
                $searchTerm = "%{$query}%";
                $usuarios = $usuarios->where(function ($q) use ($query, $searchTerm) {
                    $q->where('nome', 'LIKE', $searchTerm)
                        ->orWhere('email', 'LIKE', $searchTerm)
                        ->orWhere('support_code', 'LIKE', $searchTerm);
                    if (is_numeric($query)) {
                        $q->orWhere('id', (int)$query);
                    }
                });
            }

            if ($status === 'admin') {
                $usuarios = $usuarios->where('is_admin', 1);
            } elseif ($status === 'user') {
                $usuarios = $usuarios->where('is_admin', 0);
            }

            // Filtro por plano (pro/free) — requer isPro() via Eloquent relationship
            $planFilter = $_GET['plan'] ?? '';

            if ($planFilter === 'pro' || $planFilter === 'free') {
                // Quando filtrando por plano, buscar todos para filtrar em PHP
                // (isPro() depende de relationship, não pode ser feito em SQL)
                $allUsers = $usuarios->orderByDesc('id')
                    ->with(['assinaturaAtiva.plano'])
                    ->get();

                // Mapear e filtrar por plano
                $mapped = $allUsers->map(function ($u) {
                    return [
                        'id' => $u->id,
                        'support_code' => $u->support_code,
                        'nome' => $u->nome,
                        'email' => $u->email,
                        'avatar' => $u->avatar ? rtrim(BASE_URL, '/') . '/' . $u->avatar : '',
                        'is_admin' => $u->is_admin,
                        'is_pro' => $u->isPro(),
                        'plano_nome' => $u->isPro() ? 'Pro' : 'Free',
                        'email_verified' => $u->email_verified_at !== null,
                        'created_at' => $u->created_at,
                    ];
                });

                if ($planFilter === 'pro') {
                    $mapped = $mapped->filter(fn($u) => $u['is_pro'] === true)->values();
                } else {
                    $mapped = $mapped->filter(fn($u) => $u['is_pro'] === false)->values();
                }

                $total = $mapped->count();
                // Paginar o resultado filtrado manualmente
                $mapped = $mapped->slice($offset, $perPage)->values();
            } else {
                // Sem filtro de plano — paginação normal no SQL
                $total = $usuarios->count();
                $usersList = $usuarios->orderByDesc('id')
                    ->limit($perPage)
                    ->offset($offset)
                    ->with(['assinaturaAtiva.plano'])
                    ->get();

                $mapped = $usersList->map(function ($u) {
                    return [
                        'id' => $u->id,
                        'support_code' => $u->support_code,
                        'nome' => $u->nome,
                        'email' => $u->email,
                        'avatar' => $u->avatar ? rtrim(BASE_URL, '/') . '/' . $u->avatar : '',
                        'is_admin' => $u->is_admin,
                        'is_pro' => $u->isPro(),
                        'plano_nome' => $u->isPro() ? 'Pro' : 'Free',
                        'email_verified' => $u->email_verified_at !== null,
                        'created_at' => $u->created_at,
                    ];
                });
            }

            Response::success([
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => (int) ceil($total / $perPage),
                'users' => $mapped,
            ]);
        } catch (Exception $e) {
            error_log("🚨 [SYSADMIN] Erro ao listar usuários: " . $e->getMessage());
            Response::error('Erro ao buscar usuários: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/sysadmin/users/{id}
     * Obter dados de um usuário específico
     */
    public function getUser(int $id): void
    {
        $this->requireAuthApi();

        try {
            $user = \Application\Lib\Auth::user();

            // Verificar se é SysAdmin
            if (!$user || $user->is_admin != 1) {
                Response::error('Acesso negado', 403);
                return;
            }

            $targetUser = Usuario::find($id);

            if (!$targetUser) {
                Response::error('Usuário não encontrado', 404);
                return;
            }

            // Buscar assinatura ativa
            $subscription = AssinaturaUsuario::where('user_id', $targetUser->id)
                ->whereIn('status', [AssinaturaUsuario::ST_ACTIVE, AssinaturaUsuario::ST_CANCELED])
                ->orderByDesc('renova_em')
                ->first();

            // Buscar endereço principal do usuário
            $endereco = Endereco::where('user_id', $targetUser->id)->first();

            // Buscar nome do plano
            $planoNome = null;
            if ($subscription && $subscription->plano_id) {
                $plano = Plano::find($subscription->plano_id);
                $planoNome = $plano ? $plano->nome : ($subscription->plano_id == 1 ? 'Free' : ($subscription->plano_id == 2 ? 'Pro' : 'Plano ' . $subscription->plano_id));
            }

            Response::success([
                'id' => $targetUser->id,
                'nome' => $targetUser->nome,
                'email' => $targetUser->email,
                'avatar' => $targetUser->avatar ? rtrim(BASE_URL, '/') . '/' . $targetUser->avatar : '',
                'is_admin' => $targetUser->is_admin,
                'data_nascimento' => $targetUser->data_nascimento?->format('Y-m-d'),
                'created_at' => $targetUser->created_at,
                'endereco' => $endereco ? [
                    'cep' => $endereco->cep,
                    'rua' => $endereco->rua,
                    'numero' => $endereco->numero,
                    'complemento' => $endereco->complemento,
                    'bairro' => $endereco->bairro,
                    'cidade' => $endereco->cidade,
                    'estado' => $endereco->estado,
                ] : null,
                'subscription' => $subscription ? [
                    'status' => $subscription->status,
                    'plano_id' => $subscription->plano_id,
                    'plano_nome' => $planoNome,
                    'renova_em' => $subscription->renova_em,
                    'gateway' => $subscription->gateway,
                ] : null,
            ]);
        } catch (Exception $e) {
            error_log("🚨 [SYSADMIN] Erro ao buscar usuário: " . $e->getMessage());
            Response::error('Erro ao buscar usuário: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/sysadmin/users/{id}
     * Atualizar dados de um usuário
     */
    public function updateUser(int $id): void
    {
        $this->requireAuthApi();

        try {
            $user = \Application\Lib\Auth::user();

            // Verificar se é SysAdmin
            if (!$user || $user->is_admin != 1) {
                Response::error('Acesso negado', 403);
                return;
            }

            $targetUser = Usuario::find($id);

            if (!$targetUser) {
                Response::error('Usuário não encontrado', 404);
                return;
            }

            $payload = $this->getRequestPayload();

            // Campos permitidos para atualização
            $allowedFields = ['nome', 'email', 'is_admin', 'data_nascimento'];
            $updateData = [];

            foreach ($allowedFields as $field) {
                if (isset($payload[$field])) {
                    $updateData[$field] = $payload[$field];
                }
            }

            // Validações
            if (isset($updateData['email'])) {
                $updateData['email'] = trim($updateData['email']);
                if (!filter_var($updateData['email'], FILTER_VALIDATE_EMAIL)) {
                    Response::error('Email inválido', 400);
                    return;
                }
                // Verificar se email já existe em outro usuário
                $existingUser = Usuario::where('email', $updateData['email'])
                    ->where('id', '!=', $id)
                    ->first();
                if ($existingUser) {
                    Response::error('Este email já está em uso por outro usuário', 400);
                    return;
                }
            }

            if (isset($updateData['nome'])) {
                $updateData['nome'] = trim($updateData['nome']);
                if (strlen($updateData['nome']) < 2) {
                    Response::error('Nome deve ter pelo menos 2 caracteres', 400);
                    return;
                }
            }

            if (isset($updateData['is_admin'])) {
                $updateData['is_admin'] = (int) $updateData['is_admin'];
                // Não permitir que o usuário remova seu próprio status de admin
                if ($targetUser->id === $user->id && $updateData['is_admin'] === 0) {
                    Response::error('Você não pode remover seu próprio status de administrador', 400);
                    return;
                }
            }

            if (empty($updateData)) {
                Response::error('Nenhum campo para atualizar', 400);
                return;
            }

            // Atualizar senha se fornecida
            if (!empty($payload['senha'])) {
                $senha = trim($payload['senha']);
                $passwordErrors = PasswordStrengthValidator::validate($senha);
                if (!empty($passwordErrors)) {
                    Response::error(implode(' ', $passwordErrors), 400);
                    return;
                }
                $targetUser->senha = $senha; // O mutator fará o hash
            }

            // Aplicar atualizações
            foreach ($updateData as $field => $value) {
                $targetUser->$field = $value;
            }
            $targetUser->save();

            // Log da ação
            error_log("✏️ [SYSADMIN] Usuário {$targetUser->id} atualizado por {$user->nome} (ID {$user->id})");

            Response::success([
                'id' => $targetUser->id,
                'nome' => $targetUser->nome,
                'email' => $targetUser->email,
                'is_admin' => $targetUser->is_admin,
            ], 'Usuário atualizado com sucesso');
        } catch (Exception $e) {
            error_log("🚨 [SYSADMIN] Erro ao atualizar usuário: " . $e->getMessage());
            Response::error('Erro ao atualizar usuário: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/sysadmin/users/{id}
     * Excluir um usuário
     */
    public function deleteUser(int $id): void
    {
        $this->requireAuthApi();

        try {
            $user = \Application\Lib\Auth::user();

            // Verificar se é SysAdmin
            if (!$user || $user->is_admin != 1) {
                Response::error('Acesso negado', 403);
                return;
            }

            $targetUser = Usuario::find($id);

            if (!$targetUser) {
                Response::error('Usuário não encontrado', 404);
                return;
            }

            // Não permitir que o usuário exclua a si mesmo
            if ($targetUser->id === $user->id) {
                Response::error('Você não pode excluir sua própria conta', 400);
                return;
            }

            $userName = $targetUser->nome;
            $userEmail = $targetUser->email;

            // Soft delete
            $targetUser->delete();

            // Log da ação
            error_log("🗑️ [SYSADMIN] Usuário {$id} ({$userEmail}) excluído por {$user->nome} (ID {$user->id})");

            Response::success([
                'id' => $id,
                'nome' => $userName,
                'email' => $userEmail,
            ], 'Usuário excluído com sucesso');
        } catch (Exception $e) {
            error_log("🚨 [SYSADMIN] Erro ao excluir usuário: " . $e->getMessage());
            Response::error('Erro ao excluir usuário: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/sysadmin/stats
     * Obter estatísticas do sistema
     */
    public function getStats(): void
    {
        $this->requireAuthApi();

        try {
            $user = \Application\Lib\Auth::user();

            // Verificar se é SysAdmin
            if (!$user || $user->is_admin != 1) {
                Response::error('Acesso negado', 403);
                return;
            }

            $now = Carbon::now();
            $thirtyDaysAgo = $now->copy()->subDays(30);
            $sevenDaysAgo = $now->copy()->subDays(7);

            // Total de usuários
            $totalUsers = Usuario::count();

            // Usuários PRO ativos (plano_id = 2)
            $proUsers = AssinaturaUsuario::where('status', AssinaturaUsuario::ST_ACTIVE)
                ->where('renova_em', '>', $now)
                ->where('plano_id', 2) // Apenas plano PRO
                ->distinct('user_id')
                ->count('user_id');

            // Usuários gratuitos
            $freeUsers = max(0, $totalUsers - $proUsers);

            // Taxa de conversão
            $conversionRate = $totalUsers > 0 ? round(($proUsers / $totalUsers) * 100, 1) : 0;

            // Novos usuários hoje (usando range explícito para garantir timezone correto)
            $todayStart = Carbon::today()->toDateTimeString();
            $todayEnd = Carbon::today()->endOfDay()->toDateTimeString();
            $newToday = Usuario::whereBetween('created_at', [$todayStart, $todayEnd])->count();

            // Novos usuários esta semana
            $weekStart = $sevenDaysAgo->copy()->startOfDay()->toDateTimeString();
            $newThisWeek = Usuario::where('created_at', '>=', $weekStart)->count();

            // Novos usuários este mês
            $monthStart = $thirtyDaysAgo->copy()->startOfDay()->toDateTimeString();
            $newThisMonth = Usuario::where('created_at', '>=', $monthStart)->count();

            // Usuários por dia (últimos 30 dias)
            // Buscar timestamps e agrupar em PHP usando Carbon para garantir timezone correto
            $usersCreatedAt = Usuario::where('created_at', '>=', $thirtyDaysAgo->startOfDay())
                ->pluck('created_at')
                ->toArray();

            $usersByDay = [];
            foreach ($usersCreatedAt as $createdAt) {
                // Carbon converte automaticamente para o timezone do PHP (America/Sao_Paulo)
                $date = Carbon::parse($createdAt)->format('Y-m-d');
                if (!isset($usersByDay[$date])) {
                    $usersByDay[$date] = 0;
                }
                $usersByDay[$date]++;
            }

            // Preencher dias sem cadastros
            $usersByDayFilled = [];
            for ($i = 29; $i >= 0; $i--) {
                $date = $now->copy()->subDays($i)->format('Y-m-d');
                $usersByDayFilled[$date] = $usersByDay[$date] ?? 0;
            }

            // Assinaturas por gateway
            $subscriptionsByGateway = AssinaturaUsuario::selectRaw('gateway, COUNT(*) as count')
                ->where('status', AssinaturaUsuario::ST_ACTIVE)
                ->where('renova_em', '>', $now)
                ->groupBy('gateway')
                ->get()
                ->pluck('count', 'gateway')
                ->toArray();

            // Se não houver dados, adicionar valor padrão
            if (empty($subscriptionsByGateway)) {
                $subscriptionsByGateway = ['Interno' => $proUsers];
            }

            // Assinaturas por status
            $subscriptionsByStatus = AssinaturaUsuario::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray();

            // Usuários ativos (logaram nos últimos 30 dias)
            $activeUsers = Usuario::where('updated_at', '>=', $thirtyDaysAgo)->count();

            // Crescimento percentual (comparado ao mês anterior)
            $sixtyDaysAgo = $now->copy()->subDays(60);
            $previousMonthUsers = Usuario::where('created_at', '>=', $sixtyDaysAgo)
                ->where('created_at', '<', $thirtyDaysAgo)
                ->count();

            $growthRate = $previousMonthUsers > 0
                ? round((($newThisMonth - $previousMonthUsers) / $previousMonthUsers) * 100, 1)
                : ($newThisMonth > 0 ? 100 : 0);

            $responseData = [
                'overview' => [
                    'totalUsers' => (int)$totalUsers,
                    'proUsers' => (int)$proUsers,
                    'freeUsers' => (int)$freeUsers,
                    'conversionRate' => (float)$conversionRate,
                    'activeUsers' => (int)$activeUsers,
                ],
                'newUsers' => [
                    'today' => (int)$newToday,
                    'thisWeek' => (int)$newThisWeek,
                    'thisMonth' => (int)$newThisMonth,
                    'growthRate' => (float)$growthRate,
                ],
                'charts' => [
                    'usersByDay' => $usersByDayFilled,
                    'subscriptionsByGateway' => $subscriptionsByGateway,
                    'subscriptionsByStatus' => $subscriptionsByStatus,
                    'userDistribution' => [
                        'PRO' => (int)$proUsers,
                        'Gratuito' => (int)$freeUsers,
                    ],
                ],
            ];

            error_log("✅ [SYSADMIN] Stats gerados: PRO={$proUsers}, Free={$freeUsers}, Total={$totalUsers}");

            Response::success($responseData);
        } catch (Exception $e) {
            LogService::captureException($e, LogCategory::GENERAL, [
                'action' => 'sysadmin_stats',
            ]);
            Response::error('Erro ao buscar estatísticas: ' . $e->getMessage(), 500);
        }
    }

    // ========================================================================
    // ERROR LOGS — Visualização e gestão dos logs de erro
    // ========================================================================

    /**
     * GET /api/sysadmin/error-logs
     * Listar logs de erro com filtros e paginação
     *
     * Query params: level, category, resolved, user_id, search, date_from, date_to, page, per_page
     */
    public function errorLogs(): void
    {
        $this->requireAuthApi();

        $resolvedParam = $_GET['resolved'] ?? null;
        $resolved = null;
        if ($resolvedParam !== null && $resolvedParam !== '') {
            $resolved = in_array($resolvedParam, ['1', 'true', 'yes'], true);
        }

        $filters = [
            'level'    => $_GET['level'] ?? null,
            'category' => $_GET['category'] ?? null,
            'resolved' => $resolved,
            'user_id'  => isset($_GET['user_id']) ? (int) $_GET['user_id'] : null,
            'search'   => $_GET['search'] ?? null,
            'from'     => $_GET['date_from'] ?? null,
            'to'       => $_GET['date_to'] ?? null,
        ];

        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(10, (int) ($_GET['per_page'] ?? 25)));

        try {
            $result = LogService::query($filters, $page, $perPage);
            Response::success($result);
        } catch (Exception $e) {
            Response::error('Erro ao buscar logs: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/sysadmin/error-logs/summary
     * Dashboard resumido de errros (últimas 24h por padrão)
     *
     * Query params: hours (default 24)
     */
    public function errorLogsSummary(): void
    {
        $this->requireAuthApi();

        $hours = max(1, (int) ($_GET['hours'] ?? 24));

        try {
            $summary = LogService::summary($hours);

            // Adicionar metadados dos enums para o frontend
            $summary['filters'] = [
                'levels'     => array_map(fn(LogLevel $l) => [
                    'value' => $l->value,
                    'label' => $l->label(),
                    'color' => $l->color(),
                    'icon'  => $l->icon(),
                ], LogLevel::cases()),
                'categories' => array_map(fn(LogCategory $c) => [
                    'value' => $c->value,
                    'label' => $c->label(),
                ], LogCategory::cases()),
            ];

            Response::success($summary);
        } catch (Exception $e) {
            Response::error('Erro ao buscar resumo: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/sysadmin/error-logs/{id}/resolve
     * Marcar um log como resolvido
     */
    public function resolveErrorLog(int $id): void
    {
        $this->requireAuthApi();

        try {
            $userId = \Application\Lib\Auth::id();
            $resolved = LogService::resolve($id, $userId);

            if ($resolved) {
                Response::json(['success' => true, 'message' => 'Log marcado como resolvido']);
            } else {
                Response::error('Log não encontrado', 404);
            }
        } catch (Exception $e) {
            Response::error('Erro ao resolver log: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/sysadmin/error-logs/cleanup
     * Limpar logs resolvidos antigos
     *
     * Query params: days (default 30)
     */
    public function cleanupErrorLogs(): void
    {
        $this->requireAuthApi();

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $days = max(7, (int) ($input['days'] ?? $_GET['days'] ?? 30));

        try {
            $deleted = LogService::cleanup($days);
            Response::json([
                'success' => true,
                'message' => "{$deleted} log(s) antigo(s) removido(s)",
                'count'   => $deleted,
            ]);
        } catch (Exception $e) {
            Response::error('Erro ao limpar logs: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/sysadmin/clear-cache
     * Limpar cache de arquivos e Redis
     */
    public function clearCache(): void
    {
        $this->requireAuthApi();

        try {
            $user = \Application\Lib\Auth::user();
            if (!$user || $user->is_admin != 1) {
                Response::error('Acesso negado', 403);
                return;
            }

            $results = [];

            // 1. Limpar cache de arquivos
            $cacheDir = BASE_PATH . '/storage/cache';
            if (is_dir($cacheDir)) {
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($cacheDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::CHILD_FIRST
                );

                $count = 0;
                foreach ($files as $fileinfo) {
                    $action = $fileinfo->isDir() ? 'rmdir' : 'unlink';
                    if (@$action($fileinfo->getRealPath())) {
                        $count++;
                    }
                }
                $results['files'] = $count;
            } else {
                $results['files'] = 0;
            }

            // 2. Limpar cache Redis
            try {
                $cache = new \Application\Services\Infrastructure\CacheService();
                if ($cache->isEnabled()) {
                    $cache->flush();
                    $results['redis'] = true;
                } else {
                    $results['redis'] = false;
                }
            } catch (\Throwable $e) {
                $results['redis'] = false;
            }

            Response::json([
                'success' => true,
                'message' => "Cache limpo com sucesso ({$results['files']} arquivo(s) removido(s))",
                'details' => $results,
            ]);
        } catch (Exception $e) {
            Response::error('Erro ao limpar cache: ' . $e->getMessage(), 500);
        }
    }
}
