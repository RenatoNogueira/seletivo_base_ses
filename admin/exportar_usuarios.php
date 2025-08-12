<?php
session_start();
require_once '../config/database.php';
require_once 'functions.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

try {
    // Filtros
    $filtros = [
        'termo' => $_GET['termo'] ?? '',
        'cidade' => $_GET['cidade'] ?? '',
        'estado' => $_GET['estado'] ?? '',
        'data_inicio' => $_GET['data_inicio'] ?? '',
        'data_fim' => $_GET['data_fim'] ?? ''
    ];

    $usuarios = buscarUsuarios($pdo, $filtros, 10000, 0);

    // Log
    $termoLog = !empty($filtros['termo']) ? "termo: '{$filtros['termo']}'" : '';
    $cidadeLog = !empty($filtros['cidade']) ? "cidade: '{$filtros['cidade']}'" : '';
    $estadoLog = !empty($filtros['estado']) ? "estado: '{$filtros['estado']}'" : '';
    $filtrosLog = array_filter([$termoLog, $cidadeLog, $estadoLog]);
    $descricaoLog = 'Exportação de ' . count($usuarios) . ' usuários' . (!empty($filtrosLog) ? ' com filtros: ' . implode(', ', $filtrosLog) : '');
    registrarLog($pdo, 'exportar_usuarios', $descricaoLog);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Cabeçalhos
    $headers = [
        'ID',
        'Nome Completo',
        'CPF',
        'RG',
        'Data de Nascimento',
        'Estado Civil',
        'Nacionalidade',
        'Email Principal',
        'Email Alternativo',
        'Telefone Fixo',
        'Celular',
        'CEP',
        'Logradouro',
        'Número',
        'Complemento',
        'Bairro',
        'Cidade',
        'Estado',
        'Link do Vídeo',
        'Data de Cadastro',
        'Data Envio Formulário',
        'Total de Cursos',
        'Total de Arquivos',
        'Áreas de Formação',
        'Registros Profissionais',
        'Tipos de Documentos'
    ];
    $sheet->fromArray($headers, null, 'A1');

    // Estilo do cabeçalho
    $headerStyle = [
        'font' => ['bold' => true],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'color' => ['rgb' => 'D9D9D9']
        ]
    ];
    $sheet->getStyle('A1:Z1')->applyFromArray($headerStyle);

    // Funções para formatação de documentos
    $formatarCPF = function ($cpf) {
        $cpf = preg_replace('/\D/', '', $cpf);
        return preg_match('/^\d{11}$/', $cpf) ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf) : $cpf;
    };
    $formatarRG = function ($rg) {
        $rg = preg_replace('/\D/', '', $rg);
        return preg_match('/^\d{9}$/', $rg) ? preg_replace('/(\d{2})(\d{3})(\d{3})(\d{1})/', '$1.$2.$3-$4', $rg) : $rg;
    };

    // Inserir dados
    $linha = 2;
    foreach ($usuarios as $usuario) {
        $sheet->fromArray([
            $usuario['usuario_id'],
            $usuario['nome_completo'] ?? '',
            $formatarCPF($usuario['cpf'] ?? ''),
            $formatarRG($usuario['rg'] ?? ''),
            $usuario['data_nascimento'] ? date('d/m/Y', strtotime($usuario['data_nascimento'])) : '',
            $usuario['estado_civil'] ?? '',
            $usuario['nacionalidade'] ?? '',
            $usuario['email'] ?? '',
            $usuario['email_alternativo'] ?? '',
            $usuario['telefone_fixo'] ?? '',
            $usuario['celular'] ?? '',
            $usuario['cep'] ?? '',
            $usuario['logradouro'] ?? '',
            $usuario['numero'] ?? '',
            $usuario['complemento'] ?? '',
            $usuario['bairro'] ?? '',
            $usuario['cidade'] ?? '',
            $usuario['estado'] ?? '',
            $usuario['link_video'] ?? '',
            $usuario['data_cadastro'] ? date('d/m/Y H:i:s', strtotime($usuario['data_cadastro'])) : '',
            $usuario['data_envio_formulario'] ? date('d/m/Y H:i:s', strtotime($usuario['data_envio_formulario'])) : '',
            $usuario['total_cursos'],
            $usuario['total_arquivos'],
            $usuario['areas_formacao'] ?? '',
            $usuario['registros_profissionais'] ?? '',
            $usuario['tipos_documentos'] ?? ''
        ], null, "A{$linha}");

        // Bordas nas linhas de dados
        $sheet->getStyle("A{$linha}:Z{$linha}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        $linha++;
    }

    // Ajustar largura automática
    foreach (range('A', $sheet->getHighestColumn()) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Nome do arquivo
    $filename = 'usuarios_seletico_' . date('Y-m-d_H-i-s') . '.xlsx';

    // Download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
} catch (Exception $e) {
    error_log("Erro na exportação de usuários: " . $e->getMessage());
    try {
        registrarLog($pdo, 'erro_exportar_usuarios', 'Erro na exportação: ' . $e->getMessage());
    } catch (Exception $logError) {
    }
    header('Location: usuarios.php?erro=exportacao_falhou');
    exit;
}