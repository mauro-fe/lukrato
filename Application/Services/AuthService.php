<?php

namespace Application\Services;

use Application\Models\Usuario; // <-- novo modelo
use Application\Lib\Auth;
use Application\Core\Request;
use Application\Core\Exceptions\ValidationException;
use Application\Services\LogService;

class AuthService
{
    public const SESSION_TIMEOUT = 3600;

    // Application/Services/AuthService.php

    public function login(string $email, string $password): array
    {
        $request = new Request();
        $ip = $request->ip() ?? 'unknown';

        // 1) entrada (nunca logar senha)
        \Application\Services\LogService::info('Login start', ['email' => trim(strtolower($email)), 'ip' => $ip]);

        if (empty($email) || empty($password)) {
            \Application\Services\LogService::warning('Login validation failed: missing fields', ['email' => (string)$email, 'ip' => $ip]);
            throw new ValidationException(['email' => 'Preencha e-mail e senha.']);
        }

        $email = trim(strtolower($email));

        // 2) consulta no banco (sem depender do scope, pra evitar “undefined method”)
        $usuario = \Application\Models\Usuario::whereRaw('LOWER(email) = ?', [$email])->first();

        if (!$usuario) {
            \Application\Services\LogService::warning('Login failed: user not found', ['email' => $email, 'ip' => $ip]);
            throw new \Exception('Credenciais inválidas.');
        }

        // 3) verificação de senha
        if (!password_verify($password, $usuario->senha)) {
            \Application\Services\LogService::warning('Login failed: wrong password', ['email' => $email, 'ip' => $ip, 'user_id' => $usuario->id]);
            throw new \Exception('Credenciais inválidas.');
        }

        // 4) sessão e aliases de compat
        \Application\Lib\Auth::login($usuario);
        session_regenerate_id(true);

        $_SESSION['usuario_id']   = (int) $usuario->id;
        $_SESSION['usuario_nome'] = (string) ($usuario->nome ?? '');
        $_SESSION['admin_id']       ??= $_SESSION['usuario_id'];
        $_SESSION['admin_username'] ??= ($_SESSION['usuario_nome'] ?: 'usuario');

        \Application\Services\LogService::info('Login success', ['user_id' => $usuario->id, 'ip' => $ip]);

        $redirect = \Application\Lib\Helpers::baseUrl('admin/' . $_SESSION['admin_username'] . '/dashboard');

        return ['usuario' => $usuario, 'redirect' => $redirect];
    }



    public function logout(): void
    {
        $id = Auth::id();
        Auth::logout();
        if ($id) {
            LogService::info('Logout realizado', ['usuario_id' => $id, 'ip' => (new Request())->ip()]);
        }
    }
}