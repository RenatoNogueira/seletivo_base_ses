<?php
session_start();
require_once '../config/database.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$erro = '';

if ($_POST) {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    if (empty($email) || empty($senha)) {
        $erro = 'Por favor, preencha todos os campos.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, nome, email, senha, nivel_acesso, ativo FROM administradores WHERE email = ? AND ativo = 1");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($senha, $admin['senha'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_nome'] = $admin['nome'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_nivel'] = $admin['nivel_acesso'];

                $stmt = $pdo->prepare("UPDATE administradores SET ultimo_login = NOW() WHERE id = ?");
                $stmt->execute([$admin['id']]);

                $stmt = $pdo->prepare("INSERT INTO logs_admin (admin_id, acao, descricao, ip_address, user_agent) VALUES (?, 'login', 'Login realizado com sucesso', ?, ?)");
                $stmt->execute([$admin['id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);

                header('Location: dashboard.php');
                exit;
            } else {
                $erro = 'Email ou senha incorretos.';
            }
        } catch (PDOException $e) {
            $erro = 'Erro no sistema. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrativo - Seletivo SES</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
    body {
        background: linear-gradient(120deg, #6a11cb 0%, #2575fc 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Segoe UI', sans-serif;
    }

    .login-card {
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        max-width: 420px;
        width: 100%;
        overflow: hidden;
        animation: fadeIn 0.6s ease-in-out;
    }

    .login-header {
        background: linear-gradient(120deg, #6a11cb 0%, #2575fc 100%);
        color: #fff;
        padding: 2rem;
        text-align: center;
    }

    .login-header img {
        max-height: 60px;
    }

    .login-body {
        padding: 2rem;
    }

    .form-control {
        border-radius: 10px;
        padding: 12px;
    }

    .form-control:focus {
        border-color: #6a11cb;
        box-shadow: 0 0 0 0.2rem rgba(106, 17, 203, 0.25);
    }

    .btn-login {
        background: linear-gradient(120deg, #6a11cb 0%, #2575fc 100%);
        border: none;
        padding: 12px;
        border-radius: 10px;
        font-weight: 600;
        letter-spacing: 0.5px;
        transition: all 0.2s ease;
    }

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
    }

    .alert {
        border-radius: 10px;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-15px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    a {
        color: #6a11cb;
        transition: color 0.2s ease;
    }

    a:hover {
        color: #2575fc;
    }
    </style>
</head>

<body>

    <div class="login-card">
        <div class="login-header">
            <img src="../assets/images/branca.png" alt="Logo" class="mb-3" onerror="this.style.display='none'">
            <h3 class="mb-0">Área Administrativa</h3>
            <small>Seletivo SES</small>
        </div>
        <div class="login-body">
            <?php if ($erro): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($erro) ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope me-2"></i>Email
                    </label>
                    <input type="email" class="form-control" id="email" name="email"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                <div class="mb-4">
                    <label for="senha" class="form-label">
                        <i class="fas fa-lock me-2"></i>Senha
                    </label>
                    <input type="password" class="form-control" id="senha" name="senha" required>
                </div>
                <button type="submit" class="btn btn-primary btn-login w-100">
                    <i class="fas fa-sign-in-alt me-2"></i>Entrar
                </button>
            </form>

            <div class="text-center mt-4">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Acesso restrito a administradores
                </small>
            </div>

            <div class="text-center mt-3">
                <a href="../index.php">
                    <i class="fas fa-arrow-left me-1"></i>Voltar ao formulário
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>