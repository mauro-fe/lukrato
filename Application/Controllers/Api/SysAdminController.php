<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Usuario;
use Application\Models\AssinaturaUsuario;
use Application\Models\Endereco;
use Application\Models\Plano;
use Application\Services\MaintenanceService;
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

            // Validações
            if (empty($userIdOrEmail)) {
                Response::error('Email ou ID do usuário é obrigatório', 400);
                return;
            }

            if ($days < 1) {
                Response::error('Número de dias inválido', 400);
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

            if ($existingSubscription) {
                // Atualizar assinatura existente
                $existingSubscription->plano_id = 2; // Garantir que é plano PRO
                $existingSubscription->status = AssinaturaUsuario::ST_ACTIVE;
                $existingSubscription->renova_em = $expiresAt;
                $existingSubscription->cancelada_em = null;
                $existingSubscription->save();
            } else {
                // Criar nova assinatura
                AssinaturaUsuario::create([
                    'user_id' => $targetUser->id,
                    'plano_id' => 2, // ID do plano PRO
                    'gateway' => 'admin',
                    'status' => AssinaturaUsuario::ST_ACTIVE,
                    'renova_em' => $expiresAt,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'version' => 1,
                ]);
            }

            // Log da ação
            error_log("🎁 [SYSADMIN] Acesso PRO liberado por {$user->nome} (ID {$user->id}) para {$targetUser->nome} (ID {$targetUser->id}) por {$days} dias");

            Response::success([
                'userId' => $targetUser->id,
                'userName' => $targetUser->nome,
                'userEmail' => $targetUser->email,
                'days' => $days,
                'expiresAt' => $expiresAt->format('d/m/Y H:i'),
            ], 'Acesso PRO liberado com sucesso');
        } catch (Exception $e) {
            error_log("🚨 [SYSADMIN] Erro ao liberar acesso: " . $e->getMessage());
            Response::error('Erro ao processar solicitação: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/sysadmin/revoke-access
     * Remover acesso PRO de um usuário
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
                Response::error('Usuário não possui assinatura PRO ativa', 400);
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
            error_log("🚫 [SYSADMIN] Acesso PRO removido por {$user->nome} (ID {$user->id}) de {$targetUser->nome} (ID {$targetUser->id})");

            Response::success([
                'userId' => $targetUser->id,
                'userName' => $targetUser->nome,
                'userEmail' => $targetUser->email,
                'subscriptionsCanceled' => $activeSubscriptions->count(),
            ], 'Acesso PRO removido com sucesso');
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

            // Filtro por plano (pro/free)
            $planFilter = $_GET['plan'] ?? '';

            $total = $usuarios->count();
            $usersList = $usuarios->orderByDesc('id')
                ->limit($perPage)
                ->offset($offset)
                ->with(['assinaturaAtiva.plano'])
                ->get();

            // Mapear dados com info de plano
            $mapped = $usersList->map(function ($u) {
                return [
                    'id' => $u->id,
                    'support_code' => $u->support_code,
                    'nome' => $u->nome,
                    'email' => $u->email,
                    'is_admin' => $u->is_admin,
                    'is_pro' => $u->isPro(),
                    'plano_nome' => $u->isPro() ? 'Pro' : 'Free',
                    'email_verified' => $u->email_verified_at !== null,
                    'created_at' => $u->created_at,
                ];
            });

            // Filtrar por plano após o mapeamento (precisa calcular isPro primeiro)
            if ($planFilter === 'pro') {
                $mapped = $mapped->filter(fn($u) => $u['is_pro'] === true)->values();
                $total = $mapped->count();
            } elseif ($planFilter === 'free') {
                $mapped = $mapped->filter(fn($u) => $u['is_pro'] === false)->values();
                $total = $mapped->count();
            }

            Response::success([
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => ceil($total / $perPage),
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
                if (strlen($senha) < 6) {
                    Response::error('Senha deve ter pelo menos 6 caracteres', 400);
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

            // Novos usuários hoje
            $newToday = Usuario::whereDate('created_at', $now->toDateString())->count();

            // Novos usuários esta semana
            $newThisWeek = Usuario::where('created_at', '>=', $sevenDaysAgo)->count();

            // Novos usuários este mês
            $newThisMonth = Usuario::where('created_at', '>=', $thirtyDaysAgo)->count();

            // Usuários por dia (últimos 30 dias)
            $usersByDay = Usuario::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->pluck('count', 'date')
                ->toArray();

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
            error_log("🚨 [SYSADMIN] Erro ao buscar estatísticas: " . $e->getMessage());
            error_log($e->getTraceAsString());
            Response::error('Erro ao buscar estatísticas: ' . $e->getMessage(), 500);
        }
    }
}
