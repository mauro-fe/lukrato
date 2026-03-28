
import { CONFIG, STATE, Utils, Notifications, Modules } from './state.js';
import { apiPost, apiPut, getErrorMessage } from '../shared/api.js';

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
        await apiPut(`${CONFIG.BASE_URL}api/lancamentos/${id}/pagar`, {});
        Notifications.toast('Lançamento marcado como pago!');
        await Modules.DataManager.load();
    } catch (error) {
        Notifications.toast(getErrorMessage(error, 'Erro ao marcar como pago.'), 'error');
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
        await apiPut(`${CONFIG.BASE_URL}api/lancamentos/${id}/despagar`, {});
        Notifications.toast('Lançamento marcado como pendente!');
        await Modules.DataManager.load();
    } catch (error) {
        Notifications.toast(getErrorMessage(error, 'Erro ao desmarcar pago.'), 'error');
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
        await apiPost(`${CONFIG.BASE_URL}api/lancamentos/${id}/cancelar-recorrencia`, {});
        Notifications.toast('Recorrência cancelada com sucesso!');
        await Modules.DataManager.load();
    } catch (error) {
        Notifications.toast(getErrorMessage(error, 'Erro ao cancelar recorrência.'), 'error');
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
    const mode = isRecorrente ? 'recorrencia' : (isParcelamento ? 'parcelamento' : 'single');
    const result = await Modules.ModalManager.openDeleteScopeModal({ mode });
    if (!result?.scope) return;
    scope = result.scope;

    if (triggerBtn) triggerBtn.disabled = true;
    const okDel = await Modules.API.deleteOne(id, scope);
    if (triggerBtn) triggerBtn.disabled = false;

    if (okDel) {
        STATE.selectedIds.delete(String(id));
        const msgs = {
            single: 'Lancamento excluido com sucesso!',
            future: isParcelamento
                ? 'Parcelas futuras excluidas!'
                : 'Lancamentos futuros excluidos!',
            all: isParcelamento
                ? 'Todo o parcelamento foi excluido!'
                : 'Toda a recorrencia foi excluida!'
        };
        Notifications.toast(msgs[scope] || 'Excluido!');
        await Modules.DataManager.load();
    } else {
        Notifications.toast('Falha ao excluir lancamento.', 'error');
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
