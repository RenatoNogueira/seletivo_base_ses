<?php
require_once './config/database.php';

try {
    // Conectar ao banco
    $database = new Database();
    $pdo = $database->getConnection();

    // Gerar hash da senha "admin123"
    $senha = 'admin123';
    $hash = password_hash($senha, PASSWORD_DEFAULT);

    echo "Atualizando senha do administrador...\n";
    echo "Senha: {$senha}\n";
    echo "Hash: {$hash}\n\n";

    // Atualizar no banco
    $stmt = $pdo->prepare("UPDATE administradores SET senha = ? WHERE email = 'admin@seletico.com'");
    $result = $stmt->execute([$hash]);

    if ($result) {
        echo "✅ Senha atualizada com sucesso!\n";

        // Verificar se a senha funciona
        $stmt = $pdo->prepare("SELECT senha FROM administradores WHERE email = 'admin@seletico.com'");
        $stmt->execute();
        $admin = $stmt->fetch();

        if ($admin && password_verify($senha, $admin['senha'])) {
            echo "✅ Verificação da senha: OK\n";
        } else {
            echo "❌ Verificação da senha: FALHOU\n";
        }
    } else {
        echo "❌ Erro ao atualizar senha\n";
    }
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
