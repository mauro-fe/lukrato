import{b as S,g as T,j as E}from"./api-Bz3e_1Ao.js";import{A as k,B as b,r as B}from"./finance-CgaDv1sH.js";function v(){const t=T();return t?{"X-CSRF-TOKEN":t}:{}}async function d(t,e={}){const n=(o,a=200)=>({ok:a>=200&&a<300,status:a,headers:new Headers({"content-type":typeof o=="string"?"text/plain":"application/json"}),json:async()=>o,text:async()=>typeof o=="string"?o:JSON.stringify(o??{})});try{const o=await S(t,{...e});return n(o,200)}catch(o){return n(o?.data??{message:o?.message},o?.status||500)}}const c=document.getElementById("archivedGrid"),$=document.getElementById("totalArquivadas"),A=document.getElementById("saldoArquivado");async function l(t){try{return await t.json()}catch{return null}}function y(t){try{return Number(t).toLocaleString("pt-BR",{minimumFractionDigits:2,maximumFractionDigits:2})}catch{return(Math.round((+t||0)*100)/100).toFixed(2).replace(".",",")}}function u(t=""){return String(t).replace(/[&<>"']/g,e=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"})[e])}let m=[];function h(t){const e=t?.length||0,n=(t||[]).reduce((o,a)=>{const r=typeof a.saldoAtual=="number"?a.saldoAtual:a.saldoInicial||0;return o+r},0);$.textContent=e,A.textContent=`R$ ${y(n)}`}function j(t){if(c.innerHTML="",!t||!t.length){c.innerHTML=`
            <div class="empty-state" style="grid-column: 1 / -1;">
                <div class="empty-icon">
                    <i data-lucide="archive"></i>
                </div>
                <h3>Nenhuma conta arquivada</h3>
                <p>Quando você arquivar uma conta, ela aparecerá aqui</p>
            </div>
        `,h([]);return}h(t);for(const e of t){const n=typeof e.saldoAtual=="number"?e.saldoAtual:e.saldoInicial??0,o=n>=0?"positive":"negative",a=e.instituicao_financeira||{},r=a.nome||e.instituicao||"Sem instituição",s=a.logo_url||E("img/banks/default.svg"),p=a.cor_primaria||"#95a5a6",i=document.createElement("div");i.setAttribute("data-aos","flip-left"),i.className="account-card archived-card",i.innerHTML=`
            <div class="account-header" style="background: ${p};">
                <div class="account-logo">
                    <img src="${s}" alt="${u(e.nome||"")}" />
                </div>
            </div>
            <div class="account-body" style="position: relative;">
                <span class="acc-badge inactive" style="position: absolute; top: 1rem; right: 1rem; background: rgba(0,0,0,0.6); color: white; border: 1px solid rgba(255,255,255,0.2); z-index: 10;">
                    <i data-lucide="archive"></i>
                    Arquivada
                </span>
                <h3 class="account-name">${u(e.nome||"")}</h3>
                <div class="account-institution">${u(r)}</div>
                <div class="account-balance ${o}">
                    R$ ${y(n)}
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
        `,c.appendChild(i)}}c?.addEventListener("click",t=>{const e=t.target.closest(".btn-restore"),n=t.target.closest(".btn-delete");e&&q(Number(e.dataset.id)),n&&R(Number(n.dataset.id))});async function q(t){const e=m.find(a=>a.id===t),n=e?e.nome:"esta conta";if((await Swal.fire({title:"Restaurar conta?",html:`Deseja realmente restaurar <strong>${n}</strong>?<br><small class="text-muted">A conta voltará a aparecer na lista ativa.</small>`,icon:"question",showCancelButton:!0,confirmButtonColor:"#e67e22",cancelButtonColor:"#6c757d",confirmButtonText:'<i data-lucide="undo-2"></i> Sim, restaurar',cancelButtonText:'<i data-lucide="x"></i> Cancelar',reverseButtons:!0,buttonsStyling:!0})).isConfirmed)try{if(!(await d(k(t),{method:"POST",headers:v()})).ok)throw new Error("Falha ao restaurar");Swal.fire({title:"Restaurada!",text:"A conta foi restaurada com sucesso.",icon:"success",timer:2e3,showConfirmButton:!1}),await f()}catch(a){console.error(a),Swal.fire({title:"Erro!",text:a.message||"Falha ao restaurar.",icon:"error",confirmButtonColor:"#e67e22"})}}async function R(t,e=""){const n=m.find(r=>r.id===t),o=n?n.nome:e||"esta conta";if((await Swal.fire({title:"Excluir permanentemente?",html:`Tem certeza que deseja excluir <strong>${o}</strong>?<br><small class="text-muted" style="color: #dc3545;">Esta ação não pode ser desfeita!</small>`,icon:"warning",showCancelButton:!0,confirmButtonText:'<i data-lucide="trash-2"></i> Sim, excluir',cancelButtonText:'<i data-lucide="x"></i> Cancelar',confirmButtonColor:"#dc3545",cancelButtonColor:"#6c757d",reverseButtons:!0,buttonsStyling:!0})).isConfirmed)try{const r=await d(b(t),{method:"POST",headers:{"Content-Type":"application/json",...v()},body:JSON.stringify({force:!1})});if(r.status===422){const s=await l(r);if(s&&!s.success&&s.errors?.requires_confirmation){const i=s?.data?.counts?.origem??0,w=s?.data?.counts?.destino??0,x=s?.data?.counts?.total??i+w;if(!(await Swal.fire({title:"Excluir conta e TODOS os lançamentos?",html:`
                        <div style="text-align:left">
                            <p>A conta <b>${u(o)}</b> possui lançamentos vinculados.</p>
                            <ul style="margin:6px 0 0 18px">
                                <li>Como origem: <b>${i}</b></li>
                                <li>Como destino: <b>${w}</b></li>
                                <li>Total: <b>${x}</b></li>
                            </ul>
                            <p style="margin-top:10px">Deseja continuar e excluir <b>TUDO</b>?</p>
                        </div>`,icon:"warning",showCancelButton:!0,confirmButtonText:"Excluir tudo",cancelButtonText:"Manter arquivada",reverseButtons:!0})).isConfirmed){await Swal.fire({icon:"info",title:"Mantida",text:"A conta continuará arquivada."});return}const g=await d(`${b(t)}?force=1`,{method:"POST",headers:{"Content-Type":"application/json",...v()},body:JSON.stringify({force:!0})});if(!g.ok){const C=await l(g);throw new Error(C?.message||`HTTP ${g.status}`)}await Swal.fire({icon:"success",title:"Excluída",text:"Conta e lançamentos removidos."}),await f();return}const p=await l(r);throw new Error(p?.message||"Não foi possível excluir.")}if(!r.ok){const s=await l(r);throw new Error(s?.message||`HTTP ${r.status}`)}await Swal.fire({icon:"success",title:"Excluída",text:"Conta removida com sucesso."}),await f()}catch(r){console.error(r),Swal.fire("Erro",r.message||"Falha ao excluir conta.","error")}}async function f(){try{c.innerHTML=`
            <div class="lk-skeleton lk-skeleton--card"></div>
            <div class="lk-skeleton lk-skeleton--card"></div>
            <div class="lk-skeleton lk-skeleton--card"></div>`;const t=new Date().toISOString().slice(0,7),e=await d(`${B()}?archived=1&with_balances=1&month=${t}`),n=e.headers.get("content-type")||"";if(!e.ok){let a=`HTTP ${e.status}`;throw n.includes("application/json")?a=(await e.json().catch(()=>({})))?.message||a:a=(await e.text()).slice(0,200),new Error(a)}if(!n.includes("application/json")){const a=await e.text();throw new Error("Resposta não é JSON. Prévia: "+a.slice(0,120))}const o=await e.json();m=Array.isArray(o)?o:o.data||[],j(m)}catch(t){console.error(t),c.innerHTML='<div class="lk-empty">Erro ao carregar.</div>',h([]),Swal.fire("Erro",t.message||"Não foi possível carregar as contas arquivadas.","error")}}f();
