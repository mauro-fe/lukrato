<?php
namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;

class CategoryController extends BaseController
{
    public function index(?string $username = null): void
    {
        $this->requireAuth();

        // Se a rota tiver {username} e ele for diferente do logado, bloqueia
        // if ($username !== null && $this->adminUsername !== $username) {
        //     $this->redirect('login');
        //     return;
        // }

    
         $this->render(
            'admin/categorias/index',
            [],
            'admin/partials/header',
            null
        );
    }
}
