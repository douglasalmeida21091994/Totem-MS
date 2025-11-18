<?php
// Incluir configurações de conexão
require_once 'config.php';

// Verificar se a conexão foi estabelecida corretamente
if (mysqli_connect_errno()) {
    die("Erro na conexão: " . mysqli_connect_error());
}

// ===============================
// Definir datas padrão (últimos 7 dias)
// ===============================
$dataFim = date('Y-m-d');
$dataInicio = date('Y-m-d', strtotime('-20 days'));
$filtroOperador = '';

// ===============================
// Verificar se há filtros
// ===============================
if (!empty($_GET['data_inicio'])) {
    $dataInicio = $_GET['data_inicio'];
}
if (!empty($_GET['data_fim'])) {
    $dataFim = $_GET['data_fim'];
}
if (!empty($_GET['operador'])) {
    $filtroOperador = $_GET['operador'];
}

// Buscar lista de operadores para o select
$sqlOperadores = "SELECT DISTINCT U.NNUMEUSUA, U.CNOMEUSUA 
                 FROM SEGUSUA U 
                 JOIN CRMGRUSU GU ON GU.NNUMEUSUA = U.NNUMEUSUA 
                 JOIN CRMGRUPO GO ON GO.NNUMEGRUPO = GU.NNUMEGRUPO 
                 WHERE GO.CDESCGRUPO = 'ATENDIMENTO'
                 ORDER BY U.CNOMEUSUA";
$resultOperadores = $conn->query($sqlOperadores);

// ===============================
// Consulta SQL corrigida — agrupa por operador e grupo destino
// ===============================
$sql = "
SELECT 
    DATE_FORMAT(P.DDATAPROT, '%d/%m/%Y') AS DATA_ABERTURA,
    U.CNOMEUSUA AS OPERADOR,
    U.NNUMEUSUA AS CODIGO_OPERADOR,
    COALESCE(GD.CDESCGRUPO, 'NÃO DEFINIDO') AS GRUPO_DESTINO,
    GROUP_CONCAT(DISTINCT COALESCE(C.CDESCCLATE, 'NÃO CLASSIFICADO') ORDER BY C.CDESCCLATE SEPARATOR ', ') AS CLASSIFICACOES,
    COUNT(DISTINCT P.NNUMEPROT) AS QTDE_PROTOCOLOS,
    GROUP_CONCAT(DISTINCT P.CCODIPROT ORDER BY P.NNUMEPROT SEPARATOR ', ') AS PROTOCOLOS
FROM CRMPROT P
JOIN SEGUSUA U ON U.NNUMEUSUA = P.NOPERUSUA
JOIN CRMGRUSU GU ON GU.NNUMEUSUA = U.NNUMEUSUA
JOIN CRMGRUPO GO ON GO.NNUMEGRUPO = GU.NNUMEGRUPO AND GO.CDESCGRUPO = 'ATENDIMENTO'
LEFT JOIN CRMATEND A ON P.NNUMEPROT = A.NNUMEPROT
LEFT JOIN CRMGRRES GR ON A.NNUMECLATE = GR.NNUMECLATE
LEFT JOIN CRMGRUPO GD ON GD.NNUMEGRUPO = GR.NNUMEGRUPO
LEFT JOIN CRMCLATE C ON A.NNUMECLATE = C.NNUMECLATE
WHERE P.DDATAPROT BETWEEN ? AND ?
";

// Adiciona filtro de operador se estiver preenchido
if (!empty($filtroOperador)) {
    $sql .= " AND U.NNUMEUSUA = ? ";
}

$sql .= "
GROUP BY 
    DATE(P.DDATAPROT),
    U.CNOMEUSUA,
    U.NNUMEUSUA,
    GD.CDESCGRUPO
ORDER BY 
    DATE(P.DDATAPROT) DESC,
    U.CNOMEUSUA;
";

// ===============================
// Preparar e executar a consulta
// ===============================
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Erro na preparação da consulta: " . $conn->error);
}

// Bind dos parâmetros dinamicamente
if (!empty($filtroOperador)) {
    $stmt->bind_param('sss', $dataInicio, $dataFim, $filtroOperador);
} else {
    $stmt->bind_param('ss', $dataInicio, $dataFim);
}

if (!$stmt->execute()) {
    die("Erro ao executar a consulta: " . $stmt->error);
}
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Relatório de Solicitações - Smile Saúde</title>
<style>
/* Estilo para centralizar a coluna Qtd. Protocolos e o total geral */
table th:nth-child(5),
table td:nth-child(5),
table tr:last-child td:last-child {
    text-align: center !important;
}

:root {
    --primary-color: #6C5CE7;
    --primary-dark: #5F4DD0;
    --primary-light: #A29BFE;
    --secondary-color: #00CEC9;
    --text-dark: #2D3436;
    --text-light: #636E72;
    --background-light: #F5F5F5;
    --background-white: #FFFFFF;
    --shadow-medium: 0 4px 15px rgba(0, 0, 0, 0.15);
    --border-radius-lg: 15px;
    --transition-fast: 0.2s ease;
}

body {
    font-family: "Segoe UI", Arial, sans-serif;
    background: var(--background-light);
    margin: 0;
    padding: 20px;
    color: var(--text-dark);
}

.container {
    max-width: 1100px;
    margin: 0 auto;
    background: var(--background-white);
    padding: 25px 30px;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-medium);
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 15px;
}

.header h1 {
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 10px;
}

.btn-dashboard {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--primary-color);
    color: white;
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 500;
    transition: background-color 0.2s ease;
}

.btn-dashboard:hover {
    background: var(--primary-dark);
    color: white;
}

h1 {
    color: var(--primary-dark);
    text-align: center;
    margin-bottom: 25px;
    font-weight: 600;
}

.filtro-data {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.filter-row {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.filter-group label {
    font-weight: 500;
    color: var(--text-dark);
    font-size: 14px;
}

.periodo-select,
.filter-group input[type="date"] {
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    background-color: white;
    font-size: 14px;
    cursor: pointer;
    transition: border-color 0.3s ease;
    min-width: 180px;
}

.periodo-select:focus,
.filter-group input[type="date"]:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(108, 92, 231, 0.25);
}

/* Campos de data com animação */
.date-fields-wrapper {
    display: flex;
    gap: 8px;
    max-width: 0;
    opacity: 0;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.date-fields-wrapper.visible {
    max-width: 800px;
    opacity: 1;
}

/* Container dos botões */
.filter-buttons {
    display: flex;
    gap: 6px;
    margin-left: 8px;
    transition: margin-left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Quando os campos de data estão visíveis, afasta os botões */
.date-fields-wrapper.visible ~ .filter-buttons {
    margin-left: auto;
}

.btn-filter, .btn-clear {
    padding: 8px 20px;
    border: none;
    border-radius: 4px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 100px;
    height: 38px;
}

.btn-filter {
    background-color: var(--primary-color);
    color: white;
    border: 1px solid var(--primary-dark);
}

.btn-filter:hover {
    background-color: var(--primary-dark);
    transform: translateY(-1px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.btn-clear {
    background-color: #f8f9fa;
    color: #495057;
    border: 1px solid #ced4da;
}

.btn-clear:hover {
    background-color: #e9ecef;
    transform: translateY(-1px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

/* Ícones SVG */
.btn-icon {
    margin-right: 6px;
}

/* Tabela */
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
    margin-top: 15px;
}

th, td {
    padding: 10px 12px;
    text-align: left;
}

th {
    background: var(--primary-color);
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

tr:nth-child(even) {
    background-color: var(--background-light);
}

tr:hover {
    background-color: var(--primary-light);
    transition: var(--transition-fast);
}

.link-qtd {
    display: inline-block;
    min-width: 30px;
    padding: 5px 10px;
    background-color: var(--primary-color);
    color: white !important;
    font-weight: bold;
    text-align: center;
    border-radius: 15px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.link-qtd:hover {
    background-color: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    text-decoration: none;
}

.footer {
    margin-top: 30px;
    text-align: center;
    font-size: 12px;
    color: var(--text-light);
    padding: 10px 0;
    border-top: 1px solid #eee;
}

/* Responsividade */
@media (max-width: 768px) {
    .filter-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .date-fields-wrapper {
        flex-direction: column;
        max-width: 100%;
    }
    
    .date-fields-wrapper.visible {
        max-width: 100%;
    }
    
    .filter-buttons {
        margin-left: 0 !important;
        margin-top: 10px;
        width: 100%;
    }
    
    .btn-filter, .btn-clear {
        flex: 1;
    }
}
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Relatório de Solicitações - CRM</h1>
        <div class="header-actions">
            <a href="dashboard_geral.php" class="btn-dashboard">
                <i class="fas fa-chart-line"></i> Dashboard Geral
            </a>
        </div>
    </div>
    
    <div class="filtro-data">
        <form method="GET" action="" id="dateFilterForm">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="periodo">Período:</label>
                    <select id="periodo" name="periodo" class="periodo-select">
                        <option value="">Personalizado</option>
                        <option value="7">7 dias</option>
                        <option value="15">15 dias</option>
                        <option value="30">30 dias</option>
                        <option value="60">60 dias</option>
                        <option value="90">90 dias</option>
                        <option value="120">120 dias</option>
                        <option value="180">180 dias</option>
                        <option value="365">12 meses</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="operador">Operador:</label>
                    <select id="operador" name="operador" class="periodo-select">
                        <option value="">Todos os Operadores</option>
                        <?php 
                        if ($resultOperadores && $resultOperadores->num_rows > 0): 
                            while ($operador = $resultOperadores->fetch_assoc()): 
                                $selected = (isset($_GET['operador']) && $_GET['operador'] == $operador['NNUMEUSUA']) ? 'selected' : '';
                        ?>
                            <option value="<?= $operador['NNUMEUSUA'] ?>" <?= $selected ?>><?= htmlspecialchars($operador['CNOMEUSUA']) ?></option>
                        <?php 
                            endwhile;
                        endif; 
                        ?>
                    </select>
                </div>
                
                <div class="date-fields-wrapper" id="dateFieldsWrapper">
                    <div class="filter-group">
                        <label for="data_inicio">Data Início:</label>
                        <input type="date" id="data_inicio" name="data_inicio" value="<?php echo htmlspecialchars($dataInicio); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="data_fim">Data Fim:</label>
                        <input type="date" id="data_fim" name="data_fim" value="<?php echo htmlspecialchars($dataFim); ?>">
                    </div>
                </div>
                
                <div class="filter-buttons">
                    <button type="submit" class="btn-filter" title="Aplicar filtro">
                        <svg class="btn-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5v-2z"/>
                        </svg>
                        Filtrar
                    </button>
                    <button type="button" class="btn-clear" id="btnLimpar" title="Limpar filtros">
                        <svg class="btn-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                        </svg>
                        Limpar
                    </button>
                </div>
            </div>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Data Abertura</th>
                <th>Operador</th>
                <th>Grupo Destino</th>
                <th>Classificações</th>
                <th>Qtd. Protocolos</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if ($result && $result->num_rows > 0): 
                $totalGeral = 0;
                while ($row = $result->fetch_assoc()): 
                    $totalGeral += (int)($row['QTDE_PROTOCOLOS'] ?? 0);
            ?>
                    <tr>
                        <td><?= htmlspecialchars($row['DATA_ABERTURA']); ?></td>
                        <td><?= htmlspecialchars($row['OPERADOR']); ?></td>
                        <td><?= htmlspecialchars($row['GRUPO_DESTINO']); ?></td>
                        <td><?= htmlspecialchars($row['CLASSIFICACOES']); ?></td>
                        <td>
                            <a class="link-qtd" 
                               href="detalhes_grupo.php?data_inicio=<?= urlencode($dataInicio); ?>&data_fim=<?= urlencode($dataFim); ?>&operador=<?= urlencode($row['OPERADOR']); ?>&grupo_destino=<?= urlencode($row['GRUPO_DESTINO']); ?>&protocolos=<?= urlencode($row['PROTOCOLOS']); ?>">
                                <?= (int)$row['QTDE_PROTOCOLOS']; ?>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <tr style="font-weight: bold; background-color: #f0f0f0;">
                    <td colspan="4">Total Geral</td>
                    <td><?= $totalGeral; ?>                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        &copy; <?= date('Y'); ?> - Smile Saúde | Relatório de Solicitações CRM
    </div>
</div>

<script>
function getDateDaysAgo(days) {
    const date = new Date();
    date.setDate(date.getDate() - days);
    return date.toISOString().split('T')[0];
}

// Função para mostrar/ocultar campos de data
function toggleDateFields(show) {
    const dateFieldsWrapper = document.getElementById('dateFieldsWrapper');
    if (show) {
        dateFieldsWrapper.classList.add('visible');
    } else {
        dateFieldsWrapper.classList.remove('visible');
    }
}

// Função para atualizar as datas baseado no período selecionado
function updateDateRange(days) {
    const dataFimInput = document.getElementById('data_fim');
    const dataInicioInput = document.getElementById('data_inicio');
    
    if (days) {
        const today = new Date().toISOString().split('T')[0];
        const startDate = getDateDaysAgo(parseInt(days));
        
        dataInicioInput.value = startDate;
        dataFimInput.value = today;
        
        // Oculta os campos de data quando um período pré-definido é selecionado
        toggleDateFields(false);
    } else {
        // Mostra os campos de data quando 'Personalizado' é selecionado
        toggleDateFields(true);
    }
}

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    const periodoSelect = document.getElementById('periodo');
    
    // Verifica se já há um período selecionado (após submit do formulário)
    const urlParams = new URLSearchParams(window.location.search);
    const periodoSelecionado = urlParams.get('periodo');
    
    if (periodoSelecionado) {
        periodoSelect.value = periodoSelecionado;
        updateDateRange(periodoSelecionado);
    } else {
        // Por padrão, mostra os campos de data
        toggleDateFields(true);
    }
    
    // Adiciona o evento de mudança ao select de período
    periodoSelect.addEventListener('change', function() {
        updateDateRange(this.value);
    });
    
    // Adiciona o evento de mudança ao select de operador
    const operadorSelect = document.getElementById('operador');
    if (operadorSelect) {
        operadorSelect.addEventListener('change', function() {
            // Adiciona o período atual ao formulário antes de submeter
            const periodoInput = document.createElement('input');
            periodoInput.type = 'hidden';
            periodoInput.name = 'periodo';
            periodoInput.value = document.getElementById('periodo').value;
            
            const form = document.getElementById('dateFilterForm');
            form.appendChild(periodoInput);
            form.submit();
        });
    }
    
        // Adiciona o campo de período ao formulário quando for enviado
    const form = document.getElementById('dateFilterForm');
    form.addEventListener('submit', function(e) {
        // Adiciona o período se não for um submit de limpar
        if (!e.submitter || !e.submitter.classList.contains('btn-clear')) {
            const periodoInput = document.createElement('input');
            periodoInput.type = 'hidden';
            periodoInput.name = 'periodo';
            periodoInput.value = periodoSelect.value;
            this.appendChild(periodoInput);
        }
    });
    
    // Adiciona evento para o botão de limpar
    const btnClear = document.getElementById('btnLimpar');
    if (btnClear) {
        btnClear.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Redireciona para a página sem parâmetros
            window.location.href = 'relatorio.php';
        });
    }
});

function getCurrentDate() {
    return new Date().toISOString().split('T')[0];
}

function checkCurrentPeriod() {
    const periodoSelect = document.getElementById('periodo');
    const startDateInput = document.getElementById('data_inicio');
    const endDateInput = document.getElementById('data_fim');
    const currentStart = startDateInput.value;
    const currentEnd = endDateInput.value;
    const today = getCurrentDate();
    
    if (currentEnd !== today) {
        periodoSelect.value = '';
        return;
    }
    
    // Verifica qual período corresponde às datas atuais
    const options = periodoSelect.options;
    for (let i = 0; i < options.length; i++) {
        const days = parseInt(options[i].value);
        if (!isNaN(days) && currentStart === getDateDaysAgo(days)) {
            periodoSelect.value = days;
            return;
        }
    }
    
    // Se não encontrar correspondência, define como personalizado
    periodoSelect.value = '';
}

function toggleDateFields(selectedValue) {
    const dateFieldsWrapper = document.getElementById('dateFieldsWrapper');
    
    if (selectedValue === '') {
        // Mostra os campos de data (Personalizado)
        dateFieldsWrapper.classList.add('visible');
    } else {
        // Esconde os campos de data (período predefinido)
        dateFieldsWrapper.classList.remove('visible');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const periodoSelect = document.getElementById('periodo');
    const startDateInput = document.getElementById('data_inicio');
    const endDateInput = document.getElementById('data_fim');
    const form = document.getElementById('dateFilterForm');

    // Evento de mudança no select de período
    periodoSelect.addEventListener('change', function() {
        const days = parseInt(this.value);
        toggleDateFields(this.value);
        
        if (!isNaN(days)) {
            startDateInput.value = getDateDaysAgo(days);
            endDateInput.value = getCurrentDate();
            form.submit();
        } else if (this.value === '') {
            // Personalizado - limpa os campos para o usuário preencher
            startDateInput.value = '';
            endDateInput.value = '';
        }
    });

    // Evento de mudança nos campos de data
    [startDateInput, endDateInput].forEach(input => {
        input.addEventListener('change', function() {
            periodoSelect.value = ''; // Define como personalizado
            toggleDateFields('');
        });
    });

    // Verifica o período ao carregar a página
    checkCurrentPeriod();
    
    // Inicializa o estado correto ao carregar
    toggleDateFields(periodoSelect.value);
});
</script>

</body>
</html>

<?php
$conn->close();
?>