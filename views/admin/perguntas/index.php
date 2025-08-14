<main class="main-content position-relative border-radius-lg">
    <nav class="navbar">
        <div class="container-fluid">
            <div class="navbar-content">
                <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
                    <li class="breadcrumb-item text-sm">
                        <a class="opacity-5 text-dark" href="javascript:;">
                            <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item text-sm text-dark active" aria-current="page">Perguntas</li>
                </ol>
                <h5 class="pt-3"><i class="fas fa-list-alt"></i> Gerenciamento de Perguntas</h5>

            </div>
            <div class="d-flex ms-auto mt-3">
                <ul class="navbar-nav d-flex justify-content-end">
                    <li class="nav-item d-flex align-items-center">
                        <a href="<?= BASE_URL ?>admin/logout" class="nav-link text-body font-weight-bold px-0">
                            <button class="d-sm-inline d-none btn btn-primary mt-3 ms-3">
                                <i class="fas fa-sign-out-alt me-1"></i>Sair
                            </button>
                        </a>
                    </li>
                    <li class="nav-item d-xl-none ps-3 d-flex align-items-center">
                        <a href="javascript:;" class="nav-link text-body p-0" id="iconNavbarSidenav"
                            aria-label="Toggle sidebar">
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

    <div class="py-4">
        <div class="column">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger mb-3" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success mb-3" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <div class="col-lg-12 mb-4">
                <div class="card shadow fade-in">
                    <div class="card-header d-flex align-items-center">
                        <div class="d-flex flex-column">
                            <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i> Crie uma nova Pergunta</h4>
                            <p class="mb-0">Crie e gerencie perguntas para questionários e formulários para seu cliente.
                            </p>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Formulário para importar modelo -->
                        <form id="formModelo" action="<?= BASE_URL ?>admin/perguntas/importar-modelo" method="POST"
                            class="mb-4 form-pergunta-model">
                            <input type="hidden" name="csrf_token"
                                value="<?= htmlspecialchars($view->csrf_token ?? '') ?>">
                            <?php if (!empty($ficha)): ?>
                                <input type="hidden" name="ficha_modelo_id" value="<?= (int) $ficha->id ?>">
                            <?php endif; ?>
                            <div class="mb-3">
                                <label for="modelo_id" class="form-label fw-bold">
                                    Adicionar pergunta pronta:
                                </label>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-magic me-1"></i>
                                        </span>
                                        <select class="form-select" name="modelo_id" id="modelo_id" required
                                            aria-label="Selecione uma pergunta modelo">
                                            <option value="">Escolha uma pergunta</option>
                                            <?php
                                            $perguntas_modelo = $perguntas_modelo ?? [];
                                            foreach ($perguntas_modelo as $modelo): ?>
                                                <option value="<?= (int)$modelo->id ?>">
                                                    <?= htmlspecialchars($modelo->pergunta) ?>
                                                    (<?= htmlspecialchars($modelo->tipo) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="submit" name="usar_modelo" value="1" class="btn btn-outline-primary">
                                        <i class="fas fa-plus-circle me-1"></i> Usar
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- Formulário para criar nova pergunta -->
                        <form id="formPergunta" action="<?= BASE_URL ?>admin/perguntas/criar" method="POST"
                            class="mb-4 form-pergunta-model">
                            <input type="hidden" name="csrf_token"
                                value="<?= htmlspecialchars($view->csrf_token ?? '') ?>">
                            <?php if (isset($_GET['ficha_modelo_id'])): ?>
                                <input type="hidden" name="ficha_modelo_id" value="<?= (int) $_GET['ficha_modelo_id'] ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="pergunta" class="form-label">Pergunta <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-question"></i></span>
                                    <input type="text" class="form-control" id="pergunta" name="pergunta" required
                                        maxlength="500" aria-describedby="pergunta-help">
                                </div>
                                <div id="pergunta-help" class="form-text">Digite a pergunta que será apresentada ao
                                    usuário</div>
                            </div>

                            <div class="mb-3">
                                <label for="chave" class="form-label">Chave técnica (opcional)</label>
                                <input type="text" name="chave" id="chave" class="form-control"
                                    placeholder="Ex: nome, email, idade..." pattern="[a-z_]+" maxlength="50"
                                    aria-describedby="chave-help">
                                <div id="chave-help" class="form-text">
                                    Use somente letras minúsculas e underscore, sem espaços (ex: nome_completo)
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="tipo" class="form-label">Tipo de Resposta <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-list"></i></span>
                                    <select class="form-select" id="tipo" name="tipo" required
                                        aria-describedby="tipo-help">
                                        <option value="texto">Texto</option>
                                        <option value="checkbox">Caixa de seleção</option>
                                        <option value="radio">Escolha única</option>
                                        <option value="select">Lista suspensa</option>
                                        <option value="textarea">Texto longo</option>
                                        <option value="date">Data</option>
                                        <option value="tel">Telefone</option>
                                        <option value="number">Número</option>
                                        <option value="assinatura">Assinatura</option>
                                    </select>
                                </div>
                                <div id="tipo-help" class="form-text">Selecione o tipo de campo para a resposta
                                </div>
                            </div>

                            <div class="mb-3" id="opcoes-container" style="display: none;">
                                <label for="opcoes" class="form-label">Opções (separadas por vírgula)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-list-ol"></i></span>
                                    <input type="text" class="form-control" id="opcoes" name="opcoes"
                                        placeholder="Ex: Sim,Não,Às vezes" aria-describedby="opcoes-help">
                                </div>
                                <div id="opcoes-help" class="form-text text-muted">Digite as opções separadas por
                                    vírgula</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="obrigatorio"
                                            id="obrigatorio">
                                        <label class="form-check-label" for="obrigatorio">
                                            <i class="fas fa-asterisk text-danger me-1 small"></i> Resposta
                                            obrigatória
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="complemento_texto"
                                        id="complemento_texto">
                                    <label class="form-check-label" for="complemento_texto">
                                        <i class="fas fa-pen me-1"></i> Permitir resposta adicional em texto
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3" id="placeholder-container" style="display: none;">
                                <label for="placeholder" class="form-label">Texto de ajuda (placeholder)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-info-circle"></i></span>
                                    <input type="text" class="form-control" id="placeholder" name="placeholder"
                                        placeholder="Ex: Explique aqui..." maxlength="100"
                                        aria-describedby="placeholder-help">
                                </div>
                                <div id="placeholder-help" class="form-text">Texto que aparecerá como dica no campo
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" name="salvar" value="1" class="btn btn-success p-3">
                                    <i class="fas fa-save me-2"></i>Salvar Pergunta
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="text-end mb-3">
                <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalCriarFicha">
                    <i class="fas fa-plus me-1"></i> Criar Ficha com essas Perguntas
                </button>
            </div>

            <div class="col-lg-12">
                <div class="card shadow fade-in animate-scale">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-clipboard-list me-2"></i> Perguntas Cadastradas
                        </h4>
                        <?php $perguntas = $perguntas ?? []; ?>
                        <span class="badge bg-primary rounded-pill" id="contador-perguntas">
                            <?= count($perguntas) ?> pergunta(s)
                        </span>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($perguntas)): ?>
                            <div class="table-responsive">
                                <form id="form-excluir-multiplas" action="<?= BASE_URL ?>admin/perguntas/excluir-multiplas"
                                    method="POST">
                                    <input type="hidden" name="csrf_token"
                                        value="<?= htmlspecialchars(csrf_token('excluir_perguntas_multiplas')) ?>">
                                    <button type="submit" class="btn btn-danger mb-3" id="btn-excluir-selecionadas"
                                        disabled>
                                        <i class="fas fa-trash-alt me-1"></i> Excluir Selecionadas
                                    </button>
                                    <table class="table table-striped align-middle text-sm" role="table">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="text-center" scope="col">
                                                    <input type="checkbox" id="select-all"
                                                        aria-label="Selecionar todas as perguntas">
                                                </th>
                                                <th scope="col">Pergunta</th>
                                                <th scope="col">Tipo</th>
                                                <th class="text-center" scope="col">Obrigatória</th>
                                                <th class="text-center" scope="col">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tabela-perguntas">
                                            <?php foreach ($perguntas as $p): ?>
                                                <tr data-id="<?= (int)$p->id ?>">
                                                    <td class="text-center">
                                                        <input type="checkbox" name="perguntasSelecionadas[]"
                                                            value="<?= (int)$p->id ?>" class="checkbox-pergunta"
                                                            aria-label="Selecionar pergunta: <?= htmlspecialchars($p->pergunta) ?>">
                                                    </td>
                                                    <td>
                                                        <strong><?= htmlspecialchars($p->pergunta) ?></strong>
                                                        <?php if (isset($p->chave) && !empty($p->chave)): ?>
                                                            <!-- <br><small class="text-muted">Chave:
                                                                <?= htmlspecialchars($p->chave) ?></small> -->
                                                        <?php endif; ?>
                                                        <?php if (isset($p->admin_id) && is_null($p->admin_id)): ?>
                                                            <span class="badge bg-dark ms-1" title="Pergunta global (modelo)">
                                                                <i class="fas fa-globe"></i> Global
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $tipoIcones = [
                                                            'texto' => 'fa-font',
                                                            'checkbox' => 'fa-check-square',
                                                            'radio' => 'fa-dot-circle',
                                                            'select' => 'fa-caret-square-down',
                                                            'textarea' => 'fa-align-left',
                                                            'date' => 'fa-calendar',
                                                            'tel' => 'fa-phone',
                                                            'number' => 'fa-sort-numeric-up',
                                                            'assinatura' => 'fa-pen-fancy'
                                                        ];
                                                        $icon = $tipoIcones[$p->tipo] ?? 'fa-question-circle';
                                                        ?>
                                                        <i class="fas <?= $icon ?> me-1"></i>
                                                        <?= ucfirst(htmlspecialchars($p->tipo)) ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <?= ($p->obrigatorio ?? false)
                                                            ? '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Sim</span>'
                                                            : '<span class="badge bg-secondary"><i class="fas fa-times me-1"></i>Não</span>' ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-danger btn-sm excluir-pergunta"
                                                            data-id="<?= (int)$p->id ?>"
                                                            data-pergunta="<?= htmlspecialchars($p->pergunta) ?>"
                                                            data-token="<?= htmlspecialchars(csrf_token('excluir_pergunta_' . $p->id)) ?>"
                                                            data-url="<?= BASE_URL ?>admin/perguntas/excluir/<?= (int)$p->id ?>"
                                                            title="Excluir pergunta: <?= htmlspecialchars($p->pergunta) ?>"
                                                            aria-label="Excluir pergunta: <?= htmlspecialchars($p->pergunta) ?>">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Nenhuma pergunta cadastrada ainda.</strong>
                                <br>Comece criando sua primeira pergunta usando o formulário acima.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['mensagem'])): ?>
        <div id="mensagem-php" data-tipo="<?= htmlspecialchars($_SESSION['tipo'] ?? 'success') ?>"
            data-titulo="<?= htmlspecialchars($_SESSION['titulo'] ?? 'Sucesso!') ?>"
            data-texto="<?= htmlspecialchars($_SESSION['mensagem']) ?>">
        </div>
        <?php
        unset($_SESSION['mensagem'], $_SESSION['tipo'], $_SESSION['titulo']);
        ?>
    <?php endif; ?>

    <!-- Modal Criar Ficha -->
    <div class="modal fade" id="modalCriarFicha" tabindex="-1" aria-labelledby="modalCriarFichaLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="formCriarFicha" method="POST" action="<?= BASE_URL ?>admin/fichas-modelo/novo-rapido"
                    novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalCriarFichaLabel">
                            <i class="fas fa-list-alt me-2"></i>Nova Ficha
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($view->csrf_token ?? '') ?>">

                        <div class="mb-3">
                            <label for="nome_ficha" class="form-label">Nome da Ficha <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="nome" id="nome_ficha" class="form-control" required maxlength="100"
                                aria-describedby="nome-help">
                            <div id="nome-help" class="form-text">Digite um nome descritivo para a ficha</div>
                        </div>

                        <div class="mb-3">
                            <label for="descricao_ficha" class="form-label">Descrição</label>
                            <textarea name="descricao" id="descricao_ficha" class="form-control" rows="3"
                                maxlength="500" aria-describedby="descricao-help"></textarea>
                            <div id="descricao-help" class="form-text">Descreva brevemente o propósito desta ficha</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-1"></i> Salvar Ficha
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>