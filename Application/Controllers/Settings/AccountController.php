<?php

declare(strict_types=1);

namespace Application\Controllers\Settings;

use Application\Controllers\WebController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Models\Usuario;
use Application\Services\Infrastructure\LogService;
use Application\Services\Referral\ReferralAntifraudService;
use Closure;
use Throwable;

class AccountController extends WebController
{
    private ReferralAntifraudService $antifraudService;
    /** @var Closure(int): ?Usuario */
    private Closure $userLoader;
    /** @var Closure(): void */
    private Closure $logoutHandler;

    /**
     * @param callable(int): ?Usuario|null $userLoader
     * @param callable(): void|null $logoutHandler
     */
    public function __construct(
        ?ReferralAntifraudService $antifraudService = null,
        ?callable $userLoader = null,
        ?callable $logoutHandler = null
    ) {
        parent::__construct();

        $this->antifraudService = $this->resolveOrCreate($antifraudService, ReferralAntifraudService::class);
        $this->userLoader = $userLoader !== null
            ? Closure::fromCallable($userLoader)
            : static fn(int $userId): ?Usuario => Usuario::find($userId);
        $this->logoutHandler = $logoutHandler !== null
            ? Closure::fromCallable($logoutHandler)
            : static function (): void {
                Auth::logout();
            };
    }

    public function delete(): Response
    {
        $requestId = uniqid('acc_del_', true);
        $ip = $this->request->ip();

        LogService::info('Iniciando processo de exclusão de conta', [
            'request_id' => $requestId,
            'ip' => $ip,
        ]);

        $userId = $this->requireApiUserIdOrFail();

        try {
            $user = ($this->userLoader)($userId);

            if (!$user) {
                LogService::warning('Usuário não encontrado ao tentar excluir conta', [
                    'request_id' => $requestId,
                    'user_id' => $userId,
                    'ip' => $ip,
                ]);

                return Response::notFoundResponse('Usuário não encontrado.');
            }

            $originalEmail = $user->email;

            $anonymizedEmail = $this->buildAnonymizedEmail((int) $user->id);
            $user->email = $anonymizedEmail;
            $user->nome = 'Usuário Removido';
            $user->google_id = null;
            $user->save();

            $result = $user->delete();

            if (!$result) {
                LogService::error('Falha ao excluir conta', [
                    'request_id' => $requestId,
                    'user_id' => $userId,
                    'email' => $originalEmail,
                    'ip' => $ip,
                ]);

                return Response::errorResponse('Não foi possível excluir sua conta. Tente novamente.', 500);
            }

            $this->antifraudService->onAccountDeleted($originalEmail, $userId, $ip);

            ($this->logoutHandler)();

            LogService::info('Conta excluída com sucesso', [
                'request_id' => $requestId,
                'user_id' => $userId,
                'email_original' => $originalEmail,
                'email_anonimizado' => $anonymizedEmail,
                'delete_result' => $result,
                'ip' => $ip,
            ]);

            return Response::successResponse(null, 'Conta excluída com sucesso.');
        } catch (Throwable $e) {
            LogService::error('Erro inesperado ao excluir conta', [
                'request_id' => $requestId,
                'user_id' => $userId,
                'ip' => $ip,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Response::errorResponse('Erro ao excluir conta. Tente novamente mais tarde.', 500);
        }
    }

    private function buildAnonymizedEmail(int $userId): string
    {
        return 'deleted_' . time() . '_' . substr(md5((string) $userId), 0, 8) . '@anonimizado.local';
    }
}
