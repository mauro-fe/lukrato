<?php

namespace Application\Controllers\Auth;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Usuario;
use GUMP;

class RegisterController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function showForm(): void
    {
        $this->view->render('auth/register');
    }

    public function store(): void
    {
        $data = [
            'nome'              => (string) $this->request->post('name'),
            'email'             => (string) $this->request->post('email'),
            'senha'             => (string) $this->request->post('password'),
            'senha_confirmacao' => (string) $this->request->post('password_confirmation'),
        ];

        $gump = new GUMP();
        $gump->filter_rules([
            'nome'  => 'trim|sanitize_string',
            'email' => 'trim|sanitize_email',
        ]);
        $gump->validation_rules([
            'nome'              => 'required|min_len,3|max_len,150',
            'email'             => 'required|valid_email|max_len,150',
            'senha'             => 'required|min_len,8|max_len,72',
            'senha_confirmacao' => 'required|equalsfield,senha',
        ]);

        $valid = $gump->run($data);
        if ($valid === false) {
            $this->fail($this->mapErrorsForUi($gump->get_errors_array()));
            return;
        }

        if (Usuario::byEmail($data['email'])->exists()) {
            $this->fail(['email' => 'E-mail já cadastrado.']);
            return;
        }

        try {
            $user = new Usuario();
            $user->nome  = $data['nome'];
            $user->email = strtolower(trim($data['email']));

            $raw = (string) $data['senha'];
            $user->senha = password_get_info($raw)['algo'] !== 0
                ? $raw
                : password_hash($raw, PASSWORD_BCRYPT);

            $user->save();


            if ($this->request->isAjax()) {
                Response::json([
                    'status'   => 'success',
                    'message'  => 'Conta criada com sucesso!',
                    'redirect' => BASE_URL . 'login',
                ], 200);
                return;
            }

            $this->response
                ->setStatusCode(302)
                ->header('Location', BASE_URL . 'login')
                ->send();
        } catch (\Throwable $e) {
            if ($this->request->isAjax()) {
                Response::json([
                    'errors' => [
                        'general' => 'Falha ao cadastrar: ' . $e->getMessage(),
                        'where'   => $e->getFile() . ':' . $e->getLine(),
                    ]
                ], 500);
                return;
            }
            throw $e;
        }
    }

    private function fail(array $errors): void
    {
        if ($this->request->isAjax()) {
            Response::json(['errors' => $errors], 422);
            return;
        }

        $_SESSION['form_errors'] = $errors;
        $this->response
            ->setStatusCode(302)
            ->header('Location', BASE_URL . 'login')
            ->send();
    }

    private function mapErrorsForUi(array $errors): array
    {
        return [
            'name'                  => $errors['nome']              ?? null,
            'email'                 => $errors['email']             ?? null,
            'password'              => $errors['senha']             ?? null,
            'password_confirmation' => $errors['senha_confirmacao'] ?? null,
        ];
    }
}
