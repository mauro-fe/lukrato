const v={WELCOME:"welcome",GOAL:"goal",ACCOUNT:"account",TRANSACTION:"transaction",SUCCESS:"success"},I=[v.WELCOME,v.GOAL,v.ACCOUNT,v.TRANSACTION,v.SUCCESS],Z={[v.WELCOME]:{title:"Inicio",progress:25,skippable:!1,icon:"sparkles"},[v.GOAL]:{title:"Objetivo",progress:50,skippable:!1,icon:"target"},[v.ACCOUNT]:{title:"Conta",progress:75,skippable:!1,icon:"wallet"},[v.TRANSACTION]:{title:"Registro",progress:100,skippable:!0,icon:"receipt"},[v.SUCCESS]:{title:"Pronto",progress:100,skippable:!1,icon:"party-popper"}},xe=[{id:"control",icon:"pie-chart",emoji:"📊",title:"Controlar gastos",description:"Saber para onde meu dinheiro vai todo mes",color:"#3b82f6"},{id:"save",icon:"piggy-bank",emoji:"🐷",title:"Economizar dinheiro",description:"Guardar mais e gastar melhor",color:"#10b981"},{id:"debt",icon:"trending-down",emoji:"📉",title:"Sair das dividas",description:"Organizar e eliminar minhas pendencias",color:"#ef4444"},{id:"organize",icon:"calendar",emoji:"📅",title:"Me organizar",description:"Nao perder contas e prazos",color:"#8b5cf6"}],Ae={control:{welcome:"Vamos descobrir para onde seu dinheiro esta indo.",success:"Agora voce ja sabe para onde seu dinheiro esta indo."},save:{welcome:"Vamos criar o habito de guardar mais e gastar melhor.",success:"Agora voce deu o primeiro passo para economizar com clareza."},debt:{welcome:"Vamos organizar suas pendencias e criar um plano simples.",success:"Agora voce comecou a organizar o que precisa sair do caminho."},organize:{welcome:"Vamos deixar sua rotina financeira mais previsivel.",success:"Agora sua vida financeira comecou a ficar no lugar."}},Be=60,B={ONBOARDING_STATE:"lk_onboarding_v2_state",ONBOARDING_COMPLETED:"lk_onboarding_v2_completed",USER_GOAL:"lk_user_goal"},ce=window.__LK_CONFIG__||window.__LK_CONFIG||{},re=ce.baseUrl||"/",ee=window.__LK_ONBOARDING_CONFIG__||{};function be(){return document.querySelector('meta[name="csrf-token"]')?.content||ce.csrfToken||""}function he(e=null){if(typeof e=="string"&&e.trim()!==""){const t=e.includes("?")?"&":"?";return`${e}${t}first_visit=1`}return`${re}dashboard?first_visit=1`}function Re(e){if(Array.isArray(e))return e.find(t=>typeof t=="string"&&t.trim()!=="")||null;if(e&&typeof e=="object"){for(const t of Object.values(e))if(typeof t=="string"&&t.trim()!=="")return t}return null}function ye(e,t){return e.status===401?"Sua sessao expirou. Faca login novamente.":e.status===403?"Voce nao tem permissao para continuar.":e.status===404?"Nao foi possivel acessar o endpoint do onboarding.":e.status===419?"Seu token expirou. Recarregue a pagina e tente novamente.":e.status>=500?"O servidor encontrou um erro inesperado. Tente novamente.":t}function Fe(e){if(!e)return null;const t=e.replace(/<style[\s\S]*?<\/style>/gi," ").replace(/<script[\s\S]*?<\/script>/gi," ").replace(/<[^>]+>/g," ").replace(/\s+/g," ").trim();return t!==""?t:null}function Ce(e,t){if(e&&typeof e.message=="string"&&e.message.trim()!=="")return e.message.trim();const a=Re(e?.errors);return a||t}async function me(e,t){const o=(await e.text()).trim(),l=(e.headers.get("content-type")||"").includes("application/json")||o.startsWith("{")||o.startsWith("[");if(o===""){if(!e.ok)throw new Error(ye(e,t));return{success:!0,data:null,message:""}}if(l)try{return JSON.parse(o)}catch{throw console.warn("[Onboarding] Invalid JSON response:",o.slice(0,240)),new Error("O servidor retornou uma resposta invalida. Recarregue a pagina e tente novamente.")}const p=Fe(o);throw new Error(p||ye(e,t))}function Ge(){if(typeof ee.goal=="string"&&ee.goal.trim()!=="")return ee.goal.trim();try{const e=localStorage.getItem(B.USER_GOAL);return e&&e.trim()!==""?e.trim():null}catch{return null}}function Ne(){const e=ee.initialStep;return I.includes(e)?e:v.WELCOME}function Y(){const e=Ne(),t=I.indexOf(e);return{currentStep:e,stepIndex:t>=0?t:0,completed:!1,data:{goal:Ge(),account:ee.conta||null,transaction:null},loading:!1,error:null,userName:ce.userName||"",userId:ce.userId||null}}function Me(){try{const e=localStorage.getItem(B.ONBOARDING_STATE);if(!e)return Y();const t=JSON.parse(e);if(t.currentStep===v.SUCCESS&&t.completed)return Y();const a=I.includes(t.currentStep)?t.currentStep:Ne(),o=Y();return{...o,...t,currentStep:a,stepIndex:I.indexOf(a),data:{...o.data,...t.data||{}}}}catch(e){return console.warn("[Onboarding] Failed to restore state:",e),Y()}}class De{constructor(){this.state=Me(),this.listeners=new Set}getState(){return this.state}setState(t){const a=typeof t=="function"?t(this.state):{...this.state,...t};this.state=a,this.persist(),this.notify()}subscribe(t){return this.listeners.add(t),()=>this.listeners.delete(t)}notify(){this.listeners.forEach(t=>t(this.state))}persist(){try{localStorage.setItem(B.ONBOARDING_STATE,JSON.stringify(this.state))}catch(t){console.warn("[Onboarding] Failed to persist state:",t)}}nextStep(){const t=I.indexOf(this.state.currentStep);if(t<I.length-1){const a=I[t+1];this.setState({currentStep:a,stepIndex:t+1,error:null})}}prevStep(){const t=I.indexOf(this.state.currentStep);if(t>0){const a=I[t-1];this.setState({currentStep:a,stepIndex:t-1,error:null})}}goToStep(t){const a=I.indexOf(t);a!==-1&&this.setState({currentStep:t,stepIndex:a,error:null})}setGoal(t){this.setState(a=>({...a,data:{...a.data,goal:t}}));try{localStorage.setItem(B.USER_GOAL,t)}catch(a){console.warn("[Onboarding] Failed to persist goal:",a)}}async saveAccount(t){this.setState({loading:!0,error:null});try{const a=await fetch(`${re}api/onboarding/conta/json`,{method:"POST",headers:{Accept:"application/json","Content-Type":"application/json","X-Requested-With":"XMLHttpRequest","X-CSRF-TOKEN":be()},body:JSON.stringify(t)}),o=await me(a,"Erro ao criar conta");if(!a.ok||!o.success)throw new Error(Ce(o,"Erro ao criar conta"));const n={...o.data||{},nome:o?.data?.nome??t.nome,saldo:o?.data?.saldo??t.saldo_inicial??0,instituicao_financeira_id:o?.data?.instituicao_financeira_id??t.instituicao_financeira_id??null,instituicao:o?.data?.instituicao??t.instituicao??null};return this.setState(l=>({...l,loading:!1,data:{...l.data,account:n}})),n}catch(a){throw this.setState({loading:!1,error:a.message}),a}}async saveTransaction(t){this.setState({loading:!0,error:null});try{const a=await fetch(`${re}api/onboarding/lancamento/json`,{method:"POST",headers:{Accept:"application/json","Content-Type":"application/json","X-Requested-With":"XMLHttpRequest","X-CSRF-TOKEN":be()},body:JSON.stringify(t)}),o=await me(a,"Erro ao criar lancamento");if(!a.ok||!o.success)throw new Error(Ce(o,"Erro ao criar lancamento"));const n={...o.data||{},tipo:o?.data?.tipo??t.tipo,valor:o?.data?.valor??t.valor,descricao:o?.data?.descricao??t.descricao,categoria_id:o?.data?.categoria_id??t.categoria_id??null,conta_id:o?.data?.conta_id??t.conta_id??null},l=Math.abs(Number(n.valor)||0),p=n.tipo==="receita"?l:-l;return this.setState(r=>({...r,loading:!1,data:{...r.data,transaction:n,account:r.data.account?{...r.data.account,saldo:(Number(r.data.account.saldo)||0)+p}:r.data.account}})),n}catch(a){throw this.setState({loading:!1,error:a.message}),a}}async skipTransaction(){this.setState({loading:!1,error:null}),this.nextStep()}async completeOnboarding(){this.setState({loading:!0});try{const t=await fetch(`${re}api/onboarding/complete`,{method:"POST",headers:{Accept:"application/json","Content-Type":"application/json","X-Requested-With":"XMLHttpRequest","X-CSRF-TOKEN":be()},body:JSON.stringify({goal:this.state.data.goal})}),a=await me(t,"Erro ao concluir onboarding");try{localStorage.setItem(B.ONBOARDING_COMPLETED,"true"),localStorage.removeItem(B.ONBOARDING_STATE)}catch(o){console.warn("[Onboarding] Failed to update completion state:",o)}this.setState({completed:!0,loading:!1}),setTimeout(()=>{window.location.href=he(a?.data?.redirect??null)},2500)}catch{setTimeout(()=>{window.location.href=he()},2e3)}}reset(){try{localStorage.removeItem(B.ONBOARDING_STATE),localStorage.removeItem(B.ONBOARDING_COMPLETED),localStorage.removeItem(B.USER_GOAL)}catch(t){console.warn("[Onboarding] Failed to reset persisted state:",t)}this.state=Y(),this.notify()}getProgress(){return Z[this.state.currentStep]?.progress||0}canGoBack(){return this.state.stepIndex>0&&this.state.currentStep!==v.SUCCESS}canSkip(){return Z[this.state.currentStep]?.skippable||!1}}const h=new De;function ae(){return{state:h.getState(),nextStep:()=>h.nextStep(),prevStep:()=>h.prevStep(),goToStep:e=>h.goToStep(e),setGoal:e=>h.setGoal(e),saveAccount:e=>h.saveAccount(e),saveTransaction:e=>h.saveTransaction(e),skipTransaction:()=>h.skipTransaction(),completeOnboarding:()=>h.completeOnboarding(),getProgress:()=>h.getProgress(),canGoBack:()=>h.canGoBack(),canSkip:()=>h.canSkip(),subscribe:e=>h.subscribe(e)}}typeof window<"u"&&(window.__LK_ONBOARDING_STORE__=h);const le=I.slice(0,-1);function je(e){const t=le.indexOf(e),a=t>=0?t:0,o=a+1,n=le.length;return{currentIndex:a,currentNumber:o,total:n,progress:Math.round(o/n*100)}}function Pe(e){const{currentIndex:t,currentNumber:a,total:o,progress:n}=je(e),l=Z[e]||Z[le[t]];return`
        <div class="lk-ob2-progress-bar">
            <div class="lk-ob2-progress-meta">
                <span class="lk-ob2-progress-kicker">Passo ${a} de ${o}</span>
                <strong class="lk-ob2-progress-current">${l?.title||"Inicio"}</strong>
            </div>
            <div class="lk-ob2-progress-track">
                <div class="lk-ob2-progress-fill" style="width: ${n}%"></div>
            </div>
            <div class="lk-ob2-progress-steps">
                ${le.map((p,r)=>{const g=Z[p],y=r===t,f=r<t;return`
                        <div class="lk-ob2-progress-step ${y?"active":""} ${f?"done":""}">
                            <div class="lk-ob2-progress-dot">
                                ${f?'<i data-lucide="check"></i>':r+1}
                            </div>
                            <span class="lk-ob2-progress-label">${g.title}</span>
                        </div>
                    `}).join("")}
            </div>
        </div>
    `}const we=window.__LK_CONFIG__||window.__LK_CONFIG||{};function Ve(e){return String(e||"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#39;")}function ze(e){const{state:t,nextStep:a}=ae(),o=t.userName?t.userName.split(" ")[0]:"";e.innerHTML=`
        <div class="lk-ob2-step lk-ob2-welcome" data-step="welcome">
            <div class="lk-ob2-bg-particles"></div>

            <div class="lk-ob2-content">
                <div class="lk-ob2-logo-wrapper">
                    <div class="lk-ob2-logo-glow"></div>
                    <img src="${we.logoUrl||`${we.baseUrl||"/"}assets/img/icone.png`}"
                         alt="Lukrato" class="lk-ob2-logo">
                </div>

                <div class="lk-ob2-greeting">
                    ${o?`
                        <span class="lk-ob2-hello">Ola, ${Ve(o)}!</span>
                    `:""}
                    <h1 class="lk-ob2-title">
                        Bem-vindo ao <span class="lk-highlight">Lukrato</span>
                    </h1>
                    <p class="lk-ob2-subtitle">
                        Voce vai entender seu dinheiro em menos de ${Math.ceil(Be/60)} minuto
                    </p>
                </div>

                <div class="lk-ob2-value-props">
                    <div class="lk-ob2-value-item">
                        <div class="lk-ob2-value-icon">
                            <i data-lucide="eye"></i>
                        </div>
                        <span>Descubra para onde seu dinheiro esta indo</span>
                    </div>
                    <div class="lk-ob2-value-item">
                        <div class="lk-ob2-value-icon">
                            <i data-lucide="wallet"></i>
                        </div>
                        <span>Comece com sua conta principal e ajuste o resto depois</span>
                    </div>
                    <div class="lk-ob2-value-item">
                        <div class="lk-ob2-value-icon">
                            <i data-lucide="receipt"></i>
                        </div>
                        <span>Veja valor rapido assim que salvar o primeiro registro</span>
                    </div>
                </div>

                <button class="lk-ob2-btn-primary" id="btnStartOnboarding" type="button">
                    <span>Comecar agora</span>
                    <i data-lucide="arrow-right"></i>
                </button>

                <div class="lk-ob2-time-hint">
                    <i data-lucide="clock"></i>
                    <span>Leva menos de 1 minuto</span>
                </div>
            </div>
        </div>
    `,window.lucide&&lucide.createIcons();const n=e.querySelector("#btnStartOnboarding");n&&n.addEventListener("click",()=>{n.classList.add("loading"),setTimeout(a,300)}),requestAnimationFrame(()=>{e.querySelector(".lk-ob2-step")?.classList.add("visible")})}function Ue(e){const{state:t,setGoal:a,nextStep:o,prevStep:n}=ae();e.innerHTML=`
        <div class="lk-ob2-step lk-ob2-goal" data-step="goal">
            <div class="lk-ob2-content">
                <div class="lk-ob2-header">
                    <div class="lk-ob2-icon-box">
                        <i data-lucide="target"></i>
                    </div>
                    <h1 class="lk-ob2-title">Qual vai ser seu foco agora?</h1>
                    <p class="lk-ob2-subtitle">
                        Escolha o que mais importa hoje. O restante voce ajusta depois.
                    </p>
                </div>

                <div class="lk-ob2-goals-grid">
                    ${xe.map(f=>`
                        <button type="button"
                                class="lk-ob2-goal-card ${t.data.goal===f.id?"selected":""}"
                                data-goal-id="${f.id}"
                                style="--goal-color: ${f.color}">
                            <div class="lk-ob2-goal-emoji">${f.emoji}</div>
                            <div class="lk-ob2-goal-text">
                                <span class="lk-ob2-goal-title">${f.title}</span>
                                <span class="lk-ob2-goal-desc">${f.description}</span>
                            </div>
                            <div class="lk-ob2-goal-check">
                                <i data-lucide="check"></i>
                            </div>
                        </button>
                    `).join("")}
                </div>

                <div class="lk-ob2-actions">
                    <button type="button" class="lk-ob2-btn-back" id="btnGoalBack">
                        <i data-lucide="arrow-left"></i>
                        <span>Voltar</span>
                    </button>
                    <button type="button" class="lk-ob2-btn-primary" id="btnGoalNext" disabled>
                        <span>Continuar</span>
                        <i data-lucide="arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
    `,window.lucide&&lucide.createIcons();let l=t.data.goal;const p=e.querySelectorAll(".lk-ob2-goal-card"),r=e.querySelector("#btnGoalNext"),g=e.querySelector("#btnGoalBack");function y(){r&&(r.disabled=!l)}p.forEach(f=>{f.addEventListener("click",()=>{const P=f.dataset.goalId;p.forEach(F=>F.classList.remove("selected")),f.classList.add("selected"),l=P,a(P),y(),navigator.vibrate&&navigator.vibrate(10),setTimeout(()=>{r&&!r.disabled&&r.click()},350)})}),g&&g.addEventListener("click",n),r&&r.addEventListener("click",()=>{l&&(r.classList.add("loading"),setTimeout(o,200))}),y(),requestAnimationFrame(()=>{e.querySelector(".lk-ob2-step")?.classList.add("visible")})}function Ee(e){return(parseFloat(e)||0).toLocaleString("pt-BR",{minimumFractionDigits:2,maximumFractionDigits:2})}function He(e){return e&&parseFloat(String(e).replace(/\./g,"").replace(",","."))||0}function q(e){return String(e||"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#39;")}function j(e){return String(e||"").normalize("NFD").replace(/[\u0300-\u036f]/g,"").toLowerCase().trim()}function Ke(e=[]){return Array.isArray(e)?e.filter(t=>t&&t.id&&t.nome).map(t=>({id:t.id,nome:String(t.nome).trim(),searchKey:j(t.nome)})).sort((t,a)=>t.nome.localeCompare(a.nome,"pt-BR")):[]}function Te(e,t){const a=j(t);return a===""?null:e.find(o=>o.searchKey===a)||null}function We(e,t=[]){const{state:a,saveAccount:o,nextStep:n,prevStep:l}=ae(),p=a.data.account||{},r=a.data.goal?Ae[a.data.goal]?.welcome:null,g=Ke(t),y=p?.instituicao_financeira_id?g.find(s=>String(s.id)===String(p.instituicao_financeira_id))||null:g.find(s=>j(s.nome)===j(p?.instituicao||""))||null,f=p?.nome||y?.nome||p?.instituicao||"",P=y?.nome||p?.instituicao||"",F=Number.isFinite(Number(p?.saldo))?Ee(Number(p.saldo)):"0,00";e.innerHTML=`
        <div class="lk-ob2-step lk-ob2-account" data-step="account">
            <div class="lk-ob2-content">
                <div class="lk-ob2-header">
                    <div class="lk-ob2-icon-box">
                        <i data-lucide="wallet"></i>
                    </div>
                    <h1 class="lk-ob2-title">Como voce chama sua conta principal?</h1>
                    <p class="lk-ob2-subtitle">
                        Use um nome simples como Nubank, Carteira ou Banco. Os detalhes ficam para depois.
                    </p>
                </div>

                <div class="lk-ob2-account-intro">
                    <div class="lk-ob2-account-intro-copy">
                        <strong>Comece simples</strong>
                        <span>${q(r||"O importante agora e ver seu dinheiro aparecer no painel o mais rapido possivel.")}</span>
                    </div>
                    <span class="lk-ob2-account-intro-badge">Leva segundos</span>
                </div>

                <form class="lk-ob2-form" id="accountForm">
                    <div class="lk-ob2-form-group">
                        <label class="lk-ob2-label" for="accountName">
                            <i data-lucide="pen-line"></i>
                            Nome da conta
                        </label>
                        <input
                            type="text"
                            class="lk-ob2-input"
                            id="accountName"
                            name="nome"
                            placeholder="Ex: Nubank, Carteira, Banco"
                            value="${q(f)}"
                            required
                            autocomplete="off"
                            maxlength="50">
                        <div class="lk-ob2-account-caption">
                            Esse nome vai aparecer no seu dashboard inicial.
                        </div>
                    </div>

                    <div class="lk-ob2-form-group">
                        <label class="lk-ob2-label" for="accountInstitutionSearch">
                            <i data-lucide="building-2"></i>
                            Instituicao
                            <span class="lk-ob2-label-hint">(opcional)</span>
                        </label>

                        <div class="lk-ob2-institution-toolbar">
                            <div class="lk-ob2-search-field">
                                <i data-lucide="search"></i>
                                <input
                                    type="search"
                                    class="lk-ob2-input lk-ob2-search-input"
                                    id="accountInstitutionSearch"
                                    placeholder="Busque sua instituicao"
                                    value="${q(P)}"
                                    autocomplete="off">
                            </div>
                            <button type="button" class="lk-ob2-btn-ghost" id="btnInstitutionClear">
                                <i data-lucide="eraser"></i>
                                <span>Depois</span>
                            </button>
                        </div>

                        <div class="lk-ob2-combobox-results" id="institutionResults" hidden></div>
                        <div class="lk-ob2-combobox-empty" id="institutionEmpty" hidden>
                            Nenhuma instituicao encontrada. Voce pode seguir sem escolher agora.
                        </div>

                        <div class="lk-ob2-account-caption">
                            Buscar aqui e opcional. Se selecionar uma instituicao, usamos como sugestao de nome.
                        </div>
                    </div>

                    <div class="lk-ob2-form-group">
                        <label class="lk-ob2-label" for="accountBalance">
                            <i data-lucide="coins"></i>
                            Saldo atual
                            <span class="lk-ob2-label-hint">(pode ser aproximado)</span>
                        </label>
                        <div class="lk-ob2-money-input">
                            <span class="lk-ob2-currency">R$</span>
                            <input
                                type="text"
                                class="lk-ob2-input lk-ob2-input-money"
                                id="accountBalance"
                                name="saldo_inicial"
                                value="${q(F)}"
                                inputmode="decimal"
                                autocomplete="off">
                        </div>
                    </div>

                    <div class="lk-ob2-account-preview" id="accountPreview">
                        <div class="lk-ob2-account-preview-main">
                            <div class="lk-ob2-account-preview-icon">
                                <i data-lucide="wallet"></i>
                            </div>
                            <div class="lk-ob2-account-preview-copy">
                                <span class="lk-ob2-account-preview-label">Vai aparecer assim no seu painel</span>
                                <strong id="accountPreviewName">${q(f||"Sua conta principal")}</strong>
                                <span class="lk-ob2-account-preview-label" id="accountPreviewInstitution">
                                    ${y?`Instituicao: ${q(y.nome)}`:"Instituicao opcional"}
                                </span>
                            </div>
                        </div>
                        <strong class="lk-ob2-account-preview-balance" id="accountPreviewBalance">
                            R$ ${q(F)}
                        </strong>
                    </div>

                    <div class="lk-ob2-error" id="accountError" style="display: none;">
                        <i data-lucide="alert-circle"></i>
                        <span></span>
                    </div>

                    <div class="lk-ob2-actions">
                        <button type="button" class="lk-ob2-btn-back" id="btnAccountBack">
                            <i data-lucide="arrow-left"></i>
                            <span>Voltar</span>
                        </button>
                        <button type="submit" class="lk-ob2-btn-primary" id="btnAccountNext">
                            <span>Criar conta</span>
                            <i data-lucide="arrow-right"></i>
                        </button>
                    </div>
                </form>

                <div class="lk-ob2-progress-hint">
                    <i data-lucide="shield-check"></i>
                    <span>Voce pode conectar banco, carteira ou outras contas depois.</span>
                </div>
            </div>
        </div>
    `,window.lucide&&lucide.createIcons();const ue=e.querySelector("#accountForm"),w=e.querySelector("#accountName"),C=e.querySelector("#accountInstitutionSearch"),A=e.querySelector("#btnInstitutionClear"),T=e.querySelector("#institutionResults"),V=e.querySelector("#institutionEmpty"),z=e.querySelector("#accountBalance"),oe=e.querySelector("#accountPreviewName"),ie=e.querySelector("#accountPreviewInstitution"),N=e.querySelector("#accountPreviewBalance"),_=e.querySelector("#accountError"),k=e.querySelector("#btnAccountBack"),O=e.querySelector("#btnAccountNext");let b=y||null,G=b?.nome||"",E=!1;function R(s){const c=j(s);return c===""?g.slice(0,8):g.filter(d=>d.searchKey.includes(c)).slice(0,8)}function x(){oe&&(oe.textContent=w.value.trim()||"Sua conta principal"),ie&&(ie.textContent=b?`Instituicao: ${b.nome}`:"Instituicao opcional"),N&&(N.textContent=`R$ ${z.value||"0,00"}`)}function M(s){b=s,C.value=s?.nome||"",E=!1;const c=w.value.trim();s&&(c===""||c===G)&&(w.value=s.nome,G=s.nome),x(),$()}function se(s=!0){b=null,s&&(C.value=""),E=!1,x(),$()}function $(){if(!T||!V)return;if(!E){T.hidden=!0,V.hidden=!0,T.innerHTML="";return}const s=R(C.value);if(s.length===0){T.hidden=!0,T.innerHTML="",V.hidden=!1;return}V.hidden=!0,T.hidden=!1,T.innerHTML=s.map(c=>`
            <button
                type="button"
                class="lk-ob2-combobox-item ${b&&String(b.id)===String(c.id)?"selected":""}"
                data-institution-id="${q(c.id)}">
                <span class="lk-ob2-combobox-item-name">${q(c.nome)}</span>
                <span class="lk-ob2-combobox-item-badge">
                    ${b&&String(b.id)===String(c.id)?"Selecionada":"Usar"}
                </span>
            </button>
        `).join(""),T.querySelectorAll(".lk-ob2-combobox-item").forEach(c=>{c.addEventListener("mousedown",d=>{d.preventDefault()}),c.addEventListener("click",()=>{const d=g.find(S=>String(S.id)===String(c.dataset.institutionId));d&&M(d)})})}function W(s){_&&(_.querySelector("span").textContent=s,_.style.display="flex",_.classList.add("shake"),setTimeout(()=>_.classList.remove("shake"),500))}function ne(){_&&(_.style.display="none")}z.addEventListener("focus",function(){setTimeout(()=>this.select(),50)}),z.addEventListener("input",function(c){let d=c.target.value.replace(/[^\d]/g,"");if(d===""){c.target.value="0,00",x();return}d=parseInt(d,10),c.target.value=Ee(d/100),x()}),w.addEventListener("input",x),C.addEventListener("focus",()=>{E=!0,$()}),C.addEventListener("input",()=>{const s=C.value.trim();s?b&&j(s)!==b.searchKey&&(b=null):(b=null,G=""),E=!0,x(),$()}),C.addEventListener("blur",()=>{setTimeout(()=>{const s=b||Te(g,C.value);if(!b&&s&&j(C.value)===s.searchKey){M(s);return}E=!1,$()},120)}),A&&(A.addEventListener("mousedown",s=>{s.preventDefault()}),A.addEventListener("click",s=>{s.preventDefault(),se()})),ue.addEventListener("submit",async s=>{s.preventDefault(),ne();const c=w.value.trim();if(!c){W("Digite um nome para a conta."),w.focus();return}const d=b||Te(g,C.value);O.classList.add("loading"),O.disabled=!0;try{await o({nome:c,saldo_inicial:He(z.value),instituicao_financeira_id:d?.id||null,instituicao:d?.nome||c}),n()}catch(S){W(S.message||"Erro ao criar conta. Tente novamente."),O.classList.remove("loading"),O.disabled=!1}}),k&&k.addEventListener("click",l),x(),$(),setTimeout(()=>{w?.focus(),w?.setSelectionRange(w.value.length,w.value.length)},300),requestAnimationFrame(()=>{e.querySelector(".lk-ob2-step")?.classList.add("visible")})}function H(e){return(parseFloat(e)||0).toLocaleString("pt-BR",{minimumFractionDigits:2,maximumFractionDigits:2})}function Oe(e){return e&&parseFloat(String(e).replace(/\./g,"").replace(",","."))||0}function K(e){return String(e||"").normalize("NFD").replace(/[\u0300-\u036f]/g,"").toLowerCase().trim()}function Xe(e,t){const a=Math.abs(Number(t)||0);return e==="receita"?a:-a}function Q(e){return String(e||"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#39;")}function Qe(e=""){const t=K(e);return[{match:["aliment","mercado","restaurante","lanche"],icon:"🍔",color:"#f59e0b"},{match:["transport","uber","combust","mobilidade"],icon:"🚗",color:"#3b82f6"},{match:["lazer","cinema","jogo","divers"],icon:"🎮",color:"#8b5cf6"},{match:["casa","moradia","aluguel"],icon:"🏠",color:"#14b8a6"},{match:["saude","farmacia","medic"],icon:"💊",color:"#ef4444"},{match:["educ","curso","livro"],icon:"📚",color:"#6366f1"},{match:["salario","receita","renda"],icon:"💰",color:"#10b981"},{match:["invest","reserva"],icon:"📈",color:"#06b6d4"}].find(n=>n.match.some(l=>t.includes(l)))||{icon:"📌",color:"#64748b"}}function Je(e=[]){return Array.isArray(e)?e.filter(t=>t&&t.id&&t.nome).map(t=>({...t,nome:String(t.nome).trim(),tipo:String(t.tipo||"").trim(),searchKey:K(t.nome)})).sort((t,a)=>t.nome.localeCompare(a.nome,"pt-BR")):[]}function Le(e,t){const a=K(t);return a===""?null:e.find(o=>o.searchKey===a)||null}function Ye(e,t={}){const{state:a,saveTransaction:o,skipTransaction:n,nextStep:l,prevStep:p}=ae(),{categorias:r=[],conta:g=null}=t,y=Je(r),f=y.filter(i=>i.tipo==="despesa"||i.tipo==="expense"),P=y.filter(i=>i.tipo==="receita"||i.tipo==="income"),F=a.data.account||g||{},ue=F?.nome||"Sua conta",w=Number(F?.saldo||0),C=a.data.transaction||{},A=C.tipo==="receita"?"receita":"despesa",T=C.categoria_id&&y.find(i=>String(i.id)===String(C.categoria_id))||null,V=T?.nome||"";e.innerHTML=`
        <div class="lk-ob2-step lk-ob2-transaction" data-step="transaction">
            <div class="lk-ob2-content">
                <div class="lk-ob2-header">
                    <div class="lk-ob2-icon-box lk-ob2-icon-despesa">
                        <i data-lucide="receipt"></i>
                    </div>
                    <h1 class="lk-ob2-title">Registre um gasto ou entrada recente</h1>
                    <p class="lk-ob2-subtitle">
                        Coloque algo real de agora para ver seu painel ganhar vida na hora.
                    </p>
                </div>

                <div class="lk-ob2-type-toggle">
                    <button type="button" class="lk-ob2-type-btn ${A==="despesa"?"active":""}" data-type="despesa">
                        <i data-lucide="arrow-down"></i>
                        <span>Despesa</span>
                    </button>
                    <button type="button" class="lk-ob2-type-btn ${A==="receita"?"active":""}" data-type="receita">
                        <i data-lucide="arrow-up"></i>
                        <span>Receita</span>
                    </button>
                </div>

                <form class="lk-ob2-form" id="transactionForm">
                    <input type="hidden" id="transactionType" name="tipo" value="${A}">
                    <input type="hidden" id="contaId" name="conta_id" value="${a.data.account?.id||""}">

                    <div class="lk-ob2-form-group lk-ob2-value-group">
                        <div class="lk-ob2-big-value">
                            <span class="lk-ob2-currency-big">R$</span>
                            <input type="text"
                                   class="lk-ob2-input-big"
                                   id="transactionValue"
                                   name="valor"
                                   value="${H(C.valor||0)}"
                                   inputmode="decimal"
                                   autocomplete="off"
                                   required>
                        </div>

                        <div class="lk-ob2-balance-preview" id="transactionBalancePreview">
                            <div class="lk-ob2-balance-card">
                                <span class="lk-ob2-balance-label">Conta usada</span>
                                <strong class="lk-ob2-balance-title">${Q(ue)}</strong>
                                <span class="lk-ob2-balance-value" id="currentBalanceText">R$ ${H(w)}</span>
                            </div>
                            <div class="lk-ob2-balance-card lk-ob2-balance-card-highlight">
                                <span class="lk-ob2-balance-label">Saldo depois deste registro</span>
                                <strong class="lk-ob2-balance-title" id="projectedBalanceTitle">Vai ficar assim</strong>
                                <span class="lk-ob2-balance-value" id="projectedBalanceText">R$ ${H(w)}</span>
                            </div>
                        </div>
                    </div>

                    <div class="lk-ob2-form-group">
                        <label class="lk-ob2-label" for="transactionDesc">
                            <i data-lucide="text"></i>
                            O que foi?
                        </label>
                        <input type="text"
                               class="lk-ob2-input"
                               id="transactionDesc"
                               name="descricao"
                               placeholder="Ex: Almoco, Uber, salario..."
                               maxlength="100"
                               value="${Q(C.descricao||"")}"
                               autocomplete="off">
                    </div>

                    <div class="lk-ob2-form-group">
                        <label class="lk-ob2-label">
                            <i data-lucide="tag"></i>
                            Categoria
                            <span class="lk-ob2-label-hint">(opcional)</span>
                        </label>
                        <div class="lk-ob2-institution-toolbar">
                            <div class="lk-ob2-search-field">
                                <i data-lucide="search"></i>
                                <input
                                    type="search"
                                    class="lk-ob2-input lk-ob2-search-input"
                                    id="transactionCategorySearch"
                                    placeholder="Busque ou deixe para depois"
                                    value="${Q(V)}"
                                    autocomplete="off">
                            </div>
                            <button type="button" class="lk-ob2-btn-ghost" id="btnCategoryClear">
                                <i data-lucide="eraser"></i>
                                <span>Depois</span>
                            </button>
                        </div>
                        <div class="lk-ob2-combobox-results" id="transactionCategoryResults" hidden></div>
                        <div class="lk-ob2-combobox-empty" id="transactionCategoryEmpty" hidden>
                            Nenhuma categoria encontrada. Voce pode salvar agora e categorizar depois.
                        </div>
                        <div class="lk-ob2-field-caption">
                            Escolher categoria acelera seu painel, mas nao precisa travar o onboarding.
                        </div>
                    </div>

                    <div class="lk-ob2-error" id="transactionError" style="display: none;">
                        <i data-lucide="alert-circle"></i>
                        <span></span>
                    </div>

                    <div class="lk-ob2-actions">
                        <button type="button" class="lk-ob2-btn-back" id="btnTransactionBack">
                            <i data-lucide="arrow-left"></i>
                            <span>Voltar</span>
                        </button>
                        <button type="submit" class="lk-ob2-btn-primary lk-ob2-btn-despesa" id="btnTransactionNext">
                            <span>Salvar registro</span>
                            <i data-lucide="check"></i>
                        </button>
                    </div>
                </form>

                <div class="lk-ob2-skip-section">
                    <div class="lk-ob2-skip-divider">
                        <span>ou</span>
                    </div>
                    <button type="button" class="lk-ob2-btn-skip" id="btnSkipTransaction">
                        <span>Pular e explorar o Lukrato</span>
                        <i data-lucide="arrow-right"></i>
                    </button>
                    <p class="lk-ob2-skip-hint">
                        <i data-lucide="info"></i>
                        Voce pode adicionar mais lancamentos depois pelo menu
                    </p>
                </div>
            </div>
        </div>
    `,window.lucide&&lucide.createIcons();const z=e.querySelector(".lk-ob2-step"),oe=e.querySelector("#transactionForm"),ie=e.querySelector("#transactionType"),N=e.querySelector("#transactionValue"),_=e.querySelector("#transactionDesc"),k=e.querySelector("#transactionCategorySearch"),O=e.querySelector("#transactionCategoryResults"),b=e.querySelector("#transactionCategoryEmpty"),G=e.querySelector("#btnCategoryClear"),E=e.querySelector("#transactionError"),R=e.querySelector("#btnTransactionNext"),x=e.querySelector("#btnTransactionBack"),M=e.querySelector("#btnSkipTransaction"),se=e.querySelectorAll(".lk-ob2-type-btn"),$=e.querySelector(".lk-ob2-icon-box"),W=e.querySelector("#currentBalanceText"),ne=e.querySelector("#projectedBalanceText"),s=e.querySelector("#projectedBalanceTitle"),c=e.querySelector("#transactionBalancePreview");let d=A,S=T;T&&String(T.id);let U=!1;function X(i=d){return i==="receita"?P:f}function _e(i,m=d){const u=K(i),L=X(m);return u===""?L.slice(0,8):L.filter(qe=>qe.searchKey.includes(u)).slice(0,8)}function ge(i){S=i||null,i&&String(i.id),k&&(k.value=i?.nome||""),U=!1,D()}function fe(i=!0){S=null,i&&k&&(k.value=""),U=!1,D()}function D(){if(!O||!b)return;if(!U){O.hidden=!0,b.hidden=!0,O.innerHTML="";return}const i=_e(k?.value||"");if(i.length===0){O.hidden=!0,O.innerHTML="",b.hidden=!1;return}b.hidden=!0,O.hidden=!1,O.innerHTML=i.map(m=>{const u=Qe(m.nome),L=S&&String(S.id)===String(m.id);return`
                <button
                    type="button"
                    class="lk-ob2-combobox-item ${L?"selected":""}"
                    data-category-id="${Q(m.id)}">
                    <span class="lk-ob2-combobox-item-name">
                        <span class="lk-ob2-combobox-item-icon" style="--category-color: ${u.color}">
                            ${u.icon}
                        </span>
                        <span>${Q(m.nome)}</span>
                    </span>
                    <span class="lk-ob2-combobox-item-badge">
                        ${L?"Selecionada":"Usar"}
                    </span>
                </button>
            `}).join(""),O.querySelectorAll(".lk-ob2-combobox-item").forEach(m=>{m.addEventListener("mousedown",u=>{u.preventDefault()}),m.addEventListener("click",()=>{const u=X().find(L=>String(L.id)===String(m.dataset.categoryId));u&&(ge(u),navigator.vibrate&&navigator.vibrate(10))})})}function pe(){const i=Oe(N.value),m=w+Xe(d,i);W&&(W.textContent=`R$ ${H(w)}`),ne&&(ne.textContent=`R$ ${H(m)}`),s&&(s.textContent=d==="receita"?"Vai entrar na conta":"Vai sair da conta"),c&&(c.classList.toggle("is-income",d==="receita"),c.classList.toggle("is-expense",d==="despesa"))}function ke(i){d=i,ie.value=i,se.forEach(L=>{L.classList.toggle("active",L.dataset.type===i)}),$.classList.toggle("lk-ob2-icon-receita",i==="receita"),$.classList.toggle("lk-ob2-icon-despesa",i==="despesa"),R.classList.toggle("lk-ob2-btn-receita",i==="receita"),R.classList.toggle("lk-ob2-btn-despesa",i==="despesa");const m=X(i),u=S&&m.find(L=>String(L.id)===String(S.id))||null;u?(S=u,String(u.id),k&&(k.value=u.nome)):fe(),D(),pe()}function Se(i){E&&(E.querySelector("span").textContent=i,E.style.display="flex",E.classList.add("shake"),setTimeout(()=>E.classList.remove("shake"),500))}function $e(){E&&(E.style.display="none")}se.forEach(i=>{i.addEventListener("click",()=>{ke(i.dataset.type)})}),k&&(k.addEventListener("focus",()=>{U=!0,D()}),k.addEventListener("input",()=>{const i=k.value.trim();i?S&&K(i)!==S.searchKey&&(S=null):S=null,U=!0,D()}),k.addEventListener("blur",()=>{setTimeout(()=>{const i=S||Le(X(),k.value);if(!S&&i&&K(k.value)===i.searchKey){ge(i);return}U=!1,D()},120)})),G&&(G.addEventListener("mousedown",i=>{i.preventDefault()}),G.addEventListener("click",i=>{i.preventDefault(),fe()})),N.addEventListener("focus",function(){setTimeout(()=>this.select(),50)}),N.addEventListener("input",function(m){let u=m.target.value.replace(/[^\d]/g,"");if(u===""){m.target.value="0,00",pe();return}u=parseInt(u,10),m.target.value=H(u/100),pe()}),oe.addEventListener("submit",async i=>{i.preventDefault(),$e();const m=Oe(N.value);if(m<=0){Se("Digite um valor maior que zero."),N.focus();return}R.classList.add("loading"),R.disabled=!0;try{const u=S||Le(X(),k?.value||"");await o({tipo:d,valor:m,descricao:_.value.trim()||(d==="receita"?"Receita":"Despesa"),categoria_id:u?.id||null,conta_id:a.data.account?.id}),z?.classList.add("lk-ob2-step-saved"),navigator.vibrate&&navigator.vibrate([10,20,10]),setTimeout(l,450)}catch(u){Se(u.message||"Erro ao criar lancamento. Tente novamente."),R.classList.remove("loading"),R.disabled=!1}}),x&&x.addEventListener("click",p),M&&M.addEventListener("click",async()=>{M.classList.add("loading"),M.disabled=!0,await n()}),ke(A),D(),setTimeout(()=>N?.focus(),300),requestAnimationFrame(()=>{e.querySelector(".lk-ob2-step")?.classList.add("visible")})}function ve(e){return String(e||"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#39;")}function Ze(e){const{state:t,completeOnboarding:a}=ae(),o=xe.find(y=>y.id===t.data.goal),n=t.data.goal?Ae[t.data.goal]?.success:"Agora voce ja sabe para onde seu dinheiro esta indo.",l=!!t.data.transaction,p=t.data.account?.nome||"Sua conta";e.innerHTML=`
        <div class="lk-ob2-step lk-ob2-success" data-step="success">
            <div class="lk-ob2-confetti" id="confettiContainer"></div>

            <div class="lk-ob2-content">
                <div class="lk-ob2-success-icon">
                    <div class="lk-ob2-success-ring"></div>
                    <div class="lk-ob2-success-ring lk-ob2-success-ring-2"></div>
                    <div class="lk-ob2-success-check">
                        <i data-lucide="check"></i>
                    </div>
                </div>

                <h1 class="lk-ob2-success-title">Tudo pronto!</h1>

                <p class="lk-ob2-success-message">
                    ${n||"Agora voce ja sabe para onde seu dinheiro esta indo."}
                </p>

                <div class="lk-ob2-success-summary">
                    <div class="lk-ob2-summary-item">
                        <div class="lk-ob2-summary-icon lk-ob2-summary-account">
                            <i data-lucide="wallet"></i>
                        </div>
                        <div class="lk-ob2-summary-text">
                            <span class="lk-ob2-summary-label">Conta criada</span>
                            <span class="lk-ob2-summary-value">${ve(p)}</span>
                        </div>
                        <i data-lucide="check-circle-2" class="lk-ob2-summary-check"></i>
                    </div>

                    ${l?`
                        <div class="lk-ob2-summary-item">
                            <div class="lk-ob2-summary-icon lk-ob2-summary-transaction">
                                <i data-lucide="receipt"></i>
                            </div>
                            <div class="lk-ob2-summary-text">
                                <span class="lk-ob2-summary-label">Primeiro lancamento</span>
                                <span class="lk-ob2-summary-value">
                                    ${ve(t.data.transaction?.descricao||"Registrado")}
                                </span>
                            </div>
                            <i data-lucide="check-circle-2" class="lk-ob2-summary-check"></i>
                        </div>
                    `:""}

                    ${o?`
                        <div class="lk-ob2-summary-item">
                            <div class="lk-ob2-summary-icon" style="background: ${o.color}20; color: ${o.color};">
                                <span>${o.emoji}</span>
                            </div>
                            <div class="lk-ob2-summary-text">
                                <span class="lk-ob2-summary-label">Seu foco</span>
                                <span class="lk-ob2-summary-value">${ve(o.title)}</span>
                            </div>
                            <i data-lucide="check-circle-2" class="lk-ob2-summary-check"></i>
                        </div>
                    `:""}
                </div>

                <div class="lk-ob2-xp-earned">
                    <div class="lk-ob2-xp-badge">
                        <span class="lk-ob2-xp-value">+${l?75:50}</span>
                        <span class="lk-ob2-xp-label">XP</span>
                    </div>
                    <span class="lk-ob2-xp-text">conquistados!</span>
                </div>

                <button class="lk-ob2-btn-primary lk-ob2-btn-success" id="btnGoToDashboard" type="button">
                    <span>Ver meu dashboard</span>
                    <i data-lucide="arrow-right"></i>
                </button>

                <div class="lk-ob2-redirect-hint" id="redirectHint" style="display: none;">
                    <div class="lk-ob2-spinner"></div>
                    <span>Preparando seu dashboard...</span>
                </div>
            </div>
        </div>
    `,window.lucide&&lucide.createIcons();const r=e.querySelector("#btnGoToDashboard"),g=e.querySelector("#redirectHint");et(),tt(),r&&(r.addEventListener("click",async()=>{r.style.display="none",g.style.display="flex",await a()}),setTimeout(()=>{(!g.style.display||g.style.display==="none")&&r.click()},4e3)),requestAnimationFrame(()=>{e.querySelector(".lk-ob2-step")?.classList.add("visible")})}function et(){if(typeof confetti!="function")return;const e=3e3,t=Date.now()+e,a={startVelocity:30,spread:360,ticks:60,zIndex:99999,colors:["#e67e22","#f39c12","#10b981","#3b82f6","#8b5cf6"]},o=setInterval(()=>{const n=t-Date.now();if(n<=0){clearInterval(o);return}const l=50*(n/e);try{confetti({...a,particleCount:l,origin:{x:Math.random()*.3+.1,y:Math.random()-.2}}),confetti({...a,particleCount:l,origin:{x:Math.random()*.3+.6,y:Math.random()-.2}})}catch{clearInterval(o)}},200);setTimeout(()=>{try{confetti({particleCount:100,spread:70,origin:{x:.5,y:.5},colors:["#e67e22","#f39c12","#10b981"],zIndex:99999})}catch{}},100)}function tt(){try{const e=new(window.AudioContext||window.webkitAudioContext),t=e.createOscillator(),a=e.createGain();t.connect(a),a.connect(e.destination),t.frequency.setValueAtTime(587.33,e.currentTime),t.frequency.setValueAtTime(880,e.currentTime+.1),a.gain.setValueAtTime(.3,e.currentTime),a.gain.exponentialRampToValueAtTime(.01,e.currentTime+.5),t.start(e.currentTime),t.stop(e.currentTime+.5)}catch{}}const te=document.getElementById("onboardingRoot"),J=window.__LK_ONBOARDING_CONFIG__||{};function de(e){return I.includes(e)?e:v.WELCOME}function at(e,t){const a=t!==v.SUCCESS;e.style.display=a?"":"none",e.innerHTML=a?Pe(t):"",a&&window.lucide&&lucide.createIcons()}function ot(e,t){switch(de(t.currentStep)){case v.GOAL:Ue(e);break;case v.ACCOUNT:We(e,Array.isArray(J.instituicoes)?J.instituicoes:[]);break;case v.TRANSACTION:Ye(e,{categorias:Array.isArray(J.categorias)?J.categorias:[],conta:J.conta||null});break;case v.SUCCESS:Ze(e);break;case v.WELCOME:default:ze(e);break}}function it(){return te.innerHTML=`
        <div class="lk-ob2-app">
            <div class="lk-ob2-progress-container" id="onboardingProgress"></div>
            <div class="lk-ob2-content-container">
                <div id="onboardingStepContainer"></div>
            </div>
        </div>
    `,{progress:te.querySelector("#onboardingProgress"),step:te.querySelector("#onboardingStepContainer")}}function Ie(){if(!te)return;const e=h.getState(),t=it();at(t.progress,de(e.currentStep)),ot(t.step,e)}function st(){if(!te)return;let e=null;h.subscribe(t=>{const a=de(t.currentStep);a!==e&&(e=a,Ie())}),e=de(h.getState().currentStep),Ie()}st();
