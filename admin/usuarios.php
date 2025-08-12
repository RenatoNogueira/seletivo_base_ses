<?php
session_start();
require_once '../config/database.php';
require_once 'functions.php';

verificarLogin();

// Função auxiliar para acesso seguro a arrays
function safeGet($array, $key, $default = null)
{
    return isset($array[$key]) ? $array[$key] : $default;
}

// Parâmetros de busca com valores padrão
$filtros = [
    'termo' => safeGet($_GET, 'termo', ''),
    'cidade' => safeGet($_GET, 'cidade', ''),
    'estado' => safeGet($_GET, 'estado', ''),
    'data_inicio' => safeGet($_GET, 'data_inicio', ''),
    'data_fim' => safeGet($_GET, 'data_fim', '')
];

$pagina = max(1, intval(safeGet($_GET, 'pagina', 1)));
$limite = 20;
$offset = ($pagina - 1) * $limite;

$usuarios = buscarUsuarios($pdo, $filtros, $limite, $offset);
$totalUsuarios = contarUsuarios($pdo, $filtros);
$totalPaginas = ceil($totalUsuarios / $limite);

registrarLog($pdo, 'acesso_usuarios', 'Acesso à página de usuários');

// Função para formatar dados de forma segura
function formatarDadosUsuario($usuario)
{
    return [
        'usuario_id' => safeGet($usuario, 'usuario_id', 0),
        'nome_completo' => sanitizar(safeGet($usuario, 'nome_completo', 'Nome não informado')),
        'email' => sanitizar(safeGet($usuario, 'email', 'Email não informado')),
        'cpf' => formatarCPF(safeGet($usuario, 'cpf', '')),
        'rg' => sanitizar(safeGet($usuario, 'rg', '')),
        'data_nascimento' => safeGet($usuario, 'data_nascimento') ? date('d/m/Y', strtotime($usuario['data_nascimento'])) : '-',
        'celular' => safeGet($usuario, 'celular') ? formatarTelefone($usuario['celular']) : '',
        'telefone_fixo' => safeGet($usuario, 'telefone_fixo') ? formatarTelefone($usuario['telefone_fixo']) : '',
        'cidade' => sanitizar(safeGet($usuario, 'cidade', '-')),
        'estado' => sanitizar(safeGet($usuario, 'estado', '-')),
        'data_cadastro' => formatarData(safeGet($usuario, 'data_cadastro', '')),
        'total_cursos' => safeGet($usuario, 'total_cursos', 0),
        'total_arquivos' => safeGet($usuario, 'total_arquivos', 0),
        'nivel' => sanitizar(safeGet($usuario, 'nivel', '-')),
        'areas_formacao' => sanitizar(safeGet($usuario, 'areas_formacao', '-')),
        'registros_profissionais' => sanitizar(safeGet($usuario, 'registros_profissionais', '-'))
    ];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Admin Seletico SES</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
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

    .admin-header {
        background: white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 1rem 0;
    }

    .search-card {
        border-radius: 15px;
        border: none;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(102, 126, 234, 0.1);
    }

    .btn-action {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }

    .loading {
        display: none;
        text-align: center;
        padding: 2rem;
    }

    .no-results {
        display: none;
        text-align: center;
        padding: 2rem;
        color: #6c757d;
    }

    .truncate-text {
        max-width: 150px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Estilo adicional para DataTables */
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_processing,
    .dataTables_wrapper .dataTables_paginate {
        color: #6c757d;
    }

    .dataTables_wrapper .dataTables_filter input {
        margin-left: 0.5em;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 0.25rem 0.5rem;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 0.25rem 0.75rem;
        border: 1px solid transparent;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: #667eea;
        color: white !important;
        border: 1px solid #667eea;
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
                        <img src="../assets/images/branca.png" alt="Logo" class="img-fluid mb-3"
                            style="max-height: 60px;" onerror="this.style.display='none'">
                        <h5>Painel Administrativo</h5>
                        <small>Seletico SES</small>
                    </div>

                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link active" href="usuarios.php">
                            <i class="fas fa-users me-2"></i>Usuários
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
                                <h4 class="mb-0">Gerenciar Usuários</h4>
                                <small class="text-muted">Visualize e gerencie todos os usuários cadastrados</small>
                            </div>
                            <div class="col-auto">
                                <a href="dashboard.php" class="btn btn-outline-primary">
                                    <i class="fas fa-arrow-left me-2"></i>Voltar ao Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="container-fluid py-4">
                    <!-- Search Card -->
                    <!-- <div class="row mb-4">
                        <div class="col-12">
                            <div class="card search-card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-search me-2"></i>Buscar Usuários
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <form id="searchForm" class="row g-3">
                                        <div class="col-md-4">
                                            <label for="termo" class="form-label">Nome, CPF ou Email</label>
                                            <input type="text" class="form-control" id="termo" name="termo"
                                                placeholder="Digite para buscar..."
                                                value="<?= htmlspecialchars($filtros['termo']) ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label for="cidade" class="form-label">Cidade</label>
                                            <input type="text" class="form-control" id="cidade" name="cidade"
                                                placeholder="Cidade"
                                                value="<?= htmlspecialchars($filtros['cidade']) ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label for="estado" class="form-label">Estado</label>
                                            <select class="form-select" id="estado" name="estado">
                                                <option value="">Todos</option>
                                                <option value="AC" <?= $filtros['estado'] == 'AC' ? 'selected' : '' ?>>
                                                    AC</option>
                                                <option value="AL" <?= $filtros['estado'] == 'AL' ? 'selected' : '' ?>>
                                                    AL</option>
                                                <option value="AP" <?= $filtros['estado'] == 'AP' ? 'selected' : '' ?>>
                                                    AP</option>
                                                <option value="AM" <?= $filtros['estado'] == 'AM' ? 'selected' : '' ?>>
                                                    AM</option>
                                                <option value="BA" <?= $filtros['estado'] == 'BA' ? 'selected' : '' ?>>
                                                    BA</option>
                                                <option value="CE" <?= $filtros['estado'] == 'CE' ? 'selected' : '' ?>>
                                                    CE</option>
                                                <option value="DF" <?= $filtros['estado'] == 'DF' ? 'selected' : '' ?>>
                                                    DF</option>
                                                <option value="ES" <?= $filtros['estado'] == 'ES' ? 'selected' : '' ?>>
                                                    ES</option>
                                                <option value="GO" <?= $filtros['estado'] == 'GO' ? 'selected' : '' ?>>
                                                    GO</option>
                                                <option value="MA" <?= $filtros['estado'] == 'MA' ? 'selected' : '' ?>>
                                                    MA</option>
                                                <option value="MT" <?= $filtros['estado'] == 'MT' ? 'selected' : '' ?>>
                                                    MT</option>
                                                <option value="MS" <?= $filtros['estado'] == 'MS' ? 'selected' : '' ?>>
                                                    MS</option>
                                                <option value="MG" <?= $filtros['estado'] == 'MG' ? 'selected' : '' ?>>
                                                    MG</option>
                                                <option value="PA" <?= $filtros['estado'] == 'PA' ? 'selected' : '' ?>>
                                                    PA</option>
                                                <option value="PB" <?= $filtros['estado'] == 'PB' ? 'selected' : '' ?>>
                                                    PB</option>
                                                <option value="PR" <?= $filtros['estado'] == 'PR' ? 'selected' : '' ?>>
                                                    PR</option>
                                                <option value="PE" <?= $filtros['estado'] == 'PE' ? 'selected' : '' ?>>
                                                    PE</option>
                                                <option value="PI" <?= $filtros['estado'] == 'PI' ? 'selected' : '' ?>>
                                                    PI</option>
                                                <option value="RJ" <?= $filtros['estado'] == 'RJ' ? 'selected' : '' ?>>
                                                    RJ</option>
                                                <option value="RN" <?= $filtros['estado'] == 'RN' ? 'selected' : '' ?>>
                                                    RN</option>
                                                <option value="RS" <?= $filtros['estado'] == 'RS' ? 'selected' : '' ?>>
                                                    RS</option>
                                                <option value="RO" <?= $filtros['estado'] == 'RO' ? 'selected' : '' ?>>
                                                    RO</option>
                                                <option value="RR" <?= $filtros['estado'] == 'RR' ? 'selected' : '' ?>>
                                                    RR</option>
                                                <option value="SC" <?= $filtros['estado'] == 'SC' ? 'selected' : '' ?>>
                                                    SC</option>
                                                <option value="SP" <?= $filtros['estado'] == 'SP' ? 'selected' : '' ?>>
                                                    SP</option>
                                                <option value="SE" <?= $filtros['estado'] == 'SE' ? 'selected' : '' ?>>
                                                    SE</option>
                                                <option value="TO" <?= $filtros['estado'] == 'TO' ? 'selected' : '' ?>>
                                                    TO</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label for="data_inicio" class="form-label">Data Início</label>
                                            <input type="date" class="form-control" id="data_inicio" name="data_inicio"
                                                value="<?= htmlspecialchars($filtros['data_inicio']) ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label for="data_fim" class="form-label">Data Fim</label>
                                            <input type="date" class="form-control" id="data_fim" name="data_fim"
                                                value="<?= htmlspecialchars($filtros['data_fim']) ?>">
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search me-2"></i>Buscar
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary"
                                                onclick="limparFiltros()">
                                                <i class="fas fa-eraser me-2"></i>Limpar
                                            </button>
                                            <span class="ms-3 text-muted" id="totalUsuariosSpan">
                                                <i class="fas fa-info-circle me-1"></i>
                                                <?= number_format($totalUsuarios, 0, ',', '.') ?> usuário(s)
                                                encontrado(s)
                                            </span>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div> -->

                    <!-- Results -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-users me-2"></i>Lista de Usuários
                                    </h5>
                                    <div class="d-flex align-items-center">
                                        <button class="btn btn-success btn-sm" onclick="exportarDados()">
                                            <i class="fas fa-download me-2"></i>Exportar
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <!-- Loading -->
                                    <div class="loading" id="loading">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Carregando...</span>
                                        </div>
                                        <p class="mt-2">Carregando usuários...</p>
                                    </div>

                                    <!-- No Results -->
                                    <div class="no-results" id="noResults">
                                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                        <h5>Nenhum usuário encontrado</h5>
                                        <p class="text-muted">Tente ajustar os filtros de busca.</p>
                                    </div>

                                    <!-- Table -->
                                    <div class="table-responsive" id="tableContainer">
                                        <table class="table table-hover table-striped" id="usuariosTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Usuário</th>
                                                    <th>CPF</th>
                                                    <th>Contato</th>
                                                    <th>Localização</th>
                                                    <th>Cadastro</th>
                                                    <th>Dados</th>
                                                    <th>Formação</th>
                                                    <th>Ações</th>
                                                </tr>
                                            </thead>
                                            <tbody id="usuariosTableBody">
                                                <?php foreach ($usuarios as $usuario):
                                                    $dados = formatarDadosUsuario($usuario);
                                                ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="user-avatar me-3">
                                                                <?= strtoupper(substr($dados['nome_completo'], 0, 1)) ?>
                                                            </div>
                                                            <div>
                                                                <strong><?= $dados['nome_completo'] ?></strong>
                                                                <br>
                                                                <small class="text-muted"><?= $dados['email'] ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <code><?= $dados['cpf'] ? $dados['cpf'] : '' ?></code>
                                                        <br>
                                                        <small
                                                            class="text-muted"><?= $dados['data_nascimento'] ?></small>
                                                    </td>
                                                    <td>
                                                        <?php if (!$dados['celular'] && !$dados['telefone_fixo']): ?>
                                                        -
                                                        <?php else: ?>
                                                        <div>
                                                            <?php if ($dados['celular']): ?>
                                                            <i
                                                                class="fas fa-mobile-alt me-1"></i><?= $dados['celular'] ?><br>
                                                            <?php endif; ?>
                                                            <?php if ($dados['telefone_fixo']): ?>
                                                            <i
                                                                class="fas fa-phone me-1"></i><?= $dados['telefone_fixo'] ?>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?= $dados['cidade'] ?><br>
                                                        <small class="text-muted"><?= $dados['estado'] ?></small>
                                                    </td>
                                                    <td>
                                                        <?= $dados['data_cadastro'] ?>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex gap-1">
                                                            <span class="badge bg-primary" title="Cursos">
                                                                <i class="fas fa-graduation-cap"></i>
                                                                <?= $dados['total_cursos'] ?>
                                                            </span>
                                                            <span class="badge bg-success" title="Arquivos">
                                                                <i class="fas fa-file"></i>
                                                                <?= $dados['total_arquivos'] ?>
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="truncate-text"
                                                            title="<?= $dados['areas_formacao'] ?>">
                                                            <strong>Nível:</strong> <?= $dados['nivel'] ?><br>
                                                            <strong>Área:</strong> <?= $dados['areas_formacao'] ?><br>
                                                            <strong>Registro:</strong>
                                                            <?= $dados['registros_profissionais'] ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button type="button"
                                                                class="btn btn-outline-primary btn-action"
                                                                onclick="verDetalhes(<?= $dados['usuario_id'] ?>)"
                                                                title="Ver Detalhes">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <button type="button"
                                                                class="btn btn-outline-info btn-action"
                                                                onclick="editarUsuario(<?= $dados['usuario_id'] ?>)"
                                                                title="Editar">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button type="button"
                                                                class="btn btn-outline-danger btn-action"
                                                                onclick="excluirUsuario(<?= $dados['usuario_id'] ?>)"
                                                                title="Excluir">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
                    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
                    <!-- DataTables JS -->
                    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
                    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
                    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
                    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
                    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
                    <script>
                    $(document).ready(function() {
                        // Inicializar DataTable
                        var table = $('#usuariosTable').DataTable({
                            language: {
                                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
                            },
                            responsive: true,
                            lengthMenu: [10, 20, 50, 100],
                            pageLength: 20,
                            dom: '<"top"lf>rt<"bottom"ip>',
                            initComplete: function() {
                                $('#loading').hide();
                                $('#tableContainer').show();
                            }
                        });

                        // Configurar buscas automáticas em campos de texto
                        const inputFields = ['#termo', '#cidade'];
                        inputFields.forEach(id => {
                            $(id).on('input', function() {
                                clearTimeout(searchTimeout);
                                searchTimeout = setTimeout(() => {
                                    buscarUsuarios();
                                }, 500);
                            });
                        });

                        // Configurar buscas automáticas em campos de seleção e data
                        const changeFields = ['#estado', '#data_inicio', '#data_fim'];
                        changeFields.forEach(id => {
                            $(id).on('change', () => {
                                buscarUsuarios();
                            });
                        });

                        // Busca ao submeter o formulário
                        $('#searchForm').on('submit', function(e) {
                            e.preventDefault();
                            buscarUsuarios();
                        });
                    });

                    function buscarUsuarios() {
                        const formData = new FormData(document.getElementById('searchForm'));
                        const params = new URLSearchParams(formData);

                        $('#loading').show();
                        $('#tableContainer').hide();
                        $('#noResults').hide();

                        fetch('buscar_usuarios.php?' + params.toString())
                            .then(response => response.json())
                            .then(data => {
                                $('#loading').hide();

                                if (data.success) {
                                    if (data.usuarios.length > 0) {
                                        atualizarTabela(data.usuarios);
                                        updateTotal(data.totalUsuarios);
                                        $('#tableContainer').show();
                                    } else {
                                        $('#noResults').show();
                                    }
                                } else {
                                    $('#noResults').show();
                                    console.error('Erro na resposta:', data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Erro na busca:', error);
                                $('#loading').hide();
                                $('#noResults').show();
                            });
                    }

                    function atualizarTabela(usuarios) {
                        const table = $('#usuariosTable').DataTable();
                        table.clear().draw();

                        usuarios.forEach(usuario => {
                            const dados = {
                                usuario_id: usuario.usuario_id || 0,
                                nome_completo: usuario.nome_completo || 'Nome não informado',
                                email: usuario.email || 'Email não informado',
                                cpf: formatarCPF(usuario.cpf || ''),
                                data_nascimento: usuario.data_nascimento ? new Date(usuario.data_nascimento)
                                    .toLocaleDateString('pt-BR') : '-',
                                celular: usuario.celular ? formatarTelefone(usuario.celular) : '',
                                telefone_fixo: usuario.telefone_fixo ? formatarTelefone(usuario
                                    .telefone_fixo) : '',
                                cidade: usuario.cidade || '-',
                                estado: usuario.estado || '-',
                                data_cadastro: usuario.data_cadastro ? new Date(usuario.data_cadastro)
                                    .toLocaleString('pt-BR') : '-',
                                total_cursos: usuario.total_cursos || 0,
                                total_arquivos: usuario.total_arquivos || 0,
                                nivel: usuario.nivel || '-',
                                areas_formacao: usuario.areas_formacao || '-',
                                registros_profissionais: usuario.registros_profissionais || '-'
                            };

                            const contatoContent = (!dados.celular && !dados.telefone_fixo) ? '-' : `
                                <div>
                                    ${dados.celular ? `<i class="fas fa-mobile-alt me-1"></i>${dados.celular}<br>` : ''}
                                    ${dados.telefone_fixo ? `<i class="fas fa-phone me-1"></i>${dados.telefone_fixo}` : ''}
                                </div>
                            `;

                            const cpfContent = dados.cpf ? dados.cpf : '-';

                            const rowNode = table.row.add([
                                `
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar me-3">
                                        ${dados.nome_completo.charAt(0).toUpperCase()}
                                    </div>
                                    <div>
                                        <strong>${dados.nome_completo}</strong>
                                        <br>
                                        <small class="text-muted">${dados.email}</small>
                                    </div>
                                </div>
                                `,
                                `
                                <code>${cpfContent}</code>
                                <br>
                                <small class="text-muted">${dados.data_nascimento}</small>
                                `,
                                contatoContent,
                                `
                                ${dados.cidade}<br>
                                <small class="text-muted">${dados.estado}</small>
                                `,
                                dados.data_cadastro,
                                `
                                <div class="d-flex gap-1">
                                    <span class="badge bg-primary" title="Cursos">
                                        <i class="fas fa-graduation-cap"></i> ${dados.total_cursos}
                                    </span>
                                    <span class="badge bg-success" title="Arquivos">
                                        <i class="fas fa-file"></i> ${dados.total_arquivos}
                                    </span>
                                </div>
                                `,
                                `
                                <div class="truncate-text" title="${dados.areas_formacao}">
                                    <strong>Nível:</strong> ${dados.nivel}<br>
                                    <strong>Área:</strong> ${dados.areas_formacao}<br>
                                    <strong>Registro:</strong> ${dados.registros_profissionais}
                                </div>
                                `,
                                `
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-primary btn-action"
                                            onclick="verDetalhes(${dados.usuario_id})" title="Ver Detalhes">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-info btn-action"
                                            onclick="editarUsuario(${dados.usuario_id})" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-action"
                                            onclick="excluirUsuario(${dados.usuario_id})" title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                `
                            ]).draw(false).node();
                        });
                    }

                    function updateTotal(total) {
                        const totalSpan = document.getElementById('totalUsuariosSpan');
                        if (totalSpan) {
                            totalSpan.innerHTML =
                                `<i class="fas fa-info-circle me-1"></i> ${total.toLocaleString('pt-BR')} usuário(s) encontrado(s)`;
                        }
                    }

                    function formatarCPF(cpf) {
                        if (!cpf) return '';
                        return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                    }

                    function formatarTelefone(telefone) {
                        if (!telefone) return '';
                        const clean = telefone.replace(/\D/g, '');
                        if (clean.length === 11) {
                            return clean.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
                        } else if (clean.length === 10) {
                            return clean.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
                        }
                        return telefone;
                    }

                    function limparFiltros() {
                        document.getElementById('searchForm').reset();
                        buscarUsuarios();
                    }

                    function verDetalhes(usuarioId) {
                        fetch(`detalhes_usuario.php?id=${usuarioId}`)
                            .then(response => response.text())
                            .then(html => {
                                document.getElementById('detalhesContent').innerHTML = html;
                                new bootstrap.Modal(document.getElementById('detalhesModal')).show();
                            })
                            .catch(error => {
                                console.error('Erro ao carregar detalhes:', error);
                                alert('Erro ao carregar detalhes do usuário.');
                            });
                    }

                    function editarUsuario(usuarioId) {
                        window.location.href = `editar_usuario.php?id=${usuarioId}`;
                    }

                    function excluirUsuario(usuarioId) {
                        if (confirm('Tem certeza que deseja excluir este usuário? Esta ação não pode ser desfeita.')) {
                            fetch('excluir_usuario.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                    },
                                    body: JSON.stringify({
                                        id: usuarioId
                                    })
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        alert('Usuário excluído com sucesso!');
                                        buscarUsuarios();
                                    } else {
                                        alert('Erro ao excluir usuário: ' + data.message);
                                    }
                                })
                                .catch(error => {
                                    console.error('Erro:', error);
                                    alert('Erro ao excluir usuário.');
                                });
                        }
                    }

                    function exportarDados() {
                        const formData = new FormData(document.getElementById('searchForm'));
                        const params = new URLSearchParams(formData);
                        window.open('exportar_usuarios.php?' + params.toString(), '_blank');
                    }
                    </script>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Detalhes -->
    <div class="modal fade" id="detalhesModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalhes do Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detalhesContent">
                    <!-- Conteúdo carregado via AJAX -->
                </div>
            </div>
        </div>
    </div>
</body>

</html>