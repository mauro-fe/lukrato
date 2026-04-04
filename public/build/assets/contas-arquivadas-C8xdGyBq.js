import{a as b}from"./api-CiEmwEpk.js";const w=(document.querySelector('meta[name="base-url"]')?.content||"").replace(/\/?$/,"/"),c=document.querySelector('meta[name="csrf-token"]')?.content||"",$=t=>`${w}api/${t}`.replace(/\/{2,}/g,"/").replace(":/","://"),T=t=>`${w}index.php/api/${t}`.replace(/\/{2,}/g,"/").replace(":/","://");async function m(t,e={}){const o=(n,a=200)=>({ok:a>=200&&a<300,status:a,headers:new Headers({"content-type":typeof n=="string"?"text/plain":"application/json"}),json:async()=>n,text:async()=>typeof n=="string"?n:JSON.stringify(n??{})});try{const n=await b($(t),{credentials:"same-origin",...e});return o(n,200)}catch(n){if(n?.status!==404)return o(n?.data??{message:n?.message},n?.status||500);try{const a=await b(T(t),{credentials:"same-origin",...e});return o(a,200)}catch(a){return o(a?.data??{message:a?.message},a?.status||500)}}}const l=document.getElementById("archivedGrid"),E=document.getElementById("totalArquivadas"),k=document.getElementById("saldoArquivado");async function u(t){try{return await t.json()}catch{return null}}function x(t){try{return Number(t).toLocaleString("pt-BR",{minimumFractionDigits:2,maximumFractionDigits:2})}catch{return(Math.round((+t||0)*100)/100).toFixed(2).replace(".",",")}}function d(t=""){return String(t).replace(/[&<>"']/g,e=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"})[e])}let f=[];function h(t){const e=t?.length||0,o=(t||[]).reduce((n,a)=>{const r=typeof a.saldoAtual=="number"?a.saldoAtual:a.saldoInicial||0;return n+r},0);E.textContent=e,k.textContent=`R$ ${x(o)}`}function B(t){if(l.innerHTML="",!t||!t.length){l.innerHTML=`
            <div class="empty-state" style="grid-column: 1 / -1;">
                <div class="empty-icon">
                    <i data-lucide="archive"></i>
                </div>
                <h3>Nenhuma conta arquivada</h3>
                <p>Quando você arquivar uma conta, ela aparecerá aqui</p>
            </div>
        `,h([]);return}h(t);for(const e of t){const o=typeof e.saldoAtual=="number"?e.saldoAtual:e.saldoInicial??0,n=o>=0?"positive":"negative",a=e.instituicao_financeira||{},r=a.nome||e.instituicao||"Sem instituição",s=a.logo_url||`${w}assets/img/banks/default.svg`,g=a.cor_primaria||"#95a5a6",i=document.createElement("div");i.setAttribute("data-aos","flip-left"),i.className="account-card archived-card",i.innerHTML=`
            <div class="account-header" style="background: ${g};">
                <div class="account-logo">
                    <img src="${s}" alt="${d(e.nome||"")}" />
                </div>
            </div>
            <div class="account-body" style="position: relative;">
                <span class="acc-badge inactive" style="position: absolute; top: 1rem; right: 1rem; background: rgba(0,0,0,0.6); color: white; border: 1px solid rgba(255,255,255,0.2); z-index: 10;">
                    <i data-lucide="archive"></i>
                    Arquivada
                </span>
                <h3 class="account-name">${d(e.nome||"")}</h3>
                <div class="account-institution">${d(r)}</div>
                <div class="account-balance ${n}">
                    R$ ${x(o)}
                </div>
                <div class="acc-actions">
                    <button class="btn-action btn-restore" data-id="${e.id}" title="Restaurar conta">
                        <i data-lucide="undo-2"></i>
                        <span>Restaurar</span>
                    </button>
                    <button class="btn-action btn-delete" data-id="${e.id}" title="Excluir permanentemente">
                        <i data-lucide="trash-2"></i>
                        <span>Excluir</span>
                    </button>
                </div>
            </div>
        `,l.appendChild(i)}}l?.addEventListener("click",t=>{const e=t.target.closest(".btn-restore"),o=t.target.closest(".btn-delete");e&&A(Number(e.dataset.id)),o&&q(Number(o.dataset.id))});async function A(t){const e=f.find(a=>a.id===t),o=e?e.nome:"esta conta";if((await Swal.fire({title:"Restaurar conta?",html:`Deseja realmente restaurar <strong>${o}</strong>?<br><small class="text-muted">A conta voltará a aparecer na lista ativa.</small>`,icon:"question",showCancelButton:!0,confirmButtonColor:"#e67e22",cancelButtonColor:"#6c757d",confirmButtonText:'<i data-lucide="undo-2"></i> Sim, restaurar',cancelButtonText:'<i data-lucide="x"></i> Cancelar',reverseButtons:!0,buttonsStyling:!0})).isConfirmed)try{if(!(await m(`contas/${t}/restore`,{method:"POST",credentials:"same-origin",headers:c?{"X-CSRF-TOKEN":c}:{}})).ok)throw new Error("Falha ao restaurar");Swal.fire({title:"Restaurada!",text:"A conta foi restaurada com sucesso.",icon:"success",timer:2e3,showConfirmButton:!1}),await p()}catch(a){console.error(a),Swal.fire({title:"Erro!",text:a.message||"Falha ao restaurar.",icon:"error",confirmButtonColor:"#e67e22"})}}async function q(t,e=""){const o=f.find(r=>r.id===t),n=o?o.nome:e||"esta conta";if((await Swal.fire({title:"Excluir permanentemente?",html:`Tem certeza que deseja excluir <strong>${n}</strong>?<br><small class="text-muted" style="color: #dc3545;">Esta ação não pode ser desfeita!</small>`,icon:"warning",showCancelButton:!0,confirmButtonText:'<i data-lucide="trash-2"></i> Sim, excluir',cancelButtonText:'<i data-lucide="x"></i> Cancelar',confirmButtonColor:"#dc3545",cancelButtonColor:"#6c757d",reverseButtons:!0,buttonsStyling:!0})).isConfirmed)try{const r=await m(`contas/${t}/delete`,{method:"POST",credentials:"same-origin",headers:{"Content-Type":"application/json",...c?{"X-CSRF-TOKEN":c}:{}},body:JSON.stringify({force:!1})});if(r.status===422){const s=await u(r);if(s&&!s.success&&s.errors?.requires_confirmation){const i=s?.data?.counts?.origem??0,y=s?.data?.counts?.destino??0,S=s?.data?.counts?.total??i+y;if(!(await Swal.fire({title:"Excluir conta e TODOS os lançamentos?",html:`
                        <div style="text-align:left">
                            <p>A conta <b>${d(n)}</b> possui lançamentos vinculados.</p>
                            <ul style="margin:6px 0 0 18px">
                                <li>Como origem: <b>${i}</b></li>
                                <li>Como destino: <b>${y}</b></li>
                                <li>Total: <b>${S}</b></li>
                            </ul>
                            <p style="margin-top:10px">Deseja continuar e excluir <b>TUDO</b>?</p>
                        </div>`,icon:"warning",showCancelButton:!0,confirmButtonText:"Excluir tudo",cancelButtonText:"Manter arquivada",reverseButtons:!0})).isConfirmed){await Swal.fire({icon:"info",title:"Mantida",text:"A conta continuará arquivada."});return}const v=await m(`contas/${t}/delete?force=1`,{method:"POST",credentials:"same-origin",headers:{"Content-Type":"application/json",...c?{"X-CSRF-TOKEN":c}:{}},body:JSON.stringify({force:!0})});if(!v.ok){const C=await u(v);throw new Error(C?.message||`HTTP ${v.status}`)}await Swal.fire({icon:"success",title:"Excluída",text:"Conta e lançamentos removidos."}),await p();return}const g=await u(r);throw new Error(g?.message||"Não foi possível excluir.")}if(!r.ok){const s=await u(r);throw new Error(s?.message||`HTTP ${r.status}`)}await Swal.fire({icon:"success",title:"Excluída",text:"Conta removida com sucesso."}),await p()}catch(r){console.error(r),Swal.fire("Erro",r.message||"Falha ao excluir conta.","error")}}async function p(){try{l.innerHTML=`
            <div class="lk-skeleton lk-skeleton--card"></div>
            <div class="lk-skeleton lk-skeleton--card"></div>
            <div class="lk-skeleton lk-skeleton--card"></div>`;const t=new Date().toISOString().slice(0,7),e=await m(`contas?archived=1&with_balances=1&month=${t}`),o=e.headers.get("content-type")||"";if(!e.ok){let a=`HTTP ${e.status}`;throw o.includes("application/json")?a=(await e.json().catch(()=>({})))?.message||a:a=(await e.text()).slice(0,200),new Error(a)}if(!o.includes("application/json")){const a=await e.text();throw new Error("Resposta não é JSON. Prévia: "+a.slice(0,120))}const n=await e.json();f=Array.isArray(n)?n:n.data||[],B(f)}catch(t){console.error(t),l.innerHTML='<div class="lk-empty">Erro ao carregar.</div>',h([]),Swal.fire("Erro",t.message||"Não foi possível carregar as contas arquivadas.","error")}}p();
