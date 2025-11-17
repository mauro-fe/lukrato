<?php

namespace Application\Repositories;

use Application\Models\Telefone;
use Application\Models\Ddd;

/**
 * Repository para operações com telefones.
 */
class TelefoneRepository
{
    /**
     * Busca o telefone principal do usuário.
     */
    public function getByUserId(int $userId): ?Telefone
    {
        return Telefone::where('id_usuario', $userId)
            ->orderBy('id_telefone')
            ->first();
    }

    /**
     * Busca DDD por ID.
     */
    public function getDddById(?int $dddId): ?Ddd
    {
        if (!$dddId) {
            return null;
        }

        return Ddd::find($dddId);
    }

    /**
     * Atualiza ou cria telefone do usuário.
     */
    public function updateOrCreate(int $userId, string $dddCode, string $numero): void
    {
        $ddd = Ddd::firstOrCreate(['codigo' => $dddCode]);
        
        $telefone = $this->getByUserId($userId);

        $data = [
            'numero' => $numero,
            'id_ddd' => $ddd->id_ddd,
            'tipo' => $telefone?->tipo ?? 'celular'
        ];

        if ($telefone) {
            $telefone->fill($data)->save();
        } else {
            Telefone::create(array_merge($data, ['id_usuario' => $userId]));
        }
    }

    /**
     * Remove telefone do usuário.
     */
    public function delete(int $userId): void
    {
        Telefone::where('id_usuario', $userId)->delete();
    }
}