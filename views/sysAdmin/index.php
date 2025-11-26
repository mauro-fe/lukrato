<style>
    .admin-card {
          background: var(--glass-bg);

        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        border-left: 5px solid var(--azul);
        transition: transform 0.2s;
    }
    .admin-card:hover { transform: translateY(-5px); }
    .admin-card.danger { border-left-color: #e74c3c; }
    .admin-card.success { border-left-color: #2ecc71; }

    .admin-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .btn-admin {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: bold;
        color: white;
    }
    .btn-danger { background-color: #c0392b; }
    .btn-action { background-color: var(--azul); }
</style>

<div class="content-wrapper" style="padding: 20px;">
    <div class="page-header mb-4">
        <h1 style="color: var(--azul);"><?= htmlspecialchars($pageTitle ?? '') ?></h1>
        <p class="text-muted"><?= htmlspecialchars($subTitle ?? '') ?></p>
    </div>

    <h3 class="mb-3">Raio-X do Sistema</h3>
    <div class="admin-grid">
        <div class="admin-card">
            <h4>Usuários Totais</h4>
            <h2 id="total-users"><?= number_format($metrics['totalUsers'] ?? 0, 0, ',', '.') ?></h2>
            <small>+<?= number_format($metrics['newToday'] ?? 0, 0, ',', '.') ?> hoje</small>
        </div>

        <div class="admin-card success">
            <h4>Admins ativos</h4>
            <h2><?= number_format($metrics['totalAdmins'] ?? 0, 0, ',', '.') ?></h2>
            <small style="color: #2ecc71;">Usuários com perfil de Admin</small>
        </div>

        <div class="admin-card danger">
            <h4>Logs de Erro</h4>
            <h2>3</h2>
            <small>Último: há 20 min</small>
            <a href="#" style="font-size: 12px; float: right;">Ver Logs</a>
        </div>
    </div>

    <hr class="my-5">

    <h3 class="mb-3">Controle Mestre</h3>
    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="admin-card">
                <h5>Manutenção e Limpeza</h5>
                <p>Ferramentas para saúde do servidor.</p>
                
                <button class="btn-admin btn-action" onclick="limparCache()">
                    �Y�� Limpar Cache do Sistema
                </button>
                <button class="btn-admin btn-danger" onclick="alert('Modo manutenção ativado!')">
                    �s���? Ativar Modo Manutenção
                </button>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="admin-card">
                <h5>Buscar Usuário</h5>
                <p>Edite, promova ou bloqueie qualquer pessoa.</p>
                <div style="display: flex; gap: 10px;">
                    <input type="text" placeholder="E-mail ou ID..." style="padding: 10px; flex: 1; border: 1px solid #ccc; border-radius: 5px;">
                    <button class="btn-admin btn-action">Buscar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <h3>Últimos Cadastros</h3>
        <div class="table-responsive admin-card" style="padding: 0; overflow: hidden;">
            <table class="table table-hover mb-0">
                <thead style="background-color: #f8f9fa;">
                    <tr>
                        <th class="p-3">ID</th>
                        <th class="p-3">Nome</th>
                        <th class="p-3">Email</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($recentUsers ?? []) as $u): ?>
                        <tr>
                            <td class="p-3">#<?= (int)($u->id ?? 0) ?></td>
                            <td class="p-3"><?= htmlspecialchars($u->nome ?? '-') ?></td>
                            <td class="p-3"><?= htmlspecialchars($u->email ?? '-') ?></td>
                            <td class="p-3">
                                <?php if (($u->is_admin ?? 0) == 1): ?>
                                    <span style="color: #2ecc71;">Admin</span>
                                <?php else: ?>
                                    <span style="color: #3498db;">Usuário</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3">
                                <?= $u->created_at ? date('d/m/Y H:i', strtotime((string)$u->created_at)) : '-' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function limparCache() {
        if(confirm('Tem certeza que deseja limpar o cache do sistema?')) {
            alert('Comando enviado! Cache limpo.');
        }
    }
</script>
