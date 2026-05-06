<?php

namespace Application\DTO;

/**
 * Data Transfer Object para atualização de perfil.
 * Encapsula os dados recebidos na requisição.
 */
class PerfilUpdateDTO
{
    public function __construct(
        public readonly string $nome,
        public readonly string $email,
        public readonly string $cpf,
        public readonly string $telefone,
        public readonly string $sexo,
        public readonly string $dataNascimento,
        public readonly EnderecoDTO $endereco
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromRequest(array $data): self
    {
        $endereco = $data['endereco'] ?? [];

        return new self(
            nome: trim((string) ($data['nome'] ?? '')),
            email: mb_strtolower(trim((string) ($data['email'] ?? ''))),
            cpf: (string) ($data['cpf'] ?? ''),
            telefone: (string) ($data['telefone'] ?? ''),
            sexo: (string) ($data['sexo'] ?? ''),
            dataNascimento: (string) ($data['data_nascimento'] ?? ''),
            endereco: EnderecoDTO::fromArray(is_array($endereco) ? $endereco : [])
        );
    }
}
