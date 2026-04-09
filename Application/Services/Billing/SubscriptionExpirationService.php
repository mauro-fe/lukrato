<?php

declare(strict_types=1);

namespace Application\Services\Billing;

use Application\Config\BillingRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Models\AssinaturaUsuario;
use Application\Models\Notificacao;
use Application\Models\Usuario;
use Application\Services\Mail\EmailTemplate;
use Application\Enums\LogLevel;
use Application\Enums\LogCategory;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;
use Application\Services\Infrastructure\LogService;
use Application\Services\Communication\MailService;

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
    private BillingRuntimeConfig $runtimeConfig;

    /** Dias de carência após vencimento antes de bloquear */
    public const GRACE_PERIOD_DAYS = 3;

    public function __construct(?MailService $mail = null, ?BillingRuntimeConfig $runtimeConfig = null)
    {
        $this->mail = ApplicationContainer::resolveOrNew($mail, MailService::class);
        $this->runtimeConfig = ApplicationContainer::resolveOrNew($runtimeConfig, BillingRuntimeConfig::class);
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
                    LogService::captureException($e, LogCategory::SUBSCRIPTION, [
                        'action' => 'process_expired_subscription',
                        'subscription_id' => $subscription->id,
                        'user_id' => $subscription->user_id,
                    ], $subscription->user_id);
                }
            }

            LogService::info('[SubscriptionExpiration] Processamento concluído', $stats);
        } catch (\Throwable $e) {
            $stats['errors'][] = ['general' => $e->getMessage()];
            LogService::captureException($e, LogCategory::SUBSCRIPTION, [
                'action' => 'process_expired_subscriptions_general',
            ]);
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

        $expiredAt = Carbon::parse($subscription->renova_em)->startOfDay();
        $now = Carbon::now();

        // Calcula data/hora exata de bloqueio (fim do dia do último dia de carência)
        // Exemplo: venceu 19/01, carência 3 dias = bloqueia 22/01 às 23:59:59
        $blockedAt = $expiredAt->copy()->addDays(self::GRACE_PERIOD_DAYS)->endOfDay();

        $daysSinceExpiry = (int) $expiredAt->diffInDays($now);

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

        // Se passou do período de carência (após o horário de bloqueio), bloqueia
        if ($now->isAfter($blockedAt)) {
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

        $link = $this->billingPageLink();

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
            LogService::captureException($e, LogCategory::NOTIFICATION, [
                'action' => 'send_expiration_email',
                'user_id' => $usuario->id,
            ], $usuario->id);
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
        $link = $this->billingPageLink();

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
            LogService::captureException($e, LogCategory::NOTIFICATION, [
                'action' => 'send_blocked_email',
                'user_id' => $usuario->id,
            ], $usuario->id);
            return false;
        }
    }

    private function billingPageLink(): string
    {
        $baseUrl = defined('BASE_URL')
            ? rtrim(BASE_URL, '/')
            : rtrim($this->runtimeConfig->appUrl(), '/');

        return $baseUrl !== '' ? $baseUrl . '/billing' : '#';
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

    /**
     * Calcula a data/hora exata em que o acesso será bloqueado.
     * O bloqueio acontece às 23:59:59 do último dia de carência.
     * 
     * Exemplo: Se venceu dia 19/01, carência de 3 dias = bloqueia 22/01 às 23:59:59
     */
    public static function getBlockedAt(AssinaturaUsuario $subscription): ?Carbon
    {
        if (!$subscription->renova_em) {
            return null;
        }

        $renewsAt = Carbon::parse($subscription->renova_em)->startOfDay();

        // Bloqueia no final do dia após o período de carência
        // Se venceu dia 19, adiciona 3 dias = dia 22 às 23:59:59
        return $renewsAt->copy()->addDays(self::GRACE_PERIOD_DAYS)->endOfDay();
    }

    /**
     * Verifica se o período de carência já expirou (deve bloquear).
     * Considera o horário de expiração como 23:59:59 do último dia.
     */
    public static function isGracePeriodExpired(AssinaturaUsuario $subscription): bool
    {
        $blockedAt = self::getBlockedAt($subscription);
        if (!$blockedAt) {
            return true;
        }

        return Carbon::now()->isAfter($blockedAt);
    }

    /**
     * Retorna informações completas do estado da assinatura para UI.
     * Baseado em práticas de Stripe, Netflix, Spotify para gestão de grace period.
     * 
     * Estados possíveis:
     * - active: Assinatura ativa e não vencida
     * - grace_period: Vencida mas dentro do período de carência (pode renovar)
     * - expired: Período de carência expirou (bloqueado)
     * - canceled: Cancelada pelo usuário (acesso até fim do período pago)
     * - pending: Aguardando confirmação de pagamento
     * 
     * @return array{
     *     status: string,
     *     is_active: bool,
     *     is_in_grace: bool,
     *     is_expired: bool,
     *     is_canceled: bool,
     *     can_access: bool,
     *     should_show_renew: bool,
     *     renews_at: string|null,
     *     blocked_at: string|null,
     *     grace_days_remaining: int,
     *     grace_hours_remaining: int,
     *     access_until: string|null,
     *     status_label: string,
     *     status_color: string,
     *     action_label: string,
     *     alert_message: string|null
     * }
     */
    public static function getSubscriptionStatus(?AssinaturaUsuario $subscription): array
    {
        // Sem assinatura = plano free
        if (!$subscription) {
            return [
                'status' => 'free',
                'is_active' => false,
                'is_in_grace' => false,
                'is_expired' => false,
                'is_canceled' => false,
                'can_access' => false,
                'should_show_renew' => false,
                'renews_at' => null,
                'blocked_at' => null,
                'grace_days_remaining' => 0,
                'grace_hours_remaining' => 0,
                'access_until' => null,
                'status_label' => 'Plano Gratuito',
                'status_color' => 'gray',
                'action_label' => 'Assinar Pro',
                'alert_message' => null,
            ];
        }

        $now = Carbon::now();
        $renewsAt = $subscription->renova_em ? Carbon::parse($subscription->renova_em) : null;
        $blockedAt = self::getBlockedAt($subscription);

        // Estado base
        $result = [
            'status' => $subscription->status,
            'is_active' => false,
            'is_in_grace' => false,
            'is_expired' => false,
            'is_canceled' => false,
            'can_access' => false,
            'should_show_renew' => false,
            'renews_at' => $renewsAt?->format('d/m/Y'),
            'renews_at_full' => $renewsAt?->format('d/m/Y \à\s H:i'),
            'blocked_at' => $blockedAt?->format('d/m/Y \à\s H:i'),
            'grace_days_remaining' => 0,
            'grace_hours_remaining' => 0,
            'access_until' => null,
            'status_label' => '',
            'status_color' => 'gray',
            'action_label' => '',
            'alert_message' => null,
        ];

        // Status CANCELED - Cancelada pelo usuário
        if ($subscription->status === AssinaturaUsuario::ST_CANCELED) {
            $result['is_canceled'] = true;
            $result['can_access'] = $renewsAt && $renewsAt->isFuture();
            $result['access_until'] = $renewsAt?->format('d/m/Y');
            $result['status_label'] = 'Cancelada';
            $result['status_color'] = 'orange';
            $result['action_label'] = 'Reativar assinatura';
            $result['should_show_renew'] = true;
            $result['alert_message'] = $renewsAt
                ? "Assinatura cancelada. Acesso disponível até {$renewsAt->format('d/m/Y')}."
                : 'Assinatura cancelada.';
            return $result;
        }

        // Status EXPIRED - Bloqueado
        if ($subscription->status === AssinaturaUsuario::ST_EXPIRED) {
            $result['is_expired'] = true;
            $result['can_access'] = false;
            $result['status_label'] = 'Expirada';
            $result['status_color'] = 'red';
            $result['action_label'] = 'Renovar assinatura';
            $result['should_show_renew'] = true;
            $result['alert_message'] = 'Seu plano PRO expirou. Renove para recuperar o acesso.';
            return $result;
        }

        // Status PENDING
        if ($subscription->status === AssinaturaUsuario::ST_PENDING) {
            $result['status_label'] = 'Aguardando pagamento';
            $result['status_color'] = 'yellow';
            $result['action_label'] = 'Concluir pagamento';
            $result['alert_message'] = 'Pagamento pendente. Conclua para ativar seu plano.';
            return $result;
        }

        // Status ACTIVE - Verificar se está no período normal ou carência
        if ($subscription->status === AssinaturaUsuario::ST_ACTIVE) {
            // Ainda não venceu - Ativo normalmente
            if ($renewsAt && $renewsAt->isFuture()) {
                $result['is_active'] = true;
                $result['can_access'] = true;
                $result['status_label'] = 'Ativo';
                $result['status_color'] = 'green';
                $result['action_label'] = 'Cancelar assinatura';
                $result['access_until'] = $renewsAt->format('d/m/Y');
                return $result;
            }

            // Venceu - Verificar período de carência
            if ($blockedAt && $now->isBefore($blockedAt)) {
                // Dentro do período de carência
                $hoursRemaining = (int) $now->diffInHours($blockedAt, false);
                $daysRemaining = (int) ceil($hoursRemaining / 24);

                $result['is_in_grace'] = true;
                $result['can_access'] = true;
                $result['should_show_renew'] = true;
                $result['grace_days_remaining'] = max(0, $daysRemaining);
                $result['grace_hours_remaining'] = max(0, $hoursRemaining);
                $result['access_until'] = $blockedAt->format('d/m/Y \à\s H:i');
                $result['status_label'] = 'Vencido - Em carência';
                $result['status_color'] = 'orange';
                $result['action_label'] = 'Renovar agora';

                // Mensagem baseada no tempo restante
                if ($hoursRemaining <= 24) {
                    $result['alert_message'] = "⚠️ Seu plano venceu! Restam menos de 24 horas para renovar. Bloqueio em {$blockedAt->format('d/m/Y \à\s H:i')}.";
                } else {
                    $result['alert_message'] = "⚠️ Seu plano venceu! Você tem {$daysRemaining} dia(s) para renovar antes do bloqueio em {$blockedAt->format('d/m/Y')}.";
                }

                return $result;
            }

            // Período de carência expirou - deveria estar bloqueado
            // (O cron job ainda não rodou para mudar o status)
            $result['is_expired'] = true;
            $result['can_access'] = false;
            $result['should_show_renew'] = true;
            $result['status_label'] = 'Acesso suspenso';
            $result['status_color'] = 'red';
            $result['action_label'] = 'Renovar assinatura';
            $result['alert_message'] = 'Período de carência expirou. Renove para recuperar o acesso.';
            return $result;
        }

        // Status desconhecido ou PAST_DUE
        $result['status_label'] = 'Pendente';
        $result['status_color'] = 'yellow';
        $result['action_label'] = 'Verificar status';
        return $result;
    }

    /**
     * Formata o tempo restante de carência de forma amigável.
     */
    public static function formatGraceTimeRemaining(AssinaturaUsuario $subscription): string
    {
        $blockedAt = self::getBlockedAt($subscription);
        if (!$blockedAt) {
            return '';
        }

        $now = Carbon::now();
        if ($now->isAfter($blockedAt)) {
            return 'Expirado';
        }

        $diff = $now->diff($blockedAt);

        if ($diff->days > 0) {
            return $diff->days . ' dia(s) e ' . $diff->h . ' hora(s)';
        }

        if ($diff->h > 0) {
            return $diff->h . ' hora(s) e ' . $diff->i . ' minuto(s)';
        }

        return $diff->i . ' minuto(s)';
    }
}
