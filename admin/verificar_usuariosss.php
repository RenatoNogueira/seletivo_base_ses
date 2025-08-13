<?php
require_once '../config/database.php';

header('Content-Type: application/json');

$usuarioId = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);

if (!$usuarioId) {
    echo json_encode(['naoEncontrado' => false, 'error' => 'ID inválido']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
    $stmt->execute([$usuarioId]);
    $usuario = $stmt->fetch();

    echo json_encode(['naoEncontrado' => !$usuario]);
} catch (Exception $e) {
    echo json_encode(['naoEncontrado' => false, 'error' => $e->getMessage()]);
}