<?php

namespace Application\Providers;

use Application\DTOs\PerfilUpdateDTO;
use Application\Repositories\UsuarioRepository;
use Application\Repositories\DocumentoRepository;
use Application\Repositories\TelefoneRepository;
use Application\Repositories\EnderecoRepository;
use Application\Builders\PerfilPayloadBuilder;
use Application\Formatters\DocumentFormatter;
use Application\Formatters\TelefoneFormatter;
use Application\Formatters\DateFormatter;
use Application\Validators\EnderecoValidator;
use Application\Validators\PerfilValidator;
use Application\Services\PerfilService;
use Application\Controllers\Api\PerfilController;

/**
 * Exemplo de Service Provider para registrar as dependências.
 * Adapte conforme o container de injeção de dependências que você usa.
 */
class PerfilServiceProvider
{
    /**
     * Registra os serviços no container.
     */
    public function register($container): void
    {
        // Formatters (sem dependências)
        $container->singleton(DocumentFormatter::class);
        $container->singleton(DateFormatter::class);

        // TelefoneFormatter depende de DocumentFormatter
        $container->singleton(TelefoneFormatter::class, function ($c) {
            return new TelefoneFormatter(
                $c->make(DocumentFormatter::class)
            );
        });

        // Repositories
        $container->singleton(UsuarioRepository::class);
        $container->singleton(DocumentoRepository::class);
        $container->singleton(TelefoneRepository::class);
        $container->singleton(EnderecoRepository::class);

        // Validators
        $container->singleton(EnderecoValidator::class, function ($c) {
            return new EnderecoValidator(
                $c->make(DocumentFormatter::class)
            );
        });

        $container->singleton(PerfilValidator::class, function ($c) {
            return new PerfilValidator(
                $c->make(DocumentFormatter::class),
                $c->make(TelefoneFormatter::class),
                $c->make(DateFormatter::class),
                $c->make(UsuarioRepository::class),
                $c->make(DocumentoRepository::class),
                $c->make(EnderecoValidator::class)
            );
        });

        // Builder
        $container->singleton(PerfilPayloadBuilder::class, function ($c) {
            return new PerfilPayloadBuilder(
                $c->make(DocumentoRepository::class),
                $c->make(TelefoneRepository::class),
                $c->make(EnderecoRepository::class),
                $c->make(DocumentFormatter::class),
                $c->make(TelefoneFormatter::class),
                $c->make(DateFormatter::class)
            );
        });

        // Services
        $container->singleton(PerfilService::class, function ($c) {
            return new PerfilService(
                $c->make(UsuarioRepository::class),
                $c->make(DocumentoRepository::class),
                $c->make(TelefoneRepository::class),
                $c->make(EnderecoRepository::class),
                $c->make(PerfilPayloadBuilder::class),
                $c->make(DocumentFormatter::class),
                $c->make(TelefoneFormatter::class)
            );
        });

        // Controllers
        $container->singleton(PerfilController::class, function ($c) {
            return new PerfilController(
                $c->make(PerfilService::class),
                $c->make(PerfilValidator::class)
            );
        });
    }
}

/**
 * ALTERNATIVA: Se não usar Container DI, você pode criar um Factory
 */
class PerfilControllerFactory
{
    public static function create(): PerfilController
    {
        [$perfilService, $perfilValidator] = self::buildDependencies();
        return new PerfilController($perfilService, $perfilValidator);
    }

    /**
     * Permite reutilizar o wiring do controller quando o container nǜo estiver disponível.
     */
    public static function buildDependencies(): array
    {
        // Formatters
        $documentFormatter = new DocumentFormatter();
        $dateFormatter = new DateFormatter();
        $telefoneFormatter = new TelefoneFormatter($documentFormatter);

        // Repositories
        $usuarioRepo = new UsuarioRepository();
        $documentoRepo = new DocumentoRepository();
        $telefoneRepo = new TelefoneRepository();
        $enderecoRepo = new EnderecoRepository();

        // Validators
        $enderecoValidator = new EnderecoValidator($documentFormatter);
        $perfilValidator = new PerfilValidator(
            $documentFormatter,
            $telefoneFormatter,
            $dateFormatter,
            $usuarioRepo,
            $documentoRepo,
            $enderecoValidator
        );

        // Builder
        $payloadBuilder = new PerfilPayloadBuilder(
            $documentoRepo,
            $telefoneRepo,
            $enderecoRepo,
            $documentFormatter,
            $telefoneFormatter,
            $dateFormatter
        );

        // Service
        $perfilService = new PerfilService(
            $usuarioRepo,
            $documentoRepo,
            $telefoneRepo,
            $enderecoRepo,
            $payloadBuilder,
            $documentFormatter,
            $telefoneFormatter
        );

        return [$perfilService, $perfilValidator];
    }
}
