<?php

declare(strict_types=1);

namespace Application\Services\AI\Context;

use Application\Lib\Auth;
use Application\Services\AI\SystemContextService;

/**
 * Constrói contexto scoped para o usuário (exclui dados admin-only).
 * Adiciona metadados do perfil do usuário ao contexto.
 */
class UserContextBuilder
{
    private SystemContextService $contextService;

    public function __construct()
    {
        $this->contextService = new SystemContextService();
    }

    /**
     * Coleta contexto financeiro do usuário + metadados do perfil.
     */
    public function build(int $userId): array
    {
        // Coleta contexto scoped ao usuário
        $context = $this->contextService->gather($userId);

        // Remover dados admin-only que podem ter vazado
        $adminKeys = [
            'usuarios',
            'assinaturas',
            'indicacoes',
            'notificacoes',
            'campanhas',
            'cupons',
            'blog',
            'logs_sistema',
            'webhooks_cobranca',
            'seguranca',
            'lancamentos_por_usuario',
        ];

        foreach ($adminKeys as $key) {
            unset($context[$key]);
        }

        // Adicionar metadados do perfil
        $context = array_merge($this->getUserProfile($userId), $context);

        return $context;
    }

    /**
     * Coleta dados básicos do perfil do usuário.
     */
    private function getUserProfile(int $userId): array
    {
        try {
            $user = \Application\Models\Usuario::find($userId);

            if (!$user) {
                return [];
            }

            return [
                'usuario_nome'   => $user->nome ?? 'Usuário',
                'usuario_plano'  => $this->getUserPlan($userId),
                'usuario_desde'  => $user->created_at?->format('d/m/Y') ?? 'N/A',
            ];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Identifica o plano do usuário.
     */
    private function getUserPlan(int $userId): string
    {
        try {
            $assinatura = \Application\Models\AssinaturaUsuario::query()
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->with('plano')
                ->first();

            return $assinatura?->plano?->nome ?? 'Gratuito';
        } catch (\Throwable) {
            return 'Gratuito';
        }
    }
}
