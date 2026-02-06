<?php

declare(strict_types=1);

namespace Application\Services\Auth;

use Application\Models\Usuario;
use Application\Models\Notificacao;
use Application\Services\LogService;
use Application\Services\MailService;
use Application\Services\ReferralService;

/**
 * Serviço para verificação de email
 */
class EmailVerificationService
{
    private MailService $mailService;

    public function __construct(?MailService $mailService = null)
    {
        $this->mailService = $mailService ?? new MailService();
    }

    /**
     * Envia email de verificação para o usuário
     */
    public function sendVerificationEmail(Usuario $user): bool
    {
        // Se já verificou, não envia novamente
        if ($user->hasVerifiedEmail()) {
            LogService::info('[EmailVerification] Email já verificado', ['user_id' => $user->id]);
            return true;
        }

        // Gera novo token
        $token = $user->generateEmailVerificationToken();

        // Monta URL de verificação
        $baseUrl = rtrim($_ENV['APP_URL'] ?? (defined('BASE_URL') ? BASE_URL : ''), '/');
        $verificationUrl = $baseUrl . '/verificar-email?token=' . urlencode($token);

        try {
            $result = $this->mailService->sendEmailVerification(
                $user->email,
                $user->nome ?? 'Usuário',
                $verificationUrl
            );

            if ($result) {
                LogService::info('[EmailVerification] Email de verificação enviado', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            LogService::error('[EmailVerification] Falha ao enviar email de verificação', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Verifica o email usando o token
     */
    public function verifyEmail(string $token): array
    {
        if (empty($token)) {
            return [
                'success' => false,
                'message' => 'Token de verificação não informado.',
            ];
        }

        $user = Usuario::findByVerificationToken($token);

        if (!$user) {
            LogService::warning('[EmailVerification] Token inválido ou expirado', ['token' => substr($token, 0, 8) . '...']);
            return [
                'success' => false,
                'message' => 'Link de verificação inválido ou expirado. Solicite um novo email de verificação.',
            ];
        }

        // Verifica se o token não expirou (24 horas)
        $sentAt = $user->email_verification_sent_at;
        if ($sentAt && $sentAt->diffInHours(now()) > 24) {
            LogService::warning('[EmailVerification] Token expirado', [
                'user_id' => $user->id,
                'sent_at' => $sentAt->toDateTimeString(),
            ]);
            return [
                'success' => false,
                'message' => 'Link de verificação expirado. Solicite um novo email de verificação.',
                'expired' => true,
                'user_id' => $user->id,
            ];
        }

        // Marca email como verificado
        $user->markEmailAsVerified();

        LogService::info('[EmailVerification] Email verificado com sucesso', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        // Processa recompensas de indicação que estavam pendentes
        $this->processReferralReward($user);

        return [
            'success' => true,
            'message' => 'Email verificado com sucesso! Você já pode fazer login.',
            'user_id' => $user->id,
        ];
    }

    /**
     * Processa recompensa de indicação após verificação do email
     */
    private function processReferralReward(Usuario $user): void
    {
        // Verifica se o usuário tem uma indicação pendente
        $indicacao = \Application\Models\Indicacao::where('referred_id', $user->id)
            ->where('status', \Application\Models\Indicacao::STATUS_PENDING)
            ->first();

        if (!$indicacao) {
            return;
        }

        try {
            $referralService = new ReferralService();

            // Aplica recompensa ao indicado (7 dias)
            if (!$indicacao->referred_rewarded) {
                $referralService->applyRewardToUser($user, ReferralService::REFERRED_REWARD_DAYS);
                $indicacao->referred_rewarded = true;
                $indicacao->referred_rewarded_at = now();

                // Cria notificação para o usuário indicado
                $this->createReferralNotification(
                    $user->id,
                    'Você ganhou ' . ReferralService::REFERRED_REWARD_DAYS . ' dias PRO! 🎉',
                    'Parabéns! Por ter sido indicado, você ganhou ' . ReferralService::REFERRED_REWARD_DAYS . ' dias de acesso PRO gratuito. Aproveite todas as funcionalidades premium!',
                    'referred'
                );

                // Envia email para quem foi indicado
                $this->sendReferralRewardEmail($user, null, 'referred');
            }

            // Aplica recompensa a quem indicou (15 dias)
            $referrer = Usuario::find($indicacao->referrer_id);
            if ($referrer && !$indicacao->referrer_rewarded) {
                $referralService->applyRewardToUser($referrer, ReferralService::REFERRER_REWARD_DAYS);
                $indicacao->referrer_rewarded = true;
                $indicacao->referrer_rewarded_at = now();

                // Cria notificação para quem indicou
                $referredName = $user->nome ?? 'Um amigo';
                $this->createReferralNotification(
                    $referrer->id,
                    'Você ganhou ' . ReferralService::REFERRER_REWARD_DAYS . ' dias PRO! 🎁',
                    "{$referredName} verificou o email e agora você ganhou " . ReferralService::REFERRER_REWARD_DAYS . " dias de acesso PRO gratuito. Continue indicando amigos para ganhar mais!",
                    'referrer'
                );

                // Envia email para quem indicou
                $this->sendReferralRewardEmail($referrer, $user, 'referrer');

                // Verifica conquistas de indicação para quem indicou
                try {
                    $achievementService = new \Application\Services\AchievementService();
                    $achievementService->checkAndUnlockAchievements($referrer->id, 'referral');
                } catch (\Throwable $e) {
                    LogService::warning('[EmailVerification] Erro ao verificar conquistas', [
                        'user_id' => $referrer->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Marca indicação como completa
            $indicacao->status = \Application\Models\Indicacao::STATUS_COMPLETED;
            $indicacao->completed_at = now();
            $indicacao->save();

            LogService::info('[EmailVerification] Recompensa de indicação processada após verificação', [
                'referred_id' => $user->id,
                'referrer_id' => $indicacao->referrer_id,
            ]);
        } catch (\Throwable $e) {
            LogService::error('[EmailVerification] Erro ao processar recompensa de indicação', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cria notificação de recompensa de indicação
     */
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

            LogService::info('[EmailVerification] Notificação de indicação criada', [
                'user_id' => $userId,
                'tipo' => $tipo,
            ]);
        } catch (\Throwable $e) {
            LogService::error('[EmailVerification] Erro ao criar notificação', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Envia email de recompensa de indicação
     * 
     * @param Usuario $recipient Quem vai receber o email
     * @param Usuario|null $referred Quem foi indicado (apenas para tipo 'referrer')
     * @param string $tipo 'referred' ou 'referrer'
     */
    private function sendReferralRewardEmail(Usuario $recipient, ?Usuario $referred, string $tipo): void
    {
        try {
            if ($tipo === 'referred') {
                // Email para quem foi indicado
                $this->mailService->sendReferralRewardToReferred(
                    $recipient->email,
                    $recipient->nome ?? 'Usuário',
                    ReferralService::REFERRED_REWARD_DAYS
                );

                LogService::info('[EmailVerification] Email de recompensa enviado (indicado)', [
                    'user_id' => $recipient->id,
                    'email' => $recipient->email,
                ]);
            } elseif ($tipo === 'referrer' && $referred) {
                // Email para quem indicou
                $this->mailService->sendReferralRewardToReferrer(
                    $recipient->email,
                    $recipient->nome ?? 'Usuário',
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
            // Não falhar o processo se o email não for enviado
            LogService::error('[EmailVerification] Erro ao enviar email de recompensa', [
                'user_id' => $recipient->id,
                'tipo' => $tipo,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Reenvia email de verificação
     */
    public function resendVerificationEmail(Usuario $user): array
    {
        if ($user->hasVerifiedEmail()) {
            return [
                'success' => false,
                'message' => 'Seu email já foi verificado.',
            ];
        }

        // Limita reenvio a 1 por minuto
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
                'message' => 'Email de verificação reenviado! Verifique sua caixa de entrada.',
            ];
        }

        return [
            'success' => false,
            'message' => 'Não foi possível enviar o email. Tente novamente mais tarde.',
        ];
    }
}
