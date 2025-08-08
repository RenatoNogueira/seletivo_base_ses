<?php
session_start();
require_once '../config/database.php';
require_once 'functions.php';

verificarLogin();

$stats = obterEstatisticas($pdo);
registrarLog($pdo, 'acesso_dashboard', 'Acesso ao dashboard administrativo');
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrativo - Seletico SES</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css" rel="stylesheet">
    <style>
    .sidebar {
        min-height: 100vh;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .sidebar .nav-link {
        color: rgba(255, 255, 255, 0.8);
        border-radius: 8px;
        margin: 2px 0;
        transition: all 0.3s;
    }

    .sidebar .nav-link:hover,
    .sidebar .nav-link.active {
        color: white;
        background: rgba(255, 255, 255, 0.1);
    }

    .stat-card {
        border-radius: 15px;
        border: none;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: white;
    }

    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .bg-gradient-success {
        background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
    }

    .bg-gradient-warning {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .bg-gradient-info {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .chart-container {
        position: relative;
        height: 300px;
    }

    .admin-header {
        background: white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 1rem 0;
    }

    .welcome-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        border: none;
    }
    </style>
</head>

<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar p-3">
                    <div class="text-center mb-4">
                        <i class="fas fa-shield-alt fa-3x mb-2"></i>
                        <h5>Admin Panel</h5>
                        <small>Seletico SES</small>
                    </div>

                    <nav class="nav flex-column">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="usuarios.php">
                            <i class="fas fa-users me-2"></i>Usuários
                        </a>
                        <a class="nav-link" href="formularios.php">
                            <i class="fas fa-file-alt me-2"></i>Formulários
                        </a>
                        <a class="nav-link" href="arquivos.php">
                            <i class="fas fa-folder me-2"></i>Arquivos
                        </a>
                        <a class="nav-link" href="relatorios.php">
                            <i class="fas fa-chart-bar me-2"></i>Relatórios
                        </a>
                        <a class="nav-link" href="configuracoes.php">
                            <i class="fas fa-cog me-2"></i>Configurações
                        </a>
                        <a class="nav-link" href="logs.php">
                            <i class="fas fa-history me-2"></i>Logs
                        </a>

                        <hr class="my-3">

                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Sair
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <!-- Header -->
                <div class="admin-header">
                    <div class="container-fluid">
                        <div class="row align-items-center">
                            <div class="col">
                                <h4 class="mb-0">Dashboard Administrativo</h4>
                                <small class="text-muted">Bem-vindo, <?= sanitizar($_SESSION['admin_nome']) ?></small>
                            </div>
                            <div class="col-auto">
                                <div class="dropdown">
                                    <button class="btn btn-outline-primary dropdown-toggle" type="button"
                                        data-bs-toggle="dropdown">
                                        <i class="fas fa-user me-2"></i><?= sanitizar($_SESSION['admin_nome']) ?>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="perfil.php"><i
                                                    class="fas fa-user me-2"></i>Perfil</a></li>
                                        <li><a class="dropdown-item" href="configuracoes.php"><i
                                                    class="fas fa-cog me-2"></i>Configurações</a></li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li><a class="dropdown-item" href="logout.php"><i
                                                    class="fas fa-sign-out-alt me-2"></i>Sair</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="container-fluid py-4">
                    <!-- Welcome Card -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card welcome-card">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <h3>Bem-vindo ao Painel Administrativo!</h3>
                                            <p class="mb-0">Gerencie usuários, visualize relatórios e controle o sistema
                                                Seletico SES.</p>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chart-line fa-3x opacity-50"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="stat-icon bg-gradient-primary">
                                                <i class="fas fa-users"></i>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="text-end">
                                                <h3 class="mb-0"><?= number_format($stats['total_usuarios']) ?></h3>
                                                <small class="text-muted">Total de Usuários</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="stat-icon bg-gradient-success">
                                                <i class="fas fa-file-alt"></i>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="text-end">
                                                <h3 class="mb-0"><?= number_format($stats['total_formularios']) ?></h3>
                                                <small class="text-muted">Formulários</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="stat-icon bg-gradient-warning">
                                                <i class="fas fa-folder"></i>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="text-end">
                                                <h3 class="mb-0"><?= number_format($stats['total_arquivos']) ?></h3>
                                                <small class="text-muted">Arquivos</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="stat-icon bg-gradient-info">
                                                <i class="fas fa-graduation-cap"></i>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="text-end">
                                                <h3 class="mb-0"><?= number_format($stats['total_cursos']) ?></h3>
                                                <small class="text-muted">Cursos</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts and Recent Activity -->
                    <div class="row">
                        <div class="col-lg-8 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-line me-2"></i>Cadastros por Período
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="cadastrosChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-calendar me-2"></i>Resumo Hoje
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span>Novos Usuários</span>
                                        <span class="badge bg-primary"><?= $stats['usuarios_hoje'] ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span>Formulários Hoje</span>
                                        <span class="badge bg-success"><?= $stats['formularios_hoje'] ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span>Esta Semana</span>
                                        <span class="badge bg-info"><?= $stats['usuarios_semana'] ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>Este Mês</span>
                                        <span class="badge bg-warning"><?= $stats['usuarios_mes'] ?></span>
                                    </div>

                                    <hr>

                                    <div class="text-center">
                                        <a href="usuarios.php" class="btn btn-primary btn-sm">
                                            <i class="fas fa-users me-2"></i>Ver Todos os Usuários
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-bolt me-2"></i>Ações Rápidas
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <a href="usuarios.php" class="btn btn-outline-primary w-100">
                                                <i class="fas fa-users fa-2x d-block mb-2"></i>
                                                Gerenciar Usuários
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <a href="relatorios.php" class="btn btn-outline-success w-100">
                                                <i class="fas fa-chart-bar fa-2x d-block mb-2"></i>
                                                Gerar Relatórios
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <a href="arquivos.php" class="btn btn-outline-warning w-100">
                                                <i class="fas fa-folder fa-2x d-block mb-2"></i>
                                                Ver Arquivos
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <a href="configuracoes.php" class="btn btn-outline-info w-100">
                                                <i class="fas fa-cog fa-2x d-block mb-2"></i>
                                                Configurações
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Gráfico de cadastros
    const ctx = document.getElementById('cadastrosChart').getContext('2d');
    const cadastrosChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago'],
            datasets: [{
                label: 'Cadastros',
                data: [12, 19, 3, 5, 2, 3, 10, 15],
                borderColor: 'rgb(102, 126, 234)',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    </script>
</body>

</html>