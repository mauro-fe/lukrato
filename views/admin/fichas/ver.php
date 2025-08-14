<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <!-- Navbar -->
    <nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-sm border-radius-xl bg-white" id="navbarBlur"
        navbar-scroll="true">
        <div class="container-fluid py-1 px-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
                    <li class="breadcrumb-item text-sm">
                        <a href="<?= BASE_URL ?>admin/<?= $admin_username ?>/dashboard"
                            class="text-muted"><?= $admin_username ?></a>
                    </li>
                    <li class="breadcrumb-item text-sm">
                        <a href="<?= BASE_URL ?>admin/fichas" class="text-muted">Fichas</a>
                    </li>
                    <li class="breadcrumb-item text-sm text-dark active" aria-current="page">
                        Ficha #<?= htmlspecialchars($ficha->id ?? 'N/A') ?>
                    </li>
                </ol>
                <h6 class="font-weight-bolder mb-0">
                    <i class="fas fa-file-medical me-2"></i>Visualizar Ficha
                </h6>
            </nav>

            <div class="d-flex ms-auto mt-3">
                <ul class="navbar-nav d-flex justify-content-end">
                    <li class="nav-item d-flex align-items-center">
                        <a href="<?= BASE_URL ?>admin/logout" class="nav-link text-body font-weight-bold px-0">
                            <button class="d-sm-inline d-none btn btn-primary">
                                <i class="fas fa-sign-out-alt me-1"></i>Sair
                            </button>
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
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <!-- Informações da Ficha -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-gradient-primary text-white">
                        <div class="row align-items-center">
                            <div class="col">
                                <h4 class="mb-0 text-white">
                                    <i class="fas fa-file-medical me-2"></i>
                                    Ficha #<?= htmlspecialchars($ficha->id ?? 'N/A') ?>
                                </h4>
                                <p class="mb-0 text-white-50">
                                    <?= isset($nome_paciente) ? ucfirst(str_replace('-', ' ', $nome_paciente)) : 'Paciente' ?>
                                    -
                                    <?= isset($nome_clinica) ? ucfirst(str_replace('-', ' ', $nome_clinica)) : 'Clínica' ?>
                                </p>
                            </div>
                            <div class="col-auto">
                                <div class="btn-group" role="group">
                                    <a href="<?= BASE_URL ?>admin/fichas" class="btn btn-outline-light btn-sm">
                                        <i class="fas fa-arrow-left me-1"></i>Voltar
                                    </a>
                                    <a href="<?= BASE_URL ?>admin/ficha/exportar-pdf/<?= $ficha->id ?? 0 ?>"
                                        class="btn btn-outline-light btn-sm">
                                        <i class="fas fa-file-pdf me-1"></i>PDF
                                    </a>
                                    <button class="btn btn-outline-light btn-sm"
                                        onclick="excluirFicha(<?= $ficha->id ?? 0 ?>)">
                                        <i class="fas fa-trash me-1"></i>Excluir
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <!-- Estatísticas da Ficha -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="icon icon-lg bg-primary text-white rounded-circle mx-auto mb-2">
                                        <i class="fas fa-calendar"></i>
                                    </div>
                                    <h6 class="mb-0">Data de Criação</h6>
                                    <p class="text-muted mb-0">
                                        <?= isset($ficha->created_at) ? $ficha->created_at->format('d/m/Y H:i') : 'N/A' ?>
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="icon icon-lg bg-info text-white rounded-circle mx-auto mb-2">
                                        <i class="fas fa-list"></i>
                                    </div>
                                    <h6 class="mb-0">Total de Respostas</h6>
                                    <p class="text-muted mb-0">
                                        <?= count($respostas ?? []) ?> resposta(s)
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="icon icon-lg bg-success text-white rounded-circle mx-auto mb-2">
                                        <i class="fas fa-percentage"></i>
                                    </div>
                                    <h6 class="mb-0">Preenchimento</h6>
                                    <p class="text-muted mb-0">
                                        <?= $porcentagem_preenchimento ?? 0 ?>%
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div
                                        class="icon icon-lg <?= ($ficha_completa ?? false) ? 'bg-success' : 'bg-warning' ?> text-white rounded-circle mx-auto mb-2">
                                        <i class="fas <?= ($ficha_completa ?? false) ? 'fa-check' : 'fa-clock' ?>"></i>
                                    </div>
                                    <h6 class="mb-0">Status</h6>
                                    <p class="text-muted mb-0">
                                        <?= ($ficha_completa ?? false) ? 'Completa' : 'Incompleta' ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Barra de Progresso -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-sm font-weight-bold">Progresso do Preenchimento</span>
                                <span class="text-sm font-weight-bold"><?= $porcentagem_preenchimento ?? 0 ?>%</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-gradient-primary" role="progressbar"
                                    style="width: <?= $porcentagem_preenchimento ?? 0 ?>%"
                                    aria-valuenow="<?= $porcentagem_preenchimento ?? 0 ?>" aria-valuemin="0"
                                    aria-valuemax="100">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Respostas da Ficha -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-comments me-2 text-primary"></i>
                            Respostas do Paciente
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($respostas) && count($respostas) > 0): ?>
                            <div class="row">
                                <?php foreach ($respostas as $index => $resposta): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card border-left-primary shadow-sm h-100">
                                            <div class="card-body">
                                                <div class="d-flex align-items-start">
                                                    <div class="icon-circle bg-primary text-white me-3 flex-shrink-0">
                                                        <?= $index + 1 ?>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="font-weight-bold text-primary mb-2">
                                                            <?= htmlspecialchars($resposta->pergunta ?? 'Pergunta') ?>
                                                        </h6>

                                                        <div class="resposta-content">
                                                            <?php if (!empty($resposta->resposta)): ?>
                                                                <?php
                                                                // Verifica se tem complemento (observação)
                                                                $parts = explode(' | Obs:', $resposta->resposta);
                                                                $respostaPrincipal = trim($parts[0]);
                                                                $observacao = isset($parts[1]) ? trim($parts[1]) : null;
                                                                ?>

                                                                <p class="mb-2 text-dark">
                                                                    <strong><?= nl2br(htmlspecialchars($respostaPrincipal)) ?></strong>
                                                                </p>

                                                                <?php if ($observacao): ?>
                                                                    <div class="alert alert-info py-2 px-3 mb-0">
                                                                        <small>
                                                                            <i class="fas fa-info-circle me-1"></i>
                                                                            <strong>Observação:</strong>
                                                                            <?= nl2br(htmlspecialchars($observacao)) ?>
                                                                        </small>
                                                                    </div>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <p class="text-muted italic mb-0">
                                                                    <i class="fas fa-minus me-1"></i>Não respondido
                                                                </p>
                                                            <?php endif; ?>
                                                        </div>

                                                        <small class="text-muted">
                                                            <i class="fas fa-tag me-1"></i>
                                                            Tipo: <?= ucfirst($resposta->tipo ?? 'texto') ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Nenhuma resposta encontrada</h5>
                                <p class="text-muted mb-0">Esta ficha ainda não possui respostas registradas.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Observações da Ficha -->
                <div class="card shadow">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-sticky-note me-2 text-warning"></i>
                            Observações
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($ficha->observacoes)): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?= nl2br(htmlspecialchars($ficha->observacoes)) ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-3">Nenhuma observação registrada para esta ficha.</p>
                        <?php endif; ?>

                        <!-- Formulário para Adicionar/Editar Observação -->
                        <form id="form-observacao" onsubmit="salvarObservacao(event)">
                            <div class="mb-3">
                                <label for="observacoes" class="form-label">
                                    <?= !empty($ficha->observacoes) ? 'Editar' : 'Adicionar' ?> Observação:
                                </label>
                                <textarea class="form-control" id="observacoes" name="observacoes" rows="3"
                                    maxlength="1000"
                                    placeholder="Digite suas observações sobre esta ficha..."><?= htmlspecialchars($ficha->observacoes ?? '') ?></textarea>
                                <div class="form-text">Máximo de 1000 caracteres.</div>
                            </div>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save me-1"></i>
                                <?= !empty($ficha->observacoes) ? 'Atualizar' : 'Adicionar' ?> Observação
                            </button>
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

<script>
    // Função para salvar observação
    function salvarObservacao(event) {
        event.preventDefault();

        const form = event.target;
        const observacoes = form.observacoes.value.trim();
        const fichaId = <?= $ficha->id ?? 0 ?>;

        if (!observacoes) {
            Swal.fire({
                icon: 'warning',
                title: 'Campo obrigatório',
                text: 'Por favor, digite uma observação antes de salvar.'
            });
            return;
        }

        // Mostrar loading
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Salvando...';
        submitBtn.disabled = true;

        // Enviar via AJAX
        const formData = new FormData();
        formData.append('observacoes', observacoes);

        fetch('<?= BASE_URL ?>admin/ficha/observacao/' + fichaId, {
                method: 'POST',
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
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: data.message,
                        confirmButtonColor: '#28a745'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: data.message || 'Erro ao salvar observação.',
                        confirmButtonColor: '#e74a3b'
                    });
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Erro de conexão. Tente novamente.',
                    confirmButtonColor: '#e74a3b'
                });
            })
            .finally(() => {
                // Restaurar botão
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
    }

    // Função para excluir ficha
    function excluirFicha(fichaId) {
        Swal.fire({
            title: 'Confirmar exclusão',
            text: 'Você tem certeza que deseja excluir esta ficha? Esta ação pode ser desfeita.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74a3b',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('<?= BASE_URL ?>admin/ficha/excluir/' + fichaId, {
                        method: 'POST'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Excluída!',
                                text: data.message,
                                confirmButtonColor: '#28a745'
                            }).then(() => {
                                window.location.href = '<?= BASE_URL ?>admin/fichas';
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro!',
                                text: data.message || 'Erro ao excluir ficha.',
                                confirmButtonColor: '#e74a3b'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro!',
                            text: 'Erro de conexão.',
                            confirmButtonColor: '#e74a3b'
                        });
                    });
            }
        });
    }
</script>

<style>
    .bg-gradient-primary {
        background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
    }

    .border-left-primary {
        border-left: 4px solid #667eea !important;
    }

    .icon-circle {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.875rem;
    }

    .card {
        border: none;
        transition: all 0.3s ease;
    }

    .card:hover {
        transform: translateY(-2px);
    }

    .progress {
        border-radius: 10px;
        overflow: hidden;
    }

    .progress-bar {
        transition: width 0.6s ease;
    }

    .resposta-content {
        min-height: 60px;
    }

    @media (max-width: 768px) {
        .col-md-6 {
            margin-bottom: 1rem;
        }

        .btn-group {
            flex-direction: column;
            gap: 0.5rem;
        }

        .btn-group .btn {
            width: 100%;
        }
    }
</style>