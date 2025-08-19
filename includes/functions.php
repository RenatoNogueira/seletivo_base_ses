<?php
// Função para validar CPF
function validarCPF($cpf)
{
    // Remove caracteres não numéricos
    $cpf = preg_replace('/[^0-9]/', '', $cpf);

    // Verifica se tem 11 dígitos
    if (strlen($cpf) != 11) {
        return false;
    }

    // Verifica se todos os dígitos são iguais
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }

    // Calcula primeiro dígito verificador
    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += $cpf[$i] * (10 - $i);
    }
    $resto = $soma % 11;
    $digito1 = ($resto < 2) ? 0 : 11 - $resto;

    // Calcula segundo dígito verificador
    $soma = 0;
    for ($i = 0; $i < 10; $i++) {
        $soma += $cpf[$i] * (11 - $i);
    }
    $resto = $soma % 11;
    $digito2 = ($resto < 2) ? 0 : 11 - $resto;

    // Verifica se os dígitos calculados conferem
    return ($cpf[9] == $digito1 && $cpf[10] == $digito2);
}

// Função para formatar CPF
function formatarCPF($cpf)
{
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

// Função para validar data de nascimento
function validarDataNascimento($data)
{
    $dataObj = DateTime::createFromFormat('Y-m-d', $data);
    if (!$dataObj) {
        return false;
    }

    $hoje = new DateTime();
    $idade = $dataObj->diff($hoje)->y;

    return $idade >= 16 && $idade <= 120;
}

// Função para validar email
function validarEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Função para validar CEP
function validarCEP($cep)
{
    $cep = preg_replace('/[^0-9]/', '', $cep);
    return strlen($cep) == 8;
}

// Função para buscar endereço por CEP
function buscarEnderecoPorCEP($cep)
{
    $cep = preg_replace('/[^0-9]/', '', $cep);

    if (!validarCEP($cep)) {
        return false;
    }

    $url = "https://viacep.com.br/ws/{$cep}/json/";
    $response = file_get_contents($url);

    if ($response === false) {
        return false;
    }

    $data = json_decode($response, true);

    if (isset($data['erro'])) {
        return false;
    }

    return $data;
}

// Função para sanitizar entrada
function sanitizar($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Função para gerar nome único para arquivo
function gerarNomeArquivo($nomeOriginal)
{
    $extensao = pathinfo($nomeOriginal, PATHINFO_EXTENSION);
    return uniqid() . '_' . time() . '.' . $extensao;
}

// Função para validar arquivo PDF
function validarArquivoPDF($arquivo)
{
    $tiposPermitidos = ['application/pdf'];
    $tamanhoMaximo = 10 * 1024 * 1024; // 10MB

    if (!in_array($arquivo['type'], $tiposPermitidos)) {
        return 'Apenas arquivos PDF são permitidos.';
    }

    if ($arquivo['size'] > $tamanhoMaximo) {
        return 'Arquivo muito grande. Tamanho máximo: 10MB.';
    }

    return true;
}

// Função para iniciar sessão se não estiver iniciada
function iniciarSessao()
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Função para verificar se usuário está logado
function usuarioLogado()
{
    iniciarSessao();
    return isset($_SESSION['usuario_id']);
}

// Função para redirecionar
function redirecionar($url)
{
    header("Location: $url");
    exit();
}

// Função para exibir mensagem de erro/sucesso
function exibirMensagem($tipo, $mensagem)
{
    $classe = ($tipo == 'sucesso') ? 'alert-success' : 'alert-danger';
    return "<div class='alert $classe alert-dismissible fade show' role='alert'>
                $mensagem
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}
