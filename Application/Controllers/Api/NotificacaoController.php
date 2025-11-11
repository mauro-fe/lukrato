<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Notificacao;
use Throwable; // Importa Throwable

class NotificacaoController extends BaseController
{
    /**
     * Retorna todas as notificações do usuário logado.
     */
    public function index(): void
    {
        $this->requireAuthApi();
        $userId = $this->userId;

        try {
            /** @var \Illuminate\Support\Collection $itens */
            $itens = Notificacao::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get();

            $unread = $itens->where('lida', false)->count(); // Usa booleano

            Response::success([
                'itens'  => $itens,
                'unread' => $unread
            ]);
        } catch (Throwable $e) {
            Response::error('Falha ao buscar notificações', 500, [
                'exception' => $e->getMessage()
            ]);
        }
    }

    /**
     * Retorna a contagem de notificações não lidas.
     */
    public function unreadCount(): void
    {
        $this->requireAuthApi();
        $userId = $this->userId;

        $qtd = Notificacao::where('user_id', $userId)
            ->where('lida', false) // Usa booleano
            ->count();

        Response::success(['unread' => (int)$qtd]);
    }

    /**
     * Marca uma lista de notificações como lidas.
     */
    public function marcarLida(): void
    {
        $this->requireAuthApi();
        $userId = $this->userId;

        $rawIds = (array)($_POST['ids'] ?? []);
        
        // Sanitiza e filtra apenas IDs válidos (> 0)
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
            ->update(['lida' => true]); // Usa booleano

        Response::success(['message' => 'Notificações marcadas como lidas']);
    }

    /**
     * Marca todas as notificações do usuário como lidas.
     */
    public function marcarTodasLidas(): void
    {
        $this->requireAuthApi();
        $userId = $this->userId;

        Notificacao::where('user_id', $this->userId)
            ->where('lida', false)
            ->update(['lida' => true]); // Usa booleano

        Response::success(['message' => 'Todas as notificações foram marcadas como lidas']);
    }
}