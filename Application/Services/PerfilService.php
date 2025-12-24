<?php

namespace Application\Services;

use Application\DTOs\PerfilUpdateDTO;
use Application\Repositories\UsuarioRepository;
use Application\Repositories\DocumentoRepository;
use Application\Repositories\TelefoneRepository;
use Application\Repositories\EnderecoRepository;
use Application\Builders\PerfilPayloadBuilder;
use Application\Formatters\DocumentFormatter;
use Application\Formatters\TelefoneFormatter;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Service responsável pela lógica de negócio do perfil.
 */
class PerfilService
{
    public function __construct(
        private UsuarioRepository $usuarioRepo,
        private DocumentoRepository $documentoRepo,
        private TelefoneRepository $telefoneRepo,
        private EnderecoRepository $enderecoRepo,
        private PerfilPayloadBuilder $payloadBuilder,
        private DocumentFormatter $documentFormatter,
        private TelefoneFormatter $telefoneFormatter
    ) {}

    /**
     * Obtém os dados completos do perfil do usuário.
     */
    public function obterPerfil(int $userId): ?array
    {
        $user = $this->usuarioRepo->findById($userId);

        if (!$user) {
            return null;
        }

        return $this->payloadBuilder->build($user);
    }

    /**
     * Atualiza o perfil completo do usuário.
     */
    public function atualizarPerfil(int $userId, PerfilUpdateDTO $dto): array
    {
        return DB::connection()->transaction(function () use ($userId, $dto) {
            // 1. Atualiza dados básicos do usuário
            $user = $this->usuarioRepo->update($userId, [
                'nome' => $dto->nome,
                'email' => $dto->email,
                'username' => $dto->username !== '' ? $dto->username : null,
                'data_nascimento' => $dto->dataNascimento,
                'sexo' => $dto->sexo,
            ]);

            // 2. Atualiza ou remove CPF
            $cpfLimpo = $this->documentFormatter->digits($dto->cpf);
            
            if ($cpfLimpo !== '') {
                $this->documentoRepo->updateOrCreateCpf($userId, $cpfLimpo);
            } else {
                $this->documentoRepo->deleteCpf($userId);
            }

            // 3. Atualiza ou remove telefone
            if ($dto->telefone !== '') {
                [$ddd, $numero] = $this->telefoneFormatter->split($dto->telefone);
                $this->telefoneRepo->updateOrCreate($userId, $ddd, $numero);
            } else {
                $this->telefoneRepo->delete($userId);
            }

            // 4. Atualiza ou remove endereço
            if ($dto->endereco->isEmpty()) {
                $this->enderecoRepo->deletePrincipal($userId);
            } else {
                $this->enderecoRepo->updateOrCreatePrincipal(
                    $userId, 
                    $dto->endereco->toArray()
                );
            }

            // Retorna o perfil atualizado
            return $this->obterPerfil($userId);
        });
    }
}