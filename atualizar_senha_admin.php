<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $nome  = 'Administrador';
    $email = 'paulo.vitor@saude.ma.gov.br';
    $senha = 'paulo@vitor'; // altere conforme necessário
    $nivel = 'super_admin';

    $hash = password_hash($senha, PASSWORD_DEFAULT);

    // Verifica se o email já existe
    $stmt = $pdo->prepare("SELECT id FROM administradores WHERE email = ?");
    $stmt->execute([$email]);
    $existe = $stmt->fetch();

    if ($existe) {
        // Atualiza senha
        $stmt = $pdo->prepare("UPDATE administradores SET senha = ? WHERE email = ?");
        $stmt->execute([$hash, $email]);
        echo "✅ Senha atualizada para {$email}\n";
    } else {
        // Insere novo administrador
        $stmt = $pdo->prepare("INSERT INTO administradores (nome, email, senha, nivel_acesso) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nome, $email, $hash, $nivel]);
        echo "✅ Novo administrador criado: {$email}\n";
    }
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
