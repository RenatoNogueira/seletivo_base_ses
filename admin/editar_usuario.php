<?php
session_start();
require_once '../config/database.php';
require_once 'functions.php';

verificarLogin();

$usuarioId = intval($_GET['id'] ?? 0);
$sucesso = '';
$erro = '';

if (!$usuarioId) {
    header('Location: usuarios.php');
    exit;
}

// Buscar dados do usuário
$usuario = obterUsuario($pdo, $usuarioId);

if (!$usuario) {
    header('Location: usuarios.php?erro=usuario_nao_encontrado');
    exit;
}

// Processar formulário
if ($_POST) {
    try {
        $nome_completo = trim($_POST['nome_completo']);
        $email = trim($_POST['email']);
        $telefone_fixo = trim($_POST['telefone_fixo']);
        $celular = trim($_POST['celular']);
        $email_alternativo = trim($_POST['email_alternativo']);
        
        // Validações básicas
        if (empty($nome_completo)) {
            throw new Exception('Nome completo é obrigatório.');
        }
        
        if (empty($email) || !validarEmail($email)) {
            throw new Exception('Email válido é obrigatório.');
        }
        
        // Atualizar usuário
        $stmt = $pdo->prepare("UPDATE usuarios SET nome_completo = ?, email = ?, telefone_fixo = ?, celular = ?, email_alternativo = ? WHERE id = ?");
        $stmt->execute([$nome_completo, $email, $telefone_fixo, $celular, $email_alternativo, $usuarioId]);
        
        // Atualizar endereço se houver formulário
        if ($usuario['formulario_id'] && isset($_POST['cep'])) {
            $cep = trim($_POST['cep']);
            $logradouro = trim($_POST['logradouro']);
            $numero = trim($_POST['numero']);
            $complemento = trim($_POST['complemento']);
            $bairro = trim($_POST['bairro']);
            $cidade = trim($_POST['cidade']);
            $estado = trim($_POST['estado']);
            
            $stmt = $pdo->prepare("UPDATE formularios SET cep = ?, logradouro = ?, numero = ?, complemento = ?, bairro = ?, cidade = ?, estado = ? WHERE id = ?");
            $stmt->execute([$cep, $logradouro, $numero, $complemento, $bairro, $cidade, $estado, $usuario['formulario_id']]);
        }
        
        registrarLog($pdo, 'editar_usuario', "Usuário ID {$usuarioId} editado com sucesso");
        $sucesso = 'Usuário atualizado com sucesso!';
        
        // Recarregar dados
        $usuario = obterUsuario($pdo, $usuarioId);
        
    } catch (Exception $e) {
        $erro = $e->getMessage();
        registrarLog($pdo, 'erro_editar_usuario', "Erro ao editar usuário ID {$usuarioId}: " . $e->getMessage());
    }
}

registrarLog($pdo, 'acesso_editar_usuario', "Acesso à edição do usuário ID: {$usuarioId}");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário - Admin Seletico SES</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            border-radius: 8px;
            margin: 2px 0;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }
        .admin-header {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 1rem 0;
        }
        .form-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar p-3">
                    <div class="text-center mb-4">
                        <i class="fas fa-shield-alt fa-3x mb-2"></i>
                        <h5>Admin Panel</h5>
                        <small>Seletico SES</small>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link active" href="usuarios.php">
                            <i class="fas fa-users me-2"></i>Usuários
                        </a>
                        <a class="nav-link" href="formularios.php">
                            <i class="fas fa-file-alt me-2"></i>Formulários
                        </a>
                        <a class="nav-link" href="arquivos.php">
                            <i class="fas fa-folder me-2"></i>Arquivos
                        </a>
                        <a class="nav-link" href="relatorios.php">
                            <i class="fas fa-chart-bar me-2"></i>Relatórios
                        </a>
                        <a class="nav-link" href="configuracoes.php">
                            <i class="fas fa-cog me-2"></i>Configurações
                        </a>
                        <a class="nav-link" href="logs.php">
                            <i class="fas fa-history me-2"></i>Logs
                        </a>
                        
                        <hr class="my-3">
                        
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Sair
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <!-- Header -->
                <div class="admin-header">
                    <div class="container-fluid">
                        <div class="row align-items-center">
                            <div class="col">
                                <h4 class="mb-0">Editar Usuário</h4>
                                <small class="text-muted"><?= sanitizar($usuario['nome_completo']) ?></small>
                            </div>
                            <div class="col-auto">
                                <a href="usuarios.php" class="btn btn-outline-primary">
                                    <i class="fas fa-arrow-left me-2"></i>Voltar à Lista
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Content -->
                <div class="container-fluid py-4">
                    <?php if ($sucesso): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i>
                            <?= htmlspecialchars($sucesso) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($erro): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?= htmlspecialchars($erro) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-12">
                            <div class="card form-card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-edit me-2"></i>Dados do Usuário
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="row">
                                            <!-- Informações Pessoais -->
                                            <div class="col-md-6">
                                                <h6 class="text-primary mb-3">Informações Pessoais</h6>
                                                
                                                <div class="mb-3">
                                                    <label for="nome_completo" class="form-label">Nome Completo *</label>
                                                    <input type="text" class="form-control" id="nome_completo" name="nome_completo" 
                                                           value="<?= htmlspecialchars($usuario['nome_completo']) ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="cpf" class="form-label">CPF</label>
                                                    <input type="text" class="form-control" id="cpf" 
                                                           value="<?= formatarCPF($usuario['cpf']) ?>" readonly>
                                                    <small class="text-muted">CPF não pode ser alterado</small>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                                                    <input type="date" class="form-control" id="data_nascimento" 
                                                           value="<?= $usuario['data_nascimento'] ?>" readonly>
                                                    <small class="text-muted">Data de nascimento não pode ser alterada</small>
                                                </div>
                                            </div>
                                            
                                            <!-- Contato -->
                                            <div class="col-md-6">
                                                <h6 class="text-success mb-3">Contato</h6>
                                                
                                                <div class="mb-3">
                                                    <label for="email" class="form-label">Email Principal *</label>
                                                    <input type="email" class="form-control" id="email" name="email" 
                                                           value="<?= htmlspecialchars($usuario['email']) ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="email_alternativo" class="form-label">Email Alternativo</label>
                                                    <input type="email" class="form-control" id="email_alternativo" name="email_alternativo" 
                                                           value="<?= htmlspecialchars($usuario['email_alternativo']) ?>">
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="telefone_fixo" class="form-label">Telefone Fixo</label>
                                                            <input type="text" class="form-control" id="telefone_fixo" name="telefone_fixo" 
                                                                   value="<?= htmlspecialchars($usuario['telefone_fixo']) ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="celular" class="form-label">Celular</label>
                                                            <input type="text" class="form-control" id="celular" name="celular" 
                                                                   value="<?= htmlspecialchars($usuario['celular']) ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if ($usuario['formulario_id']): ?>
                                        <hr>
                                        
                                        <!-- Endereço -->
                                        <div class="row">
                                            <div class="col-12">
                                                <h6 class="text-info mb-3">Endereço</h6>
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label for="cep" class="form-label">CEP</label>
                                                    <input type="text" class="form-control" id="cep" name="cep" 
                                                           value="<?= htmlspecialchars($usuario['cep']) ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="logradouro" class="form-label">Logradouro</label>
                                                    <input type="text" class="form-control" id="logradouro" name="logradouro" 
                                                           value="<?= htmlspecialchars($usuario['logradouro']) ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label for="numero" class="form-label">Número</label>
                                                    <input type="text" class="form-control" id="numero" name="numero" 
                                                           value="<?= htmlspecialchars($usuario['numero']) ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="complemento" class="form-label">Complemento</label>
                                                    <input type="text" class="form-control" id="complemento" name="complemento" 
                                                           value="<?= htmlspecialchars($usuario['complemento']) ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="bairro" class="form-label">Bairro</label>
                                                    <input type="text" class="form-control" id="bairro" name="bairro" 
                                                           value="<?= htmlspecialchars($usuario['bairro']) ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label for="cidade" class="form-label">Cidade</label>
                                                    <input type="text" class="form-control" id="cidade" name="cidade" 
                                                           value="<?= htmlspecialchars($usuario['cidade']) ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-1">
                                                <div class="mb-3">
                                                    <label for="estado" class="form-label">UF</label>
                                                    <input type="text" class="form-control" id="estado" name="estado" 
                                                           value="<?= htmlspecialchars($usuario['estado']) ?>" maxlength="2">
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="row">
                                            <div class="col-12 text-end">
                                                <a href="usuarios.php" class="btn btn-outline-secondary">
                                                    <i class="fas fa-times me-2"></i>Cancelar
                                                </a>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-save me-2"></i>Salvar Alterações
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

