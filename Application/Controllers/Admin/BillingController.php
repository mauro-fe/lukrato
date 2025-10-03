<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Lib\Auth;

class BillingController extends BaseController
{
    // GET /billing
    public function index(): void
    {
        $this->requireAuth();
        $user = Auth::user();
        $this->render('admin/billing/index', ['user' => $user]);
    }
}
