<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Services\MailService;

class ContactController extends BaseController
{
    private MailService $mail;

    public function __construct()
    {
        $this->mail = new MailService();
    }


    public function send()
    {
        $nome     = trim($_POST['nome'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $assunto  = trim($_POST['assunto'] ?? '');
        $mensagem = trim($_POST['mensagem'] ?? '');

        if (!$nome || !$email || !$assunto || !$mensagem) {
            return Response::validationError([
                'message' => 'Preencha os campos obrigatórios.'
            ]);
        }

        // 1) Renderiza o template do e-mail (sem depender de base_path)
        $templatePath = dirname(__DIR__, 3) . '/Views/emails/contact-message.php';

        ob_start();
        require $templatePath; // o template "enxerga" $nome, $email, $whatsapp, $assunto, $mensagem
        $html = ob_get_clean();

        // 2) Para quem você vai mandar (email do Lukrato)
        $to = $_ENV['MAIL_USERNAME']
            ?? $_ENV['MAIL_FROM']
            ?? 'lukratosistema@gmail.com';

        // 3) Reply-To (pra responder direto pro usuário)
        $replyTo = ['email' => $email, 'name' => $nome];

        try {
            $this->mail->send(
                $to,
                'Lukrato',
                '[Contato Lukrato] ' . $assunto,
                $html,
                null,
                $replyTo
            );

            return Response::success([
                'message' => 'Mensagem enviada com sucesso.'
            ]);
        } catch (\Throwable $e) {
            return Response::error(
                'Não foi possível enviar sua mensagem agora. Tente novamente.',
                500
            );
        }
    }
}
