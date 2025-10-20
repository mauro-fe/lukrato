<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Notificacao;
use Exception;

class NotificacaoController extends BaseController
{
    /**
     * Retorna todas as notificações do usuário logado
     */
    public function index()
    {
        $this->requireAuthApi();

        try {
            $itens = Notificacao::where('user_id', $this->userId)
                ->orderBy('created_at', 'desc')
                ->get();

            $unread = $itens->where('lida', 0)->count();

            Response::success([
                'itens' => $itens,
                'unread' => $unread
            ]);
        } catch (Exception $e) {
            Response::error('Falha ao buscar notificações', 500, [
                'exception' => $e->getMessage()
            ]);
        }
    }

    /**
     * Retorna a contagem de notificações não lidas
     */
    public function unreadCount()
    {
        $this->requireAuthApi();

        $qtd = Notificacao::where('user_id', $this->userId)
            ->where('lida', 0)
            ->count();

        Response::success(['unread' => $qtd]);
    }

    /**
     * Marca uma lista de notificações como lidas
     */
    public function marcarLida()
    {
        $this->requireAuthApi();

        $ids = $_POST['ids'] ?? [];
        if (!is_array($ids) || !count($ids)) {
            Response::validationError(['ids' => 'Selecione ao menos uma notificação']);
            return;
        }

        $ids = array_values(array_filter(array_map('intval', $ids), static fn(int $id) => $id > 0));
        if (!$ids) {
            Response::validationError(['ids' => 'IDs inválidos']);
            return;
        }

        Notificacao::where('user_id', $this->userId)
            ->whereIn('id', $ids)
            ->update(['lida' => 1]);

        Response::success(['message' => 'Notificações marcadas como lidas']);
    }

    /**
     * Marca todas as notificações do usuário como lidas
     */
    public function marcarTodasLidas()
    {
        $this->requireAuthApi();

        Notificacao::where('user_id', $this->userId)
            ->where('lida', 0)
            ->update(['lida' => 1]);

        Response::success(['message' => 'Todas as notificações foram marcadas como lidas']);
    }
}
