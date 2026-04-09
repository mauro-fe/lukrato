<?php

declare(strict_types=1);

namespace Application\Services\Auth;

use Application\Config\CommunicationRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Models\Notificacao;
use Application\Models\Usuario;
use Application\Services\Communication\MailService;
use Application\Services\Gamification\AchievementService;
use Application\Services\Infrastructure\LogService;
use Application\Services\Referral\ReferralService;

class EmailVerificationService
{
    private MailService $mailService;
    private TokenPairService $tokenPairService;
    private CommunicationRuntimeConfig $runtimeConfig;
    private ?ReferralService $referralService = null;
    private ?AchievementService $achievementService = null;

    public function __construct(
        ?MailService $mailService = null,
        ?TokenPairService $tokenPairService = null,
        ?CommunicationRuntimeConfig $runtimeConfig = null
    )
    {
        $this->mailService = ApplicationContainer::resolveOrNew($mailService, MailService::class);
        $this->tokenPairService = ApplicationContainer::resolveOrNew($tokenPairService, TokenPairService::class);
        $this->runtimeConfig = ApplicationContainer::resolveOrNew($runtimeConfig, CommunicationRuntimeConfig::class);
    }

    public function sendVerificationEmail(Usuario $user): bool
    {
        $targetEmail = $this->resolveVerificationTargetEmail($user);
        if ($targetEmail === '') {
            LogService::warning('[EmailVerification] Usuario sem email alvo para verificacao', [
                'user_id' => $user->id,
            ]);
            return false;
        }

        $hasPendingChange = $this->hasPendingEmailChange($user);
        if ($user->hasVerifiedEmail() && !$hasPendingChange) {
            LogService::info('[EmailVerification] Email ja verificado', ['user_id' => $user->id]);
            return true;
        }

        $credentials = $user->generateEmailVerificationCredentials($this->tokenPairService);
        $verificationUrl = $this->buildVerificationUrl($credentials['selector'], $credentials['validator']);

        try {
            $result = $this->mailService->sendEmailVerification(
                $targetEmail,
                $user->nome ?? 'Usuario',
                $verificationUrl
            );

            if ($result) {
                LogService::info('[EmailVerification] Email de verificacao enviado', [
                    'user_id' => $user->id,
                    'email' => $targetEmail,
                    'pending_change' => $hasPendingChange,
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            LogService::error('[EmailVerification] Falha ao enviar email de verificacao', [
                'user_id' => $user->id,
                'email' => $targetEmail,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function verifyEmail(string $token = '', string $selector = '', string $validator = ''): array
    {
        if ($token === '' && ($selector === '' || $validator === '')) {
            return [
                'success' => false,
                'message' => 'Token de verificacao nao informado.',
            ];
        }

        $user = $this->resolveVerificationUser($token, $selector, $validator);

        if (!$user) {
            LogService::warning('[EmailVerification] Token invalido ou expirado', [
                'selector' => $selector !== '' ? substr($selector, 0, 8) : null,
                'legacy_token_prefix' => $token !== '' ? substr($token, 0, 8) : null,
            ]);

            return [
                'success' => false,
                'message' => 'Link de verificacao invalido ou expirado. Solicite um novo email de verificacao.',
            ];
        }

        $expiresAt = $user->email_verification_expires_at
            ?? ($user->email_verification_sent_at ? $user->email_verification_sent_at->copy()->addHours(24) : null);

        if (!$expiresAt || $expiresAt->isPast()) {
            LogService::warning('[EmailVerification] Token expirado', [
                'user_id' => $user->id,
                'expires_at' => $expiresAt?->toDateTimeString(),
            ]);

            return [
                'success' => false,
                'message' => 'Link de verificacao expirado. Solicite um novo email de verificacao.',
                'expired' => true,
                'user_id' => $user->id,
            ];
        }

        if ($this->hasPendingEmailChange($user)) {
            $pendingEmail = mb_strtolower(trim((string) $user->pending_email));
            $user->forceFill([
                'email' => $pendingEmail,
                'pending_email' => null,
                'email_verified_at' => $user->email_verified_at ?? now(),
                'email_verification_token' => null,
                'email_verification_selector' => null,
                'email_verification_token_hash' => null,
                'email_verification_expires_at' => null,
                'email_verification_reminder_sent_at' => null,
            ])->save();
        } else {
            $user->markEmailAsVerified();
        }

        LogService::info('[EmailVerification] Email verificado com sucesso', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        try {
            $this->mailService->sendWelcomeEmail($user->email, $user->nome ?? 'Usuario');
            LogService::info('[EmailVerification] Email de boas-vindas enviado apos verificacao', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        } catch (\Throwable $e) {
            LogService::error('[EmailVerification] Erro ao enviar email de boas-vindas', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        $this->processReferralReward($user);

        return [
            'success' => true,
            'message' => 'Email verificado com sucesso! você ja pode fazer login.',
            'user_id' => $user->id,
        ];
    }

    private function processReferralReward(Usuario $user): void
    {
        $indicacao = \Application\Models\Indicacao::where('referred_id', $user->id)
            ->where('status', \Application\Models\Indicacao::STATUS_PENDING)
            ->first();

        if (!$indicacao) {
            return;
        }

        try {
            $referralService = $this->referralService();

            if (!$indicacao->referred_rewarded) {
                $referralService->applyRewardToUser($user, ReferralService::REFERRED_REWARD_DAYS);
                $indicacao->referred_rewarded = true;
                $indicacao->referred_rewarded_at = now();

                $this->createReferralNotification(
                    $user->id,
                    'você ganhou ' . ReferralService::REFERRED_REWARD_DAYS . ' dias PRO!',
                    'Parabens! Por ter sido indicado, você ganhou ' . ReferralService::REFERRED_REWARD_DAYS . ' dias de acesso PRO gratuito. Aproveite todas as funcionalidades premium!',
                    'referred'
                );

                $this->sendReferralRewardEmail($user, null, 'referred');
            }

            $referrer = Usuario::find($indicacao->referrer_id);
            if ($referrer && !$indicacao->referrer_rewarded) {
                $referralService->applyRewardToUser($referrer, ReferralService::REFERRER_REWARD_DAYS);
                $indicacao->referrer_rewarded = true;
                $indicacao->referrer_rewarded_at = now();

                $referredName = $user->nome ?? 'Um amigo';
                $this->createReferralNotification(
                    $referrer->id,
                    'você ganhou ' . ReferralService::REFERRER_REWARD_DAYS . ' dias PRO!',
                    "{$referredName} verificou o email e agora você ganhou " . ReferralService::REFERRER_REWARD_DAYS . ' dias de acesso PRO gratuito. Continue indicando amigos para ganhar mais!',
                    'referrer'
                );

                $this->sendReferralRewardEmail($referrer, $user, 'referrer');

                try {
                    $this->achievementService()->checkAndUnlockAchievements($referrer->id, 'referral');
                } catch (\Throwable $e) {
                    LogService::warning('[EmailVerification] Erro ao verificar conquistas', [
                        'user_id' => $referrer->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $indicacao->status = \Application\Models\Indicacao::STATUS_COMPLETED;
            $indicacao->completed_at = now();
            $indicacao->save();

            LogService::info('[EmailVerification] Recompensa de indicacao processada apos verificacao', [
                'referred_id' => $user->id,
                'referrer_id' => $indicacao->referrer_id,
            ]);
        } catch (\Throwable $e) {
            LogService::error('[EmailVerification] Erro ao processar recompensa de indicacao', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function createReferralNotification(int $userId, string $titulo, string $mensagem, string $tipo): void
    {
        try {
            Notificacao::create([
                'user_id' => $userId,
                'tipo' => 'referral_' . $tipo,
                'titulo' => $titulo,
                'mensagem' => $mensagem,
                'lida' => 0,
                'link' => null,
            ]);

            LogService::info('[EmailVerification] Notificacao de indicacao criada', [
                'user_id' => $userId,
                'tipo' => $tipo,
            ]);
        } catch (\Throwable $e) {
            LogService::error('[EmailVerification] Erro ao criar notificacao', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendReferralRewardEmail(Usuario $recipient, ?Usuario $referred, string $tipo): void
    {
        try {
            if ($tipo === 'referred') {
                $this->mailService->sendReferralRewardToReferred(
                    $recipient->email,
                    $recipient->nome ?? 'Usuario',
                    ReferralService::REFERRED_REWARD_DAYS
                );

                LogService::info('[EmailVerification] Email de recompensa enviado (indicado)', [
                    'user_id' => $recipient->id,
                    'email' => $recipient->email,
                ]);
            } elseif ($tipo === 'referrer' && $referred) {
                $this->mailService->sendReferralRewardToReferrer(
                    $recipient->email,
                    $recipient->nome ?? 'Usuario',
                    $referred->nome ?? 'Um amigo',
                    ReferralService::REFERRER_REWARD_DAYS
                );

                LogService::info('[EmailVerification] Email de recompensa enviado (indicador)', [
                    'user_id' => $recipient->id,
                    'email' => $recipient->email,
                    'referred_name' => $referred->nome,
                ]);
            }
        } catch (\Throwable $e) {
            LogService::error('[EmailVerification] Erro ao enviar email de recompensa', [
                'user_id' => $recipient->id,
                'tipo' => $tipo,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function resendVerificationEmail(Usuario $user): array
    {
        if ($user->hasVerifiedEmail() && !$this->hasPendingEmailChange($user)) {
            return [
                'success' => false,
                'message' => 'Seu email ja foi verificado.',
            ];
        }

        $sentAt = $user->email_verification_sent_at;
        if ($sentAt && $sentAt->diffInMinutes(now()) < 1) {
            return [
                'success' => false,
                'message' => 'Aguarde 1 minuto antes de solicitar outro email.',
            ];
        }

        $sent = $this->sendVerificationEmail($user);

        if ($sent) {
            return [
                'success' => true,
                'message' => 'Email de verificacao reenviado! Verifique sua caixa de entrada.',
            ];
        }

        return [
            'success' => false,
            'message' => 'Nao foi possivel enviar o email. Tente novamente mais tarde.',
        ];
    }

    private function hasPendingEmailChange(Usuario $user): bool
    {
        $currentEmail = mb_strtolower(trim((string) ($user->email ?? '')));
        $pendingEmail = mb_strtolower(trim((string) ($user->pending_email ?? '')));

        return $pendingEmail !== '' && $pendingEmail !== $currentEmail;
    }

    private function resolveVerificationTargetEmail(Usuario $user): string
    {
        if ($this->hasPendingEmailChange($user)) {
            return mb_strtolower(trim((string) $user->pending_email));
        }

        return mb_strtolower(trim((string) $user->email));
    }

    private function referralService(): ReferralService
    {
        return $this->referralService ??= ApplicationContainer::resolveOrNew(null, ReferralService::class);
    }

    private function achievementService(): AchievementService
    {
        return $this->achievementService ??= ApplicationContainer::resolveOrNew(null, AchievementService::class);
    }

    public function getUnverifiedForReminder(): \Illuminate\Database\Eloquent\Collection
    {
        return Usuario::whereNull('email_verified_at')
            ->whereNull('email_verification_reminder_sent_at')
            ->where('created_at', '<=', now()->subHours(24))
            ->where('created_at', '>', now()->subDays(7))
            ->whereNull('deleted_at')
            ->get();
    }

    public function sendReminder(Usuario $user): bool
    {
        $credentials = $user->generateEmailVerificationCredentials($this->tokenPairService);
        $verificationUrl = $this->buildVerificationUrl($credentials['selector'], $credentials['validator']);

        try {
            $result = $this->mailService->sendVerificationReminder(
                $user->email,
                $user->nome ?? 'Usuario',
                $verificationUrl
            );

            if ($result) {
                $user->forceFill([
                    'email_verification_reminder_sent_at' => now(),
                ])->save();

                LogService::info('[EmailVerification] Lembrete de verificacao enviado', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            LogService::error('[EmailVerification] Falha ao enviar lembrete', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getExpiredUnverifiedAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        return Usuario::whereNull('email_verified_at')
            ->where('created_at', '<=', now()->subDays(7))
            ->whereNull('deleted_at')
            ->get();
    }

    public function removeUnverifiedAccount(Usuario $user): bool
    {
        try {
            $email = $user->email;
            $nome = $user->nome ?? 'Usuario';
            $userId = $user->id;

            \Application\Models\Indicacao::where('referred_id', $userId)
                ->where('status', \Application\Models\Indicacao::STATUS_PENDING)
                ->delete();

            $user->assinaturas()->delete();

            if (method_exists($user, 'categorias')) {
                $user->categorias()->delete();
            }

            $user->delete();

            LogService::info('[EmailVerification] Conta nao verificada removida', [
                'user_id' => $userId,
                'email' => $email,
                'created_at' => $user->created_at?->toDateTimeString(),
            ]);

            try {
                $this->mailService->sendAccountRemovedNotice($email, $nome);
            } catch (\Throwable $e) {
                LogService::warning('[EmailVerification] Nao foi possivel enviar aviso de remocao', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }

            return true;
        } catch (\Throwable $e) {
            LogService::error('[EmailVerification] Erro ao remover conta nao verificada', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function buildVerificationUrl(string $selector, string $validator): string
    {
        $baseUrl = $this->runtimeConfig->appUrl();

        return $baseUrl . '/verificar-email?selector=' . urlencode($selector)
            . '&validator=' . urlencode($validator);
    }

    private function resolveVerificationUser(string $token, string $selector, string $validator): ?Usuario
    {
        if ($selector !== '' && $validator !== '') {
            $user = Usuario::findByVerificationSelector($selector);

            if (!$user || !$this->tokenPairService->matches($validator, $user->email_verification_token_hash ?? null)) {
                return null;
            }

            return $user;
        }

        if ($token === '') {
            return null;
        }

        return Usuario::findByVerificationTokenHash($this->tokenPairService->hashValidator($token));
    }
}
