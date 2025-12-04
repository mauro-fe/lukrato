<?php

namespace Application\Repositories;

use Application\Models\Documento;
use Application\Models\TipoDocumento;

/**
 * Repository para operações com documentos.
 */
class DocumentoRepository
{
    /**
     * Busca o CPF do usuário.
     */
    public function getCpf(int $userId): ?string
    {
        $tipoCpf = $this->getTipoCpf();

        return Documento::where('id_usuario', $userId)
            ->where('id_tipo', $tipoCpf->id_tipo)
            ->value('numero');
    }

    /**
     * Atualiza ou cria o CPF do usuário.
     */
    public function updateOrCreateCpf(int $userId, string $cpf): void
    {
        $tipoCpf = $this->getTipoCpf();

        Documento::updateOrCreate(
            [
                'id_usuario' => $userId,
                'id_tipo' => $tipoCpf->id_tipo
            ],
            [
                'numero' => $cpf
            ]
        );
    }

    /**
     * Remove o CPF do usuário.
     */
    public function deleteCpf(int $userId): void
    {
        $tipoCpf = $this->getTipoCpf();

        Documento::where('id_usuario', $userId)
            ->where('id_tipo', $tipoCpf->id_tipo)
            ->delete();
    }

    /**
     * Verifica se CPF já existe (exceto para o usuário atual).
     */
    public function cpfExists(string $cpf, int $exceptUserId): bool
    {
        $tipoCpf = $this->getTipoCpf();

        return Documento::where('numero', $cpf)
            ->where('id_tipo', $tipoCpf->id_tipo)
            ->where('id_usuario', '!=', $exceptUserId)
            ->exists();
    }

    /**
     * Obtém ou cria o tipo de documento CPF.
     */
    private function getTipoCpf(): TipoDocumento
    {
        return TipoDocumento::firstOrCreate(['ds_tipo' => 'CPF']);
    }
}