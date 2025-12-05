<?php

namespace Application\Services;

use Application\Models\Agendamento;
use Application\Models\Usuario;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    private array $config;

    public function __construct(array $config = [])
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
        array $replyTo = []
    ): void {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('Configuracao de SMTP ausente. Verifique as variaveis MAIL_* no .env.');
        }

        if (trim($toEmail) === '') {
            throw new \InvalidArgumentException('Endereco de email do destinatario e obrigatorio.');
        }

        $mailer = $this->createMailer();
        $mailer->addAddress($toEmail, $toName);

        if (!empty($replyTo['email'])) {
            $mailer->addReplyTo($replyTo['email'], $replyTo['name'] ?? '');
        }

        if (!empty($this->config['bcc'])) {
            $mailer->addBCC($this->config['bcc']);
        }

        $mailer->Subject = $subject;
        $mailer->Body    = $htmlBody;
        $mailer->AltBody = $textBody ?? strip_tags($htmlBody);

        try {
            $mailer->send();
            // LogService::info('[mail] Envio OK', [
            //     'to' => $toEmail,
            //     'host' => $this->config['host'],
            //     'port' => $this->config['port'],
            //     'enc' => $this->config['encryption'],
            //     'from' => $this->config['from_email'],
            // ]);
        } catch (Exception $e) {
            // LogService::error('[mail] Falha ao enviar', [
            //     'to' => $toEmail,
            //     'host' => $this->config['host'],
            //     'port' => $this->config['port'],
            //     'enc' => $this->config['encryption'],
            //     'from' => $this->config['from_email'],
            //     'error' => $mailer->ErrorInfo,   // motivo direto do PHPMailer
            //     'ex' => $e->getMessage(),        // exceção
            // ]);
            throw $e;
        }
    }


    public function sendAgendamentoReminder(Agendamento $agendamento, Usuario $usuario): void
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

        $html = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>{$subject}</title>
  <style>
    body{font-family:Arial,sans-serif;background:#f5f7fb;margin:0;padding:24px;color:#1f2933;}
    .card{max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;box-shadow:0 12px 32px rgba(15,23,42,0.12);overflow:hidden;}
    .header{background:#111827;color:#ffffff;padding:32px 28px;}
    .header h1{margin:0;font-size:20px;}
    .header p{margin:8px 0 0;font-size:14px;opacity:0.8;}
    .content{padding:28px;}
    .row{margin-bottom:18px;}
    .label{display:block;font-size:11px;text-transform:uppercase;color:#6b7280;letter-spacing:0.08em;margin-bottom:4px;}
    .value{font-size:16px;color:#1f2933;}
    .badge{display:inline-flex;align-items:center;background:#1f2933;color:#ffffff;font-size:12px;border-radius:999px;padding:4px 12px;}
    .cta{margin-top:24px;text-align:center;}
    .btn{display:inline-block;padding:12px 20px;border-radius:10px;background:#111827;color:#ffffff;text-decoration:none;font-weight:600;}
    .footer{background:#f3f4f6;padding:18px 28px;font-size:12px;color:#6b7280;text-align:center;}
  </style>
</head>
<body>
  <div class="card">
    <div class="header">
      <h1>Lembrete de pagamento</h1>
      <p>Ola {$nomeUsuario}, preparamos este lembrete para voce nao esquecer.</p>
    </div>
    <div class="content">
      <div class="row">
        <span class="label">Titulo</span>
        <span class="value">{$titulo}</span>
      </div>
      <div class="row">
        <span class="label">Data e hora programadas</span>
        <span class="value">{$dataPagamento}</span>
      </div>
      <div class="row">
        <span class="label">Valor</span>
        <span class="badge">{$valor}</span>
      </div>
      <div class="row">
        <span class="label">Observacao</span>
        <span class="value">Este lembrete foi configurado em seu painel da Lukrato.</span>
      </div>
      <div class="cta">
        <a class="btn" href="{$link}" target="_blank" rel="noopener">Abrir painel</a>
      </div>
    </div>
    <div class="footer">
      Voce esta recebendo este aviso porque ativou notificacoes por e-mail para agendamentos.
    </div>
  </div>
</body>
</html>
HTML;

        $text = "Lembrete de pagamento: {$titulo}\n"
            . "Quando: {$dataPagamento}\n"
            . "Valor: {$valor}\n"
            . ($link !== '#' ? "Painel: {$link}\n\n" : "\n")
            . "Voce esta recebendo este aviso porque ativou notificacoes por e-mail na Lukrato.";

        $this->send(
            $usuario->email,
            $nomeUsuario,
            $subject,
            $html,
            $text
        );
    }

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
            $mailer->SMTPAutoTLS = true; // sobe para TLS se o servidor suportar
        } elseif ($enc === 'ssl' || $enc === PHPMailer::ENCRYPTION_SMTPS) {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            if ($mailer->Port === 587) {
                $mailer->Port = 465; // porta padrão do SSL
            }
        } else {
            // fallback seguro: STARTTLS
            $mailer->SMTPSecure  = PHPMailer::ENCRYPTION_STARTTLS;
            $mailer->SMTPAutoTLS = true;
        }

        // From deve bater com a conta, a menos que alias esteja verificado
        $mailer->setFrom($this->config['from_email'], $this->config['from_name']);
        $mailer->isHTML(true);

        // Debug SMTP para log (sem expor senha)
        if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
            $mailer->SMTPDebug  = 2; // SERVER
            $mailer->Debugoutput = static function ($str, $level) {
                // LogService::info('[SMTP] ' . trim($str));
            };
        }

        return $mailer;
    }
    public function sendPasswordReset(string $toEmail, string $toName, string $resetLink): void
    {
        $subject = 'Recuperação de senha - Lukrato';

        $safeName = htmlspecialchars($toName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeLink = htmlspecialchars($resetLink, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $html = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>{$subject}</title>
  <style>
    body{font-family:Arial,sans-serif;background:#f5f7fb;margin:0;padding:24px;color:#1f2933;}
    .card{max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;box-shadow:0 12px 32px rgba(15,23,42,0.12);overflow:hidden;}
    .header{background:#092741;color:#ffffff;padding:32px 28px;}
    .header h1{margin:0;font-size:20px;}
    .header p{margin:8px 0 0;font-size:14px;opacity:0.8;}
    .content{padding:28px;}
    .row{margin-bottom:18px;}
    .label{display:block;font-size:11px;text-transform:uppercase;color:#6b7280;letter-spacing:0.08em;margin-bottom:4px;}
    .value{font-size:16px;color:#1f2933;}
    .cta{margin-top:24px;text-align:center;}
    .btn{display:inline-block;padding:12px 20px;border-radius:10px;background:#e67e22;color:#ffffff !important;text-decoration:none;font-weight:600;}
    .btn:hover{background-color: #f39c12;}
    .footer{background:#f3f4f6;padding:18px 28px;font-size:12px;color:#6b7280;text-align:center;}
  </style>
</head>
<body>
  <div class="card">
    <div class="header">
      <h1>Redefinição de senha</h1>
      <p>Olá {$safeName}, recebemos uma solicitação para redefinir sua senha na Lukrato.</p>
    </div>
    <div class="content">
      <div class="row">
        <span class="label">O que fazer agora?</span>
        <span class="value">
          Clique no botão abaixo para criar uma nova senha. 
          Por segurança, este link é válido por tempo limitado.
        </span>
      </div>
      <div class="cta">
        <a class="btn" href="{$safeLink}" target="_blank" rel="noopener">
          Criar nova senha
        </a>
      </div>
      <div class="row" style="margin-top:24px;font-size:12px;color:#6b7280;">
        <span class="label">Não foi você?</span>
        <span class="value">
          Se você não solicitou esta redefinição, pode ignorar este e-mail. 
          Sua senha atual continuará válida.
        </span>
      </div>
    </div>
    <div class="footer">
      Este e-mail foi enviado automaticamente pela plataforma Lukrato. Não responda esta mensagem.
    </div>
  </div>
</body>
</html>
HTML;

        $text = "Olá {$toName},\n\n"
            . "Recebemos uma solicitação para redefinir sua senha no Lukrato.\n\n"
            . "Para continuar, acesse o link a seguir:\n{$resetLink}\n\n"
            . "Se você não fez essa solicitação, pode ignorar este e-mail.\n";

        $this->send(
            $toEmail,
            $toName,
            $subject,
            $html,
            $text
        );
    }
}