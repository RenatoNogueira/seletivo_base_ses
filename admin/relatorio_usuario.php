<?php
require_once '../config/database.php';
require_once 'functions.php';

date_default_timezone_set('America/Sao_Paulo');

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

// Buscar dados do usuário
$usuario = obterUsuario($pdo, $usuarioId);
if (!$usuario) {
    header('HTTP/1.0 404 Not Found');
    exit('Usuário não encontrado');
}

// Limpar saída anterior para evitar erro no PDF
ob_clean();

// Incluir biblioteca TCPDF
require_once('../vendor/tecnickcom/tcpdf/tcpdf.php');

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('Sistema Seletivo SES');
$pdf->SetAuthor('Administração SES');
$pdf->SetTitle('Relatório do Usuário #' . $usuarioId);
$pdf->SetSubject('Relatório Completo do Candidato');
$pdf->SetMargins(15, 15, 15);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
$pdf->AddPage();

// Logo
$logoPath = '../assets/img/logo-ses.png';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 15, 10, 30, '', 'PNG');
}

$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Relatório do Candidato', 0, 1, 'C');
$pdf->Ln(10);

// ==========================
// DADOS PESSOAIS
// ==========================
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Dados Pessoais', 0, 1);
$pdf->SetFont('helvetica', '', 10);

$dataCadastro = isset($usuario['created_at']) ? date('d/m/Y H:i:s', strtotime($usuario['created_at'])) : 'Não disponível';

$html = '<table border="0" cellpadding="4">
    <tr><td width="30%"><strong>ID Usuário:</strong></td><td width="70%">' . ($usuario['usuario_id'] ?? '') . '</td></tr>
    <tr><td><strong>Nome Completo:</strong></td><td>' . htmlspecialchars($usuario['nome_completo'] ?? '') . '</td></tr>
    <tr><td><strong>CPF:</strong></td><td>' . formatarCPF($usuario['cpf'] ?? '') . '</td></tr>
    <tr><td><strong>RG:</strong></td><td>' . htmlspecialchars($usuario['rg'] ?? '') . '</td></tr>
    <tr><td><strong>Data de Nascimento:</strong></td><td>' . (isset($usuario['data_nascimento']) ? date('d/m/Y', strtotime($usuario['data_nascimento'])) : '') . '</td></tr>
    <tr><td><strong>Estado Civil:</strong></td><td>' . htmlspecialchars($usuario['estado_civil'] ?? '') . '</td></tr>
    <tr><td><strong>Nacionalidade:</strong></td><td>' . htmlspecialchars($usuario['nacionalidade'] ?? '') . '</td></tr>
    <tr><td><strong>Email Principal:</strong></td><td>' . htmlspecialchars($usuario['email'] ?? '') . '</td></tr>
    <tr><td><strong>Email Alternativo:</strong></td><td>' . htmlspecialchars($usuario['email_alternativo'] ?? '') . '</td></tr>
    <tr><td><strong>Telefone Fixo:</strong></td><td>' . formatarTelefone($usuario['telefone_fixo'] ?? '') . '</td></tr>
    <tr><td><strong>Celular:</strong></td><td>' . formatarTelefone($usuario['celular'] ?? '') . '</td></tr>
    <tr><td><strong>Data Cadastro:</strong></td><td>' . (isset($usuario['data_cadastro']) ? date('d/m/Y H:i:s', strtotime($usuario['data_cadastro'])) : '') . '</td></tr>

    <tr><td><strong>ID Formulário:</strong></td><td>' . ($usuario['formulario_id'] ?? '') . '</td></tr>
    <tr><td><strong>CEP:</strong></td><td>' . htmlspecialchars($usuario['cep'] ?? '') . '</td></tr>
    <tr><td><strong>Logradouro:</strong></td><td>' . htmlspecialchars($usuario['logradouro'] ?? '') . ', ' . htmlspecialchars($usuario['numero'] ?? '') . '</td></tr>
    <tr><td><strong>Complemento:</strong></td><td>' . htmlspecialchars($usuario['complemento'] ?? '') . '</td></tr>
    <tr><td><strong>Bairro:</strong></td><td>' . htmlspecialchars($usuario['bairro'] ?? '') . '</td></tr>
    <tr><td><strong>Cidade/Estado:</strong></td><td>' . htmlspecialchars($usuario['cidade'] ?? '') . '/' . htmlspecialchars($usuario['estado'] ?? '') . '</td></tr>
    <tr><td><strong>Vídeo:</strong></td><td>' . htmlspecialchars($usuario['link_video'] ?? '') . '</td></tr>
    <tr><td><strong>Data Envio Formulário:</strong></td><td>' . (isset($usuario['data_envio_formulario']) ? date('d/m/Y H:i:s', strtotime($usuario['data_envio_formulario'])) : '') . '</td></tr>



    <tr><td><strong>Objetivos PGS:</strong></td><td>' . nl2br(htmlspecialchars($usuario['objetivo_pgs'] ?? '')) . '</td></tr>
    <tr><td><strong>Atividades PGS:</strong></td><td>' . nl2br(htmlspecialchars($usuario['atividades_pgs'] ?? '')) . '</td></tr>
    <tr><td><strong>Contribuição PGS:</strong></td><td>' . nl2br(htmlspecialchars($usuario['contribuicao_pgs'] ?? '')) . '</td></tr>

    <tr><td><strong>Total Cursos:</strong></td><td>' . ($usuario['total_cursos'] ?? 0) . '</td></tr>
    <tr><td><strong>Áreas de Formação:</strong></td><td>' . htmlspecialchars($usuario['areas_formacao'] ?? '') . '</td></tr>
    <tr><td><strong>Registros Profissionais:</strong></td><td>' . htmlspecialchars($usuario['registros_profissionais'] ?? '') . '</td></tr>

</table>';


$arquivos = [];
if (!empty($usuario['formulario_id'])) {
    $stmt = $pdo->prepare("SELECT nome_original, tipo_documento
                           FROM arquivos_upload
                           WHERE formulario_id = ?");
    $stmt->execute([$usuario['formulario_id']]);
    $arquivos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// Se houver arquivos, exibir lista detalhada
if (!empty($arquivos)) {
    $html .= '<br><strong>Arquivos Enviados no cadastro (' . count($arquivos) . ')</strong> <br><br>
    <table border="1" cellpadding="4">
        <tr style="background-color:#f2f2f2;">
            <th width="70%">Nome do Arquivo</th>
            <th width="30%">Tipo de Documento</th>
        </tr>';
    foreach ($arquivos as $arq) {
        $html .= '<tr>
            <td>' . htmlspecialchars($arq['nome_original']) . '</td>
            <td>' . htmlspecialchars($usuario['tipos_documentos'] ?? '') . '</td>
        </tr>';
    }
    $html .= '</table>';
}


$pdf->writeHTML($html, true, false, true, false, '');

// ==========================
// SE EXISTIR FORMULÁRIO
// ==========================
if ($formularioId) {
    $formulario = obterFormulario($pdo, $formularioId);
    $cursos = obterCursosUsuario($pdo, $formularioId);
    $arquivos = obterArquivosUsuario($pdo, $formularioId);

    // Endereço
    $pdf->Ln(8);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Endereço', 0, 1);
    $pdf->SetFont('helvetica', '', 10);

    $html = '<table border="0" cellpadding="4">
        <tr><td width="30%"><strong>CEP:</strong></td><td width="70%">' . htmlspecialchars($formulario['cep'] ?? '') . '</td></tr>
        <tr><td><strong>Logradouro:</strong></td><td>' . htmlspecialchars($formulario['logradouro'] ?? '') . ', ' . htmlspecialchars($formulario['numero'] ?? '') . '</td></tr>
        <tr><td><strong>Complemento:</strong></td><td>' . htmlspecialchars($formulario['complemento'] ?? '') . '</td></tr>
        <tr><td><strong>Bairro:</strong></td><td>' . htmlspecialchars($formulario['bairro'] ?? '') . '</td></tr>
        <tr><td><strong>Cidade/UF:</strong></td><td>' . htmlspecialchars($formulario['cidade'] ?? '') . '/' . htmlspecialchars($formulario['estado'] ?? '') . '</td></tr>
    </table>';
    $pdf->writeHTML($html, true, false, true, false, '');

    // Informações adicionais do formulário
    $pdf->Ln(8);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Informações do Formulário', 0, 1);
    $pdf->SetFont('helvetica', '', 10);

    $dataEnvio = isset($formulario['submitted_at']) ? date('d/m/Y H:i:s', strtotime($formulario['submitted_at'])) : 'Não disponível';

    $html = '<table border="0" cellpadding="4">
        <tr><td width="30%"><strong>Link do Vídeo:</strong></td><td width="70%">' . htmlspecialchars($formulario['link_video'] ?? '') . '</td></tr>
        <tr><td><strong>Data Envio:</strong></td><td>' . $dataEnvio . '</td></tr>
        <tr><td><strong>Objetivos PGS:</strong></td><td>' . nl2br(htmlspecialchars($formulario['objetivo_pgs'] ?? '')) . '</td></tr>
        <tr><td><strong>Atividades PGS:</strong></td><td>' . nl2br(htmlspecialchars($formulario['atividades_pgs'] ?? '')) . '</td></tr>
        <tr><td><strong>Contribuição PGS:</strong></td><td>' . nl2br(htmlspecialchars($formulario['contribuicao_pgs'] ?? '')) . '</td></tr>
    </table>';
    $pdf->writeHTML($html, true, false, true, false, '');

    // Cursos e Formações
    if (!empty($cursos)) {
        $pdf->Ln(8);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Cursos e Formações (' . count($cursos) . ')', 0, 1);
        $pdf->SetFont('helvetica', '', 10);

        $html = '<table border="1" cellpadding="4">
            <thead>
                <tr style="background-color:#f2f2f2;">
                    <th width="20%"><strong>Nível</strong></th>
                    <th width="25%"><strong>Área</strong></th>
                    <th width="25%"><strong>Instituição</strong></th>
                    <th width="15%"><strong>Registro</strong></th>
                    <th width="15%"><strong>Ano</strong></th>
                </tr>
            </thead><tbody>';
        foreach ($cursos as $curso) {
            $html .= '<tr>
                <td>' . htmlspecialchars($curso['nivel'] ?? '') . '</td>
                <td>' . htmlspecialchars($curso['area_formacao'] ?? '') . '</td>
                <td>' . htmlspecialchars($curso['instituicao'] ?? '') . '</td>
                <td>' . htmlspecialchars($curso['registro_profissional'] ?? '') . '</td>
                <td>' . htmlspecialchars($curso['ano_conclusao'] ?? '') . '</td>
            </tr>';
        }
        $html .= '</tbody></table>';
        $pdf->writeHTML($html, true, false, true, false, '');
    }
}

// Rodapé
$pdf->SetY(-15);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 10, 'Gerado em ' . date('d/m/Y H:i:s'), 0, 0, 'C');

// Saída
$pdf->Output('relatorio_usuario_' . $usuarioId . '.pdf', 'I');
