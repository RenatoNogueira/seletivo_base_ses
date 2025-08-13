<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

// Inicia sessão
iniciarSessao();

// Verifica se é upload de arquivo
// $isUpload = isset($_POST['acao']) && $_POST['acao'] === 'upload';

// Segurança — impede acesso direto sem hash
if (empty($_GET['h']) || $_GET['h'] !== ($_SESSION['hash_id'] ?? '')) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
        // Para requisições AJAX POST, permita se a sessão estiver ativa, mas logue para depuração
        error_log("Acesso AJAX sem hash, mas sessão ativa para usuario_id: " . ($_SESSION['usuario_id'] ?? 'desconhecido'));
    } else {
        error_log("Acesso não autorizado. Hash recebido: " . ($_GET['h'] ?? 'nenhum') . ", Hash esperado: " . ($_SESSION['hash_id'] ?? 'nenhum'));
        header("HTTP/1.1 403 Forbidden");
        exit("Acesso não autorizado");
    }
}

$database = new Database();
$db = $database->getConnection();

$sucesso = '';
$erro = '';
$arquivos = [];

// ID do usuário da sessão
$usuario_id = $_SESSION['usuario_id'];

// Carregar dados do usuário
$stmtUsuario = $db->prepare("SELECT * FROM usuarios WHERE id = :usuario_id");
$stmtUsuario->bindParam(':usuario_id', $usuario_id);
$stmtUsuario->execute();
$usuario = $stmtUsuario->fetch();

// Carregar formulário existente
$stmtFormulario = $db->prepare("SELECT * FROM formularios WHERE usuario_id = :usuario_id ORDER BY id DESC LIMIT 1");
$stmtFormulario->bindParam(':usuario_id', $usuario_id);
$stmtFormulario->execute();

$formulario = null;
$rascunho = [];
$cursosExistentes = [];
if ($stmtFormulario->rowCount() > 0) {
    $formulario = $stmtFormulario->fetch();

    if ($formulario['rascunho_data']) {
        $rascunho = json_decode($formulario['rascunho_data'], true) ?? [];
        $cursosExistentes = $rascunho['cursos'] ?? [];
    } else {
        // Carregar cursos de cursos_formacoes se não for rascunho
        $queryCursos = "SELECT * FROM cursos_formacoes WHERE formulario_id = :formulario_id";
        $stmtCursos = $db->prepare($queryCursos);
        $stmtCursos->bindParam(':formulario_id', $formulario['id']);
        $stmtCursos->execute();
        $cursosExistentes = $stmtCursos->fetchAll(PDO::FETCH_ASSOC);
    }

    // Carregar últimos arquivos por tipo
    $stmtArquivos = $db->prepare("
        SELECT a1.*
        FROM arquivos_upload a1
        WHERE a1.formulario_id = :formulario_id
        AND a1.uploaded_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        AND a1.id = (
            SELECT MAX(a2.id)
            FROM arquivos_upload a2
            WHERE a2.formulario_id = a1.formulario_id
            AND a2.tipo_documento = a1.tipo_documento
        )
        ORDER BY a1.uploaded_at DESC
    ");
    $stmtArquivos->bindParam(':formulario_id', $formulario['id']);
    $stmtArquivos->execute();
    $arquivos = $stmtArquivos->fetchAll(PDO::FETCH_ASSOC);
}

if (empty($cursosExistentes)) {
    $cursosExistentes = [[
        'nivel' => '',
        'area_formacao' => '',
        'registro_profissional' => '',
    ]];
}

// ======================== PROCESSAR FORMULÁRIO ========================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao == 'salvar_rascunho') {
        try {
            // Salvar rascunho
            $rascunhoData = [
                'nome_completo' => sanitizar($_POST['nome_completo'] ?? ''),
                'rg' => sanitizar($_POST['rg'] ?? ''),
                'estado_civil' => sanitizar($_POST['estado_civil'] ?? ''),
                'nacionalidade' => sanitizar($_POST['nacionalidade'] ?? ''),
                'telefone_fixo' => sanitizar($_POST['telefone_fixo'] ?? ''),
                'celular' => sanitizar($_POST['celular'] ?? ''),
                'email' => sanitizar($_POST['email'] ?? ''),
                'email_alternativo' => sanitizar($_POST['email_alternativo'] ?? ''),
                'cep' => sanitizar($_POST['cep'] ?? ''),
                'logradouro' => sanitizar($_POST['logradouro'] ?? ''),
                'numero' => sanitizar($_POST['numero'] ?? ''),
                'complemento' => sanitizar($_POST['complemento'] ?? ''),
                'bairro' => sanitizar($_POST['bairro'] ?? ''),
                'cidade' => sanitizar($_POST['cidade'] ?? ''),
                'estado' => sanitizar($_POST['estado'] ?? ''),
                'link_video' => sanitizar($_POST['link_video'] ?? ''),
                'objetivo_pgs' => sanitizar($_POST['objetivo_pgs'] ?? ''),
                'atividades_pgs' => sanitizar($_POST['atividades_pgs'] ?? ''),
                'contribuicao_pgs' => sanitizar($_POST['contribuicao_pgs'] ?? ''),
                'cursos' => $_POST['cursos'] ?? []
            ];

            $rascunhoJson = json_encode($rascunhoData);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Erro ao codificar JSON: " . json_last_error_msg());
            }

            if ($formulario) {
                // Atualizar rascunho existente
                $queryUpdate = "UPDATE formularios SET rascunho_data = :rascunho_data WHERE id = :id";
                $stmtUpdate = $db->prepare($queryUpdate);
                $stmtUpdate->bindParam(':rascunho_data', $rascunhoJson);
                $stmtUpdate->bindParam(':id', $formulario['id']);
                if (!$stmtUpdate->execute()) {
                    throw new Exception("Erro ao atualizar rascunho: " . print_r($stmtUpdate->errorInfo(), true));
                }
            } else {
                // Criar novo rascunho
                $queryInsert = "INSERT INTO formularios (usuario_id, rascunho_data) VALUES (:usuario_id, :rascunho_data)";
                $stmtInsert = $db->prepare($queryInsert);
                $stmtInsert->bindParam(':usuario_id', $_SESSION['usuario_id']);
                $stmtInsert->bindParam(':rascunho_data', $rascunhoJson);
                if (!$stmtInsert->execute()) {
                    throw new Exception("Erro ao inserir rascunho: " . print_r($stmtInsert->errorInfo(), true));
                }
            }

            if (isset($_POST['ajax'])) {
                echo json_encode(['success' => true]);
                exit;
            } else {
                $sucesso = 'Rascunho salvo com sucesso!';
            }
        } catch (Exception $e) {
            error_log("Erro ao salvar rascunho: " . $e->getMessage());
            if (isset($_POST['ajax'])) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            } else {
                $erro = 'Erro ao salvar rascunho: ' . $e->getMessage();
            }
        }
    } elseif ($acao == 'enviar_formulario') {
        // Validar e enviar formulário
        $erros = [];
        $camposObrigatorios = [];

        $nomeCompleto = sanitizar($_POST['nome_completo'] ?? '');
        $email = sanitizar($_POST['email'] ?? '');
        $celular = sanitizar($_POST['celular'] ?? '');
        $cep = sanitizar($_POST['cep'] ?? '');
        $logradouro = sanitizar($_POST['logradouro'] ?? '');
        $numero = sanitizar($_POST['numero'] ?? '');
        $bairro = sanitizar($_POST['bairro'] ?? '');
        $cidade = sanitizar($_POST['cidade'] ?? '');
        $estado = sanitizar($_POST['estado'] ?? '');
        $objetivoPgs = sanitizar($_POST['objetivo_pgs'] ?? '');
        $atividadesPgs = sanitizar($_POST['atividades_pgs'] ?? '');
        $contribuicaoPgs = sanitizar($_POST['contribuicao_pgs'] ?? '');

        // Validação de campos obrigatórios
        if (empty($nomeCompleto)) {
            $erros[] = 'Nome completo é obrigatório.';
            $camposObrigatorios[] = 'nome_completo';
        }
        if (empty($email) || !validarEmail($email)) {
            $erros[] = 'Email válido é obrigatório.';
            $camposObrigatorios[] = 'email';
        }
        if (empty($celular)) {
            $erros[] = 'Celular é obrigatório.';
            $camposObrigatorios[] = 'celular';
        }
        if (empty($cep)) {
            $erros[] = 'CEP é obrigatório.';
            $camposObrigatorios[] = 'cep';
        }
        if (empty($logradouro)) {
            $erros[] = 'Logradouro é obrigatório.';
            $camposObrigatorios[] = 'logradouro';
        }
        if (empty($numero)) {
            $erros[] = 'Número do endereço é obrigatório.';
            $camposObrigatorios[] = 'numero';
        }
        if (empty($bairro)) {
            $erros[] = 'Bairro é obrigatório.';
            $camposObrigatorios[] = 'bairro';
        }
        if (empty($cidade)) {
            $erros[] = 'Cidade é obrigatória.';
            $camposObrigatorios[] = 'cidade';
        }
        if (empty($estado)) {
            $erros[] = 'Estado é obrigatório.';
            $camposObrigatorios[] = 'estado';
        }
        if (empty($objetivoPgs)) {
            $erros[] = 'Objetivo de participar do PGS é obrigatório.';
            $camposObrigatorios[] = 'objetivo_pgs';
        }
        if (empty($atividadesPgs)) {
            $erros[] = 'Atividades e funções no PGS são obrigatórias.';
            $camposObrigatorios[] = 'atividades_pgs';
        }
        if (empty($contribuicaoPgs)) {
            $erros[] = 'Contribuição para a gestão da saúde pública é obrigatória.';
            $camposObrigatorios[] = 'contribuicao_pgs';
        }

        // Armazenar campos com erro na sessão para destacar no frontend
        $_SESSION['campos_erro'] = $camposObrigatorios;

        if (empty($erros)) {
            // Atualizar dados do usuário
            $queryUpdateUsuario = "UPDATE usuarios SET
                nome_completo = :nome_completo,
                rg = :rg,
                estado_civil = :estado_civil,
                nacionalidade = :nacionalidade,
                telefone_fixo = :telefone_fixo,
                celular = :celular,
                email = :email,
                email_alternativo = :email_alternativo
                WHERE id = :id";

            $stmtUpdateUsuario = $db->prepare($queryUpdateUsuario);
            $stmtUpdateUsuario->bindParam(':nome_completo', $nomeCompleto);

            $rg = sanitizar($_POST['rg'] ?? '');
            $stmtUpdateUsuario->bindParam(':rg', $rg);

            $estadoCivil = sanitizar($_POST['estado_civil'] ?? '');
            $stmtUpdateUsuario->bindParam(':estado_civil', $estadoCivil);

            $nacionalidade = sanitizar($_POST['nacionalidade'] ?? '');
            $stmtUpdateUsuario->bindParam(':nacionalidade', $nacionalidade);

            $telefoneFixo = sanitizar($_POST['telefone_fixo'] ?? '');
            $stmtUpdateUsuario->bindParam(':telefone_fixo', $telefoneFixo);

            $stmtUpdateUsuario->bindParam(':celular', $celular);
            $stmtUpdateUsuario->bindParam(':email', $email);

            $emailAlternativo = sanitizar($_POST['email_alternativo'] ?? '');
            $stmtUpdateUsuario->bindParam(':email_alternativo', $emailAlternativo);
            $stmtUpdateUsuario->bindParam(':id', $_SESSION['usuario_id']);
            $stmtUpdateUsuario->execute();

            // Inserir/atualizar formulário
            if ($formulario) {
                $queryUpdateForm = "UPDATE formularios SET
                    link_video = :link_video,
                    cep = :cep,
                    logradouro = :logradouro,
                    numero = :numero,
                    complemento = :complemento,
                    bairro = :bairro,
                    cidade = :cidade,
                    estado = :estado,
                    objetivo_pgs = :objetivo_pgs,
                    atividades_pgs = :atividades_pgs,
                    contribuicao_pgs = :contribuicao_pgs,
                    submitted_at = CURRENT_TIMESTAMP,
                    rascunho_data = NULL
                    WHERE id = :id";

                $stmtUpdateForm = $db->prepare($queryUpdateForm);
                $stmtUpdateForm->bindParam(':id', $formulario['id']);
                $formularioId = $formulario['id'];
            } else {
                $queryInsertForm = "INSERT INTO formularios (
                    usuario_id, link_video,
                    cep, logradouro, numero, complemento, bairro, cidade, estado,
                    objetivo_pgs, atividades_pgs, contribuicao_pgs
                ) VALUES (
                    :usuario_id, :link_video,
                    :cep, :logradouro, :numero, :complemento, :bairro, :cidade, :estado,
                    :objetivo_pgs, :atividades_pgs, :contribuicao_pgs
                )";

                $stmtUpdateForm = $db->prepare($queryInsertForm);
                $stmtUpdateForm->bindParam(':usuario_id', $_SESSION['usuario_id']);
                $formularioId = null;
            }

            $linkVideo = sanitizar($_POST['link_video'] ?? '');
            $stmtUpdateForm->bindParam(':link_video', $linkVideo);

            $cep = sanitizar($_POST['cep'] ?? '');
            $stmtUpdateForm->bindParam(':cep', $cep);

            $logradouro = sanitizar($_POST['logradouro'] ?? '');
            $stmtUpdateForm->bindParam(':logradouro', $logradouro);

            $numero = sanitizar($_POST['numero'] ?? '');
            $stmtUpdateForm->bindParam(':numero', $numero);

            $complemento = sanitizar($_POST['complemento'] ?? '');
            $stmtUpdateForm->bindParam(':complemento', $complemento);

            $bairro = sanitizar($_POST['bairro'] ?? '');
            $stmtUpdateForm->bindParam(':bairro', $bairro);

            $cidade = sanitizar($_POST['cidade'] ?? '');
            $stmtUpdateForm->bindParam(':cidade', $cidade);

            $estado = sanitizar($_POST['estado'] ?? '');
            $stmtUpdateForm->bindParam(':estado', $estado);

            $stmtUpdateForm->bindParam(':objetivo_pgs', $objetivoPgs);
            $stmtUpdateForm->bindParam(':atividades_pgs', $atividadesPgs);
            $stmtUpdateForm->bindParam(':contribuicao_pgs', $contribuicaoPgs);

            $stmtUpdateForm->execute();

            if (!$formularioId) {
                $formularioId = $db->lastInsertId();
            }

            // Processar áreas de formação
            if (isset($_POST['cursos']) && is_array($_POST['cursos'])) {
                // Remover cursos existentes
                $queryDeleteCursos = "DELETE FROM cursos_formacoes WHERE formulario_id = :formulario_id";
                $stmtDeleteCursos = $db->prepare($queryDeleteCursos);
                $stmtDeleteCursos->bindParam(':formulario_id', $formularioId);
                $stmtDeleteCursos->execute();

                // Inserir novas áreas de formação
                foreach ($_POST['cursos'] as $curso) {
                    $nivel = sanitizar($curso['nivel'] ?? '');
                    $areaFormacao = sanitizar($curso['area_formacao'] ?? $curso['area_formacao_texto'] ?? '');
                    $registroProfissional = sanitizar($curso['registro_profissional'] ?? '');

                    if (!empty($nivel) && !empty($areaFormacao)) {
                        $queryInsertCurso = "INSERT INTO cursos_formacoes (
                            formulario_id, nivel, area_formacao, registro_profissional
                        ) VALUES (
                            :formulario_id, :nivel, :area_formacao, :registro_profissional
                        )";

                        $stmtInsertCurso = $db->prepare($queryInsertCurso);
                        $stmtInsertCurso->bindParam(':formulario_id', $formularioId);
                        $stmtInsertCurso->bindParam(':nivel', $nivel);
                        $stmtInsertCurso->bindParam(':area_formacao', $areaFormacao);
                        $stmtInsertCurso->bindParam(':registro_profissional', $registroProfissional);
                        $stmtInsertCurso->execute();
                    }
                }
            }

            redirecionar('sucesso.php');
        } else {
            $erro = implode('<br>', $erros);
        }
    }
}

// Limpar campos_erro da sessão após exibir
if (isset($_SESSION['campos_erro'])) {
    unset($_SESSION['campos_erro']);
}