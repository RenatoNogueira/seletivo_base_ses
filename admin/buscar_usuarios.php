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
    // Parâmetros de busca
    $filtros = [
        'termo' => $_GET['termo'] ?? '',
        'cidade' => $_GET['cidade'] ?? '',
        'estado' => $_GET['estado'] ?? '',
        'data_inicio' => $_GET['data_inicio'] ?? '',
        'data_fim' => $_GET['data_fim'] ?? ''
    ];

    $pagina = max(1, intval($_GET['pagina'] ?? 1));
    $limite = intval($_GET['limite'] ?? 20);
    $offset = ($pagina - 1) * $limite;

    // Buscar usuários
    $usuarios = buscarUsuarios($pdo, $filtros, $limite, $offset);
    $totalUsuarios = contarUsuarios($pdo, $filtros);
    $totalPaginas = ceil($totalUsuarios / $limite);

    // Registrar log da busca
    $termoLog = !empty($filtros['termo']) ? "termo: '{$filtros['termo']}'" : '';
    $cidadeLog = !empty($filtros['cidade']) ? "cidade: '{$filtros['cidade']}'" : '';
    $estadoLog = !empty($filtros['estado']) ? "estado: '{$filtros['estado']}'" : '';
    $filtrosLog = array_filter([$termoLog, $cidadeLog, $estadoLog]);
    $descricaoLog = 'Busca AJAX de usuários' . (!empty($filtrosLog) ? ' com filtros: ' . implode(', ', $filtrosLog) : '');

    registrarLog($pdo, 'busca_usuarios', $descricaoLog);

    // Preparar dados para retorno
    $usuariosFormatados = [];
    foreach ($usuarios as $usuario) {
        $usuariosFormatados[] = [
            'usuario_id' => $usuario['usuario_id'],
            'cpf' => $usuario['cpf'],
            'nome_completo' => $usuario['nome_completo'],
            'email' => $usuario['email'],
            'rg' => $usuario['rg'],
            'estado_civil' => $usuario['estado_civil'],
            'nacionalidade' => $usuario['nacionalidade'],
            'telefone_fixo' => $usuario['telefone_fixo'],
            'celular' => $usuario['celular'],
            'email_alternativo' => $usuario['email_alternativo'],
            'data_nascimento' => $usuario['data_nascimento'],
            'data_cadastro' => $usuario['data_cadastro'],
            'formulario_id' => $usuario['formulario_id'],
            'link_video' => $usuario['link_video'],
            'cep' => $usuario['cep'],
            'logradouro' => $usuario['logradouro'],
            'numero' => $usuario['numero'],
            'complemento' => $usuario['complemento'],
            'bairro' => $usuario['bairro'],
            'cidade' => $usuario['cidade'],
            'estado' => $usuario['estado'],
            'data_envio_formulario' => $usuario['data_envio_formulario'],
            'total_cursos' => intval($usuario['total_cursos']),
            'total_arquivos' => intval($usuario['total_arquivos']),
            'areas_formacao' => $usuario['areas_formacao'],
            'registros_profissionais' => $usuario['registros_profissionais'],
            'tipos_documentos' => $usuario['tipos_documentos']
        ];
    }

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
        'filtros' => $filtros,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    // Log do erro
    error_log("Erro na busca AJAX de usuários: " . $e->getMessage());

    // Registrar log do erro
    try {
        registrarLog($pdo, 'erro_busca_usuarios', 'Erro na busca AJAX: ' . $e->getMessage());
    } catch (Exception $logError) {
        // Erro silencioso no log
    }

    // Retornar erro
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}