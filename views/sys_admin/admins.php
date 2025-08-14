<main class="main-content position-relative">
    <!-- Navbar melhorada -->
    <div class="container-fluid py-3 px-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-transparent mb-2 pb-0 pt-1 px-0">
                <li class="breadcrumb-item">
                    <a href="#" class="text-muted">admin_username</a>
                </li>
                <li class="breadcrumb-item text-dark active" aria-current="page">Fichas</li>
            </ol>
            <h4 class="font-weight-bold mb-0 text-dark">
                <i class="fas fa-file-medical me-3 text-primary"></i>Gestão de Fichas
            </h4>
        </nav>

        <h1>Gerenciar Administradores</h1>

        <table class="table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Telefone</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td><?= htmlspecialchars($admin->username) ?></td>
                        <td><?= htmlspecialchars($admin->email) ?></td>
                        <td><?= htmlspecialchars($admin->telefone) ?></td>
                        <td><?= $admin->ativo ? 'Ativo' : 'Inativo' ?></td>
                        <td>
                            <?php if (!$admin->ativo): ?>
                                <a href="<?= BASE_URL . 'sysadmin/admins/autorizar/' . $admin->id ?>"
                                    class="btn btn-success btn-sm">Autorizar</a>
                            <?php else: ?>
                                <a href="<?= BASE_URL . 'sysadmin/admins/bloquear/' . $admin->id ?>"
                                    class="btn btn-warning btn-sm">Bloquear</a>
                            <?php endif; ?>
                            <a href="<?= BASE_URL . 'sysadmin/admins/editar/' . $admin->id ?>"
                                class="btn btn-primary btn-sm">Editar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>