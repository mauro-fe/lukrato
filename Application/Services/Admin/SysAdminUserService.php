<?php

declare(strict_types=1);

namespace Application\Services\Admin;

use Application\Core\Exceptions\ClientErrorException;
use Application\Models\AssinaturaUsuario;
use Application\Models\Endereco;
use Application\Models\Plano;
use Application\Models\Usuario;
use Application\Services\Infrastructure\LogService;
use Application\Validators\PasswordStrengthValidator;
use Carbon\Carbon;

class SysAdminUserService
{
    /**
     * @param array<string, mixed> $payload
     * @return array{data:array<string, mixed>,message:string}
     */
    public function grantAccess(int $adminUserId, string $adminName, array $payload): array
    {
        $userIdOrEmail = $payload['userId'] ?? null;
        $days = (int) ($payload['days'] ?? 0);
        $planType = (string) ($payload['planType'] ?? 'pro');

        if (empty($userIdOrEmail)) {
            throw new ClientErrorException(400, 'Email ou ID do usuario e obrigatorio');
        }

        if ($days < 1) {
            throw new ClientErrorException(400, 'Numero de dias invalido');
        }

        if (!in_array($planType, ['pro', 'ultra'], true)) {
            throw new ClientErrorException(400, 'Tipo de plano invalido. Use "pro" ou "ultra".');
        }

        $plan = Plano::where('code', $planType)->first();
        if (!$plan) {
            throw new \RuntimeException('Plano nao encontrado no sistema');
        }

        $targetUser = $this->findUserByIdentifier($userIdOrEmail);
        if (!$targetUser) {
            throw new ClientErrorException(404, 'Usuario nao encontrado');
        }

        $existingSubscription = AssinaturaUsuario::where('user_id', $targetUser->id)
            ->whereIn('status', [AssinaturaUsuario::ST_ACTIVE, AssinaturaUsuario::ST_CANCELED])
            ->where('renova_em', '>', Carbon::now())
            ->first();

        $expiresAt = Carbon::now()->addDays($days);
        $planLabel = strtoupper($planType);

        if ($existingSubscription) {
            $existingSubscription->plano_id = $plan->id;
            $existingSubscription->status = AssinaturaUsuario::ST_ACTIVE;
            $existingSubscription->renova_em = $expiresAt;
            $existingSubscription->cancelada_em = null;
            $existingSubscription->save();
        } else {
            AssinaturaUsuario::create([
                'user_id' => $targetUser->id,
                'plano_id' => $plan->id,
                'gateway' => 'admin',
                'status' => AssinaturaUsuario::ST_ACTIVE,
                'renova_em' => $expiresAt,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'version' => 1,
            ]);
        }

        LogService::info('[SYSADMIN] Acesso premium liberado', [
            'admin_user_id' => $adminUserId,
            'admin_user_name' => $adminName,
            'target_user_id' => $targetUser->id,
            'target_user_name' => $targetUser->nome,
            'plan_type' => $planType,
            'days' => $days,
        ]);

        return [
            'data' => [
                'userId' => $targetUser->id,
                'userName' => $targetUser->nome,
                'userEmail' => $targetUser->email,
                'planType' => $planType,
                'planName' => $plan->nome,
                'days' => $days,
                'expiresAt' => $expiresAt->format('d/m/Y H:i'),
            ],
            'message' => "Acesso {$planLabel} liberado com sucesso",
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{data:array<string, mixed>,message:string}
     */
    public function revokeAccess(int $adminUserId, string $adminName, array $payload): array
    {
        $userIdOrEmail = $payload['userId'] ?? null;

        if (empty($userIdOrEmail)) {
            throw new ClientErrorException(400, 'Email ou ID do usuario e obrigatorio');
        }

        $targetUser = $this->findUserByIdentifier($userIdOrEmail);
        if (!$targetUser) {
            throw new ClientErrorException(404, 'Usuario nao encontrado');
        }

        $activeSubscriptions = AssinaturaUsuario::where('user_id', $targetUser->id)
            ->whereIn('status', [AssinaturaUsuario::ST_ACTIVE])
            ->get();

        if ($activeSubscriptions->isEmpty()) {
            throw new ClientErrorException(400, 'Usuario nao possui assinatura premium ativa');
        }

        foreach ($activeSubscriptions as $subscription) {
            $subscription->status = AssinaturaUsuario::ST_CANCELED;
            $subscription->cancelada_em = Carbon::now();
            $subscription->renova_em = Carbon::now();
            $subscription->save();
        }

        LogService::info('[SYSADMIN] Acesso premium removido', [
            'admin_user_id' => $adminUserId,
            'admin_user_name' => $adminName,
            'target_user_id' => $targetUser->id,
            'target_user_name' => $targetUser->nome,
            'subscriptions_canceled' => $activeSubscriptions->count(),
        ]);

        return [
            'data' => [
                'userId' => $targetUser->id,
                'userName' => $targetUser->nome,
                'userEmail' => $targetUser->email,
                'subscriptionsCanceled' => $activeSubscriptions->count(),
            ],
            'message' => 'Acesso premium removido com sucesso',
        ];
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function listUsers(array $query): array
    {
        $search = (string) ($query['query'] ?? '');
        $status = (string) ($query['status'] ?? '');
        $planFilter = (string) ($query['plan'] ?? '');
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($query['perPage'] ?? 10)));
        $offset = ($page - 1) * $perPage;

        $usersQuery = Usuario::query();

        if ($search !== '') {
            $searchTerm = "%{$search}%";
            $usersQuery = $usersQuery->where(function ($builder) use ($search, $searchTerm): void {
                $builder->where('nome', 'LIKE', $searchTerm)
                    ->orWhere('email', 'LIKE', $searchTerm)
                    ->orWhere('support_code', 'LIKE', $searchTerm);

                if (is_numeric($search)) {
                    $builder->orWhere('id', (int) $search);
                }
            });
        }

        if ($status === 'admin') {
            $usersQuery = $usersQuery->where('is_admin', 1);
        } elseif ($status === 'user') {
            $usersQuery = $usersQuery->where('is_admin', 0);
        }

        if ($planFilter === 'pro' || $planFilter === 'free') {
            $allUsers = $usersQuery->orderByDesc('id')
                ->with(['assinaturaAtiva.plano'])
                ->get();

            $mapped = $allUsers->map(fn($user) => $this->mapUserListItem($user));

            if ($planFilter === 'pro') {
                $mapped = $mapped->filter(static fn(array $user): bool => $user['is_pro'] === true)->values();
            } else {
                $mapped = $mapped->filter(static fn(array $user): bool => $user['is_pro'] === false)->values();
            }

            $total = $mapped->count();
            $mapped = $mapped->slice($offset, $perPage)->values();
        } else {
            $total = $usersQuery->count();
            $mapped = $usersQuery->orderByDesc('id')
                ->limit($perPage)
                ->offset($offset)
                ->with(['assinaturaAtiva.plano'])
                ->get()
                ->map(fn($user) => $this->mapUserListItem($user));
        }

        return [
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => (int) ceil($total / $perPage),
            'users' => $mapped,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getUser(int $id): array
    {
        $targetUser = Usuario::find($id);
        if (!$targetUser) {
            throw new ClientErrorException(404, 'Usuario nao encontrado');
        }

        $subscription = AssinaturaUsuario::where('user_id', $targetUser->id)
            ->whereIn('status', [AssinaturaUsuario::ST_ACTIVE, AssinaturaUsuario::ST_CANCELED])
            ->orderByDesc('renova_em')
            ->first();

        $address = Endereco::where('user_id', $targetUser->id)->first();

        return [
            'id' => $targetUser->id,
            'nome' => $targetUser->nome,
            'email' => $targetUser->email,
            'avatar' => $targetUser->avatar ? rtrim(BASE_URL, '/') . '/' . $targetUser->avatar : '',
            'is_admin' => $targetUser->is_admin,
            'data_nascimento' => $targetUser->data_nascimento?->format('Y-m-d'),
            'created_at' => $targetUser->created_at,
            'endereco' => $address ? [
                'cep' => $address->cep,
                'rua' => $address->rua,
                'numero' => $address->numero,
                'complemento' => $address->complemento,
                'bairro' => $address->bairro,
                'cidade' => $address->cidade,
                'estado' => $address->estado,
            ] : null,
            'subscription' => $subscription ? [
                'status' => $subscription->status,
                'plano_id' => $subscription->plano_id,
                'plano_nome' => $this->resolvePlanName($subscription),
                'renova_em' => $subscription->renova_em,
                'gateway' => $subscription->gateway,
            ] : null,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{data:array<string, mixed>,message:string}
     */
    public function updateUser(int $adminUserId, string $adminName, int $targetUserId, array $payload): array
    {
        $targetUser = Usuario::find($targetUserId);
        if (!$targetUser) {
            throw new ClientErrorException(404, 'Usuario nao encontrado');
        }

        $allowedFields = ['nome', 'email', 'is_admin', 'data_nascimento'];
        $updateData = [];

        foreach ($allowedFields as $field) {
            if (isset($payload[$field])) {
                $updateData[$field] = $payload[$field];
            }
        }

        if (isset($updateData['email'])) {
            $updateData['email'] = trim((string) $updateData['email']);
            if (!filter_var($updateData['email'], FILTER_VALIDATE_EMAIL)) {
                throw new ClientErrorException(400, 'Email invalido');
            }

            $normalizedEmail = mb_strtolower($updateData['email']);
            $existingUser = Usuario::where('id', '!=', $targetUserId)
                ->where(function ($query) use ($normalizedEmail) {
                    $query->whereRaw('LOWER(email) = ?', [$normalizedEmail])
                        ->orWhereRaw('LOWER(pending_email) = ?', [$normalizedEmail]);
                })
                ->first();

            if ($existingUser) {
                throw new ClientErrorException(400, 'Este email ja esta em uso por outro usuario');
            }
        }

        if (isset($updateData['nome'])) {
            $updateData['nome'] = trim((string) $updateData['nome']);
            if (strlen($updateData['nome']) < 2) {
                throw new ClientErrorException(400, 'Nome deve ter pelo menos 2 caracteres');
            }
        }

        if (isset($updateData['is_admin'])) {
            $updateData['is_admin'] = (int) $updateData['is_admin'];
            if ($targetUser->id === $adminUserId && $updateData['is_admin'] === 0) {
                throw new ClientErrorException(400, 'você nao pode remover seu proprio status de administrador');
            }
        }

        if (empty($updateData)) {
            throw new ClientErrorException(400, 'Nenhum campo para atualizar');
        }

        if (!empty($payload['senha'])) {
            $password = trim((string) $payload['senha']);
            $passwordErrors = PasswordStrengthValidator::validate($password);
            if (!empty($passwordErrors)) {
                throw new ClientErrorException(400, implode(' ', $passwordErrors));
            }

            $targetUser->senha = $password;
        }

        foreach ($updateData as $field => $value) {
            $targetUser->$field = $value;
        }

        $targetUser->save();

        LogService::info('[SYSADMIN] Usuario atualizado', [
            'admin_user_id' => $adminUserId,
            'admin_user_name' => $adminName,
            'target_user_id' => $targetUser->id,
        ]);

        return [
            'data' => [
                'id' => $targetUser->id,
                'nome' => $targetUser->nome,
                'email' => $targetUser->email,
                'is_admin' => $targetUser->is_admin,
            ],
            'message' => 'Usuario atualizado com sucesso',
        ];
    }

    /**
     * @return array{data:array<string, mixed>,message:string}
     */
    public function deleteUser(int $adminUserId, string $adminName, int $targetUserId): array
    {
        $targetUser = Usuario::find($targetUserId);
        if (!$targetUser) {
            throw new ClientErrorException(404, 'Usuario nao encontrado');
        }

        if ($targetUser->id === $adminUserId) {
            throw new ClientErrorException(400, 'você nao pode excluir sua propria conta');
        }

        $userName = $targetUser->nome;
        $userEmail = $targetUser->email;
        $targetUser->delete();

        LogService::info('[SYSADMIN] Usuario excluido', [
            'admin_user_id' => $adminUserId,
            'admin_user_name' => $adminName,
            'target_user_id' => $targetUserId,
            'target_user_email' => $userEmail,
        ]);

        return [
            'data' => [
                'id' => $targetUserId,
                'nome' => $userName,
                'email' => $userEmail,
            ],
            'message' => 'Usuario excluido com sucesso',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        $now = Carbon::now();
        $thirtyDaysAgo = $now->copy()->subDays(30);
        $sevenDaysAgo = $now->copy()->subDays(7);

        $totalUsers = Usuario::count();

        $proUsers = AssinaturaUsuario::where('status', AssinaturaUsuario::ST_ACTIVE)
            ->where('renova_em', '>', $now)
            ->where('plano_id', 2)
            ->distinct('user_id')
            ->count('user_id');

        $freeUsers = max(0, $totalUsers - $proUsers);
        $conversionRate = $totalUsers > 0 ? round(($proUsers / $totalUsers) * 100, 1) : 0;

        $todayStart = Carbon::today()->toDateTimeString();
        $todayEnd = Carbon::today()->endOfDay()->toDateTimeString();
        $newToday = Usuario::whereBetween('created_at', [$todayStart, $todayEnd])->count();

        $weekStart = $sevenDaysAgo->copy()->startOfDay()->toDateTimeString();
        $newThisWeek = Usuario::where('created_at', '>=', $weekStart)->count();

        $monthStart = $thirtyDaysAgo->copy()->startOfDay()->toDateTimeString();
        $newThisMonth = Usuario::where('created_at', '>=', $monthStart)->count();

        $usersCreatedAt = Usuario::where('created_at', '>=', $thirtyDaysAgo->copy()->startOfDay())
            ->pluck('created_at')
            ->toArray();

        $usersByDay = [];
        foreach ($usersCreatedAt as $createdAt) {
            $date = Carbon::parse($createdAt)->format('Y-m-d');
            $usersByDay[$date] = ($usersByDay[$date] ?? 0) + 1;
        }

        $usersByDayFilled = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i)->format('Y-m-d');
            $usersByDayFilled[$date] = $usersByDay[$date] ?? 0;
        }

        $subscriptionsByGateway = AssinaturaUsuario::selectRaw('gateway, COUNT(*) as count')
            ->where('status', AssinaturaUsuario::ST_ACTIVE)
            ->where('renova_em', '>', $now)
            ->groupBy('gateway')
            ->get()
            ->pluck('count', 'gateway')
            ->toArray();

        if (empty($subscriptionsByGateway)) {
            $subscriptionsByGateway = ['Interno' => $proUsers];
        }

        $subscriptionsByStatus = AssinaturaUsuario::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        $activeUsers = Usuario::where('updated_at', '>=', $thirtyDaysAgo)->count();

        $sixtyDaysAgo = $now->copy()->subDays(60);
        $previousMonthUsers = Usuario::where('created_at', '>=', $sixtyDaysAgo)
            ->where('created_at', '<', $thirtyDaysAgo)
            ->count();

        $growthRate = $previousMonthUsers > 0
            ? round((($newThisMonth - $previousMonthUsers) / $previousMonthUsers) * 100, 1)
            : ($newThisMonth > 0 ? 100 : 0);

        LogService::info('[SYSADMIN] Stats gerados', [
            'total_users' => $totalUsers,
            'pro_users' => $proUsers,
            'free_users' => $freeUsers,
        ]);

        return [
            'overview' => [
                'totalUsers' => (int) $totalUsers,
                'proUsers' => (int) $proUsers,
                'freeUsers' => (int) $freeUsers,
                'conversionRate' => (float) $conversionRate,
                'activeUsers' => (int) $activeUsers,
            ],
            'newUsers' => [
                'today' => (int) $newToday,
                'thisWeek' => (int) $newThisWeek,
                'thisMonth' => (int) $newThisMonth,
                'growthRate' => (float) $growthRate,
            ],
            'charts' => [
                'usersByDay' => $usersByDayFilled,
                'subscriptionsByGateway' => $subscriptionsByGateway,
                'subscriptionsByStatus' => $subscriptionsByStatus,
                'userDistribution' => [
                    'PRO' => (int) $proUsers,
                    'Gratuito' => (int) $freeUsers,
                ],
            ],
        ];
    }

    private function findUserByIdentifier(mixed $userIdOrEmail): ?object
    {
        return is_numeric((string) $userIdOrEmail)
            ? Usuario::find($userIdOrEmail)
            : Usuario::where('email', $userIdOrEmail)->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapUserListItem(object $user): array
    {
        return [
            'id' => $user->id,
            'support_code' => $user->support_code,
            'nome' => $user->nome,
            'email' => $user->email,
            'avatar' => $user->avatar ? rtrim(BASE_URL, '/') . '/' . $user->avatar : '',
            'is_admin' => $user->is_admin,
            'is_pro' => $user->isPro(),
            'plano_nome' => $user->isPro() ? 'Pro' : 'Free',
            'email_verified' => $user->email_verified_at !== null,
            'created_at' => $user->created_at,
        ];
    }

    private function resolvePlanName(object $subscription): ?string
    {
        if (!$subscription->plano_id) {
            return null;
        }

        $plan = Plano::find($subscription->plano_id);
        if ($plan) {
            return $plan->nome;
        }

        if ((int) $subscription->plano_id === 1) {
            return 'Free';
        }

        if ((int) $subscription->plano_id === 2) {
            return 'Pro';
        }

        return 'Plano ' . $subscription->plano_id;
    }
}
