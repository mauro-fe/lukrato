<!-- Chat -->
<div class="chat-card">
    <div class="chat-title">
        <i data-lucide="message-circle" style="color:var(--blue-600)"></i>
        Chat Assistente
    </div>

    <div class="chat-messages" id="chatMessages">
        <div class="chat-empty" id="chatEmpty">
            <i data-lucide="bot"></i>
            <strong>Olá! Sou seu assistente financeiro.</strong>
            <span>Pergunte sobre métricas, usuários, padrões de gastos ou qualquer coisa do sistema.</span>
        </div>
    </div>

    <div class="chat-input-row">
        <textarea id="chatInput"
            placeholder="Digite sua pergunta... (Enter para enviar, Shift+Enter para nova linha)"
            rows="1"></textarea>
        <button id="chatSend" title="Enviar">
            <i data-lucide="send" style="width:16px;height:16px;"></i>
        </button>
    </div>
</div>
