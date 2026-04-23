import{m as T,e as b,b as S}from"./api-DpYnTMaG.js";import{e as x,a as _}from"./utils-Bj4jxwhy.js";import{t as C,a as B,r as w}from"./ui-H2yoVZe7.js";import{y as R,z as $,b as D}from"./finance-CgaDv1sH.js";const u=T();let m=[];const y=()=>document.getElementById("archivedGrid"),L=()=>document.getElementById("totalArquivados"),q=()=>document.getElementById("limiteTotal");function h(t){return _(t).replace("R$ ","").replace("R$ ","")}function A(t){return{visa:"#1a1f71",mastercard:"#eb001b",elo:"#ffcb05",amex:"#006fcf",hipercard:"#d9001b"}[String(t||"").toLowerCase()]||"#e67e22"}function O(t){const e=L();e&&(e.textContent=t.length);const a=t.reduce((r,i)=>r+parseFloat(i.limite_total||0),0),s=q();s&&(s.textContent=`R$ ${h(a)}`)}function H(){const t=y();t&&(t.innerHTML=`
        <div class="empty-state">
            <div class="empty-icon">
                <i data-lucide="credit-card" style="color: white;"></i>
            </div>
            <h3>Nenhum cartao arquivado</h3>
            <p>você nao possui cartoes arquivados no momento</p>
        </div>
    `,w())}function M(t){const e=y();if(e){if(!t.length){H();return}e.innerHTML=t.map(a=>{const s=x(a.nome_cartao||"Sem nome"),r=String(a.bandeira||"Desconhecida").toLowerCase(),i=h(a.limite_total||0),o=h(a.limite_disponivel||0),c=a.ultimos_digitos||"0000",l=a.conta?.instituicao_financeira?.cor_primaria||a.instituicao_cor||a.cor_cartao||A(r),p={visa:`${u}assets/img/bandeiras/visa.png`,mastercard:`${u}assets/img/bandeiras/mastercard.png`,elo:`${u}assets/img/bandeiras/elo.png`,amex:`${u}assets/img/bandeiras/amex.png`,hipercard:`${u}assets/img/bandeiras/hipercard.png`}[r]||"",n=p?`<img src="${p}" alt="${r}" class="brand-logo">`:'<i data-lucide="credit-card" class="brand-icon-fallback"></i>';return`
            <div class="credit-card surface-card surface-card--interactive surface-card--clip" data-brand="${r}" data-id="${a.id}" style="background: ${l}">
                <div class="card-header">
                    <div class="card-brand">
                        ${n}
                        <span class="card-name">${s}</span>
                    </div>
                    <div class="card-actions">
                        <button class="card-action-btn" onclick="handleRestore(${a.id})" title="Restaurar">
                            <i data-lucide="undo-2"></i>
                        </button>
                        <button class="card-action-btn" onclick="handleHardDelete(${a.id}, '${s.replace(/'/g,"\\'")}')" title="Excluir permanentemente">
                            <i data-lucide="trash-2"></i>
                        </button>
                    </div>
                </div>
                <div class="card-number">**** **** **** ${c}</div>
                <div class="card-footer">
                    <div class="card-holder">
                        <div class="card-label">Limite Disponivel</div>
                        <div class="card-value">R$ ${o}</div>
                    </div>
                    <div class="card-limit">
                        <div class="card-label">Limite Total</div>
                        <div class="card-value">R$ ${i}</div>
                    </div>
                </div>
            </div>
        `}).join(""),w()}}async function v(t,e={}){try{return await S(t,{...e})}catch(a){throw a}}async function g(){const t=y();if(t)try{t.setAttribute("aria-busy","true");const e=await v(`${D()}?archived=1`);m=Array.isArray(e)?e:e?.data||[],O(m),M(m)}catch(e){console.error(e),t.innerHTML=`
            <div class="empty-state">
                <div class="empty-icon"><i data-lucide="triangle-alert"></i></div>
                <h3>Erro ao carregar</h3>
                <p>${x(b(e,"Nao foi possivel carregar os cartoes arquivados."))}</p>
            </div>
        `,w()}finally{t.setAttribute("aria-busy","false")}}window.handleRestore=async function(e){const a=m.find(i=>i.id===e),s=a?a.nome_cartao:"este cartao";if((await window.Swal.fire({title:"Restaurar Cartao",html:`Deseja restaurar o cartao <strong>${s}</strong>?`,icon:"question",showCancelButton:!0,confirmButtonText:'<i data-lucide="undo-2"></i> Sim, restaurar',cancelButtonText:'<i data-lucide="x"></i> Cancelar',confirmButtonColor:"#2ecc71",cancelButtonColor:"#6c757d",reverseButtons:!0})).isConfirmed)try{await v(R(e),{method:"POST"}),C("O cartao foi restaurado com sucesso."),await g()}catch(i){console.error(i),B(b(i,"Falha ao restaurar."))}};window.handleHardDelete=async function(e,a=""){const s=m.find(o=>o.id===e),r=s?s.nome_cartao:a||"este cartao";if((await window.Swal.fire({title:"Excluir permanentemente?",html:`Tem certeza que deseja excluir <strong>${r}</strong>?<br><small class="text-muted" style="color: #dc3545;">Esta acao nao pode ser desfeita!</small>`,icon:"warning",showCancelButton:!0,confirmButtonText:'<i data-lucide="trash-2"></i> Sim, excluir',cancelButtonText:'<i data-lucide="x"></i> Cancelar',confirmButtonColor:"#dc3545",cancelButtonColor:"#6c757d",reverseButtons:!0})).isConfirmed)try{const o=await v($(e),{method:"POST",body:{force:!1}});if(o?.success===!1&&o?.errors?.requires_confirmation){const c=o?.data?.total_lancamentos||0,l=o?.data?.total_faturas||0,f=o?.data?.total_itens||0,p=c+l+f;let n="";if(p>0?(n='<ul style="text-align:left; margin-top: 1rem; margin-bottom: 1rem;">',c>0&&(n+=`<li><b>${c}</b> lancamento(s)</li>`),l>0&&(n+=`<li><b>${l}</b> fatura(s)</li>`),f>0&&(n+=`<li><b>${f}</b> item(ns) de fatura</li>`),n+="</ul>"):n=`<p style="margin: 1rem 0; white-space: pre-line;">${o.message||"Nenhum dado vinculado encontrado"}</p>`,!(await window.Swal.fire({title:"Excluir cartao e TODOS os dados vinculados?",html:`<div style="text-align:left; padding: 1rem;">
                    <p style="margin-bottom: 1rem;">O cartao <b>${r.replace(/</g,"&lt;")}</b> possui os seguintes dados vinculados:</p>
                    ${n}
                    <p style="margin-top: 1rem; color: #dc3545; font-weight: 600;">Ao excluir o cartao, TODOS esses dados serao excluidos permanentemente!</p>
                    <p style="margin-top: 0.5rem;">Esta acao nao pode ser desfeita. Deseja continuar?</p>
                </div>`,icon:"warning",showCancelButton:!0,confirmButtonText:'<i data-lucide="trash-2"></i> Sim, excluir tudo',cancelButtonText:'<i data-lucide="x"></i> Cancelar',confirmButtonColor:"#dc3545",cancelButtonColor:"#6c757d",reverseButtons:!0})).isConfirmed)return;const d=await v($(e),{method:"POST",body:{force:!0}});if(!d?.success)throw new Error(d?.message||"Erro ao excluir");const E=(d.data?.deleted_lancamentos||0)+(d.data?.deleted_faturas||0)+(d.data?.deleted_itens||0);await window.Swal.fire({icon:"success",title:"Excluido!",html:`<p><b>${r}</b> e todos os dados vinculados foram excluidos permanentemente.</p>
                    <p style="margin-top: 0.5rem; font-size: 0.9em; color: #6c757d;">Total de registros excluidos: ${E}</p>`,timer:3e3,showConfirmButton:!1}),await g();return}if(o?.success===!1)throw new Error(o?.message||"Erro ao excluir");C("Cartao excluido com sucesso."),await g()}catch(o){console.error(o),B(b(o,"Falha ao excluir."))}};g();
