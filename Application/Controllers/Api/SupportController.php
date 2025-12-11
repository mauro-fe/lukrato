<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Usuario;
use Application\Models\Telefone;
use Application\Services\MailService;

class SupportController extends BaseController
{
    public function send(): void
    {
        $this->requireAuth();

        $data    = json_decode(file_get_contents('php://input'), true) ?? [];
        $message = trim($data['message'] ?? '');
        $retorno = $data['retorno'] ?? null; // "email" ou "whatsapp"

        if (!in_array($retorno, ['email', 'whatsapp'], true)) {
            $retorno = null;
        }

        if ($message === '' || mb_strlen($message) < 10) {
            (new Response())
                ->jsonBody([
                    'success' => false,
                    'message' => 'Mensagem é obrigatória e deve ter pelo menos 10 caracteres.',
                    'errors'  => ['message' => 'Mensagem é obrigatória e deve ter pelo menos 10 caracteres.'],
                ])
                ->send();
            return;
        }

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

        $nome = trim($usuario->nome ?? 'Usuário Lukrato');

        $email = trim($usuario->email ?? '');

        // Busca telefone + DDD na tabela telefones (com relação ddd)
        $telefoneModel = Telefone::with('ddd')
            ->where('id_usuario', $usuario->id ?? $usuario->id_usuario ?? null)
            ->first();

        $foneFormatado = null;

        if ($telefoneModel) {
            $ddd  = $telefoneModel->ddd->codigo ?? null;   // precisa da relação ddd() no model Telefone
            $num  = trim($telefoneModel->numero ?? '');

            if ($num !== '') {
                $foneFormatado = $ddd
                    ? sprintf('(%s) %s', $ddd, $num)
                    : $num;
            }
        }

        try {
            $mailService = new MailService();

            $mailService->sendSupportMessage(
                $email,
                $nome,
                $message,
                $foneFormatado,
                $retorno
            );

            (new Response())
                ->jsonBody([
                    'success' => true,
                    'message' => 'Mensagem enviada com sucesso. Em breve entraremos em contato.',
                ])
                ->send();
        } catch (\InvalidArgumentException $e) {
            (new Response())
                ->jsonBody([
                    'success' => false,
                    'message' => $e->getMessage(),
                ])
                ->send();
        } catch (\Throwable $e) {
            (new Response())
                ->jsonBody([
                    'success' => false,
                    'message' => 'Não foi possível enviar sua mensagem de suporte. Tente novamente mais tarde.',
                ])
                ->send();
        }
    }
}
