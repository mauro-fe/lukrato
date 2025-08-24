<?php

namespace Application\Services;

use Application\Models\Usuario;
use Application\Core\Exceptions\ValidationException;

class AdminService
{
    /**
     * Valida campos que devem ser únicos ao atualizar o perfil.
     *
     * @param int $adminId ID do admin atual
     * @param array $dados Dados validados do formulário
     * @throws ValidationException
     */
    public function validateUniqueFields(int $adminId, array $dados): void
    {
        $erros = [];

        if (isset($dados['username']) && $this->isFieldTaken('username', $dados['username'], $adminId)) {
            $erros['username'] = 'Este nome de usuário já está em uso.';
        }

        if (isset($dados['email']) && $this->isFieldTaken('email', $dados['email'], $adminId)) {
            $erros['email'] = 'Este e-mail já está em uso.';
        }

        if (isset($dados['cnpj']) && $this->isFieldTaken('cnpj', $dados['cnpj'], $adminId)) {
            $erros['cnpj'] = 'Este CNPJ já está cadastrado.';
        }

        if (!empty($erros)) {
            throw new ValidationException($erros, 422);
        }
    }

    /**
     * Valida unicidade de um campo específico.
     *
     * @param int $adminId
     * @param string $campo
     * @param string $valor
     * @throws ValidationException
     */
    public function validateUniqueField(int $adminId, string $campo, string $valor): void
    {
        $camposUnicos = ['username', 'email', 'cnpj'];

        if (in_array($campo, $camposUnicos) && $this->isFieldTaken($campo, $valor, $adminId)) {
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
     * @param int $adminId ID do admin atual (a ser ignorado)
     * @return bool
     */
    private function isFieldTaken(string $campo, string $valor, int $adminId): bool
    {
        return Usuario::where($campo, $valor)
            ->where('id', '!=', $adminId)
            ->exists();
    }
}
