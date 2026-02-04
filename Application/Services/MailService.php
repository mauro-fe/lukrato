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
     * Envia email de boas-vindas para novo usuário.
     */
    public function sendWelcomeEmail(string $toEmail, string $userName): bool
    {
        $baseUrl = rtrim($_ENV['APP_URL'] ?? '', '/');
        $dashboardUrl = $baseUrl ? $baseUrl . '/dashboard' : '#';
        $agendamentosUrl = $baseUrl ? $baseUrl . '/agendamentos' : '#';
        $categoriasUrl = $baseUrl ? $baseUrl . '/categorias' : '#';
        $billingUrl = $baseUrl ? $baseUrl . '/billing' : '#';

        $firstName = explode(' ', trim($userName))[0];
        $safeFirstName = htmlspecialchars($firstName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $subject = "🎉 Bem-vindo(a) ao Lukrato, {$firstName}!";

        // Conteúdo do email - texto de boas-vindas criativo e acolhedor
        $content = <<<HTML
      <div style="text-align: center; margin-bottom: 32px;">
        <div style="font-size: 48px; margin-bottom: 16px;">🎉</div>
      </div>

      <p style="font-size: 17px; line-height: 1.8; color: #2c3e50; margin: 0 0 24px 0; text-align: center;">
        <strong>Parabéns!</strong> Sua conta foi criada com sucesso.
      </p>

      <p style="font-size: 15px; line-height: 1.8; color: #5a6c7d; margin: 0 0 20px 0;">
        A partir de agora, você tem em mãos uma ferramenta poderosa para organizar suas finanças 
        de forma simples e inteligente.
      </p>

      <p style="font-size: 15px; line-height: 1.8; color: #5a6c7d; margin: 0 0 20px 0;">
        No Lukrato, você pode acompanhar suas receitas e despesas, gerenciar seus cartões de crédito, 
        criar agendamentos para nunca esquecer um pagamento e muito mais — tudo em um único lugar.
      </p>

      <p style="font-size: 15px; line-height: 1.8; color: #5a6c7d; margin: 0 0 32px 0;">
        Comece agora mesmo e dê o primeiro passo rumo ao controle total das suas finanças. 
        Estamos aqui para te ajudar nessa jornada! 💪
      </p>

      <div style="text-align: center; margin: 32px 0;">
        <a href="{$dashboardUrl}" 
           style="display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #e67e22 0%, #d35400 100%); 
                  color: #ffffff; text-decoration: none; border-radius: 10px; font-weight: 600; 
                  font-size: 16px; box-shadow: 0 4px 14px rgba(230, 126, 34, 0.4);">
          Acessar meu painel →
        </a>
      </div>

      <div style="border-top: 1px solid #e5e7eb; padding-top: 24px; margin-top: 32px; text-align: center;">
        <p style="font-size: 14px; color: #7f8c8d; line-height: 1.6; margin: 0;">
          Dúvidas? Estamos sempre prontos para ajudar.<br>
          Use o botão de suporte no painel ou responda este email.
        </p>
      </div>
HTML;

        $html = EmailTemplate::wrap(
            $subject,
            'linear-gradient(135deg, #e67e22 0%, #d35400 100%)',
            "Olá, {$safeFirstName}! 👋",
            'Sua conta foi criada com sucesso. Vamos começar?',
            $content,
            'Você recebeu este email porque acabou de criar uma conta no Lukrato. © ' . date('Y') . ' Lukrato'
        );

        $text = <<<TEXT
Bem-vindo(a) ao Lukrato, {$firstName}!

Estamos muito felizes em ter você conosco!

Você acabou de dar o primeiro passo para assumir o controle da sua vida financeira.

O Lukrato foi criado para simplificar sua rotina com dinheiro — sem planilhas complicadas e sem dor de cabeça.
Aqui você pode organizar receitas, despesas e cartões de crédito, acompanhando tudo com clareza.

COMO COMEÇAR AGORA:
• Configure suas categorias
• Adicione suas contas bancárias
• Registre seus primeiros lançamentos
• Crie agendamentos e evite surpresas

🎯 Desafio inicial: registre 3 lançamentos hoje e sinta a diferença.

Tudo começa por aqui 👇
Acesse seu painel: {$dashboardUrl}

Se precisar de ajuda, é só responder este e-mail ou usar o botão de suporte dentro do painel.

Conte com a gente,
Time Lukrato 💙
TEXT;

        return $this->send($toEmail, $userName, $subject, $html, $text);
    }

    /**
     * Envia email de verificação de conta.
     */
    public function sendEmailVerification(string $toEmail, string $userName, string $verificationUrl): bool
    {
        $firstName = explode(' ', trim($userName))[0];
        $safeFirstName = htmlspecialchars($firstName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $subject = "✉️ Confirme seu e-mail - Lukrato";

        $content = <<<HTML
      <div style="text-align: center; margin-bottom: 32px;">
        <div style="font-size: 48px; margin-bottom: 16px;">✉️</div>
      </div>

      <p style="font-size: 17px; line-height: 1.8; color: #2c3e50; margin: 0 0 24px 0; text-align: center;">
        <strong>Falta pouco!</strong> Confirme seu e-mail para ativar sua conta.
      </p>

      <p style="font-size: 15px; line-height: 1.8; color: #5a6c7d; margin: 0 0 20px 0;">
        Você está a um passo de começar a organizar suas finanças com o Lukrato. 
        Para garantir a segurança da sua conta, precisamos confirmar que este e-mail é seu.
      </p>

      <p style="font-size: 15px; line-height: 1.8; color: #5a6c7d; margin: 0 0 32px 0;">
        Clique no botão abaixo para verificar seu e-mail. Este link é válido por 24 horas.
      </p>

      <div style="text-align: center; margin: 32px 0;">
        <a href="{$verificationUrl}" 
           style="display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #27ae60 0%, #219a52 100%); 
                  color: #ffffff; text-decoration: none; border-radius: 10px; font-weight: 600; 
                  font-size: 16px; box-shadow: 0 4px 14px rgba(39, 174, 96, 0.4);">
          Verificar meu e-mail ✓
        </a>
      </div>

      <div style="border-top: 1px solid #e5e7eb; padding-top: 24px; margin-top: 32px;">
        <p style="font-size: 13px; color: #7f8c8d; line-height: 1.6; margin: 0;">
          <strong>Se você não criou uma conta no Lukrato</strong>, pode ignorar este e-mail com segurança.
        </p>
        <p style="font-size: 13px; color: #95a5a6; line-height: 1.6; margin: 12px 0 0 0;">
          Se o botão não funcionar, copie e cole este link no seu navegador:<br>
          <span style="word-break: break-all; color: #3498db;">{$verificationUrl}</span>
        </p>
      </div>
HTML;

        $html = EmailTemplate::wrap(
            $subject,
            'linear-gradient(135deg, #27ae60 0%, #219a52 100%)',
            "Olá, {$safeFirstName}! 👋",
            'Confirme seu e-mail para começar a usar o Lukrato',
            $content,
            'Você recebeu este email porque acabou de criar uma conta no Lukrato. © ' . date('Y') . ' Lukrato'
        );

        $text = <<<TEXT
Olá, {$firstName}!

Falta pouco para ativar sua conta no Lukrato!

Para garantir a segurança da sua conta, precisamos confirmar que este e-mail é seu.

Clique no link abaixo para verificar seu e-mail (válido por 24 horas):
{$verificationUrl}

Se você não criou uma conta no Lukrato, pode ignorar este e-mail com segurança.

Atenciosamente,
Time Lukrato
TEXT;

        return $this->send($toEmail, $userName, $subject, $html, $text);
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
