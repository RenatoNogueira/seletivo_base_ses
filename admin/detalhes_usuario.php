<?php
session_start();
require_once '../config/database.php';
require_once 'functions.php';

// Verificar se está logado
if (!isset($_SESSION['admin_id'])) {
    echo '<div class="alert alert-danger">Acesso não autorizado.</div>';
    exit;
}

$usuarioId = intval($_GET['id'] ?? 0);

if (!$usuarioId) {
    echo '<div class="alert alert-danger">ID do usuário não fornecido.</div>';
    exit;
}

// Função auxiliar para acesso seguro a arrays
function safeGet($array, $key, $default = null)
{
    return isset($array[$key]) ? $array[$key] : $default;
}

// Buscar dados do usuário
$usuario = obterUsuario($pdo, $usuarioId);


if (!$usuario) {
    echo '<div class="alert alert-warning">Usuário não encontrado.</div>';
    exit;
}

// Buscar arquivos e cursos se houver formulário
// Função para obter o caminho completo do arquivo - ATUALIZADA
function getCaminhoCompleto($caminhoRelativo)
{
    if (empty($caminhoRelativo)) {
        return '';
    }

    // Remove qualquer barra inicial para evitar duplicação
    $caminhoRelativo = ltrim($caminhoRelativo, '/\\');

    // Verifica se o caminho já contém 'uploads'
    if (strpos($caminhoRelativo, 'uploads/') === 0) {
        return '/' . $caminhoRelativo;
    }

    return '/uploads/' . $caminhoRelativo;
}


$arquivos = [];
$cursos = [];
$formularioId = safeGet($usuario, 'formulario_id');

if ($formularioId) {
    // DEBUG - Verificar o formulario_id
    error_log("Buscando cursos para formulario_id: " . $formularioId);

    $arquivos = obterArquivosUsuario($pdo, $formularioId);
    $cursos = obterCursosUsuario($pdo, $formularioId);

    // DEBUG - Verificar os dados retornados
    error_log("Número de cursos encontrados: " . count($cursos));
    error_log(print_r($cursos, true));
}



if (safeGet($usuario, 'formulario_id')) {
    $arquivos = obterArquivosUsuario($pdo, $usuario['formulario_id']);
    $cursos = obterCursosUsuario($pdo, $usuario['formulario_id']);
}

// Registrar log
registrarLog($pdo, 'visualizar_usuario', "Visualização de detalhes do usuário ID: {$usuarioId}");

// Função para formatar tamanho de arquivo
function formatarTamanho($bytes)
{
    if ($bytes == 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes, $k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
?>

<div class="row">
    <!-- Informações Pessoais -->
    <div class="col-md-6 mb-4">
        <h6 class="text-primary mb-3">
            <i class="fas fa-user me-2"></i>Informações Pessoais
        </h6>
        <table class="table table-sm">
            <tr>
                <td><strong>Nome Completo:</strong></td>
                <td><?= sanitizar(safeGet($usuario, 'nome_completo', 'Não informado')) ?></td>
            </tr>
            <tr>
                <td><strong>CPF:</strong></td>
                <td><code><?= formatarCPF(safeGet($usuario, 'cpf', '')) ?></code></td>
            </tr>
            <tr>
                <td><strong>RG:</strong></td>
                <td><?= sanitizar(safeGet($usuario, 'rg', 'Não informado')) ?></td>
            </tr>
            <tr>
                <td><strong>Data de Nascimento:</strong></td>
                <td><?= safeGet($usuario, 'data_nascimento') ? date('d/m/Y', strtotime($usuario['data_nascimento'])) : 'Não informado' ?>
                </td>
            </tr>
            <tr>
                <td><strong>Estado Civil:</strong></td>
                <td><?= sanitizar(safeGet($usuario, 'estado_civil', 'Não informado')) ?></td>
            </tr>
            <tr>
                <td><strong>Nacionalidade:</strong></td>
                <td><?= sanitizar(safeGet($usuario, 'nacionalidade', 'Não informado')) ?></td>
            </tr>
        </table>
    </div>

    <!-- Contato -->
    <div class="col-md-6 mb-4">
        <h6 class="text-success mb-3">
            <i class="fas fa-phone me-2"></i>Contato
        </h6>
        <table class="table table-sm">
            <tr>
                <td><strong>Email Principal:</strong></td>
                <td><?= sanitizar(safeGet($usuario, 'email', 'Não informado')) ?></td>
            </tr>
            <tr>
                <td><strong>Email Alternativo:</strong></td>
                <td><?= sanitizar(safeGet($usuario, 'email_alternativo', 'Não informado')) ?></td>
            </tr>
            <tr>
                <td><strong>Telefone Fixo:</strong></td>
                <td><?= formatarTelefone(safeGet($usuario, 'telefone_fixo', '')) ?></td>
            </tr>
            <tr>
                <td><strong>Celular:</strong></td>
                <td><?= formatarTelefone(safeGet($usuario, 'celular', '')) ?></td>
            </tr>
            <tr>
                <td><strong>Data de Cadastro:</strong></td>
                <td><?= formatarData(safeGet($usuario, 'data_cadastro', '')) ?></td>
            </tr>
        </table>
    </div>
</div>

<?php if (safeGet($usuario, 'formulario_id')): ?>
<!-- Endereço -->
<div class="row">
    <div class="col-12 mb-4">
        <h6 class="text-info mb-3">
            <i class="fas fa-map-marker-alt me-2"></i>Endereço
        </h6>
        <table class="table table-sm">
            <tr>
                <td><strong>CEP:</strong></td>
                <td><?= sanitizar(safeGet($usuario, 'cep', 'Não informado')) ?></td>
            </tr>
            <tr>
                <td><strong>Logradouro:</strong></td>
                <td><?= sanitizar(safeGet($usuario, 'logradouro', 'Não informado')) ?></td>
            </tr>
            <tr>
                <td><strong>Número:</strong></td>
                <td><?= sanitizar(safeGet($usuario, 'numero', 'Não informado')) ?></td>
            </tr>
            <tr>
                <td><strong>Complemento:</strong></td>
                <td><?= sanitizar(safeGet($usuario, 'complemento', 'Não informado')) ?></td>
            </tr>
            <tr>
                <td><strong>Bairro:</strong></td>
                <td><?= sanitizar(safeGet($usuario, 'bairro', 'Não informado')) ?></td>
            </tr>
            <tr>
                <td><strong>Cidade:</strong></td>
                <td><?= sanitizar(safeGet($usuario, 'cidade', 'Não informado')) ?></td>
            </tr>
            <tr>
                <td><strong>Estado:</strong></td>
                <td><?= sanitizar(safeGet($usuario, 'estado', 'Não informado')) ?></td>
            </tr>
        </table>
    </div>
</div>

<!-- Link do Vídeo -->
<?php if (safeGet($usuario, 'link_video')): ?>
<div class="row">
    <div class="col-12 mb-4">
        <h6 class="text-warning mb-3">
            <i class="fas fa-video me-2"></i>Vídeo de Apresentação
        </h6>
        <div class="alert alert-info p-3">
            <div class="d-flex align-items-center">
                <strong class="me-2">Link:</strong>
                <a href="<?= sanitizar($usuario['link_video']) ?>" target="_blank"
                    class="alert-link text-truncate d-inline-block" style="max-width: 80%">
                    <?= sanitizar($usuario['link_video']) ?>
                    <i class="fas fa-external-link-alt ms-1"></i>
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Cursos e Formações -->
<?php if ($formularioId): ?>
<div class="row">
    <div class="col-12 mb-4">
        <h6 class="text-primary mb-3">
            <i class="fas fa-graduation-cap me-2"></i>Cursos e Formações
        </h6>

        <?php if (!empty($cursos)): ?>
        <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>Área de Formação</th>
                        <th>Registro</th>
                        <th>Instituição</th>
                        <th>Ano Conclusão</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cursos as $curso): ?>
                    <tr>
                        <td><?= htmlspecialchars($curso['nivel'] ?? '') ?></td>
                        <td><?= htmlspecialchars($curso['area_formacao'] ?? '') ?></td>
                        <td><?= htmlspecialchars($curso['registro_profissional'] ?? '') ?></td>
                        <td><?= htmlspecialchars($curso['instituicao'] ?? '') ?></td>
                        <td><?= htmlspecialchars($curso['ano_conclusao'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="alert alert-info">Nenhum curso registrado</div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>



<!-- Programa Gestão em Saúde (PGS) -->
<?php if (!empty($usuario['objetivo_pgs']) || !empty($usuario['atividades_pgs']) || !empty($usuario['contribuicao_pgs'])): ?>
<div class="row">
    <div class="col-12 mb-4">
        <h6 class="text-purple mb-3">
            <i class="fas fa-heartbeat me-2"></i>Programa Gestão em Saúde (PGS)
        </h6>

        <?php if (!empty($usuario['objetivo_pgs'])): ?>
        <div class="card mb-3">
            <div class="card-header bg-purple text-white">
                <strong>Objetivo no PGS</strong>
            </div>
            <div class="card-body">
                <p><?= nl2br(htmlspecialchars($usuario['objetivo_pgs'])) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($usuario['atividades_pgs'])): ?>
        <div class="card mb-3">
            <div class="card-header bg-purple text-white">
                <strong>Atividades e Funções no PGS</strong>
            </div>
            <div class="card-body">
                <p><?= nl2br(htmlspecialchars($usuario['atividades_pgs'])) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($usuario['contribuicao_pgs'])): ?>
        <div class="card">
            <div class="card-header bg-purple text-white">
                <strong>Contribuição para a Gestão da Saúde Pública</strong>
            </div>
            <div class="card-body">
                <p><?= nl2br(htmlspecialchars($usuario['contribuicao_pgs'])) ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>


<!-- Arquivos Enviados -->
<?php if (!empty($arquivos)): ?>
<div class="row">
    <div class="col-12 mb-4">
        <h6 class="text-success mb-3">
            <i class="fas fa-folder me-2"></i>Arquivos Enviados (<?= count($arquivos) ?>)
        </h6>
        <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead class="table-light">
                    <tr>
                        <th>Nome do Arquivo</th>
                        <th>Tipo de Documento</th>
                        <th>Tamanho</th>
                        <th>Data de Upload</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>

                    <?php foreach ($arquivos as $arquivo):
                                $tamanho = safeGet($arquivo, 'tamanho_arquivo', 0);
                                $caminhoRelativo = safeGet($arquivo, 'caminho_arquivo', '');

                                // Normaliza o caminho
                                $caminhoRelativo = ltrim(str_replace('\\', '/', $caminhoRelativo), '/');

                                // Verifica se já contém 'uploads/'
                                if (strpos($caminhoRelativo, 'uploads/') === 0) {
                                    $caminhoCompleto = '../' . $caminhoRelativo;
                                } else {
                                    $caminhoCompleto = '../uploads/' . $caminhoRelativo;
                                }

                                $caminhoFisico = $_SERVER['DOCUMENT_ROOT'] . $caminhoCompleto;
                            ?>
                    <tr>
                        <td>
                            <i class="fas fa-file-pdf text-danger me-2"></i>
                            <?= sanitizar(safeGet($arquivo, 'nome_arquivo', 'Arquivo sem nome')) ?>
                        </td>
                        <td>
                            <span
                                class="badge bg-info"><?= sanitizar(safeGet($arquivo, 'tipo_documento', 'Não classificado')) ?></span>
                        </td>
                        <td><?= formatarTamanho($tamanho) ?></td>
                        <td><?= formatarData(safeGet($arquivo, 'data_upload', '')) ?></td>
                        <td>
                            <?php if (safeGet($arquivo, 'caminho_arquivo')): ?>
                            <a href="<?= $caminhoCompleto ?>" target="_blank" class="btn btn-outline-primary btn-sm"
                                title="Visualizar">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="<?= $caminhoCompleto ?>" download class="btn btn-outline-success btn-sm"
                                title="Download">
                                <i class="fas fa-download"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php else: ?>
<!-- Usuário sem formulário -->
<div class="row">
    <div class="col-12">
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Formulário não preenchido:</strong> Este usuário ainda não preencheu o formulário completo.
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Resumo -->
<div class="row">
    <div class="col-12">
        <div class="card bg-light">
            <div class="card-body">
                <h6 class="card-title">
                    <i class="fas fa-chart-pie me-2"></i>Resumo
                </h6>
                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="border-end">
                            <h4 class="text-primary"><?= safeGet($usuario, 'total_cursos', 0) ?></h4>
                            <small class="text-muted">Cursos</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border-end">
                            <h4 class="text-success"><?= safeGet($usuario, 'total_arquivos', 0) ?></h4>
                            <small class="text-muted">Arquivos</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border-end">
                            <h4 class="text-info"><?= safeGet($usuario, 'formulario_id') ? 'Sim' : 'Não' ?></h4>
                            <small class="text-muted">Formulário</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <h4 class="text-warning"><?= safeGet($usuario, 'link_video') ? 'Sim' : 'Não' ?></h4>
                        <small class="text-muted">Vídeo</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Ações -->
<div class="row mt-4">
    <div class="col-12 text-end">
        <a href="editar_usuario.php?id=<?= $usuarioId ?>" class="btn btn-primary disabled">
            <i class="fas fa-edit me-2"></i>Editar Usuário
        </a>
        <a type="button" target=”_blank” rel=”noopener” class="btn btn-success"
            href="relatorio_usuario.php?id=<?= $usuarioId ?>" ?>
            <i class="fas fa-file-pdf me-2"></i>Gerar Relatório
        </a>
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times me-2"></i>Fechar
        </button>
    </div>
</div>