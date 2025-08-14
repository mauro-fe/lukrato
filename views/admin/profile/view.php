<?php
$pageTitle = "Visualizar Perfil";
$admin_username = $_SESSION['admin_username'] ?? 'admin';
?>

<main class="main-content position-relative border-radius-lg">
    <nav class="navbar">
        <div class="container-fluid">
            <div class="navbar-content">
                <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
                    <li class="breadcrumb-item text-sm"><span
                            class="text-muted"><?= htmlspecialchars($admin_username) ?></span></li>
                    <li class="breadcrumb-item text-sm text-dark active" aria-current="page">Perfil</li>
                </ol>
            </div>
            <h6 class="font-weight-bolder mb-0">Meu Perfil</h6>

            <ul class="navbar-nav justify-content-end">
                <li class="nav-item d-flex align-items-center">
                    <a href="<?= BASE_URL ?>admin/logout" class="nav-link text-body font-weight-bold px-0">
                        <button class="d-sm-inline d-none btn btn-primary">
                            <i class="fas fa-sign-out-alt"></i>Sair
                        </button>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Conteúdo -->
    <div class="py-4">
        <!-- Linha que agrupa os dois cards lado a lado -->
        <div class="row">
            <!-- Card do Perfil -->
            <div class="col-md-9">
                <div class="card shadow mb-4">
                    <div class="card-header text-center py-4">
                        <div class="row">
                            <div class="col">
                                <h3 class="mb-0">
                                    <i class="fas fa-user-circle me-2"></i>Meu Perfil
                                </h3>
                                <small>Informações da sua conta</small>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <div class="row">
                            <!-- Coluna Esquerda: Pessoal -->
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-user me-2"></i>Informações Pessoais
                                </h5>

                                <?php
                                $infoPessoais = [
                                    'Nome de Usuário' => $admin->username ?? 'Não informado',
                                    'Nome Completo' => $admin->nome_completo ?? 'Não informado',
                                    'E-mail' => '<i class="fas fa-envelope me-1 text-sucess"></i>' . ($admin->email ?? 'Não informado'),
                                    'Telefone' => '<i class="fas fa-phone me-1 text-success"></i>' . ($admin->telefone ?? 'Não informado'),
                                ];

                                foreach ($infoPessoais as $label => $value): ?>
                                    <div class="mb-3">
                                        <label class="form-label text-muted"><?= $label ?></label>
                                        <p class="fw-bold"><?= $value ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Coluna Direita: Clínica -->
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-clinic-medical me-2"></i>Informações da Clínica
                                </h5>

                                <?php
                                $infoClinica = [
                                    'Nome da Clínica' => $admin->nome_clinica ?? 'Não informado',
                                    'Razão Social' => $admin->razao_social ?? 'Não informado',
                                    'CNPJ' => '<i class="fas fa-id-card me-1 text-success"></i>' . ($admin->cpf_cnpj ?? 'Não informado'),
                                ];

                                foreach ($infoClinica as $label => $value): ?>
                                    <div class="mb-3">
                                        <label class="form-label text-muted"><?= $label ?></label>
                                        <p class="fw-bold"><?= $value ?></p>
                                    </div>
                                <?php endforeach; ?>

                                <div class="mb-3">
                                    <label class="form-label text-muted">Status da Conta</label>
                                    <p>
                                        <?php if (!empty($admin->ativo)): ?>
                                            <span class="badge bg-success"><i class="fas fa-check me-1"></i>Ativa</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><i class="fas fa-times me-1"></i>Inativa</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Informações da Conta -->
                        <hr class="my-4">
                        <div class="row">
                            <div class="col-12">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-info-circle me-2"></i>Informações da Conta
                                </h5>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted">Membro desde</label>
                                <p class="fw-bold">
                                    <i class="fas fa-calendar me-1 text-info"></i>
                                    <?= isset($admin->created_at) ? date('d/m/Y H:i', strtotime($admin->created_at)) : 'Não informado' ?>
                                </p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted">Último Login</label>
                                <p class="fw-bold">
                                    <i class="fas fa-clock me-1 text-warning"></i>
                                    <?= isset($admin->ultimo_login) && $admin->ultimo_login ? date('d/m/Y H:i', strtotime($admin->ultimo_login)) : 'Primeiro acesso' ?>
                                </p>
                            </div>
                        </div>

                        <!-- Ações -->
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <a href="<?= BASE_URL ?>admin/<?= $admin_username ?>/dashboard"
                                class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Voltar ao Dashboard
                            </a>
                            <div>
                                <a href="<?= BASE_URL ?>admin/<?= $admin_username ?>/perfil/editar"
                                    class="btn btn-primary me-2">
                                    <i class="fas fa-edit me-1"></i>Editar Perfil
                                </a>
                                <a href="<?= BASE_URL ?>admin/<?= $admin_username ?>/editCredentials"
                                    class="btn btn-outline-warning">
                                    <i class="fas fa-key me-1"></i>Alterar Senha
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Card Estatísticas -->
            <div class="col-md-3">
                <div class="card shadow mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line me-2 text-success"></i>Estatísticas Rápidas
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center">
                            <div class="mb-4">
                                <i class="fas fa-file-medical fa-2x text-primary mb-2"></i>
                                <h4 class="fw-bold text-primary"><?= $totalFichas ?? 0 ?></h4>
                                <p class="text-muted mb-0">Fichas Cadastradas</p>
                            </div>
                            <div class="mb-4">
                                <i class="fas fa-question-circle fa-2x text-info mb-2"></i>
                                <h4 class="fw-bold text-info"><?= $totalPerguntas ?? 0 ?></h4>
                                <p class="text-muted mb-0">Perguntas Criadas</p>
                            </div>
                            <div>
                                <i class="fas fa-calendar fa-2x text-success mb-2"></i>
                                <h4 class="fw-bold text-success">
                                    <?= isset($admin->created_at) ? date_diff(date_create($admin->created_at), date_create())->days : 0 ?>
                                </h4>
                                <p class="text-muted mb-0">Dias de Uso</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- SweetAlert2: mensagens -->
<?php if (!empty($error)): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: '<?= htmlspecialchars($error) ?>',
                confirmButtonColor: '#e74a3b'
            });
        });
    </script>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: '<?= htmlspecialchars($success) ?>',
                confirmButtonColor: '#28a745'
            });
        });
    </script>
<?php endif; ?>