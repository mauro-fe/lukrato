<?php

namespace Application\Repositories;

use Application\Models\Endereco;

/**
 * Repository para operações com endereços.
 */
class EnderecoRepository
{
    /**
     * Busca o endereço principal do usuário.
     */
    public function getPrincipal(int $userId): Endereco
    {
        return Endereco::where('user_id', $userId)
            ->where('tipo', 'principal')
            ->firstOr(function () {
                // Retorna um modelo vazio se não encontrar
                return new Endereco();
            });
    }

    /**
     * Atualiza ou cria endereço principal.
     */
    public function updateOrCreatePrincipal(int $userId, array $data): void
    {
        Endereco::updateOrCreate(
            [
                'user_id' => $userId,
                'tipo' => 'principal'
            ],
            $data
        );
    }

    /**
     * Remove endereço principal do usuário.
     */
    public function deletePrincipal(int $userId): void
    {
        Endereco::where('user_id', $userId)
            ->where('tipo', 'principal')
            ->delete();
    }
}