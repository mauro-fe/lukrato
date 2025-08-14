<style>
    :root {
        --primary-color: #0d7377;
        --primary-darker-color: #084c4f;
        --secondary-color: #14a085;
        --success-color: #10b981;
        --info-color: #0891b2;
        --warning-color: #f59e0b;
        --danger-color: #ef4444;
        --primary-gradient: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-darker-color) 100%);
        --card-shadow: 0 10px 30px rgba(13, 115, 119, 0.1);
        --card-shadow-hover: 0 15px 40px rgba(13, 115, 119, 0.15);
        --btn-shadow: 0 4px 15px rgba(13, 115, 119, 0.4);
        --btn-shadow-hover: 0 6px 20px rgba(13, 115, 119, 0.6);
    }

    body {
        background-color: #f8fafc;
        font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        min-height: 100vh;
        background-image:
            radial-gradient(circle at 20% 80%, rgba(13, 115, 119, 0.05) 0%, transparent 50%),
            radial-gradient(circle at 80% 20%, rgba(13, 115, 119, 0.05) 0%, transparent 50%);
    }

    .main-content {
        padding: 20px;
    }

    .header-banner {
        background: var(--primary-gradient);
        color: white;
        padding: 3rem 2rem;
        border-radius: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: var(--card-shadow);
        position: relative;
        overflow: hidden;
    }

    .header-banner::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 100%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
        border-radius: 50%;
    }

    .header-banner h2 {
        font-weight: 700;
        margin-bottom: 1rem;
        position: relative;
        z-index: 2;
    }

    .header-banner p {
        font-size: 1.1rem;
        opacity: 0.95;
        position: relative;
        z-index: 2;
    }

    .pergunta-card {
        background-color: #fff;
        border-radius: 1.5rem;
        box-shadow: var(--card-shadow);
        padding: 2rem;
        margin-bottom: 2rem;
        border-left: 6px solid var(--primary-color);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .pergunta-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: var(--primary-gradient);
        transform: scaleX(0);
        transition: transform 0.3s ease;
        transform-origin: left;
    }

    .pergunta-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--card-shadow-hover);
        border-left-color: var(--primary-darker-color);
    }

    .pergunta-card:hover::before {
        transform: scaleX(1);
    }

    .pergunta-titulo {
        color: var(--primary-color);
        font-weight: 700;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        font-size: 1.25rem;
    }

    .pergunta-icon {
        background: var(--primary-gradient);
        color: white;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        margin-right: 15px;
        font-size: 1.1rem;
        box-shadow: 0 4px 12px rgba(13, 115, 119, 0.3);
    }

    .form-check-input:checked {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.25rem rgba(13, 115, 119, 0.25);
    }

    .form-check-input:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.25rem rgba(13, 115, 119, 0.25);
    }

    .option-item {
        margin-bottom: 0.75rem;
        padding: 1rem;
        border-radius: 12px;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }

    .option-item:hover {
        background-color: rgba(13, 115, 119, 0.08);
        border-color: rgba(13, 115, 119, 0.2);
        transform: translateX(5px);
    }

    .form-control,
    .form-select {
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
        font-size: 1rem;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.25rem rgba(13, 115, 119, 0.15);
        transform: translateY(-2px);
    }

    .input-group-text {
        background: var(--primary-gradient);
        color: white;
        border: none;
        border-radius: 12px 0 0 12px;
        font-weight: 600;
    }

    .input-group .form-control,
    .input-group .form-select {
        border-left: none;
        border-radius: 0 12px 12px 0;
    }

    .btn-primary {
        background: var(--primary-gradient);
        border: none;
        box-shadow: var(--btn-shadow);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border-radius: 15px;
        padding: 1rem 2rem;
        font-weight: 600;
        font-size: 1.1rem;
        position: relative;
        overflow: hidden;
    }

    .btn-primary::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s;
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, var(--primary-darker-color) 0%, var(--primary-color) 100%);
        transform: translateY(-3px);
        box-shadow: var(--btn-shadow-hover);
    }

    .btn-primary:hover::before {
        left: 100%;
    }

    .complemento-texto {
        border-top: 2px dashed rgba(13, 115, 119, 0.3);
        padding-top: 1.5rem;
        margin-top: 1.5rem;
    }

    .progress-container {
        position: sticky;
        top: 20px;
        background: linear-gradient(145deg, #ffffff, #f8fafc);
        padding: 1.5rem;
        border-radius: 20px;
        box-shadow: var(--card-shadow);
        z-index: 100;
        margin-bottom: 2rem;
        border: 1px solid rgba(13, 115, 119, 0.1);
    }

    .progress {
        height: 12px;
        background-color: #e2e8f0;
        border-radius: 10px;
        overflow: hidden;
    }

    .progress-bar {
        background: var(--primary-gradient);
        transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .progress-bar::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        bottom: 0;
        right: 0;
        background-image: linear-gradient(-45deg,
                rgba(255, 255, 255, 0.2) 25%,
                transparent 25%,
                transparent 50%,
                rgba(255, 255, 255, 0.2) 50%,
                rgba(255, 255, 255, 0.2) 75%,
                transparent 75%,
                transparent);
        background-size: 50px 50px;
        animation: move 2s linear infinite;
    }

    @keyframes move {
        0% {
            background-position: 0 0;
        }

        100% {
            background-position: 50px 50px;
        }
    }

    .obrigatorio-badge {
        background: linear-gradient(135deg, var(--danger-color), #ef4444);
        color: white;
        font-size: 0.75rem;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        margin-left: 0.75rem;
        font-weight: 600;
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
    }

    .fade-in {
        animation: fadeInUp 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .footer {
        text-align: center;
        padding: 3rem 0;
        color: var(--primary-color);
        font-weight: 600;
    }

    /* Estilos para os diferentes tipos de perguntas */
    .card-texto {
        border-left-color: var(--info-color);
    }

    .card-textarea {
        border-left-color: var(--secondary-color);
    }

    .card-checkbox {
        border-left-color: var(--success-color);
    }

    .card-radio {
        border-left-color: var(--warning-color);
    }

    .card-select {
        border-left-color: var(--danger-color);
    }

    .card-date,
    .card-tel,
    .card-email,
    .card-number {
        border-left-color: var(--primary-color);
    }

    /* Indicador visual de progresso nas perguntas respondidas */
    .pergunta-card.border-success {
        border-left-color: var(--success-color) !important;
        background: linear-gradient(145deg, #ffffff, rgba(16, 185, 129, 0.02));
    }

    .pergunta-card.border-success .pergunta-icon {
        background: linear-gradient(135deg, var(--success-color), #059669);
    }

    /* Animação de loading para o botão */
    .btn-loading {
        position: relative;
        color: transparent;
    }

    .btn-loading::after {
        content: '';
        position: absolute;
        width: 20px;
        height: 20px;
        top: 50%;
        left: 50%;
        margin-left: -10px;
        margin-top: -10px;
        border: 2px solid #ffffff;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    /* Responsividade aprimorada */
    @media (max-width: 768px) {
        .header-banner {
            padding: 2rem 1.5rem;
            border-radius: 1rem;
        }

        .pergunta-card {
            padding: 1.5rem;
            border-radius: 1rem;
        }

        .pergunta-icon {
            width: 35px;
            height: 35px;
            margin-right: 10px;
        }

        .progress-container {
            position: relative;
            top: 0;
        }
    }

    /* Campo inválido com estilo aprimorado */
    .form-control.is-invalid,
    .form-select.is-invalid {
        border-color: var(--danger-color);
        box-shadow: 0 0 0 0.25rem rgba(239, 68, 68, 0.25);
        animation: shake 0.5s ease-in-out;
    }

    @keyframes shake {

        0%,
        100% {
            transform: translateX(0);
        }

        25% {
            transform: translateX(-5px);
        }

        75% {
            transform: translateX(5px);
        }
    }
</style>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <div class="container mt-4">
        <!-- Header Banner -->
        <div class="header-banner fade-in">
            <h2><i class="fas fa-clipboard-list me-2"></i> Formulário de Perguntas</h2>
            <p class="mb-0">Por favor, responda cuidadosamente às perguntas abaixo.</p>
        </div>

        <!-- Progress Bar -->
        <div class="progress-container fade-in">
            <div class="d-flex justify-content-between mb-1">
                <span>Progresso do formulário</span>
                <span id="progress-text">0%</span>
            </div>
            <div class="progress">
                <div class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0"
                    aria-valuemax="100"></div>
            </div>
        </div>

        <!-- Form -->
        <form id="perguntasForm" action="<?= BASE_URL ?>user/salvar-respostas" method="POST">
            <?= csrf_input() ?>
            <input type="hidden" name="admin_id" value="<?= htmlspecialchars($admin_id) ?>">
            <!-- <input type="hidden" name="ficha_modelo_id" value="<?= $ficha_modelo_id ?>"> -->

            <?php
            $contador = 1;
            $totalPerguntas = count($perguntas);
            foreach ($perguntas as $p):
                $tipoIcone = [
                    'texto' => 'fas fa-font',
                    'textarea' => 'fas fa-align-left',
                    'checkbox' => 'fas fa-check-square',
                    'radio' => 'fas fa-dot-circle',
                    'select' => 'fas fa-caret-square-down',
                    'date' => 'fas fa-calendar-alt',
                    'tel' => 'fas fa-phone',
                    'email' => 'fas fa-envelope',
                    'number' => 'fas fa-hashtag'
                ];
                $icone = isset($tipoIcone[$p->tipo]) ? $tipoIcone[$p->tipo] : 'fas fa-question';
                $cardClass = 'card-' . $p->tipo;
                $opcoes = !empty($p->opcoes) ? array_map('trim', explode(',', $p->opcoes)) : [];
                $obrigatorio = !empty($p->obrigatorio);
            ?>

                <div class="pergunta-card <?= $cardClass ?>" data-pergunta-id="<?= $p->id ?>">
                    <h5 class="pergunta-titulo">
                        <span class="pergunta-icon"><i class="<?= $icone ?>"></i></span>
                        <?= htmlspecialchars($p->pergunta) ?>
                        <?php if ($obrigatorio): ?>
                            <span class="obrigatorio-badge"><i class="fas fa-asterisk me-1"></i>Obrigatório</span>
                        <?php endif; ?>
                    </h5>

                    <div class="pergunta-conteudo">
                        <?php if (in_array($p->tipo, ['texto', 'email', 'tel', 'number', 'date'])): ?>
                            <div class="input-group">
                                <span class="input-group-text"><i class="<?= $icone ?>"></i></span>
                                <input type="<?= $p->tipo === 'texto' ? 'text' : $p->tipo ?>" class="form-control"
                                    name="respostas[<?= $p->id ?>]" placeholder="<?= htmlspecialchars($p->placeholder ?? '') ?>"
                                    <?= $obrigatorio ? 'required' : '' ?>>
                            </div>

                        <?php elseif ($p->tipo == 'textarea'): ?>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-paragraph"></i></span>
                                <textarea class="form-control" name="respostas[<?= $p->id ?>]" rows="3"
                                    placeholder="<?= htmlspecialchars($p->placeholder ?? '') ?>"
                                    <?= $obrigatorio ? 'required' : '' ?>></textarea>
                            </div>

                        <?php elseif ($p->tipo == 'checkbox'): ?>
                            <div class="row">
                                <?php foreach ($opcoes as $op): ?>
                                    <div class="col-md-6">
                                        <div class="option-item">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="respostas[<?= $p->id ?>][]"
                                                    value="<?= htmlspecialchars($op) ?>" id="check_<?= $p->id ?>_<?= md5($op) ?>">
                                                <label class="form-check-label" for="check_<?= $p->id ?>_<?= md5($op) ?>">
                                                    <?= htmlspecialchars($op) ?>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                        <?php elseif ($p->tipo == 'radio'): ?>
                            <div class="row">
                                <?php foreach ($opcoes as $op): ?>
                                    <div class="col-md-6">
                                        <div class="option-item">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="respostas[<?= $p->id ?>]"
                                                    value="<?= htmlspecialchars($op) ?>" id="radio_<?= $p->id ?>_<?= md5($op) ?>"
                                                    <?= $obrigatorio ? 'required' : '' ?>>
                                                <label class="form-check-label" for="radio_<?= $p->id ?>_<?= md5($op) ?>">
                                                    <?= htmlspecialchars($op) ?>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                        <?php elseif ($p->tipo == 'select'): ?>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-list"></i></span>
                                <select name="respostas[<?= $p->id ?>]" class="form-select"
                                    <?= $obrigatorio ? 'required' : '' ?>>
                                    <option value="">Selecione uma opção</option>
                                    <?php foreach ($opcoes as $op): ?>
                                        <option value="<?= htmlspecialchars($op) ?>"><?= htmlspecialchars($op) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <!-- Campo de resposta adicional (com placeholder customizado) -->
                        <?php if (!empty($p->complemento_texto)): ?>
                            <div class="complemento-texto mt-3">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-comment-dots"></i></span>
                                    <input type="text" name="respostas_texto[<?= $p->id ?>]" class="form-control"
                                        placeholder="<?= htmlspecialchars($p->placeholder ?? 'Descreva mais (opcional)') ?>">
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($p->tipo == 'assinatura'): ?>
                            <label class="form-label"><?= htmlspecialchars($p->pergunta) ?></label>
                            <input type="text" class="form-control" name="respostas[<?= $p->id ?>]"
                                placeholder="<?= htmlspecialchars($p->placeholder ?? 'Digite sua assinatura') ?>"
                                <?= $obrigatorio ? 'required' : '' ?>>
                        <?php endif; ?>
                    </div>

                    <div class="text-muted mt-2 small">
                        <i class="fas fa-info-circle me-1"></i> Pergunta <?= $contador ?> de <?= $totalPerguntas ?>
                    </div>
                </div>
            <?php
                $contador++;
            endforeach;
            ?>

            <div class="d-grid gap-2 mb-5">
                <button type="submit" id="submitBtn" class="btn btn-primary btn-lg">
                    <i class="fas fa-paper-plane me-2"></i> Enviar Respostas
                </button>
            </div>
        </form>


        <div class="footer">
            <p>© <?= date('Y') ?> - Formulário de Perguntas</p>
        </div>
    </div>
</main>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.12/sweetalert2.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const perguntasForm = document.getElementById('perguntasForm');
        const perguntasCards = document.querySelectorAll('.pergunta-card');
        const progressBar = document.querySelector('.progress-bar');
        const progressText = document.getElementById('progress-text');
        const submitBtn = document.getElementById('submitBtn');

        // Atualiza o progresso do formulário
        function atualizarProgresso() {
            const totalPerguntas = perguntasCards.length;
            let perguntasRespondidas = 0;

            perguntasCards.forEach(card => {
                const perguntaId = card.getAttribute('data-pergunta-id');
                const inputs = card.querySelectorAll('input, textarea, select');
                let respondida = false;

                inputs.forEach(input => {
                    if (input.type === 'checkbox' || input.type === 'radio') {
                        if (input.checked) {
                            respondida = true;
                        }
                    } else if (input.value.trim() !== '') {
                        respondida = true;
                    }
                });

                // Destaca visualmente a pergunta respondida
                if (respondida) {
                    card.classList.add('border-success');
                    perguntasRespondidas++;
                } else {
                    card.classList.remove('border-success');
                }
            });

            const percentual = Math.round((perguntasRespondidas / totalPerguntas) * 100);
            progressBar.style.width = percentual + '%';
            progressBar.setAttribute('aria-valuenow', percentual);
            progressText.textContent = percentual + '%';

            // Altera a cor da barra de progresso com base no percentual
            if (percentual < 30) {
                progressBar.className = 'progress-bar bg-danger';
            } else if (percentual < 70) {
                progressBar.className = 'progress-bar bg-warning';
            } else {
                progressBar.className = 'progress-bar bg-success';
            }
        }

        // Adiciona listeners a todos os inputs para atualizar o progresso
        document.querySelectorAll('input, textarea, select').forEach(element => {
            element.addEventListener('change', atualizarProgresso);
            element.addEventListener('input', atualizarProgresso);
        });

        // Scroll suave para a próxima pergunta
        function scrollToNextQuestion(currentCard) {
            const nextCard = currentCard.nextElementSibling;
            if (nextCard && nextCard.classList.contains('pergunta-card')) {
                nextCard.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }

        // Adiciona comportamento ao pressionar Enter nos campos de texto
        document.querySelectorAll('input[type="text"], textarea').forEach(input => {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    const currentCard = this.closest('.pergunta-card');
                    scrollToNextQuestion(currentCard);
                }
            });
        });

        // Confirmação antes de enviar
        perguntasForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Verificar campos obrigatórios
            let todosPreenchidos = true;
            const camposObrigatorios = perguntasForm.querySelectorAll('[required]');

            camposObrigatorios.forEach(campo => {
                if (!campo.value.trim()) {
                    todosPreenchidos = false;
                    campo.classList.add('is-invalid');
                } else {
                    campo.classList.remove('is-invalid');
                }
            });

            if (!todosPreenchidos) {
                Swal.fire({
                    title: 'Atenção!',
                    text: 'Por favor, preencha todos os campos obrigatórios.',
                    icon: 'warning',
                    confirmButtonColor: '#6366f1'
                });

                // Scroll para o primeiro campo não preenchido
                const primeiroInvalido = perguntasForm.querySelector('.is-invalid');
                if (primeiroInvalido) {
                    primeiroInvalido.closest('.pergunta-card').scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }

                return;
            }

            // Confirmar envio
            Swal.fire({
                title: 'Enviar respostas?',
                text: 'Confirme para enviar suas respostas. Após o envio, não será possível fazer alterações.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#ef4444',
                confirmButtonText: 'Sim, enviar',
                cancelButtonText: 'Revisar respostas'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mostrar loading enquanto envia
                    Swal.fire({
                        title: 'Enviando...',
                        html: 'Por favor, aguarde enquanto suas respostas são enviadas.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Enviar o formulário
                    perguntasForm.submit();
                }
            });
        });

        // Tooltip para campos obrigatórios
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('.obrigatorio-badge'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl, {
                title: 'Este campo precisa ser preenchido'
            });
        });

        // Mostrar toast de boas-vindas
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        Toast.fire({
            icon: 'info',
            title: 'Formulário carregado, vamos começar!'
        });

        // Inicializa o progresso
        atualizarProgresso();
    });
</script>