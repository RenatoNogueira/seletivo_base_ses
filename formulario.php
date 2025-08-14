<?php
require_once 'functions/formulario_funcao.php';
require_once 'includes/parts/header.php';

date_default_timezone_set('America/Sao_Paulo');
?>

<body>
    <?php
    require_once 'includes/parts/navbar.php';
    ?>
    <!-- Formulário Principal -->
    <div class="col-lg-9">
        <div class="form-card">
            <div class="section-header">
                <h3 class="mb-0">
                    <i class="fas fa-clipboard-list section-icon"></i>
                    Formulário de Dados Pessoais
                </h3>
            </div>

            <!-- Alertas -->
            <div class="container mt-3">
                <div class="row">
                    <div class="col-12">
                        <?php if ($sucesso): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= exibirMensagem('sucesso', $sucesso) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                aria-label="Fechar"></button>
                        </div>
                        <?php endif; ?>

                        <?php if ($erro): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= exibirMensagem('erro', $erro) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                aria-label="Fechar"></button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>


            <div class="card-body p-4">
                <div id="saveStatus" class="text-muted mb-3"></div>

                <form method="POST" id="formularioForm" enctype="multipart/form-data">
                    <!-- Informações Pessoais -->
                    <div class="mb-5">
                        <h4 class="text-primary mb-3">
                            <i class="fas fa-user me-2"></i>Informações Pessoais
                        </h4>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nome_completo" class="form-label">Nome Completo *</label>
                                <input type="text"
                                    class="form-control <?= isset($_SESSION['campos_erro']) && in_array('nome_completo', $_SESSION['campos_erro']) ? 'is-invalid' : '' ?>"
                                    id="nome_completo" name="nome_completo"
                                    value="<?= htmlspecialchars($rascunho['nome_completo'] ?? $usuario['nome_completo'] ?? '') ?>"
                                    required>
                                <?php if (isset($_SESSION['campos_erro']) && in_array('nome_completo', $_SESSION['campos_erro'])): ?>
                                <div class="invalid-feedback">Nome completo é obrigatório.</div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="cpf" class="form-label">CPF</label>
                                <input type="text" class="form-control" id="cpf" name="cpf"
                                    value="<?= formatarCPF($usuario['cpf']) ?>" readonly>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="rg" class="form-label">RG</label>
                                <input type="text" class="form-control" id="rg" name="rg"
                                    value="<?= htmlspecialchars($rascunho['rg'] ?? $usuario['rg'] ?? '') ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                                <input type="date" class="form-control" id="data_nascimento" name="data_nascimento"
                                    value="<?= $usuario['data_nascimento'] ?>" readonly>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="estado_civil" class="form-label">Estado Civil</label>
                                <select class="form-select" id="estado_civil" name="estado_civil">
                                    <option value="">Selecione...</option>
                                    <option value="Solteiro(a)"
                                        <?= ($rascunho['estado_civil'] ?? $usuario['estado_civil'] ?? '') == 'Solteiro(a)' ? 'selected' : '' ?>>
                                        Solteiro(a)</option>
                                    <option value="Casado(a)"
                                        <?= ($rascunho['estado_civil'] ?? $usuario['estado_civil'] ?? '') == 'Casado(a)' ? 'selected' : '' ?>>
                                        Casado(a)</option>
                                    <option value="Divorciado(a)"
                                        <?= ($rascunho['estado_civil'] ?? $usuario['estado_civil'] ?? '') == 'Divorciado(a)' ? 'selected' : '' ?>>
                                        Divorciado(a)</option>
                                    <option value="Viúvo(a)"
                                        <?= ($rascunho['estado_civil'] ?? $usuario['estado_civil'] ?? '') == 'Viúvo(a)' ? 'selected' : '' ?>>
                                        Viúvo(a)</option>
                                    <option value="União Estável"
                                        <?= ($rascunho['estado_civil'] ?? $usuario['estado_civil'] ?? '') == 'União Estável' ? 'selected' : '' ?>>
                                        União Estável</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="nacionalidade" class="form-label">Nacionalidade</label>
                                <input type="text" class="form-control" id="nacionalidade" name="nacionalidade"
                                    value="<?= htmlspecialchars($rascunho['nacionalidade'] ?? $usuario['nacionalidade'] ?? 'Brasileira') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Informações de Contato -->
                    <div class="mb-5">
                        <h4 class="text-primary mb-3">
                            <i class="fas fa-envelope me-2"></i>Informações de Contato
                        </h4>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="telefone_fixo" class="form-label">Telefone Fixo</label>
                                <input type="tel" class="form-control" id="telefone_fixo" name="telefone_fixo"
                                    placeholder="(11) 1234-5678"
                                    value="<?= htmlspecialchars($rascunho['telefone_fixo'] ?? $usuario['telefone_fixo'] ?? '') ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="celular" class="form-label">Celular *</label>
                                <input type="tel" class="form-control" id="celular" name="celular"
                                    placeholder="(11) 91234-5678" required
                                    value="<?= htmlspecialchars($rascunho['celular'] ?? $usuario['celular'] ?? '') ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    placeholder="seu@email.com" required
                                    value="<?= htmlspecialchars($rascunho['email'] ?? $usuario['email'] ?? '') ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="email_alternativo" class="form-label">Email Alternativo</label>
                                <input type="email" class="form-control" id="email_alternativo" name="email_alternativo"
                                    placeholder="alternativo@email.com"
                                    value="<?= htmlspecialchars($rascunho['email_alternativo'] ?? $usuario['email_alternativo'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Endereço -->
                    <div class="mb-5">
                        <h4 class="text-primary mb-3">
                            <i class="fas fa-map-marker-alt me-2"></i>Endereço
                        </h4>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="cep" class="form-label">CEP</label>
                                <input type="text" class="form-control" id="cep" name="cep" placeholder="00000-000"
                                    maxlength="9"
                                    value="<?= htmlspecialchars($rascunho['cep'] ?? $formulario['cep'] ?? '') ?>">
                            </div>

                            <div class="col-md-8 mb-3">
                                <label for="logradouro" class="form-label">Logradouro</label>
                                <input type="text" class="form-control" id="logradouro" name="logradouro"
                                    placeholder="Rua, Avenida, etc."
                                    value="<?= htmlspecialchars($rascunho['logradouro'] ?? $formulario['logradouro'] ?? '') ?>">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="numero" class="form-label">Número</label>
                                <input type="text" class="form-control" id="numero" name="numero" placeholder="123"
                                    value="<?= htmlspecialchars($rascunho['numero'] ?? $formulario['numero'] ?? '') ?>">
                            </div>

                            <div class="col-md-9 mb-3">
                                <label for="complemento" class="form-label">Complemento</label>
                                <input type="text" class="form-control" id="complemento" name="complemento"
                                    placeholder="Apartamento, Bloco, etc."
                                    value="<?= htmlspecialchars($rascunho['complemento'] ?? $formulario['complemento'] ?? '') ?>">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="bairro" class="form-label">Bairro</label>
                                <input type="text" class="form-control" id="bairro" name="bairro"
                                    placeholder="Nome do bairro"
                                    value="<?= htmlspecialchars($rascunho['bairro'] ?? $formulario['bairro'] ?? '') ?>">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="cidade" class="form-label">Cidade</label>
                                <input type="text" class="form-control" id="cidade" name="cidade"
                                    placeholder="Nome da cidade"
                                    value="<?= htmlspecialchars($rascunho['cidade'] ?? $formulario['cidade'] ?? '') ?>">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="">Selecione...</option>
                                    <option value="AC"
                                        <?= ($rascunho['estado'] ?? $formulario['estado'] ?? '') == 'AC' ? 'selected' : '' ?>>
                                        Acre</option>
                                    <option value="AL"
                                        <?= ($rascunho['estado'] ?? $formulario['estado'] ?? '') == 'AL' ? 'selected' : '' ?>>
                                        Alagoas</option>
                                    <option value="AP"
                                        <?= ($rascunho['estado'] ?? $formulario['estado'] ?? '') == 'AP' ? 'selected' : '' ?>>
                                        Amapá</option>
                                    <option value="AM"
                                        <?= ($rascunho['estado'] ?? $formulario['estado'] ?? '') == 'AM' ? 'selected' : '' ?>>
                                        Amazonas</option>
                                    <option value="BA"
                                        <?= ($rascunho['estado'] ?? $formulario['estado'] ?? '') == 'BA' ? 'selected' : '' ?>>
                                        Bahia</option>
                                    <option value="CE"
                                        <?= ($rascunho['estado'] ?? $formulario['estado'] ?? '') == 'CE' ? 'selected' : '' ?>>
                                        Ceará</option>
                                    <option value="DF"
                                        <?= ($rascunho['estado'] ?? $formulario['estado'] ?? '') == 'DF' ? 'selected' : '' ?>>
                                        Distrito Federal</option>
                                    <option value="ES"
                                        <?= ($rascunho['estado'] ?? $formulario['estado'] ?? '') == 'ES' ? 'selected' : '' ?>>
                                        Espírito Santo</option>
                                    <option value="GO"
                                        <?= ($rascunho['estado'] ?? $formulario['estado'] ?? '') == 'GO' ? 'selected' : '' ?>>
                                        Goiás</option>
                                    <option value="MA"
                                        <?= ($rascunho['estado'] ?? $formulario['estado'] ?? '') == 'MA' ? 'selected' : '' ?>>
                                        Maranhão</option>
                                    <option value="MT"
                                        <?= ($rascunho['estado'] ?? $formulario['estado'] ?? '') == 'MT' ? 'selected' : '' ?>>
                                        Mato Grosso</option>
                                    <option value="MS"
                                        <?= ($rascunho['estado'] ?? $formulario['estado'] ?? '') == 'MS' ? 'selected' : '' ?>>
                                        Mato Grosso do Sul</option>
                                    <option value="MG"
                                        <?= ($rascunho['estado'] ?? $formulario['estado'] ?? '') == 'MG' ? 'selected' : '' ?>>
                                        Minas Gerais</option>
                                    <option value="PA"
                                        <?= ($rascunho['estado'] ?? $formulario['estado'] ?? '') == 'PA' ? 'selected' : '' ?>>
                                        Pará</option>
                                    <option value="PB"
                                        <?= ($rascunho['estado'] ?? $formulario['estado'] ?? '') == 'PB' ? 'selected' : '' ?>>
                                        Paraíba</option>
                                    <option value="PR"
                                        <?= ($rascunho['estado'] ?? $formulario['estado'] ?? '') == 'PR' ? 'selected' : '' ?>>
                                        Paraná</option>
                                    <option value="PE"
                                        <?= ($rascunho['estado'] ?? $formulario['estado'] ?? '') == 'PE' ? 'selected' : '' ?>>
                                        Pernambuco</option>
                                    <option value="PI"
                                        <?= ($rascunho['estado'] ?? $formulario['estado'] ?? '') == 'PI' ? 'selected' : '' ?>>
                                        Piauí</option>
                                    <option value="RJ"
                                        <?= ($rascunho['estado'] ?? $formulario['estado'] ?? '') == 'RJ' ? 'selected' : '' ?>>
                                        Rio de Janeiro</option>
                                    <option value="RN"
                                        <?= ($rascunho['estado'] ?? $formulario['estado'] ?? '') == 'RN' ? 'selected' : '' ?>>
                                        Rio Grande do Norte</option>
                                    <option value="RS"
                                        <?= ($rascunho['estado'] ?? $formulario['estado'] ?? '') == 'RS' ? 'selected' : '' ?>>
                                        Rio Grande do Sul</option>
                                    <option value="RO"
                                        <?= ($rascunho['estado'] ?? $formulario['estado'] ?? '') == 'RO' ? 'selected' : '' ?>>
                                        Rondônia</option>
                                    <option value="RR"
                                        <?= ($rascunho['estado'] ?? $formulario['estado'] ?? '') == 'RR' ? 'selected' : '' ?>>
                                        Roraima</option>
                                    <option value="SC"
                                        <?= ($rascunho['estado'] ?? $formulario['estado'] ?? '') == 'SC' ? 'selected' : '' ?>>
                                        Santa Catarina</option>
                                    <option value="SP"
                                        <?= ($rascunho['estado'] ?? $formulario['estado'] ?? '') == 'SP' ? 'selected' : '' ?>>
                                        São Paulo</option>
                                    <option value="SE"
                                        <?= ($rascunho['estado'] ?? $formulario['estado'] ?? '') == 'SE' ? 'selected' : '' ?>>
                                        Sergipe</option>
                                    <option value="TO"
                                        <?= ($rascunho['estado'] ?? $formulario['estado'] ?? '') == 'TO' ? 'selected' : '' ?>>
                                        Tocantins</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Área de Formação -->
                    <div class="mb-5">
                        <h4 class="text-primary mb-3">
                            <i class="fas fa-graduation-cap me-2"></i>Área de Formação
                        </h4>

                        <div id="cursosContainer">
                            <?php
                            foreach ($cursosExistentes as $index => $curso):
                            ?>
                            <div class="curso-item" data-index="<?= $index ?>">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">Área de Formação <?= $index + 1 ?></h6>
                                    <?php if ($index > 0): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                        onclick="removerCurso(<?= $index ?>)">
                                        <i class="fas fa-trash"></i> Remover
                                    </button>
                                    <?php endif; ?>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nível</label>
                                        <select class="form-select nivel-select" required
                                            name="cursos[<?= $index ?>][nivel]"
                                            onchange="atualizarAreaFormacao(this, <?= $index ?>)">
                                            <option value="">Selecione...</option>
                                            <option value="Superior"
                                                <?= ($curso['nivel'] ?? '') == 'Superior' ? 'selected' : '' ?>>
                                                Superior</option>
                                            <option value="Técnico"
                                                <?= ($curso['nivel'] ?? '') == 'Técnico' ? 'selected' : '' ?>>
                                                Técnico</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Área de Formação</label>
                                        <select class="form-select area-formacao-select" required
                                            name="cursos[<?= $index ?>][area_formacao]" id="area-formacao-<?= $index ?>"
                                            style="display: none;">
                                            <option value="">Selecione primeiro o nível...</option>
                                        </select>
                                        <input type="text" class="form-control area-formacao-input"
                                            name="cursos[<?= $index ?>][area_formacao_texto]"
                                            id="area-formacao-input-<?= $index ?>"
                                            value="<?= htmlspecialchars($curso['area_formacao'] ?? '') ?>"
                                            placeholder="Ex: Tecnologia" style="display: block;" disabled>
                                    </div>

                                    <div class="col-md-12 mb-3" id="registro-profissional-container-<?= $index ?>"
                                        style="<?= ($curso['nivel'] ?? '') == 'Superior' ? '' : 'display: none;' ?>">
                                        <label class="form-label">Número de Registro Profissional <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control"
                                            name="cursos[<?= $index ?>][registro_profissional]"
                                            value="<?= htmlspecialchars($curso['registro_profissional'] ?? '') ?>"
                                            placeholder="ex: 000000-G/MA">
                                        <div class="form-text">Obrigatório para profissões regulamentadas</div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Link do Pitch Vídeo -->
                    <div class="mb-5">
                        <h4 class="text-primary mb-3">
                            <i class="fas fa-video me-2"></i>Link do Pitch Vídeo
                            <i class="fas fa-info-circle ms-2" data-bs-toggle="tooltip" data-bs-placement="right"
                                title="URL do Vídeo (YouTube, Google Drive, Vimeo ou outra plataforma de vídeo.) com no máximo 3 minutos"></i>
                        </h4>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <input type="url" class="form-control" id="link_video" name="link_video"
                                    placeholder="https://youtube.com/watch?v=..."
                                    value="<?= htmlspecialchars($rascunho['link_video'] ?? $formulario['link_video'] ?? '') ?>">
                                <div class="form-text">YouTube, Vimeo ou outra plataforma de vídeo</div>
                            </div>
                        </div>
                    </div>

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
                        <div id="filesList">
                            <!-- Arquivos aparecerão aqui -->
                        </div>

                        <!-- Progresso de Upload -->
                        <div id="uploadProgress" class="upload-progress" style="display: none;">
                            <div class="progress mb-2">
                                <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                                    role="progressbar" style="width: 0%"></div>
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


                    <!-- Programa Gestão em Saúde (PGS) -->
                    <div class="mb-5">
                        <h4 class="text-primary mb-3">
                            <i class="fas fa-heartbeat me-2"></i>Questionário de Perfil de Candidato
                        </h4>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="objetivo_pgs" class="form-label">Qual o objetivo de você participar
                                    do Programa Gestão em Saúde (PGS)? *</label>
                                <textarea
                                    class="form-control <?= isset($_SESSION['campos_erro']) && in_array('objetivo_pgs', $_SESSION['campos_erro']) ? 'is-invalid' : '' ?>"
                                    id="objetivo_pgs" name="objetivo_pgs" rows="4" maxlength="1500"
                                    placeholder="Descreva seu objetivo em participar do PGS..."><?= htmlspecialchars($rascunho['objetivo_pgs'] ?? $formulario['objetivo_pgs'] ?? '') ?></textarea>
                                <div class="form-text">Máximo 1500 caracteres. <span
                                        id="contador_objetivo">0</span>/1500</div>
                                <?php if (isset($_SESSION['campos_erro']) && in_array('objetivo_pgs', $_SESSION['campos_erro'])): ?>
                                <div class="invalid-feedback">Objetivo de participar do PGS é obrigatório.</div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="atividades_pgs" class="form-label">Quais atividades e funções você
                                    considera desempenhar, dentro da sua área de formação, no PGS? *</label>
                                <textarea
                                    class="form-control<?= isset($_SESSION['campos_erro']) && in_array('atividades_pgs', $_SESSION['campos_erro']) ? 'is-invalid' : '' ?>"
                                    id="atividades_pgs" name="atividades_pgs" rows="4" maxlength="1500"
                                    placeholder="Descreva as atividades e funções que pretende desempenhar..."><?= htmlspecialchars($rascunho['atividades_pgs'] ?? $formulario['atividades_pgs'] ?? '') ?></textarea>
                                <div class="form-text">Máximo 1500 caracteres. <span
                                        id="contador_atividades">0</span>/1500</div>
                                <?php if (isset($_SESSION['campos_erro']) && in_array('atividades_pgs', $_SESSION['campos_erro'])): ?>
                                <div class="invalid-feedback">Atividades e funções no PGS são obrigatórias.
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="contribuicao_pgs" class="form-label">De que maneira você, com suas
                                    experiências, competências e habilidades pode contribuir para uma gestão
                                    mais efetiva e eficiente da saúde pública, através do PGS? *</label>
                                <textarea
                                    class="form-control <?= isset($_SESSION['campos_erro']) && in_array('contribuicao_pgs', $_SESSION['campos_erro']) ? 'is-invalid' : '' ?>"
                                    id="contribuicao_pgs" name="contribuicao_pgs" rows="4" maxlength="1500"
                                    placeholder="Descreva como pode contribuir para a gestão da saúde pública..."><?= htmlspecialchars($rascunho['contribuicao_pgs'] ?? $formulario['contribuicao_pgs'] ?? '') ?></textarea>
                                <div class="form-text">Máximo 1500 caracteres. <span
                                        id="contador_contribuicao">0</span>/1500</div>
                                <?php if (isset($_SESSION['campos_erro']) && in_array('contribuicao_pgs', $_SESSION['campos_erro'])): ?>
                                <div class="invalid-feedback">Contribuição para a gestão da saúde pública é
                                    obrigatória.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Botões de Ação -->
                    <div class="d-flex justify-content-between">
                        <div>
                            <button type="button" class="btn btn-outline-danger" onclick="limparFormulario()">
                                <i class="fas fa-eraser me-2"></i>Limpar Tudo
                            </button>
                        </div>

                        <button type="submit" name="acao" value="enviar_formulario" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>Enviar Formulário
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
        integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js"
        integrity="sha384-7qAoOXltbVP82dhxHAUje59V5r2YsVfBafyUDxEdApLPmcdhBPg1DKg1ERo0BZlK" crossorigin="anonymous">
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
    </script>


    <script>
    let cursoIndex = <?= count($cursosExistentes) ?>;

    // Formatação de telefone
    function formatarTelefone(input) {
        let value = input.value.replace(/\D/g, '');
        if (value.length <= 10) {
            value = value.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
        } else {
            value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
        }
        input.value = value;
    }

    // Formatação de CEP
    function formatarCEP(input) {
        let value = input.value.replace(/\D/g, '');
        value = value.replace(/(\d{5})(\d{3})/, '$1-$2');
        input.value = value;
    }

    // Buscar endereço por CEP
    function buscarCEP() {
        const cep = document.getElementById('cep').value.replace(/\D/g, '');

        if (cep.length === 8) {
            fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(response => response.json())
                .then(data => {
                    if (!data.erro) {
                        document.getElementById('logradouro').value = data.logradouro || '';
                        document.getElementById('bairro').value = data.bairro || '';
                        document.getElementById('cidade').value = data.localidade || '';
                        document.getElementById('estado').value = data.uf || '';
                    }
                })
                .catch(error => console.error('Erro ao buscar CEP:', error));
        }
    }

    // Adicionar curso
    function adicionarCurso() {
        const container = document.getElementById('cursosContainer');
        const cursoHTML = `
                <div class="curso-item" data-index="${cursoIndex}">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Área de Formação ${cursoIndex + 1}</h6>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removerCurso(${cursoIndex})">
                            <i class="fas fa-trash"></i> Remover
                        </button>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nível</label>
                            <select class="form-select nivel-select" name="cursos[${cursoIndex}][nivel]"
                                    onchange="atualizarAreaFormacao(this, ${cursoIndex})">
                                <option value="">Selecione...</option>
                                <option value="Superior">Superior</option>
                                <option value="Técnico">Técnico</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Área de Formação</label>
                            <select class="form-select area-formacao-select" name="cursos[${cursoIndex}][area_formacao]"
                                    id="area-formacao-${cursoIndex}" style="display: none;">
                                <option value="">Selecione primeiro o nível...</option>
                            </select>
                            <input type="text" class="form-control area-formacao-input" name="cursos[${cursoIndex}][area_formacao_texto]"
                                   id="area-formacao-input-${cursoIndex}"
                                   placeholder="Ex: Tecnologia" style="display: block;">
                        </div>

                        <div class="col-md-12 mb-3" id="registro-profissional-container-${cursoIndex}" style="display: none;">
                            <label class="form-label">Número de Registro Profissional <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="cursos[${cursoIndex}][registro_profissional]"
                                   id="registro-profissional-${cursoIndex}"
                                   placeholder="ex: 000000-G/MA">
                            <div class="form-text">Obrigatório para profissões regulamentadas</div>
                        </div>
                    </div>
                </div>
            `;

        container.insertAdjacentHTML('beforeend', cursoHTML);

        // Adicionar listeners aos novos campos
        const newCurso = container.querySelector(`[data-index="${cursoIndex}"]`);
        const newInputs = newCurso.querySelectorAll('input, select, textarea');
        newInputs.forEach(input => {
            input.addEventListener('input', autoSave);
            input.addEventListener('change', autoSave);
        });

        cursoIndex++;
        autoSave();
    }

    // Remover curso
    function removerCurso(index) {
        const cursoItem = document.querySelector(`.curso-item[data-index="${index}"]`);
        if (cursoItem) {
            cursoItem.remove();
            autoSave();
        }
    }



    // Áreas de formação por nível
    const areasFormacao = {
        'Técnico': [
            'Técnico Administrativo',
            'Técnico em Enfermagem*',
            'Técnico em Eletrotécnica',
            'Técnico em Massoterapia',
            'Técnico em Radiologia*'
        ],
        'Superior': [
            'Administração',
            'Ciências Contábeis',
            'Ciências da Computação',
            'Ciências Econômicas',
            'Tecnólogo em Recursos Humanos',
            'Comunicação Social/Jornalismo*',
            'Direito*',
            'Enfermagem*',
            'Fisioterapia*',
            'Medicina*',
            'Nutrição*',
            'Psicologia*',
        ]
    };

    // Atualizar área de formação baseado no nível selecionado
    function atualizarAreaFormacao(selectNivel, index) {
        const nivel = selectNivel.value;
        const selectAreaFormacao = document.getElementById(`area-formacao-${index}`);
        const inputAreaFormacao = document.getElementById(`area-formacao-input-${index}`);
        const registroProfissionalContainer = document.getElementById(`registro-profissional-container-${index}`);

        if (nivel === 'Superior' || nivel === 'Técnico') {
            // Mostrar select e esconder input
            selectAreaFormacao.style.display = 'block';
            inputAreaFormacao.style.display = 'none';

            // Limpar e popular o select
            selectAreaFormacao.innerHTML = '<option value="">Selecione a área...</option>';

            if (areasFormacao[nivel]) {
                areasFormacao[nivel].forEach(area => {
                    const option = document.createElement('option');
                    option.value = area;
                    option.textContent = area;
                    selectAreaFormacao.appendChild(option);
                });
            }

            // Atualizar o name do campo ativo
            selectAreaFormacao.name = `cursos[${index}][area_formacao]`;
            inputAreaFormacao.name = `cursos[${index}][area_formacao_texto]`;

            // Adicionar evento para verificar se precisa de registro profissional
            selectAreaFormacao.addEventListener('change', function() {
                verificarRegistroProfissional(this.value, index);
            });

        } else {
            // Mostrar input e esconder select para "Livre" ou vazio
            selectAreaFormacao.style.display = 'none';
            inputAreaFormacao.style.display = 'block';

            // Esconder registro profissional para nível "Livre"
            if (registroProfissionalContainer) {
                registroProfissionalContainer.style.display = 'none';
            }

            // Atualizar o name do campo ativo
            inputAreaFormacao.name = `cursos[${index}][area_formacao]`;
            selectAreaFormacao.name = `cursos[${index}][area_formacao_select]`;
        }
    }

    // Função para verificar se a área de formação precisa de registro profissional
    function verificarRegistroProfissional(areaFormacao, index) {
        const registroProfissionalContainer = document.getElementById(`registro-profissional-container-${index}`);

        // Áreas que exigem registro profissional (com asterisco na imagem)
        const areasComRegistro = [
            'Técnico em Enfermagem*',
            'Técnico em Radiologia*',
            'Comunicação Social/Jornalismo*',
            'Direito*',
            'Enfermagem*',
            'Fisioterapia*',
            'Medicina*',
            'Nutrição*',
            'Psicologia*',
        ];

        if (areasComRegistro.includes(areaFormacao)) {
            registroProfissionalContainer.style.display = 'block';
        } else {
            registroProfissionalContainer.style.display = 'none';
        }
    }

    // Inicializar áreas de formação para cursos existentes
    document.addEventListener('DOMContentLoaded', function() {
        // Verificar cursos já carregados
        document.querySelectorAll('.nivel-select').forEach((select, index) => {
            if (select.value) {
                atualizarAreaFormacao(select, index);
            }
        });

        // Inicializar contadores de caracteres PGS
        atualizarContador('objetivo_pgs', 'contador_objetivo');
        atualizarContador('atividades_pgs', 'contador_atividades');
        atualizarContador('contribuicao_pgs', 'contador_contribuicao');
    });

    // Limpar formulário
    function limparFormulario() {
        if (confirm('Tem certeza que deseja limpar todos os dados do formulário?')) {
            // Limpar o formulário
            const form = document.getElementById('formularioForm');
            form.reset();

            // Limpar a lista de cursos e recriar um curso inicial
            const cursosContainer = document.getElementById('cursosContainer');
            cursosContainer.innerHTML = '';
            cursoIndex = 1; // Redefinir índice para 1 (já que o primeiro curso tem índice 0)

            // Adicionar um curso inicial
            const cursoHTML = `
            <div class="curso-item" data-index="0">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Área de Formação 1</h6>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nível</label>
                        <select class="form-select nivel-select" name="cursos[0][nivel]" onchange="atualizarAreaFormacao(this, 0)">
                            <option value="">Selecione...</option>
                            <option value="Superior">Superior</option>
                            <option value="Técnico">Técnico</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Área de Formação</label>
                        <select class="form-select area-formacao-select" name="cursos[0][area_formacao]" id="area-formacao-0" style="display: none;">
                            <option value="">Selecione primeiro o nível...</option>
                        </select>
                        <input type="text" class="form-control area-formacao-input" name="cursos[0][area_formacao_texto]" id="area-formacao-input-0" placeholder="Ex: Tecnologia" style="display: block;">
                    </div>
                    <div class="col-md-12 mb-3" id="registro-profissional-container-0" style="display: none;">
                        <label class="form-label">Número de Registro Profissional <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="cursos[0][registro_profissional]" placeholder="ex: 000000-G/MA">
                        <div class="form-text">Obrigatório para profissões regulamentadas</div>
                    </div>
                </div>
            </div>
        `;
            65
            cursosContainer.insertAdjacentHTML('beforeend', cursoHTML);

            // Limpar a lista de arquivos (drag & drop)
            selectedFiles = [];
            updateFilesList();

            // Atualizar progresso
            verificarProgresso();

            // Disparar auto-save para salvar o estado limpo
            autoSave();
        }
    }

    // Contadores de caracteres para campos PGS
    function atualizarContador(textareaId, contadorId) {
        const textarea = document.getElementById(textareaId);
        const contador = document.getElementById(contadorId);

        if (textarea && contador) {
            contador.textContent = textarea.value.length;

            // Adicionar classe de aviso quando próximo do limite
            if (textarea.value.length > 1400) {
                contador.style.color = '#dc3545'; // vermelho
            } else if (textarea.value.length > 1200) {
                contador.style.color = '#ffc107'; // amarelo
            } else {
                contador.style.color = '#6c757d'; // cinza padrão
            }
        }
    }

    // Event listeners
    document.getElementById('telefone_fixo').addEventListener('input', function() {
        formatarTelefone(this);
    });

    document.getElementById('celular').addEventListener('input', function() {
        formatarTelefone(this);
    });

    document.getElementById('cep').addEventListener('input', function() {
        formatarCEP(this);
    });

    document.getElementById('cep').addEventListener('blur', buscarCEP);

    // Event listeners para contadores de caracteres PGS
    document.getElementById('objetivo_pgs').addEventListener('input', function() {
        atualizarContador('objetivo_pgs', 'contador_objetivo');
    });

    document.getElementById('atividades_pgs').addEventListener('input', function() {
        atualizarContador('atividades_pgs', 'contador_atividades');
    });

    document.getElementById('contribuicao_pgs').addEventListener('input', function() {
        atualizarContador('contribuicao_pgs', 'contador_contribuicao');
    });

    // Validação do formulário
    document.getElementById('formularioForm').addEventListener('submit', function(e) {
        const acao = e.submitter.value;

        if (acao === 'enviar_formulario') {
            const nomeCompleto = document.getElementById('nome_completo').value.trim();
            const email = document.getElementById('email').value.trim();
            const celular = document.getElementById('celular').value.trim();

            if (!nomeCompleto || !email || !celular) {
                e.preventDefault();
                alert('Por favor, preencha os campos obrigatórios: Nome Completo, Email e Celular.');
                return;
            }

            if (!confirm(
                    'Tem certeza que deseja enviar o formulário?'
                )) {
                e.preventDefault();
            }
        }
    });
    </script>
    <script src="assets/js/upload.js"></script>
    <script src="assets/js/delete.js"></script>
    <script src="assets/js/barra_de_progresso.js"></script>
    <script src="assets/js/auto_save.js"></script>
</body>

</html>