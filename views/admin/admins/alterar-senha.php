<!-- views/admin/admins/editCredentials.php -->
<style>
.card {
    border-radius: 1rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    overflow: hidden;
}

.card-header {
    background-color: #30625c;
    color: white;
    font-weight: 600;
    text-align: center;
    padding: 1.5rem 0;
    border-bottom: none;
}

.card-header h2 {
    color: #fff;
}
</style>

<!-- <?php
        // Digite a senha aqui
        $senha = 'Admin.123';

        // Gera o hash usando o algoritmo padrão (bcrypt)
        $hash = password_hash($senha, PASSWORD_DEFAULT);

        echo "Senha: $senha\n";
        echo "Hash: $hash\n";
        ?> -->

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg  ">
    <div class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-sm border-radius-xl bg-white mb-5">
        <div class="container-fluid py-1 px-3">
            <!-- Breadcrumb e Título -->
            <div class="d-flex flex-column">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent mb-1">
                        <li class="breadcrumb-item text-sm">
                            <span class="text-muted"><?= $_SESSION['admin_username'] ?? 'Admin' ?></span>
                        </li>
                        <li class="breadcrumb-item text-sm text-dark active" aria-current="page">Senha</li>
                    </ol>
                </nav>
                <h6 class="font-weight-bolder mb-0">Alterar senha</h6>
            </div>

            <div class="d-flex ms-auto mt-3">
                <!-- Botão de Logout -->
                <ul class="navbar-nav  justify-content-end flex-row">
                    <li class="nav-item d-flex align-items-center ms-3">
                        <a href="<?= BASE_URL ?>admin/logout" class="nav-link text-body font-weight-bold px-0">
                            <button class="d-sm-inline d-none btn btn-primary "><i
                                    class="fas fa-sign-out-alt me-1"></i>Sair</button>
                        </a>
                    </li>
                    <li class="nav-item d-xl-none ps-3 d-flex align-items-center">
                        <a href="javascript:;" class="nav-link text-body p-0" id="iconNavbarSidenav">
                            <div class="sidenav-toggler-inner">
                                <i class="sidenav-toggler-line"></i>
                                <i class="sidenav-toggler-line"></i>
                                <i class="sidenav-toggler-line"></i>
                            </div>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h2 class="text-center mb-4">Alterar senha</h2>
                    </div>
                    <div class="card shadow p-4">
                        <!-- CORREÇÃO: URL atualizada para o novo controller -->
                        <form id="form-credentials" action="<?= BASE_URL ?>admin/alterar-senha" method="POST"
                            autocomplete="off">
                            <?= csrf_input() ?>


                            <!-- Campo falso escondido para enganar o navegador -->
                            <input type="password" name="fakepass" style="position:absolute; top:-9999px; left:-9999px;"
                                autocomplete="off">

                            <div class="mb-3">
                                <label for="current_password" class="form-label">Senha Atual</label>
                                <input type="password" class="form-control" id="current_password"
                                    name="current_password" placeholder="Digite a senha atual"
                                    autocomplete="current-password" readonly onfocus="this.removeAttribute('readonly');"
                                    required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Nova Senha</label>
                                <input type="password" class="form-control" id="password" name="password"
                                    placeholder="Digite a nova senha" autocomplete="new-password" readonly
                                    onfocus="this.removeAttribute('readonly');" required>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirmar Nova Senha</label>
                                <input type="password" class="form-control" id="confirm_password"
                                    name="confirm_password" placeholder="Confirme a nova senha"
                                    autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly');"
                                    required>
                            </div>

                            <small id="passwordHelp" class="text-muted d-block mt-1">
                                <ul class="list-unstyled mb-0 text-muted" style="font-size: 0.9em;" id="passwordHelp">
                                    <li id="length"><i class="fas fa-times text-danger me-1"></i>Mínimo de 8 caracteres
                                    </li>
                                    <li id="uppercase"><i class="fas fa-times text-danger me-1"></i>Pelo menos 1 letra
                                        maiúscula</li>
                                    <li id="lowercase"><i class="fas fa-times text-danger me-1"></i>Pelo menos 1 letra
                                        minúscula</li>
                                    <li id="number"><i class="fas fa-times text-danger me-1"></i>Pelo menos 1 número
                                    </li>
                                    <li id="special"><i class="fas fa-times text-danger me-1"></i>Pelo menos 1 caractere
                                        especial (!@#$...)</li>
                                    <li id="matchInfo"><i class="fas fa-times text-danger me-1"></i>As senhas devem ser
                                        iguais</li>
                                </ul>
                            </small>

                            <button type="submit" class="btn btn-primary w-100">Salvar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Exibir mensagens de erro/sucesso -->
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

<script>
document.addEventListener("DOMContentLoaded", function() {
    const passwordInput = document.getElementById("password");
    const confirmInput = document.getElementById("confirm_password");

    const requirements = {
        length: {
            regex: /.{8,}/,
            element: document.getElementById("length")
        },
        uppercase: {
            regex: /[A-Z]/,
            element: document.getElementById("uppercase")
        },
        lowercase: {
            regex: /[a-z]/,
            element: document.getElementById("lowercase")
        },
        number: {
            regex: /\d/,
            element: document.getElementById("number")
        },
        special: {
            regex: /[!@#$%^&*(),.?":{}|<>]/,
            element: document.getElementById("special")
        }
    };

    function updateRequirementIcons() {
        const value = passwordInput.value;

        for (const key in requirements) {
            const {
                regex,
                element
            } = requirements[key];
            const icon = element.querySelector("i");
            if (regex.test(value)) {
                icon.classList.remove("fa-times", "text-danger");
                icon.classList.add("fa-check", "text-success");
            } else {
                icon.classList.remove("fa-check", "text-success");
                icon.classList.add("fa-times", "text-danger");
            }
        }

        // Verifica se as senhas coincidem
        const matchIcon = document.getElementById("matchInfo").querySelector("i");
        if (confirmInput.value && passwordInput.value === confirmInput.value) {
            matchIcon.classList.remove("fa-times", "text-danger");
            matchIcon.classList.add("fa-check", "text-success");
        } else {
            matchIcon.classList.remove("fa-check", "text-success");
            matchIcon.classList.add("fa-times", "text-danger");
        }
    }

    passwordInput.addEventListener("input", updateRequirementIcons);
    confirmInput.addEventListener("input", updateRequirementIcons);
});

// Tirando preenchimento automático do navegador nos formulários
document.addEventListener("DOMContentLoaded", function() {
    const fields = document.querySelectorAll("#form-credentials input[type='password']");

    fields.forEach(field => {
        // Define readonly e autocomplete via JS (mais difícil do navegador ignorar)
        field.setAttribute("readonly", true);
        field.setAttribute("autocomplete", "off");

        // Remove readonly só uma vez no foco
        field.addEventListener("focus", function() {
            field.removeAttribute("readonly");
        });
    });
});

// Validação com sweetalert2
document.querySelector("#form-credentials").addEventListener("submit", function(e) {
    e.preventDefault(); // Impede o envio padrão

    const form = e.target;
    const currentPassword = document.querySelector("#current_password").value.trim();
    const password = document.querySelector("#password").value.trim();
    const confirmPassword = document.querySelector("#confirm_password").value.trim();

    // Validação de campos vazios
    if (!currentPassword || !password || !confirmPassword) {
        return Swal.fire({
            icon: "warning",
            title: "Campos obrigatórios",
            text: "Preencha todos os campos antes de continuar."
        });
    }

    // Validação de senha forte
    const regexSenhaSegura = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&.]).{8,}$/;
    if (!regexSenhaSegura.test(password)) {
        return Swal.fire({
            icon: "warning",
            title: "Senha fraca",
            html: `
        Sua senha deve conter no mínimo:
        <ul style="text-align:left;">
            <li>8 caracteres</li>
            <li>1 letra maiúscula</li>
            <li>1 letra minúscula</li>
            <li>1 número</li>
            <li>1 caractere especial (@$!%*#?&)</li>
        </ul>
        `
        });
    }

    // Confirmação de senha
    if (password !== confirmPassword) {
        return Swal.fire({
            icon: "warning",
            title: "Senhas diferentes",
            text: "A confirmação deve ser idêntica à nova senha."
        });
    }

    // Envia se estiver tudo ok
    const formData = new FormData(form);

    fetch(form.action, {
            method: "POST",
            body: formData
        })
        .then(async response => {
            const rawText = await response.text();
            try {
                return JSON.parse(rawText);
            } catch (err) {
                console.error("Erro ao interpretar JSON:", rawText);
                throw new Error("Resposta inválida do servidor.");
            }
        })
        .then(data => {
            if (data.status === "error") {
                Swal.fire({
                    icon: "error",
                    title: "Erro!",
                    text: data.message,
                    footer: data.footer || "",
                    confirmButtonText: "OK"
                });
            } else if (data.status === "success") {
                Swal.fire({
                    icon: "success",
                    title: "Sucesso!",
                    text: data.message,
                    confirmButtonText: "OK"
                }).then(() => {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                });
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            Swal.fire({
                icon: "error",
                title: "Erro!",
                text: "Ocorreu um problema ao processar sua solicitação.",
                confirmButtonText: "OK"
            });
        });
});
</script>