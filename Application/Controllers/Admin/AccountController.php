<?php

namespace Application\Controllers\Admin;

use Application\Models\Account;
use GUMP;

class AccountController extends AdminController
{
    public function index(string $username)
    {
        $accounts = Account::where('user_id', $this->userId())->orderBy('name')->get();
        $csrf_token = $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $this->renderAdmin('admin/accounts/index', compact('accounts', 'csrf_token'));
    }

    public function store(string $username)
    {
        $this->requirePost();
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            $this->setError('CSRF inválido');
            return $this->redirect('admin/' . $this->username() . '/accounts');
        }
        $v = new GUMP();
        $data = $v->sanitize($_POST);
        $rules = ['name' => 'required|min_len,2', 'type' => 'required|contains_list,checking;savings;broker', 'currency' => 'required|exact_len,3'];
        if ($v->validate($data, $rules) !== true) {
            $this->setError('Dados inválidos');
            return $this->redirect('admin/' . $this->username() . '/accounts');
        }
        Account::create([
            'user_id' => $this->userId(),
            'name' => $data['name'],
            'type' => $data['type'],
            'currency' => strtoupper($data['currency']),
            'balance_cached' => 0
        ]);
        $this->setSuccess('Conta criada');
        return $this->redirect('admin/' . $this->username() . '/accounts');
    }

    public function destroy(string $username, int $id)
    {
        Account::where('user_id', $this->userId())->where('id', $id)->delete();
        $this->setSuccess('Conta excluída');
        return $this->redirect('admin/' . $this->username() . '/accounts');
    }
}
