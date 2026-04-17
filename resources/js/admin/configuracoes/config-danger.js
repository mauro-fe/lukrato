import { apiDelete, apiLogout, getErrorMessage } from '../shared/api.js';

async function confirmDeleteAccount() {
    if (!window.Swal) {
        return window.confirm('ATENCAO: Esta acao e irreversivel. Deseja realmente excluir sua conta e todos os dados?');
    }

    const firstConfirmation = await Swal.fire({
        title: 'Confirmar exclusao de conta',
        html: `
            <div style="text-align:left;padding:1rem;">
                <p style="font-size:1.1rem;margin-bottom:1rem;"><strong>Esta acao e permanente e irreversivel.</strong></p>
                <p style="margin-bottom:0.5rem;">Ao confirmar, os seguintes dados serao permanentemente removidos:</p>
                <ul style="margin:1rem 0;padding-left:1.5rem;">
                    <li>Todos os lancamentos e historico financeiro</li>
                    <li>Contas e cartoes cadastrados</li>
                    <li>Categorias personalizadas</li>
                    <li>Metas e agendamentos</li>
                    <li>Informacoes pessoais</li>
                    <li>Plano PRO (se ativo)</li>
                </ul>
                <p style="color:#e74c3c;font-weight:bold;margin-top:1rem;">Nao sera possivel recuperar estes dados.</p>
                <p style="color:#7f8c8d;font-size:0.9rem;margin-top:1rem;">Apos exclusao, sera preciso aguardar 90 dias para criar nova conta com o mesmo email.</p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#95a5a6',
        confirmButtonText: 'Sim, excluir minha conta',
        cancelButtonText: 'Cancelar',
        focusCancel: true,
    });

    if (!firstConfirmation.isConfirmed) {
        return false;
    }

    const finalConfirmation = await Swal.fire({
        title: 'Ultima confirmacao',
        text: 'Digite "EXCLUIR" para confirmar a exclusao definitiva da sua conta',
        input: 'text',
        inputPlaceholder: 'Digite: EXCLUIR',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#95a5a6',
        confirmButtonText: 'Confirmar exclusao',
        cancelButtonText: 'Cancelar',
        inputValidator: (value) => {
            if (value !== 'EXCLUIR') {
                return 'Voce precisa digitar "EXCLUIR" para confirmar';
            }
            return null;
        },
    });

    return finalConfirmation.isConfirmed;
}

export function initConfigDangerZone(context) {
    const button = document.getElementById('btn-delete-account');
    const deleteAccountEndpoint = context.endpoints?.deleteAccount;
    if (!button) {
        return;
    }

    if (!deleteAccountEndpoint) {
        console.error('Endpoint de exclusao de conta nao configurado.');
        return;
    }

    button.addEventListener('click', async () => {
        const confirmed = await confirmDeleteAccount();
        if (!confirmed) {
            return;
        }

        try {
            button.disabled = true;

            if (window.Swal) {
                Swal.fire({
                    title: 'Excluindo conta...',
                    text: 'Por favor aguarde',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading(),
                });
            }

            const response = await apiDelete(deleteAccountEndpoint);
            if (response?.success === false) {
                throw new Error(getErrorMessage({ data: response }, 'Erro ao excluir conta.'));
            }

            if (window.Swal) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Conta excluida!',
                    text: 'Sua conta foi excluida com sucesso. Voce sera redirecionado.',
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    allowOutsideClick: false,
                });
            }

            await apiLogout({ redirectTo: `${context.BASE}login` });
        } catch (error) {
            console.error('Erro ao excluir conta:', error);

            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: getErrorMessage(error, 'Nao foi possivel excluir a conta. Tente novamente.'),
                });
            }
        } finally {
            button.disabled = false;
        }
    });
}
