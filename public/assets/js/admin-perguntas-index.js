/**
 * Sistema de Gerenciamento de Perguntas - Versão Corrigida
 * Problema do botão de exclusão individual resolvido
 */

// ===== UTILITÁRIOS =====
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function handleError(error, context = 'Operação') {
    console.error(`Erro em ${context}:`, error);
    let message = error.message || 'Erro desconhecido';

    if (error.name === 'TypeError') {
        message = 'Erro de conexão. Verifique sua internet.';
    }

    Swal.fire({
        icon: 'error',
        title: 'Erro!',
        text: message,
        confirmButtonColor: '#4e73df'
    });
}

function validarFormulario(form) {
    const campos = form.querySelectorAll('input[required], textarea[required], select[required]');
    let isValid = true;

    campos.forEach(campo => {
        if (!campo.value.trim()) {
            campo.classList.add('is-invalid');
            isValid = false;
        } else {
            campo.classList.remove('is-invalid');
        }
    });

    return isValid;
}

// ===== CONSTANTES E ELEMENTOS =====
const BASE_URL = "<?= BASE_URL ?>";
const ELEMENTOS = {
    tipo: document.getElementById('tipo'),
    opcoesContainer: document.getElementById('opcoes-container'),
    complementoCheckbox: document.getElementById('complemento_texto'),
    placeholderContainer: document.getElementById('placeholder-container'),
    formPergunta: document.getElementById('formPergunta'),
    tabelaPerguntas: document.querySelector('#tabela-perguntas'),
    formCriarFicha: document.getElementById('formCriarFicha'),
    formExcluirMultiplas: document.getElementById('form-excluir-multiplas'),
    selectAllCheckbox: document.getElementById('select-all'),
    formModelo: document.querySelector('#formModelo'),
    contadorPerguntas: document.getElementById('contador-perguntas'),
    btnExcluirSelecionadas: document.getElementById('btn-excluir-selecionadas')
};

// ===== FUNÇÕES HELPER =====
function getIconeTipo(tipo) {
    const icones = {
        texto: 'fa-font',
        checkbox: 'fa-check-square',
        radio: 'fa-dot-circle',
        select: 'fa-caret-square-down',
        textarea: 'fa-align-left',
        date: 'fa-calendar',
        tel: 'fa-phone',
        number: 'fa-sort-numeric-up',
        assinatura: 'fa-pen-fancy'
    };
    return icones[tipo] || 'fa-question-circle';
}

function capitalize(texto) {
    if (!texto) return '';
    return texto.charAt(0).toUpperCase() + texto.slice(1);
}

function atualizarBotaoExcluir() {
    const checkboxesMarcados = document.querySelectorAll('.checkbox-pergunta:checked');
    if (ELEMENTOS.btnExcluirSelecionadas) {
        ELEMENTOS.btnExcluirSelecionadas.disabled = checkboxesMarcados.length === 0;
    }
}

function atualizarContadorPerguntas() {
    if (!ELEMENTOS.tabelaPerguntas) return;
    const total = ELEMENTOS.tabelaPerguntas.querySelectorAll('tr[data-id]').length;
    if (ELEMENTOS.contadorPerguntas) {
        ELEMENTOS.contadorPerguntas.textContent = `${total} pergunta(s)`;
    }
}

function exibirLinhaSemPerguntasSeVazio() {
    if (!ELEMENTOS.tabelaPerguntas) return;

    // Remove linha existente se houver
    const linhaExistente = document.getElementById('sem-perguntas');
    if (linhaExistente) {
        linhaExistente.remove();
    }

    // Verifica se há perguntas (procura por tr que tenham data-id)
    const perguntasExistentes = ELEMENTOS.tabelaPerguntas.querySelectorAll('tr[data-id]');

    if (perguntasExistentes.length === 0) {
        const linhaVazia = document.createElement('tr');
        linhaVazia.id = 'sem-perguntas';
        linhaVazia.innerHTML = `
            <td colspan="6" class="text-center text-muted py-4">
                <i class="fas fa-info-circle me-2"></i> Nenhuma pergunta cadastrada ainda.
            </td>
        `;
        ELEMENTOS.tabelaPerguntas.appendChild(linhaVazia);
        // console.log('Linha "sem perguntas" adicionada');
    }
    // else {
    //     console.log(`${perguntasExistentes.length} pergunta(s) encontrada(s)`);
    // }
}

// ===== FUNÇÃO PARA ADICIONAR PERGUNTA NA TABELA =====
function adicionarLinhaPerguntaNaTabela(p, csrfToken) {
    if (!ELEMENTOS.tabelaPerguntas) return;

    // Remove a linha de "sem perguntas" se existir
    const semPerguntas = document.getElementById('sem-perguntas');
    if (semPerguntas) {
        semPerguntas.remove();
        // console.log('Linha "sem perguntas" removida ao adicionar nova pergunta');
    }

    // Verificar se já existe
    const linhaExistente = ELEMENTOS.tabelaPerguntas.querySelector(`tr[data-id="${p.id}"]`);
    if (linhaExistente) {
        console.warn('Pergunta já existe na tabela:', p.id);
        return;
    }

    const novaLinha = document.createElement('tr');
    novaLinha.setAttribute('data-id', p.id);

    novaLinha.innerHTML = `
        <td class="text-center">
            <input type="checkbox" name="perguntasSelecionadas[]" value="${p.id}" class="checkbox-pergunta">
        </td>
        <td title="${escapeHtml(p.pergunta)}">${escapeHtml(p.pergunta)}</td>
        <td><i class="fas ${getIconeTipo(p.tipo)} me-1"></i> ${capitalize(p.tipo)}</td>
        <td class="text-center">${p.obrigatorio ?
            '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Sim</span>' :
            '<span class="badge bg-secondary"><i class="fas fa-times me-1"></i>Não</span>'}</td>
        <td class="text-center">
            <button class="btn btn-danger btn-sm excluir-pergunta"  
                    data-id="${p.id}"
                    data-pergunta="${escapeHtml(p.pergunta)}"
                    data-token="${csrfToken}" 
                    data-url="${BASE_URL}admin/perguntas/excluir/${p.id}"
                    title="Excluir pergunta">
                <i class="fas fa-trash-alt"></i>
            </button>
        </td>`;

    // Animação suave
    novaLinha.style.opacity = '0';
    novaLinha.style.transform = 'translateY(-20px)';
    ELEMENTOS.tabelaPerguntas.prepend(novaLinha);

    requestAnimationFrame(() => {
        novaLinha.style.transition = 'all 0.3s ease';
        novaLinha.style.opacity = '1';
        novaLinha.style.transform = 'translateY(0)';
    });

    // Atualiza o estado dos checkboxes se "selecionar todos" estiver marcado
    if (ELEMENTOS.selectAllCheckbox && ELEMENTOS.selectAllCheckbox.checked) {
        novaLinha.querySelector('.checkbox-pergunta').checked = true;
    }

    atualizarBotaoExcluir();
    atualizarContadorPerguntas();

    // console.log('Nova pergunta adicionada:', p);
}

// ===== FUNÇÕES DE INTERFACE =====
function verificarTipo() {
    if (!ELEMENTOS.tipo) return;
    const tiposComOpcoes = ['checkbox', 'radio', 'select'];
    if (tiposComOpcoes.includes(ELEMENTOS.tipo.value)) {
        ELEMENTOS.opcoesContainer.style.display = 'block';
    } else {
        ELEMENTOS.opcoesContainer.style.display = 'none';
    }
}

function verificarComplemento() {
    if (!ELEMENTOS.complementoCheckbox) return;
    if (ELEMENTOS.complementoCheckbox.checked) {
        ELEMENTOS.placeholderContainer.style.display = 'block';
    } else {
        ELEMENTOS.placeholderContainer.style.display = 'none';
    }
}

// ===== EXCLUSÃO INDIVIDUAL - VERSÃO CORRIGIDA =====
function configurarExclusaoIndividual() {
    if (!ELEMENTOS.tabelaPerguntas) return;

    ELEMENTOS.tabelaPerguntas.addEventListener('click', async function (e) {
        // Verifica se clicou no botão de excluir individual
        const botaoExcluir = e.target.closest('.excluir-pergunta');
        if (!botaoExcluir) return;

        // Previne qualquer comportamento padrão
        e.preventDefault();
        e.stopPropagation();

        const id = botaoExcluir.dataset.id;
        const pergunta = botaoExcluir.dataset.pergunta || '(sem título)';
        const csrfToken = botaoExcluir.dataset.token;
        const actionUrl = botaoExcluir.dataset.url;

        // console.log('Excluindo pergunta individual:', { id, pergunta, actionUrl });

        // Validação básica
        if (!id || !csrfToken || !actionUrl) {
            console.error('Dados incompletos para exclusão:', { id, csrfToken, actionUrl });
            Swal.fire('Erro!', 'Dados incompletos para exclusão.', 'error');
            return;
        }

        // Confirmação
        const confirmacao = await Swal.fire({
            title: 'Confirmar exclusão',
            html: `Você tem certeza que deseja excluir a pergunta:<br><strong>${pergunta}</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74a3b',
            cancelButtonColor: '#858796',
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar'
        });

        if (!confirmacao.isConfirmed) return;

        // Desabilita o botão durante a operação
        botaoExcluir.disabled = true;

        // Loading
        Swal.fire({
            title: 'Excluindo...',
            text: 'Por favor, aguarde.',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const formData = new FormData();
            formData.append('csrf_token', csrfToken);

            const response = await fetch(actionUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const contentType = response.headers.get('content-type');
            if (!response.ok || !contentType || !contentType.includes('application/json')) {
                const html = await response.text();
                throw new Error(`Resposta inválida do servidor:\n\n${html}`);
            }

            const result = await response.json();


            if (result.status === 'success') {
                Swal.fire('Excluída!', result.message, 'success');

                // Remove a linha da tabela
                const linha = botaoExcluir.closest('tr');
                if (linha) {
                    linha.remove();
                }

                // Atualiza interface
                atualizarBotaoExcluir();
                atualizarContadorPerguntas();
                exibirLinhaSemPerguntasSeVazio();

                // console.log('Pergunta excluída com sucesso:', id);
            } else {
                throw new Error(result.message || 'Erro ao excluir a pergunta.');
            }
        } catch (error) {
            console.error('Erro na exclusão individual:', error);
            handleError(error, 'Exclusão de pergunta');
        } finally {
            // Re-habilita o botão (caso ainda exista)
            if (botaoExcluir.parentNode) {
                botaoExcluir.disabled = false;
            }
        }
    });
}

// ===== EXCLUSÃO MÚLTIPLA =====
function configurarExclusaoMultipla() {
    if (!ELEMENTOS.formExcluirMultiplas) return;

    ELEMENTOS.formExcluirMultiplas.addEventListener('submit', async function (e) {
        e.preventDefault();

        const selecionadas = Array.from(document.querySelectorAll('.checkbox-pergunta:checked'))
            .map(cb => cb.value);

        if (selecionadas.length === 0) {
            Swal.fire('Atenção!', 'Selecione pelo menos uma pergunta para excluir.', 'warning');
            return;
        }

        const csrfToken = this.querySelector('input[name="csrf_token"]').value;

        const confirmacao = await Swal.fire({
            title: 'Tem certeza?',
            text: `Você deseja excluir as ${selecionadas.length} perguntas selecionadas?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sim, excluir todas',
            cancelButtonText: 'Cancelar'
        });

        if (!confirmacao.isConfirmed) return;

        Swal.fire({
            title: 'Excluindo...',
            text: 'Aguarde enquanto as perguntas são removidas.',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const formData = new FormData();
            selecionadas.forEach(id => formData.append('perguntasSelecionadas[]', id));
            formData.append('csrf_token', csrfToken);

            const response = await fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-CSRF-TOKEN': csrfToken }
            });
            const result = await response.json();

            if (result.status === 'success') {
                // Remove todas as linhas marcadas
                selecionadas.forEach(id => {
                    const linha = document.querySelector(`tr[data-id="${id}"]`);
                    if (linha) linha.remove();
                });

                Swal.fire('Sucesso!', result.message, 'success');
                atualizarBotaoExcluir();
                atualizarContadorPerguntas();
                exibirLinhaSemPerguntasSeVazio();

                if (ELEMENTOS.selectAllCheckbox) {
                    ELEMENTOS.selectAllCheckbox.checked = false;
                }
            } else {
                throw new Error(result.message || 'Erro ao excluir as perguntas.');
            }
        } catch (error) {
            handleError(error, 'Exclusão múltipla');
        }
    });
}

// ===== CONFIGURAÇÃO DE FORMULÁRIOS =====
function configurarFormularios() {
    // ===== FORMULÁRIO DE USAR MODELO =====
    if (ELEMENTOS.formModelo) {
        ELEMENTOS.formModelo.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitButton = ELEMENTOS.formModelo.querySelector('button[type="submit"]');
            const modeloId = ELEMENTOS.formModelo.querySelector('#modelo_id').value;

            if (!modeloId) {
                Swal.fire('Atenção!', 'Por favor, escolha uma pergunta modelo para usar.', 'warning');
                return;
            }

            submitButton.disabled = true;
            Swal.fire({
                title: 'Importando...',
                text: 'Aguarde enquanto a pergunta é adicionada.',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const response = await fetch(ELEMENTOS.formModelo.action, {
                    method: 'POST',
                    body: new FormData(ELEMENTOS.formModelo),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await response.json();

                if (data.status === 'duplicate') {
                    const confirmacao = await Swal.fire({
                        icon: 'warning',
                        title: 'Pergunta já existe!',
                        html: `
                            <p>A pergunta <strong>${escapeHtml(data.pergunta_existente.texto)}</strong> já está no seu banco.</p>
                            <p>Deseja adicioná-la mesmo assim?</p>
                        `,
                        showCancelButton: true,
                        confirmButtonColor: '#1cc88a',
                        cancelButtonColor: '#e74a3b',
                        confirmButtonText: 'Sim, adicionar',
                        cancelButtonText: 'Cancelar'
                    });

                    if (!confirmacao.isConfirmed) {
                        Swal.fire('Cancelado', 'A pergunta não foi adicionada.', 'info');
                        submitButton.disabled = false;
                        return;
                    }

                    const formData = new FormData(ELEMENTOS.formModelo);
                    formData.append('forcar_salvamento', '1');

                    const responseForcada = await fetch(ELEMENTOS.formModelo.action, {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });

                    const dataFinal = await responseForcada.json();
                    if (!responseForcada.ok || dataFinal.status !== 'success') {
                        throw new Error(dataFinal.message || 'Erro ao adicionar pergunta forçada.');
                    }

                    Swal.fire('Importada!', dataFinal.message, 'success');
                    adicionarLinhaPerguntaNaTabela(dataFinal.pergunta, dataFinal.csrf_token);
                    ELEMENTOS.formModelo.reset();
                    submitButton.disabled = false;
                    return;
                }

                if (!response.ok || data.status !== 'success') {
                    throw new Error(data.message || 'Erro ao importar a pergunta.');
                }

                Swal.fire('Importada!', data.message, 'success');
                adicionarLinhaPerguntaNaTabela(data.pergunta, data.csrf_token);
                ELEMENTOS.formModelo.reset();
            } catch (error) {
                handleError(error, 'Importação de modelo');
            } finally {
                submitButton.disabled = false;
            }
        });
    }

    // ===== FORMULÁRIO DE CRIAÇÃO RÁPIDA DE FICHA =====
    if (ELEMENTOS.formCriarFicha) {
        ELEMENTOS.formCriarFicha.addEventListener('submit', async (e) => {
            e.preventDefault();

            const form = ELEMENTOS.formCriarFicha;
            const submitButton = form.querySelector('button[type="submit"]');
            const formData = new FormData(form);

            submitButton.disabled = true;

            Swal.fire({
                title: 'Criando ficha...',
                text: 'Por favor, aguarde.',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const contentType = response.headers.get('content-type');
                if (!response.ok || !contentType || !contentType.includes('application/json')) {
                    const html = await response.text();
                    throw new Error(`Resposta inválida do servidor:\n\n${html}`);
                }

                const data = await response.json();

                if (data.status === 'success') {
                    window.location.href = data.redirect;
                } else {
                    throw new Error(data.message || 'Erro ao criar a ficha.');
                }

            } catch (error) {
                handleError(error, 'Criação de ficha');
            } finally {
                submitButton.disabled = false;
            }
        });
    }


    // ===== FORMULÁRIO DE CRIAR PERGUNTA MANUAL =====
    if (ELEMENTOS.formPergunta) {
        ELEMENTOS.formPergunta.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!validarFormulario(ELEMENTOS.formPergunta)) {
                Swal.fire('Atenção!', 'Preencha todos os campos obrigatórios.', 'warning');
                return;
            }

            const submitButton = ELEMENTOS.formPergunta.querySelector('button[type="submit"]');
            const formData = new FormData(ELEMENTOS.formPergunta);

            submitButton.disabled = true;
            Swal.fire({
                title: 'Salvando...',
                text: 'Aguarde enquanto a pergunta é adicionada.',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const response = await fetch(ELEMENTOS.formPergunta.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await response.json();

                if (data.status === 'duplicate') {
                    const confirmacao = await Swal.fire({
                        icon: 'warning',
                        title: 'Pergunta já existe!',
                        html: `
                            <p>A pergunta <strong>${escapeHtml(data.pergunta_existente.texto)}</strong> já está no seu banco.</p>
                            <p>Deseja adicioná-la mesmo assim?</p>
                        `,
                        showCancelButton: true,
                        confirmButtonColor: '#1cc88a',
                        cancelButtonColor: '#e74a3b',
                        confirmButtonText: 'Sim, adicionar',
                        cancelButtonText: 'Cancelar'
                    });

                    if (!confirmacao.isConfirmed) {
                        Swal.fire('Cancelado', 'A pergunta não foi adicionada.', 'info');
                        submitButton.disabled = false;
                        return;
                    }

                    formData.append('forcar_salvamento', '1');

                    const responseForcada = await fetch(ELEMENTOS.formPergunta.action, {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });

                    const dataFinal = await responseForcada.json();
                    if (!responseForcada.ok || dataFinal.status !== 'success') {
                        throw new Error(dataFinal.message || 'Erro ao adicionar pergunta forçada.');
                    }

                    Swal.fire('Adicionada!', dataFinal.message, 'success');
                    adicionarLinhaPerguntaNaTabela(dataFinal.pergunta, dataFinal.csrf_token);
                    ELEMENTOS.formPergunta.reset();
                    verificarTipo();
                    verificarComplemento();
                    submitButton.disabled = false;
                    return;
                }

                if (!response.ok || data.status !== 'success') {
                    throw new Error(data.message || 'Erro ao salvar pergunta.');
                }

                Swal.fire('Adicionada!', data.message, 'success');
                adicionarLinhaPerguntaNaTabela(data.pergunta, data.csrf_token);
                ELEMENTOS.formPergunta.reset();
                verificarTipo();
                verificarComplemento();
            } catch (error) {
                handleError(error, 'Criação de pergunta');
            } finally {
                submitButton.disabled = false;
            }
        });
    }
}


// ===== CONFIGURAÇÃO DE CHECKBOXES =====
function configurarCheckboxes() {
    // Listener para checkboxes individuais
    if (ELEMENTOS.tabelaPerguntas) {
        ELEMENTOS.tabelaPerguntas.addEventListener('change', function (e) {
            if (e.target.classList.contains('checkbox-pergunta')) {
                atualizarBotaoExcluir();

                // Atualiza estado do "selecionar todos"
                const todosCheckboxes = document.querySelectorAll('.checkbox-pergunta');
                const marcados = document.querySelectorAll('.checkbox-pergunta:checked');

                if (ELEMENTOS.selectAllCheckbox) {
                    ELEMENTOS.selectAllCheckbox.checked = todosCheckboxes.length > 0 &&
                        marcados.length === todosCheckboxes.length;
                    ELEMENTOS.selectAllCheckbox.indeterminate = marcados.length > 0 &&
                        marcados.length < todosCheckboxes.length;
                }
            }
        });
    }

    // Listener para "selecionar todos"
    if (ELEMENTOS.selectAllCheckbox) {
        ELEMENTOS.selectAllCheckbox.addEventListener('change', function () {
            const checkboxes = ELEMENTOS.tabelaPerguntas?.querySelectorAll('.checkbox-pergunta');
            if (checkboxes) {
                checkboxes.forEach(cb => cb.checked = this.checked);
                this.indeterminate = false;
                atualizarBotaoExcluir();
            }
        });
    }
}

// ===== INICIALIZAÇÃO =====
function inicializar() {
    // Verifica estado inicial da tabela
    exibirLinhaSemPerguntasSeVazio();

    // Inicializa interface
    verificarTipo();
    verificarComplemento();
    atualizarBotaoExcluir();
    atualizarContadorPerguntas();

    // Configura event listeners
    configurarExclusaoIndividual();
    configurarExclusaoMultipla();
    configurarFormularios();
    configurarCheckboxes();

    // Event listeners para mudanças de tipo e complemento
    ELEMENTOS.tipo?.addEventListener('change', verificarTipo);
    ELEMENTOS.complementoCheckbox?.addEventListener('change', verificarComplemento);

    // Handler para mensagens flash
    const flashEl = document.getElementById('mensagem-php');
    if (flashEl) {
        Swal.fire({
            icon: flashEl.dataset.tipo || 'success',
            title: flashEl.dataset.titulo || 'Sucesso!',
            text: flashEl.dataset.texto || '',
            confirmButtonColor: '#4e73df'
        });
    }

    // console.log('Sistema de gerenciamento de perguntas inicializado com sucesso');
}

// ===== INICIALIZAÇÃO AUTOMÁTICA =====
document.addEventListener('DOMContentLoaded', inicializar);