// Função para verificar o progresso do formulário
function verificarProgresso() {
    const campos = {
        'dados_pessoais': ['nome_completo', 'rg', 'estado_civil', 'nacionalidade'],
        'contato': ['telefone_fixo', 'celular', 'email', 'email_alternativo'],
        'endereco': ['cep', 'logradouro', 'numero', 'bairro', 'cidade', 'estado'],
        'profissional': ['objetivo_pgs', 'atividades_pgs', 'contribuicao_pgs'],
        'formacao': ['cursos[0][nivel]', 'cursos[0][area_formacao]']
    };

    // Verificar quais seções estão completas
    const secoesCompletas = {
        'dados_pessoais': verificarSecao(campos.dados_pessoais),
        'contato': verificarSecao(campos.contato),
        'endereco': verificarSecao(campos.endereco),
        'profissional': verificarSecao(campos.profissional),
        'formacao': verificarSecao(campos.formacao)
    };

    // Atualizar ícones de progresso
    atualizarIconesProgresso(secoesCompletas);
}

// Verificar se uma seção está completa
function verificarSecao(campos) {
    return campos.every(campo => {
        if (campo.includes('[')) {
            // Para campos de array como cursos[0][nivel]
            const [prefix, index, name] = campo.match(/(\w+)\[(\d+)\]\[(\w+)\]/).slice(1);
            const elements = document.querySelectorAll(`[name="${prefix}[${index}][${name}]"]`);
            return elements.length > 0 && elements[0].value.trim() !== '';
        } else {
            const element = document.querySelector(`[name="${campo}"]`);
            return element && element.value.trim() !== '';
        }
    });
}

// Atualizar ícones de progresso na sidebar
function atualizarIconesProgresso(secoes) {
    const progressItems = document.querySelectorAll('.progress-item');

    // Dados Pessoais (sempre completo pois é o passo atual)
    progressItems[0].querySelector('.progress-icon').className = 'progress-icon completed';
    progressItems[0].querySelector('.progress-icon').innerHTML = '<i class="fas fa-check"></i>';

    // Contato
    updateProgressIcon(progressItems[1], secoes.contato);

    // Endereço
    updateProgressIcon(progressItems[2], secoes.endereco);

    // Profissional
    updateProgressIcon(progressItems[3], secoes.profissional);

    // Formação
    updateProgressIcon(progressItems[4], secoes.formacao);
}

function updateProgressIcon(item, isComplete) {
    const icon = item.querySelector('.progress-icon');
    if (isComplete) {
        icon.className = 'progress-icon completed';
        icon.innerHTML = '<i class="fas fa-check"></i>';
    } else {
        icon.className = 'progress-icon pending';
        icon.innerHTML = '<i class="fas fa-' + getIconForStep(item) + '"></i>';
    }
}

function getIconForStep(item) {
    const stepText = item.querySelector('div > div').textContent;
    if (stepText.includes('Contato')) return 'envelope';
    if (stepText.includes('Endereço')) return 'map-marker-alt';
    if (stepText.includes('Profissional')) return 'briefcase';
    if (stepText.includes('Formação')) return 'graduation-cap';
    return 'circle';
}

// Adicionar event listeners para campos do formulário
document.addEventListener('DOMContentLoaded', function () {
    // Verificar progresso inicial
    verificarProgresso();

    // Adicionar listeners para todos os campos de entrada
    const inputs = document.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        input.addEventListener('change', verificarProgresso);
        input.addEventListener('input', verificarProgresso);
    });

    // Verificar também quando cursos são adicionados/removidos
    const observer = new MutationObserver(verificarProgresso);
    observer.observe(document.getElementById('cursosContainer'), {
        childList: true,
        subtree: true
    });
});

// Adicionar barra de progresso geral
function atualizarBarraProgresso() {
    const totalSecoes = 5; // Total de seções (incluindo login)
    const secoesCompletas = document.querySelectorAll('.progress-icon.completed').length;
    const progresso = Math.round((secoesCompletas / totalSecoes) * 100);

    // Criar ou atualizar a barra de progresso
    let progressBar = document.querySelector('.progresso-geral');
    if (!progressBar) {
        progressBar = document.createElement('div');
        progressBar.className = 'progresso-geral mb-3';
        progressBar.innerHTML = `
            <div class="d-flex justify-content-between mb-1">
                <span>Progresso geral</span>
                <span>${progresso}%</span>
            </div>
            <div class="progress" style="height: 10px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: ${progresso}%"></div>
            </div>
        `;
        document.querySelector('.progress-sidebar').prepend(progressBar);
    } else {
        progressBar.querySelector('.progress-bar').style.width = `${progresso}%`;
        progressBar.querySelector('span:last-child').textContent = `${progresso}%`;
    }
}

// Modificar a função verificarProgresso para incluir a barra geral
const originalVerificarProgresso = verificarProgresso;
verificarProgresso = function () {
    originalVerificarProgresso();
    atualizarBarraProgresso();
};

// Executar uma primeira vez ao carregar
verificarProgresso();