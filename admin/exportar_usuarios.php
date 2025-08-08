<?php
session_start();
require_once '../config/database.php';
require_once 'functions.php';

// Verificar se está logado
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

try {
    // Parâmetros de busca
    $filtros = [
        'termo' => $_GET['termo'] ?? '',
        'cidade' => $_GET['cidade'] ?? '',
        'estado' => $_GET['estado'] ?? '',
        'data_inicio' => $_GET['data_inicio'] ?? '',
        'data_fim' => $_GET['data_fim'] ?? ''
    ];

    // Buscar todos os usuários com os filtros aplicados (sem limite)
    $usuarios = buscarUsuarios($pdo, $filtros, 10000, 0);
    
    // Registrar log da exportação
    $termoLog = !empty($filtros['termo']) ? "termo: '{$filtros['termo']}'" : '';
    $cidadeLog = !empty($filtros['cidade']) ? "cidade: '{$filtros['cidade']}'" : '';
    $estadoLog = !empty($filtros['estado']) ? "estado: '{$filtros['estado']}'" : '';
    $filtrosLog = array_filter([$termoLog, $cidadeLog, $estadoLog]);
    $descricaoLog = 'Exportação de ' . count($usuarios) . ' usuários' . (!empty($filtrosLog) ? ' com filtros: ' . implode(', ', $filtrosLog) : '');
    
    registrarLog($pdo, 'exportar_usuarios', $descricaoLog);
    
    // Configurar cabeçalhos para download CSV
    $filename = 'usuarios_seletico_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Expires: 0');
    
    // Abrir output stream
    $output = fopen('php://output', 'w');
    
    // Adicionar BOM para UTF-8 (para Excel reconhecer acentos)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Cabeçalhos do CSV
    $headers = [
        'ID',
        'Nome Completo',
        'CPF',
        'RG',
        'Data de Nascimento',
        'Estado Civil',
        'Nacionalidade',
        'Email Principal',
        'Email Alternativo',
        'Telefone Fixo',
        'Celular',
        'CEP',
        'Logradouro',
        'Número',
        'Complemento',
        'Bairro',
        'Cidade',
        'Estado',
        'Link do Vídeo',
        'Data de Cadastro',
        'Data Envio Formulário',
        'Total de Cursos',
        'Total de Arquivos',
        'Áreas de Formação',
        'Registros Profissionais',
        'Tipos de Documentos'
    ];
    
    fputcsv($output, $headers, ';');
    
    // Dados dos usuários
    foreach ($usuarios as $usuario) {
        $row = [
            $usuario['usuario_id'],
            $usuario['nome_completo'] ?? '',
            $usuario['cpf'] ?? '',
            $usuario['rg'] ?? '',
            $usuario['data_nascimento'] ? date('d/m/Y', strtotime($usuario['data_nascimento'])) : '',
            $usuario['estado_civil'] ?? '',
            $usuario['nacionalidade'] ?? '',
            $usuario['email'] ?? '',
            $usuario['email_alternativo'] ?? '',
            $usuario['telefone_fixo'] ?? '',
            $usuario['celular'] ?? '',
            $usuario['cep'] ?? '',
            $usuario['logradouro'] ?? '',
            $usuario['numero'] ?? '',
            $usuario['complemento'] ?? '',
            $usuario['bairro'] ?? '',
            $usuario['cidade'] ?? '',
            $usuario['estado'] ?? '',
            $usuario['link_video'] ?? '',
            $usuario['data_cadastro'] ? date('d/m/Y H:i:s', strtotime($usuario['data_cadastro'])) : '',
            $usuario['data_envio_formulario'] ? date('d/m/Y H:i:s', strtotime($usuario['data_envio_formulario'])) : '',
            $usuario['total_cursos'],
            $usuario['total_arquivos'],
            $usuario['areas_formacao'] ?? '',
            $usuario['registros_profissionais'] ?? '',
            $usuario['tipos_documentos'] ?? ''
        ];
        
        fputcsv($output, $row, ';');
    }
    
    fclose($output);
    
} catch (Exception $e) {
    // Log do erro
    error_log("Erro na exportação de usuários: " . $e->getMessage());
    
    // Registrar log do erro
    try {
        registrarLog($pdo, 'erro_exportar_usuarios', 'Erro na exportação: ' . $e->getMessage());
    } catch (Exception $logError) {
        // Erro silencioso no log
    }
    
    // Redirecionar com erro
    header('Location: usuarios.php?erro=exportacao_falhou');
    exit;
}
?>

