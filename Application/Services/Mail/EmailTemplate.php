<?php

declare(strict_types=1);

namespace Application\Services\Mail;

/**
 * Template base para emails HTML.
 */
class EmailTemplate
{
    /**
     * Gera estrutura HTML base para emails.
     */
    public static function wrap(string $title, string $headerBg, string $headerTitle, string $headerSubtitle, string $content, string $footer): string
    {
        $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeHeaderBg = htmlspecialchars($headerBg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeHeaderTitle = htmlspecialchars($headerTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeHeaderSubtitle = htmlspecialchars($headerSubtitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeFooter = htmlspecialchars($footer, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$safeTitle}</title>
  <style>
    body{font-family:Arial,sans-serif;background:#f5f7fb;margin:0;padding:24px;color:#1f2933;}
    .card{max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;box-shadow:0 12px 32px rgba(15,23,42,0.12);overflow:hidden;}
    .header{background:{$safeHeaderBg};color:#ffffff;padding:32px 28px;}
    .header h1{margin:0;font-size:20px;}
    .header p{margin:8px 0 0;font-size:14px;opacity:0.85;}
    .content{padding:28px;}
    .row{margin-bottom:18px;}
    .label{display:block;font-size:11px;text-transform:uppercase;color:#6b7280;letter-spacing:0.08em;margin-bottom:4px;font-weight:600;}
    .value{font-size:16px;color:#1f2933;line-height:1.5;}
    .badge{display:inline-flex;align-items:center;background:#1f2933;color:#ffffff;font-size:13px;border-radius:999px;padding:6px 14px;font-weight:600;}
    .cta{margin-top:24px;text-align:center;}
    .btn{display:inline-block;padding:14px 28px;border-radius:10px;background:#e67e22;color:#ffffff !important;text-decoration:none;font-weight:600;transition:background 0.2s;}
    .btn:hover{background:#f39c12;}
    .btn-dark{background:#111827;}
    .btn-dark:hover{background:#1f2937;}
    .message-box{margin-top:8px;padding:14px 16px;border-radius:8px;background:#f9fafb;border:1px solid #e5e7eb;white-space:pre-wrap;line-height:1.6;}
    .footer{background:#f3f4f6;padding:18px 28px;font-size:12px;color:#6b7280;text-align:center;line-height:1.5;}
    @media only screen and (max-width: 600px) {
      body{padding:12px;}
      .header{padding:24px 20px;}
      .content{padding:20px;}
      .footer{padding:14px 20px;}
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="header">
      <h1>{$safeHeaderTitle}</h1>
      <p>{$safeHeaderSubtitle}</p>
    </div>
    <div class="content">
      {$content}
    </div>
    <div class="footer">
      {$safeFooter}
    </div>
  </div>
</body>
</html>
HTML;
    }

    /**
     * Cria uma linha de campo (label + value).
     */
    public static function row(string $label, string $value, bool $escapeValue = true): string
    {
        $safeLabel = htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeValue = $escapeValue 
            ? htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            : $value;

        return <<<HTML
      <div class="row">
        <span class="label">{$safeLabel}</span>
        <span class="value">{$safeValue}</span>
      </div>
HTML;
    }

    /**
     * Cria um botão de call-to-action.
     */
    public static function button(string $text, string $url, bool $dark = false): string
    {
        $safeText = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeUrl = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $btnClass = $dark ? 'btn btn-dark' : 'btn';

        return <<<HTML
      <div class="cta">
        <a class="{$btnClass}" href="{$safeUrl}" target="_blank" rel="noopener">
          {$safeText}
        </a>
      </div>
HTML;
    }

    /**
     * Cria um badge.
     */
    public static function badge(string $text): string
    {
        $safeText = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return "<span class=\"badge\">{$safeText}</span>";
    }

    /**
     * Cria uma caixa de mensagem.
     */
    public static function messageBox(string $message): string
    {
        $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

        return <<<HTML
      <div class="row">
        <span class="label">Mensagem</span>
        <div class="message-box">{$safeMessage}</div>
      </div>
HTML;
    }
}
