import{b as _,d as v,e as c,c as x,i as $}from"./api-Dkfcp6ON.js";import{e as r}from"./utils-Bj4jxwhy.js";const l=_();let d=[];document.addEventListener("DOMContentLoaded",()=>{p()});async function p(){try{const t=await v(`${l}api/cupons`);if(t.success)d=t.data.cupons,I();else throw new Error(t.message||"Erro ao carregar cupons")}catch(t){console.error("Erro ao carregar cupons:",t),LKFeedback.error(c(t,"Erro ao carregar cupons."))}finally{document.getElementById("loading").style.display="none"}}function I(){const t=document.getElementById("cuponsTableBody"),e=document.getElementById("cuponsTable"),s=document.getElementById("emptyState"),a=document.getElementById("cuponsStats");if(d.length===0){e.style.display="none",s.style.display="block",a.style.display="none";return}const i=d.filter(o=>o.is_valid).length,n=d.reduce((o,u)=>o+u.uso_atual,0);document.getElementById("statTotalCupons").textContent=d.length,document.getElementById("statCuponsAtivos").textContent=i,document.getElementById("statTotalUsos").textContent=n,a.style.display="grid",e.style.display="table",s.style.display="none",t.innerHTML=d.map(o=>{const u=o.is_valid?'<span class="badge badge-ativo"><i data-lucide="circle-check"></i> Valido</span>':'<span class="badge badge-inativo"><i data-lucide="x-circle"></i> Invalido</span>',E=o.tipo_desconto==="percentual"?'<span class="badge badge-percentual"><i data-lucide="percent"></i> Percentual</span>':'<span class="badge badge-fixo"><i data-lucide="dollar-sign"></i> Fixo</span>';let m="";if(o.limite_uso>0){const g=o.uso_atual/o.limite_uso*100;m=`<span class="uso-badge ${g>=80?"esgotado":g>=50?"limitado":""}"><i data-lucide="pie-chart"></i> ${o.uso_atual}/${o.limite_uso}</span>`}else m=`<span class="uso-badge"><i data-lucide="infinity"></i> ${o.uso_atual} usos</span>`;return`
            <tr>
                <td><span class="cupom-codigo">${r(o.codigo)}</span></td>
                <td><span class="desconto-valor">${r(o.desconto_formatado)}</span></td>
                <td>${E}</td>
                <td><i data-lucide="calendar-days" style="margin-right: 0.375rem; opacity: 0.5;"></i>${r(o.valido_ate)}</td>
                <td>${m}</td>
                <td>${u}</td>
                <td>
                    <button class="btn-action btn-detalhes-mobile" data-action="verDetalhesMobile" data-cupom-id="${o.id}" title="Ver detalhes">
                        <i data-lucide="eye"></i>
                    </button>
                    <button class="btn-action btn-ver" data-action="verEstatisticas" data-cupom-id="${o.id}" title="Ver estatisticas">
                        <i data-lucide="bar-chart-3"></i> Ver
                    </button>
                    <button class="btn-action btn-excluir" data-action="excluirCupom" data-cupom-id="${o.id}" data-cupom-codigo="${r(o.codigo)}" title="Excluir">
                        <i data-lucide="trash-2"></i> Excluir
                    </button>
                </td>
            </tr>
        `}).join(""),typeof lucide<"u"&&lucide.createIcons()}function B(){document.getElementById("modalCupom").classList.add("show"),document.getElementById("formCupom").reset(),document.getElementById("hora_valido_ate").value="23:59",document.getElementById("apenas_primeira_assinatura").checked=!0,document.getElementById("permite_reativacao").checked=!1,document.getElementById("meses_inatividade_reativacao").value="3",document.getElementById("reativacaoGroup").style.display="block",document.getElementById("mesesInatividadeGroup").style.display="none",document.getElementById("modalTitle").textContent="Criar Novo Cupom"}function f(){document.getElementById("modalCupom").classList.remove("show")}function w(){const t=document.getElementById("apenas_primeira_assinatura").checked,e=document.getElementById("reativacaoGroup");e.style.display=t?"block":"none",t||(document.getElementById("permite_reativacao").checked=!1,h())}function h(){const t=document.getElementById("permite_reativacao").checked,e=document.getElementById("mesesInatividadeGroup");e.style.display=t?"block":"none"}function C(){const t=document.getElementById("tipo_desconto").value,e=document.getElementById("descontoHelp");t==="percentual"?(e.textContent="Desconto em percentual (0-100)",document.getElementById("valor_desconto").placeholder="10"):(e.textContent="Valor fixo em reais",document.getElementById("valor_desconto").placeholder="19.90")}document.getElementById("formCupom").addEventListener("submit",async t=>{t.preventDefault();const e=new FormData(t.target),s=Object.fromEntries(e.entries());s.valor_desconto=parseFloat(s.valor_desconto),s.limite_uso=parseInt(s.limite_uso,10)||0,s.ativo=!0,s.apenas_primeira_assinatura=document.getElementById("apenas_primeira_assinatura").checked,s.permite_reativacao=document.getElementById("permite_reativacao").checked,s.meses_inatividade_reativacao=parseInt(document.getElementById("meses_inatividade_reativacao").value,10)||3;try{const a=await x(`${l}api/cupons`,s);if(a.success)LKFeedback.success(a.message,{toast:!0}),f(),p();else throw new Error(a.message||"Erro ao criar cupom")}catch(a){console.error("Erro ao criar cupom:",a),LKFeedback.error(c(a,"Erro ao criar cupom."))}});async function b(t,e){if((await LKFeedback.confirm(`Deseja realmente excluir o cupom "${e}"?`,{title:"Confirmar exclusao?",icon:"warning",isDanger:!0,confirmButtonText:"Sim, excluir",cancelButtonText:"Cancelar"})).isConfirmed)try{const a=await $(`${l}api/cupons`,{id:t});if(a.success)LKFeedback.success(a.message,{toast:!0}),p();else throw new Error(a.message||"Erro ao excluir cupom")}catch(a){console.error("Erro ao excluir cupom:",a),LKFeedback.error(c(a,"Erro ao excluir cupom."))}}async function y(t){try{const e=await v(`${l}api/cupons/estatisticas`,{id:t});if(!e.success)throw new Error(e.message||"Erro ao carregar estatisticas");const{cupom:s,estatisticas:a,usos:i}=e.data;let n="";i.length>0?(n='<div style="max-height: 300px; overflow-y: auto; margin-top: 1rem;"><table style="width: 100%; font-size: 0.9rem;"><thead><tr><th style="text-align: left; padding: 0.5rem; border-bottom: 1px solid #ddd;">Usuario</th><th style="text-align: left; padding: 0.5rem; border-bottom: 1px solid #ddd;">Desconto</th><th style="text-align: left; padding: 0.5rem; border-bottom: 1px solid #ddd;">Data</th></tr></thead><tbody>',i.forEach(o=>{n+=`<tr><td style="padding: 0.5rem; border-bottom: 1px solid #eee;">${r(o.usuario)}<br><small>${r(o.email)}</small></td><td style="padding: 0.5rem; border-bottom: 1px solid #eee;">${r(o.desconto_aplicado)}</td><td style="padding: 0.5rem; border-bottom: 1px solid #eee;">${r(o.usado_em)}</td></tr>`}),n+="</tbody></table></div>"):n='<p style="text-align: center; color: #999; margin-top: 1rem;">Nenhum uso registrado ainda</p>',Swal.fire({title:`Estatisticas: ${r(s.codigo)}`,html:`
                <div style="text-align: left;">
                    <p><strong>Desconto:</strong> ${r(s.desconto_formatado)}</p>
                    <p><strong>Usos:</strong> ${s.uso_atual} ${s.limite_uso>0?"/ "+s.limite_uso:"(ilimitado)"}</p>
                    <hr style="margin: 1rem 0;">
                    <p><strong>Total de Desconto Concedido:</strong> R$ ${a.total_desconto}</p>
                    <p><strong>Valor Total Original:</strong> R$ ${a.total_valor_original}</p>
                    ${n}
                </div>
            `,width:700,confirmButtonText:"Fechar"})}catch(e){LKFeedback.error(c(e,"Erro ao carregar estatisticas do cupom."))}}function k(t){const e=d.find(n=>n.id===t);if(!e)return;const s=e.is_valid?"Valido":"Invalido",a=e.tipo_desconto==="percentual"?"Percentual":"Valor Fixo",i=e.limite_uso>0?`${e.uso_atual} de ${e.limite_uso} usos`:`${e.uso_atual} usos (ilimitado)`;Swal.fire({title:r(e.codigo),html:`
            <div style="text-align: left; padding: 1rem;">
                <div style="margin-bottom: 1rem; padding: 0.75rem; background: rgba(230, 126, 34, 0.1); border-radius: 8px;">
                    <strong style="color: var(--color-primary);">Desconto:</strong><br>
                    <span style="font-size: 1.5rem; font-weight: bold; color: var(--color-primary);">${r(e.desconto_formatado)}</span>
                </div>

                <div style="display: grid; gap: 0.75rem;">
                    <div>
                        <strong>Tipo:</strong><br>
                        <span>${a}</span>
                    </div>

                    <div>
                        <strong>Validade:</strong><br>
                        <span>${r(e.valido_ate)}</span>
                    </div>

                    <div>
                        <strong>Uso:</strong><br>
                        <span>${i}</span>
                    </div>

                    <div>
                        <strong>Status:</strong><br>
                        <span>${s}</span>
                    </div>

                    ${e.descricao?`
                    <div>
                        <strong>Descricao:</strong><br>
                        <span>${r(e.descricao)}</span>
                    </div>
                    `:""}
                </div>

                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #ddd; display: flex; gap: 0.5rem; justify-content: center;">
                    <button data-action="verEstatisticasFromSwal" data-cupom-id="${e.id}"
                        style="padding: 0.5rem 1rem; background: #3498db; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                        <i data-lucide="bar-chart-3"></i> Ver Estatisticas
                    </button>
                    <button data-action="excluirCupomFromSwal" data-cupom-id="${e.id}" data-cupom-codigo="${r(e.codigo)}"
                        style="padding: 0.5rem 1rem; background: #e74c3c; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                        <i data-lucide="trash-2"></i> Excluir
                    </button>
                </div>
            </div>
        `,width:400,showConfirmButton:!0,confirmButtonText:"Fechar",confirmButtonColor:"#e67e22"})}document.addEventListener("click",t=>{const e=t.target.closest("[data-action]");if(!e)return;const s=e.dataset.action,a=e.dataset.cupomId?parseInt(e.dataset.cupomId,10):null,i=e.dataset.cupomCodigo||"";switch(s){case"abrirModalCriarCupom":B();break;case"fecharModalCupom":f();break;case"verDetalhesMobile":k(a);break;case"verEstatisticas":y(a);break;case"excluirCupom":b(a,i);break;case"verEstatisticasFromSwal":Swal.close(),y(a);break;case"excluirCupomFromSwal":Swal.close(),b(a,i);break}});document.addEventListener("change",t=>{const e=t.target.closest("[data-action]");if(e)switch(e.dataset.action){case"atualizarPlaceholder":C();break;case"toggleReativacao":w();break;case"toggleMesesInatividade":h();break}});
