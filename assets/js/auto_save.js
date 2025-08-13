let isSaving = false;

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formularioForm');
    const inputs = form.querySelectorAll('input, select, textarea');

    inputs.forEach(input => {
        input.addEventListener('input', autoSave);
        input.addEventListener('change', autoSave);
    });
});

function autoSave() {
    if (isSaving) return;
    clearTimeout(saveTimer);
    document.getElementById('saveStatus').textContent = 'Salvando...';
    saveTimer = setTimeout(saveDraft, 1000); // Debounce de 1 segundo
}

function saveDraft() {
    isSaving = true;
    const form = document.getElementById('formularioForm');
    const formData = new FormData(form);
    formData.append('acao', 'salvar_rascunho');
    formData.append('ajax', '1');

    // Extrair o hash da URL atual
    const urlParams = new URLSearchParams(window.location.search);
    const hash = urlParams.get('h');

    // Adicionar o hash à URL do fetch
    const fetchUrl = hash ? `formulario.php?h=${hash}` : 'formulario.php';

    fetch(fetchUrl, {
        method: 'POST',
        body: formData
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erro HTTP: ${response.status} - ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                document.getElementById('saveStatus').textContent = 'Salvo automaticamente em ' + new Date()
                    .toLocaleTimeString();
            } else {
                document.getElementById('saveStatus').textContent = 'Erro ao salvar rascunho: ' + (data
                    .message || 'Desconhecido');
            }
        })
        .catch(error => {
            console.error('Erro na requisição AJAX:', error);
            document.getElementById('saveStatus').textContent = 'Erro ao salvar rascunho: ' + error.message;
        })
        .finally(() => {
            isSaving = false;
        });
}

// Auto-save draft functionality
let saveTimer;

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formularioForm');
    const inputs = form.querySelectorAll('input, select, textarea');

    inputs.forEach(input => {
        input.addEventListener('input', autoSave);
        input.addEventListener('change', autoSave);
    });
});

function autoSave() {
    clearTimeout(saveTimer);
    document.getElementById('saveStatus').textContent = 'Salvando...';
    saveTimer = setTimeout(saveDraft, 1000); // Debounce for 1 second
}

function saveDraft() {
    const form = document.getElementById('formularioForm');
    const formData = new FormData(form);
    formData.append('acao', 'salvar_rascunho');
    formData.append('ajax', '1');

    fetch('formulario.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('saveStatus').textContent = 'Salvo automaticamente em ' + new Date()
                    .toLocaleTimeString();
            } else {
                document.getElementById('saveStatus').textContent = 'Erro ao salvar rascunho.';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('saveStatus').textContent = 'Erro ao salvar rascunho.';
        });
}