<?php
echo "<h2>DEBUG COMPLETO - Teste das Funções de Usuários</h2>";

try {
    echo "<p>1. Incluindo arquivos necessários...</p>";
    require_once '../config/database.php';
    require_once 'functions.php';
    echo "<p>✅ Arquivos incluídos com sucesso!</p>";
    
    echo "<p>2. Verificando conexão PDO...</p>";
    if ($pdo) {
        echo "<p>✅ Conexão PDO OK</p>";
        echo "<p>Tipo de conexão: " . get_class($pdo) . "</p>";
    } else {
        echo "<p>❌ Conexão PDO FALHOU!</p>";
        exit;
    }
    
    echo "<p>3. Testando consulta SQL direta...</p>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $totalUsuarios = $stmt->fetch()['total'];
    echo "<p>✅ Total de usuários na tabela: <strong>{$totalUsuarios}</strong></p>";
    
    if ($totalUsuarios == 0) {
        echo "<p>❌ PROBLEMA: Não há usuários na tabela!</p>";
        exit;
    }
    
    echo "<p>4. Testando consulta SQL direta com dados...</p>";
    $stmt = $pdo->query("SELECT id, nome_completo, cpf FROM usuarios LIMIT 3");
    $usuariosDiretos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>✅ Consulta direta retornou: <strong>" . count($usuariosDiretos) . "</strong> registros</p>";
    
    if (!empty($usuariosDiretos)) {
        echo "<ul>";
        foreach ($usuariosDiretos as $usuario) {
            echo "<li>ID: {$usuario['id']} - {$usuario['nome_completo']} - {$usuario['cpf']}</li>";
        }
        echo "</ul>";
    }
    
    echo "<p>5. Testando função buscarUsuarios() - VERSÃO DEBUG...</p>";
    $usuarios = buscarUsuarios($pdo, [], 5, 0);
    echo "<p>✅ Função buscarUsuarios() retornou: <strong>" . count($usuarios) . "</strong> usuários</p>";
    
    if (!empty($usuarios)) {
        echo "<p style='color: green; font-size: 18px; font-weight: bold;'>✅ SUCESSO! Função funcionando!</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background-color: #f0f0f0;'><th>ID</th><th>Nome</th><th>CPF</th><th>Email</th></tr>";
        
        foreach ($usuarios as $usuario) {
            echo "<tr>";
            echo "<td>" . $usuario['usuario_id'] . "</td>";
            echo "<td>" . sanitizar($usuario['nome_completo']) . "</td>";
            echo "<td>" . sanitizar($usuario['cpf']) . "</td>";
            echo "<td>" . sanitizar($usuario['email']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red; font-size: 18px; font-weight: bold;'>❌ PROBLEMA: Função retorna array vazio!</p>";
        
        echo "<p>6. DEBUG AVANÇADO - Testando passo a passo...</p>";
        
        // Teste 1: Preparar consulta
        echo "<p>6.1. Preparando consulta SQL...</p>";
        $sql = "SELECT id as usuario_id, nome_completo, cpf, email FROM usuarios ORDER BY created_at DESC";
        echo "<p>SQL: <code>{$sql}</code></p>";
        
        try {
            $stmt = $pdo->prepare($sql);
            echo "<p>✅ Consulta preparada com sucesso</p>";
            
            // Teste 2: Executar consulta
            echo "<p>6.2. Executando consulta...</p>";
            $resultado = $stmt->execute();
            echo "<p>✅ Execução retornou: " . ($resultado ? 'TRUE' : 'FALSE') . "</p>";
            
            // Teste 3: Buscar resultados
            echo "<p>6.3. Buscando resultados...</p>";
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<p>✅ fetchAll() retornou: <strong>" . count($dados) . "</strong> registros</p>";
            
            if (!empty($dados)) {
                echo "<p>Primeiros registros:</p>";
                echo "<ul>";
                foreach (array_slice($dados, 0, 3) as $registro) {
                    echo "<li>" . print_r($registro, true) . "</li>";
                }
                echo "</ul>";
            }
            
        } catch (Exception $e) {
            echo "<p>❌ Erro na consulta: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<p>7. Testando função obterEstatisticas()...</p>";
    $stats = obterEstatisticas($pdo);
    echo "<p>✅ Estatísticas obtidas:</p>";
    echo "<ul>";
    foreach ($stats as $chave => $valor) {
        echo "<li><strong>{$chave}:</strong> {$valor}</li>";
    }
    echo "</ul>";
    
    echo "<p>8. Testando função contarUsuarios()...</p>";
    $total = contarUsuarios($pdo);
    echo "<p>✅ Total de usuários: <strong>{$total}</strong></p>";
    
    echo "<hr>";
    if (!empty($usuarios)) {
        echo "<p style='color: green; font-size: 20px; font-weight: bold;'>🎉 PROBLEMA RESOLVIDO!</p>";
        echo "<p>A função buscarUsuarios() está funcionando corretamente.</p>";
    } else {
        echo "<p style='color: red; font-size: 20px; font-weight: bold;'>🚨 AINDA HÁ PROBLEMAS!</p>";
        echo "<p>A função buscarUsuarios() ainda não está retornando dados.</p>";
        echo "<p>Verifique os logs de erro do PHP para mais detalhes.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro geral: " . $e->getMessage() . "</p>";
    echo "<p>Arquivo: " . $e->getFile() . "</p>";
    echo "<p>Linha: " . $e->getLine() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='usuarios.php'>← Ir para Página de Usuários</a></p>";
echo "<p><a href='login.php'>← Voltar ao Login</a></p>";
?>

