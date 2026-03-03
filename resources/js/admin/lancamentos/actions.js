/**
 * ============================================================================
 * LUKRATO — Lançamentos / Shared Action Handlers
 * ============================================================================
 * Common action handlers shared between desktop (table.js) and mobile (mobile.js).
 * Prevents code duplication and ensures consistent behavior.
 * ============================================================================
 */

import { CONFIG, STATE, Utils, Notifications, Modules } from './state.js';

/**
 * Handle the "marcar-pago" action for a lancamento.
 * @param {number|string} id
 * @param {HTMLElement} triggerBtn - Button that triggered the action (will be disabled during request)
 */
export async function handleMarcarPago(id, triggerBtn) {
    const ok = await Notifications.ask(
        'Marcar como pago?',
        'Este lançamento será marcado como pago.'
    );
    if (!ok) return;

    if (triggerBtn) triggerBtn.disabled = true;
    try {
        const csrfToken = Utils.getCSRFToken();
        const response = await fetch(`${CONFIG.BASE_URL}api/lancamentos/${id}/pagar`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            }
        });
        if (response.ok) {
            Notifications.toast('Lançamento marcado como pago!');
            await Modules.DataManager.load();
        } else {
            const err = await response.json().catch(() => ({}));
            Notifications.toast(err.message || 'Erro ao marcar como pago.', 'error');
        }
    } catch (error) {
        Notifications.toast('Erro ao marcar como pago.', 'error');
    }
    if (triggerBtn) triggerBtn.disabled = false;
}

/**
 * Handle the "desmarcar-pago" action for a lancamento.
 * @param {number|string} id
 * @param {HTMLElement} triggerBtn
 */
export async function handleDesmarcarPago(id, triggerBtn) {
    const ok = await Notifications.ask(
        'Marcar como pendente?',
        'Este lançamento voltará a ser pendente e não afetará o saldo.'
    );
    if (!ok) return;

    if (triggerBtn) triggerBtn.disabled = true;
    try {
        const csrfToken = Utils.getCSRFToken();
        const response = await fetch(`${CONFIG.BASE_URL}api/lancamentos/${id}/despagar`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            }
        });
        if (response.ok) {
            Notifications.toast('Lançamento marcado como pendente!');
            await Modules.DataManager.load();
        } else {
            const err = await response.json().catch(() => ({}));
            Notifications.toast(err.message || 'Erro ao desmarcar pago.', 'error');
        }
    } catch (error) {
        Notifications.toast('Erro ao desmarcar pago.', 'error');
    }
    if (triggerBtn) triggerBtn.disabled = false;
}

/**
 * Handle the "cancelar-recorrencia" action for a lancamento.
 * @param {number|string} id
 * @param {HTMLElement} triggerBtn
 */
export async function handleCancelarRecorrencia(id, triggerBtn) {
    const ok = await Notifications.ask(
        'Cancelar recorrência?',
        'Todos os lançamentos futuros não pagos desta série serão cancelados.'
    );
    if (!ok) return;

    if (triggerBtn) triggerBtn.disabled = true;
    try {
        const csrfToken = Utils.getCSRFToken();
        const response = await fetch(`${CONFIG.BASE_URL}api/lancamentos/${id}/cancelar-recorrencia`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            }
        });
        if (response.ok) {
            Notifications.toast('Recorrência cancelada com sucesso!');
            await Modules.DataManager.load();
        } else {
            const err = await response.json().catch(() => ({}));
            Notifications.toast(err.message || 'Erro ao cancelar recorrência.', 'error');
        }
    } catch (error) {
        Notifications.toast('Erro ao cancelar recorrência.', 'error');
    }
    if (triggerBtn) triggerBtn.disabled = false;
}

/**
 * Handle the "delete" action for a lancamento.
 * @param {number|string} id
 * @param {object} item - The lancamento data object
 * @param {HTMLElement} triggerBtn
 */
export async function handleDelete(id, item, triggerBtn) {
    if (Utils.isSaldoInicial(item)) return;

    let scope = 'single';
    const isRecorrente = item.recorrente && item.recorrencia_pai_id;
    const isParcelamento = !!item.parcelamento_id;

    if (isRecorrente || isParcelamento) {
        const tipoLabel = isRecorrente ? 'recorrência' : 'parcelamento';
        const result = await Swal.fire({
            title: 'Excluir lançamento',
            html: `<p>Este lançamento faz parte de uma <strong>${tipoLabel}</strong>. O que deseja fazer?</p>`,
            icon: 'question',
            input: 'radio',
            inputOptions: {
                'single': 'Apenas este lançamento',
                'future': 'Este e todos os futuros não pagos',
                'all': `Toda a ${tipoLabel}`
            },
            inputValue: 'single',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Excluir',
            cancelButtonText: 'Cancelar',
            inputValidator: (value) => !value ? 'Selecione uma opção' : undefined
        });
        if (!result.isConfirmed) return;
        scope = result.value;
    } else {
        const ok = await Notifications.ask(
            'Excluir lançamento?',
            'Esta ação não pode ser desfeita.'
        );
        if (!ok) return;
    }

    if (triggerBtn) triggerBtn.disabled = true;
    const okDel = await Modules.API.deleteOne(id, scope);
    if (triggerBtn) triggerBtn.disabled = false;

    if (okDel) {
        STATE.selectedIds.delete(String(id));
        const msgs = {
            single: 'Lançamento excluído com sucesso!',
            future: 'Lançamentos futuros excluídos!',
            all: 'Toda a série excluída!'
        };
        Notifications.toast(msgs[scope] || 'Excluído!');
        await Modules.DataManager.load();
    } else {
        Notifications.toast('Falha ao excluir lançamento.', 'error');
    }
}

/**
 * Handle the "edit" action for a lancamento.
 * Routes to the correct modal (transfer vs regular).
 * @param {object} item - The lancamento data object
 */
export function handleEdit(item) {
    if (!Utils.canEditLancamento(item)) return;
    if (item.eh_transferencia) {
        Modules.ModalManager.openEditTransferencia(item);
    } else {
        Modules.ModalManager.openEditLancamento(item);
    }
}
