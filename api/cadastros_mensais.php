<?php
require_once '../config/database.php';
require_once '../admin/functions.php';

header('Content-Type: application/json');

try {
    $ano = $_GET['ano'] ?? date('Y');
    $dados = obterDadosCadastrosMensais($pdo, $ano);

    echo json_encode([
        'success' => true,
        'data' => array_values($dados),
        'ano' => $ano
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao carregar dados: ' . $e->getMessage()
    ]);
}