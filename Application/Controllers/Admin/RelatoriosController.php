<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Illuminate\Database\Capsule\Manager as DB;
use Application\Core\Response as HttpResponse;

class RelatoriosController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function view(): void
    {
        $this->requireAuth();
        $this->renderAdmin('admin/relatorios/relatorios', [
            'username' => $this->adminUsername ?? 'usuário',
            'menu'     => 'relatorios',
        ]);
    }
}
