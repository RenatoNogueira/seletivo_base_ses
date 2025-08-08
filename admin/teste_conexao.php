<?php
echo "<h2>Teste de Conexão com Banco de Dados</h2>";

try {
    echo "<p>1. Incluindo arquivo de configuração...</p>";
    require_once '../config/database.php';
    echo "<p>✅ Arquivo incluído com sucesso!</p>";
    
    echo "<p>2. Verificando variável \$pdo...</p>";
    if (isset($pdo)) {
        echo "<p>✅ Variável \$pdo existe!</p>";
        echo "<p>Tipo: " . gettype($pdo) . "</p>";
        
        if ($pdo instanceof PDO) {
            echo "<p>✅ \$pdo é uma instância válida de PDO!</p>";
            
            echo "<p>3. Testando consulta simples...</p>";
            $stmt = $pdo->query("SELECT 1 as teste");
            $resultado = $stmt->fetch();
            
            if ($resultado && $resultado['teste'] == 1) {
                echo "<p>✅ Consulta executada com sucesso!</p>";
                
                echo "<p>4. Verificando tabela administradores...</p>";
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM administradores");
                $resultado = $stmt->fetch();
                echo "<p>✅ Tabela administradores encontrada! Total de registros: " . $resultado['total'] . "</p>";
                
                echo "<p>5. Verificando administrador padrão...</p>";
                $stmt = $pdo->prepare("SELECT id, nome, email, nivel_acesso FROM administradores WHERE email = ?");
                $stmt->execute(['admin@seletico.com']);
                $admin = $stmt->fetch();
                
                if ($admin) {
                    echo "<p>✅ Administrador padrão encontrado!</p>";
                    echo "<p>ID: " . $admin['id'] . "</p>";
                    echo "<p>Nome: " . $admin['nome'] . "</p>";
                    echo "<p>Email: " . $admin['email'] . "</p>";
                    echo "<p>Nível: " . $admin['nivel_acesso'] . "</p>";
                } else {
                    echo "<p>❌ Administrador padrão não encontrado!</p>";
                }
                
            } else {
                echo "<p>❌ Erro na consulta de teste!</p>";
            }
        } else {
            echo "<p>❌ \$pdo não é uma instância válida de PDO!</p>";
        }
    } else {
        echo "<p>❌ Variável \$pdo não existe!</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
    echo "<p>Arquivo: " . $e->getFile() . "</p>";
    echo "<p>Linha: " . $e->getLine() . "</p>";
}

echo "<hr>";
echo "<p><a href='login.php'>← Voltar ao Login</a></p>";
?>

