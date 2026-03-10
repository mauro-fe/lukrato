<?php

namespace Application\Controllers\Api\Notification;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Notificacao;
use Application\Models\Notification;
use Application\Services\Cartao\CartaoFaturaService;
use Application\Services\Cartao\CartaoCreditoService;
use Application\Services\Billing\SubscriptionExpirationService;
use Throwable;

class NotificacaoController extends BaseController
{
    public function index(): void
    {
        try {
            $this->requireAuthApi();
            $userId = $this->userId;

            // Garante que a sessão está iniciada
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Busca notificações do banco
            $itens = Notificacao::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($n) {
                    $arr = $n->toArray();
                    // Garante que 'lida' seja sempre integer 0 ou 1
                    $arr['lida'] = (int) $n->lida;
                    return $arr;
                })
                ->toArray();

            // Adiciona alertas de cartões dinamicamente (com tratamento de erro)
            try {
                $cartaoService = new CartaoCreditoService();
                $faturaService = new CartaoFaturaService();

                $alertasVencimento = $faturaService->verificarVencimentosProximos($userId);
                $alertasLimite = $cartaoService->verificarLimitesBaixos($userId);

                // Recupera alertas ignorados da sessão e limpa os expirados
                $alertasIgnorados = $this->limparAlertasExpirados($_SESSION['alertas_ignorados'] ?? []);
                $_SESSION['alertas_ignorados'] = $alertasIgnorados;

                // IDs dos alertas atuais (para remover da lista de ignorados se não existem mais)
                $alertasAtuaisIds = [];

                // Converte alertas em formato de notificação
                foreach ($alertasVencimento as $alerta) {
                    $alertaId = 'cartao_venc_' . $alerta['cartao_id'];
                    $alertasAtuaisIds[] = $alertaId;

                    // Pula se foi marcado como lido
                    if (isset($alertasIgnorados[$alertaId])) {
                        continue;
                    }

                    $itens[] = [
                        'id' => $alertaId,
                        'tipo' => 'alerta',
                        'titulo' => 'Fatura vencendo',
                        'mensagem' => "Fatura de {$alerta['nome_cartao']} vence em {$alerta['dias_faltando']} dia(s) - R$ " . number_format($alerta['valor_fatura'], 2, ',', '.'),
                        'lida' => 0,
                        'created_at' => date('Y-m-d H:i:s'),
                        'link' => '/cartoes',
                        'icone' => '📅',
                        'cor' => '#f39c12',
                        'dinamico' => true
                    ];
                }

                foreach ($alertasLimite as $alerta) {
                    $alertaId = 'cartao_lim_' . $alerta['cartao_id'];
                    $alertasAtuaisIds[] = $alertaId;

                    // Pula se foi marcado como lido
                    if (isset($alertasIgnorados[$alertaId])) {
                        continue;
                    }

                    $cor = $alerta['percentual_disponivel'] < 10 ? '#e74c3c' : '#e67e22';
                    $icone = $alerta['percentual_disponivel'] < 10 ? '🔴' : '🟠';

                    $itens[] = [
                        'id' => $alertaId,
                        'tipo' => 'alerta',
                        'titulo' => 'Limite baixo',
                        'mensagem' => "{$alerta['nome_cartao']}: apenas {$alerta['percentual_disponivel']}% disponível (R$ " . number_format($alerta['limite_disponivel'], 2, ',', '.') . ")",
                        'lida' => 0,
                        'created_at' => date('Y-m-d H:i:s'),
                        'link' => '/cartoes',
                        'icone' => $icone,
                        'cor' => $cor,
                        'dinamico' => true
                    ];
                }

                // Remove alertas ignorados que não existem mais (problema resolvido)
                foreach (array_keys($alertasIgnorados) as $alertaId) {
                    if (!in_array($alertaId, $alertasAtuaisIds)) {
                        unset($_SESSION['alertas_ignorados'][$alertaId]);
                    }
                }
            } catch (\Exception $e) {
                // Se falhar ao buscar alertas, apenas loga e continua com notificações do banco
                error_log("⚠️ Erro ao buscar alertas de cartões para notificações: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
            }

            // Adiciona alerta de período de carência (plano PRO vencendo)
            // NOTA: Só adiciona se NÃO existir notificação do banco sobre subscription_expired
            try {
                $usuario = \Application\Models\Usuario::find($userId);
                if ($usuario) {
                    $assinatura = $usuario->assinaturaAtiva()->with('plano')->first();

                    if ($assinatura && $assinatura->plano?->code === 'pro') {
                        $graceDaysLeft = SubscriptionExpirationService::getGraceDaysRemaining($assinatura);
                        $isInGrace = SubscriptionExpirationService::isInGracePeriod($assinatura);

                        // Verifica se já existe notificação do banco sobre vencimento desta assinatura
                        $jaTemNotificacaoBanco = collect($itens)->contains(function ($item) use ($assinatura) {
                            return isset($item['tipo'])
                                && in_array($item['tipo'], ['subscription_expired', 'subscription_blocked'])
                                && isset($item['link'])
                                && str_contains($item['link'], "subscription_id={$assinatura->id}");
                        });

                        if ($isInGrace && $graceDaysLeft > 0 && !$jaTemNotificacaoBanco) {
                            $alertaId = 'subscription_grace_' . $assinatura->id;

                            if (!isset($alertasIgnorados[$alertaId])) {
                                $itens[] = [
                                    'id' => $alertaId,
                                    'tipo' => 'alerta',
                                    'titulo' => '⏰ Plano PRO vencendo!',
                                    'mensagem' => "Seu plano venceu! Restam {$graceDaysLeft} dia(s) para renovar antes de perder o acesso PRO.",
                                    'lida' => 0,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'link' => '/billing',
                                    'icone' => '⏰',
                                    'cor' => '#e74c3c',
                                    'dinamico' => true,
                                    'priority' => 'high'
                                ];
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                error_log("⚠️ Erro ao verificar período de carência: " . $e->getMessage());
            }

            // ================================================
            // NOTIFICAÇÕES DE CAMPANHAS (nova tabela notifications)
            // ================================================
            try {
                $campaignNotifications = Notification::where('user_id', $userId)
                    ->orderBy('created_at', 'desc')
                    ->limit(50)
                    ->get();

                foreach ($campaignNotifications as $notif) {
                    // Mapear tipo para ícone e cor
                    $iconMap = [
                        'info' => ['📢', '#6b7280'],
                        'promo' => ['👑', '#f59e0b'],
                        'update' => ['🚀', '#8b5cf6'],
                        'alert' => ['⚠️', '#ef4444'],
                        'success' => ['✅', '#10b981'],
                        'reminder' => ['🔔', '#3b82f6'],
                    ];
                    $iconData = $iconMap[$notif->type] ?? $iconMap['info'];

                    $itens[] = [
                        'id' => 'campaign_' . $notif->id, // Prefixo para identificar
                        'tipo' => $notif->type,
                        'titulo' => $notif->title,
                        'mensagem' => $notif->message,
                        'lida' => (int) $notif->is_read,
                        'created_at' => $notif->created_at->format('Y-m-d H:i:s'),
                        'link' => $notif->link,
                        'icone' => $iconData[0],
                        'cor' => $iconData[1],
                        'dinamico' => false,
                        'campaign_id' => $notif->campaign_id,
                    ];
                }
            } catch (\Exception $e) {
                error_log("⚠️ Erro ao buscar notificações de campanhas: " . $e->getMessage());
            }

            // Ordenar todos os itens por created_at (mais recente primeiro)
            usort($itens, function ($a, $b) {
                $dateA = strtotime($a['created_at'] ?? '1970-01-01');
                $dateB = strtotime($b['created_at'] ?? '1970-01-01');
                return $dateB - $dateA; // Ordem decrescente (mais recente primeiro)
            });

            // Conta não lidas: lida === 0 (não lida)
            $unread = count(array_filter($itens, fn($i) => (int) ($i['lida'] ?? 1) === 0));

            Response::success([
                'itens'  => $itens,
                'unread' => $unread
            ]);
        } catch (Throwable $e) {
            error_log("❌ Erro ao buscar notificações: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            // Retorna sucesso com array vazio ao invés de erro 500
            Response::success([
                'itens' => [],
                'unread' => 0
            ]);
        }
    }

    public function unreadCount(): void
    {
        try {
            $this->requireAuthApi();
            $userId = $this->userId;

            // Garante que a sessão está iniciada
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Lê e limpa alertas ignorados da sessão, depois libera o lock
            // para permitir requisições paralelas
            $alertasIgnorados = $this->limparAlertasExpirados($_SESSION['alertas_ignorados'] ?? []);
            $_SESSION['alertas_ignorados'] = $alertasIgnorados;
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }

            // Conta notificações não lidas do banco
            $qtd = Notificacao::where('user_id', $userId)
                ->where('lida', false)
                ->count();

            // Adiciona alertas de cartões (excluindo os ignorados)
            try {
                $cartaoService = new CartaoCreditoService();
                $faturaService = new CartaoFaturaService();

                $alertasVencimento = $faturaService->verificarVencimentosProximos($userId);
                $alertasLimite = $cartaoService->verificarLimitesBaixos($userId);

                // Conta apenas alertas não ignorados
                foreach ($alertasVencimento as $alerta) {
                    $alertaId = 'cartao_venc_' . $alerta['cartao_id'];
                    if (!isset($alertasIgnorados[$alertaId])) {
                        $qtd++;
                    }
                }

                foreach ($alertasLimite as $alerta) {
                    $alertaId = 'cartao_lim_' . $alerta['cartao_id'];
                    if (!isset($alertasIgnorados[$alertaId])) {
                        $qtd++;
                    }
                }
            } catch (\Exception $e) {
                // Se falhar, só retorna as notificações do banco
                error_log("⚠️ Erro ao buscar alertas de cartões: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
            }

            // Conta notificações de campanhas não lidas
            try {
                $qtd += Notification::where('user_id', $userId)
                    ->where('is_read', false)
                    ->count();
            } catch (\Exception $e) {
                error_log("⚠️ Erro ao contar notificações de campanhas: " . $e->getMessage());
            }

            Response::success(['unread' => (int)$qtd]);
        } catch (\Throwable $e) {
            error_log("❌ Erro ao buscar contagem não lidas: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            // Retorna 0 ao invés de erro 500
            Response::success(['unread' => 0]);
        }
    }
    private function limparAlertasExpirados(array $alertasIgnorados): array
    {
        $agora = time();
        $limite24h = 24 * 60 * 60; // 24 horas em segundos

        foreach ($alertasIgnorados as $alertaId => $timestamp) {
            // Remove se passou mais de 24 horas
            if (($agora - $timestamp) > $limite24h) {
                unset($alertasIgnorados[$alertaId]);
            }
        }

        return $alertasIgnorados;
    }

    public function marcarLida(): void
    {
        $this->requireAuthApi();
        $userId = $this->userId;

        // Garante que a sessão está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $rawIds = (array)($_POST['ids'] ?? []);

        // Separar IDs numéricos (do banco) de IDs de alertas dinâmicos (strings) e campanhas
        $idsNumericos = [];
        $idsDinamicos = [];
        $idsCampanhas = [];

        foreach ($rawIds as $id) {
            if (is_numeric($id) && (int)$id > 0) {
                $idsNumericos[] = (int)$id;
            } elseif (is_string($id) && str_starts_with($id, 'campaign_')) {
                // Notificações de campanhas
                $campaignNotifId = (int) str_replace('campaign_', '', $id);
                if ($campaignNotifId > 0) {
                    $idsCampanhas[] = $campaignNotifId;
                }
            } elseif (is_string($id) && !empty($id)) {
                // Alertas dinâmicos (cartao_venc_*, cartao_lim_*, subscription_grace_*, etc.)
                $idsDinamicos[] = $id;
            }
        }

        // Marcar notificações do banco (legadas) como lidas
        if (!empty($idsNumericos)) {
            Notificacao::where('user_id', $userId)
                ->whereIn('id', $idsNumericos)
                ->update(['lida' => true]);
        }

        // Marcar notificações de campanhas como lidas
        if (!empty($idsCampanhas)) {
            Notification::where('user_id', $userId)
                ->whereIn('id', $idsCampanhas)
                ->update([
                    'is_read' => true,
                    'read_at' => now()
                ]);
        }

        // Marcar alertas dinâmicos como ignorados
        if (!empty($idsDinamicos)) {
            if (!isset($_SESSION['alertas_ignorados'])) {
                $_SESSION['alertas_ignorados'] = [];
            }

            $timestamp = time();
            foreach ($idsDinamicos as $alertaId) {
                $_SESSION['alertas_ignorados'][$alertaId] = $timestamp;
            }
        }

        if (empty($idsNumericos) && empty($idsDinamicos) && empty($idsCampanhas)) {
            Response::validationError(['ids' => 'Nenhum ID de notificação válido fornecido.']);
            return;
        }

        Response::success(['message' => 'Notificações marcadas como lidas']);
    }


    public function marcarTodasLidas(): void
    {
        $this->requireAuthApi();
        $userId = $this->userId;

        // Garante que a sessão está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Marca notificações do banco (legadas) como lidas
        Notificacao::where('user_id', $this->userId)
            ->where('lida', false)
            ->update(['lida' => true]);

        // Marca notificações de campanhas como lidas
        try {
            Notification::where('user_id', $userId)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now()
                ]);
        } catch (\Exception $e) {
            error_log("⚠️ Erro ao marcar notificações de campanhas: " . $e->getMessage());
        }

        // Marca alertas dinâmicos como ignorados com timestamp
        if (!isset($_SESSION['alertas_ignorados'])) {
            $_SESSION['alertas_ignorados'] = [];
        }

        $timestamp = time();

        // Busca alertas atuais e adiciona à lista de ignorados
        try {
            $cartaoService = new CartaoCreditoService();
            $faturaService = new CartaoFaturaService();

            $alertasVencimento = $faturaService->verificarVencimentosProximos($userId);
            $alertasLimite = $cartaoService->verificarLimitesBaixos($userId);

            foreach ($alertasVencimento as $alerta) {
                $_SESSION['alertas_ignorados']['cartao_venc_' . $alerta['cartao_id']] = $timestamp;
            }

            foreach ($alertasLimite as $alerta) {
                $_SESSION['alertas_ignorados']['cartao_lim_' . $alerta['cartao_id']] = $timestamp;
            }
        } catch (\Exception $e) {
            error_log("⚠️ Erro ao marcar alertas dinâmicos: " . $e->getMessage());
        }

        Response::success(['message' => 'Todas as notificações foram marcadas como lidas']);
    }

    /**
     * Verifica se há notificações de recompensa de indicação não lidas
     * GET /api/notificacoes/referral-rewards
     * 
     * Retorna notificações de referral para mostrar modal de parabéns
     */
    public function getReferralRewards(): void
    {
        $this->requireAuthApi();

        // Liberar lock da sessão para permitir requisições paralelas
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        try {
            // Busca notificações de referral não lidas
            $rewards = Notificacao::where('user_id', $this->userId)
                ->where('lida', 0)
                ->where(function ($query) {
                    $query->where('tipo', 'referral_referred')
                        ->orWhere('tipo', 'referral_referrer');
                })
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($n) {
                    return [
                        'id' => $n->id,
                        'tipo' => $n->tipo,
                        'titulo' => $n->titulo,
                        'mensagem' => $n->mensagem,
                        'created_at' => $n->created_at?->toDateTimeString(),
                    ];
                })
                ->toArray();

            Response::success([
                'rewards' => $rewards,
                'count' => count($rewards),
            ], 'Recompensas de indicação');
        } catch (Throwable $e) {
            error_log("❌ Erro ao buscar recompensas de referral: " . $e->getMessage());
            Response::error('Erro ao buscar recompensas', 500);
        }
    }

    /**
     * Marca notificações de referral como vistas
     * POST /api/notificacoes/referral-rewards/seen
     */
    public function markReferralRewardsSeen(): void
    {
        $this->requireAuthApi();

        try {
            $payload = $this->getRequestPayload();
            $ids = $payload['ids'] ?? [];

            if (!empty($ids) && is_array($ids)) {
                Notificacao::where('user_id', $this->userId)
                    ->whereIn('id', $ids)
                    ->update(['lida' => 1]);
            }

            Response::success(['message' => 'Recompensas marcadas como vistas']);
        } catch (Throwable $e) {
            error_log("❌ Erro ao marcar recompensas como vistas: " . $e->getMessage());
            Response::error('Erro ao marcar recompensas', 500);
        }
    }
}
