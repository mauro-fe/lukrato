<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">


<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-sm border-radius-xl bg-white" id="navbarBlur"
        navbar-scroll="true">
        <div class="container-fluid py-1 px-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
                    <li class="breadcrumb-item text-sm">
                        <a class="opacity-5 text-dark" href="javascript:;">
                            <i class="fas fa-user-shield me-1"></i>
                            <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item text-sm text-dark active" aria-current="page">
                        <i class="fas fa-question-circle me-1"></i>
                        Perguntas
                    </li>
                </ol>
                <a class="navbar-brand" href="#" aria-label="Gestão de Perguntas">
                    <i class="fas fa-question-circle me-2"></i> Gestão de Perguntas
                </a>
            </nav>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="page-header animate-fadeInUp">
            <h2 class="mb-4">
                <i class="fas fa-list-alt me-2"></i> Perguntas da Ficha:
                <strong><?= htmlspecialchars($ficha->nome) ?></strong>
            </h2>

            <?php if (!empty($ficha->descricao)): ?>
                <p class="text-muted"><i class="fas fa-info-circle me-1"></i> <?= htmlspecialchars($ficha->descricao) ?></p>
            <?php endif; ?>
        </div>

        <?php if (empty($perguntas)): ?>
            <div class="empty-state animate-fadeInUp">
                <i class="fas fa-question-circle"></i>
                <h4>Nenhuma pergunta encontrada</h4>
                <p>Nenhuma pergunta vinculada a esta ficha.</p>
            </div>
        <?php else: ?>
            <div class="questions-table animate-fadeInUp">
                <div class="table-responsive">
                    <table class="table table-striped text-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 80px;">#</th>
                                <th>Pergunta</th>
                                <th style="width: 120px;">Tipo</th>
                                <th style="width: 120px;">Obrigatória</th>
                                <th style="width: 120px;">Complemento</th>
                                <th style="width: 100px;">Fixa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($perguntas as $index => $p): ?>
                                <tr>
                                    <td>
                                        <div class="question-number"><?= $index + 1 ?></div>
                                    </td>
                                    <td>
                                        <div class="question-text">
                                            <?= htmlspecialchars($p->pergunta->pergunta ?? 'Pergunta não encontrada') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="question-type"><?= ucfirst($p->pergunta->tipo ?? '-') ?></span>
                                    </td>
                                    <td>
                                        <?= ($p->pergunta->obrigatorio ?? false)
                                            ? '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Sim</span>'
                                            : '<span class="badge bg-secondary"><i class="fas fa-times me-1"></i>Não</span>' ?>
                                    </td>
                                    <td>
                                        <?= ($p->pergunta->complemento_texto ?? false)
                                            ? '<span class="badge bg-primary"><i class="fas fa-plus me-1"></i>Sim</span>'
                                            : '<span class="badge bg-secondary"><i class="fas fa-times me-1"></i>Não</span>' ?>
                                    </td>
                                    <td>
                                        <?= ($p->pergunta->fixa ?? false)
                                            ? '<span class="badge bg-warning text-dark"><i class="fas fa-thumbtack me-1"></i>Sim</span>'
                                            : '<span class="badge bg-secondary"><i class="fas fa-times me-1"></i>Não</span>' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <div class="action-buttons animate-fadeInUp">
            <a href="<?= BASE_URL ?>admin/<?= $admin_username ?>/fichas-modelo" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Voltar para Fichas
            </a>
        </div>
    </div>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
    // Adicionar animações nas linhas da tabela
    document.addEventListener('DOMContentLoaded', function() {
        const rows = document.querySelectorAll('tbody tr');
        rows.forEach((row, index) => {
            row.style.animationDelay = `${0.1 * index}s`;
            row.classList.add('animate-fadeInUp');
        });

        // Efeito de hover nos badges
        const badges = document.querySelectorAll('.badge');
        badges.forEach(badge => {
            badge.style.transition = 'all 0.3s ease';
            badge.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.05)';
            });
            badge.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        });

        // Tooltips para os tipos de pergunta
        const questionTypes = document.querySelectorAll('.question-type');
        questionTypes.forEach(type => {
            type.style.transition = 'all 0.3s ease';
            type.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.2)';
            });
            type.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });
    });
</script>