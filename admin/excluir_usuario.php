<?php
session_start();
require_once '../config/database.php';
require_once 'functions.php';

// Verificar se está logado
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

// Verificar nível de acesso (apenas super_admin pode excluir)
verificarNivel('super_admin');

// Configurar cabeçalho JSON
header('Content-Type: application/json');

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

try {
    // Obter dados JSON
    $input = json_decode(file_get_contents('php://input'), true);
    $usuarioId = intval($input['id'] ?? 0);
    
    if (!$usuarioId) {
        throw new Exception('ID do usuário não fornecido');
    }
    
    // Verificar se o usuário existe
    $stmt = $pdo->prepare("SELECT id, nome_completo FROM usuarios WHERE id = ?");
    $stmt->execute([$usuarioId]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        throw new Exception('Usuário não encontrado');
    }
    
    // Iniciar transação
    $pdo->beginTransaction();
    
    // Buscar formulário do usuário
    $stmt = $pdo->prepare("SELECT id FROM formularios WHERE usuario_id = ?");
    $stmt->execute([$usuarioId]);
    $formulario = $stmt->fetch();
    
    if ($formulario) {
        $formularioId = $formulario['id'];
        
        // Excluir arquivos físicos
        $stmt = $pdo->prepare("SELECT caminho_arquivo FROM arquivos_upload WHERE formulario_id = ?");
        $stmt->execute([$formularioId]);
        $arquivos = $stmt->fetchAll();
        
        foreach ($arquivos as $arquivo) {
            $caminhoArquivo = $arquivo['caminho_arquivo'];
            if (file_exists($caminhoArquivo)) {
                unlink($caminhoArquivo);
            }
        }
        
        // Excluir registros relacionados
        $stmt = $pdo->prepare("DELETE FROM arquivos_upload WHERE formulario_id = ?");
        $stmt->execute([$formularioId]);
        
        $stmt = $pdo->prepare("DELETE FROM cursos_formacoes WHERE formulario_id = ?");
        $stmt->execute([$formularioId]);
        
        $stmt = $pdo->prepare("DELETE FROM formularios WHERE id = ?");
        $stmt->execute([$formularioId]);
    }
    
    // Excluir usuário
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute([$usuarioId]);
    
    // Confirmar transação
    $pdo->commit();
    
    // Registrar log
    registrarLog($pdo, 'excluir_usuario', "Usuário '{$usuario['nome_completo']}' (ID: {$usuarioId}) excluído com sucesso");
    
    echo json_encode([
        'success' => true,
        'message' => 'Usuário excluído com sucesso',
        'usuario_id' => $usuarioId,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    // Reverter transação em caso de erro
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log do erro
    error_log("Erro ao excluir usuário: " . $e->getMessage());
    
    // Registrar log do erro
    try {
        registrarLog($pdo, 'erro_excluir_usuario', 'Erro ao excluir usuário: ' . $e->getMessage());
    } catch (Exception $logError) {
        // Erro silencioso no log
    }
    
    // Retornar erro
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>

