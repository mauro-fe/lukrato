<?php

namespace Application\Controllers\Auth;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Usuario;
use Application\Services\LogService;
use Application\Core\Exceptions\ValidationException; 
use GUMP;
use Throwable; 

class RegistroController extends BaseController
{
    /**
     * Exibe o formulário de registro.
     */
    public function showForm(): void
    {
        $errors = $_SESSION['form_errors'] ?? null;
        $success = $this->getSuccess(); 
        unset($_SESSION['form_errors']);
        
        $this->render('auth/register', [
            'errors'  => $errors,
            'success' => $success
        ]);
    }

    /**
     * Processa a submissão do formulário de registro.
     */
    public function store(): void
    {
        $isAjax = $this->request->isAjax();
        
        $data = [
            'nome'                => (string) $this->request->post('name'),
            'email'               => strtolower(trim((string) $this->request->post('email'))),
            'senha'               => (string) $this->request->post('password'),
            'senha_confirmacao'   => (string) $this->request->post('password_confirmation'),
        ];

        try {
            // 1. Validação GUMP
            $gump = new GUMP();
            $gump->filter_rules([
                'nome'  => 'trim|sanitize_string',
                'email' => 'trim|sanitize_email',
            ]);
            $gump->validation_rules([
                'nome'                => 'required|min_len,3|max_len,150',
                'email'               => 'required|valid_email|max_len,150',
                'senha'               => 'required|min_len,8|max_len,72',
                'senha_confirmacao'   => 'required|equalsfield,senha',
            ]);

            $validData = $gump->run($data);
            if ($validData === false) {
                throw new ValidationException(
                    // Argumento 1: array $errors
                    $this->mapGumpErrors($gump->get_errors_array()),
                    // Argumento 2: string $message
                    'Falha na validação' 
                );
            }

            // 2. Validação de E-mail Único
            if (Usuario::where('email', $validData['email'])->exists()) {
                
                // ***** ESTA É A LINHA CORRIGIDA *****
                throw new ValidationException(
                    // Argumento 1: array $errors
                    ['email' => 'E-mail já cadastrado.'],
                    // Argumento 2: string $message
                    'E-mail já cadastrado.'
                );
            }

            // 3. Criação do Usuário
            $user = new Usuario();
            $user->nome  = $validData['nome'];
            $user->email = $validData['email'];
            $user->senha = $validData['senha']; // Assumindo que o Model Usuario faz o hash
            $user->save();

            // 4. Resposta de Sucesso
            if ($isAjax) {
                $this->ok([
                    'message'  => 'Conta criada com sucesso!',
                    'redirect' => rtrim(BASE_URL, '/') . '/login',
                ], 201);
            } else {
                $this->setSuccess('Conta criada com sucesso! Você já pode fazer o login.');
                $this->redirect('login');
            }

        } catch (ValidationException $e) {
            // 5. Resposta de Falha de Validação
            if ($isAjax) {
                $this->fail($e->getMessage(), 422, $e->getErrors());
            } else {
                $_SESSION['form_errors'] = $e->getErrors();
                $this->redirect('register');
            }
            
        } catch (Throwable $e) {
            // 6. Resposta de Erro Interno
            LogService::error('Falha ao registrar usuário', ['exception' => $e->getMessage()]);

            if ($isAjax) {
                $this->fail('Falha ao cadastrar. Tente novamente mais tarde.', 500);
            } else {
                $this->setError('Não foi possível criar sua conta. Tente novamente mais tarde.');
                $this->redirect('register');
            }
        }
    }

    /**
     * Mapeia os erros do GUMP para chaves de formulário UI.
     */
    private function mapGumpErrors(array $errors): array
    {
        return [
            'name'                  => $errors['nome'] ?? null,
            'email'                 => $errors['email'] ?? null,
            'password'              => $errors['senha'] ?? null,
            'password_confirmation' => $errors['senha_confirmacao'] ?? null,
        ];
    }
}