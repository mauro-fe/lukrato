import { resolveMetaOperationForLancamento } from '../shared/lancamento-meta.js';

export function attachLancamentoGlobalPayloadMethods(ManagerClass, dependencies) {
    const {
        parseMoney,
    } = dependencies;

    Object.assign(ManagerClass.prototype, {
    coletarDadosFormulario() {
        const contaId = this.contaSelecionada?.id;
        if (!contaId) throw new Error('Conta não selecionada');

        const dados = {
            conta_id: parseInt(contaId),
            tipo: document.getElementById('globalLancamentoTipo').value,
            descricao: document.getElementById('globalLancamentoDescricao').value.trim(),
            valor: parseMoney(document.getElementById('globalLancamentoValor').value),
            data: document.getElementById('globalLancamentoData').value,
            hora_lancamento: document.getElementById('globalLancamentoHora')?.value || null,
            categoria_id: document.getElementById('globalLancamentoCategoria').value || null,
            subcategoria_id: document.getElementById('globalLancamentoSubcategoria')?.value || null,
            meta_id: null,
            meta_operacao: null,
            meta_valor: null,
            pago: true
        };

        const metaIdRaw = document.getElementById('globalLancamentoMeta')?.value || '';
        const metaId = Number.parseInt(String(metaIdRaw), 10);
        if (Number.isFinite(metaId) && metaId > 0) {
            const metaValorInformado = parseMoney(document.getElementById('globalLancamentoMetaValor')?.value || '');
            const metaValor = Math.max(0, Math.min(dados.valor, metaValorInformado || dados.valor));
            dados.meta_id = metaId;
            dados.meta_valor = Number(metaValor.toFixed(2));
            dados.meta_operacao = resolveMetaOperationForLancamento({
                tipo: this.tipoAtual,
                hasMetaLink: true,
                isRealizacao: Boolean(document.getElementById('globalLancamentoMetaRealizacao')?.checked)
            });
        }

        if (this.tipoAtual === 'transferencia') {
            dados.conta_destino_id = parseInt(document.getElementById('globalLancamentoContaDestino')?.value) || null;
            dados.eh_transferencia = true;
        }

        // Forma de pagamento (despesa)
        if (this.tipoAtual === 'despesa') {
            const formaPag = document.getElementById('globalFormaPagamento')?.value || '';
            if (formaPag) dados.forma_pagamento = formaPag;
            if (formaPag === 'cartao_credito') {
                const cartaoId = document.getElementById('globalLancamentoCartaoCredito')?.value;
                if (cartaoId) {
                    dados.cartao_credito_id = parseInt(cartaoId);

                    // Assinatura/recorrência no cartão
                    const assinaturaCheck = document.getElementById('globalLancamentoAssinaturaCartao');
                    if (assinaturaCheck?.checked) {
                        dados.recorrente = '1';
                        dados.recorrencia_freq = document.getElementById('globalLancamentoAssinaturaFreq')?.value || 'mensal';
                        dados.eh_parcelado = false; // Assinatura não é parcelamento

                        const modoAssinatura = document.querySelector('input[name="global_assinatura_modo"]:checked')?.value || 'infinito';
                        if (modoAssinatura === 'data') {
                            const fimAssinatura = document.getElementById('globalLancamentoAssinaturaFim')?.value || null;
                            if (fimAssinatura) {
                                dados.recorrencia_fim = fimAssinatura;
                            }
                        }
                    } else {
                        dados.eh_parcelado = document.getElementById('globalLancamentoParcelado')?.checked || false;
                        if (dados.eh_parcelado) {
                            dados.total_parcelas = parseInt(document.getElementById('globalLancamentoTotalParcelas')?.value) || 1;
                        }
                    }
                }
            }
        }

        // Forma de recebimento (receita)
        if (this.tipoAtual === 'receita') {
            const formaRec = document.getElementById('globalFormaRecebimento')?.value;
            if (formaRec) {
                dados.forma_pagamento = formaRec;
                if (formaRec === 'estorno_cartao') {
                    const cartaoId = document.getElementById('globalLancamentoCartaoCredito')?.value;
                    if (cartaoId) dados.cartao_credito_id = parseInt(cartaoId);
                    const faturaVal = document.getElementById('globalLancamentoFaturaEstorno')?.value;
                    if (faturaVal) dados.fatura_mes_ano = faturaVal;
                }
            }
            if (document.getElementById('globalLancamentoParcelado')?.checked) {
                dados.eh_parcelado = true;
                dados.total_parcelas = parseInt(document.getElementById('globalLancamentoTotalParcelas')?.value) || 1;
            }
        }

        // Parcelamento despesa sem cartão
        if (this.tipoAtual === 'despesa' && !dados.cartao_credito_id) {
            if (document.getElementById('globalLancamentoParcelado')?.checked) {
                dados.eh_parcelado = true;
                dados.total_parcelas = parseInt(document.getElementById('globalLancamentoTotalParcelas')?.value) || 1;
            }
        }

        // Recorrência + Lembrete + Pago
        if (this.tipoAtual === 'receita' || this.tipoAtual === 'despesa') {
            dados.pago = document.getElementById('globalLancamentoPago')?.checked ? true : false;

            if (document.getElementById('globalLancamentoRecorrente')?.checked) {
                dados.pago = false;
                dados.recorrente = '1';
                dados.recorrencia_freq = document.getElementById('globalLancamentoRecorrenciaFreq')?.value || 'mensal';
                const modo = document.querySelector('input[name="global_recorrencia_modo"]:checked')?.value || 'infinito';
                if (modo === 'quantidade') {
                    dados.recorrencia_total = parseInt(document.getElementById('globalLancamentoRecorrenciaTotal')?.value) || 12;
                } else if (modo === 'data') {
                    const recFim = document.getElementById('globalLancamentoRecorrenciaFim')?.value;
                    if (recFim) dados.recorrencia_fim = recFim;
                }
            }

            const tempoAviso = document.getElementById('globalLancamentoTempoAviso')?.value || '';
            if (tempoAviso && !dados.cartao_credito_id && !dados.pago) {
                dados.lembrar_antes_segundos = parseInt(tempoAviso);
                dados.canal_inapp = document.getElementById('globalCanalInapp')?.checked ? '1' : '0';
                dados.canal_email = document.getElementById('globalCanalEmail')?.checked ? '1' : '0';
            }
        }

        return dados;
    }
    });
}
