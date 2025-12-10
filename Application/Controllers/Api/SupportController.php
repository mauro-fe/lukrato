<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Usuario;
use Application\Services\MailService;

class SupportController extends BaseController
{
    public function send(): void
    {
        // Garante que só usuários logados usam o suporte
        $this->requireAuth();

        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $message = trim($data['message'] ?? '');

        if ($message === '') {
            (new Response())
                ->jsonBody([
                    'success' => false,
                    'message' => 'Mensagem é obrigatória.',
                    'errors'  => ['message' => 'Mensagem é obrigatória.'],
                ])
                ->send();
            return;
        }

        // Ajusta para o jeito que você guarda o ID do usuário no BaseController
        $userId = $this->userId;


        if (!$userId) {
            (new Response())
                ->jsonBody([
                    'success' => false,
                    'message' => 'Não foi possível identificar o usuário logado.',
                ])
                ->send();
            return;
        }

        $usuario = Usuario::find($userId);

        if (!$usuario) {
            (new Response())
                ->jsonBody([
                    'success' => false,
                    'message' => 'Usuário não encontrado.',
                ])
                ->send();
            return;
        }

        $nome  = trim($usuario->primeiro_nome ?? $usuario->nome ?? '');
        $email = $usuario->email ?? '';

        if ($email === '') {
            (new Response())
                ->jsonBody([
                    'success' => false,
                    'message' => 'Usuário não possui e-mail cadastrado.',
                ])
                ->send();
            return;
        }

        try {
            $mailService = new MailService();
            $mailService->sendSupportMessage($email, $nome, $message);

            (new Response())
                ->jsonBody([
                    'success' => true,
                    'message' => 'Mensagem enviada com sucesso. Em breve entraremos em contato.',
                ])
                ->send();
        } catch (\Throwable $e) {
            // Aqui você pode logar o erro com LogService
            (new Response())
                ->jsonBody([
                    'success' => false,
                    'message' => 'Não foi possível enviar sua mensagem de suporte. Tente novamente mais tarde.',
                ])
                ->send();
        }
    }
}
