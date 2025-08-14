<?php
session_start();
require_once '../config/database.php';
require_once 'functions.php';

verificarLogin();

$stats = obterEstatisticas($pdo);
registrarLog($pdo, 'acesso_dashboard', 'Acesso ao dashboard administrativo');

date_default_timezone_set('America/Sao_Paulo');

// Obter ano selecionado ou usar o atual
$anoSelecionado = $_GET['ano'] ?? date('Y');

// Puxar os dados do banco (pode retornar menos de 12 meses)
$dadosCadastros = obterDadosCadastrosMensais($pdo, $anoSelecionado);
$anosDisponiveis = obterAnosDisponiveis($pdo);

// Meses do ano
$meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dec'];

// Garantir 12 meses (Janeiro = índice 0) preenchendo meses sem dados com 0
$dadosCompleto = array_fill(0, 12, 0);
foreach ($dadosCadastros as $mesIndex => $valor) {
    // Ajuste se $mesIndex do banco vem de 1 a 12
    $dadosCompleto[$mesIndex - 1] = $valor;
}

// Índice do mês atual (0 a 11)
$mesAtual = date('n') - 1;

// Rotacionar os dados para que o gráfico comece no mês atual
$dadosCorrigidos = array_merge(
    array_slice($dadosCompleto, $mesAtual),
    array_slice($dadosCompleto, 0, $mesAtual)
);

// Preparar dados para o gráfico
$dadosGraficoJson = json_encode(array_values($dadosCompleto));
$mesesJson = json_encode($meses);

// Encontrar o índice do mês com mais cadastros (sem imprimir nada ainda)
$indiceMesMaisCadastros = array_search(max($dadosCompleto), $dadosCompleto);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrativo - Seletivo SES</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="../assets/css/dashboard.css">
</head>

<body class="bg-light">

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar p-3">
                    <div class="text-center mb-4">
                        <img src="../assets/images/branca.png" alt="Logo" class="img-fluid mb-3"
                            style="max-height: 60px;" onerror="this.style.display='none'">
                        <h5>Painel Administrativo</h5>
                        <small>Seletivo SES</small>
                    </div>

                    <nav class="nav flex-column">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="usuarios.php">
                            <i class="fas fa-users me-2"></i>Usuários
                        </a>
                        <!-- <a class="nav-link" href="formularios.php">
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
                        </a> -->

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
                                        <!-- <li><a class="dropdown-item" href="perfil.php"><i
                                                    class="fas fa-user me-2"></i>Perfil</a></li> -->
                                        <!-- <li><a class="dropdown-item" href="configuracoes.php"><i
                                                    class="fas fa-cog me-2"></i>Configurações</a></li> -->
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
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-chart-line me-2"></i>Estatísticas de Cadastros
                                        </h5>
                                        <small class="text-muted">Análise mensal de usuários cadastrados</small>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                            id="dropdownAno" data-bs-toggle="dropdown">
                                            <i class="fas fa-calendar-alt me-1"></i>Ano: <?= $anoSelecionado ?>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownAno">
                                            <?php foreach ($anosDisponiveis as $ano): ?>
                                            <li>
                                                <a class="dropdown-item <?= $ano == $anoSelecionado ? 'active' : '' ?>"
                                                    href="?ano=<?= $ano ?>">
                                                    <i class="fas fa-calendar me-2"></i><?= $ano ?>
                                                    <?php if ($ano == $anoSelecionado): ?>
                                                    <i class="fas fa-check ms-2"></i>
                                                    <?php endif; ?>
                                                </a>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container" style="position: relative; height: 300px;">
                                        <canvas id="cadastrosChart"></canvas>
                                    </div>

                                    <!-- Resumo estatístico abaixo do gráfico -->
                                    <div class="row mt-3">
                                        <div class="col-md-4">
                                            <div class="card bg-light">
                                                <div class="card-body text-center py-2">
                                                    <small class="text-muted">Média Mensal</small>
                                                    <h5 class="mb-0">
                                                        <?= number_format(array_sum($dadosCadastros) / max(1, count(array_filter($dadosCadastros)))) ?>
                                                    </h5>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card bg-light">
                                                <div class="card-body text-center py-2">
                                                    <small class="text-muted">Total do Ano</small>
                                                    <h5 class="mb-0"><?= number_format(array_sum($dadosCadastros)) ?>
                                                    </h5>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card bg-light">
                                                <div class="card-body text-center py-2">
                                                    <small class="text-muted">Mês com Mais Cadastros</small>
                                                    <h5 class="mb-0">
                                                        <?php
                                                        // Encontrar o índice do mês com mais cadastros no array completo
                                                        $indiceMesMaisCadastros = array_search(max($dadosCompleto), $dadosCompleto);
                                                        echo $meses[$indiceMesMaisCadastros] . ': ' . max($dadosCompleto);
                                                        ?>
                                                    </h5>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <div class="card-footer text-muted small d-flex justify-content-between">
                                    <div>
                                        <i class="fas fa-info-circle me-1"></i>
                                        Dados atualizados em <?= date('d/m/Y H:i:s') ?>
                                    </div>
                                    <div>
                                        <!-- <a href="#" class="text-muted" data-bs-toggle="tooltip" title="Exportar dados">
                                            <i class="fas fa-download me-1"></i>Exportar
                                        </a> -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-calendar-check me-2"></i>Atividade Recente
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="activity-item mb-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="fas fa-user-plus text-primary me-2"></i>
                                                <span>Novos Usuários</span>
                                            </div>
                                            <span
                                                class="badge bg-primary rounded-pill"><?= $stats['usuarios_hoje'] ?></span>
                                        </div>
                                        <small class="text-muted d-block mt-1">Hoje</small>
                                    </div>

                                    <div class="activity-item mb-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="fas fa-file-alt text-success me-2"></i>
                                                <span>Formulários</span>
                                            </div>
                                            <span
                                                class="badge bg-success rounded-pill"><?= $stats['formularios_hoje'] ?></span>
                                        </div>
                                        <small class="text-muted d-block mt-1">Hoje</small>
                                    </div>

                                    <div class="activity-item mb-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="fas fa-chart-line text-info me-2"></i>
                                                <span>Esta Semana</span>
                                            </div>
                                            <span
                                                class="badge bg-info rounded-pill"><?= $stats['usuarios_semana'] ?></span>
                                        </div>
                                        <small class="text-muted d-block mt-1">Últimos 7 dias</small>
                                    </div>

                                    <div class="activity-item mb-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="fas fa-calendar-alt text-warning me-2"></i>
                                                <span>Este Mês</span>
                                            </div>
                                            <span
                                                class="badge bg-warning rounded-pill"><?= $stats['usuarios_mes'] ?></span>
                                        </div>
                                        <small class="text-muted d-block mt-1">Mês atual</small>
                                    </div>

                                    <div class="activity-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="fas fa-users text-secondary me-2"></i>
                                                <span>Total de Usuários</span>
                                            </div>
                                            <span
                                                class="badge bg-secondary rounded-pill"><?= $stats['total_usuarios'] ?></span>
                                        </div>
                                        <small class="text-muted d-block mt-1">Registrados no sistema</small>
                                    </div>

                                    <hr class="my-3">

                                    <div class="text-center">
                                        <a href="usuarios.php" class="btn btn-primary btn-sm me-2">
                                            <i class="fas fa-users me-2"></i>Ver Todos
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <!-- <div class="row">
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
                                            <a href="relatorios.php" class="btn btn-outline-success w-100 disabled">
                                                <i class="fas fa-chart-bar fa-2x d-block mb-2"></i>
                                                Gerar Relatórios
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <a href="arquivos.php" class="btn btn-outline-warning w-100 disabled">
                                                <i class="fas fa-folder fa-2x d-block mb-2"></i>
                                                Ver Arquivos
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <a href="configuracoes.php" class="btn btn-outline-info w-100 disabled">
                                                <i class="fas fa-cog fa-2x d-block mb-2"></i>
                                                Configurações
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@1.0.2"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Elementos do DOM
        const ctx = document.getElementById('cadastrosChart').getContext('2d');
        const chartContainer = document.querySelector('.chart-container');
        const dropdownAno = document.getElementById('dropdownAno');
        const meses = <?= $mesesJson ?>;

        // Variável para armazenar a instância do gráfico
        let cadastrosChart;

        // Função para renderizar o gráfico
        // Adicione no início do script (após obter os meses):
        const mesAtual = new Date().getMonth(); // 0-11 (Jan-Dez)

        // Atualize a função renderChart para incluir a anotação:
        function renderChart(data, ano) {
            if (cadastrosChart) {
                cadastrosChart.destroy();
            }

            dropdownAno.textContent = `Ano: ${ano}`;

            cadastrosChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: meses,
                    datasets: [{
                        label: 'Cadastros de Usuários',
                        data: data,
                        borderColor: 'rgb(102, 126, 234)',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: 'rgb(102, 126, 234)',
                        pointRadius: function(context) {
                            return context.dataIndex === mesAtual ? 6 : 4;
                        },
                        pointHoverRadius: function(context) {
                            return context.dataIndex === mesAtual ? 8 : 6;
                        }
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: ${context.raw}`;
                                }
                            }
                        },
                        annotation: {
                            annotations: {
                                highlightMonth: {
                                    type: 'box',
                                    xMin: mesAtual - 0.5,
                                    xMax: mesAtual + 0.5,
                                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                                    borderColor: 'rgba(255, 99, 132, 0.5)',
                                    borderWidth: 1
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            },
                            title: {
                                display: true,
                                text: 'Quantidade de Cadastros'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Meses do Ano'
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            });
        }

        // Carregar dados iniciais
        renderChart(<?= $dadosGraficoJson ?>, <?= $anoSelecionado ?>);

        // Atualizar o gráfico quando o ano for alterado
        document.querySelectorAll('.dropdown-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();

                const ano = this.getAttribute('href').split('=')[1];

                // Mostrar estado de carregamento
                chartContainer.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2 text-muted">Carregando dados...</p>
                </div>
            `;

                // Remover classe active de todos os itens
                document.querySelectorAll('.dropdown-item').forEach(el => {
                    el.classList.remove('active');
                });

                // Adicionar classe active ao item clicado
                this.classList.add('active');

                // Buscar dados via AJAX
                fetch(`../api/cadastros_mensais.php?ano=${ano}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Erro na requisição');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message);
                        }

                        // Restaurar canvas
                        chartContainer.innerHTML = '<canvas id="cadastrosChart"></canvas>';

                        // Obter novo contexto
                        const newCtx = document.getElementById('cadastrosChart').getContext(
                            '2d');

                        // Atualizar gráfico com novos dados
                        renderChart(data.data, data.ano);
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        chartContainer.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${error.message || 'Erro ao carregar dados'}
                        </div>
                    `;

                        // Adicionar botão para tentar novamente
                        const retryBtn = document.createElement('button');
                        retryBtn.className = 'btn btn-sm btn-primary mt-2';
                        retryBtn.innerHTML =
                            '<i class="fas fa-sync-alt me-1"></i> Tentar novamente';
                        retryBtn.onclick = () => window.location.reload();
                        chartContainer.querySelector('.alert').appendChild(retryBtn);
                    });
            });
        });
    });
    </script>
</body>

</html>