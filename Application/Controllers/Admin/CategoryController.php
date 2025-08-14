<?php

namespace Application\Controllers\Admin;

use Application\Models\Category;
use GUMP;

class CategoryController extends AdminController
{
    public function index(string $username)
    {
        $categories = Category::where('user_id', $this->userId())->orderBy('name')->get();
        $csrf_token = $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $this->renderAdmin('admin/categories/index', compact('categories', 'csrf_token'));
    }

    public function store(string $username)
    {
        $this->requirePost();
        $v = new GUMP();
        $data = $v->sanitize($_POST);
        $rules = ['name' => 'required|min_len,2', 'type' => 'required|contains_list,income;expense'];
        if ($v->validate($data, $rules) !== true) {
            $this->setError('Dados inválidos');
            return $this->redirect('admin/' . $this->username() . '/categories');
        }
        Category::create(['user_id' => $this->userId(), 'name' => $data['name'], 'type' => $data['type']]);
        $this->setSuccess('Categoria criada');
        return $this->redirect('admin/' . $this->username() . '/categories');
    }

    public function destroy(string $username, int $id)
    {
        Category::where('user_id', $this->userId())->where('id', $id)->delete();
        $this->setSuccess('Categoria excluída');
        return $this->redirect('admin/' . $this->username() . '/categories');
    }
}
