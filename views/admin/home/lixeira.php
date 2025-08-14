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

        <?php $this->setHeader('admin/includes/header'); ?>

        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0"><i class="fas fa-trash-alt me-2"></i> Fichas na Lixeira</h3>
                <a href="<?= BASE_URL . 'admin/' . $admin->username . '/dashboard' ?>"
                    class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Voltar ao Dashboard
                </a>
            </div>

            <?php if (count($fichas) === 0): ?>
            <div class="alert alert-info text-center">
                Nenhuma ficha na lixeira.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Status</th>
                            <th>Atualizada em</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fichas as $ficha): ?>
                        <tr data-id="<?= $ficha->id ?>">
                            <td>#<?= str_pad($ficha->id, 3, '0', STR_PAD_LEFT) ?></td>
                            <td><?= htmlspecialchars($ficha->nome_completo ?? '---') ?></td>
                            <td><span class="badge bg-warning text-dark">Excluída</span></td>
                            <td><?= date('d/m/Y H:i', strtotime($ficha->updated_at)) ?></td>
                            <td>
                                <button class="btn btn-sm btn-danger" title="Excluir Definitivamente"
                                    onclick="excluirDefinitivamente(<?= $ficha->id ?>)">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                                <button class="btn btn-sm btn-success" title="Restaurar Ficha"
                                    onclick="restaurarFicha(<?= $ficha->id ?>)">
                                    <i class="fas fa-undo"></i>
                                </button>

                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <script>
        const BASE_URL = '<?= BASE_URL ?>';
        const USERNAME = '<?= $admin->username ?>';
        </script>


        <?php $this->setFooter('admin/includes/footer'); ?>

        <script>
        async function confirmarExclusao(url, mensagem, tituloSucesso, onSuccess) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const result = await Swal.fire({
                title: 'Confirmar exclusão',
                text: mensagem,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim',
                cancelButtonText: 'Cancelar',
                showLoaderOnConfirm: true,
                preConfirm: async () => {
                    try {
                        const response = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-Token': csrfToken
                            }
                        });

                        const text = await response.text();
                        console.log('Resposta bruta:', text);

                        const data = JSON.parse(text);
                        if (!data || data.status !== 'success') throw new Error(data.message ||
                            'Erro inesperado.');
                        return data;
                    } catch (err) {
                        Swal.showValidationMessage(err.message);
                    }
                }
            });

            if (result.isConfirmed && result.value && result.value.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: tituloSucesso,
                    text: result.value.message,
                    timer: 2000,
                    showConfirmButton: false
                });

                if (typeof onSuccess === 'function') onSuccess();
            }
        }

        function excluirDefinitivamente(id) {
            const url = `${BASE_URL}admin/${USERNAME}/fichas/${id}/excluir-definitivamente`;

            confirmarExclusao(url, 'Deseja excluir esta ficha permanentemente? Esta ação não poderá ser desfeita.',
                'Ficha excluída!', () => {
                    document.querySelector(`tr[data-id="${id}"]`).remove();
                });
        }

        function restaurarFicha(id) {
            const url = `${BASE_URL}admin/${USERNAME}/fichas/${id}/restaurar`;

            confirmarExclusao(url, 'Deseja restaurar esta ficha da lixeira?', 'Ficha restaurada!', () => {
                document.querySelector(`tr[data-id="${id}"]`).remove();
            });
        }
        </script>