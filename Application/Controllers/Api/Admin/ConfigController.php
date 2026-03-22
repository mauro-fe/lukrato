<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Admin;

use Application\Controllers\Api\User\PerfilController as UserPerfilController;
use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Providers\PerfilControllerFactory;
use Application\Services\Infrastructure\LogService;
use Application\Services\User\PerfilService;
use Application\Validators\PerfilValidator;
use Throwable;

class ConfigController extends BaseController
{
    private const PROFILE_FIELDS = [
        'nome',
        'email',
        'cpf',
        'telefone',
        'sexo',
        'data_nascimento',
        'endereco',
        'cep',
        'rua',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'estado',
    ];

    private PerfilService $perfilService;
    private PerfilValidator $validator;

    public function __construct(
        ?PerfilService $perfilService = null,
        ?PerfilValidator $validator = null
    ) {
        parent::__construct();

        [$resolvedPerfilService, $resolvedValidator] = PerfilControllerFactory::buildDependencies();

        $this->perfilService = $perfilService ?? $resolvedPerfilService;
        $this->validator = $validator ?? $resolvedValidator;
    }

    public function update(): Response
    {
        $this->requireApiUserIdOrFail();

        try {
            $payload = $this->getRequestPayload();

            if ($payload === []) {
                return $this->failResponse('Nenhum dado enviado.', 400);
            }

            if ($this->isThemePayload($payload)) {
                return $this->delegateToPerfil('updateTheme', [
                    'theme' => (string) $payload['theme'],
                ], 'theme');
            }

            if ($this->containsProfileFields($payload)) {
                return $this->delegateToPerfil('update', $this->mergeWithCurrentProfile($payload), 'profile');
            }

            LogService::warning('Payload legado não suportado em /api/config', [
                'user_id' => $this->userId,
                'keys' => array_keys($payload),
            ]);

            return $this->failResponse(
                'Rota legada de configurações não suporta mais este payload. Use /api/perfil ou /api/perfil/tema.',
                410
            );
        } catch (Throwable $e) {
            return $this->failAndLogResponse($e, 'Erro ao processar configurações legadas.');
        }
    }

    private function isThemePayload(array $payload): bool
    {
        return isset($payload['theme']) && count(array_diff(array_keys($payload), ['theme'])) === 0;
    }

    private function containsProfileFields(array $payload): bool
    {
        foreach (self::PROFILE_FIELDS as $field) {
            if (array_key_exists($field, $payload)) {
                return true;
            }
        }

        return false;
    }

    private function mergeWithCurrentProfile(array $payload): array
    {
        $currentProfile = $this->perfilService->obterPerfil((int) $this->userId) ?? [];
        $currentAddress = is_array($currentProfile['endereco'] ?? null) ? $currentProfile['endereco'] : [];
        $incomingAddress = $this->extractIncomingAddress($payload);

        return [
            'nome' => trim((string) ($payload['nome'] ?? $currentProfile['nome'] ?? '')),
            'email' => trim((string) ($payload['email'] ?? $currentProfile['email'] ?? '')),
            'cpf' => (string) ($payload['cpf'] ?? $currentProfile['cpf'] ?? ''),
            'telefone' => (string) ($payload['telefone'] ?? $currentProfile['telefone'] ?? ''),
            'sexo' => (string) ($payload['sexo'] ?? $currentProfile['sexo'] ?? ''),
            'data_nascimento' => (string) ($payload['data_nascimento'] ?? $currentProfile['data_nascimento'] ?? ''),
            'endereco' => [
                'cep' => trim((string) ($incomingAddress['cep'] ?? $currentAddress['cep'] ?? '')),
                'rua' => trim((string) ($incomingAddress['rua'] ?? $currentAddress['rua'] ?? '')),
                'numero' => trim((string) ($incomingAddress['numero'] ?? $currentAddress['numero'] ?? '')),
                'complemento' => trim((string) ($incomingAddress['complemento'] ?? $currentAddress['complemento'] ?? '')),
                'bairro' => trim((string) ($incomingAddress['bairro'] ?? $currentAddress['bairro'] ?? '')),
                'cidade' => trim((string) ($incomingAddress['cidade'] ?? $currentAddress['cidade'] ?? '')),
                'estado' => trim((string) ($incomingAddress['estado'] ?? $currentAddress['estado'] ?? '')),
            ],
        ];
    }

    private function extractIncomingAddress(array $payload): array
    {
        if (isset($payload['endereco']) && is_array($payload['endereco'])) {
            return $payload['endereco'];
        }

        $address = [];
        foreach (['cep', 'rua', 'numero', 'complemento', 'bairro', 'cidade', 'estado'] as $field) {
            if (array_key_exists($field, $payload)) {
                $address[$field] = $payload[$field];
            }
        }

        return $address;
    }

    private function delegateToPerfil(string $method, array $payload, string $legacyType): Response
    {
        LogService::info('Delegando rota legada /api/config para /api/perfil', [
            'user_id' => $this->userId,
            'type' => $legacyType,
            'method' => $method,
        ]);

        $previousPost = $_POST;
        $_POST = $payload;

        try {
            $controller = new UserPerfilController($this->perfilService, $this->validator);

            return $controller->{$method}();
        } finally {
            $_POST = $previousPost;
        }
    }
}
