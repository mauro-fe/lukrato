import{b as T,e as b,a as y}from"./api-CiEmwEpk.js";import{e as x,a as S}from"./utils-Bj4jxwhy.js";import{t as B,a as C,r as w}from"./ui-H2yoVZe7.js";const c=T();let m=[];const $=()=>document.getElementById("archivedGrid"),_=()=>document.getElementById("totalArquivados"),R=()=>document.getElementById("limiteTotal");function h(t){return S(t).replace("R$ ","").replace("R$ ","")}function L(t){return{visa:"#1a1f71",mastercard:"#eb001b",elo:"#ffcb05",amex:"#006fcf",hipercard:"#d9001b"}[String(t||"").toLowerCase()]||"#e67e22"}function D(t){const e=_();e&&(e.textContent=t.length);const a=t.reduce((s,i)=>s+parseFloat(i.limite_total||0),0),o=R();o&&(o.textContent=`R$ ${h(a)}`)}function q(){const t=$();t&&(t.innerHTML=`
        <div class="empty-state">
            <div class="empty-icon">
                <i data-lucide="credit-card" style="color: white;"></i>
            </div>
            <h3>Nenhum cartao arquivado</h3>
            <p>você nao possui cartoes arquivados no momento</p>
        </div>
    `,w())}function A(t){const e=$();if(e){if(!t.length){q();return}e.innerHTML=t.map(a=>{const o=x(a.nome_cartao||"Sem nome"),s=String(a.bandeira||"Desconhecida").toLowerCase(),i=h(a.limite_total||0),r=h(a.limite_disponivel||0),l=a.ultimos_digitos||"0000",d=a.conta?.instituicao_financeira?.cor_primaria||a.instituicao_cor||a.cor_cartao||L(s),p={visa:`${c}assets/img/bandeiras/visa.png`,mastercard:`${c}assets/img/bandeiras/mastercard.png`,elo:`${c}assets/img/bandeiras/elo.png`,amex:`${c}assets/img/bandeiras/amex.png`,hipercard:`${c}assets/img/bandeiras/hipercard.png`}[s]||"",n=p?`<img src="${p}" alt="${s}" class="brand-logo">`:'<i data-lucide="credit-card" class="brand-icon-fallback"></i>';return`
            <div class="credit-card surface-card surface-card--interactive surface-card--clip" data-brand="${s}" data-id="${a.id}" style="background: ${d}">
                <div class="card-header">
                    <div class="card-brand">
                        ${n}
                        <span class="card-name">${o}</span>
                    </div>
                    <div class="card-actions">
                        <button class="card-action-btn" onclick="handleRestore(${a.id})" title="Restaurar">
                            <i data-lucide="undo-2"></i>
                        </button>
                        <button class="card-action-btn" onclick="handleHardDelete(${a.id}, '${o.replace(/'/g,"\\'")}')" title="Excluir permanentemente">
                            <i data-lucide="trash-2"></i>
                        </button>
                    </div>
                </div>
                <div class="card-number">**** **** **** ${l}</div>
                <div class="card-footer">
                    <div class="card-holder">
                        <div class="card-label">Limite Disponivel</div>
                        <div class="card-value">R$ ${r}</div>
                    </div>
                    <div class="card-limit">
                        <div class="card-label">Limite Total</div>
                        <div class="card-value">R$ ${i}</div>
                    </div>
                </div>
            </div>
        `}).join(""),w()}}async function g(t,e={}){const a=`${c}api/${t}`.replace(/\/{2,}/g,"/").replace(":/","://");try{return await y(a,{credentials:"same-origin",...e})}catch(o){if(o?.status!==404)throw o;const s=`${c}index.php/api/${t}`.replace(/\/{2,}/g,"/").replace(":/","://");return y(s,{credentials:"same-origin",...e})}}async function v(){const t=$();if(t)try{t.setAttribute("aria-busy","true");const e=await g("cartoes?archived=1");m=Array.isArray(e)?e:e?.data||[],D(m),A(m)}catch(e){console.error(e),t.innerHTML=`
            <div class="empty-state">
                <div class="empty-icon"><i data-lucide="triangle-alert"></i></div>
                <h3>Erro ao carregar</h3>
                <p>${x(b(e,"Nao foi possivel carregar os cartoes arquivados."))}</p>
            </div>
        `,w()}finally{t.setAttribute("aria-busy","false")}}window.handleRestore=async function(e){const a=m.find(i=>i.id===e),o=a?a.nome_cartao:"este cartao";if((await window.Swal.fire({title:"Restaurar Cartao",html:`Deseja restaurar o cartao <strong>${o}</strong>?`,icon:"question",showCancelButton:!0,confirmButtonText:'<i data-lucide="undo-2"></i> Sim, restaurar',cancelButtonText:'<i data-lucide="x"></i> Cancelar',confirmButtonColor:"#2ecc71",cancelButtonColor:"#6c757d",reverseButtons:!0})).isConfirmed)try{await g(`cartoes/${e}/restore`,{method:"POST"}),B("O cartao foi restaurado com sucesso."),await v()}catch(i){console.error(i),C(b(i,"Falha ao restaurar."))}};window.handleHardDelete=async function(e,a=""){const o=m.find(r=>r.id===e),s=o?o.nome_cartao:a||"este cartao";if((await window.Swal.fire({title:"Excluir permanentemente?",html:`Tem certeza que deseja excluir <strong>${s}</strong>?<br><small class="text-muted" style="color: #dc3545;">Esta acao nao pode ser desfeita!</small>`,icon:"warning",showCancelButton:!0,confirmButtonText:'<i data-lucide="trash-2"></i> Sim, excluir',cancelButtonText:'<i data-lucide="x"></i> Cancelar',confirmButtonColor:"#dc3545",cancelButtonColor:"#6c757d",reverseButtons:!0})).isConfirmed)try{const r=await g(`cartoes/${e}/delete`,{method:"POST",body:{force:!1}});if(r?.success===!1&&r?.errors?.requires_confirmation){const l=r?.data?.total_lancamentos||0,d=r?.data?.total_faturas||0,f=r?.data?.total_itens||0,p=l+d+f;let n="";if(p>0?(n='<ul style="text-align:left; margin-top: 1rem; margin-bottom: 1rem;">',l>0&&(n+=`<li><b>${l}</b> lancamento(s)</li>`),d>0&&(n+=`<li><b>${d}</b> fatura(s)</li>`),f>0&&(n+=`<li><b>${f}</b> item(ns) de fatura</li>`),n+="</ul>"):n=`<p style="margin: 1rem 0; white-space: pre-line;">${r.message||"Nenhum dado vinculado encontrado"}</p>`,!(await window.Swal.fire({title:"Excluir cartao e TODOS os dados vinculados?",html:`<div style="text-align:left; padding: 1rem;">
                    <p style="margin-bottom: 1rem;">O cartao <b>${s.replace(/</g,"&lt;")}</b> possui os seguintes dados vinculados:</p>
                    ${n}
                    <p style="margin-top: 1rem; color: #dc3545; font-weight: 600;">Ao excluir o cartao, TODOS esses dados serao excluidos permanentemente!</p>
                    <p style="margin-top: 0.5rem;">Esta acao nao pode ser desfeita. Deseja continuar?</p>
                </div>`,icon:"warning",showCancelButton:!0,confirmButtonText:'<i data-lucide="trash-2"></i> Sim, excluir tudo',cancelButtonText:'<i data-lucide="x"></i> Cancelar',confirmButtonColor:"#dc3545",cancelButtonColor:"#6c757d",reverseButtons:!0})).isConfirmed)return;const u=await g(`cartoes/${e}/delete`,{method:"POST",body:{force:!0}});if(!u?.success)throw new Error(u?.message||"Erro ao excluir");const E=(u.data?.deleted_lancamentos||0)+(u.data?.deleted_faturas||0)+(u.data?.deleted_itens||0);await window.Swal.fire({icon:"success",title:"Excluido!",html:`<p><b>${s}</b> e todos os dados vinculados foram excluidos permanentemente.</p>
                    <p style="margin-top: 0.5rem; font-size: 0.9em; color: #6c757d;">Total de registros excluidos: ${E}</p>`,timer:3e3,showConfirmButton:!1}),await v();return}if(r?.success===!1)throw new Error(r?.message||"Erro ao excluir");B("Cartao excluido com sucesso."),await v()}catch(r){console.error(r),C(b(r,"Falha ao excluir."))}};v();
