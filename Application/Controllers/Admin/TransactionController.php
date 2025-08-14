<?php

namespace Application\Controllers\Admin;

use Application\Models\{Transaction, Account, Category};
use GUMP;

class TransactionController extends AdminController
{
    public function index(string $username)
    {
        $month = $_GET['month'] ?? date('Y-m');
        $accounts = Account::where('user_id', $this->userId())->get();
        $categories = Category::where('user_id', $this->userId())->orderBy('name')->get();
        $items = Transaction::where('user_id', $this->userId())
            ->whereRaw('DATE_FORMAT(date, "%Y-%m") = ?', [$month])
            ->orderBy('date', 'desc')->get();
        $csrf_token = $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $this->renderAdmin('admin/transactions/index', compact('items', 'accounts', 'categories', 'month', 'csrf_token'));
    }

    public function store(string $username)
    {
        $this->requirePost();
        $v = new GUMP();
        $data = $v->sanitize($_POST);
        $rules = [
            'date' => 'required|date',
            'amount' => 'required|float',
            'type' => 'required|contains_list,income;expense',
            'account_id' => 'required|integer',
            'category_id' => 'required|integer'
        ];
        if ($v->validate($data, $rules) !== true) {
            $this->setError('Dados inválidos');
            return $this->redirect('admin/' . $this->username() . '/transactions?month=' . urlencode($_GET['month'] ?? date('Y-m')));
        }
        Transaction::create([
            'user_id' => $this->userId(),
            'account_id' => (int)$data['account_id'],
            'category_id' => (int)$data['category_id'],
            'date' => $data['date'],
            'amount' => (float)$data['amount'],
            'type' => $data['type'],
            'notes' => trim($data['notes'] ?? '')
        ]);
        $this->setSuccess('Transação lançada');
        return $this->redirect('admin/' . $this->username() . '/transactions?month=' . urlencode($_GET['month'] ?? date('Y-m')));
    }
}
