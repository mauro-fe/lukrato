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
        public readonly string $username,
        public readonly string $cpf,
        public readonly string $telefone,
        public readonly string $sexo,
        public readonly string $dataNascimento,
        public readonly EnderecoDTO $endereco
    ) {}

    /**
     * Cria uma instância do DTO a partir dos dados da requisição.
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            nome: trim($data['nome'] ?? ''),
            email: mb_strtolower(trim($data['email'] ?? '')),
            username: trim($data['username'] ?? ''),
            cpf: $data['cpf'] ?? '',
            telefone: $data['telefone'] ?? '',
            sexo: $data['sexo'] ?? '',
            dataNascimento: $data['data_nascimento'] ?? '',
            endereco: EnderecoDTO::fromArray($data['endereco'] ?? [])
        );
    }
}