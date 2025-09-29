<?php
$pageTitle = "Editar Perfil";
$admin_username = $_SESSION['admin_username'] ?? 'admin';
?>

<main class="main-content position-relative border-radius-lg">
    <nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-sm border-radius-xl bg-white" id="navbarBlur"
        navbar-scroll="true">
        <div class="container-fluid py-1 px-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
                    <li class="breadcrumb-item text-sm">
                        <a href="<?= BASE_URL ?>perfil" class="text-muted"><?= $admin_username ?></a>
                    </li>
                    <li class="breadcrumb-item text-sm text-dark active" aria-current="page">Editar Perfil</li>
                </ol>
                <h6 class="font-weight-bolder mb-0">Editar Perfil</h6>
            </nav>

            <ul class="navbar-nav justify-content-end">
                <li class="nav-item d-flex align-items-center">
                    <a href="<?= BASE_URL ?>logout" class="nav-link text-body font-weight-bold px-0">
                        <button class="d-sm-inline d-none btn btn-primary mt-3 ms-3">
                            <i class="fas fa-sign-out-alt me-1"></i>Sair
                        </button>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-gradient-primary text-white text-center py-4">
                        <h3 class="mb-0 text-white">
                            <i class="fas fa-edit me-2"></i>Editar Perfil
                        </h3>
                        <small class="text-white-50">Atualize suas informações pessoais</small>
                    </div>

                    <div class="card-body p-4">
                        <form id="form-profile" action="<?= BASE_URL ?>api/perfil"
                            method="POST" autocomplete="off" novalidate>
                            <?= csrf_input() ?>

                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="text-primary mb-3">
                                        <i class="fas fa-user me-2"></i>Dados Pessoais
                                    </h5>

                                    <div class="form-group mb-3">
                                        <label for="username" class="form-label">
                                            Nome de Usuário <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            <input type="text" class="form-control" id="username" name="username"
                                                value="<?= htmlspecialchars($admin->username ?? '') ?>" required
                                                minlength="3" maxlength="30" pattern="^[a-zA-Z0-9_]{3,30}$">
                                            <div class="invalid-feedback">
                                                O nome de usuário deve ter entre 3 e 30 caracteres (apenas letras,
                                                números e _).
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label for="nome_completo" class="form-label">
                                            Nome Completo <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                            <input type="text" class="form-control" id="nome_completo"
                                                name="nome_completo"
                                                value="<?= htmlspecialchars($admin->nome_completo ?? '') ?>" required
                                                maxlength="100">
                                            <div class="invalid-feedback">
                                                Por favor, informe seu nome completo.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label for="email" class="form-label">
                                            E-mail <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            <input type="email" class="form-control" id="email" name="email"
                                                value="<?= htmlspecialchars($admin->email ?? '') ?>" required
                                                maxlength="100">
                                            <div class="invalid-feedback">
                                                Por favor, insira um endereço de e-mail válido.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label for="telefone" class="form-label">Telefone</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                            <input type="tel" class="form-control" id="telefone" name="telefone"
                                                value="<?= htmlspecialchars($admin->telefone ?? '') ?>" maxlength="20"
                                                placeholder="(00) 00000-0000">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <h5 class="text-primary mb-3">
                                        <i class="fas fa-clinic-medical me-2"></i>Dados da Clínica
                                    </h5>

                                    <div class="form-group mb-3">
                                        <label for="nome_clinica" class="form-label">Nome da Clínica</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-hospital"></i></span>
                                            <input type="text" class="form-control" id="nome_clinica"
                                                name="nome_clinica"
                                                value="<?= htmlspecialchars($admin->nome_clinica ?? '') ?>"
                                                maxlength="100">
                                        </div>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label for="razao_social" class="form-label">Razão Social</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                                            <input type="text" class="form-control" id="razao_social"
                                                name="razao_social"
                                                value="<?= htmlspecialchars($admin->razao_social ?? '') ?>"
                                                maxlength="100">
                                        </div>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label for="cnpj" class="form-label">CNPJ</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                            <input type="text" class="form-control" id="cpf_cnpj" name="cpf_cnpj"
                                                value="<?= htmlspecialchars($admin->cpf_cnpj ?? '') ?>" maxlength="18"
                                                placeholder="00.000.000/0001-00">
                                        </div>
                                    </div>

                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <small>
                                            <strong>Dica:</strong> Mantenha seus dados sempre atualizados para
                                            garantir o melhor funcionamento do sistema.
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <a href="<?= BASE_URL ?>perfil"
                                    class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-1"></i>Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary" id="btn-save">
                                    <i class="fas fa-save me-1"></i>Salvar Alterações
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Exibir mensagens se houver -->
<?php if (isset($error) && !empty($error)): ?>
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

<?php if (isset($success) && !empty($success)): ?>
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
