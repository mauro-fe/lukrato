<?php

namespace Application\Controllers\Auth;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Usuario;
use GUMP;

class RegistroController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function showForm(): void
    {
        $this->render('auth/register');
    }

    public function store(): void
    {
        $nome  = (string) $this->request->post('name');
        $email = strtolower(trim((string) $this->request->post('email')));
        $senha = (string) $this->request->post('password');

        $data = [
            'nome'              => $nome,
            'email'             => $email,
            'senha'             => $senha,
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
            $errors = $this->mapErrorsForUi($gump->get_errors_array());
            if ($this->request->isAjax()) {
                Response::validationError($errors);
                return;
            }
            $_SESSION['form_errors'] = $errors;
            $this->response->redirect(BASE_URL . 'register')->send();
            return;
        }

        if (Usuario::where('email', $email)->exists()) {
            $errors = ['email' => 'E-mail já cadastrado.'];
            if ($this->request->isAjax()) {
                Response::validationError($errors);
                return;
            }
            $_SESSION['form_errors'] = $errors;
            $this->response->redirect(BASE_URL . 'register')->send();
            return;
        }

        try {
            $user = new Usuario();
            $user->nome  = $nome;
            $user->email = $email;
            $user->senha = password_hash($senha, PASSWORD_BCRYPT);

            $user->save();

            if ($this->request->isAjax()) {
                Response::success([
                    'message'  => 'Conta criada com sucesso!',
                    'redirect' => rtrim(BASE_URL, '/') . '/login',
                ]);

                return;
            }

            $this->response->redirect(BASE_URL . 'login')->send();
        } catch (\Throwable $e) {
            if ($this->request->isAjax()) {
                Response::error('Falha ao cadastrar. Tente novamente mais tarde.', 500, [
                    'hint' => 'registration_failed'
                ]);
                return;
            }
            throw $e;
        }
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
