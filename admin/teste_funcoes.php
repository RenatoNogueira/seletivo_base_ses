<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Debug Funções de Usuários</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container py-4">
        <div class="card shadow">
            <div class="card-header bg-dark text-white">
                <h4 class="mb-0">DEBUG COMPLETO - Teste das Funções de Usuários</h4>
            </div>
            <div class="card-body">
                <?php
                try {
                    echo "<p class='text-secondary'>1. Incluindo arquivos necessários...</p>";
                    require_once '../config/database.php';
                    require_once 'functions.php';
                    echo "<div class='alert alert-success'>✅ Arquivos incluídos com sucesso!</div>";

                    echo "<p class='text-secondary'>2. Verificando conexão PDO...</p>";
                    if ($pdo) {
                        echo "<div class='alert alert-success'>✅ Conexão PDO OK</div>";
                        echo "<p><strong>Tipo de conexão:</strong> " . get_class($pdo) . "</p>";
                    } else {
                        echo "<div class='alert alert-danger'>❌ Conexão PDO FALHOU!</div>";
                        exit;
                    }

                    echo "<p class='text-secondary'>3. Testando consulta SQL direta...</p>";
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
                    $totalUsuarios = $stmt->fetch()['total'];
                    echo "<div class='alert alert-success'>✅ Total de usuários: <strong>{$totalUsuarios}</strong></div>";

                    if ($totalUsuarios == 0) {
                        echo "<div class='alert alert-danger'>❌ Nenhum usuário encontrado!</div>";
                        exit;
                    }

                    echo "<p class='text-secondary'>4. Testando consulta com dados...</p>";
                    $stmt = $pdo->query("SELECT id, nome_completo, cpf FROM usuarios LIMIT 3");
                    $usuariosDiretos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo "<div class='alert alert-success'>✅ Consulta retornou " . count($usuariosDiretos) . " registros</div>";

                    if (!empty($usuariosDiretos)) {
                        echo "<ul class='list-group mb-3'>";
                        foreach ($usuariosDiretos as $usuario) {
                            echo "<li class='list-group-item'>ID: {$usuario['id']} - {$usuario['nome_completo']} - {$usuario['cpf']}</li>";
                        }
                        echo "</ul>";
                    }

                    echo "<p class='text-secondary'>5. Testando função <code>buscarUsuarios()</code>...</p>";
                    $usuarios = buscarUsuarios($pdo, [], 5, 0);
                    echo "<div class='alert alert-success'>✅ Função retornou " . count($usuarios) . " usuários</div>";

                    if (!empty($usuarios)) {
                        echo "<p class='fw-bold text-success'>✅ SUCESSO! Função funcionando!</p>";
                        echo "<div class='table-responsive'>
                            <table class='table table-bordered table-striped'>
                                <thead class='table-dark'>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome</th>
                                        <th>CPF</th>
                                        <th>Email</th>
                                    </tr>
                                </thead>
                                <tbody>";
                        foreach ($usuarios as $usuario) {
                            echo "<tr>
                                <td>{$usuario['usuario_id']}</td>
                                <td>" . sanitizar($usuario['nome_completo']) . "</td>
                                <td>" . sanitizar($usuario['cpf']) . "</td>
                                <td>" . sanitizar($usuario['email']) . "</td>
                              </tr>";
                        }
                        echo "  </tbody>
                            </table>
                          </div>";
                    } else {
                        echo "<div class='alert alert-danger fw-bold'>❌ PROBLEMA: Função retorna array vazio!</div>";

                        echo "<p class='fw-bold'>6. DEBUG AVANÇADO</p>";
                        $sql = "SELECT id as usuario_id, nome_completo, cpf, email FROM usuarios ORDER BY created_at DESC";
                        echo "<p>SQL: <code>{$sql}</code></p>";

                        try {
                            $stmt = $pdo->prepare($sql);
                            echo "<div class='alert alert-success'>✅ Consulta preparada com sucesso</div>";

                            $resultado = $stmt->execute();
                            echo "<div class='alert alert-info'>Execução retornou: " . ($resultado ? 'TRUE' : 'FALSE') . "</div>";

                            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            echo "<div class='alert alert-success'>✅ fetchAll() retornou: <strong>" . count($dados) . "</strong> registros</div>";

                            if (!empty($dados)) {
                                echo "<ul class='list-group mb-3'>";
                                foreach (array_slice($dados, 0, 3) as $registro) {
                                    echo "<li class='list-group-item'><pre>" . print_r($registro, true) . "</pre></li>";
                                }
                                echo "</ul>";
                            }
                        } catch (Exception $e) {
                            echo "<div class='alert alert-danger'>❌ Erro na consulta: {$e->getMessage()}</div>";
                        }
                    }

                    echo "<p class='text-secondary'>7. Testando função <code>obterEstatisticas()</code>...</p>";
                    $stats = obterEstatisticas($pdo);
                    echo "<ul class='list-group mb-3'>";
                    foreach ($stats as $chave => $valor) {
                        echo "<li class='list-group-item'><strong>{$chave}:</strong> {$valor}</li>";
                    }
                    echo "</ul>";

                    echo "<p class='text-secondary'>8. Testando função <code>contarUsuarios()</code>...</p>";
                    $total = contarUsuarios($pdo);
                    echo "<div class='alert alert-success'>✅ Total de usuários: <strong>{$total}</strong></div>";

                    echo "<hr>";
                    if (!empty($usuarios)) {
                        echo "<div class='alert alert-success fw-bold'>🎉 PROBLEMA RESOLVIDO! Função buscarUsuarios() funcionando.</div>";
                    } else {
                        echo "<div class='alert alert-danger fw-bold'>🚨 AINDA HÁ PROBLEMAS! Função buscarUsuarios() não retornou dados.</div>";
                    }
                } catch (Exception $e) {
                    echo "<div class='alert alert-danger'>❌ Erro geral: {$e->getMessage()}</div>";
                    echo "<p><strong>Arquivo:</strong> {$e->getFile()}</p>";
                    echo "<p><strong>Linha:</strong> {$e->getLine()}</p>";
                    echo "<pre>{$e->getTraceAsString()}</pre>";
                }
                ?>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="usuarios.php" class="btn btn-primary">← Ir para Página de Usuários</a>
                <a href="index.php" class="btn btn-secondary">← Voltar ao Login</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>