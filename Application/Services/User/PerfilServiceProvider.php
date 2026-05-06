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
use Illuminate\Container\Container;

/**
 * Registra o grafo de dependências do domínio de perfil.
 */
class PerfilServiceProvider
{
    /**
     * Registra os serviços no container.
     */
    public function register(Container $container): void
    {
        $container->singleton(DocumentFormatter::class);
        $container->singleton(DateFormatter::class);
        $container->singleton(TelefoneFormatter::class);

        $container->singleton(UsuarioRepository::class);
        $container->singleton(DocumentoRepository::class);
        $container->singleton(TelefoneRepository::class);
        $container->singleton(EnderecoRepository::class);
        $container->singleton(EmailVerificationService::class);
        $container->singleton(EnderecoValidator::class);
        $container->singleton(PerfilValidator::class);
        $container->singleton(PerfilPayloadBuilder::class);
        $container->singleton(PerfilService::class);
    }
}
