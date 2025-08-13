// Drag & Drop Upload System
let selectedFiles = [];
const maxFiles = 7;
const maxFileSize = 10 * 1024 * 1024; // 10MB

const tiposDocumento = {
    'RG/CPF': 'RG e CPF',
    'DECLARACAO_DE_DISPONIBILIDADE': 'Declaração de Disponibilidade',
    'CURRICULO': 'Curriculo',
    'DIPLOMA': 'Diploma de Conclusão de Curso',
    'COMPROV_DE_REGISTRO': 'Comprovante de Registro',
    'OUTROS': 'Outros (Experiência Profissional)'
};

// Inicializar drag & drop
document.addEventListener('DOMContentLoaded', function () {
    initializeDragDrop();
});

function initializeDragDrop() {
    const dragDropArea = document.getElementById('dragDropArea');
    const fileInput = document.getElementById('fileInput');

    // Eventos de drag & drop
    dragDropArea.addEventListener('dragover', handleDragOver);
    dragDropArea.addEventListener('dragenter', handleDragEnter);
    dragDropArea.addEventListener('dragleave', handleDragLeave);
    dragDropArea.addEventListener('drop', handleDrop);
    dragDropArea.addEventListener('click', () => fileInput.click());

    // Evento de seleção de arquivo
    fileInput.addEventListener('change', handleFileSelect);
}

function handleDragOver(e) {
    e.preventDefault();
    e.stopPropagation();
}

function handleDragEnter(e) {
    e.preventDefault();
    e.stopPropagation();
    e.target.closest('.drag-drop-area').classList.add('drag-over');
}

function handleDragLeave(e) {
    e.preventDefault();
    e.stopPropagation();
    if (!e.target.closest('.drag-drop-area').contains(e.relatedTarget)) {
        e.target.closest('.drag-drop-area').classList.remove('drag-over');
    }
}

function handleDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    e.target.closest('.drag-drop-area').classList.remove('drag-over');

    const files = Array.from(e.dataTransfer.files);
    processFiles(files);
}

function handleFileSelect(e) {
    const files = Array.from(e.target.files);
    processFiles(files);
    e.target.value = ''; // Limpar input para permitir seleção do mesmo arquivo
}

function processFiles(files) {
    const validFiles = [];

    for (let file of files) {
        // Verificar se já atingiu o limite
        if (selectedFiles.length + validFiles.length >= maxFiles) {
            showAlert('warning', `Máximo de ${maxFiles} arquivos permitidos.`);
            break;
        }

        // Verificar tipo de arquivo
        if (file.type !== 'application/pdf') {
            showAlert('error', `Arquivo "${file.name}" não é um PDF válido.`);
            continue;
        }

        // Verificar tamanho
        if (file.size > maxFileSize) {
            showAlert('error', `Arquivo "${file.name}" é muito grande. Máximo 10MB.`);
            continue;
        }

        // Verificar se já existe
        if (selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
            showAlert('warning', `Arquivo "${file.name}" já foi selecionado.`);
            continue;
        }

        validFiles.push(file);
    }

    // Adicionar arquivos válidos
    validFiles.forEach(file => {
        const fileObj = {
            file: file,
            id: Date.now() + Math.random(),
            name: file.name,
            size: file.size,
            type: '',
            status: 'pending'
        };
        selectedFiles.push(fileObj);
    });

    updateFilesList();
}

function updateFilesList() {
    const filesList = document.getElementById('filesList');

    if (selectedFiles.length === 0) {
        filesList.innerHTML = '';
        return;
    }

    filesList.innerHTML = selectedFiles.map(fileObj => `
                <div class="file-item" data-file-id="${fileObj.id}">
                    <div class="file-info">
                        <i class="fas fa-file-pdf file-icon"></i>
                        <div class="file-details">
                            <h6>${fileObj.name}</h6>
                            <small>${formatFileSize(fileObj.size)}</small>
                        </div>
                    </div>

                    <select class="form-select file-type-select" onchange="updateFileType('${fileObj.id}', this.value)" required>
                        <option value="">Selecione o tipo...</option>
                        ${Object.entries(tiposDocumento).map(([key, label]) =>
        `<option value="${key}" ${fileObj.type === key ? 'selected' : ''}>${label}</option>`
    ).join('')}
                    </select>

                    <div class="file-actions">
                        <div class="file-status ${fileObj.status}">
                            ${getStatusIcon(fileObj.status)}
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFile('${fileObj.id}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `).join('');
}

function updateFileType(fileId, type) {
    const fileObj = selectedFiles.find(f => f.id == fileId);
    if (fileObj) {
        fileObj.type = type;
    }
}

function removeFile(fileId) {
    selectedFiles = selectedFiles.filter(f => f.id != fileId);
    updateFilesList();
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function getStatusIcon(status) {
    switch (status) {
        case 'success':
            return '<i class="fas fa-check-circle"></i>';
        case 'error':
            return '<i class="fas fa-exclamation-circle"></i>';
        case 'uploading':
            return '<i class="fas fa-spinner fa-spin"></i>';
        default:
            return '<i class="fas fa-clock"></i>';
    }
}

function showAlert(type, message) {
    const alertClass = type === 'error' ? 'alert-danger' :
        type === 'warning' ? 'alert-warning' : 'alert-info';

    const alert = document.createElement('div');
    alert.className = `alert ${alertClass} alert-dismissible fade show`;
    alert.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

    document.querySelector('.form-card .card-body').insertBefore(alert, document.querySelector('form'));

    // Auto-remover após 5 segundos
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

// Upload AJAX para drag & drop
function uploadFiles(event) {
    event.preventDefault(); // Previne o envio do formulário

    if (selectedFiles.length === 0) {
        showAlert('warning', 'Nenhum arquivo selecionado para upload.');
        return false;
    }

    // Verificar se todos os arquivos têm tipo definido
    const filesWithoutType = selectedFiles.filter(f => !f.type);
    if (filesWithoutType.length > 0) {
        showAlert('error', 'Por favor, selecione o tipo para todos os arquivos.');
        return false;
    }

    const formData = new FormData();
    const types = [];

    // Adicionar arquivos e tipos ao FormData
    selectedFiles.forEach((fileObj, index) => {
        formData.append('files[]', fileObj.file);
        types.push(fileObj.type);
    });

    // Adicionar tipos como array
    types.forEach((type, index) => {
        formData.append(`types[${index}]`, type);
    });

    // Mostrar progresso
    showUploadProgress();

    // Fazer upload via AJAX
    fetch('upload_ajax.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            hideUploadProgress();

            if (data.success) {
                // Limpar arquivos enviados com sucesso
                selectedFiles = selectedFiles.filter(f =>
                    !data.files.some(uploadedFile => uploadedFile.original_name === f.name)
                );

                // Atualizar lista de arquivos
                updateFilesList();

                showAlert('success', data.message);

                // Recarregar a página para mostrar os novos arquivos
                setTimeout(() => location.reload(), 1500);

                // Mostrar erros se houver
                if (data.errors && data.errors.length > 0) {
                    data.errors.forEach(error => {
                        showAlert('error', error);
                    });
                }
            } else {
                showAlert('error', data.message || 'Erro no upload dos arquivos.');
                // Resetar status dos arquivos
                selectedFiles.forEach(f => f.status = 'pending');
                updateFilesList();
            }
        })
        .catch(error => {
            hideUploadProgress();
            console.error('Erro no upload:', error);
            showAlert('error', 'Erro de conexão durante o upload.');
        });
}

function showUploadProgress() {
    const uploadProgress = document.getElementById('uploadProgress');
    const progressBar = document.getElementById('progressBar');
    const uploadStatus = document.getElementById('uploadStatus');

    uploadProgress.style.display = 'block';
    progressBar.style.width = '0%';
    uploadStatus.innerHTML = '<small class="text-muted">Iniciando upload...</small>';

    // Simular progresso (em uma implementação real, você usaria XMLHttpRequest para progresso real)
    let progress = 0;
    const interval = setInterval(() => {
        progress += Math.random() * 30;
        if (progress > 90) progress = 90;

        progressBar.style.width = progress + '%';
        uploadStatus.innerHTML =
            `<small class="text-muted">Enviando arquivos... ${Math.round(progress)}%</small>`;

        if (progress >= 90) {
            clearInterval(interval);
            uploadStatus.innerHTML = '<small class="text-muted">Finalizando...</small>';
        }
    }, 200);
}

function hideUploadProgress() {
    const uploadProgress = document.getElementById('uploadProgress');
    const progressBar = document.getElementById('progressBar');

    progressBar.style.width = '100%';
    setTimeout(() => {
        uploadProgress.style.display = 'none';
    }, 1000);
}

// Adicionar botão de upload após a lista de arquivos
function addUploadButton() {
    const filesList = document.getElementById('filesList');

    if (selectedFiles.length > 0 && !document.getElementById('uploadButton')) {
        const uploadButton = document.createElement('div');
        uploadButton.id = 'uploadButton';
        uploadButton.className = 'text-center mt-3';
        uploadButton.innerHTML = `
<button type="button" class="btn btn-success btn-lg" onclick="uploadFiles(event)">
 <i class="fas fa-cloud-upload-alt me-2"></i>Fazer Upload dos Arquivos
</button>
                `;
        filesList.appendChild(uploadButton);
    } else if (selectedFiles.length === 0 && document.getElementById('uploadButton')) {
        document.getElementById('uploadButton').remove();
    }
}

// Atualizar a função updateFilesList para incluir o botão de upload
const originalUpdateFilesList = updateFilesList;
updateFilesList = function () {
    originalUpdateFilesList();
    addUploadButton();
};