<?php

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\PerfilService;
use Application\DTO\PerfilUpdateDTO;
use Application\Validators\PerfilValidator;
use Application\Providers\PerfilControllerFactory;
use Throwable;


class PerfilController
{
    private PerfilService $perfilService;
    private PerfilValidator $validator;

    public function __construct(
        ?PerfilService $perfilService = null,
        ?PerfilValidator $validator = null
    ) {
        if ($perfilService !== null && $validator !== null) {
            $this->perfilService = $perfilService;
            $this->validator = $validator;
            return;
        }

        [
            $this->perfilService,
            $this->validator
        ] = PerfilControllerFactory::buildDependencies();
    }


    public function show(): void
    {
        try {
            $user = Auth::user();

            if (!$user) {
                Response::error('Não autenticado', 401);
                return;
            }

            $perfil = $this->perfilService->obterPerfil($user->id);

            if (!$perfil) {
                Response::error('Usuário não encontrado', 404);
                return;
            }

            Response::success([
                'user' => $perfil,
            ], 'Perfil carregado');
        } catch (Throwable $e) {
            Response::error('Erro interno', 500, [
                'exception' => $e->getMessage()
            ]);
        }
    }


    public function update(): void
    {
        try {
            $user = Auth::user();

            if (!$user) {
                Response::error('Não autenticado', 401);
                return;
            }

            $dto = PerfilUpdateDTO::fromRequest($_POST);

            $errors = $this->validator->validate($dto, $user->id);

            if (!empty($errors)) {
                Response::validationError($errors);
                return;
            }

            $perfilAtualizado = $this->perfilService->atualizarPerfil($user->id, $dto);

            Response::success([
                'message' => 'Perfil atualizado com sucesso',
                'user' => $perfilAtualizado,
            ]);
        } catch (Throwable $e) {
            $statusCode = $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
                ? 404
                : 500;

            Response::error(
                'Erro interno ao atualizar perfil',
                $statusCode,
                ['exception' => $e->getMessage()]
            );
        }
    }
}