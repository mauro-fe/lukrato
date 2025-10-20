<?php

namespace Application\Services;

use Application\Models\Usuario;
use Application\Core\Exceptions\ValidationException;

class AdminService
{

    public function validateUniqueFields(int $userId, array $dados): void
    {
        $erros = [];

        if (isset($dados['username']) && $this->isFieldTaken('username', $dados['username'], $userId)) {
            $erros['username'] = 'Este nome de usuário já está em uso.';
        }

        if (isset($dados['email']) && $this->isFieldTaken('email', $dados['email'], $userId)) {
            $erros['email'] = 'Este e-mail já está em uso.';
        }

        if (isset($dados['cnpj']) && $this->isFieldTaken('cnpj', $dados['cnpj'], $userId)) {
            $erros['cnpj'] = 'Este CNPJ já está cadastrado.';
        }

        if (!empty($erros)) {
            throw new ValidationException($erros, 422);
        }
    }

    /**
     * Valida unicidade de um campo específico.
     *
     * @param int $userId
     * @param string $campo
     * @param string $valor
     * @throws ValidationException
     */
    public function validateUniqueField(int $userId, string $campo, string $valor): void
    {
        $camposUnicos = ['username', 'email', 'cnpj'];

        if (in_array($campo, $camposUnicos) && $this->isFieldTaken($campo, $valor, $userId)) {
            throw new ValidationException([
                $campo => "Este {$campo} já está em uso por outro administrador."
            ], 422);
        }
    }

    /**
     * Verifica se um campo está sendo usado por outro admin.
     *
     * @param string $campo
     * @param string $valor
     * @param int $userId ID do admin atual (a ser ignorado)
     * @return bool
     */
    private function isFieldTaken(string $campo, string $valor, int $userId): bool
    {
        return Usuario::where($campo, $valor)
            ->where('id', '!=', $userId)
            ->exists();
    }
}
