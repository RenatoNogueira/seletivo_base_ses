<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

iniciarSessao();

// Verificar se usuário está logado
if (!usuarioLogado()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $response = ['success' => true, 'files' => [], 'errors' => []];

    // Verificar se há arquivos
    if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
        throw new Exception('Nenhum arquivo foi enviado');
    }

    // Verificar se há tipos de documento
    if (!isset($_POST['types']) || !is_array($_POST['types'])) {
        throw new Exception('Tipos de documento não especificados');
    }

    $files = $_FILES['files'];
    $types = $_POST['types'];
    $uploadDir = 'uploads/';

    // Criar diretório de upload se não existir
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Criar diretório específico do usuário
    $userUploadDir = $uploadDir . 'user_' . $_SESSION['usuario_id'] . '/';
    if (!file_exists($userUploadDir)) {
        mkdir($userUploadDir, 0755, true);
    }

    // Processar cada arquivo
    for ($i = 0; $i < count($files['name']); $i++) {
        $fileName = $files['name'][$i];
        $fileTmpName = $files['tmp_name'][$i];
        $fileSize = $files['size'][$i];
        $fileError = $files['error'][$i];
        $fileType = $files['type'][$i];
        $documentType = $types[$i] ?? '';

        // Verificar se houve erro no upload
        if ($fileError !== UPLOAD_ERR_OK) {
            $response['errors'][] = "Erro no upload do arquivo: $fileName";
            continue;
        }

        // Verificar tipo de arquivo
        if ($fileType !== 'application/pdf') {
            $response['errors'][] = "Arquivo $fileName não é um PDF válido";
            continue;
        }

        // Verificar tamanho (10MB)
        if ($fileSize > 10 * 1024 * 1024) {
            $response['errors'][] = "Arquivo $fileName é muito grande (máximo 10MB)";
            continue;
        }

        // Verificar se tipo de documento foi especificado
        if (empty($documentType)) {
            $response['errors'][] = "Tipo de documento não especificado para: $fileName";
            continue;
        }

        // Gerar nome único para o arquivo
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $uniqueFileName = uniqid() . '_' . time() . '.' . $fileExtension;
        $filePath = $userUploadDir . $uniqueFileName;

        // Mover arquivo para diretório de destino
        if (move_uploaded_file($fileTmpName, $filePath)) {
            // Verificar se já existe um formulário para este usuário ou criar um temporário
            $queryFormulario = "SELECT id FROM formularios WHERE usuario_id = :usuario_id ORDER BY id DESC LIMIT 1";
            $stmtFormulario = $db->prepare($queryFormulario);
            $stmtFormulario->bindParam(':usuario_id', $_SESSION['usuario_id']);
            $stmtFormulario->execute();
            $formulario = $stmtFormulario->fetch(PDO::FETCH_ASSOC);

            $formularioId = null;
            if ($formulario) {
                $formularioId = $formulario['id'];
            } else {
                // Criar formulário temporário para armazenar os uploads
                $queryCreateForm = "INSERT INTO formularios (usuario_id, rascunho_data) VALUES (:usuario_id, JSON_OBJECT('temp', true))";
                $stmtCreateForm = $db->prepare($queryCreateForm);
                $stmtCreateForm->bindParam(':usuario_id', $_SESSION['usuario_id']);
                if ($stmtCreateForm->execute()) {
                    $formularioId = $db->lastInsertId();
                }
            }

            if ($formularioId) {
                // Salvar informações no banco de dados
                $query = "INSERT INTO arquivos_upload (
                    formulario_id, nome_original, nome_salvo, tipo_documento,
                    tamanho, tipo_mime, caminho_arquivo, uploaded_at
                ) VALUES (
                    :formulario_id, :nome_original, :nome_salvo, :tipo_documento,
                    :tamanho, :tipo_mime, :caminho_arquivo, CURRENT_TIMESTAMP
                )";

                $stmt = $db->prepare($query);
                $stmt->bindParam(':formulario_id', $formularioId);
                $stmt->bindParam(':nome_original', $fileName);
                $stmt->bindParam(':nome_salvo', $uniqueFileName);
                $stmt->bindParam(':tipo_documento', $documentType);
                $stmt->bindParam(':tamanho', $fileSize);
                $stmt->bindParam(':tipo_mime', $fileType);
                $stmt->bindParam(':caminho_arquivo', $filePath);

                if ($stmt->execute()) {
                    $response['files'][] = [
                        'id' => $db->lastInsertId(),
                        'original_name' => $fileName,
                        'saved_name' => $uniqueFileName,
                        'type' => $documentType,
                        'size' => $fileSize,
                        'status' => 'success'
                    ];
                } else {
                    // Se falhou ao salvar no banco, remover arquivo
                    unlink($filePath);
                    $response['errors'][] = "Erro ao salvar informações do arquivo: $fileName no banco de dados";
                }
            } else {
                // Se não conseguiu criar/encontrar formulário, remover arquivo
                unlink($filePath);
                $response['errors'][] = "Erro ao criar formulário para o arquivo: $fileName";
            }
        } else {
            $response['errors'][] = "Erro ao mover arquivo: $fileName";
        }
    }

    // Se houve erros mas também sucessos, ainda consideramos sucesso parcial
    if (!empty($response['files'])) {
        $response['success'] = true;
        $response['message'] = count($response['files']) . ' arquivo(s) enviado(s) com sucesso';

        if (!empty($response['errors'])) {
            $response['message'] .= '. ' . count($response['errors']) . ' arquivo(s) com erro';
        }
    } else {
        $response['success'] = false;
        $response['message'] = 'Nenhum arquivo foi enviado com sucesso';
    }

    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}