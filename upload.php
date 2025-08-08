<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

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

$uploadDir = 'uploads/';
$maxFiles = 7;
$maxFileSize = 2 * 1024 * 1024; // 10MB

// Verificar se o diretório de upload existe
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$uploadedFiles = [];
$errors = [];

// Verificar se há arquivos para upload
if (!isset($_FILES['arquivos']) || empty($_FILES['arquivos']['name'][0])) {
    echo json_encode(['success' => false, 'message' => 'Nenhum arquivo selecionado']);
    exit;
}

$files = $_FILES['arquivos'];
$fileCount = count($files['name']);

// Verificar limite de arquivos
if ($fileCount > $maxFiles) {
    echo json_encode(['success' => false, 'message' => "Máximo de {$maxFiles} arquivos permitidos"]);
    exit;
}

// Processar cada arquivo
for ($i = 0; $i < $fileCount; $i++) {
    if ($files['error'][$i] === UPLOAD_ERR_OK) {
        $fileName = $files['name'][$i];
        $fileTmpName = $files['tmp_name'][$i];
        $fileSize = $files['size'][$i];
        $fileType = $files['type'][$i];

        // Validar arquivo
        $validation = validarArquivoPDF([
            'name' => $fileName,
            'type' => $fileType,
            'size' => $fileSize
        ]);

        if ($validation !== true) {
            $errors[] = "Arquivo {$fileName}: {$validation}";
            continue;
        }

        // Gerar nome único
        $uniqueName = gerarNomeArquivo($fileName);
        $uploadPath = $uploadDir . $uniqueName;

        // Mover arquivo
        if (move_uploaded_file($fileTmpName, $uploadPath)) {
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
                    formulario_id, nome_original, nome_salvo,
                    tamanho, tipo_mime, caminho_arquivo, uploaded_at
                ) VALUES (
                    :formulario_id, :nome_original, :nome_salvo,
                    :tamanho, :tipo_mime, :caminho_arquivo, CURRENT_TIMESTAMP
                )";

                $stmt = $db->prepare($query);
                $stmt->bindParam(':formulario_id', $formularioId);
                $stmt->bindParam(':nome_original', $fileName);
                $stmt->bindParam(':nome_salvo', $uniqueName);
                $stmt->bindParam(':tamanho', $fileSize);
                $stmt->bindParam(':tipo_mime', $fileType);
                $stmt->bindParam(':caminho_arquivo', $uploadPath);

                if ($stmt->execute()) {
                    $uploadedFiles[] = [
                        'id' => $db->lastInsertId(),
                        'original_name' => $fileName,
                        'saved_name' => $uniqueName,
                        'path' => $uploadPath,
                        'size' => $fileSize,
                        'type' => $fileType
                    ];
                } else {
                    // Se falhou ao salvar no banco, remover arquivo
                    unlink($uploadPath);
                    $errors[] = "Erro ao salvar informações do arquivo {$fileName} no banco de dados";
                }
            } else {
                // Se não conseguiu criar/encontrar formulário, remover arquivo
                unlink($uploadPath);
                $errors[] = "Erro ao criar formulário para o arquivo {$fileName}";
            }
        } else {
            $errors[] = "Erro ao salvar arquivo {$fileName}";
        }
    } else {
        $errors[] = "Erro no upload do arquivo {$files['name'][$i]}";
    }
}

// Resposta
if (!empty($uploadedFiles)) {
    echo json_encode([
        'success' => true,
        'message' => count($uploadedFiles) . ' arquivo(s) enviado(s) com sucesso',
        'files' => $uploadedFiles,
        'errors' => $errors
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Nenhum arquivo foi enviado com sucesso',
        'errors' => $errors
    ]);
}