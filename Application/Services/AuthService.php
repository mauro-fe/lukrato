<?php

namespace Application\Services;

use Application\Models\Usuario; // <-- novo modelo
use Application\Lib\Auth;
use Application\Core\Request;
use Application\Core\Exceptions\ValidationException;
use Application\Services\LogService;
use Application\Lib\Helpers;

class AuthService
{
    public const SESSION_TIMEOUT = 3600;


    public function login(string $email, string $password): array
    {
        $request = new Request();
        $ip = $request->ip() ?? 'unknown';

        LogService::info('Login start', ['email' => trim(strtolower($email)), 'ip' => $ip]);

        if (empty($email) || empty($password)) {
            LogService::warning('Login validation failed: missing fields', ['email' => (string)$email, 'ip' => $ip]);
            throw new ValidationException(['email' => 'Preencha e-mail e senha.']);
        }

        $email = trim(strtolower($email));

        $usuario = Usuario::whereRaw('LOWER(email) = ?', [$email])->first();

        if (!$usuario) {
            LogService::warning('Login failed: user not found', ['email' => $email, 'ip' => $ip]);
            throw new \Exception('Credenciais inválidas.');
        }

        if (!password_verify($password, $usuario->senha)) {
            LogService::warning('Login failed: wrong password', ['email' => $email, 'ip' => $ip, 'user_id' => $usuario->id]);
            throw new \Exception('Credenciais inválidas.');
        }

        Auth::login($usuario);
        session_regenerate_id(true);

        $_SESSION['usuario_id']   = (int) $usuario->id;
        $_SESSION['usuario_nome'] = (string) ($usuario->nome ?? '');
        $_SESSION['admin_id']       ??= $_SESSION['usuario_id'];
        $_SESSION['admin_username'] ??= ($_SESSION['usuario_nome'] ?: 'usuario');

        LogService::info('Login success', ['user_id' => $usuario->id, 'ip' => $ip]);

        $redirect = Helpers::baseUrl('admin/' . $_SESSION['admin_username'] . '/dashboard');

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
