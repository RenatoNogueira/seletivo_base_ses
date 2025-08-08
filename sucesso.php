<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

iniciarSessao();

// Verificar se usuário está logado
if (!usuarioLogado()) {
    redirecionar('index.php');
}

// Obter dados do usuário e formulário
$database = new Database();
$db = $database->getConnection();

$usuario_id = $_SESSION['usuario_id'];

// Buscar dados do usuário
$queryUsuario = "SELECT * FROM usuarios WHERE id = :usuario_id";
$stmtUsuario = $db->prepare($queryUsuario);
$stmtUsuario->bindParam(':usuario_id', $usuario_id);
$stmtUsuario->execute();
$usuario = $stmtUsuario->fetch();

// Buscar dados do formulário
$queryFormulario = "SELECT * FROM formularios WHERE usuario_id = :usuario_id ORDER BY id DESC LIMIT 1";
$stmtFormulario = $db->prepare($queryFormulario);
$stmtFormulario->bindParam(':usuario_id', $usuario_id);
$stmtFormulario->execute();
$formulario = $stmtFormulario->fetch();

// Buscar cursos de formação
$cursos = [];
if ($formulario) {
    $queryCursos = "SELECT * FROM cursos_formacoes WHERE formulario_id = :formulario_id";
    $stmtCursos = $db->prepare($queryCursos);
    $stmtCursos->bindParam(':formulario_id', $formulario['id']);
    $stmtCursos->execute();
    $cursos = $stmtCursos->fetchAll(PDO::FETCH_ASSOC);
}

// Buscar arquivos enviados
$arquivos = [];
if ($formulario) {
    $queryArquivos = "SELECT * FROM arquivos_upload WHERE formulario_id = :formulario_id";
    $stmtArquivos = $db->prepare($queryArquivos);
    $stmtArquivos->bindParam(':formulario_id', $formulario['id']);
    $stmtArquivos->execute();
    $arquivos = $stmtArquivos->fetchAll(PDO::FETCH_ASSOC);
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

    /* Estilos para a impressão */
    @page {
        size: A4;
        margin: 2cm;
    }

    @media print {
        body * {
            visibility: hidden;
        }

        #printable-area,
        #printable-area * {
            visibility: visible;
        }

        #printable-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            padding: 0;
            margin: 0;
            background: white;
        }

        .no-print {
            display: none !important;
        }

        .page-break {
            page-break-after: always;
        }
    }

    .document-header {
        border-bottom: 3px solid #2F3D71;
        padding-bottom: 15px;
        margin-bottom: 30px;
    }

    .document-title {
        color: #2F3D71;
        font-weight: bold;
        text-align: center;
        margin-bottom: 5px;
    }

    .document-subtitle {
        color: #555;
        text-align: center;
        font-size: 1.1rem;
        margin-bottom: 20px;
    }

    .section-title {
        color: #2F3D71;
        border-bottom: 1px solid #ddd;
        padding-bottom: 5px;
        margin-top: 25px;
        margin-bottom: 15px;
        font-size: 1.2rem;
    }

    .info-label {
        font-weight: bold;
        color: #555;
        min-width: 180px;
        display: inline-block;
    }

    .info-value {
        color: #333;
    }

    .document-footer {
        margin-top: 40px;
        padding-top: 15px;
        border-top: 1px solid #ddd;
        font-size: 0.9rem;
        color: #666;
        text-align: center;
    }

    .signature-line {
        border-top: 1px solid #000;
        width: 300px;
        margin: 40px auto 10px;
        text-align: center;
        padding-top: 5px;
    }

    .document-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 30px;
        background: white;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .watermark {
        position: fixed;
        opacity: 0.1;
        font-size: 80px;
        color: #2F3D71;
        transform: rotate(-45deg);
        z-index: -1;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-45deg);
        white-space: nowrap;
    }
    </style>
</head>

<body>
    <div class="success-card no-print">
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

            <button onclick="window.print()" class="btn btn-outline-primary">
                <i class="fas fa-print me-2"></i>Imprimir Ficha de Inscrição
            </button>
        </div>

        <div class="mt-4">
            <small class="text-muted">
                Data de envio: <?= date('d/m/Y H:i:s') ?>
            </small>
        </div>
    </div>

    <!-- Área para impressão (hidden até ser impressa) -->
    <div id="printable-area" class="document-container" style="display: none;">
        <div class="watermark">CONFIDENCIAL</div>

        <div class="document-header">
            <div class="row">
                <div class="col-3 text-left mb-5">
                    <img src="./assets/images/branca.png" alt="Logo" style="height: 80px;">
                </div>
                <div class="col-6 text-center mt-5">
                    <h1 class="document-title">FICHA DE INSCRIÇÃO</h1>
                    <h2 class="document-subtitle">PROGRAMA GESTÃO EM SAÚDE (PGS)</h2>
                </div>
                <div class="col-3 text-right">
                    <p style="font-size: 0.9rem; margin-bottom: 0;">
                        <strong>Nº Inscrição:</strong><br>
                        <?= str_pad($usuario['id'], 6, '0', STR_PAD_LEFT) ?>
                    </p>
                    <p style="font-size: 0.9rem;">
                        <strong>Data:</strong> <?= date('d/m/Y') ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="section-title">1. DADOS PESSOAIS</div>
        <div class="row">
            <div class="col-md-6">
                <p><span class="info-label">Nome Completo:</span> <span
                        class="info-value"><?= htmlspecialchars($usuario['nome_completo'] ?? '') ?></span></p>
                <p><span class="info-label">CPF:</span> <span
                        class="info-value"><?= htmlspecialchars($usuario['cpf'] ?? '') ?></span></p>
                <p><span class="info-label">RG:</span> <span
                        class="info-value"><?= htmlspecialchars($usuario['rg'] ?? '') ?></span></p>
            </div>
            <div class="col-md-6">
                <p><span class="info-label">Data Nascimento:</span> <span
                        class="info-value"><?= date('d/m/Y', strtotime($usuario['data_nascimento'])) ?></span></p>
                <p><span class="info-label">Estado Civil:</span> <span
                        class="info-value"><?= htmlspecialchars($usuario['estado_civil'] ?? '') ?></span></p>
                <p><span class="info-label">Nacionalidade:</span> <span
                        class="info-value"><?= htmlspecialchars($usuario['nacionalidade'] ?? '') ?></span></p>
            </div>
        </div>

        <div class="section-title">2. CONTATO</div>
        <div class="row">
            <div class="col-md-6">
                <p><span class="info-label">Telefone Fixo:</span> <span
                        class="info-value"><?= htmlspecialchars($usuario['telefone_fixo'] ?? '') ?></span></p>
                <p><span class="info-label">Celular:</span> <span
                        class="info-value"><?= htmlspecialchars($usuario['celular'] ?? '') ?></span></p>
            </div>
            <div class="col-md-6">
                <p><span class="info-label">E-mail:</span> <span
                        class="info-value"><?= htmlspecialchars($usuario['email'] ?? '') ?></span></p>
                <p><span class="info-label">E-mail Alternativo:</span> <span
                        class="info-value"><?= htmlspecialchars($usuario['email_alternativo'] ?? '') ?></span></p>
            </div>
        </div>

        <div class="section-title">3. ENDEREÇO</div>
        <div class="row">
            <div class="col-12">
                <p><span class="info-label">Logradouro:</span> <span
                        class="info-value"><?= htmlspecialchars($formulario['logradouro'] ?? '') ?>,
                        <?= htmlspecialchars($formulario['numero'] ?? '') ?></span></p>
                <p><span class="info-label">Complemento:</span> <span
                        class="info-value"><?= htmlspecialchars($formulario['complemento'] ?? '') ?></span></p>
                <p><span class="info-label">Bairro:</span> <span
                        class="info-value"><?= htmlspecialchars($formulario['bairro'] ?? '') ?></span></p>
                <p><span class="info-label">Cidade/Estado:</span> <span
                        class="info-value"><?= htmlspecialchars($formulario['cidade'] ?? '') ?>/<?= htmlspecialchars($formulario['estado'] ?? '') ?></span>
                </p>
                <p><span class="info-label">CEP:</span> <span
                        class="info-value"><?= htmlspecialchars($formulario['cep'] ?? '') ?></span></p>
            </div>
        </div>

        <?php if (!empty($cursos)): ?>
        <div class="section-title">4. FORMAÇÃO ACADÊMICA</div>
        <?php foreach ($cursos as $index => $curso): ?>
        <div class="mb-3 p-3 border" style="page-break-inside: avoid;">
            <h5 style="color: #2F3D71;">Formação <?= $index + 1 ?></h5>
            <p><span class="info-label">Nível:</span> <span
                    class="info-value"><?= htmlspecialchars($curso['nivel'] ?? '') ?></span></p>
            <p><span class="info-label">Área de Formação:</span> <span
                    class="info-value"><?= htmlspecialchars($curso['area_formacao'] ?? '') ?></span></p>
            <?php if (!empty($curso['registro_profissional'])): ?>
            <p><span class="info-label">Registro Profissional:</span> <span
                    class="info-value"><?= htmlspecialchars($curso['registro_profissional'] ?? '') ?></span></p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($arquivos)): ?>
        <div class="section-title">5. DOCUMENTOS ENVIADOS</div>
        <ul>
            <?php foreach ($arquivos as $arquivo): ?>
            <li><?= htmlspecialchars($arquivo['nome_original']) ?> (<?= htmlspecialchars($arquivo['tipo_documento']) ?>)
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <div class="section-title">6. PITCH VÍDEO</div>
        <p><span class="info-label">Link do vídeo:</span> <span
                class="info-value"><?= htmlspecialchars($formulario['link_video'] ?? '') ?></span></p>

        <div class="section-title">7. RESPOSTAS PGS</div>

        <div class="mb-4">
            <h5 style="color: #555;">Objetivo de participar do PGS</h5>
            <div style="border: 1px solid #eee; padding: 10px; background: #f9f9f9; min-height: 100px;">
                <?= nl2br(htmlspecialchars($formulario['objetivo_pgs'] ?? '')) ?>
            </div>
        </div>

        <div class="mb-4">
            <h5 style="color: #555;">Atividades e funções no PGS</h5>
            <div style="border: 1px solid #eee; padding: 10px; background: #f9f9f9; min-height: 100px;">
                <?= nl2br(htmlspecialchars($formulario['atividades_pgs'] ?? '')) ?>
            </div>
        </div>

        <div class="mb-4">
            <h5 style="color: #555;">Contribuição para a gestão da saúde pública</h5>
            <div style="border: 1px solid #eee; padding: 10px; background: #f9f9f9; min-height: 100px;">
                <?= nl2br(htmlspecialchars($formulario['contribuicao_pgs'] ?? '')) ?>
            </div>
        </div>

        <div class="document-footer">
            <p>Documento gerado automaticamente em <?= date('d/m/Y H:i:s') ?></p>
            <p>Sistema de Inscrição PGS - Secretaria de Estado da Saúde</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Mostrar a área de impressão apenas quando for imprimir
    document.addEventListener('DOMContentLoaded', function() {
        const beforePrint = () => {
            document.getElementById('printable-area').style.display = 'block';
        };

        const afterPrint = () => {
            document.getElementById('printable-area').style.display = 'none';
        };

        if (window.matchMedia) {
            const mediaQueryList = window.matchMedia('print');
            mediaQueryList.addListener((mql) => {
                if (mql.matches) {
                    beforePrint();
                } else {
                    afterPrint();
                }
            });
        }

        window.onbeforeprint = beforePrint;
        window.onafterprint = afterPrint;
    });
    </script>
</body>

</html>