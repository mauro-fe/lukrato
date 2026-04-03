<!-- Tab Panel: IA -->
<div class="sysadmin-tab-panel" id="panel-ia" role="tabpanel" aria-labelledby="tab-ia">
    <div
        style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1.25rem;padding:3rem 1rem;text-align:center;">
        <div
            style="width:64px;height:64px;background:#ede9fe;border-radius:50%;display:flex;align-items:center;justify-content:center;">
            <i data-lucide="bot" style="width:32px;height:32px;color:#7c3aed;"></i>
        </div>
        <div>
            <h2 style="font-size:1.3rem;font-weight:700;margin:0 0 .5rem;">Assistente de Inteligência Artificial</h2>
            <p style="color:#6b7280;margin:0;max-width:440px;">Chat interativo com IA, sugestão automática de categorias
                e análise de padrões financeiros dos seus usuários.</p>
        </div>
        <a href="<?= BASE_URL ?>sysadmin/ai"
            style="display:inline-flex;align-items:center;gap:.5rem;padding:.65rem 1.5rem;background:#7c3aed;color:#fff;border-radius:.625rem;text-decoration:none;font-weight:600;font-size:.9rem;transition:background .15s;"
            onmouseover="this.style.background='#6d28d9'" onmouseout="this.style.background='#7c3aed'">
            <i data-lucide="external-link" style="width:16px;height:16px;"></i>
            Abrir Assistente IA
        </a>
        <a href="<?= BASE_URL ?>sysadmin/ai/logs"
            style="display:inline-flex;align-items:center;gap:.5rem;padding:.65rem 1.5rem;background:transparent;color:#7c3aed;border:1px solid #7c3aed;border-radius:.625rem;text-decoration:none;font-weight:600;font-size:.9rem;transition:background .15s,color .15s;"
            onmouseover="this.style.background='#7c3aed';this.style.color='#fff'"
            onmouseout="this.style.background='transparent';this.style.color='#7c3aed'">
            <i data-lucide="file-text" style="width:16px;height:16px;"></i>
            Logs da IA
        </a>
        <div style="display:flex;gap:1.5rem;flex-wrap:wrap;justify-content:center;margin-top:.5rem;">
            <div style="text-align:center;">
                <div style="font-size:1.4rem;font-weight:700;color:#7c3aed;">3</div>
                <div style="font-size:.78rem;color:#9ca3af;">Endpoints de IA</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:1.4rem;font-weight:700;color:#7c3aed;">gpt-4o-mini</div>
                <div style="font-size:.78rem;color:#9ca3af;">Modelo padrão</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:1.4rem;font-weight:700;color:#7c3aed;">800ms</div>
                <div style="font-size:.78rem;color:#9ca3af;">Debounce sugestão</div>
            </div>
        </div>
    </div>
</div>