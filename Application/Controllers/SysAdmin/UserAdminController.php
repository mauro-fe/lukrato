<?php

namespace Application\Controllers\SysAdmin;

use Application\Controllers\BaseController;
use Application\Lib\Auth;
use Application\Models\Usuario;

class UserAdminController extends BaseController
{
    public function list(): void
    {
        $this->requireAuth();
        $user = Auth::user();
        if (!$user || $user->is_admin != 1) {
            http_response_code(403);
            echo json_encode(['error' => 'Acesso negado']);
            return;
        }

        $query = $_GET['query'] ?? '';
        $status = $_GET['status'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int)($_GET['perPage'] ?? 10)));
        $offset = ($page - 1) * $perPage;

        $usuarios = Usuario::query();
        if ($query) {
            $usuarios = $usuarios->where(function ($q) use ($query) {
                $q->where('nome', 'LIKE', "%$query%")
                    ->orWhere('email', 'LIKE', "%$query%")
                    ->orWhere('id', $query);
            });
        }
        if ($status === 'admin') {
            $usuarios = $usuarios->where('is_admin', 1);
        } elseif ($status === 'user') {
            $usuarios = $usuarios->where('is_admin', 0);
        }
        $total = $usuarios->count();
        $usuarios = $usuarios->orderByDesc('id')->limit($perPage)->offset($offset)->get();

        echo json_encode([
            'success' => true,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'users' => $usuarios,
        ]);
    }
}
