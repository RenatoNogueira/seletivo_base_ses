<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

// Set JSON headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

iniciarSessao();

// Initialize response structure
$response = [
    'success' => false,
    'message' => '',
    'files' => [],
    'errors' => []
];

// Check if user is logged in
if (!usuarioLogado()) {
    http_response_code(401);
    $response['errors'][] = 'Usuário não autenticado';
    echo json_encode($response);
    exit;
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response['errors'][] = 'Método não permitido';
    echo json_encode($response);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // Verify files were uploaded
    if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
        throw new Exception('Nenhum arquivo foi enviado');
    }

    // Verify document types were specified
    if (!isset($_POST['types']) || !is_array($_POST['types'])) {
        throw new Exception('Tipos de documento não especificados');
    }

    $files = $_FILES['files'];
    $types = $_POST['types'];
    $uploadDir = 'uploads/';
    $usuarioId = $_SESSION['usuario_id'];

    // Create upload directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Create user-specific directory
    $userUploadDir = $uploadDir . 'user_' . $usuarioId . '/';
    if (!file_exists($userUploadDir)) {
        mkdir($userUploadDir, 0755, true);
    }

    // Process each file
    foreach ($files['name'] as $i => $fileName) {
        $fileTmpName = $files['tmp_name'][$i];
        $fileSize = $files['size'][$i];
        $fileError = $files['error'][$i];
        $fileType = $files['type'][$i];
        $documentType = $types[$i] ?? '';

        // Skip file if there was an upload error
        if ($fileError !== UPLOAD_ERR_OK) {
            $response['errors'][] = "Erro no upload do arquivo: $fileName";
            continue;
        }

        // Validate file type (PDF only)
        if ($fileType !== 'application/pdf') {
            $response['errors'][] = "Arquivo $fileName não é um PDF válido";
            continue;
        }

        // Validate file size (10MB max)
        if ($fileSize > 10 * 1024 * 1024) {
            $response['errors'][] = "Arquivo $fileName é muito grande (máximo 10MB)";
            continue;
        }

        // Validate document type was specified
        if (empty($documentType)) {
            $response['errors'][] = "Tipo de documento não especificado para: $fileName";
            continue;
        }

        // Generate unique filename
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $uniqueFileName = uniqid() . '_' . time() . '.' . $fileExtension;
        $filePath = $userUploadDir . $uniqueFileName;

        // Move file to destination
        if (move_uploaded_file($fileTmpName, $filePath)) {
            // Check for existing form or create a temporary one
            $queryFormulario = "SELECT id FROM formularios WHERE usuario_id = :usuario_id ORDER BY id DESC LIMIT 1";
            $stmtFormulario = $db->prepare($queryFormulario);
            $stmtFormulario->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmtFormulario->execute();
            $formulario = $stmtFormulario->fetch(PDO::FETCH_ASSOC);

            $formularioId = null;
            if ($formulario) {
                $formularioId = $formulario['id'];
            } else {
                // Create temporary form record
                $queryCreateForm = "INSERT INTO formularios (usuario_id, status, rascunho_data)
                                    VALUES (:usuario_id, 'incompleto', JSON_OBJECT('temp', true))";
                $stmtCreateForm = $db->prepare($queryCreateForm);
                $stmtCreateForm->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
                if ($stmtCreateForm->execute()) {
                    $formularioId = $db->lastInsertId();
                }
            }

            if ($formularioId) {
                // Save file info to database
                $query = "INSERT INTO arquivos_upload (
                    formulario_id, nome_original, nome_salvo, tipo_documento,
                    tamanho, tipo_mime, caminho_arquivo, uploaded_at
                ) VALUES (
                    :formulario_id, :nome_original, :nome_salvo, :tipo_documento,
                    :tamanho, :tipo_mime, :caminho_arquivo, CURRENT_TIMESTAMP
                )";

                $stmt = $db->prepare($query);
                $stmt->bindParam(':formulario_id', $formularioId, PDO::PARAM_INT);
                $stmt->bindParam(':nome_original', $fileName);
                $stmt->bindParam(':nome_salvo', $uniqueFileName);
                $stmt->bindParam(':tipo_documento', $documentType);
                $stmt->bindParam(':tamanho', $fileSize, PDO::PARAM_INT);
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
                    // Remove file if database insert fails
                    unlink($filePath);
                    $response['errors'][] = "Erro ao salvar informações do arquivo: $fileName no banco de dados";
                }
            } else {
                // Remove file if form creation fails
                unlink($filePath);
                $response['errors'][] = "Erro ao criar formulário para o arquivo: $fileName";
            }
        } else {
            $response['errors'][] = "Erro ao mover arquivo: $fileName";
        }
    }

    // Set success message if any files were uploaded
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
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    $response['errors'][] = $e->getMessage();
    echo json_encode($response);
}