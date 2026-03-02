<?php

declare(strict_types=1);

namespace Application\Services\Referral;

use Application\Models\Usuario;
use Illuminate\Database\Capsule\Manager as Capsule;
use Application\Services\Infrastructure\LogService;

/**
 * Serviço de proteção anti-fraude para o sistema de indicações
 * 
 * Protege contra:
 * - Usuários que excluem conta e recriam para ganhar indicação novamente
 * - Múltiplas contas do mesmo IP para auto-indicação
 * - Abuso do sistema de indicações com contas fake
 */
class ReferralAntifraudService
{
    // Configurações de limites
    const MAX_REFERRALS_PER_MONTH = 5;          // Máximo de indicações que uma pessoa pode fazer por mês
    const QUARANTINE_DAYS = 90;                  // Dias de quarentena após excluir conta
    const MAX_ACCOUNTS_PER_IP = 3;               // Máximo de contas por IP
    const IP_TRACKING_DAYS = 30;                 // Período em dias para tracking de IP
    const MIN_ACCOUNT_AGE_FOR_REFERRAL = 24;     // Horas mínimas de conta para poder indicar

    /**
     * Hash de email para armazenamento seguro
     */
    public function hashEmail(string $email): string
    {
        return hash('sha256', strtolower(trim($email)) . ($_ENV['APP_KEY'] ?? 'lukrato_secret'));
    }

    /**
     * Extrai o domínio do email
     */
    public function getEmailDomain(string $email): ?string
    {
        $parts = explode('@', $email);
        return count($parts) === 2 ? strtolower($parts[1]) : null;
    }

    /**
     * Registra evento de tracking para um usuário
     */
    public function trackEvent(
        string $email,
        int $userId,
        string $eventType,
        ?string $ip = null,
        ?int $referredBy = null,
        ?\DateTimeInterface $quarantineUntil = null,
        ?array $metadata = null
    ): void {
        $emailHash = $this->hashEmail($email);
        
        try {
            Capsule::table('referral_antifraud_tracking')->insert([
                'email_hash' => $emailHash,
                'email_domain' => $this->getEmailDomain($email),
                'ip_address' => $ip,
                'ip_hash' => $ip ? hash('sha256', $ip . ($_ENV['APP_KEY'] ?? 'lukrato_secret')) : null,
                'original_user_id' => $userId,
                'event_type' => $eventType,
                'referred_by' => $referredBy,
                'quarantine_until' => $quarantineUntil,
                'metadata' => $metadata ? json_encode($metadata) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            LogService::error('[ReferralAntifraud] Erro ao registrar evento', [
                'event_type' => $eventType,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Verifica se um email pode criar conta (não está em quarentena)
     * 
     * @return array ['allowed' => bool, 'reason' => string|null, 'quarantine_until' => Carbon|null]
     */
    public function canCreateAccount(string $email, ?string $ip = null): array
    {
        $emailHash = $this->hashEmail($email);

        // 1. Verifica se o email está em quarentena (conta excluída recentemente)
        $quarantine = Capsule::table('referral_antifraud_tracking')
            ->where('email_hash', $emailHash)
            ->where('event_type', 'account_deleted')
            ->where('quarantine_until', '>', now())
            ->orderBy('quarantine_until', 'desc')
            ->first();

        if ($quarantine) {
            $quarantineUntil = \Carbon\Carbon::parse($quarantine->quarantine_until);
            $daysRemaining = now()->diffInDays($quarantineUntil);
            
            LogService::warning('[ReferralAntifraud] Email em quarentena tentou criar conta', [
                'email_hash' => substr($emailHash, 0, 8) . '...',
                'days_remaining' => $daysRemaining,
            ]);

            return [
                'allowed' => false,
                'reason' => "Este email foi usado anteriormente em uma conta excluída. Você poderá criar uma nova conta em {$daysRemaining} dias.",
                'quarantine_until' => $quarantineUntil,
            ];
        }

        // 2. Verifica limite de contas por IP
        if ($ip) {
            $recentAccountsFromIp = $this->countRecentAccountsFromIp($ip);
            
            if ($recentAccountsFromIp >= self::MAX_ACCOUNTS_PER_IP) {
                LogService::warning('[ReferralAntifraud] Limite de contas por IP atingido', [
                    'ip' => $ip,
                    'count' => $recentAccountsFromIp,
                ]);

                return [
                    'allowed' => false,
                    'reason' => 'Limite de novas contas atingido para sua rede. Tente novamente mais tarde.',
                    'quarantine_until' => null,
                ];
            }
        }

        return [
            'allowed' => true,
            'reason' => null,
            'quarantine_until' => null,
        ];
    }

    /**
     * Conta quantas contas foram criadas de um IP recentemente
     */
    public function countRecentAccountsFromIp(string $ip): int
    {
        $since = now()->subDays(self::IP_TRACKING_DAYS);

        return Capsule::table('referral_antifraud_tracking')
            ->where('ip_address', $ip)
            ->where('event_type', 'account_created')
            ->where('created_at', '>=', $since)
            ->count();
    }

    /**
     * Verifica se um email já foi usado como indicado anteriormente
     */
    public function wasAlreadyReferred(string $email): bool
    {
        $emailHash = $this->hashEmail($email);

        return Capsule::table('referral_antifraud_tracking')
            ->where('email_hash', $emailHash)
            ->where('event_type', 'referral_used')
            ->exists();
    }

    /**
     * Valida se uma indicação pode ser processada
     * 
     * @return array ['allowed' => bool, 'reason' => string|null]
     */
    public function canProcessReferral(string $referredEmail, int $referrerId, ?string $ip = null): array
    {
        $emailHash = $this->hashEmail($referredEmail);

        // 1. Verifica se o email já foi indicado antes (mesmo em conta excluída)
        if ($this->wasAlreadyReferred($referredEmail)) {
            LogService::warning('[ReferralAntifraud] Email já foi indicado anteriormente', [
                'email_hash' => substr($emailHash, 0, 8) . '...',
                'referrer_id' => $referrerId,
            ]);

            return [
                'allowed' => false,
                'reason' => 'Este email já foi indicado anteriormente.',
            ];
        }

        // 2. Verifica limite mensal de indicações do referrer
        $monthlyReferrals = $this->countMonthlyReferrals($referrerId);
        if ($monthlyReferrals >= self::MAX_REFERRALS_PER_MONTH) {
            LogService::warning('[ReferralAntifraud] Limite mensal de indicações atingido', [
                'referrer_id' => $referrerId,
                'count' => $monthlyReferrals,
            ]);

            return [
                'allowed' => false,
                'reason' => 'Você atingiu o limite de ' . self::MAX_REFERRALS_PER_MONTH . ' indicações por mês. Tente novamente no próximo mês.',
            ];
        }

        // 3. Verifica idade mínima da conta do referrer
        $referrer = Usuario::find($referrerId);
        if ($referrer) {
            $accountAgeHours = $referrer->created_at->diffInHours(now());
            if ($accountAgeHours < self::MIN_ACCOUNT_AGE_FOR_REFERRAL) {
                $hoursRemaining = self::MIN_ACCOUNT_AGE_FOR_REFERRAL - $accountAgeHours;
                
                LogService::warning('[ReferralAntifraud] Conta muito nova para indicar', [
                    'referrer_id' => $referrerId,
                    'account_age_hours' => $accountAgeHours,
                ]);

                return [
                    'allowed' => false,
                    'reason' => "Sua conta precisa ter pelo menos 24 horas para indicar amigos. Faltam {$hoursRemaining} horas.",
                ];
            }
        }

        // 4. Verifica se o IP do indicado é o mesmo do referrer (possível auto-indicação)
        if ($ip && $referrer && $referrer->registration_ip === $ip) {
            // Permite mas loga para análise posterior
            LogService::info('[ReferralAntifraud] Possível auto-indicação detectada (mesmo IP)', [
                'referrer_id' => $referrerId,
                'ip' => $ip,
            ]);
            
            // Se já houve múltiplas indicações do mesmo IP, bloqueia
            $sameIpReferrals = $this->countReferralsFromSameIp($referrerId, $ip);
            if ($sameIpReferrals >= 2) {
                return [
                    'allowed' => false,
                    'reason' => 'Detectamos múltiplas indicações do mesmo local. Por segurança, essa indicação não pode ser processada.',
                ];
            }
        }

        return [
            'allowed' => true,
            'reason' => null,
        ];
    }

    /**
     * Conta indicações do mês atual para um referrer
     */
    public function countMonthlyReferrals(int $referrerId): int
    {
        $startOfMonth = now()->startOfMonth();

        return Capsule::table('indicacoes')
            ->where('referrer_id', $referrerId)
            ->where('created_at', '>=', $startOfMonth)
            ->count();
    }

    /**
     * Conta indicações do mesmo IP para um referrer
     */
    public function countReferralsFromSameIp(int $referrerId, string $ip): int
    {
        return Capsule::table('indicacoes')
            ->where('referrer_id', $referrerId)
            ->where('ip_address', $ip)
            ->count();
    }

    /**
     * Registra criação de conta para tracking
     */
    public function onAccountCreated(Usuario $user, ?string $ip = null, ?int $referredById = null): void
    {
        // Atualiza o hash do email original no usuário
        $user->original_email_hash = $this->hashEmail($user->email);
        $user->registration_ip = $ip;
        $user->save();

        // Registra evento de criação
        $this->trackEvent(
            $user->email,
            $user->id,
            'account_created',
            $ip,
            $referredById,
            null,
            [
                'created_at' => now()->toIso8601String(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]
        );
    }

    /**
     * Registra uso de indicação para tracking
     */
    public function onReferralUsed(Usuario $referredUser, int $referrerId, ?string $ip = null): void
    {
        $this->trackEvent(
            $referredUser->email,
            $referredUser->id,
            'referral_used',
            $ip,
            $referrerId,
            null,
            [
                'referrer_id' => $referrerId,
            ]
        );
    }

    /**
     * Registra exclusão de conta e aplica quarentena
     */
    public function onAccountDeleted(string $originalEmail, int $userId, ?string $ip = null): void
    {
        $quarantineUntil = now()->addDays(self::QUARANTINE_DAYS);

        $this->trackEvent(
            $originalEmail,
            $userId,
            'account_deleted',
            $ip,
            null,
            $quarantineUntil,
            [
                'deleted_at' => now()->toIso8601String(),
            ]
        );

        LogService::info('[ReferralAntifraud] Quarentena aplicada após exclusão de conta', [
            'user_id' => $userId,
            'quarantine_until' => $quarantineUntil->toIso8601String(),
        ]);
    }

    /**
     * Obtém estatísticas anti-fraude (para admin)
     */
    public function getStats(): array
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $last30Days = $now->copy()->subDays(30);

        return [
            'accounts_in_quarantine' => Capsule::table('referral_antifraud_tracking')
                ->where('event_type', 'account_deleted')
                ->where('quarantine_until', '>', $now)
                ->count(),
            'referrals_this_month' => Capsule::table('referral_antifraud_tracking')
                ->where('event_type', 'referral_used')
                ->where('created_at', '>=', $startOfMonth)
                ->count(),
            'blocked_attempts_30d' => Capsule::table('referral_antifraud_tracking')
                ->where('created_at', '>=', $last30Days)
                ->whereNotNull('metadata')
                ->whereRaw("JSON_EXTRACT(metadata, '$.blocked') = true")
                ->count(),
            'suspicious_ips_30d' => Capsule::table('referral_antifraud_tracking')
                ->select('ip_address')
                ->where('event_type', 'account_created')
                ->where('created_at', '>=', $last30Days)
                ->whereNotNull('ip_address')
                ->groupBy('ip_address')
                ->havingRaw('COUNT(*) >= ?', [self::MAX_ACCOUNTS_PER_IP])
                ->count(),
        ];
    }

    /**
     * Verifica se um email usa domínio temporário/descartável
     */
    public function isDisposableEmail(string $email): bool
    {
        $disposableDomains = [
            'tempmail.com', 'temp-mail.org', 'guerrillamail.com', 'guerrillamail.org',
            '10minutemail.com', 'mailinator.com', 'throwaway.email', 'tempail.com',
            'fakeinbox.com', 'sharklasers.com', 'trashmail.com', 'yopmail.com',
            'getnada.com', 'maildrop.cc', 'mohmal.com', 'dispostable.com',
            'mailnesia.com', 'tempr.email', 'throwawaymail.com', 'tmpmail.org',
            'temp-mail.io', 'burnermail.io', 'tempmailo.com', 'emailondeck.com',
        ];

        $domain = $this->getEmailDomain($email);
        
        if (!$domain) {
            return false;
        }

        return in_array($domain, $disposableDomains);
    }

    /**
     * Limpa registros de tracking muito antigos (para GDPR/LGPD)
     * Manter apenas por 1 ano
     */
    public function cleanupOldRecords(): int
    {
        $oneYearAgo = now()->subYear();

        $deleted = Capsule::table('referral_antifraud_tracking')
            ->where('created_at', '<', $oneYearAgo)
            ->where('quarantine_until', '<', now())
            ->delete();

        LogService::info('[ReferralAntifraud] Limpeza de registros antigos', [
            'deleted_count' => $deleted,
        ]);

        return $deleted;
    }
}
