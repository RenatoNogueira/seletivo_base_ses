<?php
session_start();
require_once '../config/database.php';
require_once 'functions.php';

// Verificar se está logado
if (!isset($_SESSION['admin_id'])) {
    echo '<div class="alert alert-danger">Acesso não autorizado.</div>';
    exit;
}

// Consulta para buscar arquivos com dados do usuário
$sql = "
    SELECT
        au.id AS arquivo_id,
        au.nome_original,
        au.nome_salvo,
        au.caminho_arquivo,
        au.tamanho,
        au.tipo_mime,
        au.uploaded_at,
        au.tipo_documento,
        u.nome_completo,
        u.cpf,
        f.id AS formulario_id
    FROM arquivos_upload au
    INNER JOIN formularios f ON f.id = au.formulario_id
    INNER JOIN usuarios u ON u.id = f.usuario_id
    ORDER BY au.uploaded_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$arquivos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Arquivos Enviados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <div class="container mt-4">
        <h1 class="mb-4">Arquivos Enviados</h1>

        <?php if (empty($arquivos)): ?>
        <div class="alert alert-warning">Nenhum arquivo enviado até o momento.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Usuário</th>
                        <th>CPF</th>
                        <th>Tipo Documento</th>
                        <th>Nome Original</th>
                        <th>Tamanho</th>
                        <th>Data Envio</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($arquivos as $arq): ?>
                    <tr>
                        <td><?= htmlspecialchars($arq['nome_completo']) ?></td>
                        <td><?= htmlspecialchars($arq['cpf']) ?></td>
                        <td><?= htmlspecialchars($arq['tipo_documento'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($arq['nome_original']) ?></td>
                        <td><?= number_format($arq['tamanho'] / 1024, 2) ?> KB</td>
                        <td><?= date('d/m/Y H:i', strtotime($arq['uploaded_at'])) ?></td>
                        <td>
                            <a href="<?= htmlspecialchars($arq['caminho_arquivo']) ?>" target="_blank"
                                class="btn btn-sm btn-primary">
                                <i class="bi bi-download"></i> Baixar
                            </a>
                            <a href="excluir_arquivo.php?id=<?= $arq['arquivo_id'] ?>" class="btn btn-sm btn-danger"
                                onclick="return confirm('Tem certeza que deseja excluir este arquivo?')">
                                <i class="bi bi-trash"></i> Excluir
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>