<?php

declare(strict_types=1);

namespace Application\Services\User;

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
use Application\Services\Auth\EmailVerificationService;
use Application\Services\User\PerfilService;

/**
 * Service Provider para registrar as dependências do Perfil.
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
        $container->singleton(EmailVerificationService::class);

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
                $c->make(TelefoneFormatter::class),
                $c->make(EmailVerificationService::class)
            );
        });

        // Workflow and controller-facing perfil dependencies are resolved from these bindings.
    }
}
