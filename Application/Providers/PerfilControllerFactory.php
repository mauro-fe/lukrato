<?php

namespace Application\Providers;

// Classes principais que o Controller precisa
use Application\Services\PerfilService;
use Application\Validators\PerfilValidator;

// --- Dependências do PerfilService (7) ---
use Application\Repositories\UsuarioRepository;
use Application\Repositories\DocumentoRepository;
use Application\Repositories\TelefoneRepository;
use Application\Repositories\EnderecoRepository;
use Application\Builders\PerfilPayloadBuilder;
use Application\Formatters\DocumentFormatter;
use Application\Formatters\TelefoneFormatter;

// --- Dependências adicionais do PerfilValidator (3) ---
use Application\Formatters\DateFormatter;
use Application\Validators\EnderecoValidator;
// Nota: UsuarioRepository e DocumentoRepository já foram importados

/**
 * Factory responsável por construir o PerfilController
 * e todas as suas dependências.
 */
class PerfilControllerFactory
{
    /**
     * Cria e retorna as dependências prontas para o PerfilController.
     *
     * @return array
     */
    public static function buildDependencies(): array
    {
        // --- 1. Criar todas as dependências "netas" ---
        // (Assumindo que estas classes têm construtores simples)
        // Se alguma destas classes também precisar de argumentos,
        // o erro vai mudar e nos mostrar qual é a próxima.

        // Repositórios
        $usuarioRepo = new UsuarioRepository();
        $documentoRepo = new DocumentoRepository();
        $telefoneRepo = new TelefoneRepository();
        $enderecoRepo = new EnderecoRepository();

        // Formatters
        $documentFormatter = new DocumentFormatter();
        $telefoneFormatter = new TelefoneFormatter($documentFormatter);
        $dateFormatter = new DateFormatter();

        // Builders e Validators
        $payloadBuilder = new PerfilPayloadBuilder(
            $documentoRepo,
            $telefoneRepo,
            $enderecoRepo,
            $documentFormatter,
            $telefoneFormatter,
            $dateFormatter
        );
        $enderecoValidator = new EnderecoValidator($documentFormatter);


        // --- 2. Criar o PerfilService ---
        // (Passando as 7 dependências que ele precisa)
        $perfilService = new PerfilService(
            $usuarioRepo,
            $documentoRepo,
            $telefoneRepo,
            $enderecoRepo,
            $payloadBuilder,
            $documentFormatter,
            $telefoneFormatter
        );

        // --- 3. Criar o PerfilValidator ---
        // (Passando as 6 dependências que ele precisa)
        $validator = new PerfilValidator(
            $documentFormatter, // Reutilizado
            $telefoneFormatter, // Reutilizado
            $dateFormatter,
            $usuarioRepo,       // Reutilizado
            $documentoRepo,     // Reutilizado
            $enderecoValidator
        );

        // --- 4. Retornar o array final ---
        return [$perfilService, $validator];
    }

    /**
     * Cria e retorna apenas o PerfilService.
     * Útil quando só precisa do service sem o controller.
     *
     * @return PerfilService
     */
    public static function createService(): PerfilService
    {
        // Repositórios
        $usuarioRepo = new UsuarioRepository();
        $documentoRepo = new DocumentoRepository();
        $telefoneRepo = new TelefoneRepository();
        $enderecoRepo = new EnderecoRepository();

        // Formatters
        $documentFormatter = new DocumentFormatter();
        $telefoneFormatter = new TelefoneFormatter($documentFormatter);
        $dateFormatter = new DateFormatter();

        // Builder
        $payloadBuilder = new PerfilPayloadBuilder(
            $documentoRepo,
            $telefoneRepo,
            $enderecoRepo,
            $documentFormatter,
            $telefoneFormatter,
            $dateFormatter
        );

        return new PerfilService(
            $usuarioRepo,
            $documentoRepo,
            $telefoneRepo,
            $enderecoRepo,
            $payloadBuilder,
            $documentFormatter,
            $telefoneFormatter
        );
    }
}
