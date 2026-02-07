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

  public function sendAgendamentoReminder(Agendamento $agendamento, Usuario $usuario, string $tipo = 'padrao'): bool
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

    // Personalizar mensagem baseado no tipo de lembrete
    if ($tipo === 'antecedencia') {
      $subject = 'Lembrete: ' . $titulo . ' vence em breve';
      $headerTitle = 'Lembrete antecipado';
      $headerSubtitle = "Olá {$nomeUsuario}, seu pagamento está próximo!";
    } elseif ($tipo === 'horario') {
      $subject = 'Atenção: ' . $titulo . ' vence agora!';
      $headerTitle = 'Pagamento agora!';
      $headerSubtitle = "Olá {$nomeUsuario}, chegou a hora do seu pagamento.";
    } else {
      $subject = 'Lembrete de pagamento: ' . $titulo;
      $headerTitle = 'Lembrete de pagamento';
      $headerSubtitle = "Olá {$nomeUsuario}, preparamos este lembrete para você não esquecer.";
    }

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
      $headerTitle,
      $headerSubtitle,
      $content,
      'Você está recebendo este aviso porque ativou notificações por e-mail para agendamentos.'
    );

    $text = "{$subject}\n"
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
   * Envia email de recompensa para quem FOI INDICADO (ganhou 7 dias PRO)
   */
  public function sendReferralRewardToReferred(string $toEmail, string $userName, int $days = 7): bool
  {
    $firstName = explode(' ', trim($userName))[0];
    $safeFirstName = htmlspecialchars($firstName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    $baseUrl = rtrim($_ENV['APP_URL'] ?? '', '/');
    $dashboardUrl = $baseUrl ? $baseUrl . '/dashboard' : '#';

    $subject = "🎉 Você ganhou {$days} dias de acesso PRO grátis!";

    $content = <<<HTML
      <div style="text-align: center; margin-bottom: 32px;">
        <div style="font-size: 64px; margin-bottom: 16px;">🎁</div>
      </div>

      <p style="font-size: 18px; line-height: 1.8; color: #2c3e50; margin: 0 0 24px 0; text-align: center;">
        <strong>Parabéns, {$safeFirstName}!</strong>
      </p>

      <p style="font-size: 16px; line-height: 1.8; color: #5a6c7d; margin: 0 0 20px 0; text-align: center;">
        Por ter sido indicado(a) por um amigo, você ganhou <strong style="color: #10b981;">{$days} dias de acesso PRO gratuito</strong>!
      </p>

      <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 16px; padding: 24px 32px; margin: 32px 0; text-align: center;">
        <div style="font-size: 32px; margin-bottom: 8px;">👑</div>
        <p style="color: white; font-size: 18px; font-weight: 700; margin: 0 0 8px 0;">
          Acesso PRO Ativado!
        </p>
        <p style="color: rgba(255,255,255,0.9); font-size: 14px; margin: 0;">
          +{$days} dias de funcionalidades premium
        </p>
      </div>

      <p style="font-size: 15px; line-height: 1.8; color: #5a6c7d; margin: 0 0 20px 0;">
        Com o acesso PRO você pode:
      </p>

      <ul style="font-size: 14px; line-height: 2; color: #5a6c7d; margin: 0 0 24px 20px; padding: 0;">
        <li>📊 Lançamentos ilimitados</li>
        <li>💳 Múltiplos cartões de crédito</li>
        <li>📈 Relatórios avançados</li>
        <li>🎯 Metas financeiras</li>
        <li>⭐ Pontos em dobro na gamificação</li>
      </ul>

      <div style="text-align: center; margin: 32px 0;">
        <a href="{$dashboardUrl}" 
           style="display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); 
                  color: #ffffff; text-decoration: none; border-radius: 10px; font-weight: 600; 
                  font-size: 16px; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.4);">
          Acessar meu painel 🚀
        </a>
      </div>

      <div style="border-top: 1px solid #e5e7eb; padding-top: 24px; margin-top: 32px;">
        <p style="font-size: 14px; color: #7f8c8d; line-height: 1.6; margin: 0; text-align: center;">
          💡 <strong>Dica:</strong> Você também pode indicar amigos e ganhar ainda mais dias PRO!
        </p>
      </div>
HTML;

    $html = EmailTemplate::wrap(
      $subject,
      'linear-gradient(135deg, #10b981 0%, #059669 100%)',
      "Presente para você! 🎁",
      'Você ganhou dias de acesso PRO no Lukrato',
      $content,
      'Você recebeu este email porque verificou sua conta no Lukrato e foi indicado por um amigo. © ' . date('Y') . ' Lukrato'
    );

    $text = <<<TEXT
Parabéns, {$firstName}! 🎉

Você ganhou {$days} dias de acesso PRO gratuito por ter sido indicado(a) por um amigo!

Com o acesso PRO você pode:
- Lançamentos ilimitados
- Múltiplos cartões de crédito
- Relatórios avançados
- Metas financeiras
- Pontos em dobro na gamificação

Acesse seu painel: {$dashboardUrl}

Dica: Você também pode indicar amigos e ganhar ainda mais dias PRO!

Atenciosamente,
Time Lukrato
TEXT;

    return $this->send($toEmail, $userName, $subject, $html, $text);
  }

  /**
   * Envia email de recompensa para quem INDICOU (ganhou 15 dias PRO)
   */
  public function sendReferralRewardToReferrer(
    string $toEmail,
    string $userName,
    string $referredName,
    int $days = 15
  ): bool {
    $firstName = explode(' ', trim($userName))[0];
    $safeFirstName = htmlspecialchars($firstName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeReferredName = htmlspecialchars($referredName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    $baseUrl = rtrim($_ENV['APP_URL'] ?? '', '/');
    $dashboardUrl = $baseUrl ? $baseUrl . '/dashboard' : '#';
    $referralUrl = $baseUrl ? $baseUrl . '/indicar' : '#';

    $subject = "🎁 {$referredName} verificou o email - Você ganhou {$days} dias PRO!";

    $content = <<<HTML
      <div style="text-align: center; margin-bottom: 32px;">
        <div style="font-size: 64px; margin-bottom: 16px;">🎉</div>
      </div>

      <p style="font-size: 18px; line-height: 1.8; color: #2c3e50; margin: 0 0 24px 0; text-align: center;">
        <strong>Ótimas notícias, {$safeFirstName}!</strong>
      </p>

      <p style="font-size: 16px; line-height: 1.8; color: #5a6c7d; margin: 0 0 20px 0; text-align: center;">
        <strong style="color: #3b82f6;">{$safeReferredName}</strong> verificou o email e agora você ganhou 
        <strong style="color: #10b981;">{$days} dias de acesso PRO gratuito</strong>!
      </p>

      <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 16px; padding: 24px 32px; margin: 32px 0; text-align: center;">
        <div style="font-size: 32px; margin-bottom: 8px;">👥</div>
        <p style="color: white; font-size: 18px; font-weight: 700; margin: 0 0 8px 0;">
          +{$days} dias PRO adicionados!
        </p>
        <p style="color: rgba(255,255,255,0.9); font-size: 14px; margin: 0;">
          Obrigado por indicar amigos para o Lukrato
        </p>
      </div>

      <p style="font-size: 15px; line-height: 1.8; color: #5a6c7d; margin: 0 0 24px 0; text-align: center;">
        Continue indicando amigos e ganhe <strong>{$days} dias PRO</strong> para cada um que se cadastrar e verificar o email!
      </p>

      <div style="text-align: center; margin: 32px 0;">
        <a href="{$referralUrl}" 
           style="display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); 
                  color: #ffffff; text-decoration: none; border-radius: 10px; font-weight: 600; 
                  font-size: 16px; box-shadow: 0 4px 14px rgba(59, 130, 246, 0.4); margin-right: 12px;">
          Indicar mais amigos 👥
        </a>
      </div>

      <div style="border-top: 1px solid #e5e7eb; padding-top: 24px; margin-top: 32px;">
        <p style="font-size: 14px; color: #7f8c8d; line-height: 1.6; margin: 0; text-align: center;">
          🏆 <strong>Seu programa de indicações:</strong> Você ganha {$days} dias PRO por cada amigo que indicar!
        </p>
      </div>
HTML;

    $html = EmailTemplate::wrap(
      $subject,
      'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)',
      "Sua indicação deu certo! 🎁",
      "{$safeReferredName} verificou o email e você foi recompensado",
      $content,
      'Você recebeu este email porque indicou um amigo para o Lukrato. © ' . date('Y') . ' Lukrato'
    );

    $text = <<<TEXT
Ótimas notícias, {$firstName}! 🎉

{$referredName} verificou o email e agora você ganhou {$days} dias de acesso PRO gratuito!

Continue indicando amigos e ganhe {$days} dias PRO para cada um que se cadastrar e verificar o email!

Acesse seu painel de indicações: {$referralUrl}

Atenciosamente,
Time Lukrato
TEXT;

    return $this->send($toEmail, $userName, $subject, $html, $text);
  }

  /**
   * Envia email de confirmação de assinatura PRO ativada
   */
  public function sendSubscriptionConfirmation(
    string $toEmail,
    string $userName,
    string $planoNome = 'PRO',
    ?string $renovaEm = null,
    ?float $valor = null
  ): bool {
    $firstName = explode(' ', trim($userName))[0];
    $safeFirstName = htmlspecialchars($firstName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safePlanoNome = htmlspecialchars($planoNome, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    $baseUrl = rtrim($_ENV['APP_URL'] ?? '', '/');
    $dashboardUrl = $baseUrl ? $baseUrl . '/dashboard' : '#';
    $billingUrl = $baseUrl ? $baseUrl . '/billing' : '#';

    $subject = "✅ Pagamento confirmado - Lukrato {$safePlanoNome} ativado!";

    // Formatar data de renovação
    $renovaFormatada = '';
    if ($renovaEm) {
      try {
        $data = new \DateTime($renovaEm);
        $renovaFormatada = $data->format('d/m/Y');
      } catch (\Throwable $e) {
        $renovaFormatada = $renovaEm;
      }
    }

    // Formatar valor
    $valorFormatado = '';
    if ($valor) {
      $valorFormatado = 'R$ ' . number_format($valor, 2, ',', '.');
    }

    $content = <<<HTML
      <div style="text-align: center; margin-bottom: 32px;">
        <div style="font-size: 64px; margin-bottom: 16px;">🎉</div>
      </div>

      <p style="font-size: 18px; line-height: 1.8; color: #2c3e50; margin: 0 0 24px 0; text-align: center;">
        <strong>Pagamento confirmado, {$safeFirstName}!</strong>
      </p>

      <p style="font-size: 16px; line-height: 1.8; color: #5a6c7d; margin: 0 0 20px 0; text-align: center;">
        Seu acesso ao <strong style="color: #f59e0b;">Lukrato {$safePlanoNome}</strong> foi ativado com sucesso!
      </p>

      <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border-radius: 16px; padding: 24px 32px; margin: 32px 0; text-align: center;">
        <div style="font-size: 32px; margin-bottom: 8px;">👑</div>
        <p style="color: white; font-size: 18px; font-weight: 700; margin: 0 0 8px 0;">
          Lukrato {$safePlanoNome} Ativo!
        </p>
        <p style="color: rgba(255,255,255,0.9); font-size: 14px; margin: 0;">
          Aproveite todos os recursos premium
        </p>
      </div>

      <div style="background: #f8fafc; border-radius: 12px; padding: 20px 24px; margin: 24px 0;">
        <p style="font-size: 14px; color: #64748b; margin: 0 0 12px 0; font-weight: 600;">📋 Detalhes da assinatura:</p>
        <table style="width: 100%; font-size: 14px; color: #475569;">
          <tr>
            <td style="padding: 8px 0;">Plano:</td>
            <td style="padding: 8px 0; text-align: right; font-weight: 600; color: #f59e0b;">{$safePlanoNome}</td>
          </tr>
HTML;

    if ($valorFormatado) {
      $content .= <<<HTML
          <tr>
            <td style="padding: 8px 0;">Valor:</td>
            <td style="padding: 8px 0; text-align: right; font-weight: 600;">{$valorFormatado}</td>
          </tr>
HTML;
    }

    if ($renovaFormatada) {
      $content .= <<<HTML
          <tr>
            <td style="padding: 8px 0;">Próxima renovação:</td>
            <td style="padding: 8px 0; text-align: right;">{$renovaFormatada}</td>
          </tr>
HTML;
    }

    $content .= <<<HTML
        </table>
      </div>

      <p style="font-size: 15px; line-height: 1.8; color: #5a6c7d; margin: 0 0 20px 0;">
        Agora você tem acesso a:
      </p>

      <ul style="font-size: 14px; line-height: 2; color: #5a6c7d; margin: 0 0 24px 20px; padding: 0;">
        <li>📊 Lançamentos ilimitados</li>
        <li>💳 Cartões de crédito ilimitados</li>
        <li>📈 Relatórios avançados e análises</li>
        <li>🎯 Metas financeiras personalizadas</li>
        <li>⭐ Pontos em dobro na gamificação</li>
        <li>🔔 Lembretes e alertas inteligentes</li>
      </ul>

      <div style="text-align: center; margin: 32px 0;">
        <a href="{$dashboardUrl}" 
           style="display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); 
                  color: #ffffff; text-decoration: none; border-radius: 10px; font-weight: 600; 
                  font-size: 16px; box-shadow: 0 4px 14px rgba(245, 158, 11, 0.4);">
          Acessar meu painel 🚀
        </a>
      </div>

      <div style="border-top: 1px solid #e5e7eb; padding-top: 24px; margin-top: 32px;">
        <p style="font-size: 13px; color: #7f8c8d; line-height: 1.6; margin: 0; text-align: center;">
          Gerencie sua assinatura em <a href="{$billingUrl}" style="color: #3498db;">Minha Assinatura</a>
        </p>
      </div>
HTML;

    $html = EmailTemplate::wrap(
      $subject,
      'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
      "Bem-vindo ao Lukrato {$safePlanoNome}! 👑",
      'Seu pagamento foi confirmado e sua assinatura está ativa',
      $content,
      'Você recebeu este email porque assinou o Lukrato PRO. © ' . date('Y') . ' Lukrato'
    );

    $text = <<<TEXT
Pagamento confirmado, {$firstName}! 🎉

Seu acesso ao Lukrato {$planoNome} foi ativado com sucesso!

Detalhes da assinatura:
- Plano: {$planoNome}
TEXT;

    if ($valorFormatado) {
      $text .= "\n- Valor: {$valorFormatado}";
    }

    if ($renovaFormatada) {
      $text .= "\n- Próxima renovação: {$renovaFormatada}";
    }

    $text .= <<<TEXT


Agora você tem acesso a:
- Lançamentos ilimitados
- Cartões de crédito ilimitados  
- Relatórios avançados e análises
- Metas financeiras personalizadas
- Pontos em dobro na gamificação
- Lembretes e alertas inteligentes

Acesse seu painel: {$dashboardUrl}

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
