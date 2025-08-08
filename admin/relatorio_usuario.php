<?php
require_once '../config/database.php';
require_once 'functions.php';

// Verificar se está logado como admin
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('HTTP/1.0 403 Forbidden');
    exit('Acesso não autorizado');
}

$usuarioId = intval($_GET['id'] ?? 0);
$formularioId = intval($_GET['form_id'] ?? 0);

if (!$usuarioId) {
    header('HTTP/1.0 400 Bad Request');
    exit('ID do usuário não fornecido');
}

function formatarTamanhoArquivo($bytes)
{
    if ($bytes == 0) return '0 Bytes';

    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));

    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

$usuarioId = intval($_GET['id'] ?? 0);
$formularioId = intval($_GET['form_id'] ?? 0);

if (!$usuarioId) {
    header('HTTP/1.0 400 Bad Request');
    exit('ID do usuário não fornecido');
}


// Buscar dados do usuário
$usuario = obterUsuario($pdo, $usuarioId);
if (!$usuario) {
    header('HTTP/1.0 404 Not Found');
    exit('Usuário não encontrado');
}

// Limpar qualquer saída anterior para evitar problemas com TCPDF
ob_clean();

// Incluir a biblioteca TCPDF (ou Dompdf)
require_once('../vendor/tecnickcom/tcpdf/tcpdf.php');

// Criar novo documento PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Configurações do documento
$pdf->SetCreator('Sistema Seletivo SES');
$pdf->SetAuthor('Administração SES');
$pdf->SetTitle('Relatório do Usuário #' . $usuarioId);
$pdf->SetSubject('Relatório Completo do Candidato');

// Margens
$pdf->SetMargins(15, 15, 15);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);

// Quebra de página automática
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Adicionar página
$pdf->AddPage();

// Logo (opcional)
$logoPath = '../assets/img/logo-ses.png';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 15, 10, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
}

// Título
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Relatório do Candidato', 0, 1, 'C');
$pdf->Ln(10);

// Informações básicas
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Dados Pessoais', 0, 1);
$pdf->SetFont('helvetica', '', 10);

// Verificar se created_at existe no array
$dataCadastro = isset($usuario['created_at']) ? date('d/m/Y H:i:s', strtotime($usuario['created_at'])) : 'Não disponível';

$html = '
<table border="0" cellpadding="4">
    <tr>
        <td width="30%"><strong>Nome Completo:</strong></td>
        <td width="70%">' . htmlspecialchars($usuario['nome_completo'] ?? '') . '</td>
    </tr>
    <tr>
        <td><strong>CPF:</strong></td>
        <td>' . formatarCPF($usuario['cpf'] ?? '') . '</td>
    </tr>
    <tr>
        <td><strong>Data de Nascimento:</strong></td>
        <td>' . (isset($usuario['data_nascimento']) ? date('d/m/Y', strtotime($usuario['data_nascimento'])) : '') . '</td>
    </tr>
    <tr>
        <td><strong>RG:</strong></td>
        <td>' . htmlspecialchars($usuario['rg'] ?? '') . '</td>
    </tr>
    <tr>
        <td><strong>Estado Civil:</strong></td>
        <td>' . htmlspecialchars($usuario['estado_civil'] ?? '') . '</td>
    </tr>
    <tr>
        <td><strong>Nacionalidade:</strong></td>
        <td>' . htmlspecialchars($usuario['nacionalidade'] ?? '') . '</td>
    </tr>
    <tr>
        <td><strong>E-mail Principal:</strong></td>
        <td>' . htmlspecialchars($usuario['email'] ?? '') . '</td>
    </tr>
    <tr>
        <td><strong>E-mail Alternativo:</strong></td>
        <td>' . htmlspecialchars($usuario['email_alternativo'] ?? '') . '</td>
    </tr>
    <tr>
        <td><strong>Telefone Fixo:</strong></td>
        <td>' . formatarTelefone($usuario['telefone_fixo'] ?? '') . '</td>
    </tr>
    <tr>
        <td><strong>Celular:</strong></td>
        <td>' . formatarTelefone($usuario['celular'] ?? '') . '</td>
    </tr>
    <tr>
        <td><strong>Data de Cadastro:</strong></td>
        <td>' . $dataCadastro . '</td>
    </tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');

// Se existir formulário, adicionar mais informações
if ($formularioId) {
    $formulario = obterFormulario($pdo, $formularioId);
    $cursos = obterCursosUsuario($pdo, $formularioId);
    $arquivos = obterArquivosUsuario($pdo, $formularioId);

    // Endereço
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Endereço', 0, 1);
    $pdf->SetFont('helvetica', '', 10);

    $html = '
    <table border="0" cellpadding="4">
        <tr>
            <td width="30%"><strong>CEP:</strong></td>
            <td width="70%">' . htmlspecialchars($formulario['cep'] ?? '') . '</td>
        </tr>
        <tr>
            <td><strong>Logradouro:</strong></td>
            <td>' . htmlspecialchars($formulario['logradouro'] ?? '') . ', ' . htmlspecialchars($formulario['numero'] ?? '') . '</td>
        </tr>
        <tr>
            <td><strong>Complemento:</strong></td>
            <td>' . htmlspecialchars($formulario['complemento'] ?? '') . '</td>
        </tr>
        <tr>
            <td><strong>Bairro:</strong></td>
            <td>' . htmlspecialchars($formulario['bairro'] ?? '') . '</td>
        </tr>
        <tr>
            <td><strong>Cidade/UF:</strong></td>
            <td>' . htmlspecialchars($formulario['cidade'] ?? '') . '/' . htmlspecialchars($formulario['estado'] ?? '') . '</td>
        </tr>
    </table>';

    $pdf->writeHTML($html, true, false, true, false, '');

    // Informações do formulário
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Informações do Formulário', 0, 1);
    $pdf->SetFont('helvetica', '', 10);

    $dataEnvio = isset($formulario['submitted_at']) ? date('d/m/Y H:i:s', strtotime($formulario['submitted_at'])) : 'Não disponível';

    $html = '
    <table border="0" cellpadding="4">
        <tr>
            <td width="30%"><strong>Link do Vídeo:</strong></td>
            <td width="70%">' . htmlspecialchars($formulario['link_video'] ?? '') . '</td>
        </tr>
        <tr>
            <td><strong>Data de Envio:</strong></td>
            <td>' . $dataEnvio . '</td>
        </tr>
    </table>';

    $pdf->writeHTML($html, true, false, true, false, '');

    // Objetivos e Contribuições
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Objetivos e Contribuições', 0, 1);
    $pdf->SetFont('helvetica', '', 10);

    $html = '
    <table border="0" cellpadding="4">
        <tr>
            <td width="30%"><strong>Objetivos PGS:</strong></td>
            <td width="70%">' . nl2br(htmlspecialchars($formulario['objetivo_pgs'] ?? '')) . '</td>
        </tr>
        <tr>
            <td><strong>Atividades PGS:</strong></td>
            <td>' . nl2br(htmlspecialchars($formulario['atividades_pgs'] ?? '')) . '</td>
        </tr>
        <tr>
            <td><strong>Contribuição PGS:</strong></td>
            <td>' . nl2br(htmlspecialchars($formulario['contribuicao_pgs'] ?? '')) . '</td>
        </tr>
    </table>';

    $pdf->writeHTML($html, true, false, true, false, '');

    // Cursos e Formações
    if (!empty($cursos)) {
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Cursos e Formações (' . count($cursos) . ')', 0, 1);
        $pdf->SetFont('helvetica', '', 10);

        $html = '
        <table border="1" cellpadding="4">
            <thead>
                <tr style="background-color:#f2f2f2;">
                    <th width="20%"><strong>Nível</strong></th>
                    <th width="30%"><strong>Área de Formação</strong></th>
                    <th width="25%"><strong>Instituição</strong></th>
                    <th width="15%"><strong>Registro Profissional</strong></th>
                    <th width="10%"><strong>Ano Conclusão</strong></th>
                </tr>
            </thead>
            <tbody>';

        foreach ($cursos as $curso) {
            $html .= '
            <tr>
                <td>' . htmlspecialchars($curso['nivel'] ?? '') . '</td>
                <td>' . htmlspecialchars($curso['area_formacao'] ?? '') . '</td>
                <td>' . htmlspecialchars($curso['instituicao'] ?? '') . '</td>
                <td>' . htmlspecialchars($curso['registro_profissional'] ?? '') . '</td>
                <td>' . htmlspecialchars($curso['ano_conclusao'] ?? '') . '</td>
            </tr>';
        }

        $html .= '
            </tbody>
        </table>';

        $pdf->writeHTML($html, true, false, true, false, '');
    }

    // Arquivos enviados
    if (!empty($arquivos)) {
        $pdf->Ln(8);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Documentos Enviados (' . count($arquivos) . ')', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);

        $html = '
    <style>
        .document-table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 5px;
        }
        .document-table th {
            background-color: #f8f8f8;
            padding: 5px;
            text-align: left;
            border: 1px solid #ddd;
            font-weight: bold;
        }
        .document-table td {
            padding: 5px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        .document-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
    <table class="document-table">
        <thead>
            <tr>
                <th width="40%">Nome do Documento</th>
                <th width="25%">Tipo</th>
                <th width="15%">Tamanho</th>
                <th width="20%">Data Upload</th>
            </tr>
        </thead>
        <tbody>';

        foreach ($arquivos as $arquivo) {
            $nomeDocumento = htmlspecialchars($arquivo['nome_original'] ?? 'Sem nome');
            $tipoDocumento = htmlspecialchars($arquivo['tipo_documento'] ?? '');
            $tamanhoDocumento = formatarTamanhoArquivo($arquivo['tamanho'] ?? 0);
            $dataUpload = isset($arquivo['uploaded_at']) ? date('d/m/Y H:i', strtotime($arquivo['uploaded_at'])) : 'Não disponível';

            $html .= '
        <tr>
            <td>' . $nomeDocumento . '</td>
            <td>' . $tipoDocumento . '</td>
            <td style="text-align: center;">' . $tamanhoDocumento . '</td>
            <td style="text-align: center;">' . $dataUpload . '</td>
        </tr>';
        }

        $html .= '
        </tbody>
    </table>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Ln(5);
    }
} else {
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(0, 10, 'O usuário ainda não enviou nenhum formulário.', 0, 1);
}

// Rodapé
$pdf->SetY(-15);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 10, 'Gerado em ' . date('d/m/Y H:i:s'), 0, 0, 'C');

// Saída do PDF
$pdf->Output('relatorio_usuario_' . $usuarioId . '.pdf', 'I');