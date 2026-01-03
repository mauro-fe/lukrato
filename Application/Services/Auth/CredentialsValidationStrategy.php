<?php

namespace Application\Services\Auth;

use Application\DTO\Auth\CredentialsDTO;
use Application\Models\Usuario;
use Application\Core\Exceptions\ValidationException;

class CredentialsValidationStrategy extends AbstractValidationStrategy
{
    protected function performValidation(CredentialsDTO $credentials): void
    {
        // 1) Campos vazios
        if ($credentials->isEmpty()) {
            $this->addError('email', 'Preencha e-mail e senha.');
            return;
        }

        // 2) Busca usuário ignorando maiúsculas/minúsculas
        $usuario = Usuario::whereRaw('LOWER(email) = ?', [strtolower($credentials->email)])->first();

        if (!$usuario) {
            // Usuário não encontrado
            $this->addError('credentials', 'E-mail ou senha inválidos.');
            return;
        }

        // 3) Detectar conta "só Google" (sem senha local, mas com google_id)
        $semSenhaLocal = ($usuario->senha === null || $usuario->senha === '');
        $temGoogle     = !empty($usuario->google_id);

        if ($semSenhaLocal && $temGoogle) {
            // Aqui queremos uma mensagem ESPECÍFICA, então lançamos a exception direto
            throw new ValidationException(
                errors: [
                    'email' => 'Esta conta foi criada usando o Google. Clique em "Entrar com Google" para acessar.'
                ],
                message: 'Conta vinculada ao Google'
            );
        }

        // 4) Não tem senha e também não tem Google → trata como credenciais inválidas
        if ($semSenhaLocal) {
            $this->addError('credentials', 'E-mail ou senha inválidos.');
            return;
        }

        // 5) Tem senha local → validar senha normalmente
        if (!password_verify($credentials->password, $usuario->senha)) {
            $this->addError('credentials', 'E-mail ou senha inválidos.');
        }
    }

    protected function getErrorMessage(): string
    {
        // Mensagem geral quando NÃO for o caso especial do Google
        return 'Falha na validação de credenciais';
    }
}
