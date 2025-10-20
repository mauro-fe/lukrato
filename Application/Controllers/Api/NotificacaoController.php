<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Notificacao;

class NotificacaoController extends BaseController
{
    public function index()
    {
        $this->requireAuthApi();

        $itens = Notificacao::where('user_id', $this->userId)
            ->orderBy('created_at', 'desc')
            ->get();

        Response::success(['itens' => $itens]);
    }

    public function unreadCount()
    {
        $this->requireAuthApi();

        error_log(sprintf(
            '[NotificacaoController@unreadCount] user_id=%s request_uri=%s',
            $this->userId ?? 'null',
            $_SERVER['REQUEST_URI'] ?? '-'
        ));

        $qtd = Notificacao::where('user_id', $this->userId)
            ->where('lida', 0)
            ->count();

        Response::success(['unread' => $qtd]);
    }

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
            Response::validationError(['ids' => 'Selecione ao menos uma notificação válida']);
            return;
        }

        Notificacao::where('user_id', $this->userId)
            ->whereIn('id', $ids)
            ->update(['lida' => 1]);

        Response::success();
    }
}
