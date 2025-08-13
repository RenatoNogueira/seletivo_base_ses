<?php
session_start();
require_once '../config/database.php';
require_once 'functions.php';

// Registrar log de logout se estiver logado
if (isset($_SESSION['admin_id'])) {
    try {
        registrarLog($pdo, 'logout', 'Logout realizado');
    } catch (Exception $e) {
        // Log silencioso em caso de erro
    }
}

// Destruir sessão
session_destroy();

// Redirecionar para login
header('Location: index.php?logout=1');
exit;
