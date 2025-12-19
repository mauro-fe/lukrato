<?php

namespace Application\Validators;

use Application\DTOs\PerfilUpdateDTO;
use Application\Formatters\DocumentFormatter;
use Application\Formatters\TelefoneFormatter;
use Application\Formatters\DateFormatter;
use Application\Repositories\UsuarioRepository;
use Application\Repositories\DocumentoRepository;

/**
 * Validator responsável por validar dados do perfil.
 */
class PerfilValidator
{
    public function __construct(
        private DocumentFormatter $documentFormatter,
        private TelefoneFormatter $telefoneFormatter,
        private DateFormatter $dateFormatter,
        private UsuarioRepository $usuarioRepo,
        private DocumentoRepository $documentoRepo,
        private EnderecoValidator $enderecoValidator
    ) {}

    /**
     * Valida os dados do DTO de perfil.
     * 
     * @return array Array de erros (vazio se não houver erros)
     */
    public function validate(PerfilUpdateDTO $dto, int $currentUserId): array
    {
        $errors = [];

        // Validações básicas
        $errors = array_merge($errors, $this->validateBasicFields($dto));

        // Validações de unicidade
        $errors = array_merge($errors, $this->validateUniqueness($dto, $currentUserId));

        // Validação de endereço
        if (!$dto->endereco->isEmpty()) {
            $errors = array_merge($errors, $this->enderecoValidator->validate($dto->endereco));
        }

        return $errors;
    }

    /**
     * Valida campos básicos (formato, obrigatoriedade).
     */
    private function validateBasicFields(PerfilUpdateDTO $dto): array
    {
        $errors = [];

        // Nome
        if ($dto->nome === '') {
            $errors['nome'] = 'Nome é obrigatório.';
        }

        // Email
        if ($dto->email === '' || !filter_var($dto->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'E-mail inválido.';
        }

        // Username
        if ($dto->username !== '' && mb_strlen($dto->username) < 3) {
            $errors['username'] = 'Username deve ter no mínimo 3 caracteres.';
        }

        // CPF
        $cpf = $this->documentFormatter->digits($dto->cpf);
        if ($cpf !== '' && !$this->documentFormatter->isValidCpf($cpf)) {
            $errors['cpf'] = 'CPF inválido.';
        }

        // Data de nascimento
        $dataNascimento = $this->dateFormatter->parse($dto->dataNascimento);
        if ($dto->dataNascimento !== '' && $dataNascimento === null) {
            $errors['data_nascimento'] = 'Data inválida.';
        } elseif ($dataNascimento && strtotime($dataNascimento) > strtotime('today')) {
            $errors['data_nascimento'] = 'Data no futuro não permitida.';
        }

        // Telefone
        [$ddd, $numero] = $this->telefoneFormatter->split($dto->telefone);
        if ($dto->telefone !== '' && ($ddd === null || $numero === null)) {
            $errors['telefone'] = 'Telefone inválido. Use DDD + número.';
        }

        return $errors;
    }

    /**
     * Valida unicidade de email, username e CPF.
     */
    private function validateUniqueness(PerfilUpdateDTO $dto, int $currentUserId): array
    {
        $errors = [];

        // Email único
        if ($this->usuarioRepo->emailExists($dto->email, $currentUserId)) {
            $errors['email'] = 'Este e-mail já está em uso.';
        }

        // Username único
        if ($dto->username !== '' && $this->usuarioRepo->usernameExists($dto->username, $currentUserId)) {
            $errors['username'] = 'Este username já está em uso.';
        }

        // CPF único
        $cpf = $this->documentFormatter->digits($dto->cpf);
        if ($cpf !== '' && $this->documentoRepo->cpfExists($cpf, $currentUserId)) {
            $errors['cpf'] = 'Este CPF já está em uso.';
        }

        return $errors;
    }
}