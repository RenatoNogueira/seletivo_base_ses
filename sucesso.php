<?php
require_once 'includes/functions.php';

iniciarSessao();

// Verificar se usuário está logado
if (!usuarioLogado()) {
    redirecionar('index.php');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulário Enviado com Sucesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
    body {
        background: linear-gradient(135deg, #e3f2fd 0%, #c5cae9 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .success-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        padding: 3rem;
        text-align: center;
        max-width: 500px;
        width: 100%;
    }

    .success-icon {
        font-size: 4rem;
        color: #10b981;
        margin-bottom: 1.5rem;
    }

    .btn-primary {
        background-color: #6366f1;
        border-color: #6366f1;
    }

    .btn-primary:hover {
        background-color: #5855eb;
        border-color: #5855eb;
    }
    </style>
</head>

<body>
    <div class="success-card">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>

        <h2 class="text-success mb-3">Formulário Enviado com Sucesso!</h2>

        <p class="text-muted mb-4">
            Seus dados foram enviados e salvos com sucesso. Obrigado por preencher o formulário completo.
        </p>

        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Importante:</strong> Seus dados foram registrados em nosso sistema e não podem mais ser editados.
        </div>

        <div class="d-grid gap-2">
            <a href="logout.php" class="btn btn-primary">
                <i class="fas fa-sign-out-alt me-2"></i>Sair do Sistema
            </a>

            <!-- <a href="formulario.php" class="btn btn-outline-secondary">
                <i class="fas fa-eye me-2"></i>Visualizar Dados Enviados
            </a> -->
        </div>

        <div class="mt-4">
            <small class="text-muted">
                Data de envio: <?= date('d/m/Y H:i:s') ?>
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>