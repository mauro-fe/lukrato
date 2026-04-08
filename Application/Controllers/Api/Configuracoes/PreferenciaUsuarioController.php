<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Configuracoes;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\UseCases\Configuracoes\PreferenciasUsuarioUseCase;

class PreferenciaUsuarioController extends ApiController
{
    private PreferenciasUsuarioUseCase $useCase;

    public function __construct(?PreferenciasUsuarioUseCase $useCase = null)
    {
        parent::__construct();
        $this->useCase = $this->resolveOrCreate($useCase, PreferenciasUsuarioUseCase::class);
    }

    public function show(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        return $this->respondUseCase($this->useCase->showTheme($userId));
    }

    public function update(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        return $this->respondUseCase($this->useCase->updateTheme($userId, $this->getRequestPayload()));
    }

    public function updateDisplayName(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        return $this->respondUseCase($this->useCase->updateDisplayName($userId, $this->getRequestPayload()));
    }

    public function showHelpPreferences(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        return $this->respondUseCase($this->useCase->showHelpPreferences($userId));
    }

    public function updateHelpPreferences(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        return $this->respondUseCase($this->useCase->updateHelpPreferences($userId, $this->getRequestPayload()));
    }

    public function showUiPreferences(string $page): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        return $this->respondUseCase($this->useCase->showUiPreferences($userId, $page));
    }

    public function updateUiPreferences(string $page): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        return $this->respondUseCase($this->useCase->updateUiPreferences($userId, $page, $this->getRequestPayload()));
    }

    public function birthdayCheck(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        return $this->respondUseCase($this->useCase->birthdayCheck($userId));
    }

    /**
     * @param array<string,mixed> $result
     */
    private function respondUseCase(array $result): Response
    {
        return $this->respondApiWorkflowResult(
            $result,
            useWorkflowFailureOnFailure: false,
            preserveSuccessMeta: true
        );
    }
}
