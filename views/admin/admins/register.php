<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Painel Administrativo</title>

    <!-- Ícones -->
    <link rel="apple-touch-icon" sizes="76x76" href="<?= BASE_URL ?>/assets/img/apple-icon.png">
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/favicon.png">

    <!-- Fonts e ícones -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- Nucleo Icons -->
    <link href="https://demos.creative-tim.com/soft-ui-dashboard/assets/css/nucleo-icons.css" rel="stylesheet">
    <link href="https://demos.creative-tim.com/soft-ui-dashboard/assets/css/nucleo-svg.css" rel="stylesheet">

    <!-- CSS Principal -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/soft-ui-dashboard.css?v=1.1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-dt/css/jquery.dataTables.min.css">
    <?php loadPageCss(); ?>

</head>

<body>
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <main class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-xl-7">
                    <div class="registration-card">
                        <div class="card-header">
                            <h3><i class="fas fa-user-plus me-2"></i>Criar Conta</h3>
                            <p class="subtitle">Junte-se à nossa plataforma médica</p>
                        </div>

                        <div class="card-body">
                            <form id="registerForm" method="POST" action="<?= BASE_URL ?>admin/novo/salvar" autocomplete="off">
                                <?php $form_data = $form_data ?? []; ?>

                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="nome_completo" class="form-label">
                                            <i class="fas fa-user me-1"></i>Nome completo
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-address-card"></i>
                                            </span>
                                            <input type="text" class="form-control" name="nome_completo" id="nome_completo"
                                                value="<?= htmlspecialchars($form_data['nome_completo'] ?? '') ?>"
                                                placeholder="Digite seu nome completo" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="username" class="form-label">
                                            <i class="fas fa-at me-1"></i>Nome de usuário
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-user"></i>
                                            </span>
                                            <input type="text" class="form-control" name="username" id="username"
                                                value="<?= htmlspecialchars($form_data['username'] ?? '') ?>"
                                                placeholder="Escolha um nome de usuário" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="email" class="form-label">
                                            <i class="fas fa-envelope me-1"></i>E-mail
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-envelope"></i>
                                            </span>
                                            <input type="email" class="form-control" name="email" id="email"
                                                value="<?= htmlspecialchars($form_data['email'] ?? '') ?>"
                                                placeholder="Digite seu e-mail" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="telefone" class="form-label">
                                            <i class="fas fa-phone me-1"></i>Telefone
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-phone"></i>
                                            </span>
                                            <input type="tel" class="form-control" name="telefone" id="telefone"
                                                value="<?= htmlspecialchars($form_data['telefone'] ?? '') ?>"
                                                placeholder="(00) 00000-0000" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="nome_clinica" class="form-label">
                                            <i class="fas fa-clinic-medical me-1"></i>Nome da clínica
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-clinic-medical"></i>
                                            </span>
                                            <input type="text" class="form-control" name="nome_clinica" id="nome_clinica"
                                                value="<?= htmlspecialchars($form_data['nome_clinica'] ?? '') ?>"
                                                placeholder="Digite o nome da sua clínica" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="cpf_cnpj" class="form-label">
                                            <i class="fas fa-id-card me-1"></i>CPF ou CNPJ
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-id-card"></i>
                                            </span>
                                            <input type="text" class="form-control" name="cpf_cnpj" id="cpf_cnpj"
                                                value="<?= htmlspecialchars($form_data['cpf_cnpj'] ?? '') ?>"
                                                placeholder="Digite seu CPF ou CNPJ" required>
                                        </div>
                                        <small class="form-text text-muted mt-1">
                                            <i class="fas fa-info-circle me-1"></i>Digite um CPF (11 dígitos) ou CNPJ (14 dígitos)
                                        </small>
                                    </div>

                                    <div class="form-group">
                                        <label for="password" class="form-label">
                                            <i class="fas fa-lock me-1"></i>Senha
                                        </label>
                                        <div class="password-group">
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-lock"></i>
                                                </span>
                                                <input type="password" class="form-control" name="password" id="password"
                                                    placeholder="Digite sua senha" required>
                                                <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            <div class="password-strength">
                                                <div class="password-strength-bar" id="passwordStrengthBar"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="confirm_password" class="form-label">
                                            <i class="fas fa-shield-alt me-1"></i>Confirmar senha
                                        </label>
                                        <div class="password-group">
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-shield-alt"></i>
                                                </span>
                                                <input type="password" class="form-control" name="confirm_password"
                                                    id="confirm_password" placeholder="Confirme sua senha" required>
                                                <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="submit-btn">
                                    <i class="fas fa-user-plus me-2"></i>Criar minha conta
                                </button>
                            </form>

                            <div class="login-link-container">
                                <p class="mb-0">Já possui uma conta?
                                    <a href="<?= BASE_URL ?>admin/login" class="login-link">
                                        <i class="fas fa-sign-in-alt me-1"></i>Entrar
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>