<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

iniciarSessao();

date_default_timezone_set('America/Sao_Paulo'); // Força fuso horário do Brasil

$erro = '';

// Configurações de período de funcionamento
$dataAbertura = '2025-08-11';  // Data de abertura do formulário
$dataFechamento = '2025-08-15'; // Data de fechamento do formulário

// Verificar status atual
// Captura hora do computador do usuário se enviada
if (!empty($_POST['hora_local_usuario'])) {
    try {
        $dataUsuario = new DateTime($_POST['hora_local_usuario'], new DateTimeZone('UTC'));
        $dataUsuario->setTimezone(new DateTimeZone('America/Sao_Paulo'));
        $dataAtual = $dataUsuario->format('Y-m-d');
    } catch (Exception $e) {
        $dataAtual = date('Y-m-d'); // fallback para servidor
    }
} else {
    $dataAtual = date('Y-m-d'); // fallback para servidor
}
$formularioAberto = ($dataAtual >= $dataAbertura && $dataAtual <= $dataFechamento);

function gerarHashId($id)
{
    $chave_secreta = 'af1wzAcUWL';
    return hash('sha256', $id . $chave_secreta);
}

function exibirPeriodoInscricoes($abertura, $fechamento)
{
    $inicio = new DateTime($abertura);
    $fim = new DateTime($fechamento);
    $diasTotal = $inicio->diff($fim)->days + 1;

    return "Período: " . $inicio->format('d/m/Y') . " à " . $fim->format('d/m/Y') . " ($diasTotal dias)";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!$formularioAberto) {
        $erro = "Fora do período de inscrições. " . exibirPeriodoInscricoes($dataAbertura, $dataFechamento);
    } else {
        $cpf = sanitizar($_POST['cpf'] ?? '');
        $dataNascimento = sanitizar($_POST['data_nascimento'] ?? '');

        if (empty($cpf) || empty($dataNascimento)) {
            $erro = 'CPF e data de nascimento são obrigatórios.';
        } elseif (!validarCPF($cpf)) {
            $erro = 'CPF inválido.';
        } elseif (!validarDataNascimento($dataNascimento)) {
            $erro = 'Data de nascimento inválida ou idade menor que 16 anos.';
        } else {
            $database = new Database();
            $db = $database->getConnection();

            if ($db) {
                $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
                $query = "SELECT id, cpf, data_nascimento FROM usuarios WHERE cpf = :cpf";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':cpf', $cpfLimpo);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $usuario = $stmt->fetch();
                    if ($usuario['data_nascimento'] == $dataNascimento) {
                        $_SESSION['usuario_id'] = $usuario['id'];
                        $_SESSION['usuario_cpf'] = $usuario['cpf'];
                        $_SESSION['hash_id'] = gerarHashId($usuario['id']);
                        redirecionar('formulario.php?h=' . $_SESSION['hash_id']);
                    } else {
                        $erro = 'Data de nascimento não corresponde ao CPF cadastrado.';
                    }
                } else {
                    $query = "INSERT INTO usuarios (cpf, data_nascimento) VALUES (:cpf, :data_nascimento)";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':cpf', $cpfLimpo);
                    $stmt->bindParam(':data_nascimento', $dataNascimento);

                    try {
                        if ($stmt->execute()) {
                            $novoId = $db->lastInsertId();
                            $_SESSION['usuario_id'] = $novoId;
                            $_SESSION['usuario_cpf'] = $cpfLimpo;
                            $_SESSION['hash_id'] = gerarHashId($novoId);
                            redirecionar('formulario.php?h=' . $_SESSION['hash_id']);
                        } else {
                            $erro = 'Erro ao criar usuário. Tente novamente.';
                        }
                    } catch (PDOException $e) {
                        if ($e->getCode() == '23000') {
                            $erro = 'Este CPF já está cadastrado no sistema.';
                        } else {
                            $erro = 'Erro ao processar seu cadastro. Tente novamente.';
                        }
                    }
                }
            } else {
                $erro = 'Erro de conexão com o banco de dados.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seletivo PGS - 2025</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="./assets/css/index.css">
</head>

<body>
    <div class="login-card d-flex flex-column flex-md-row">
        <div class="login-info d-md-flex flex-column justify-content-center col-md-5">
            <h3>Bem-vindo!</h3>
            <p>Preencha seu CPF e data de nascimento para acessar ou criar seu cadastro.</p>
            <hr class="border-light">
            <p>Se você ainda não possui cadastro, o sistema criará automaticamente seu perfil.</p>

            <div class="periodo-funcionamento">
                <i class="fas fa-calendar-alt me-2"></i>
                <?= exibirPeriodoInscricoes($dataAbertura, $dataFechamento) ?>
            </div>
        </div>

        <div class="login-form col-12 col-md-7">
            <div class="text-center mb-4">
                <img src="assets/images/preta.png" alt="Logo" class="img-fluid mb-3" style="max-height: 60px;"
                    onerror="this.style.display='none'">
                <h2 class="h5">Seletivo PGS</h2>
                <p><small>Programa Gestão e Saúde</small></p>
            </div>

            <div class="status-sistema <?= $formularioAberto ? 'status-aberto' : 'status-fechado' ?>">
                <i class="fas <?= $formularioAberto ? 'fa-check-circle' : 'fa-times-circle' ?> me-2"></i>
                Inscrições <?= $formularioAberto ? 'ABERTAS' : 'ENCERRADAS' ?>
            </div>

            <?php if ($erro): ?>
            <div class="alert alert-danger"><?= $erro ?></div>
            <?php endif; ?>

            <form method="POST" id="loginForm"
                <?= !$formularioAberto ? 'onsubmit="event.preventDefault(); return false;"' : '' ?>>
                <div class="mb-3">
                    <label for="cpf" class="form-label">CPF</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="cpf" name="cpf" placeholder="000.000.000-00"
                            maxlength="14" required value="<?= htmlspecialchars($_POST['cpf'] ?? '') ?>"
                            <?= !$formularioAberto ? 'disabled' : '' ?>>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                        <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" required
                            value="<?= htmlspecialchars($_POST['data_nascimento'] ?? '') ?>"
                            <?= !$formularioAberto ? 'disabled' : '' ?>>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2" <?= !$formularioAberto ? 'disabled' : '' ?>>
                    <i class="fas <?= $formularioAberto ? 'fa-sign-in-alt' : 'fa-lock' ?> me-2"></i>
                    <?= $formularioAberto ? 'Entrar' : 'Formulário Fechado' ?>
                </button>
            </form>

            <div class="text-center mt-4">
                <small class="text-muted">Sistema seguro de cadastro de dados pessoais</small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.getElementById('cpf').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        e.target.value = value;
    });

    document.getElementById('loginForm').addEventListener('submit', function(e) {
        const cpf = document.getElementById('cpf').value.replace(/\D/g, '');
        const dataNascimento = document.getElementById('data_nascimento').value;

        if (cpf.length !== 11) {
            e.preventDefault();
            alert('CPF deve ter 11 dígitos.');
            return;
        }

        if (!dataNascimento) {
            e.preventDefault();
            alert('Data de nascimento é obrigatória.');
            return;
        }

        const hoje = new Date();
        const nascimento = new Date(dataNascimento);
        let idade = hoje.getFullYear() - nascimento.getFullYear();
        const mesAtual = hoje.getMonth();
        const mesNascimento = nascimento.getMonth();

        if (mesAtual < mesNascimento || (mesAtual === mesNascimento && hoje.getDate() < nascimento.getDate())) {
            idade--;
        }

        if (idade < 16) {
            e.preventDefault();
            alert('Idade mínima: 16 anos.');
        }
    });
    </script>
</body>

</html>