<?php

use Application\Lib\Auth; ?>
<main class="main-content position-relative">
    <!-- Navbar melhorada -->
    <div class="container-fluid py-3 px-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-transparent mb-2 pb-0 pt-1 px-0">
                <li class="breadcrumb-item">
                    <a href="#" class="text-muted">admin_username</a>
                </li>
                <li class="breadcrumb-item text-dark active" aria-current="page">Fichas</li>
            </ol>
            <h4 class="font-weight-bold mb-0 text-dark">
                <i class="fas fa-file-medical me-3 text-primary"></i>Gestão de Fichas
            </h4>
        </nav>

        <div class="container py-4">
            <h2 class="mb-4">
                Painel SysAdmin
                <span class="badge bg-dark ms-2">Acesso restrito</span>
            </h2>

            <div class="row g-4">
                <div class="col-md-6 col-xl-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <h5 class="card-title">Cadastrar Novo Cliente</h5>
                            <p class="card-text">Adicione novos administradores ao sistema com acesso próprio.</p>
                            <a href="<?= BASE_URL ?>admin/novo" class="btn btn-success" target="_blank">
                                <i class="fas fa-user-plus me-1"></i> Cadastrar
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Card 1 -->
                <div class="col-md-6 col-xl-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <h5 class="card-title">Gerenciar Clientes</h5>
                            <p class="card-text">Visualize e controle os administradores cadastrados no sistema.</p>
                            <a href="<?= BASE_URL ?>sysadmin/admins" class="btn btn-primary">Acessar</a>
                        </div>
                    </div>
                </div>

                <!-- Card 2 -->
                <div class="col-md-6 col-xl-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <h5 class="card-title">Configurações Gerais</h5>
                            <p class="card-text">Gerencie configurações avançadas do sistema e permissões.</p>
                            <a href="#" class="btn btn-outline-secondary disabled">Em breve</a>
                        </div>
                    </div>
                </div>

                <!-- Card 3 -->
                <div class="col-md-6 col-xl-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <h5 class="card-title">Logs e Auditoria</h5>
                            <p class="card-text">Visualize registros de ações, tentativas de login e mais.</p>
                            <a href="#" class="btn btn-outline-secondary disabled">Em breve</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>