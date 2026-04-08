<?php

declare(strict_types=1);

namespace Application\Repositories;

use Application\Container\ApplicationContainer;
use Application\Formatters\DocumentFormatter;
use Application\Models\Documento;
use Application\Models\TipoDocumento;
use Application\Services\Infrastructure\CpfProtectionService;

/**
 * Repository para operações com documentos.
 */
class DocumentoRepository
{
    public function __construct(
        private ?CpfProtectionService $cpfProtectionService = null,
        private ?DocumentFormatter $documentFormatter = null
    ) {
        $this->cpfProtectionService = ApplicationContainer::resolveOrNew($this->cpfProtectionService, CpfProtectionService::class);
        $this->documentFormatter = ApplicationContainer::resolveOrNew($this->documentFormatter, DocumentFormatter::class);
    }

    /**
     * Busca o CPF do usuário.
     */
    public function getCpf(int $userId): ?string
    {
        $tipoCpf = $this->getTipoCpf();

        $documento = Documento::where('id_usuario', $userId)
            ->where('id_tipo', $tipoCpf->id_tipo)
            ->first(['numero', 'cpf_encrypted']);

        if (!$documento) {
            return null;
        }

        if (!empty($documento->cpf_encrypted)) {
            return $this->cpfProtectionService->decrypt((string) $documento->cpf_encrypted);
        }

        $legacyCpf = $this->normalizeCpf((string) ($documento->numero ?? ''));
        return $legacyCpf !== '' ? $legacyCpf : null;
    }

    /**
     * Atualiza ou cria o CPF do usuário.
     */
    public function updateOrCreateCpf(int $userId, string $cpf): void
    {
        $tipoCpf = $this->getTipoCpf();
        $normalizedCpf = $this->normalizeCpf($cpf);

        Documento::updateOrCreate(
            [
                'id_usuario' => $userId,
                'id_tipo' => $tipoCpf->id_tipo,
            ],
            [
                'numero' => null,
                'cpf_hash' => $this->cpfProtectionService->hash($normalizedCpf),
                'cpf_encrypted' => $this->cpfProtectionService->encrypt($normalizedCpf),
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

    public function hasCpf(int $userId): bool
    {
        $tipoCpf = $this->getTipoCpf();

        return Documento::where('id_usuario', $userId)
            ->where('id_tipo', $tipoCpf->id_tipo)
            ->where(function ($query) {
                $query->whereNotNull('cpf_encrypted')
                    ->orWhereNotNull('numero');
            })
            ->exists();
    }

    /**
     * Verifica se CPF já existe (exceto para o usuário atual).
     */
    public function cpfExists(string $cpf, int $exceptUserId): bool
    {
        $tipoCpf = $this->getTipoCpf();
        $normalizedCpf = $this->normalizeCpf($cpf);

        return Documento::where('cpf_hash', $this->cpfProtectionService->hash($normalizedCpf))
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

    private function normalizeCpf(string $cpf): string
    {
        return $this->documentFormatter->digits($cpf);
    }
}
