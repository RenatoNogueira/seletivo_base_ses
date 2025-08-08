<?php
header('Content-Type: application/json');

// Database configuration
require_once './config/database.php';

// Check if the request is POST and has the ID parameter
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Requisição inválida']);
    exit;
}

$arquivoId = (int)$_POST['id'];

try {
    // Start transaction
    $pdo->beginTransaction();

    // 1. First, get the file information from database
    $stmt = $pdo->prepare("SELECT * FROM arquivos_upload WHERE id = ?");
    $stmt->execute([$arquivoId]);
    $arquivo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$arquivo) {
        throw new Exception('Arquivo não encontrado no banco de dados');
    }

    // 2. Delete the physical file
    $caminhoArquivo = $arquivo['caminho_arquivo'];
    if (file_exists($caminhoArquivo) && !unlink($caminhoArquivo)) {
        throw new Exception('Falha ao excluir o arquivo físico');
    }

    // 3. Delete the database record
    $stmt = $pdo->prepare("DELETE FROM arquivos_upload WHERE id = ?");
    $stmt->execute([$arquivoId]);

    // Commit transaction
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Arquivo excluído com sucesso']);
} catch (Exception $e) {
    // Rollback transaction if there was an error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}