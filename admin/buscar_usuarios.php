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

// Configurar cabeçalho JSON
header('Content-Type: application/json');

try {
    // Parâmetros de busca com sanitização
    $filtros = [
        'termo' => trim($_GET['termo'] ?? ''),
        'cidade' => trim($_GET['cidade'] ?? ''),
        'estado' => trim($_GET['estado'] ?? ''),
        'data_inicio' => trim($_GET['data_inicio'] ?? ''),
        'data_fim' => trim($_GET['data_fim'] ?? '')
    ];

    // Validação dos parâmetros
    $pagina = max(1, intval($_GET['pagina'] ?? 1));
    $limite = min(max(10, intval($_GET['limite'] ?? 20)), 100); // Limite entre 10 e 100
    $offset = ($pagina - 1) * $limite;

    // Buscar usuários com os filtros
    $usuarios = buscarUsuarios($pdo, $filtros, $limite, $offset);
    $totalUsuarios = contarUsuarios($pdo, $filtros);
    $totalPaginas = ceil($totalUsuarios / $limite);

    // Preparar dados para retorno
    $usuariosFormatados = array_map(function ($usuario) {
        return [
            'usuario_id' => (int)$usuario['usuario_id'],
            'cpf' => formatarCPF($usuario['cpf']),
            'nome_completo' => sanitizar($usuario['nome_completo']),
            'email' => sanitizar($usuario['email']),
            'rg' => sanitizar($usuario['rg']),
            'data_nascimento' => $usuario['data_nascimento'] ? date('d/m/Y', strtotime($usuario['data_nascimento'])) : '-',
            'celular' => formatarTelefone($usuario['celular']),
            'telefone_fixo' => formatarTelefone($usuario['telefone_fixo']),
            'cidade' => sanitizar($usuario['cidade']),
            'estado' => sanitizar($usuario['estado']),
            'data_cadastro' => formatarData($usuario['data_cadastro']),
            'total_cursos' => (int)$usuario['total_cursos'],
            'total_arquivos' => (int)$usuario['total_arquivos'],
            'nivel' => sanitizar($usuario['nivel']),
            'areas_formacao' => sanitizar($usuario['areas_formacao']),
            'registros_profissionais' => sanitizar($usuario['registros_profissionais']),
            'tipos_documentos' => sanitizar($usuario['tipos_documentos'])
        ];
    }, $usuarios);

    // Retornar resposta JSON
    echo json_encode([
        'success' => true,
        'usuarios' => $usuariosFormatados,
        'pagination' => [
            'pagina_atual' => $pagina,
            'total_paginas' => $totalPaginas,
            'total_usuarios' => $totalUsuarios,
            'limite' => $limite,
            'offset' => $offset
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (PDOException $e) {
    error_log("Erro na busca de usuários: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor ao buscar usuários',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    error_log("Erro geral na busca: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro inesperado',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}