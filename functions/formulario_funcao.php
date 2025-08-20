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
    } else {
        // Carregar dados do formulário finalizado
        $rascunho = [
            'orgao_expedidor' => $formulario['orgao_expedidor'] ?? '',
            'titulo_eleitor' => $formulario['titulo_eleitor'] ?? '',
            'pis_pasep' => $formulario['pis_pasep'] ?? '',
            'certificado_reservista' => $formulario['certificado_reservista'] ?? '',
            'sexo' => $formulario['sexo'] ?? '',
            'pcd' => $formulario['pcd'] ?? '',
            'tipo_concorrencia' => $formulario['tipo_concorrencia'] ?? '',
            'tipo_funcionario' => $formulario['tipo_funcionario'] ?? '',
            'pos_graduacao' => $formulario['pos_graduacao'] ?? '',
            'nome_completo' => $formulario['nome_completo'] ?? '',
            'rg' => $formulario['rg'] ?? '',
            'telefone_fixo' => $formulario['telefone_fixo'] ?? '',
            'celular' => $formulario['celular'] ?? '',
            'email' => $formulario['email'] ?? '',
            'email_alternativo' => $formulario['email_alternativo'] ?? '',
            'cep' => $formulario['cep'] ?? '',
            'logradouro' => $formulario['logradouro'] ?? '',
            'numero' => $formulario['numero'] ?? '',
            'complemento' => $formulario['complemento'] ?? '',
            'bairro' => $formulario['bairro'] ?? '',
            'cidade' => $formulario['cidade'] ?? '',
            'estado' => $formulario['estado'] ?? ''
        ];
    }
}

// Carregar últimos arquivos por tipo
if ($formulario) {
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

// ======================== PROCESSAR FORMULÁRIO ========================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao == 'salvar_rascunho') {
        try {
            // Salvar rascunho com os novos campos
            $rascunhoData = [
                'nome_completo' => sanitizar($_POST['nome_completo'] ?? ''),
                'rg' => sanitizar($_POST['rg'] ?? ''),
                'orgao_expedidor' => sanitizar($_POST['orgao_expedidor'] ?? ''),
                'titulo_eleitor' => sanitizar($_POST['titulo_eleitor'] ?? ''),
                'pis_pasep' => sanitizar($_POST['pis_pasep'] ?? ''),
                'certificado_reservista' => sanitizar($_POST['certificado_reservista'] ?? ''),
                'sexo' => sanitizar($_POST['sexo'] ?? ''),
                'pcd' => sanitizar($_POST['pcd'] ?? ''),
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
                'tipo_concorrencia' => sanitizar($_POST['tipo_concorrencia'] ?? ''),
                'tipo_funcionario' => sanitizar($_POST['tipo_funcionario'] ?? ''),
                'pos_graduacao' => sanitizar($_POST['pos_graduacao'] ?? '')
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

        // Novos campos obrigatórios
        $nomeCompleto = sanitizar($_POST['nome_completo'] ?? '');
        $rg = sanitizar($_POST['rg'] ?? '');
        $orgaoExpedidor = sanitizar($_POST['orgao_expedidor'] ?? '');
        $tituloEleitor = sanitizar($_POST['titulo_eleitor'] ?? '');
        $sexo = sanitizar($_POST['sexo'] ?? '');
        $email = sanitizar($_POST['email'] ?? '');
        $celular = sanitizar($_POST['celular'] ?? '');
        $cep = sanitizar($_POST['cep'] ?? '');
        $logradouro = sanitizar($_POST['logradouro'] ?? '');
        $numero = sanitizar($_POST['numero'] ?? '');
        $bairro = sanitizar($_POST['bairro'] ?? '');
        $cidade = sanitizar($_POST['cidade'] ?? '');
        $estado = sanitizar($_POST['estado'] ?? '');
        $tipoConcorrencia = sanitizar($_POST['tipo_concorrencia'] ?? '');
        $tipoFuncionario = sanitizar($_POST['tipo_funcionario'] ?? '');
        $posGraduacao = sanitizar($_POST['pos_graduacao'] ?? '');

        // Validação de campos obrigatórios
        if (empty($nomeCompleto)) {
            $erros[] = 'Nome completo é obrigatório.';
            $camposObrigatorios[] = 'nome_completo';
        }
        if (empty($rg)) {
            $erros[] = 'RG é obrigatório.';
            $camposObrigatorios[] = 'rg';
        }
        if (empty($orgaoExpedidor)) {
            $erros[] = 'Órgão expedidor é obrigatório.';
            $camposObrigatorios[] = 'orgao_expedidor';
        }
        if (empty($tituloEleitor)) {
            $erros[] = 'Título eleitoral é obrigatório.';
            $camposObrigatorios[] = 'titulo_eleitor';
        }
        if (empty($sexo)) {
            $erros[] = 'Sexo é obrigatório.';
            $camposObrigatorios[] = 'sexo';
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
        if (empty($tipoConcorrencia)) {
            $erros[] = 'Tipo de concorrência é obrigatório.';
            $camposObrigatorios[] = 'tipo_concorrencia';
        }
        if (empty($tipoFuncionario)) {
            $erros[] = 'Tipo de funcionário é obrigatório.';
            $camposObrigatorios[] = 'tipo_funcionario';
        }
        if (empty($posGraduacao)) {
            $erros[] = 'Seleção da pós-graduação é obrigatória.';
            $camposObrigatorios[] = 'pos_graduacao';
        }

        // Armazenar campos com erro na sessão para destacar no frontend
        $_SESSION['campos_erro'] = $camposObrigatorios;

        if (empty($erros)) {
            // Atualizar dados do usuário
            $queryUpdateUsuario = "UPDATE usuarios SET
                nome_completo = :nome_completo,
                rg = :rg,
                telefone_fixo = :telefone_fixo,
                celular = :celular,
                email = :email,
                email_alternativo = :email_alternativo
                WHERE id = :id";

            $stmtUpdateUsuario = $db->prepare($queryUpdateUsuario);
            $stmtUpdateUsuario->bindParam(':nome_completo', $nomeCompleto);
            $stmtUpdateUsuario->bindParam(':rg', $rg);
            
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
                    orgao_expedidor = :orgao_expedidor,
                    titulo_eleitor = :titulo_eleitor,
                    pis_pasep = :pis_pasep,
                    certificado_reservista = :certificado_reservista,
                    sexo = :sexo,
                    pcd = :pcd,
                    cep = :cep,
                    logradouro = :logradouro,
                    numero = :numero,
                    complemento = :complemento,
                    bairro = :bairro,
                    cidade = :cidade,
                    estado = :estado,
                    tipo_concorrencia = :tipo_concorrencia,
                    tipo_funcionario = :tipo_funcionario,
                    pos_graduacao = :pos_graduacao,
                    submitted_at = CURRENT_TIMESTAMP,
                    rascunho_data = NULL
                    WHERE id = :id";

                $stmtUpdateForm = $db->prepare($queryUpdateForm);
                $stmtUpdateForm->bindParam(':id', $formulario['id']);
                $formularioId = $formulario['id'];
            } else {
                $queryInsertForm = "INSERT INTO formularios (
                    usuario_id, orgao_expedidor, titulo_eleitor, pis_pasep, certificado_reservista,
                    sexo, pcd, cep, logradouro, numero, complemento, bairro, cidade, estado,
                    tipo_concorrencia, tipo_funcionario, pos_graduacao
                ) VALUES (
                    :usuario_id, :orgao_expedidor, :titulo_eleitor, :pis_pasep, :certificado_reservista,
                    :sexo, :pcd, :cep, :logradouro, :numero, :complemento, :bairro, :cidade, :estado,
                    :tipo_concorrencia, :tipo_funcionario, :pos_graduacao
                )";

                $stmtUpdateForm = $db->prepare($queryInsertForm);
                $stmtUpdateForm->bindParam(':usuario_id', $_SESSION['usuario_id']);
                $formularioId = null;
            }

            // Bind dos novos parâmetros
            $stmtUpdateForm->bindParam(':orgao_expedidor', $orgaoExpedidor);
            $stmtUpdateForm->bindParam(':titulo_eleitor', $tituloEleitor);
            
            $pisPasep = sanitizar($_POST['pis_pasep'] ?? '');
            $stmtUpdateForm->bindParam(':pis_pasep', $pisPasep);
            
            $certificadoReservista = sanitizar($_POST['certificado_reservista'] ?? '');
            $stmtUpdateForm->bindParam(':certificado_reservista', $certificadoReservista);
            
            $stmtUpdateForm->bindParam(':sexo', $sexo);
            
            $pcd = isset($_POST['pcd']) ? 1 : 0;
            $stmtUpdateForm->bindParam(':pcd', $pcd);
            
            $stmtUpdateForm->bindParam(':cep', $cep);
            $stmtUpdateForm->bindParam(':logradouro', $logradouro);
            $stmtUpdateForm->bindParam(':numero', $numero);
            
            $complemento = sanitizar($_POST['complemento'] ?? '');
            $stmtUpdateForm->bindParam(':complemento', $complemento);
            
            $stmtUpdateForm->bindParam(':bairro', $bairro);
            $stmtUpdateForm->bindParam(':cidade', $cidade);
            $stmtUpdateForm->bindParam(':estado', $estado);
            $stmtUpdateForm->bindParam(':tipo_concorrencia', $tipoConcorrencia);
            $stmtUpdateForm->bindParam(':tipo_funcionario', $tipoFuncionario);
            $stmtUpdateForm->bindParam(':pos_graduacao', $posGraduacao);

            $stmtUpdateForm->execute();

            if (!$formularioId) {
                $formularioId = $db->lastInsertId();
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