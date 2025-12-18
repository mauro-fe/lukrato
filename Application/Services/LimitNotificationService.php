<?php

namespace Application\Services;

use Application\Models\Notificacao;

/**
 * Serviço para gerenciar notificações relacionadas a limites de uso
 */
class LimitNotificationService
{
    private LancamentoLimitService $limitService;

    public function __construct(?LancamentoLimitService $limitService = null)
    {
        $this->limitService = $limitService ?? new LancamentoLimitService();
    }

    /**
     * Cria uma notificação de aviso de limite para o usuário
     */
    public function notifyWarning(int $userId, array $usage): ?Notificacao
    {
        // Não notifica se não deve avisar ou se já está bloqueado
        if (!($usage['should_warn'] ?? false) || ($usage['blocked'] ?? false)) {
            return null;
        }

        // Verifica se já existe uma notificação similar recente (últimas 24h)
        $existingNotification = Notificacao::where('user_id', $userId)
            ->where('tipo', 'limite_lancamentos')
            ->where('created_at', '>=', now()->subDay())
            ->where('lida', false)
            ->first();

        if ($existingNotification) {
            // Atualiza a notificação existente ao invés de criar duplicata
            $message = $this->limitService->getWarningMessage($usage);
            $existingNotification->mensagem = $message;
            $existingNotification->save();
            return $existingNotification;
        }

        // Cria nova notificação
        $message = $this->limitService->getWarningMessage($usage);
        $percentage = $usage['percentage'] ?? 0;
        
        $notification = Notificacao::create([
            'user_id'   => $userId,
            'tipo'      => 'limite_lancamentos',
            'titulo'    => $percentage >= 90 ? 'Limite crítico atingido!' : 'Atenção: Limite próximo',
            'mensagem'  => $message,
            'lida'      => false,
            'link'      => '/planos',
        ]);

        return $notification;
    }

    /**
     * Cria notificação quando o limite é atingido
     */
    public function notifyBlocked(int $userId, array $usage): ?Notificacao
    {
        if (!($usage['blocked'] ?? false)) {
            return null;
        }

        // Verifica se já existe notificação de bloqueio recente
        $existingNotification = Notificacao::where('user_id', $userId)
            ->where('tipo', 'limite_bloqueado')
            ->where('created_at', '>=', now()->subDay())
            ->where('lida', false)
            ->first();

        if ($existingNotification) {
            return $existingNotification;
        }

        $limit = $this->limitService->getFreeLimit();
        
        $notification = Notificacao::create([
            'user_id'   => $userId,
            'tipo'      => 'limite_bloqueado',
            'titulo'    => 'Limite de lançamentos atingido',
            'mensagem'  => "Você atingiu o limite de {$limit} lançamentos deste mês. " .
                          $this->limitService->getUpgradeCta(),
            'lida'      => false,
            'link'      => '/planos',
        ]);

        return $notification;
    }

    /**
     * Marca notificações de limite antigas como lidas
     */
    public function markOldNotificationsAsRead(int $userId): int
    {
        return Notificacao::where('user_id', $userId)
            ->whereIn('tipo', ['limite_lancamentos', 'limite_bloqueado'])
            ->where('lida', false)
            ->where('created_at', '<', now()->subDays(7))
            ->update(['lida' => true]);
    }

    /**
     * Verifica e notifica o usuário sobre seu uso atual se necessário
     */
    public function checkAndNotify(int $userId, string $ym): ?Notificacao
    {
        $usage = $this->limitService->usage($userId, $ym);

        // Marca notificações antigas como lidas
        $this->markOldNotificationsAsRead($userId);

        // Verifica se está bloqueado
        if ($usage['blocked'] ?? false) {
            return $this->notifyBlocked($userId, $usage);
        }

        // Verifica se deve avisar
        if ($usage['should_warn'] ?? false) {
            return $this->notifyWarning($userId, $usage);
        }

        return null;
    }
}
