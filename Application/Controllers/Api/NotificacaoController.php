<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Notificacao;
use Application\Services\CartaoFaturaService;
use Application\Services\CartaoCreditoService;
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
                        'lida' => false,
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
                        'lida' => false,
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

            $unread = count(array_filter($itens, fn($i) => !($i['lida'] ?? true)));

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

                // Recupera e limpa alertas ignorados
                $alertasIgnorados = $this->limparAlertasExpirados($_SESSION['alertas_ignorados'] ?? []);
                $_SESSION['alertas_ignorados'] = $alertasIgnorados;

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

            Response::success(['unread' => (int)$qtd]);
        } catch (\Throwable $e) {
            error_log("❌ Erro ao buscar contagem não lidas: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            // Retorna 0 ao invés de erro 500
            Response::success(['unread' => 0]);
        }
    }

    /**
     * Limpa alertas expirados (mais de 24 horas)
     */
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

        $rawIds = (array)($_POST['ids'] ?? []);

        $ids = array_values(
            array_filter(
                array_map('intval', $rawIds),
                static fn(int $id): bool => $id > 0
            )
        );

        if (empty($ids)) {
            Response::validationError(['ids' => 'Nenhum ID de notificação válido fornecido.']);
            return;
        }

        Notificacao::where('user_id', $userId)
            ->whereIn('id', $ids)
            ->update(['lida' => true]);

        Response::success(['message' => 'Notificações marcadas como lidas']);
    }


    public function marcarTodasLidas(): void
    {
        $this->requireAuthApi();
        $userId = $this->userId;

        // Marca notificações do banco como lidas
        Notificacao::where('user_id', $this->userId)
            ->where('lida', false)
            ->update(['lida' => true]);

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
}
