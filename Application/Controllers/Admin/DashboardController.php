<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Admin\ProfileController;
use Application\Models\{Transaction, Account};

class DashboardController extends BaseController
{
    public function index(string $username)
    {
        $uid = $_SESSION['user_id'] ?? null;
        $month = $_GET['month'] ?? date('Y-m');
        $income  = Transaction::where('user_id', $uid)->where('type', 'income')
            ->whereRaw('DATE_FORMAT(date, "%Y-%m") = ?', [$month])->sum('amount');
        $expense = Transaction::where('user_id', $uid)->where('type', 'expense')
            ->whereRaw('DATE_FORMAT(date, "%Y-%m") = ?', [$month])->sum('amount');
        $balance = Account::where('user_id', $uid)->sum('balance_cached');
        $saving_rate = $income > 0 ? round((($income - $expense) / $income) * 100, 1) : 0;
        return $this->renderAdmin('admin/dashboard/index', compact('month', 'income', 'expense', 'balance', 'saving_rate'));
    }
}
