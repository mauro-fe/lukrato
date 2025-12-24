<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Contracts\MailServiceInterface;
use Application\Models\Agendamento;
use Application\Models\Usuario;
use Application\Services\Mail\EmailTemplate;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Serviço de envio de emails via SMTP.
 */
class MailService implements MailServiceInterface
{
    private array $config;
    private LoggerInterface $logger;

    public function __construct(array $config = [], ?LoggerInterface $logger = null)
    {
        $defaults = [
            'host'       => $_ENV['MAIL_HOST'] ?? '',
            'username'   => $_ENV['MAIL_USERNAME'] ?? '',
            'password'   => $_ENV['MAIL_PASSWORD'] ?? '',
            'port'       => (int) ($_ENV['MAIL_PORT'] ?? 587),
            'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? PHPMailer::ENCRYPTION_STARTTLS,
            'from_email' => $_ENV['MAIL_FROM'] ?? ($_ENV['MAIL_USERNAME'] ?? 'no-reply@localhost'),
            'from_name'  => $_ENV['MAIL_FROM_NAME'] ?? 'Lukrato',
            'bcc'        => $_ENV['MAIL_BCC'] ?? null,
        ];

        $this->config = array_merge($defaults, $config);
        $this->logger = $logger ?? new NullLogger();
    }

    public function isConfigured(): bool
    {
        return !empty($this->config['host'])
            && !empty($this->config['username'])
            && !empty($this->config['from_email']);
    }

    public function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        ?string $textBody = null,
        array $replyTo = [],
        array $attachments = []
    ): bool {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('Configuracao de SMTP ausente. Verifique as variaveis MAIL_* no .env.');
        }

        $toEmail = trim($toEmail);
        if ($toEmail === '' || !$this->isValidEmail($toEmail)) {
            throw new \InvalidArgumentException('Endereco de email do destinatario invalido: ' . $toEmail);
        }

        $mailer = $this->createMailer();
        $mailer->addAddress($toEmail, $toName);

        if (!empty($replyTo['email']) && $this->isValidEmail($replyTo['email'])) {
            $mailer->addReplyTo($replyTo['email'], $replyTo['name'] ?? '');
        }

        if (!empty($this->config['bcc']) && $this->isValidEmail($this->config['bcc'])) {
            $mailer->addBCC($this->config['bcc']);
        }

        // Adicionar anexos
        foreach ($attachments as $attachment) {
            if (isset($attachment['path']) && file_exists($attachment['path'])) {
                $mailer->addAttachment(
                    $attachment['path'],
                    $attachment['name'] ?? basename($attachment['path'])
                );
            }
        }

        $mailer->Subject = $subject;
        $mailer->Body    = $htmlBody;
        $mailer->AltBody = $textBody ?? strip_tags($htmlBody);

        try {
            $success = $mailer->send();
            
            if ($success) {
                $this->logger->info('[mail] Envio OK', [
                    'to' => $toEmail,
                    'subject' => $subject,
                    'host' => $this->config['host'],
                ]);
            }
            
            return $success;
        } catch (Exception $e) {
            $this->logger->error('[mail] Falha ao enviar', [
                'to' => $toEmail,
                'subject' => $subject,
                'host' => $this->config['host'],
                'port' => $this->config['port'],
                'error' => $mailer->ErrorInfo,
                'exception' => $e->getMessage(),
            ]);
            
            throw new \RuntimeException(
                'Falha ao enviar email: ' . $mailer->ErrorInfo,
                0,
                $e
            );
        }
    }

    public function sendPasswordReset(string $toEmail, string $toName, string $resetLink): bool
    {
        $subject = 'Recuperação de senha - Lukrato';

        $safeName = htmlspecialchars($toName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $content = EmailTemplate::row(
            'O que fazer agora?',
            'Clique no botão abaixo para criar uma nova senha. Por segurança, este link é válido por tempo limitado.'
        );

        $content .= EmailTemplate::button('Criar nova senha', $resetLink);

        $content .= EmailTemplate::row(
            'Não foi você?',
            'Se você não solicitou esta redefinição, pode ignorar este e-mail. Sua senha atual continuará válida.',
            true
        );

        $html = EmailTemplate::wrap(
            $subject,
            '#092741',
            'Redefinição de senha',
            "Olá {$safeName}, recebemos uma solicitação para redefinir sua senha na Lukrato.",
            $content,
            'Este e-mail foi enviado automaticamente pela plataforma Lukrato. Não responda esta mensagem.'
        );

        $text = "Olá {$toName},\n\n"
            . "Recebemos uma solicitação para redefinir sua senha no Lukrato.\n\n"
            . "Para continuar, acesse o link a seguir:\n{$resetLink}\n\n"
            . "Se você não fez essa solicitação, pode ignorar este e-mail.\n";

        return $this->send($toEmail, $toName, $subject, $html, $text);
    }

    public function sendAgendamentoReminder(Agendamento $agendamento, Usuario $usuario): bool
    {
        $titulo = $agendamento->titulo ?? 'Agendamento';
        $dataPagamento = $agendamento->data_pagamento instanceof \DateTimeInterface
            ? $agendamento->data_pagamento->format('d/m/Y H:i')
            : (string) $agendamento->data_pagamento;

        $valor = $agendamento->valor_centavos
            ? 'R$ ' . number_format($agendamento->valor_centavos / 100, 2, ',', '.')
            : '-';

        $nomeUsuario = trim((string) ($usuario->primeiro_nome ?? $usuario->nome ?? ''));

        $baseUrl = defined('BASE_URL')
            ? rtrim(BASE_URL, '/')
            : rtrim($_ENV['APP_URL'] ?? '', '/');
        $link = $baseUrl ? $baseUrl . '/agendamentos' : '#';

        $subject = 'Lembrete de pagamento: ' . $titulo;

        $content = EmailTemplate::row('Título', $titulo);
        $content .= EmailTemplate::row('Data e hora programadas', $dataPagamento);
        $content .= EmailTemplate::row('Valor', EmailTemplate::badge($valor), false);
        $content .= EmailTemplate::row(
            'Observação',
            'Este lembrete foi configurado em seu painel da Lukrato.'
        );

        if ($link !== '#') {
            $content .= EmailTemplate::button('Abrir painel', $link, true);
        }

        $html = EmailTemplate::wrap(
            $subject,
            '#111827',
            'Lembrete de pagamento',
            "Olá {$nomeUsuario}, preparamos este lembrete para você não esquecer.",
            $content,
            'Você está recebendo este aviso porque ativou notificações por e-mail para agendamentos.'
        );

        $text = "Lembrete de pagamento: {$titulo}\n"
            . "Quando: {$dataPagamento}\n"
            . "Valor: {$valor}\n"
            . ($link !== '#' ? "Painel: {$link}\n\n" : "\n")
            . "Você está recebendo este aviso porque ativou notificações por e-mail na Lukrato.";

        return $this->send($usuario->email, $nomeUsuario, $subject, $html, $text);
    }

    public function sendSupportMessage(
        string $fromEmail,
        string $name,
        string $message,
        ?string $phone = null,
        ?string $preferredContact = null
    ): bool {
        if (trim($message) === '') {
            throw new \InvalidArgumentException('A mensagem é obrigatória para o suporte.');
        }

        // Pelo menos um meio de contato (email ou telefone)
        $fromEmail = trim($fromEmail);
        $phone = $phone ? trim($phone) : null;
        
        if ($fromEmail === '' && ($phone === null || $phone === '')) {
            throw new \InvalidArgumentException('Informe ao menos um meio de contato (e-mail ou telefone).');
        }

        $supportEmail = $_ENV['SUPPORT_EMAIL']
            ?? $this->config['from_email']
            ?? ($_ENV['MAIL_FROM'] ?? $_ENV['MAIL_USERNAME'] ?? 'lukratosistema@gmail.com');

        $supportName = 'Suporte Lukrato';
        $subject = '[Suporte Lukrato] Nova mensagem de contato';

        $preferredLabel = 'Não informado';
        if ($preferredContact === 'whatsapp') {
            $preferredLabel = 'WhatsApp';
        } elseif ($preferredContact === 'email') {
            $preferredLabel = 'E-mail';
        }

        $content = EmailTemplate::row('Nome', $name);
        $content .= EmailTemplate::row('E-mail', $fromEmail !== '' ? $fromEmail : 'Não informado');
        $content .= EmailTemplate::row('Telefone', $phone ?? 'Não informado');
        $content .= EmailTemplate::row('Preferência de retorno', $preferredLabel);
        $content .= EmailTemplate::messageBox($message);

        $html = EmailTemplate::wrap(
            $subject,
            '#111827',
            'Nova mensagem de suporte',
            'Um usuário enviou uma mensagem pelo botão de suporte no painel.',
            $content,
            'Este e-mail foi gerado automaticamente pela plataforma Lukrato a partir do botão de suporte.'
        );

        $text = "Nova mensagem de suporte Lukrato\n\n"
            . "Nome: {$name}\n"
            . "Email: " . ($fromEmail !== '' ? $fromEmail : 'Não informado') . "\n"
            . "Telefone: " . ($phone ?? 'Não informado') . "\n"
            . "Preferência de retorno: {$preferredLabel}\n\n"
            . "Mensagem:\n{$message}\n";

        $replyTo = [];
        if ($fromEmail !== '' && $this->isValidEmail($fromEmail)) {
            $replyTo = ['email' => $fromEmail, 'name' => $name];
        }

        return $this->send($supportEmail, $supportName, $subject, $html, $text, $replyTo);
    }

    /**
     * Valida se um email é válido.
     */
    private function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Cria instância configurada do PHPMailer.
     */
    private function createMailer(): PHPMailer
    {
        $mailer = new PHPMailer(true);
        $mailer->isSMTP();
        $mailer->Host       = $this->config['host'];
        $mailer->SMTPAuth   = true;
        $mailer->Username   = $this->config['username'];
        $mailer->Password   = $this->config['password'];
        $mailer->Port       = (int) $this->config['port'];
        $mailer->CharSet    = 'UTF-8';

        // Normaliza encryption: 'tls' => STARTTLS (587), 'ssl' => SMTPS (465)
        $enc = strtolower((string)$this->config['encryption']);
        if ($enc === 'tls') {
            $mailer->SMTPSecure  = PHPMailer::ENCRYPTION_STARTTLS;
            $mailer->SMTPAutoTLS = true;
        } elseif ($enc === 'ssl' || $enc === PHPMailer::ENCRYPTION_SMTPS) {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            if ($mailer->Port === 587) {
                $mailer->Port = 465;
            }
        } else {
            $mailer->SMTPSecure  = PHPMailer::ENCRYPTION_STARTTLS;
            $mailer->SMTPAutoTLS = true;
        }

        $mailer->setFrom($this->config['from_email'], $this->config['from_name']);
        $mailer->isHTML(true);

        // Debug SMTP apenas em modo debug
        if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
            $mailer->SMTPDebug = 2;
            $logger = $this->logger;
            $mailer->Debugoutput = static function ($str) use ($logger) {
                $logger->debug('[SMTP] ' . trim($str));
            };
        }

        return $mailer;
    }
}
