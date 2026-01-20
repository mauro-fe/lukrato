<?php

namespace Application\Repositories;

use Application\Models\Usuario;
use Application\Models\Sexo;

/**
 * Repository para operações com usuários.
 */
class UsuarioRepository
{
    /**
     * Busca usuário por ID.
     */
    public function findById(int $id): ?Usuario
    {
        return Usuario::find($id);
    }

    /**
     * Atualiza dados do usuário.
     */
    public function update(int $id, array $data): Usuario
    {
        $user = Usuario::findOrFail($id);

        // Dados básicos
        $user->nome = $data['nome'];
        $user->email = $data['email'];
        $user->data_nascimento = $data['data_nascimento'];

        // Sexo
        if (!empty($data['sexo'])) {
            $sexoLabel = $this->mapSexoLabel($data['sexo']);
            if ($sexoLabel) {
                $sexo = Sexo::firstOrCreate(['nm_sexo' => $sexoLabel]);
                $user->id_sexo = $sexo->id_sexo;
            }
        } else {
            $user->id_sexo = null;
        }

        $user->save();

        return $user;
    }

    /**
     * Verifica se email já existe (exceto para o usuário atual).
     */
    public function emailExists(string $email, int $exceptUserId): bool
    {
        return Usuario::whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
            ->where('id', '!=', $exceptUserId)
            ->exists();
    }

    /**
     * Mapeia valor de sexo para o label do banco de dados.
     */
    private function mapSexoLabel(string $value): ?string
    {
        $normalized = $this->normalizeSexoValue($value);

        return match ($normalized) {
            'M', 'MASCULINO' => 'Masculino',
            'F', 'FEMININO'  => 'Feminino',
            'O', 'OUTRO'     => 'Outro',
            'N', 'NAO INFORMADO', 'NAO-INFORMADO', 'PREFIRO NAO INFORMAR' => 'Nao informado',
            default => null,
        };
    }

    /**
     * Normaliza string de sexo.
     */
    private function normalizeSexoValue(string $value): string
    {
        $base = strtr($value, [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ã' => 'A',
            'Õ' => 'O',
            'Ç' => 'C',
            'á' => 'A',
            'é' => 'E',
            'í' => 'I',
            'ó' => 'O',
            'ú' => 'U',
            'ã' => 'A',
            'õ' => 'O',
            'ç' => 'C',
        ]);

        $base = str_replace(['-', '_', ' '], ' ', $base);
        return strtoupper(trim($base));
    }

    /**
     * Deleta o usuário.
     */
    public function delete(int $id): void
    {
        $user = Usuario::find($id);
        if ($user) {
            $user->delete();
        }
    }
}
