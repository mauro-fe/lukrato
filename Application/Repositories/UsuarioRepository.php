<?php

declare(strict_types=1);

namespace Application\Repositories;

use Application\Models\Sexo;
use Application\Models\Usuario;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Repository for user persistence operations.
 */
class UsuarioRepository
{
    private ?bool $pendingEmailColumnExists = null;

    /**
     * Find user by id.
     */
    public function findById(int $id): ?Usuario
    {
        return Usuario::find($id);
    }

    /**
     * Update user profile fields.
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): Usuario
    {
        $user = Usuario::findOrFail($id);

        $user->nome = $data['nome'];
        if (array_key_exists('email', $data)) {
            $user->email = $data['email'];
        }
        $user->data_nascimento = $data['data_nascimento'] ?: null;

        if (!empty($data['sexo'])) {
            $sexoLabel = $this->mapSexoLabel((string) $data['sexo']);
            if ($sexoLabel !== null) {
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
     * Check whether email already exists for another user.
     */
    public function emailExists(string $email, int $exceptUserId): bool
    {
        $normalized = mb_strtolower(trim($email));

        $query = Usuario::where('id', '!=', $exceptUserId);

        if ($this->hasPendingEmailColumn()) {
            $query->where(function ($nested) use ($normalized) {
                $nested->whereRaw('LOWER(email) = ?', [$normalized])
                    ->orWhereRaw('LOWER(pending_email) = ?', [$normalized]);
            });

            return $query->exists();
        }

        return $query->whereRaw('LOWER(email) = ?', [$normalized])->exists();
    }

    public function findByEmailOrPending(string $email): ?Usuario
    {
        $normalized = mb_strtolower(trim($email));

        if ($this->hasPendingEmailColumn()) {
            return Usuario::where(function ($query) use ($normalized) {
                $query->whereRaw('LOWER(email) = ?', [$normalized])
                    ->orWhereRaw('LOWER(pending_email) = ?', [$normalized]);
            })->first();
        }

        return Usuario::whereRaw('LOWER(email) = ?', [$normalized])->first();
    }

    private function mapSexoLabel(string $value): ?string
    {
        $normalized = $this->normalizeSexoValue($value);

        return match ($normalized) {
            'M', 'MASCULINO' => 'Masculino',
            'F', 'FEMININO' => 'Feminino',
            'O', 'OUTRO' => 'Outro',
            'NB', 'NAO BINARIO' => 'Nao-binario',
            'N', 'NAO INFORMADO', 'NAO-INFORMADO', 'PREFIRO NAO INFORMAR' => 'Nao informado',
            default => null,
        };
    }

    private function normalizeSexoValue(string $value): string
    {
        $base = strtr($value, [
            'Á' => 'A',
            'À' => 'A',
            'Â' => 'A',
            'Ã' => 'A',
            'Ä' => 'A',
            'É' => 'E',
            'È' => 'E',
            'Ê' => 'E',
            'Ë' => 'E',
            'Í' => 'I',
            'Ì' => 'I',
            'Î' => 'I',
            'Ï' => 'I',
            'Ó' => 'O',
            'Ò' => 'O',
            'Ô' => 'O',
            'Õ' => 'O',
            'Ö' => 'O',
            'Ú' => 'U',
            'Ù' => 'U',
            'Û' => 'U',
            'Ü' => 'U',
            'Ç' => 'C',
            'á' => 'a',
            'à' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'ä' => 'a',
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'í' => 'i',
            'ì' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ó' => 'o',
            'ò' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ö' => 'o',
            'ú' => 'u',
            'ù' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ç' => 'c',
        ]);

        $base = str_replace(['-', '_', ' '], ' ', $base);

        return strtoupper(trim($base));
    }

    /**
     * Soft-delete user and anonymize email.
     */
    public function delete(int $id): void
    {
        $user = Usuario::find($id);
        if ($user === null) {
            return;
        }

        $anonymizedEmail = 'deleted_' . time() . '_' . substr(md5((string) $id), 0, 8) . '@excluido.local';
        $user->email = $anonymizedEmail;
        $user->nome = 'Usuario Removido';
        $user->google_id = null;
        $user->save();

        $user->delete();
    }

    private function hasPendingEmailColumn(): bool
    {
        if ($this->pendingEmailColumnExists !== null) {
            return $this->pendingEmailColumnExists;
        }

        try {
            $this->pendingEmailColumnExists = Capsule::schema()->hasColumn('usuarios', 'pending_email');
        } catch (\Throwable) {
            $this->pendingEmailColumnExists = false;
        }

        return $this->pendingEmailColumnExists;
    }
}
