<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- SweetAlert2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
<!-- Animate.css -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

<main class="main-content position-relative max-height-vh-100 h-100">
    <nav class="navbar navbar-expand-lg px-4 shadow-sm" id="navbarBlur">
        <div class="container-fluid py-1 px-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
                    <li class="breadcrumb-item text-sm">
                        <a href="javascript:;" class="text-decoration-none">
                            <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item text-sm text-primary fw-bold" aria-current="page">Respostas</li>
                </ol>
                <a class="navbar-brand" href="#" aria-label="Gestão de Perguntas">
                    <i class="fas fa-question-circle me-2"></i> Gestão de Respostas
                </a>
            </nav>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="main-card fade-in" id="ficha-container">
            <div class="card-header p-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h4 class="mb-3">
                            <i class="fas fa-clipboard-list me-2"></i>
                            Respostas da Ficha #<?= str_pad($ficha->id, 3, '0', STR_PAD_LEFT) ?>
                        </h4>

                        <div class="info-card">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <i class="fas fa-user-edit me-2"></i>
                                    <strong>Preenchido por:</strong>
                                    <?= htmlspecialchars($ficha->nome_completo ?? 'Desconhecido') ?>
                                </div>
                                <div class="col-md-6">
                                    <i class="fas fa-calendar-alt me-2"></i>
                                    <?= $ficha->created_at ? (new DateTime($ficha->created_at))->format('d/m/Y H:i') : 'Data não disponível' ?>
                                </div>
                            </div>
                        </div>

                        <div class="stats-container">
                            <div class="stat-item">
                                <i class="fas fa-question"></i>
                                <span id="total-questions">5 Perguntas</span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-check-circle"></i>
                                <span id="answered-questions">4 Respondidas</span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-clock"></i>
                                <span>Há 2 horas</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="controls-section">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-6">
                            <div class="search-container">
                                <i class="fas fa-search search-icon"></i>
                                <input type="search" id="filtro-perguntas" class="form-control search-input"
                                    placeholder="Buscar perguntas...">
                            </div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <button class="btn btn-modern btn-outline-modern" id="btn-expandir-todos">
                                <i class="fas fa-expand-arrows-alt me-1"></i> Expandir
                            </button>
                            <button class="btn btn-modern btn-outline-modern" id="btn-recolher-todos">
                                <i class="fas fa-compress-arrows-alt me-1"></i> Recolher
                            </button>
                            <button class="btn btn-modern btn-success-modern" id="btn-imprimir">
                                <i class="fas fa-print me-1"></i> Imprimir
                            </button>
                            <button class="btn btn-modern btn-danger-modern" id="btn-pdf">
                                <i class="fas fa-file-pdf me-1"></i> PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-4" id="respostas-body">
                <div class="accordion accordion-modern" id="accordionRespostas">
                    <?php if (empty($respostas)): ?>
                        <div class="alert alert-info text-center">Nenhuma resposta foi encontrada.</div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($respostas as $index => $resposta): ?>
                                <div class="col-lg-6 mb-4">
                                    <div class="accordion-item-modern"
                                        data-pergunta="<?= htmlspecialchars(strtolower($resposta->pergunta)) ?>">
                                        <h2 class="accordion-header" id="heading-<?= $index ?>">
                                            <button class="accordion-button-modern collapsed" type="button"
                                                data-target="#collapse-<?= $index ?>">

                                                <?= htmlspecialchars($resposta->pergunta) ?>
                                            </button>
                                        </h2>
                                        <div id="collapse-<?= $index ?>" class="accordion-collapse collapse">

                                            <div class="accordion-body-modern">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="response-text flex-grow-1">
                                                        <?php
                                                        $respostaTexto = $resposta->resposta ?? '';
                                                        echo trim($respostaTexto) !== ''
                                                            ? nl2br(htmlspecialchars($respostaTexto))
                                                            : '<em class="text-muted">Sem resposta</em>';
                                                        ?>
                                                    </div>
                                                    <?php if (!empty(trim($resposta->resposta))): ?>
                                                        <button class="btn btn-copy-modern ms-3"
                                                            data-resposta="<?= htmlspecialchars($resposta->resposta) ?>"
                                                            title="Copiar resposta">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div> <?php endforeach; ?>
                        </div> <?php endif; ?>

                    <div id="nenhum-resultado" class="no-results" style="display: none;">
                    </div>
                </div>
            </div>

            <div class="card-footer text-center py-3" style="background: rgba(248, 249, 250, 0.5); border: none;">
                <small class="text-muted">
                    <i class="fas fa-check-circle me-1"></i>
                    Fim das respostas
                </small>
            </div>
        </div>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {

        const QuestionManager = {
            elements: {},
            state: {
                konamiCode: [],
                correctKonamiCode: [38, 38, 40, 40, 37, 39, 37, 39, 66, 65],
            },

            init() {
                this.cacheElements();
                this.bindEvents();
                this.applyInitialAnimations();
                this.updateStats();
                console.log('🚀 Sistema de Gestão de Perguntas carregado com sucesso!');
                console.log('💡 Dica: Tente o código Konami para uma surpresa! ↑↑↓↓←→←→BA');
            },

            cacheElements() {
                this.elements.accordionItems = document.querySelectorAll('.accordion-item-modern');
                this.elements.collapseElements = document.querySelectorAll('.accordion-collapse');
                this.elements.filtroInput = document.getElementById('filtro-perguntas');
                this.elements.nenhumResultado = document.getElementById('nenhum-resultado');
                this.elements.totalQuestionsSpan = document.getElementById('total-questions');
                this.elements.answeredQuestionsSpan = document.getElementById('answered-questions');
                this.elements.btnExpandir = document.getElementById('btn-expandir-todos');
                this.elements.btnRecolher = document.getElementById('btn-recolher-todos');
                this.elements.btnImprimir = document.getElementById('btn-imprimir');
                this.elements.btnPdf = document.getElementById('btn-pdf');
                this.elements.copyButtons = document.querySelectorAll('.btn-copy-modern');
            },

            bindEvents() {
                this.elements.filtroInput.addEventListener('input', this.filterQuestions.bind(this));
                this.elements.btnExpandir.addEventListener('click', () => this.toggleAll(true));
                this.elements.btnRecolher.addEventListener('click', () => this.toggleAll(false));
                this.elements.btnImprimir.addEventListener('click', this.preparePrint.bind(this));
                this.elements.btnPdf.addEventListener('click', this.generatePdf.bind(this));
                this.elements.accordionItems.forEach(item => {
                    const button = item.querySelector('.accordion-button-modern');
                    const collapseId = button.getAttribute('data-target'); // <- agora usa 'data-target'
                    const collapseEl = document.querySelector(collapseId);

                    button.addEventListener('click', (e) => {
                        e.preventDefault();

                        const instance = bootstrap.Collapse.getOrCreateInstance(collapseEl, {
                            toggle: false
                        });

                        if (collapseEl.classList.contains('show')) {
                            instance.hide();
                        } else {
                            instance.show();
                        }
                    });
                });



                this.elements.copyButtons.forEach(button => {
                    button.addEventListener('click', this.copyToClipboard.bind(this));
                });

                document.addEventListener('keydown', this.handleKonamiCode.bind(this));
                window.addEventListener('afterprint', this.afterPrint.bind(this));
            },



            applyInitialAnimations() {
                this.elements.accordionItems.forEach((item, index) => {
                    item.style.animationDelay = `${index * 0.05}s`;
                    item.classList.add('fade-in');
                });
            },

            updateStats() {
                const allItems = this.elements.accordionItems;
                const visibleItems = Array.from(allItems).filter(item => item.style.display !== 'none');
                const total = allItems.length;
                const answered = Array.from(allItems).filter(item => {
                    const responseText = item.querySelector('.response-text');
                    return responseText && !responseText.textContent.includes('Sem resposta');
                }).length;

                this.elements.totalQuestionsSpan.textContent =
                    `${visibleItems.length} de ${total} Pergunta${total !== 1 ? 's' : ''}`;
                this.elements.answeredQuestionsSpan.textContent =
                    `${answered} Respondida${answered !== 1 ? 's' : ''}`;
            },

            filterQuestions(e) {
                const termo = e.target.value.toLowerCase().trim();
                let visibleCount = 0;

                this.elements.nenhumResultado.style.display = visibleCount === 0 ? 'block' : 'none';
                this.updateStats();

                // Feedback visual no campo de busca
                if (termo.length > 0) {
                    e.target.style.background = visibleCount > 0 ?
                        'linear-gradient(135deg, #d4edda, #c3e6cb)' :
                        'linear-gradient(135deg, #f8d7da, #f5c6cb)';
                } else {
                    e.target.style.background = 'rgba(255, 255, 255, 0.9)';
                }
            },

            toggleAll(expand) {
                // Filtra para pegar apenas os elementos que estão em colunas visíveis
                const visibleCollapseElements = Array.from(this.elements.collapseElements).filter(el => {
                    const colunaPai = el.closest('.col-lg-6'); // Procura a coluna pai
                    return colunaPai && colunaPai.style.display !== 'none';
                });

                visibleCollapseElements.forEach(el => {
                    // Usa o método moderno para obter ou criar a instância do Collapse
                    const instance = bootstrap.Collapse.getOrCreateInstance(el);

                    // Chama explicitamente .show() ou .hide(), que não "invertem" o estado
                    if (expand) {
                        instance.show();
                    } else {
                        instance.hide();
                    }
                });

                // Mantém o feedback visual para o usuário
                Swal.fire({
                    icon: expand ? 'success' : 'info',
                    title: expand ? 'Tudo expandido!' : 'Tudo recolhido!',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 1500,
                    timerProgressBar: true
                });
            },

            async copyToClipboard(e) {
                const button = e.currentTarget;
                const textToCopy = button.dataset.resposta;

                try {
                    await navigator.clipboard.writeText(textToCopy);
                    button.classList.add('animate__animated', 'animate__rubberBand');

                    Swal.fire({
                        icon: 'success',
                        title: 'Copiado!',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000
                    });

                    button.addEventListener('animationend', () => {
                        button.classList.remove('animate__animated', 'animate__rubberBand');
                    }, {
                        once: true
                    });

                } catch (err) {
                    console.error('Erro ao copiar:', err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro ao copiar',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            },

            preparePrint() {
                Swal.fire({
                    title: 'Preparar para impressão?',
                    text: "Isso expandirá todas as respostas visíveis.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sim, imprimir',
                    cancelButtonText: 'Cancelar',
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.state.printState = Array.from(this.elements.collapseElements)
                            .filter(el => !el.classList.contains('show'))
                            .map(el => el.id);

                        this.toggleAll(true);
                        setTimeout(() => window.print(), 500);
                    }
                });
            },

            afterPrint() {
                if (this.state.printState) {
                    this.state.printState.forEach(id => {
                        const el = document.getElementById(id);
                        if (el) {
                            const instance = bootstrap.Collapse.getInstance(el) || new bootstrap
                                .Collapse(el, {
                                    toggle: false
                                });
                            instance.hide();
                        }
                    });
                    delete this.state.printState;
                }
            },

            async generatePdf() {
                const btn = this.elements.btnPdf;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<div class="loading-spinner"></div> Gerando...';
                btn.disabled = true;

                try {
                    const {
                        jsPDF
                    } = window.jspdf;
                    const pdf = new jsPDF({
                        orientation: 'p',
                        unit: 'mm',
                        format: 'a4'
                    });
                    const margin = 15;
                    let y = 20;

                    const fichaId = document.querySelector('h4')?.textContent.match(/#(\d+)/)?.[1] ||
                        '001';
                    const nomePaciente = document.querySelector('.nome-paciente')?.textContent.trim() ||
                        'Paciente';

                    pdf.setFont('helvetica', 'bold');
                    pdf.setFontSize(18);
                    pdf.text(`Respostas da Ficha #${fichaId}`, margin, y);
                    y += 10;

                    pdf.setFont('helvetica', 'normal');
                    pdf.setFontSize(10);
                    const preenchidoPor = document.querySelector('.fa-user-edit')?.parentElement
                        ?.textContent.trim() || '';
                    const data = document.querySelector('.fa-calendar-alt')?.parentElement?.textContent
                        .trim() || '';
                    pdf.text(preenchidoPor, margin, y);
                    pdf.text(data, pdf.internal.pageSize.getWidth() - margin - pdf.getTextWidth(data), y);
                    y += 10;
                    pdf.line(margin, y, pdf.internal.pageSize.getWidth() - margin, y);
                    y += 10;

                    const visibleItems = Array.from(this.elements.accordionItems).filter(item => item.style
                        .display !== 'none');
                    for (const item of visibleItems) {
                        if (y > 270) {
                            pdf.addPage();
                            y = 20;
                        }

                        const pergunta = item.querySelector('.accordion-button-modern').textContent.trim();
                        const resposta = item.querySelector('.response-text').innerHTML.replace(
                            /<br\s*\/?>/gi, '\n');
                        const cleanResposta = new DOMParser().parseFromString(resposta, 'text/html')
                            .documentElement.textContent;

                        pdf.setFont('helvetica', 'bold');
                        pdf.setFontSize(12);
                        const questionLines = pdf.splitTextToSize(pergunta, pdf.internal.pageSize
                            .getWidth() - margin * 2);
                        pdf.text(questionLines, margin, y);
                        y += (questionLines.length * 5) + 2;

                        pdf.setFont('helvetica', 'normal');
                        pdf.setFontSize(11);
                        const answerLines = pdf.splitTextToSize(cleanResposta, pdf.internal.pageSize
                            .getWidth() - margin * 2);
                        pdf.text(answerLines, margin, y);
                        y += (answerLines.length * 5) + 8;
                    }

                    pdf.save(`${nomePaciente}-Ficha-${fichaId}.pdf`);

                    Swal.fire({
                        icon: 'success',
                        title: 'PDF Gerado!',
                        text: 'O arquivo foi baixado com sucesso.'
                    });

                } catch (error) {
                    console.error("Erro ao gerar PDF:", error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Ocorreu um erro ao gerar o PDF.'
                    });
                } finally {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            },

            handleKonamiCode(e) {
                this.state.konamiCode.push(e.keyCode);
                this.state.konamiCode = this.state.konamiCode.slice(-this.state.correctKonamiCode.length);

                if (JSON.stringify(this.state.konamiCode) === JSON.stringify(this.state.correctKonamiCode)) {
                    this.activateSecretMode();
                    this.state.konamiCode = [];
                }
            },

            activateSecretMode() {
                Swal.fire({
                    title: '🎉 Código Secreto Ativado! 🎉',
                    html: 'Você encontrou o easter egg. O layout agora vai dançar!',
                    background: 'var(--primary-gradient)',
                    color: 'white',
                });
                document.body.classList.add('pulse-animation');
                setTimeout(() => document.body.classList.remove('pulse-animation'), 4000);
            }
        };

        QuestionManager.init();
    });
</script>