<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Models\AssinaturaUsuario;
use Application\Models\Notificacao;
use Application\Models\Usuario;
use Application\Services\Mail\EmailTemplate;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Serviço para gerenciar expiração de assinaturas PRO.
 * 
 * Responsabilidades:
 * - Verificar assinaturas vencidas
 * - Criar notificações no sino
 * - Enviar emails de aviso
 * - Bloquear acesso após período de carência (3 dias)
 */
class SubscriptionExpirationService
{
    private MailService $mail;

    /** Dias de carência após vencimento antes de bloquear */
    public const GRACE_PERIOD_DAYS = 3;

    public function __construct(?MailService $mail = null)
    {
        $this->mail = $mail ?? new MailService();
    }

    /**
     * Processa todas as assinaturas vencidas.
     * Deve ser chamado periodicamente (cron job).
     * 
     * @return array Estatísticas do processamento
     */
    public function processExpiredSubscriptions(): array
    {
        $stats = [
            'checked' => 0,
            'notified' => 0,
            'blocked' => 0,
            'emails_sent' => 0,
            'errors' => [],
        ];

        try {
            // Busca assinaturas PRO ativas que venceram
            $expiredSubscriptions = AssinaturaUsuario::query()
                ->where('status', AssinaturaUsuario::ST_ACTIVE)
                ->whereHas('plano', fn($q) => $q->where('code', 'pro'))
                ->where('renova_em', '<', Carbon::now())
                ->with(['usuario', 'plano'])
                ->get();

            $stats['checked'] = $expiredSubscriptions->count();

            foreach ($expiredSubscriptions as $subscription) {
                try {
                    $this->processExpiredSubscription($subscription, $stats);
                } catch (\Throwable $e) {
                    $stats['errors'][] = [
                        'subscription_id' => $subscription->id,
                        'user_id' => $subscription->user_id,
                        'error' => $e->getMessage(),
                    ];
                    LogService::error('[SubscriptionExpiration] Erro ao processar assinatura', [
                        'subscription_id' => $subscription->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            LogService::info('[SubscriptionExpiration] Processamento concluído', $stats);
        } catch (\Throwable $e) {
            $stats['errors'][] = ['general' => $e->getMessage()];
            LogService::error('[SubscriptionExpiration] Erro geral', ['error' => $e->getMessage()]);
        }

        return $stats;
    }

    /**
     * Processa uma assinatura vencida específica.
     */
    private function processExpiredSubscription(AssinaturaUsuario $subscription, array &$stats): void
    {
        $usuario = $subscription->usuario;
        if (!$usuario) {
            return;
        }

        $expiredAt = Carbon::parse($subscription->renova_em);
        $daysSinceExpiry = (int) $expiredAt->diffInDays(Carbon::now());

        // Verifica se já existe notificação de vencimento para esta assinatura
        $alreadyNotified = $this->hasExpirationNotification($usuario->id, $subscription->id);

        // Se ainda não notificou, cria notificação e envia email
        if (!$alreadyNotified) {
            $this->createExpirationNotification($usuario, $subscription, $daysSinceExpiry);
            $stats['notified']++;

            // Envia email
            if ($this->sendExpirationEmail($usuario, $subscription, $daysSinceExpiry)) {
                $stats['emails_sent']++;
            }
        }

        // Se passou do período de carência (3 dias), bloqueia
        if ($daysSinceExpiry >= self::GRACE_PERIOD_DAYS) {
            $this->blockSubscription($subscription);
            $stats['blocked']++;

            // Notifica sobre bloqueio se ainda não fez
            if (!$this->hasBlockedNotification($usuario->id, $subscription->id)) {
                $this->createBlockedNotification($usuario, $subscription);
                $this->sendBlockedEmail($usuario, $subscription);
            }
        }
    }

    /**
     * Verifica se o usuário já foi notificado sobre vencimento desta assinatura.
     */
    private function hasExpirationNotification(int $userId, int $subscriptionId): bool
    {
        return Notificacao::where('user_id', $userId)
            ->where('tipo', 'subscription_expired')
            ->where('link', 'like', "%subscription_id={$subscriptionId}%")
            ->exists();
    }

    /**
     * Verifica se o usuário já foi notificado sobre bloqueio desta assinatura.
     */
    private function hasBlockedNotification(int $userId, int $subscriptionId): bool
    {
        return Notificacao::where('user_id', $userId)
            ->where('tipo', 'subscription_blocked')
            ->where('link', 'like', "%subscription_id={$subscriptionId}%")
            ->exists();
    }

    /**
     * Cria notificação de vencimento no sino.
     */
    private function createExpirationNotification(Usuario $usuario, AssinaturaUsuario $subscription, int $daysSinceExpiry): void
    {
        $graceDaysLeft = max(0, self::GRACE_PERIOD_DAYS - $daysSinceExpiry);

        Notificacao::create([
            'user_id' => $usuario->id,
            'tipo' => 'subscription_expired',
            'titulo' => '⚠️ Seu plano PRO venceu!',
            'mensagem' => $graceDaysLeft > 0
                ? "Seu plano PRO venceu. Você tem {$graceDaysLeft} dia(s) para renovar antes de perder o acesso aos recursos premium."
                : "Seu plano PRO venceu e os recursos premium foram desativados. Renove agora para continuar usando!",
            'lida' => false,
            'link' => '/billing?subscription_id=' . $subscription->id,
        ]);

        LogService::info('[SubscriptionExpiration] Notificação de vencimento criada', [
            'user_id' => $usuario->id,
            'subscription_id' => $subscription->id,
        ]);
    }

    /**
     * Cria notificação de bloqueio no sino.
     */
    private function createBlockedNotification(Usuario $usuario, AssinaturaUsuario $subscription): void
    {
        Notificacao::create([
            'user_id' => $usuario->id,
            'tipo' => 'subscription_blocked',
            'titulo' => '🔒 Plano PRO desativado',
            'mensagem' => 'Seu período de carência expirou e os recursos PRO foram desativados. Renove sua assinatura para recuperar o acesso completo.',
            'lida' => false,
            'link' => '/billing?subscription_id=' . $subscription->id,
        ]);

        LogService::info('[SubscriptionExpiration] Notificação de bloqueio criada', [
            'user_id' => $usuario->id,
            'subscription_id' => $subscription->id,
        ]);
    }

    /**
     * Envia email de vencimento.
     */
    private function sendExpirationEmail(Usuario $usuario, AssinaturaUsuario $subscription, int $daysSinceExpiry): bool
    {
        if (!$this->mail->isConfigured()) {
            LogService::warning('[SubscriptionExpiration] Email não configurado');
            return false;
        }

        $graceDaysLeft = max(0, self::GRACE_PERIOD_DAYS - $daysSinceExpiry);
        $nomeUsuario = trim((string)($usuario->primeiro_nome ?? $usuario->nome ?? 'Cliente'));
        $dataVencimento = Carbon::parse($subscription->renova_em)->format('d/m/Y');

        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : rtrim($_ENV['APP_URL'] ?? '', '/');
        $link = $baseUrl ? $baseUrl . '/billing' : '#';

        $subject = '⚠️ Seu plano PRO Lukrato venceu - Renove agora!';

        $content = EmailTemplate::row('Status', EmailTemplate::badge('Plano Vencido'), false);
        $content .= EmailTemplate::row('Data de vencimento', $dataVencimento);

        if ($graceDaysLeft > 0) {
            $content .= EmailTemplate::row(
                'Período de carência',
                "Você ainda tem <strong>{$graceDaysLeft} dia(s)</strong> para renovar antes de perder o acesso aos recursos PRO.",
                false
            );
        } else {
            $content .= EmailTemplate::row(
                'Atenção',
                '<strong>Seu período de carência expirou!</strong> Os recursos PRO foram desativados.',
                false
            );
        }

        $content .= EmailTemplate::row(
            'O que você perde?',
            '• Importação automática de extratos<br>• Relatórios avançados<br>• Categorização inteligente<br>• Suporte prioritário',
            false
        );

        if ($link !== '#') {
            $content .= EmailTemplate::button('Renovar meu plano PRO', $link);
        }

        $html = EmailTemplate::wrap(
            $subject,
            '#e74c3c',
            'Plano PRO Vencido',
            "Olá {$nomeUsuario}, seu plano PRO precisa ser renovado.",
            $content,
            'Você está recebendo este email porque sua assinatura PRO do Lukrato expirou.'
        );

        $text = "Olá {$nomeUsuario},\n\n"
            . "Seu plano PRO Lukrato venceu em {$dataVencimento}.\n\n"
            . ($graceDaysLeft > 0
                ? "Você tem {$graceDaysLeft} dia(s) para renovar antes de perder o acesso aos recursos premium.\n\n"
                : "Seu período de carência expirou e os recursos PRO foram desativados.\n\n")
            . "Renove agora: {$link}\n";

        try {
            return $this->mail->send($usuario->email, $nomeUsuario, $subject, $html, $text);
        } catch (\Throwable $e) {
            LogService::error('[SubscriptionExpiration] Erro ao enviar email de vencimento', [
                'user_id' => $usuario->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Envia email de bloqueio.
     */
    private function sendBlockedEmail(Usuario $usuario, AssinaturaUsuario $subscription): bool
    {
        if (!$this->mail->isConfigured()) {
            return false;
        }

        $nomeUsuario = trim((string)($usuario->primeiro_nome ?? $usuario->nome ?? 'Cliente'));
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : rtrim($_ENV['APP_URL'] ?? '', '/');
        $link = $baseUrl ? $baseUrl . '/billing' : '#';

        $subject = '🔒 Seu plano PRO Lukrato foi desativado';

        $content = EmailTemplate::row('Status', EmailTemplate::badge('PRO Desativado'), false);
        $content .= EmailTemplate::row(
            'O que aconteceu?',
            'Seu período de carência de ' . self::GRACE_PERIOD_DAYS . ' dias após o vencimento expirou e os recursos PRO foram desativados.'
        );
        $content .= EmailTemplate::row(
            'Seus dados estão seguros',
            'Não se preocupe! Todos os seus dados continuam salvos. Ao renovar, você terá acesso completo novamente.'
        );

        if ($link !== '#') {
            $content .= EmailTemplate::button('Reativar meu plano PRO', $link);
        }

        $html = EmailTemplate::wrap(
            $subject,
            '#7f8c8d',
            'Plano PRO Desativado',
            "Olá {$nomeUsuario}, seu plano PRO foi desativado por falta de renovação.",
            $content,
            'Renove sua assinatura para recuperar o acesso aos recursos premium.'
        );

        $text = "Olá {$nomeUsuario},\n\n"
            . "Seu plano PRO Lukrato foi desativado por falta de renovação.\n\n"
            . "Seus dados continuam salvos. Renove para recuperar o acesso completo.\n\n"
            . "Renovar: {$link}\n";

        try {
            return $this->mail->send($usuario->email, $nomeUsuario, $subject, $html, $text);
        } catch (\Throwable $e) {
            LogService::error('[SubscriptionExpiration] Erro ao enviar email de bloqueio', [
                'user_id' => $usuario->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Bloqueia a assinatura (muda status para expired).
     */
    private function blockSubscription(AssinaturaUsuario $subscription): void
    {
        $subscription->status = AssinaturaUsuario::ST_EXPIRED;
        $subscription->save();

        LogService::info('[SubscriptionExpiration] Assinatura bloqueada', [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
        ]);
    }

    /**
     * Verifica se uma assinatura específica está no período de carência.
     * Útil para mostrar avisos na UI.
     */
    public static function isInGracePeriod(AssinaturaUsuario $subscription): bool
    {
        if ($subscription->status !== AssinaturaUsuario::ST_ACTIVE) {
            return false;
        }

        $renewsAt = Carbon::parse($subscription->renova_em);
        if ($renewsAt->isFuture()) {
            return false;
        }

        $daysSinceExpiry = $renewsAt->diffInDays(Carbon::now());
        return $daysSinceExpiry < self::GRACE_PERIOD_DAYS;
    }

    /**
     * Retorna quantos dias restam no período de carência.
     */
    public static function getGraceDaysRemaining(AssinaturaUsuario $subscription): int
    {
        if ($subscription->status !== AssinaturaUsuario::ST_ACTIVE) {
            return 0;
        }

        $renewsAt = Carbon::parse($subscription->renova_em);
        if ($renewsAt->isFuture()) {
            return self::GRACE_PERIOD_DAYS;
        }

        $daysSinceExpiry = $renewsAt->diffInDays(Carbon::now());
        return max(0, self::GRACE_PERIOD_DAYS - (int)$daysSinceExpiry);
    }
}
