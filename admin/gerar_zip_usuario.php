<?php
session_start();
require_once '../config/database.php';
require_once 'functions.php';

// Verificar se está logado
if (!isset($_SESSION['admin_id'])) {
    header('HTTP/1.0 403 Forbidden');
    echo 'Acesso não autorizado';
    exit;
}

// Verificar se o ID do usuário foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('HTTP/1.0 400 Bad Request');
    echo 'ID de usuário inválido';
    exit;
}

$usuario_id = (int)$_GET['id'];

// Buscar informações do usuário
$stmtUsuario = $pdo->prepare("SELECT nome_completo, cpf FROM usuarios WHERE id = ?");
$stmtUsuario->execute([$usuario_id]);
$usuario = $stmtUsuario->fetch();

if (!$usuario) {
    header('HTTP/1.0 404 Not Found');
    echo 'Usuário não encontrado';
    exit;
}

// Buscar arquivos do usuário
$sql = "
    SELECT
        au.id AS arquivo_id,
        au.nome_original,
        au.caminho_arquivo,
        au.tipo_documento
    FROM arquivos_upload au
    INNER JOIN formularios f ON f.id = au.formulario_id
    WHERE f.usuario_id = ?
    ORDER BY au.tipo_documento, au.nome_original
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$usuario_id]);
$arquivos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($arquivos)) {
    header('HTTP/1.0 404 Not Found');
    echo 'Nenhum arquivo encontrado para este usuário';
    exit;
}

// Criar arquivo ZIP
$zip = new ZipArchive();
$zipFileName = tempnam(sys_get_temp_dir(), 'zip_');

if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'Não foi possível criar o arquivo ZIP';
    exit;
}

// Contador de arquivos adicionados
$arquivosAdicionados = 0;

// Adicionar arquivos ao ZIP
foreach ($arquivos as $arquivo) {
    // Normalizar caminho do arquivo
    $caminhoRelativo = str_replace('\\', '/', $arquivo['caminho_arquivo']);

    // Verificar se o caminho já começa com 'uploads/'
    if (strpos($caminhoRelativo, 'uploads/') === 0) {
        $caminhoCompleto = '../' . $caminhoRelativo;
    } else {
        $caminhoCompleto = '../uploads/' . $caminhoRelativo;
    }

    // Obter caminho físico absoluto
    $caminhoFisico = realpath(dirname(__FILE__) . '/' . $caminhoCompleto);

    // Verificar se o arquivo existe
    if ($caminhoFisico && file_exists($caminhoFisico)) {
        // Criar nome do arquivo no ZIP com tipo de documento (se existir)
        $nomeNoZip = $arquivo['nome_original'];
        if (!empty($arquivo['tipo_documento'])) {
            $nomeNoZip = $arquivo['tipo_documento'] . '_' . $nomeNoZip;
        }

        // Adicionar ao ZIP
        if ($zip->addFile($caminhoFisico, $nomeNoZip)) {
            $arquivosAdicionados++;
        }
    }
}

// Fechar o arquivo ZIP
$zip->close();

// Verificar se algum arquivo foi adicionado
if ($arquivosAdicionados === 0) {
    unlink($zipFileName); // Remover arquivo ZIP vazio
    header('HTTP/1.0 404 Not Found');
    echo 'Nenhum dos arquivos foi encontrado no servidor';
    exit;
}

// Configurar headers para download
$nomeUsuario = preg_replace('/[^a-zA-Z0-9]/', '_', $usuario['nome_completo']);
$cpfFormatado = preg_replace('/[^0-9]/', '', $usuario['cpf']);
$nomeArquivo =  $nomeUsuario . '_' . $cpfFormatado . '.zip';

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
header('Content-Length: ' . filesize($zipFileName));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Enviar arquivo e remover temporário
readfile($zipFileName);
unlink($zipFileName);
exit;