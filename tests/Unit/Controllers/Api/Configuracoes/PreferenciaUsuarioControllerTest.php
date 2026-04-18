<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Configuracoes;

use Application\Controllers\Api\Configuracoes\PreferenciaUsuarioController;
use Application\Core\Exceptions\AuthException;
use Application\Models\Usuario;
use Application\UseCases\Configuracoes\PreferenciasUsuarioUseCase;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class PreferenciaUsuarioControllerTest extends TestCase
{
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';
    }

    protected function tearDown(): void
    {
        $_POST = [];
        unset($_SERVER['REQUEST_METHOD']);
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testUpdateReturnsValidationErrorForInvalidTheme(): void
    {
        $this->seedAuthenticatedUserSession(41, 'Theme User');
        $_POST['theme'] = 'sepia';

        $controller = new PreferenciaUsuarioController();

        $response = $controller->update();

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => [
                'theme' => 'Deve ser: light, dark ou system.',
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testUpdateReturnsThemeSuccessContractPayload(): void
    {
        $this->seedAuthenticatedUserSession(41, 'Theme User');
        $_POST = ['theme' => 'dark'];

        $useCase = $this->createMock(PreferenciasUsuarioUseCase::class);
        $useCase
            ->expects($this->once())
            ->method('updateTheme')
            ->with(41, ['theme' => 'dark'])
            ->willReturn([
                'success' => true,
                'message' => 'Success',
                'data' => [
                    'message' => 'Preferência de tema atualizada.',
                    'theme' => 'dark',
                ],
                'status' => 200,
            ]);

        $controller = new PreferenciaUsuarioController($useCase);

        $response = $controller->update();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'message' => 'Preferência de tema atualizada.',
                'theme' => 'dark',
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testUpdateDisplayNameReturnsValidationErrorWhenDisplayNameIsMissing(): void
    {
        $this->seedAuthenticatedUserSession(41, 'Theme User');
        $_POST = ['display_name' => ''];

        $controller = new PreferenciaUsuarioController();

        $response = $controller->updateDisplayName();

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => [
                'display_name' => 'Digite como prefere ser chamado.',
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testUpdateDisplayNameReturnsSuccessContractPayload(): void
    {
        $this->seedAuthenticatedUserSession(41, 'Theme User');
        $_POST = ['display_name' => 'Maria Silva'];

        $useCase = $this->createMock(PreferenciasUsuarioUseCase::class);
        $useCase
            ->expects($this->once())
            ->method('updateDisplayName')
            ->with(41, ['display_name' => 'Maria Silva'])
            ->willReturn([
                'success' => true,
                'message' => 'Success',
                'data' => [
                    'message' => 'Nome de exibição salvo.',
                    'display_name' => 'Maria Silva',
                    'first_name' => 'Maria',
                ],
                'status' => 200,
            ]);

        $controller = new PreferenciaUsuarioController($useCase);

        $response = $controller->updateDisplayName();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'message' => 'Nome de exibição salvo.',
                'display_name' => 'Maria Silva',
                'first_name' => 'Maria',
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testShowReturnsThemeContractPayload(): void
    {
        $this->seedAuthenticatedUserSession(41, 'Theme User');
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $useCase = $this->createMock(PreferenciasUsuarioUseCase::class);
        $useCase
            ->expects($this->once())
            ->method('showTheme')
            ->with(41)
            ->willReturn([
                'success' => true,
                'message' => 'Success',
                'data' => [
                    'theme' => 'dark',
                ],
                'status' => 200,
            ]);

        $controller = new PreferenciaUsuarioController($useCase);

        $response = $controller->show();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'theme' => 'dark',
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testShowHelpPreferencesReturnsContractPayload(): void
    {
        $this->seedAuthenticatedUserSession(41, 'Theme User');
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $useCase = $this->createMock(PreferenciasUsuarioUseCase::class);
        $useCase
            ->expects($this->once())
            ->method('showHelpPreferences')
            ->with(41)
            ->willReturn([
                'success' => true,
                'message' => 'Success',
                'data' => [
                    'preferences' => [
                        'settings' => [
                            'auto_offer' => true,
                        ],
                        'tour_completed' => [
                            'dashboard' => 'v2',
                        ],
                        'offer_dismissed' => [],
                        'tips_seen' => [
                            'perfil' => 'v1',
                        ],
                    ],
                ],
                'status' => 200,
            ]);

        $controller = new PreferenciaUsuarioController($useCase);

        $response = $controller->showHelpPreferences();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'preferences' => [
                    'settings' => [
                        'auto_offer' => true,
                    ],
                    'tour_completed' => [
                        'dashboard' => 'v2',
                    ],
                    'offer_dismissed' => [],
                    'tips_seen' => [
                        'perfil' => 'v1',
                    ],
                ],
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testUpdateHelpPreferencesReturnsValidationErrorForInvalidAction(): void
    {
        $this->seedAuthenticatedUserSession(41, 'Theme User');
        $_POST = ['action' => 'unknown'];

        $controller = new PreferenciaUsuarioController();

        $response = $controller->updateHelpPreferences();

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => [
                'action' => 'Ação de ajuda inválida.',
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testUpdateHelpPreferencesReturnsSuccessContractPayload(): void
    {
        $this->seedAuthenticatedUserSession(41, 'Theme User');
        $_POST = [
            'action' => 'set_auto_offer',
            'value' => false,
        ];

        $useCase = $this->createMock(PreferenciasUsuarioUseCase::class);
        $useCase
            ->expects($this->once())
            ->method('updateHelpPreferences')
            ->with(41, [
                'action' => 'set_auto_offer',
                'value' => false,
            ])
            ->willReturn([
                'success' => true,
                'message' => 'Preferências de ajuda atualizadas',
                'data' => [
                    'preferences' => [
                        'settings' => [
                            'auto_offer' => false,
                        ],
                        'tour_completed' => [
                            'dashboard' => 'v2',
                        ],
                        'offer_dismissed' => [],
                        'tips_seen' => [
                            'perfil' => 'v1',
                        ],
                    ],
                ],
                'status' => 200,
            ]);

        $controller = new PreferenciaUsuarioController($useCase);

        $response = $controller->updateHelpPreferences();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Preferências de ajuda atualizadas',
            'data' => [
                'preferences' => [
                    'settings' => [
                        'auto_offer' => false,
                    ],
                    'tour_completed' => [
                        'dashboard' => 'v2',
                    ],
                    'offer_dismissed' => [],
                    'tips_seen' => [
                        'perfil' => 'v1',
                    ],
                ],
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testShowUiPreferencesReturnsPageScopedContractPayload(): void
    {
        $this->seedAuthenticatedUserSession(41, 'Theme User');
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $useCase = $this->createMock(PreferenciasUsuarioUseCase::class);
        $useCase
            ->expects($this->once())
            ->method('showUiPreferences')
            ->with(41, 'dashboard')
            ->willReturn([
                'success' => true,
                'message' => 'Success',
                'data' => [
                    'page' => 'dashboard',
                    'preferences' => [
                        'highlightCards' => true,
                        'density' => 'compact',
                    ],
                ],
                'status' => 200,
            ]);

        $controller = new PreferenciaUsuarioController($useCase);

        $response = $controller->showUiPreferences('dashboard');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'page' => 'dashboard',
                'preferences' => [
                    'highlightCards' => true,
                    'density' => 'compact',
                ],
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testUpdateUiPreferencesReturnsValidationErrorForInvalidPreferencesPayload(): void
    {
        $this->seedAuthenticatedUserSession(41, 'Theme User');
        $_POST = [
            'preferences' => 'invalid',
        ];

        $controller = new PreferenciaUsuarioController();

        $response = $controller->updateUiPreferences('dashboard');

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => [
                'preferences' => 'Formato de preferências inválido.',
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testUpdateUiPreferencesReturnsSuccessContractPayload(): void
    {
        $this->seedAuthenticatedUserSession(41, 'Theme User');
        $_POST = [
            'preferences' => [
                'highlightCards' => true,
                'density' => 'compact',
            ],
        ];

        $useCase = $this->createMock(PreferenciasUsuarioUseCase::class);
        $useCase
            ->expects($this->once())
            ->method('updateUiPreferences')
            ->with(41, 'dashboard', [
                'preferences' => [
                    'highlightCards' => true,
                    'density' => 'compact',
                ],
            ])
            ->willReturn([
                'success' => true,
                'message' => 'Preferências de interface atualizadas',
                'data' => [
                    'page' => 'dashboard',
                    'preferences' => [
                        'highlightCards' => true,
                        'density' => 'compact',
                    ],
                ],
                'status' => 200,
            ]);

        $controller = new PreferenciaUsuarioController($useCase);

        $response = $controller->updateUiPreferences('dashboard');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Preferências de interface atualizadas',
            'data' => [
                'page' => 'dashboard',
                'preferences' => [
                    'highlightCards' => true,
                    'density' => 'compact',
                ],
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testBirthdayCheckReturnsContractPayload(): void
    {
        $this->seedAuthenticatedUserSession(41, 'Maria Silva');
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $useCase = $this->createMock(PreferenciasUsuarioUseCase::class);
        $useCase
            ->expects($this->once())
            ->method('birthdayCheck')
            ->with(41)
            ->willReturn([
                'success' => true,
                'message' => 'Success',
                'data' => [
                    'is_birthday' => true,
                    'first_name' => 'Maria',
                    'age' => 32,
                    'full_name' => 'Maria Silva',
                ],
                'status' => 200,
            ]);

        $controller = new PreferenciaUsuarioController($useCase);

        $response = $controller->birthdayCheck();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'is_birthday' => true,
                'first_name' => 'Maria',
                'age' => 32,
                'full_name' => 'Maria Silva',
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testShowThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new PreferenciaUsuarioController();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Não autenticado');

        $controller->show();
    }

    private function seedAuthenticatedUserSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('preferencia-usuario-controller-test');

        $user = new Usuario();
        $user->id = $userId;
        $user->nome = $name;
        $user->is_admin = 0;

        $_SESSION['usuario_logged_in'] = true;
        $_SESSION['user_id'] = $userId;
        $_SESSION['usuario_nome'] = $name;
        $_SESSION['usuario_cache'] = [
            'id' => $userId,
            'data' => $user,
        ];
    }
}
