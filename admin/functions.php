<?php
// Verificar se o usuário está logado como admin
function verificarLogin()
{
    if (!isset($_SESSION['admin_id'])) {
        header('Location: index.php');
        exit;
    }
}

// Verificar nível de acesso
function verificarNivel($nivelRequerido = 'admin', bool $retornarBooleano = false)
{
    // Verificar se a sessão está iniciada
    if (session_status() !== PHP_SESSION_ACTIVE) {
        if ($retornarBooleano) return false;
        throw new Exception('Sessão não inicializada');
    }

    // Verificar se o nível do usuário está definido na sessão
    if (!isset($_SESSION['admin_nivel'])) {
        if ($retornarBooleano) return false;
        throw new Exception('Nível de acesso não definido na sessão');
    }

    // Hierarquia de níveis de acesso
    $niveisHierarquia = [
        'visitante' => 0,
        'moderador' => 1,
        'admin' => 2,
        'super_admin' => 3
    ];

    // Obter nível numérico do usuário atual
    $nivelUsuario = $niveisHierarquia[$_SESSION['admin_nivel']] ?? -1;

    // Se nível do usuário não existe na hierarquia
    if ($nivelUsuario === -1) {
        if ($retornarBooleano) return false;
        throw new Exception('Nível de acesso do usuário é inválido');
    }

    // Tratar múltiplos níveis permitidos (array)
    if (is_array($nivelRequerido)) {
        $acessoPermitido = false;
        foreach ($nivelRequerido as $nivel) {
            if (($niveisHierarquia[$nivel] ?? -1) <= $nivelUsuario) {
                $acessoPermitido = true;
                break;
            }
        }
    }
    // Tratar nível único (string)
    else {
        $nivelMinimo = $niveisHierarquia[$nivelRequerido] ?? -1;
        if ($nivelMinimo === -1) {
            if ($retornarBooleano) return false;
            throw new Exception('Nível requerido é inválido');
        }
        $acessoPermitido = ($nivelUsuario >= $nivelMinimo);
    }

    // Comportamento padrão (redirecionar)
    if (!$retornarBooleano && !$acessoPermitido) {
        // Registrar tentativa de acesso não autorizado
        if (function_exists('registrarLog')) {
            registrarLog(
                $GLOBALS['pdo'] ?? null,
                'tentativa_acesso',
                "Tentativa de acesso não autorizado. Nível do usuário: {$_SESSION['admin_nivel']}, Nível requerido: " .
                    (is_array($nivelRequerido) ? implode(',', $nivelRequerido) : $nivelRequerido
                    )
            );
        }

        http_response_code(403);
        $_SESSION['erro_acesso'] = 'Você não tem permissão para acessar esta funcionalidade';
        header('Location: dashboard.php');
        exit;
    }

    return $acessoPermitido;
}

// Registrar log de atividade
function registrarLog($pdo, $acao, $descricao = '')
{
    try {
        $stmt = $pdo->prepare("INSERT INTO logs_admin (admin_id, acao, descricao, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['admin_id'] ?? 0,
            $acao,
            $descricao,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (PDOException $e) {
        // Log silencioso em caso de erro
    }
}

// Sanitizar dados para exibição
function sanitizar($texto)
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

// Validar email
function validarEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Formatar CPF
function formatarCPF($cpf)
{
    if (!$cpf) return '-';
    $cpf = preg_replace('/\D/', '', $cpf);
    if (strlen($cpf) == 11) {
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
    }
    return $cpf;
}

// Formatar data
function formatarData($data)
{
    if (!$data) return '-';
    return date('d/m/Y H:i:s', strtotime($data));
}

// Formatar telefone
function formatarTelefone($telefone)
{
    if (!$telefone) return '-';
    $telefone = preg_replace('/\D/', '', $telefone);
    if (strlen($telefone) == 11) {
        return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $telefone);
    } elseif (strlen($telefone) == 10) {
        return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $telefone);
    }
    return $telefone;
}

// Obter estatísticas do sistema
function obterEstatisticas($pdo)
{
    try {
        $stats = [];

        // Total de usuários
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
        $stats['total_usuarios'] = $stmt->fetch()['total'];

        // Total de formulários
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM formularios");
        $stats['total_formularios'] = $stmt->fetch()['total'];

        // Total de arquivos
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM arquivos_upload");
        $stats['total_arquivos'] = $stmt->fetch()['total'];

        // Total de cursos
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM cursos_formacoes");
        $stats['total_cursos'] = $stmt->fetch()['total'];

        // Usuários hoje
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE DATE(created_at) = CURDATE()");
        $stats['usuarios_hoje'] = $stmt->fetch()['total'];

        // Usuários esta semana
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE YEARWEEK(created_at) = YEARWEEK(NOW())");
        $stats['usuarios_semana'] = $stmt->fetch()['total'];

        // Usuários este mês
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())");
        $stats['usuarios_mes'] = $stmt->fetch()['total'];

        // Formulários hoje
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM formularios WHERE DATE(submitted_at) = CURDATE()");
        $stats['formularios_hoje'] = $stmt->fetch()['total'];

        return $stats;
    } catch (PDOException $e) {
        return [
            'total_usuarios' => 0,
            'total_formularios' => 0,
            'total_arquivos' => 0,
            'total_cursos' => 0,
            'usuarios_hoje' => 0,
            'usuarios_semana' => 0,
            'usuarios_mes' => 0,
            'formularios_hoje' => 0
        ];
    }
}

// Buscar usuários com filtros - VERSÃO DEBUG ULTRA-SIMPLES
function buscarUsuarios($pdo, $filtros = [], $limite = 20, $offset = 0)
{
    try {
        // Versão mais simples possível para debug
        $sql = "SELECT
  u.id as usuario_id,
                    u.cpf,
                    u.nome_completo,
                    u.email,
                    u.rg,
                    u.estado_civil,
                    u.nacionalidade,
                    u.telefone_fixo,
                    u.celular,
                    u.email_alternativo,
                    u.data_nascimento,
                    u.created_at as data_cadastro,
                    f.id as formulario_id,
                    f.link_video,
                    f.cep,
                    f.logradouro,
                    f.numero,
                    f.complemento,
                    f.bairro,
                    f.cidade,
                    f.estado,
                    f.submitted_at as data_envio_formulario,
                    (SELECT COUNT(*) FROM cursos_formacoes cf WHERE cf.formulario_id = f.id) as total_cursos,
                    (SELECT COUNT(*) FROM arquivos_upload au WHERE au.formulario_id = f.id) as total_arquivos,
                    (SELECT GROUP_CONCAT(DISTINCT cf.nivel SEPARATOR ', ') FROM cursos_formacoes cf WHERE cf.formulario_id = f.id) as nivel,
                    (SELECT GROUP_CONCAT(DISTINCT cf.area_formacao SEPARATOR ', ') FROM cursos_formacoes cf WHERE cf.formulario_id = f.id) as areas_formacao,
                    (SELECT GROUP_CONCAT(DISTINCT cf.registro_profissional SEPARATOR ', ') FROM cursos_formacoes cf WHERE cf.formulario_id = f.id AND cf.registro_profissional IS NOT NULL) as registros_profissionais
                FROM usuarios u
                LEFT JOIN formularios f ON u.id = f.usuario_id
                WHERE 1=1";

        // Sem filtros por enquanto para debug
        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Debug: log do resultado
        error_log("DEBUG buscarUsuarios: SQL = " . $sql);
        error_log("DEBUG buscarUsuarios: Resultado count = " . count($resultado));

        return $resultado;
    } catch (PDOException $e) {
        error_log("ERRO buscarUsuarios: " . $e->getMessage());
        return [];
    }
}

// Contar total de usuários com filtros
function contarUsuarios($pdo, $filtros = [])
{
    try {
        $sql = "SELECT COUNT(DISTINCT u.id) as total
                FROM usuarios u
                LEFT JOIN formularios f ON u.id = f.usuario_id
                WHERE 1=1";
        $params = [];

        if (!empty($filtros['termo'])) {
            $sql .= " AND (u.nome_completo LIKE ? OR u.cpf LIKE ? OR u.email LIKE ?)";
            $termo = '%' . $filtros['termo'] . '%';
            $params[] = $termo;
            $params[] = $termo;
            $params[] = $termo;
        }

        if (!empty($filtros['cidade'])) {
            $sql .= " AND f.cidade LIKE ?";
            $params[] = '%' . $filtros['cidade'] . '%';
        }

        if (!empty($filtros['estado'])) {
            $sql .= " AND f.estado = ?";
            $params[] = $filtros['estado'];
        }

        if (!empty($filtros['data_inicio'])) {
            $sql .= " AND DATE(u.created_at) >= ?";
            $params[] = $filtros['data_inicio'];
        }

        if (!empty($filtros['data_fim'])) {
            $sql .= " AND DATE(u.created_at) <= ?";
            $params[] = $filtros['data_fim'];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total'];
    } catch (PDOException $e) {
        error_log("Erro ao contar usuários: " . $e->getMessage());
        return 0;
    }
}

// Obter detalhes de um usuário (atualizada com campos PGS)
function obterUsuario($pdo, $usuarioId)
{
    try {
        $sql = "SELECT
                    u.id as usuario_id,
                    u.cpf,
                    u.nome_completo,
                    u.email,
                    u.rg,
                    u.estado_civil,
                    u.nacionalidade,
                    u.telefone_fixo,
                    u.celular,
                    u.email_alternativo,
                    u.data_nascimento,
                    u.created_at as data_cadastro,
                    f.id as formulario_id,
                    f.link_video,
                    f.cep,
                    f.logradouro,
                    f.numero,
                    f.complemento,
                    f.bairro,
                    f.cidade,
                    f.estado,
                    f.objetivo_pgs,
                    f.atividades_pgs,
                    f.contribuicao_pgs,
                    f.submitted_at as data_envio_formulario,
                    (SELECT COUNT(*) FROM cursos_formacoes cf WHERE cf.formulario_id = f.id) as total_cursos,
                    (SELECT COUNT(*) FROM arquivos_upload au WHERE au.formulario_id = f.id) as total_arquivos,
                    (SELECT GROUP_CONCAT(DISTINCT area_formacao SEPARATOR ', ') FROM cursos_formacoes cf WHERE cf.formulario_id = f.id) as areas_formacao,
                    (SELECT GROUP_CONCAT(DISTINCT registro_profissional SEPARATOR ', ') FROM cursos_formacoes cf WHERE cf.formulario_id = f.id AND cf.registro_profissional IS NOT NULL) as registros_profissionais,
                    (SELECT GROUP_CONCAT(DISTINCT tipo_documento SEPARATOR ', ') FROM arquivos_upload au WHERE au.formulario_id = f.id) as tipos_documentos
                FROM usuarios u
                LEFT JOIN formularios f ON u.id = f.usuario_id
                WHERE u.id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuarioId]);

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // DEBUG
        error_log("Dados do usuário obtidos: " . print_r($usuario, true));

        return $usuario;
    } catch (PDOException $e) {
        error_log("Erro ao obter usuário: " . $e->getMessage());
        return null;
    }
}

// Obter formulário completo com dados PGS
function obterFormularioCompleto($pdo, $formularioId)
{
    try {
        $sql = "SELECT
                    f.*,
                    u.nome_completo,
                    u.cpf,
                    u.email
                FROM formularios f
                JOIN usuarios u ON f.usuario_id = u.id
                WHERE f.id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$formularioId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao obter formulário completo: " . $e->getMessage());
        return null;
    }
}


// Obter arquivos de um usuário
function obterArquivosUsuario($pdo, $formularioId)
{
    try {
        $stmt = $pdo->prepare("SELECT
                                id,
                                nome_original as nome_arquivo,
                                caminho_arquivo,
                                tamanho,
                                tipo_documento,
                                uploaded_at as data_upload
                              FROM arquivos_upload
                              WHERE formulario_id = ?
                              ORDER BY uploaded_at DESC");
        $stmt->execute([$formularioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Obter cursos de um usuário
function obterCursosUsuario($pdo, $formularioId)
{
    try {
        $stmt = $pdo->prepare("
            SELECT
                nivel,
                area_formacao,
                registro_profissional,
                instituicao,
                ano_conclusao
            FROM cursos_formacoes
            WHERE formulario_id = ?
            ORDER BY ano_conclusao DESC
        ");
        $stmt->execute([$formularioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar cursos: " . $e->getMessage());
        return [];
    }
}

function obterFormulario($pdo, $formularioId)
{
    try {
        $stmt = $pdo->prepare("SELECT * FROM formularios WHERE id = ?");
        $stmt->execute([$formularioId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar formulário: " . $e->getMessage());
        return false;
    }
}


/**
 * Obtém dados de cadastros mensais para o gráfico
 */
function obterDadosCadastrosMensais($pdo, $ano = null)
{
    $ano = $ano ?? date('Y');

    $sql = "SELECT
                MONTH(created_at) as mes,
                COUNT(id) as total
            FROM usuarios
            WHERE YEAR(created_at) = :ano
            GROUP BY MONTH(created_at)
            ORDER BY mes";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':ano' => $ano]);

    $dados = array_fill(1, 12, 0); // Inicializa todos os meses com 0

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $dados[$row['mes']] = (int)$row['total'];
    }

    return $dados;
}

/**
 * Obtém anos disponíveis para filtro
 */
function obterAnosDisponiveis($pdo)
{
    $sql = "SELECT DISTINCT YEAR(created_at) as ano
            FROM usuarios
            ORDER BY ano DESC";

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}



// ... outras funções existentes ...

/**
 * Formata o tamanho do arquivo para leitura humana
 * @param int $bytes Tamanho em bytes
 * @return string Tamanho formatado
 */
function formatFileSize($bytes)
{
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' bytes';
    } elseif ($bytes == 1) {
        return '1 byte';
    } else {
        return '0 bytes';
    }
}