<main class="main-content">
    <nav class="navbar">
        <div class="container-fluid">
            <div class="navbar-content">
                <div>
                    <ul class="breadcrumb">
                        <li><i class="fas fa-user-shield"></i> <a href="javascript:;">
                                <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?>
                            </a> </li>
                        <li class="text-dark">Perguntas</li>
                    </ul>
                    <h4>
                        <i class="fas fa-chart-pie me-2" style="color: var(--primary-color);"></i>
                        Gestão de Perguntas
                    </h4>
                </div>
                <!-- Logout -->
                <a href="<?= BASE_URL ?>admin/logout" class="btn btn-outline">
                    <i class="fas fa-sign-out-alt me-2"></i>Sair
                </a>
            </div>
        </div>
    </nav>

    <div class="content-wrapper">
        <div class="table-container">
            <h2 class="page-title">
                <i class="fas fa-folder-open mt-4"></i>Fichas de Perguntas
            </h2>
            <div class="table-responsive">
                <div id="fichasTable"></div>
            </div>
        </div>
    </div>

    <div style="display: none;">
        <form id="delete-form" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($view->csrf_token) ?>">
        </form>
    </div>

    <!-- CDN Scripts -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tabulator/5.5.2/css/tabulator.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.css">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/tabulator/5.5.2/js/tabulator.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.js"></script>

    <?php

    use Application\Lib\Helpers;

    $baseUrl = BASE_URL;
    $adminUsername = $_SESSION['admin_username'] ?? 'admin';

    $fichasData = array_map(function ($ficha) use ($baseUrl, $adminUsername) {
        $admin = $ficha->admin ?? null;
        $slug = $admin->slug_clinica ?? Helpers::slugify($admin->nome_clinica ?? 'clinica');

        return [
            'id' => $ficha->id,
            'nome' => $ficha->nome,
            'descricao' => $ficha->descricao,
            'created_at' => $ficha->created_at,
            'view_url' => "{$baseUrl}admin/{$adminUsername}/fichas-modelo/{$ficha->id}/perguntas",
            'copy_link' => "{$baseUrl}user/responder/{$slug}?ficha_id={$ficha->id}",
            'edit_url' => "{$baseUrl}admin/{$adminUsername}/banco-perguntas?ficha_id={$ficha->id}",
        ];
    }, $fichas->all());
    ?>

    <div id="fichasTable"></div>

    <!-- Formulário invisível para exclusão -->
    <form id="delete-form" method="POST" style="display:none;"></form>

    <!-- Tabulator, SweetAlert2 e seu script principal -->
    <script>
        const fichasData = <?= json_encode($fichasData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        console.log('fichasData:', fichasData);

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

        async function handleCopyLink(link, button) {
            try {
                await navigator.clipboard.writeText(link);
                await Swal.fire({
                    icon: 'success',
                    title: 'Link copiado!',
                    html: `<div style="margin-top: 1rem; text-align: left;"><p style="margin-bottom: 0.5rem;">O link foi copiado para sua área de transferência:</p><code style="background: #f1f1f1; padding: 0.5rem; border-radius: 0.5rem; word-break: break-all; display: block;">${link}</code></div>`,
                    confirmButtonText: '<i class="fas fa-check"></i> Entendido',
                    confirmButtonColor: 'var(--primary-color)',
                });
            } catch (err) {
                console.error('Erro ao copiar:', err);
                await Swal.fire({
                    icon: 'error',
                    title: 'Erro ao copiar',
                    text: 'Não foi possível copiar o link. Por favor, tente manualmente.',
                    confirmButtonColor: '#d33',
                });
            }
        }

        async function handleDeleteFicha(fichaId, fichaName) {
            const result = await Swal.fire({
                title: 'Confirmar exclusão',
                html: `<div style="text-align: left;"><p style="margin-bottom: 0.5rem;">Você realmente deseja excluir a ficha:</p><div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 0.5rem; border-radius: 0.5rem; margin: 1rem 0;"><i class="fas fa-file-alt" style="margin-right: 0.5rem;"></i><strong>${fichaName}</strong></div><p style="color: #dc3545; margin: 1rem 0 0 0;"><small><i class="fas fa-exclamation-triangle" style="margin-right: 0.25rem;"></i>Esta ação é irreversível!</small></p></div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-trash" style="margin-right: 0.5rem;"></i>Sim, excluir!',
                cancelButtonText: '<i class="fas fa-times" style="margin-right: 0.5rem;"></i>Cancelar',
                reverseButtons: true,
            });

            if (result.isConfirmed) {
                const form = document.getElementById('delete-form');
                if (form) {
                    form.action = `<?= BASE_URL ?>admin/fichas-modelo/excluir/${fichaId}`;
                    form.submit();
                }
            }
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            }) + ' ' + date.toLocaleTimeString('pt-BR', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        const table = new Tabulator("#fichasTable", {
            data: fichasData,
            layout: "fitColumns",
            responsiveLayout: false,
            pagination: true,
            paginationSize: 10,
            paginationSizeSelector: [5, 10, 25, 50, true],
            movableColumns: false,
            placeholder: "Nenhum registro encontrado",
            height: "auto",
            locale: "pt-br",
            langs: {
                "pt-br": {
                    "pagination": {
                        "page_size": "registros por página",
                        "first": "Primeiro",
                        "first_title": "Primeira página",
                        "last": "Último",
                        "last_title": "Última página",
                        "prev": "<",
                        "prev_title": "Página anterior",
                        "next": ">",
                        "next_title": "Próxima página",
                        "counter": {
                            "showing": "Página",
                            "of": "de",
                            "rows": "(_TOTAL_ registros)",
                        }
                    }
                }
            },
            columns: [{
                    title: '<i class="fas fa-tag"></i> Nome',
                    field: "nome",
                    width: 250,
                    sorter: "string",
                    headerFilter: "input",
                    headerFilterPlaceholder: "Buscar nome...",
                    formatter: function(cell) {
                        return `<div class="ficha-name"><i class="fas fa-file-alt"></i>${cell.getValue()}</div>`;
                    }
                },
                {
                    title: '<i class="fas fa-align-left"></i> Descrição',
                    field: "descricao",
                    width: 300,
                    sorter: "string",
                    headerFilter: "input",
                    headerFilterPlaceholder: "Buscar descrição...",
                    formatter: function(cell) {
                        return `<div class="ficha-description">${cell.getValue()}</div>`;
                    }
                },
                {
                    title: '<i class="fas fa-calendar"></i> Data de Criação',
                    field: "created_at",
                    width: 300,
                    sorter: "datetime",
                    formatter: function(cell) {
                        return `<div class="creation-date"><i class="far fa-clock"></i>${formatDate(cell.getValue())}</div>`;
                    }
                },
                {
                    title: '<i class="fas fa-cogs"></i> Ações',
                    field: "actions",
                    widthGrow: 2,
                    hozAlign: "center",
                    headerSort: false,
                    formatter: function(cell) {
                        const rowData = cell.getRow().getData();
                        return `
                        <div class="action-buttons">
                            <a href="${rowData.view_url}" class="btn btn-primary btn-sm btn-action" data-action="view">
                                <i class="fas fa-eye"></i>Ver
                            </a>
                            <button type="button" class="btn btn-success btn-sm btn-action" data-action="copy" data-link="${rowData.copy_link}">
                                <i class="fas fa-copy"></i>Copiar
                            </button>
                            <a href="${rowData.edit_url}" class="btn btn-warning btn-sm btn-action" data-action="edit">
                                <i class="fas fa-edit"></i>Editar
                            </a>
                            <button type="button" class="btn btn-danger btn-sm btn-action" data-action="delete" 
                                    data-ficha-id="${rowData.id}" data-ficha-name="${rowData.nome}">
                                <i class="fas fa-trash"></i>Excluir
                            </button>
                        </div>
                    `;
                    }
                }
            ]
        });

        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('click', async function(e) {
                const btn = e.target.closest('.btn-action');
                if (!btn) return;
                e.preventDefault();

                const action = btn.dataset.action;

                if (action === 'view' || action === 'edit') {
                    window.location.href = btn.getAttribute('href');
                } else if (action === 'copy') {
                    await handleCopyLink(btn.dataset.link, btn);
                } else if (action === 'delete') {
                    await handleDeleteFicha(btn.dataset.fichaId, btn.dataset.fichaName);
                }
            });

            setTimeout(() => {
                Toast.fire({
                    icon: 'info',
                    title: 'Fichas carregadas com sucesso!'
                });
            }, 800);
        });
    </script>