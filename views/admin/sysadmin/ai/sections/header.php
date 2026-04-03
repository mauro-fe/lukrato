<!-- Header -->
<div class="ai-header">
    <div class="header-content">
        <h1>
            <i data-lucide="bot"></i>
            Assistente IA
        </h1>
        <p>Chat inteligente e ferramentas de análise financeira</p>
    </div>
    <div style="display:flex;align-items:center;gap:.75rem;">
        <span class="ai-badge <?= strtolower($aiProvider) === 'ollama' ? 'ollama' : '' ?>">
            <i data-lucide="cpu" style="width:13px;height:13px;"></i>
            <?= htmlspecialchars($aiProvider) ?> · <?= htmlspecialchars($aiModel) ?>
        </span>
        <a href="<?= BASE_URL ?>sysadmin" class="btn-back">
            <i data-lucide="arrow-left"></i>
            Voltar ao Painel
        </a>
    </div>
</div>
