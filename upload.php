<!-- Upload de Arquivos com Drag & Drop -->
<div class="mb-5">
    <h4 class="text-primary mb-3">
        <i class="fas fa-upload me-2"></i>Upload de Documentos
    </h4>

    <div class="mb-3">
        <p class="text-muted">Arraste e solte seus documentos ou clique para selecionar
            (máximo de 10MB)</p>
    </div>

    <!-- Área de Drag & Drop -->
    <div id="dragDropArea" class="drag-drop-area mb-4">
        <div class="drag-drop-content">
            <i class="fas fa-cloud-upload-alt drag-drop-icon"></i>
            <h5>Arraste e solte seus arquivos aqui</h5>
            <p class="text-muted">ou</p>
            <button type="button" class="btn btn-primary"
                onclick="event.stopPropagation(); document.getElementById('fileInput').click()"
                onclick="document.getElementById('fileInput').click()">
                <i class="fas fa-folder-open me-2"></i>Selecionar Arquivos
            </button>
            <input type="file" id="fileInput" multiple accept=".pdf" style="display: none;">
        </div>
    </div>

    <!-- Lista de Arquivos Selecionados -->
    <div id="filesList" class="files-list">
        <!-- Arquivos aparecerão aqui -->
    </div>

    <!-- Progresso de Upload -->
    <div id="uploadProgress" class="upload-progress" style="display: none;">
        <div class="progress mb-2">
            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                style="width: 0%"></div>
        </div>
        <div id="uploadStatus" class="text-center">
            <small class="text-muted">Preparando upload...</small>
        </div>
    </div>

    <div class="form-text mt-2">
        <strong>Tipos de documentos aceitos:</strong> RG, Título de Eleitor, Diploma,
        Certificado, Registro Profissional, Comprovante de Residência<br>
        <strong>Formato:</strong> Apenas arquivos PDF | <strong>Tamanho máximo:</strong>
        10MB por arquivo
    </div>
</div>









<?php
// Separar arquivos recentes (últimas 24h) e antigos
$arquivosRecentes = [];
$arquivosAntigos = [];
$arquivosDuplicados = [];
$agora = new DateTime();
$intervalo = new DateInterval('PT24H'); // 24 horas

// Primeiro, identificar duplicados
$nomesArquivos = [];
foreach ($arquivos as $arquivo) {
    $nome = $arquivo['nome_original'];
    if (isset($nomesArquivos[$nome])) {
        $nomesArquivos[$nome]['count']++;
        $nomesArquivos[$nome]['ids'][] = $arquivo['id'];
    } else {
        $nomesArquivos[$nome] = ['count' => 1, 'ids' => [$arquivo['id']]];
    }
}

// Agora classificar os arquivos
foreach ($arquivos as $arquivo) {
    $dataUpload = new DateTime($arquivo['uploaded_at']);
    $diferenca = $agora->diff($dataUpload);
    $tipo = $arquivo['tipo_documento'] ?? 'Documento';

    // Verificar se é duplicado
    $isDuplicado = $nomesArquivos[$arquivo['nome_original']]['count'] > 1;

    if ($diferenca->days == 0 && $diferenca->h < 24) {
        $arquivosRecentes[$tipo][] = ['arquivo' => $arquivo, 'duplicado' => $isDuplicado];
    } else {
        $arquivosAntigos[$tipo][] = ['arquivo' => $arquivo, 'duplicado' => $isDuplicado];
    }
}
?>

<?php if (!empty($arquivosRecentes) || !empty($arquivosAntigos)): ?>
<div class="mt-4">
    <h5 class="text-success mb-3"><i class="fas fa-file-alt me-2"></i>Documentos Enviados
    </h5>

    <?php if (!empty($arquivosRecentes)): ?>
    <div class="mb-4">
        <h6 class="text-primary"><i class="fas fa-clock me-2"></i>Últimos arquivos enviados
        </h6>

        <!-- Adicionar alerta sobre duplicados -->
        <?php
                $totalDuplicados = array_reduce($arquivosRecentes, function ($carry, $grupo) {
                    return $carry + array_reduce($grupo, function ($c, $item) {
                        return $c + ($item['duplicado'] ? 1 : 0);
                    }, 0);
                }, 0);

                if ($totalDuplicados > 0): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Existem <?= $totalDuplicados ?> arquivo(s) duplicado(s) nos últimos envios.
        </div>
        <?php endif; ?>

        <?php foreach ($arquivosRecentes as $tipo => $grupo): ?>
        <div class="card mb-3">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><?= htmlspecialchars($tipo) ?></h6>
                <?php
                            $duplicadosNoGrupo = array_reduce($grupo, function ($carry, $item) {
                                return $carry + ($item['duplicado'] ? 1 : 0);
                            }, 0);
                            if ($duplicadosNoGrupo > 0): ?>
                <span class="badge bg-danger"><?= $duplicadosNoGrupo ?> duplicado(s)</span>
                <?php endif; ?>
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($grupo as $item):
                                $arquivo = $item['arquivo'];
                                $isDuplicado = $item['duplicado'];
                            ?>
                <li
                    class="list-group-item d-flex justify-content-between align-items-center <?= $isDuplicado ? 'bg-light-warning' : '' ?>">
                    <div>
                        <i class="fas fa-file-pdf text-danger me-2"></i>
                        <strong><?= htmlspecialchars($arquivo['nome_original']) ?></strong>
                        <?php if ($isDuplicado): ?>
                        <span class="badge bg-warning text-dark ms-2">Duplicado</span>
                        <?php endif; ?>
                        <br>
                        <small class="text-muted">Enviado em:
                            <?= date('d/m/Y H:i', strtotime($arquivo['uploaded_at'])) ?></small>
                    </div>
                    <div>
                        <a href="<?= htmlspecialchars($arquivo['caminho_arquivo']) ?>"
                            class="btn btn-outline-primary btn-sm" target="_blank">
                            <i class="fas fa-download me-1"></i>Download
                        </a>
                        <button class="btn btn-outline-danger btn-sm ms-2"
                            onclick="confirmarExclusao(<?= $arquivo['id'] ?>, '<?= htmlspecialchars(addslashes($arquivo['nome_original'])) ?>')">
                            <i class="fas fa-trash-alt me-1"></i>Excluir
                        </button>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Código para arquivos antigos permanece o mesmo -->
    <?php if (!empty($arquivosAntigos)): ?>
    <div class="mb-4">
        <h6 class="text-secondary"><i class="fas fa-history me-2"></i>Anteriores</h6>
        <?php foreach ($arquivosAntigos as $tipo => $grupo): ?>
        <div class="card mb-3">
            <div class="card-header bg-light">
                <h6 class="mb-0"><?= htmlspecialchars($tipo) ?></h6>
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($grupo as $item):
                                $arquivo = $item['arquivo'];
                                $isDuplicado = $item['duplicado'];
                            ?>
                <li
                    class="list-group-item d-flex justify-content-between align-items-center <?= $isDuplicado ? 'bg-light-warning' : '' ?>">
                    <div>
                        <i class="fas fa-file-pdf text-danger me-2"></i>
                        <strong><?= htmlspecialchars($arquivo['nome_original']) ?></strong>
                        <?php if ($isDuplicado): ?>
                        <span class="badge bg-warning text-dark ms-2">Duplicado</span>
                        <?php endif; ?>
                        <br>
                        <small class="text-muted">Enviado em:
                            <?= date('d/m/Y H:i', strtotime($arquivo['uploaded_at'])) ?></small>
                    </div>
                    <div>
                        <a href="<?= htmlspecialchars($arquivo['caminho_arquivo']) ?>"
                            class="btn btn-outline-primary btn-sm" target="_blank">
                            <i class="fas fa-download me-1"></i>Download
                        </a>
                        <button class="btn btn-outline-danger btn-sm ms-2"
                            onclick="confirmarExclusao(<?= $arquivo['id'] ?>, '<?= htmlspecialchars(addslashes($arquivo['nome_original'])) ?>')">
                            <i class="fas fa-trash-alt me-1"></i>Excluir
                        </button>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="mt-4 text-muted">
    <i class="fas fa-info-circle me-2"></i>Nenhum documento enviado ainda.
</div>
<?php endif; ?>