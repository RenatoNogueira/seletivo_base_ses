<?php
// Iniciar buffer para evitar saídas acidentais
ob_start();

session_start();
require_once '../config/database.php';
require_once 'functions.php';

// Função para enviar resposta padronizada
function sendJsonResponse($success, $message, $data = [], $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json');

    // Limpar qualquer saída anterior
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $response = [
        'success' => $success,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ] + $data;

    echo json_encode($response);
    exit;
}

// Verificar autenticação e autorização
if (!isset($_SESSION['admin_id'])) {
    sendJsonResponse(false, 'Não autorizado', [], 401);
}

try {
    verificarNivel('super_admin');
} catch (Exception $e) {
    sendJsonResponse(false, $e->getMessage(), [], 403);
}

// Verificar método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Método não permitido', [], 405);
}

// Verificar conteúdo JSON
$jsonInput = file_get_contents('php://input');
if (empty($jsonInput)) {
    sendJsonResponse(false, 'Dados JSON não fornecidos', [], 400);
}

$input = json_decode($jsonInput, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    sendJsonResponse(false, 'JSON inválido: ' . json_last_error_msg(), [], 400);
}

// Validar ID do usuário
$usuarioId = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT);
if (!$usuarioId || $usuarioId <= 0) {
    sendJsonResponse(false, 'ID do usuário inválido', [], 400);
}

try {
    // Verificar existência do usuário
    $stmt = $pdo->prepare("SELECT id, nome_completo FROM usuarios WHERE id = ?");
    $stmt->execute([$usuarioId]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        sendJsonResponse(false, 'Usuário não encontrado', [], 404);
    }

    // Iniciar transação
    $pdo->beginTransaction();

    // Processar formulário do usuário (se existir)
    $formularioId = null;
    $stmt = $pdo->prepare("SELECT id FROM formularios WHERE usuario_id = ?");
    $stmt->execute([$usuarioId]);
    $formulario = $stmt->fetch();

    if ($formulario) {
        $formularioId = $formulario['id'];

        // Excluir arquivos físicos
        $stmt = $pdo->prepare("SELECT caminho_arquivo FROM arquivos_upload WHERE formulario_id = ?");
        $stmt->execute([$formularioId]);
        $arquivos = $stmt->fetchAll();

        $errosArquivos = [];
        foreach ($arquivos as $arquivo) {
            if (!empty($arquivo['caminho_arquivo']) && file_exists($arquivo['caminho_arquivo'])) {
                if (!@unlink($arquivo['caminho_arquivo'])) {
                    $errosArquivos[] = $arquivo['caminho_arquivo'];
                }
            }
        }

        if (!empty($errosArquivos)) {
            throw new Exception('Não foi possível excluir alguns arquivos: ' . implode(', ', $errosArquivos));
        }

        // Excluir registros relacionados
        $tabelas = ['arquivos_upload', 'cursos_formacoes', 'formularios'];
        foreach ($tabelas as $tabela) {
            $stmt = $pdo->prepare("DELETE FROM $tabela WHERE formulario_id = ?");
            if (!$stmt->execute([$formularioId])) {
                throw new Exception("Erro ao excluir da tabela $tabela");
            }
        }
    }

    // Excluir usuário
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    if (!$stmt->execute([$usuarioId]) || $stmt->rowCount() === 0) {
        throw new Exception('Erro ao excluir usuário');
    }

    // Confirmar transação
    $pdo->commit();

    // Registrar log (opcional - não interrompe o fluxo se falhar)
    try {
        registrarLog($pdo, 'excluir_usuario', "Usuário {$usuario['nome_completo']} (ID: $usuarioId) excluído");
    } catch (Exception $e) {
        error_log("Erro no log: " . $e->getMessage());
    }

    sendJsonResponse(true, 'Usuário excluído com sucesso', ['usuario_id' => $usuarioId]);
} catch (Exception $e) {
    // Reverter em caso de erro
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Registrar erro
    error_log("Erro ao excluir usuário: " . $e->getMessage());

    try {
        registrarLog($pdo, 'erro_excluir_usuario', $e->getMessage());
    } catch (Exception $logError) {
        error_log("Erro no log de erro: " . $logError->getMessage());
    }

    sendJsonResponse(false, $e->getMessage(), [], 500);
}