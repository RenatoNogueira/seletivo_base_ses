<?php
session_start();
require_once '../config/database.php';
require_once 'functions.php';

// Verificar se está logado
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}


function encurtarNomeArquivo($nome, $limite = 30)
{
    if (mb_strlen($nome) <= $limite) {
        return $nome;
    }
    $extensao = pathinfo($nome, PATHINFO_EXTENSION);
    $nomeSemExt = pathinfo($nome, PATHINFO_FILENAME);

    // Mantém início e final, corta no meio
    $inicio = mb_substr($nomeSemExt, 0, $limite - mb_strlen($extensao) - 5);
    $fim = mb_substr($nomeSemExt, -5);
    return $inicio . '...' . $fim . '.' . $extensao;
}

// Função auxiliar para acesso seguro a arrays
function safeGet($array, $key, $default = null)
{
    return isset($array[$key]) ? $array[$key] : $default;
}

// Processar parâmetro de busca se existir
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// Consulta para buscar arquivos agrupados por usuário
$sql = "
    SELECT
        u.id AS usuario_id,
        u.nome_completo,
        u.cpf,
        COUNT(au.id) AS total_arquivos,
        GROUP_CONCAT(DISTINCT au.tipo_documento SEPARATOR ', ') AS tipos_documentos,
        MAX(au.uploaded_at) AS ultimo_envio
    FROM usuarios u
    LEFT JOIN formularios f ON f.usuario_id = u.id
    LEFT JOIN arquivos_upload au ON au.formulario_id = f.id
    WHERE 1=1
";

// Adicionar condições de busca se houver termo
if (!empty($searchTerm)) {
    $sql .= " AND (
        u.nome_completo LIKE :search OR
        u.cpf LIKE :search OR
        au.tipo_documento LIKE :search OR
        au.nome_original LIKE :search
    )";
}

$sql .= " GROUP BY u.id ORDER BY ultimo_envio DESC, u.nome_completo ASC";

$stmt = $pdo->prepare($sql);

// Bind do parâmetro de busca se existir
if (!empty($searchTerm)) {
    $searchParam = "%$searchTerm%";
    $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
}

$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contagem total de arquivos
$totalArquivos = $pdo->query("SELECT COUNT(*) FROM arquivos_upload")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Arquivos | Sistema Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
    :root {
        --primary-color: #4e73df;
        --secondary-color: #858796;
        --success-color: #1cc88a;
        --info-color: #36b9cc;
        --warning-color: #f6c23e;
        --danger-color: #e74a3b;
        --light-color: #f8f9fc;
        --dark-color: #5a5c69;
    }

    body {
        background-color: #f8f9fc;
        font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    }

    .navbar-brand {
        font-weight: 800;
        font-size: 1.5rem;
    }

    .card {
        border: none;
        border-radius: 0.35rem;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }

    .card-header {
        background-color: #f8f9fc;
        border-bottom: 1px solid #e3e6f0;
        padding: 1rem 1.35rem;
    }

    .card-header h6 {
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.85rem;
        color: #4e73df;
    }

    .accordion-button:not(.collapsed) {
        background-color: rgba(78, 115, 223, 0.05);
        color: var(--primary-color);
        box-shadow: none;
    }

    .accordion-button:focus {
        box-shadow: none;
        border-color: rgba(0, 0, 0, 0.125);
    }

    .badge-documento {
        background-color: var(--secondary-color);
        color: white;
    }

    .table-details {
        font-size: 0.85rem;
    }

    .table-details th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.7rem;
        letter-spacing: 0.5px;
        color: var(--dark-color);
    }

    .file-missing {
        color: var(--danger-color);
        text-decoration: line-through;
    }

    .status-badge {
        font-size: 0.7rem;
        font-weight: 700;
        padding: 0.35rem 0.65rem;
        border-radius: 0.25rem;
    }

    .btn-download-all {
        background-color: var(--success-color);
        border-color: var(--success-color);
    }

    .btn-download-all:hover {
        background-color: #17a673;
        border-color: #17a673;
    }

    .search-container {
        max-width: 300px;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: var(--primary-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 1rem;
    }

    .breadcrumb {
        background-color: transparent;
        padding: 0;
    }

    .page-title {
        font-weight: 700;
        color: var(--dark-color);
    }

    .stats-card {
        border-left: 0.25rem solid var(--primary-color);
    }

    .stats-card .card-body {
        padding: 1rem 1.5rem;
    }

    .stats-card .stats-icon {
        font-size: 1.5rem;
        color: #dddfeb;
    }

    .stats-card.primary {
        border-left-color: var(--primary-color);
    }

    .stats-card.success {
        border-left-color: var(--success-color);
    }

    .file-icon {
        font-size: 1.25rem;
        margin-right: 0.5rem;
        color: var(--secondary-color);
    }

    .file-preview-modal .modal-body img {
        max-width: 100%;
        max-height: 80vh;
        display: block;
        margin: 0 auto;
    }

    .action-buttons .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
    }

    .no-results {
        display: none;
        padding: 1rem;
        text-align: center;
        color: #6c757d;
    }

    .search-highlight {
        background-color: #fff3cd;
        font-weight: bold;
    }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-cloud-arrow-up-fill me-2"></i>Arquivos
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                            data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>
                            <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <!-- <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Perfil</a></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Configurações</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li> -->
                            <li><a class="dropdown-item" href="logout.php"><i
                                        class="bi bi-box-arrow-right me-2"></i>Sair</a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Breadcrumb e Título -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Documentos</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-0 page-title">
                    <i class="bi bi-files me-2"></i>Gerenciamento de Documentos
                </h1>
            </div>
            <!-- <div class="search-container">
                <form method="GET" action="" class="input-group">
                    <input type="text" name="search" id="searchInput" class="form-control form-control-sm"
                        placeholder="Pesquisar..." value="<?= htmlspecialchars($searchTerm) ?>">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                    <?php if (!empty($searchTerm)): ?>
                    <a href="?" class="btn btn-outline-danger" title="Limpar busca">
                        <i class="bi bi-x-lg"></i>
                    </a>
                    <?php endif; ?>
                </form>
            </div> -->
        </div>

        <!-- Cards de Estatísticas -->
        <div class="row mb-4">
            <div class="col-xl-6 col-md-6 mb-4">
                <div class="card stats-card primary h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Usuários com Documentos</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count($usuarios) ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-people-fill stats-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-md-6 mb-4">
                <div class="card stats-card success h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Total de Documentos</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalArquivos ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-file-earmark-text stats-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Usuários -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="bi bi-list-ul me-2"></i>Documentos por Usuário
                </h6>
            </div>
            <div class="card-body">
                <?php if (empty($usuarios)): ?>
                <div class="alert alert-warning mb-0">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?= empty($searchTerm) ? 'Nenhum documento enviado até o momento.' : 'Nenhum resultado encontrado para sua busca.' ?>
                </div>
                <?php else: ?>
                <div class="no-results alert alert-info mb-3">
                    <i class="bi bi-info-circle me-2"></i> Nenhum resultado encontrado para
                    "<?= htmlspecialchars($searchTerm) ?>"
                </div>

                <div class="accordion" id="usuariosAccordion">
                    <?php foreach ($usuarios as $usuario): ?>
                    <div class="accordion-item mb-3 border-0 rounded-3 shadow-sm">
                        <h2 class="accordion-header" id="heading<?= $usuario['usuario_id'] ?>">
                            <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapse<?= $usuario['usuario_id'] ?>" aria-expanded="false"
                                aria-controls="collapse<?= $usuario['usuario_id'] ?>">
                                <div class="d-flex w-100 align-items-center">
                                    <div class="user-avatar me-3">
                                        <?= strtoupper(substr($usuario['nome_completo'], 0, 1)) ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex flex-column">
                                            <span
                                                class="fw-bold"><?= highlightSearchTerm(htmlspecialchars($usuario['nome_completo']), $searchTerm) ?></span>
                                            <span class="text-muted small">CPF: <?= formatCPF($usuario['cpf']) ?></span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-primary rounded-pill me-2">
                                            <i class="bi bi-file-earmark me-1"></i> <?= $usuario['total_arquivos'] ?>
                                        </span>
                                        <?php if ($usuario['tipos_documentos']): ?>
                                        <span class="badge bg-info rounded-pill">
                                            <i class="bi bi-tags me-1"></i>
                                            <?= highlightSearchTerm(htmlspecialchars($usuario['tipos_documentos']), $searchTerm) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="collapse<?= $usuario['usuario_id'] ?>" class="accordion-collapse collapse"
                            aria-labelledby="heading<?= $usuario['usuario_id'] ?>" data-bs-parent="#usuariosAccordion">
                            <div class="accordion-body p-0">
                                <div class="d-flex justify-content-between align-items-center p-3 bg-light">
                                    <h6 class="mb-0 text-dark">
                                        <i class="bi bi-folder2-open me-2"></i>Documentos Enviados
                                    </h6>
                                    <div>
                                        <a href="gerar_zip_usuario.php?id=<?= $usuario['usuario_id'] ?>"
                                            class="btn btn-sm btn-download-all">
                                            <i class="bi bi-file-earmark-zip me-1"></i> Baixar Todos
                                        </a>
                                    </div>
                                </div>

                                <?php
                                        // Buscar arquivos específicos deste usuário
                                        $sqlArquivos = "
                                    SELECT
                                        au.id AS arquivo_id,
                                        au.nome_original,
                                        au.nome_salvo,
                                        au.caminho_arquivo,
                                        au.tamanho,
                                        au.tipo_mime,
                                        au.uploaded_at,
                                        au.tipo_documento,
                                        f.id AS formulario_id
                                    FROM arquivos_upload au
                                    INNER JOIN formularios f ON f.id = au.formulario_id
                                    WHERE f.usuario_id = ?
                                    ORDER BY au.uploaded_at DESC
                                ";
                                        $stmtArquivos = $pdo->prepare($sqlArquivos);
                                        $stmtArquivos->execute([$usuario['usuario_id']]);
                                        $arquivos = $stmtArquivos->fetchAll(PDO::FETCH_ASSOC);
                                        ?>

                                <div class="table-responsive">
                                    <table class="table table-details mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Tipo</th>
                                                <th>Documento</th>
                                                <th>Tamanho</th>
                                                <th>Envio</th>
                                                <th class="text-end">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($arquivos as $arq):
                                                        $tamanho = safeGet($arq, 'tamanho', 0);
                                                        $caminhoRelativo = safeGet($arq, 'caminho_arquivo', '');

                                                        // Normalizar caminho do arquivo
                                                        $caminhoRelativo = str_replace('\\', '/', $caminhoRelativo);

                                                        // Verificar se já começa com 'uploads/'
                                                        if (strpos($caminhoRelativo, 'uploads/') === 0) {
                                                            $caminhoCompleto = '../' . $caminhoRelativo;
                                                        } else {
                                                            $caminhoCompleto = '../uploads/' . $caminhoRelativo;
                                                        }

                                                        // Verificar se o arquivo existe fisicamente
                                                        $caminhoFisico = realpath(dirname(__FILE__) . '/' . $caminhoCompleto);
                                                        $arquivoExiste = file_exists($caminhoFisico);

                                                        // Obter ícone baseado no tipo MIME
                                                        $fileIcon = getFileIcon($arq['tipo_mime']);
                                                    ?>
                                            <tr>
                                                <td>
                                                    <?php if ($arq['tipo_documento']): ?>
                                                    <span class="badge badge-documento">
                                                        <?= highlightSearchTerm(htmlspecialchars($arq['tipo_documento']), $searchTerm) ?>
                                                    </span>
                                                    <?php else: ?>
                                                    <span class="text-muted small">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="<?= !$arquivoExiste ? 'file-missing' : '' ?>">
                                                    <i class="bi <?= $fileIcon ?> file-icon"></i>
                                                    <?= highlightSearchTerm(
                                                                    htmlspecialchars(encurtarNomeArquivo($arq['nome_original'])),
                                                                    $searchTerm
                                                                ) ?>
                                                    <?php if (!$arquivoExiste): ?>
                                                    <span class="badge bg-danger ms-2 status-badge">Não
                                                        encontrado</span>
                                                    <?php endif; ?>
                                                </td>

                                                <td><?= formatFileSize($tamanho) ?></td>
                                                <td><?= date('d/m/Y H:i', strtotime($arq['uploaded_at'])) ?></td>
                                                <td class="text-end action-buttons">
                                                    <div class="btn-group btn-group-sm">
                                                        <?php if ($arquivoExiste): ?>
                                                        <a href="<?= htmlspecialchars($caminhoCompleto) ?>"
                                                            target="_blank" class="btn btn-outline-primary"
                                                            title="Baixar">
                                                            <i class="bi bi-download"></i>
                                                        </a>
                                                        <button class="btn btn-outline-secondary" title="Visualizar"
                                                            data-bs-toggle="modal" data-bs-target="#filePreviewModal"
                                                            data-file="<?= htmlspecialchars($caminhoCompleto) ?>"
                                                            data-type="<?= htmlspecialchars($arq['tipo_mime']) ?>">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        <?php else: ?>
                                                        <button class="btn btn-outline-secondary" disabled
                                                            title="Arquivo não encontrado">
                                                            <i class="bi bi-download"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                        <!-- <a href="excluir_arquivo.php?id=<?= $arq['arquivo_id'] ?>"
                                                            class="btn btn-outline-danger"
                                                            onclick="return confirm('Tem certeza que deseja excluir este arquivo?')"
                                                            title="Excluir">
                                                            <i class="bi bi-trash"></i>
                                                        </a> -->
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
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal de Visualização -->
    <div class="modal fade" id="filePreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Visualização do Documento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body file-preview-modal text-center">
                    <div id="filePreviewContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <a href="#" id="downloadPreview" class="btn btn-primary">
                        <i class="bi bi-download me-1"></i> Baixar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="sticky-footer bg-white mt-4">
        <div class="container my-auto">
            <div class="copyright text-center my-auto">
                <span>Copyright &copy; Sistema de Gerenciamento <?= date('Y') ?></span>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
    $(document).ready(function() {
        // Verificar se há termo de busca e mostrar mensagem se não houver resultados
        const searchTerm = "<?= $searchTerm ?>";
        if (searchTerm && $(".accordion-item").length === 0) {
            $(".no-results").show();
        } else {
            $(".no-results").hide();
        }

        // Função para destacar texto nos resultados
        function highlightText(element, searchText) {
            if (!searchText) return;

            const text = $(element).text();
            const regex = new RegExp(searchText, 'gi');
            const highlighted = text.replace(regex, match =>
                `<span class="search-highlight">${match}</span>`
            );

            $(element).html(highlighted);
        }

        // Aplicar destaque nos resultados da busca do servidor
        if (searchTerm) {
            $('.accordion-item').each(function() {
                highlightText($(this).find('.fw-bold'), searchTerm);
                highlightText($(this).find('.badge-documento'), searchTerm);
                highlightText($(this).find('td:nth-child(2)'), searchTerm);
            });
        }

        // Busca em tempo real nos acordeões
        $('#searchInput').on('input', function() {
            const searchText = $(this).val().toLowerCase();

            if (searchText.length === 0) {
                $('.accordion-item').show();
                $('.no-results').hide();
                return;
            }

            let hasResults = false;

            $('.accordion-item').each(function() {
                const userText = $(this).text().toLowerCase();
                if (userText.includes(searchText)) {
                    $(this).show();
                    hasResults = true;

                    // Destacar texto encontrado
                    highlightText($(this).find('.fw-bold'), searchText);
                    highlightText($(this).find('.badge-documento'), searchText);
                    highlightText($(this).find('td:nth-child(2)'), searchText);

                    // Expandir o item se corresponder à busca
                    const collapseId = $(this).find('.accordion-button').attr('data-bs-target');
                    $(collapseId).addClass('show');
                    $(this).find('.accordion-button').removeClass('collapsed');
                } else {
                    $(this).hide();
                }
            });

            if (hasResults) {
                $('.no-results').hide();
            } else {
                $('.no-results').text('Nenhum resultado encontrado para "' + searchText + '"');
                $('.no-results').show();
            }
        });

        // Configuração das tabelas internas
        $('.table-details').each(function() {
            $(this).DataTable({
                searching: false,
                paging: false,
                info: false,
                ordering: false,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
                }
            });
        });

        // Modal de visualização
        $('#filePreviewModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var fileUrl = button.data('file');
            var fileType = button.data('type');
            var modal = $(this);

            modal.find('#downloadPreview').attr('href', fileUrl);

            $('#filePreviewContent').html('');

            if (fileType.startsWith('image/')) {
                $('#filePreviewContent').html('<img src="' + fileUrl + '" class="img-fluid">');
            } else if (fileType === 'application/pdf') {
                $('#filePreviewContent').html(
                    '<embed src="' + fileUrl +
                    '" type="application/pdf" width="100%" height="600px">');
            } else {
                $('#filePreviewContent').html(
                    '<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Visualização não disponível para este tipo de arquivo</div>'
                );
            }
        });

        // Tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
    </script>
</body>

</html>

<?php
// Função auxiliar para obter ícone baseado no tipo MIME
function getFileIcon($mimeType)
{
    if (strpos($mimeType, 'image/') === 0) return 'bi-file-image';
    if (strpos($mimeType, 'application/pdf') === 0) return 'bi-file-pdf';
    if (
        strpos($mimeType, 'application/msword') === 0 ||
        strpos($mimeType, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') === 0
    ) {
        return 'bi-file-word';
    }
    if (
        strpos($mimeType, 'application/vnd.ms-excel') === 0 ||
        strpos($mimeType, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') === 0
    ) {
        return 'bi-file-excel';
    }
    if (
        strpos($mimeType, 'application/zip') === 0 ||
        strpos($mimeType, 'application/x-rar-compressed') === 0
    ) {
        return 'bi-file-zip';
    }
    return 'bi-file-earmark';
}

// Função para formatar CPF
function formatCPF($cpf)
{
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

// Função para destacar termos de busca no texto
function highlightSearchTerm($text, $searchTerm)
{
    if (empty($searchTerm)) return $text;

    $pattern = '/(' . preg_quote($searchTerm, '/') . ')/i';
    return preg_replace($pattern, '<span class="search-highlight">$1</span>', $text);
}
?>