<?php

namespace Application\Services\Auth;

use Application\DTOs\Auth\RegistrationDTO;
use Application\Models\Usuario;
use Application\Services\LogService;
use Throwable;

class RegistrationHandler
{
    private RegistrationValidationStrategy $validationStrategy;

    public function __construct()
    {
        $this->validationStrategy = new RegistrationValidationStrategy();
    }

    public function handle(RegistrationDTO $registration): array
    {
        try {
            $this->validationStrategy->validateRegistration($registration);

            $user = $this->createUser($registration);

            LogService::info('User registered', ['user_id' => $user->id]);

            return [
                'usuario' => $user,
                'user_id' => $user->id,
                'message' => 'Conta criada com sucesso!',
                'redirect' => rtrim(BASE_URL, '/') . '/login',
            ];
        } catch (Throwable $e) {
            LogService::error('Registration failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }


    private function createUser(RegistrationDTO $registration): Usuario
    {
        $user = new Usuario();
        $user->nome = $registration->name;
        $user->email = $registration->email;
        $user->senha = $registration->password;
        $user->save();

        return $user;
    }
}
