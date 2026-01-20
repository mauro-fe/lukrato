<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Usuario;
use Application\Models\AssinaturaUsuario;
use Carbon\Carbon;
use Exception;

class SysAdminController extends BaseController
{
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
            if ($user?->is_admin !== 1) {
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
                        ->orWhere('email', 'LIKE', $searchTerm);
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

            $total = $usuarios->count();
            $usersList = $usuarios->orderByDesc('id')
                ->limit($perPage)
                ->offset($offset)
                ->get(['id', 'nome', 'email', 'is_admin', 'created_at']);

            Response::success([
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => ceil($total / $perPage),
                'users' => $usersList,
            ]);
        } catch (Exception $e) {
            error_log("🚨 [SYSADMIN] Erro ao listar usuários: " . $e->getMessage());
            Response::error('Erro ao buscar usuários: ' . $e->getMessage(), 500);
        }
    }
}
