<?php

declare(strict_types=1);

namespace Application\Controllers\Api\User;

use Application\Config\CommunicationRuntimeConfig;
use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Services\Communication\MailService;

class ContactController extends ApiController
{
    private MailService $mail;
    private CommunicationRuntimeConfig $runtimeConfig;

    public function __construct(
        ?MailService $mail = null,
        ?CommunicationRuntimeConfig $runtimeConfig = null
    )
    {
        parent::__construct();
        $this->mail = $this->resolveOrCreate($mail, MailService::class);
        $this->runtimeConfig = $this->resolveOrCreate($runtimeConfig, CommunicationRuntimeConfig::class);
    }

    public function send(): Response
    {
        $nome = trim((string) $this->getPost('nome', ''));
        $email = trim((string) $this->getPost('email', ''));
        $whatsapp = trim((string) $this->getPost('whatsapp', ''));
        $assunto = trim((string) $this->getPost('assunto', ''));
        $mensagem = trim((string) $this->getPost('mensagem', ''));

        if (!$nome || !$email || !$assunto || !$mensagem) {
            return Response::validationErrorResponse([
                'message' => 'Preencha os campos obrigatórios.',
            ]);
        }

        $templatePath = dirname(__DIR__, 4) . '/views/emails/contact-message.php';

        ob_start();
        require $templatePath;
        $html = ob_get_clean();

        $to = $this->runtimeConfig->mailInboxEmail();

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

            return Response::successResponse([
                'message' => 'Mensagem enviada com sucesso.',
            ]);
        } catch (\Throwable $e) {
            return Response::errorResponse(
                'Não foi possível enviar sua mensagem agora. Tente novamente.',
                500
            );
        }
    }
}
