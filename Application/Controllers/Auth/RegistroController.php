<?php

namespace Application\Controllers\Auth;

use Application\Controllers\BaseController;
use Application\Services\Auth\AuthService;
use Application\Core\Exceptions\ValidationException;
use Application\Services\LogService;
use Application\Models\Usuario;
use Application\Lib\Auth;
use Throwable;

class RegistroController extends BaseController
{
    private AuthService $authService;

    public function __construct()
    {
        parent::__construct();
        $this->authService = new AuthService();
    }

    public function showForm(): void
    {
        // Dados vindos do login social (ex: Google)
        $socialData = $_SESSION['social_register'] ?? null;

        $this->render('auth/register', [
            'errors'     => $this->getSessionErrors(),
            'success'    => $this->getSuccess(),
            'socialData' => $socialData, // usado na view para pré-preencher nome/email
        ]);
    }

    public function store(): void
    {
        $isAjax = $this->request->isAjax();

        $emailTentativa = $this->request->post('email', 'não-informado');

        // Verifica se veio de login social (Google)
        $socialData     = $_SESSION['social_register'] ?? null;
        $isGoogleSocial = !empty($socialData) && (($socialData['provider'] ?? null) === 'google');

        try {
            // Payload base
            $payload = [
                'name'  => $this->request->post('name', ''),
                'email' => $emailTentativa,
            ];

            if ($isGoogleSocial) {
                // Cadastro via Google → conta social, sem senha obrigatória
                $payload['google_id']             = $socialData['google_id'] ?? null;
                $payload['password']              = null;
                $payload['password_confirmation'] = null;
                $payload['provider']              = 'google';
            } else {
                // Cadastro tradicional → exige senha
                $payload['password']              = $this->request->post('password', '');
                $payload['password_confirmation'] = $this->request->post('password_confirmation', '');
            }

            $result = $this->authService->register($payload);

            // Cadastro concluído → pode limpar os dados temporários do social login
            if ($socialData) {
                unset($_SESSION['social_register']);
            }

            $this->respondToRegistrationSuccess($result, $isAjax, $emailTentativa, $socialData);
        } catch (ValidationException $e) {
            $this->respondToValidationError($e, $isAjax, $emailTentativa);
        } catch (Throwable $e) {
            $this->respondToRegistrationError($e, $isAjax, $emailTentativa);
        }
    }

    private function getSessionErrors(): ?array
    {
        $errors = $_SESSION['form_errors'] ?? null;
        unset($_SESSION['form_errors']);
        return $errors;
    }

    /**
     * @param array      $result      Resultado retornado pelo AuthService::register()
     * @param bool       $isAjax
     * @param string     $email
     * @param array|null $socialData  Dados de login social (ex: Google) ou null
     */
    private function respondToRegistrationSuccess(array $result, bool $isAjax, string $email, ?array $socialData = null): void
    {
        $provider = $socialData['provider'] ?? 'local';

        LogService::info('Novo usuário registrado com sucesso.', [
            'email'    => $email,
            'ip'       => $this->request->ip() ?? 'unknown',
            'user_id'  => $result['user_id'] ?? 'unknown',
            'provider' => $provider,
        ]);

        // 👉 Se veio do Google, já faz login e manda pro dashboard
        if ($provider === 'google') {
            $userId = $result['user_id'] ?? null;
            $usuario = null;

            if ($userId) {
                $usuario = Usuario::find($userId);
            }

            // fallback: busca por e-mail se por algum motivo não vier o ID
            if (!$usuario) {
                $usuario = Usuario::where('email', $email)->first();
            }

            if ($usuario) {
                Auth::login($usuario);
            }

            // Resposta AJAX
            if ($isAjax) {
                $this->ok([
                    'message'  => 'Conta criada com Google e login realizado com sucesso!',
                    'redirect' => 'dashboard',
                ], 201);
                return;
            }

            // Resposta normal (HTTP tradicional)
            $this->setSuccess('Conta criada com Google! Bem-vindo ao Lukrato.');
            $this->redirect('dashboard');
            return;
        }

        // Fluxo padrão (cadastro por e-mail/senha)
        if ($isAjax) {
            $this->ok([
                'message'  => $result['message'],
                'redirect' => $result['redirect'] ?? 'login'
            ], 201);
        } else {
            $this->setSuccess('Conta criada com sucesso! Você já pode fazer o login.');
            $this->redirect('login');
        }
    }

    private function respondToValidationError(ValidationException $e, bool $isAjax, string $email): void
    {
        LogService::warning('Falha de validação no registro.', [
            'email'  => $email,
            'ip'     => $this->request->ip() ?? 'unknown',
            'errors' => $e->getErrors()
        ]);

        if ($isAjax) {
            $this->fail($e->getMessage(), 422, $e->getErrors());
        } else {
            $_SESSION['form_errors'] = $e->getErrors();
            $this->redirect('register');
        }
    }

    private function respondToRegistrationError(Throwable $e, bool $isAjax, string $email): void
    {
        LogService::error('Exceção crítica ao tentar registrar usuário.', [
            'email'   => $email,
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => $e->getTraceAsString()
        ]);

        $message = 'Falha ao cadastrar. Tente novamente mais tarde.';

        if ($isAjax) {
            $this->fail($message, 500);
        } else {
            $this->setError($message);
            $this->redirect('register');
        }
    }
}
