<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Admin;

use Application\Controllers\BaseController;
use Application\Controllers\Api\User\PerfilController as UserPerfilController;
use Application\Providers\PerfilControllerFactory;
use Application\Services\Infrastructure\LogService;
use Application\Services\User\PerfilService;
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

    public function __construct(?PerfilService $perfilService = null)
    {
        parent::__construct();
        $this->perfilService = $perfilService ?? PerfilControllerFactory::createService();
    }

    public function update(): void
    {
        $this->requireAuthApi();

        try {
            $payload = $this->getRequestPayload();

            if ($payload === []) {
                $this->fail('Nenhum dado enviado.', 400);
                return;
            }

            if ($this->isThemePayload($payload)) {
                $this->delegateToPerfil('updateTheme', [
                    'theme' => (string) $payload['theme'],
                ], 'theme');
                return;
            }

            if ($this->containsProfileFields($payload)) {
                $this->delegateToPerfil('update', $this->mergeWithCurrentProfile($payload), 'profile');
                return;
            }

            LogService::warning('Payload legado não suportado em /api/config', [
                'user_id' => $this->userId,
                'keys' => array_keys($payload),
            ]);

            $this->fail(
                'Rota legada de configurações não suporta mais este payload. Use /api/perfil ou /api/perfil/tema.',
                410
            );
        } catch (Throwable $e) {
            $this->failAndLog($e, 'Erro ao processar configurações legadas.');
        }
    }

    private PerfilService $perfilService;

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

    private function delegateToPerfil(string $method, array $payload, string $legacyType): void
    {
        LogService::info('Delegando rota legada /api/config para /api/perfil', [
            'user_id' => $this->userId,
            'type' => $legacyType,
            'method' => $method,
        ]);

        $_POST = $payload;

        $controller = new UserPerfilController();
        $controller->{$method}();
    }
}
