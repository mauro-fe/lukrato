<?php

namespace Application\Middlewares;

use Application\Core\Exceptions\HttpResponseException;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Router;
use Application\Lib\Auth;

/**
 * Middleware de autenticação.
 *
 * - Requisições API/AJAX sem auth: retorna 404 stealth (previne enumeração).
 * - Requisições web sem auth: redireciona para /login?intended={path}
 *   para que o usuário possa fazer login e voltar à página solicitada.
 *
 * A detecção de sessão expirada no frontend é feita pelo SessionManager
 * via endpoint público /api/session/status (que NÃO passa por este middleware).
 */
class AuthMiddleware
{
    /**
     * Verifica se o usuário está logado.
     * Se não estiver -> redireciona para login (web) ou 404/401 (API).
     */
    public static function handle(Request $request): void
    {
        if (!Auth::isLoggedIn()) {
            Auth::logout();
            self::handleUnauthenticated($request);
            return;
        }

        if (!Auth::checkActivity()) {
            Auth::logout();
            self::handleUnauthenticated($request);
            return;
        }
    }

    /**
     * Usuário não autenticado:
     * - API/AJAX -> 404 stealth (sem cookie) ou 401 (com cookie)
     * - Web -> redireciona para login com ?intended=
     */
    private static function handleUnauthenticated(Request $request): void
    {
        if ($request->wantsJson() || $request->isAjax()) {
            if (!empty($_COOKIE['lukrato_known_user'])) {
                throw new HttpResponseException(Response::unauthorizedResponse('Sessão expirada'));
            }

            throw new HttpResponseException(Router::notFoundResponse($request));
        }

        throw new HttpResponseException(self::buildLoginRedirectResponse());
    }

    /**
     * Redireciona para /login preservando a URL solicitada como ?intended=
     */
    private static function buildLoginRedirectResponse(): Response
    {
        $intended = self::extractIntendedPath();
        $loginUrl = BASE_URL . 'login';

        if ($intended !== '') {
            $loginUrl .= '?intended=' . urlencode($intended);
        }

        return Response::redirectResponse($loginUrl);
    }

    /**
     * Extrai o path relativo da requisição (sem base path, sem query string).
     * Retorna string vazia se for a raiz ou se for inválido.
     */
    private static function extractIntendedPath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = str_replace('/index.php', '', dirname($scriptName));
        $basePath = rtrim($basePath, '/');

        if ($basePath !== '' && strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }

        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = trim($path, '/');

        if ($path === '' || $path === 'login' || !preg_match('#^[a-zA-Z0-9/_\\-]+$#', $path)) {
            return '';
        }

        return $path;
    }
}
