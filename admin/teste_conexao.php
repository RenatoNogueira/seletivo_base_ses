<?php
// Ativa exibição de erros (opcional para debug)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Teste de Conexão - Banco de Dados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container py-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Teste de Conexão com Banco de Dados</h4>
            </div>
            <div class="card-body">
                <?php
                try {
                    echo "<p class='text-secondary'>1. Incluindo arquivo de configuração...</p>";
                    require_once '../config/database.php';
                    echo "<div class='alert alert-success'>✅ Arquivo incluído com sucesso!</div>";

                    echo "<p class='text-secondary'>2. Verificando variável \$pdo...</p>";
                    if (isset($pdo)) {
                        echo "<div class='alert alert-success'>✅ Variável \$pdo existe!</div>";
                        echo "<p><strong>Tipo:</strong> " . gettype($pdo) . "</p>";

                        if ($pdo instanceof PDO) {
                            echo "<div class='alert alert-success'>✅ \$pdo é uma instância válida de PDO!</div>";

                            echo "<p class='text-secondary'>3. Testando consulta simples...</p>";
                            $stmt = $pdo->query("SELECT 1 as teste");
                            $resultado = $stmt->fetch();

                            if ($resultado && $resultado['teste'] == 1) {
                                echo "<div class='alert alert-success'>✅ Consulta executada com sucesso!</div>";

                                echo "<p class='text-secondary'>4. Verificando tabela administradores...</p>";
                                $stmt = $pdo->query("SELECT COUNT(*) as total FROM administradores");
                                $resultado = $stmt->fetch();
                                echo "<div class='alert alert-success'>✅ Tabela encontrada! Total de registros: <strong>{$resultado['total']}</strong></div>";

                                echo "<p class='text-secondary'>5. Verificando administrador padrão...</p>";
                                $stmt = $pdo->prepare("SELECT id, nome, email, nivel_acesso FROM administradores WHERE email = ?");
                                $stmt->execute(['admin@seletico.com']);
                                $admin = $stmt->fetch();

                                if ($admin) {
                                    echo "<div class='alert alert-success'>✅ Administrador padrão encontrado!</div>";
                                    echo "<ul class='list-group mb-3'>
                                        <li class='list-group-item'><strong>ID:</strong> {$admin['id']}</li>
                                        <li class='list-group-item'><strong>Nome:</strong> {$admin['nome']}</li>
                                        <li class='list-group-item'><strong>Email:</strong> {$admin['email']}</li>
                                        <li class='list-group-item'><strong>Nível:</strong> {$admin['nivel_acesso']}</li>
                                      </ul>";
                                } else {
                                    echo "<div class='alert alert-danger'>❌ Administrador padrão não encontrado!</div>";
                                }
                            } else {
                                echo "<div class='alert alert-danger'>❌ Erro na consulta de teste!</div>";
                            }
                        } else {
                            echo "<div class='alert alert-danger'>❌ \$pdo não é uma instância válida de PDO!</div>";
                        }
                    } else {
                        echo "<div class='alert alert-danger'>❌ Variável \$pdo não existe!</div>";
                    }
                } catch (Exception $e) {
                    echo "<div class='alert alert-danger'>❌ Erro: {$e->getMessage()}</div>";
                    echo "<p><strong>Arquivo:</strong> {$e->getFile()}</p>";
                    echo "<p><strong>Linha:</strong> {$e->getLine()}</p>";
                }
                ?>
            </div>
            <div class="card-footer text-end">
                <a href="index.php" class="btn btn-secondary">
                    ← Voltar ao Login
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>