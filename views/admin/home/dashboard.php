<!-- Tabulator CSS -->
<link href="https://unpkg.com/tabulator-tables@5.5.2/dist/css/tabulator.min.css" rel="stylesheet">

<!-- Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<!-- SweetAlert2 -->
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

<!-- Animate.css para animações -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">


<!-- Meta tags para PHP -->
<meta name="admin-username" content="<?= $admin_username ?>">
<script>
window.BASE_URL = "<?= BASE_URL ?>";
window.ADMIN_USERNAME = "<?= $admin_username ?>";
</script>
<meta name="csrf-token" content="<?= csrf_token('default') ?>">

<main class="main-content">
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container-fluid">
            <div class="navbar-content">
                <div>
                    <ul class="breadcrumb">
                        <li> <i class="fas fa-user-shield"></i><a class="text-muted">
                                <?= htmlspecialchars($admin->username) ?></a></li>
                        <li class="text-dark">Dashboard</li>
                    </ul>
                    <h4>
                        <i class="fas fa-chart-pie me-2" style="color: var(--primary-color);"></i>
                        Painel de Controle
                    </h4>
                </div>
                <!-- Logout -->
                <a href="<?= BASE_URL ?>admin/logout" class="btn btn-outline">
                    <i class="fas fa-sign-out-alt me-2"></i>Sair
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <!-- Estatísticas -->
        <div class="row stagger-animation">
            <div class="col-3">
                <div class="stats-card">
                    <div class="stats-content">
                        <div class="stats-numbers">
                            <p>Total de Fichas</p>
                            <h3 class="counter" data-target="<?= $totalFichas ?>"><?= $totalFichas ?></h3>
                        </div>
                        <div class="icon-shape">
                            <i class="fas fa-file-medical"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-3">
                <div class="stats-card">
                    <div class="stats-content">
                        <div class="stats-numbers">
                            <p>Fichas Hoje</p>
                            <h3 class="counter" data-target="<?= $fichasHoje ?>"><?= $fichasHoje ?></h3>
                        </div>
                        <div class="icon-shape bg-gradient-success">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-3">
                <div class="stats-card">
                    <div class="stats-content">
                        <div class="stats-numbers">
                            <p>Esta Semana</p>
                            <h3 class="counter" data-target="<?= $fichasSemana ?>"><?= $fichasSemana ?></h3>
                        </div>
                        <div class="icon-shape bg-gradient-info">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-3">
                <div class="stats-card">
                    <div class="stats-content">
                        <div class="stats-numbers">
                            <p>Ações</p>
                            <div style="margin-top: 10px;">
                                <a href="<?= BASE_URL . 'admin/' . $admin->username . '/fichas-lixeira' ?>"
                                    class="btn btn-outline-danger btn-sm">
                                    <i class="fas fa-trash me-1"></i> Lixeira
                                </a>
                            </div>
                        </div>
                        <div class="icon-shape bg-gradient-warning">
                            <i class="fas fa-cog"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Fichas -->
        <div class="row">
            <div class="col-12">
                <div class="main-card">
                    <div class="card-header">
                        <div>
                            <h2 class="page-title">
                                <i class="fas fa-list me-2" style="color: var(--success);"></i>
                                Lista de Fichas
                            </h2>
                            <p>
                                <span class="counter"
                                    data-target="<?= $fichas->count() ?>"><?= $fichas->count() ? htmlspecialchars($fichas->count()) : 0 ?></span>
                                fichas encontradas
                            </p>
                        </div>
                        <div class="export-buttons">
                            <button class="btn btn-outline-success" onclick="exportarExcel()">
                                <i class="fas fa-file-excel me-2"></i>Excel
                            </button>
                            <button class="btn btn-outline-danger" onclick="exportarPDF()">
                                <i class="fas fa-file-pdf me-2"></i>PDF
                            </button>
                        </div>
                    </div>

                    <div style="padding: 0 30px 30px 30px;">
                        <!-- Tabulator Table -->
                        <div id="tabelaFichas"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://unpkg.com/tabulator-tables@5.5.2/dist/js/tabulator.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Dados vindos do PHP convertidos para JavaScript
const fichasData = [
    <?php if (!empty($fichas)): ?>
    <?php foreach ($fichas as $index => $ficha): ?> {
        id: <?= $ficha->id ?>,
        nome_completo: "<?= htmlspecialchars($ficha->nome_completo, ENT_QUOTES) ?>",
        telefone: "<?= htmlspecialchars($ficha->telefone ?? '', ENT_QUOTES) ?>",
        email: "<?= htmlspecialchars($ficha->email ?? '', ENT_QUOTES) ?>",
        created_at: "<?= $ficha->created_at->format('Y-m-d H:i:s') ?>"
    }
    <?= $index < count($fichas) - 1 ? ',' : '' ?>
    <?php endforeach; ?>
    <?php endif; ?>
];

// Inicialização do Tabulator
const table = new Tabulator("#tabelaFichas", {
    data: fichasData,
    layout: "fitColumns",
    responsiveLayout: "hide",
    pagination: "local",
    paginationSize: 25,
    paginationSizeSelector: [10, 25, 50, 100],
    movableColumns: true,
    resizableRows: false,
    selectable: false,
    placeholder: "Nenhum registro encontrado",
    headerFilterPlaceholder: "Filtrar...",
    langs: {
        "pt-br": {
            "pagination": {
                "page_size": "Tamanho da página",
                "first": "Primeira",
                "first_title": "Primeira Página",
                "last": "Última",
                "last_title": "Última Página",
                "prev": "Anterior",
                "prev_title": "Página Anterior",
                "next": "Próxima",
                "next_title": "Próxima Página"
            }
        }
    },
    locale: "pt-br",
    columns: [{
            title: "Paciente",
            field: "paciente",
            width: 250,
            formatter: function(cell, formatterParams, onRendered) {
                const data = cell.getRow().getData();
                const initials = getInitials(data.nome_completo);
                const fichaId = String(data.id).padStart(3, '0');

                return `
                            <div class="patient-info">
                                <div class="patient-avatar">${initials}</div>
                                <div class="patient-details">
                                    <p>Ficha #${fichaId}</p>
                                </div>
                            </div>
                        `;
            }
        },
        {
            title: "Nome Completo",
            field: "nome_completo",
            headerFilter: "input",
            width: 250
        },
        {
            title: "Contato",
            field: "contato",
            width: 250,
            formatter: function(cell, formatterParams, onRendered) {
                const data = cell.getRow().getData();
                let html = '<div class="contact-info">';

                if (data.telefone) {
                    html += `<div><i class="fas fa-phone"></i>${data.telefone}</div>`;
                }
                if (data.email) {
                    html += `<div><i class="fas fa-envelope"></i>${data.email}</div>`;
                }

                html += '</div>';
                return html;
            }
        },
        {
            title: "Data",
            field: "created_at",
            width: 250,
            hozAlign: "center",
            formatter: function(cell, formatterParams, onRendered) {
                const date = new Date(cell.getValue());
                return `<span class="font-weight-bold">${formatDate(date)}</span>`;
            },
            sorter: "datetime"
        },
        {
            title: "Ações",
            field: "acoes",
            widthGrow: 2,
            hozAlign: "center",
            headerSort: false,
            formatter: function(cell, formatterParams, onRendered) {
                const data = cell.getRow().getData();
                const adminUsername = window.ADMIN_USERNAME || '<?= $admin_username ?>';

                return `
                            <div class="d-flex justify-content-center gap-2">
                                <a href="${window.BASE_URL}admin/${adminUsername}/respostas/visualizar/${data.id}" 
                                   class="btn  btn-success" title="Visualizar ficha completa">
                                    <i class="fas fa-eye me-1"></i> Ver
                                </a>
                                <button class="btn  btn-primary" title="Exportar PDF" onclick="exportarPDF(${data.id})">
                                    <i class="fas fa-file-pdf"></i>
                                </button>
                                <button class="btn btn-danger" title="Mover para Lixeira" onclick="moverParaLixeira(${data.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        `;
            }
        }
    ]
});

// Funções auxiliares
function getInitials(name) {
    const names = name.split(' ');
    const first = names[0] ? names[0][0].toUpperCase() : '';
    const last = names[1] ? names[1][0].toUpperCase() : '';
    return first + last;
}

function formatDate(date) {
    return date.toLocaleDateString('pt-BR') + ' ' +
        date.toLocaleTimeString('pt-BR', {
            hour: '2-digit',
            minute: '2-digit'
        });
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Animações de entrada escalonadas
    function staggerAnimation() {
        const cards = document.querySelectorAll('.stats-card');
        cards.forEach((card, index) => {
            card.style.animationDelay = (index * 0.1) + 's';
            card.classList.add('fadeInUp');
        });
    }

    // Counter animation melhorada
    function animateCounters() {
        const counters = document.querySelectorAll('.counter');
        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-target'));
            let current = 0;
            const increment = target / 60; // 60 frames for smooth animation

            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                counter.textContent = Math.floor(current);
            }, 16); // ~60fps
        });
    }

    // Inicializar animações
    staggerAnimation();
    animateCounters();

});


// Função para feedback de busca
function showSearchFeedback(count, query) {
    const message = count > 0 ?
        `${count} resultado${count > 1 ? 's' : ''} para "${query}"` :
        `Nenhum resultado para "${query}"`;

    // Mostrar feedback discreto
    let feedback = document.querySelector('.search-feedback');
    if (!feedback) {
        feedback = document.createElement('div');
        feedback.className = 'search-feedback';
        feedback.style.cssText = `
                    position: absolute;
                    top: 100%;
                    left: 0;
                    right: 0;
                    background: rgba(0, 123, 255, 0.1);
                    border: 1px solid rgba(0, 123, 255, 0.2);
                    border-radius: 10px;
                    padding: 8px 12px;
                    font-size: 0.85rem;
                    color: var(--primary-color);
                    z-index: 1000;
                    margin-top: 5px;
                    display: none;
                `;
        document.querySelector('.search-container').appendChild(feedback);
    }

    feedback.textContent = message;
    feedback.style.display = 'block';

    setTimeout(() => {
        feedback.style.display = 'none';
    }, 2000);
}

// Função para confirmação de exclusão (integrada com PHP)
function confirmarExclusao(url, mensagemConfirmacao, tituloSucesso, callbackDepois) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    Swal.fire({
        title: 'Confirmar exclusão',
        text: mensagemConfirmacao,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-trash me-2"></i>Sim, excluir',
        cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
        customClass: {
            popup: 'swal-modern',
            confirmButton: 'btn-delete-confirm',
            cancelButton: 'btn-delete-cancel'
        },
        buttonsStyling: false,
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
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
                const data = JSON.parse(text);

                if (!data || typeof data !== 'object') {
                    throw new Error('Resposta inválida do servidor.');
                }

                return data;
            } catch (error) {
                Swal.showValidationMessage(`Erro: ${error.message}`);
                return false;
            }
        }
    }).then((result) => {
        if (result.isConfirmed && result.value && result.value.status === 'success') {
            const data = result.value;

            Swal.fire({
                icon: 'success',
                title: tituloSucesso,
                text: data.message,
                timer: 2000,
                timerProgressBar: true,
                showConfirmButton: false,
                customClass: {
                    popup: 'swal-modern-success'
                }
            }).then(() => {
                if (callbackDepois && typeof callbackDepois === 'function') {
                    callbackDepois();
                } else {
                    // Remover linha da tabela com animação
                    const rowToRemove = table.getRows().find(row =>
                        row.getData().id === parseInt(url.split('/').pop())
                    );
                    if (rowToRemove) {
                        rowToRemove.delete();

                        // Atualizar contador
                        const counter = document.querySelector('.counter[data-target]');
                        if (counter) {
                            const currentValue = parseInt(counter.textContent) - 1;
                            counter.textContent = currentValue;
                            counter.setAttribute('data-target', currentValue);
                        }
                    }
                }
            });

        } else if (result.isConfirmed) {
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: (result.value && result.value.message) || 'Erro ao processar a exclusão.',
                customClass: {
                    popup: 'swal-modern-error'
                }
            });
        }
    });
}

// Função para mover para lixeira (integrada com PHP)
function moverParaLixeira(fichaId) {
    const ADMIN_USERNAME = window.ADMIN_USERNAME || '<?= $admin_username ?>';
    const url = window.BASE_URL + `admin/${ADMIN_USERNAME}/ficha/${fichaId}/lixeira`;

    confirmarExclusao(
        url,
        `Você tem certeza que deseja mover a ficha #${fichaId} para a lixeira? Você ainda poderá restaurá-la ou excluí-la definitivamente depois.`,
        'Ficha movida para a lixeira!'
    );
}

// Exportação PDF (integrada com PHP)
function exportarPDF(fichaId) {
    const ADMIN_USERNAME = window.ADMIN_USERNAME || '<?= $admin_username ?>';
    const isMultiple = !fichaId;
    const title = isMultiple ? 'Exportando todas as fichas' : `Exportando ficha #${fichaId}`;

    Swal.fire({
        title: title,
        text: 'Gerando arquivo PDF...',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        },
        customClass: {
            popup: 'swal-modern'
        }
    });

    setTimeout(() => {
        const url = fichaId ?
            window.BASE_URL + `admin/${ADMIN_USERNAME}/pdf/${fichaId}` :
            window.BASE_URL + `admin/${ADMIN_USERNAME}/exportar-pdf-todas`;

        const newWindow = window.open(url, '_blank');

        if (newWindow) {
            Swal.fire({
                icon: 'success',
                title: 'PDF gerado!',
                text: 'O arquivo foi aberto em uma nova aba.',
                timer: 2000,
                timerProgressBar: true,
                showConfirmButton: false,
                customClass: {
                    popup: 'swal-modern-success'
                }
            });
        } else {
            window.location.href = url;
            Swal.close();
        }
    }, 1500);
}

// Exportação Excel (integrada com PHP)
function exportarExcel() {
    Swal.fire({
        title: 'Exportar para Excel',
        text: 'Escolha o formato de exportação:',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-file-excel me-2"></i>Excel Completo',
        cancelButtonText: '<i class="fas fa-file-csv me-2"></i>CSV Simples',
        customClass: {
            popup: 'swal-modern',
            confirmButton: 'btn-excel-full',
            cancelButton: 'btn-excel-csv'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            exportToExcel('full');
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            exportToExcel('csv');
        }
    });
}

function exportToExcel(type) {
    const ADMIN_USERNAME = window.ADMIN_USERNAME || '<?= $admin_username ?>';

    Swal.fire({
        title: 'Processando exportação',
        text: 'Gerando arquivo...',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        },
        customClass: {
            popup: 'swal-modern'
        }
    });

    setTimeout(() => {
        const url = window.BASE_URL + `admin/${ADMIN_USERNAME}/exportar-${type}`;
        window.location.href = url;

        Swal.fire({
            icon: 'success',
            title: 'Download iniciado!',
            text: 'O arquivo será baixado em instantes.',
            timer: 2000,
            timerProgressBar: true,
            showConfirmButton: false,
            customClass: {
                popup: 'swal-modern-success'
            }
        });
    }, 2000);
}

// Função para obter token CSRF
function getCsrfToken(tokenId = 'default') {
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    return metaTag ? metaTag.getAttribute('content') : '';
}

// Sistema de tooltips customizado
function initializeTooltips() {
    const elementsWithTooltip = document.querySelectorAll('[title]');

    elementsWithTooltip.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
        element.addEventListener('mousemove', moveTooltip);
    });
}

let currentTooltip = null;

function showTooltip(e) {
    const text = e.target.getAttribute('title');
    if (!text) return;

    // Remove o title para evitar o tooltip nativo
    e.target.setAttribute('data-original-title', text);
    e.target.removeAttribute('title');

    currentTooltip = document.createElement('div');
    currentTooltip.className = 'custom-tooltip';
    currentTooltip.textContent = text;
    currentTooltip.style.cssText = `
                position: absolute;
                background: rgba(0, 0, 0, 0.8);
                color: white;
                padding: 8px 12px;
                border-radius: 6px;
                font-size: 12px;
                z-index: 10000;
                pointer-events: none;
                opacity: 0;
                transition: opacity 0.2s ease;
                white-space: nowrap;
            `;

    document.body.appendChild(currentTooltip);

    // Posicionar tooltip
    const rect = e.target.getBoundingClientRect();
    currentTooltip.style.left = rect.left + (rect.width / 2) - (currentTooltip.offsetWidth / 2) + 'px';
    currentTooltip.style.top = rect.top - currentTooltip.offsetHeight - 8 + 'px';

    // Fade in
    setTimeout(() => {
        if (currentTooltip) {
            currentTooltip.style.opacity = '1';
        }
    }, 10);
}

function hideTooltip(e) {
    if (currentTooltip) {
        currentTooltip.style.opacity = '0';
        setTimeout(() => {
            if (currentTooltip && currentTooltip.parentNode) {
                currentTooltip.parentNode.removeChild(currentTooltip);
            }
            currentTooltip = null;
        }, 200);
    }

    // Restaurar o title original
    const originalTitle = e.target.getAttribute('data-original-title');
    if (originalTitle) {
        e.target.setAttribute('title', originalTitle);
        e.target.removeAttribute('data-original-title');
    }
}

function moveTooltip(e) {
    if (currentTooltip) {
        const rect = e.target.getBoundingClientRect();
        currentTooltip.style.left = rect.left + (rect.width / 2) - (currentTooltip.offsetWidth / 2) + 'px';
        currentTooltip.style.top = rect.top - currentTooltip.offsetHeight - 8 + 'px';
    }
}

// Inicializar tooltips após carregamento
document.addEventListener('DOMContentLoaded', initializeTooltips);

// Sistema de toast para notificações
function showToast(message, type = 'info', duration = 4000) {
    const toast = document.createElement('div');
    toast.className = `custom-toast toast-${type}`;

    const icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-info-circle'
    };

    const colors = {
        success: '#28a745',
        error: '#dc3545',
        warning: '#ffc107',
        info: '#17a2b8'
    };

    toast.innerHTML = `
                <i class="${icons[type] || icons.info}" style="margin-right: 10px; color: ${colors[type] || colors.info};"></i>
                ${message}
            `;

    toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                border: 1px solid ${colors[type] || colors.info};
                border-left: 4px solid ${colors[type] || colors.info};
                padding: 15px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                z-index: 10001;
                font-size: 14px;
                max-width: 400px;
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s ease;
            `;

    document.body.appendChild(toast);

    // Animar entrada
    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(0)';
    }, 10);

    // Auto-remove
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, duration);

    // Click para fechar
    toast.addEventListener('click', () => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    });
}

// Expor APIs globalmente
window.TabulatorDashboard = {
    table: table,
    showToast: showToast,
    exportPDF: exportarPDF,
    exportExcel: exportarExcel,
    moveToTrash: moverParaLixeira,
    searchTable: function(query) {
        document.querySelector('input[name="q"]').value = query;
        table.setFilter([{
                field: "nome_completo",
                type: "like",
                value: query
            },
            {
                field: "telefone",
                type: "like",
                value: query
            },
            {
                field: "email",
                type: "like",
                value: query
            }
        ], "or");
    },
    clearFilters: function() {
        table.clearFilter();
        document.querySelector('input[name="q"]').value = '';
    }
};

// Event listeners para teclas de atalho
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + K = Focar na busca
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        document.querySelector('input[name="q"]').focus();
        showToast('Campo de busca focado', 'info', 2000);
    }

    // Ctrl/Cmd + E = Exportar Excel
    if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
        e.preventDefault();
        exportarExcel();
    }

    // Ctrl/Cmd + P = Exportar PDF
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        exportarPDF();
    }

    // Escape = Limpar filtros
    if (e.key === 'Escape') {
        window.TabulatorDashboard.clearFilters();
        showToast('Filtros limpos', 'info', 2000);
    }
});

// Log de inicialização
// console.log('🚀 Dashboard PHP integrado inicializado com sucesso!');
// console.log('📊 Tabulator carregado com', fichasData.length, 'registros do PHP');
// console.log('✨ Todas as funcionalidades ativas com backend PHP');
</script>