<?php
// Incluir configurações de conexão
require_once 'config.php';

// Verificar se os parâmetros necessários foram fornecidos
if (!isset($_GET['operador']) || !isset($_GET['grupo'])) {
    die("Parâmetros insuficientes.");
}

$operador = $_GET['operador'];
$grupo = $_GET['grupo'];
$dataInicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-01');
$dataFim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');

// Consulta para obter os detalhes dos protocolos do operador no grupo
$sql = "
    SELECT 
        P.NNUMEPROT as protocolo,
        P.CCODIPROT as codigo_protocolo,
        DATE_FORMAT(P.DDATAPROT, '%d/%m/%Y %H:%i') as data_hora,
        C.CDESCCLATE as classificacao,
        G.CDESCGRUPO as grupo_destino,
        M.CTEXTMENSA as mensagem
    FROM CRMPROT P
    JOIN SEGUSUA U ON P.NOPERUSUA = U.NNUMEUSUA
    LEFT JOIN CRMATEND A ON P.NNUMEPROT = A.NNUMEPROT
    LEFT JOIN CRMGRRES GR ON A.NNUMECLATE = GR.NNUMECLATE
    LEFT JOIN CRMGRUPO G ON GR.NNUMEGRUPO = G.NNUMEGRUPO
    LEFT JOIN CRMCLATE C ON A.NNUMECLATE = C.NNUMECLATE
    LEFT JOIN CRMMENSA M ON A.NNUMEATEND = M.NNUMEATEND
    WHERE U.CNOMEUSUA = ? 
      AND G.CDESCGRUPO = ?
      AND P.DDATAPROT BETWEEN ? AND ?
    ORDER BY P.DDATAPROT DESC
    LIMIT 100
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ssss', $operador, $grupo, $dataInicio, $dataFim);
$stmt->execute();
$result = $stmt->get_result();

echo '<div class="detalhes-operador">';
echo '  <h3>' . htmlspecialchars($operador) . ' - ' . htmlspecialchars($grupo) . '</h3>';
echo '  <p class="periodo-consulta">Período: ' . date('d/m/Y', strtotime($dataInicio)) . ' a ' . date('d/m/Y', strtotime($dataFim)) . '</p>';

if ($result->num_rows > 0) {
    echo '<div class="protocolos-lista">';
    echo '  <h4>Últimos Protocolos</h4>';
    echo '  <div class="tabela-container">';
    echo '    <table class="tabela-detalhes">';
    echo '      <thead>';
    echo '        <tr>';
    echo '          <th>Protocolo</th>';
    echo '          <th>Data/Hora</th>';
    echo '          <th>Classificação</th>';
    echo '          <th>Mensagem</th>';
    echo '        </tr>';
    echo '      </thead>';
    echo '      <tbody>';
    
    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '  <td>' . htmlspecialchars($row['codigo_protocolo']) . '</td>';
        echo '  <td>' . htmlspecialchars($row['data_hora']) . '</td>';
        echo '  <td>' . htmlspecialchars($row['classificacao'] ?? 'N/A') . '</td>';
        echo '  <td>' . nl2br(htmlspecialchars(substr($row['mensagem'] ?? 'Sem mensagem', 0, 100) . (strlen($row['mensagem'] ?? '') > 100 ? '...' : ''))) . '</td>';
        echo '</tr>';
    }
    
    echo '      </tbody>';
    echo '    </table>';
    echo '  </div>';
    
    // Estatísticas adicionais
    $sqlStats = "
        SELECT 
            COUNT(DISTINCT P.NNUMEPROT) as total_protocolos,
            COUNT(DISTINCT DATE(P.DDATAPROT)) as dias_trabalhados,
            COUNT(DISTINCT P.NNUMEPROT) / GREATEST(COUNT(DISTINCT DATE(P.DDATAPROT)), 1) as media_diaria
        FROM CRMPROT P
        JOIN SEGUSUA U ON P.NOPERUSUA = U.NNUMEUSUA
        LEFT JOIN CRMATEND A ON P.NNUMEPROT = A.NNUMEPROT
        LEFT JOIN CRMGRRES GR ON A.NNUMECLATE = GR.NNUMECLATE
        LEFT JOIN CRMGRUPO G ON GR.NNUMEGRUPO = G.NNUMEGRUPO
        WHERE U.CNOMEUSUA = ? 
          AND G.CDESCGRUPO = ?
          AND P.DDATAPROT BETWEEN ? AND ?
    ";
    
    $stmtStats = $conn->prepare($sqlStats);
    $stmtStats->bind_param('ssss', $operador, $grupo, $dataInicio, $dataFim);
    $stmtStats->execute();
    $stats = $stmtStats->get_result()->fetch_assoc();
    
    echo '<div class="estatisticas">';
    echo '  <h4>Estatísticas</h4>';
    echo '  <div class="stats-grid">';
    echo '    <div class="stat-item">';
    echo '      <i class="eva eva-file-text-outline stat-icon"></i>';
    echo '      <span class="stat-value">' . number_format($stats['total_protocolos'], 0, ',', '.') . '</span>';
    echo '      <span class="stat-label">Total de Protocolos</span>';
    echo '    </div>';
    echo '    <div class="stat-item">';
    echo '      <i class="eva eva-calendar-outline stat-icon"></i>';
    echo '      <span class="stat-value">' . number_format($stats['dias_trabalhados'], 0, ',', '.') . '</span>';
    echo '      <span class="stat-label">Dias Trabalhados</span>';
    echo '    </div>';
    echo '    <div class="stat-item">';
    echo '      <i class="eva eva-bar-chart-outline stat-icon"></i>';
    echo '      <span class="stat-value">' . number_format($stats['media_diaria'], 1, ',', '.') . '</span>';
    echo '      <span class="stat-label">Média Diária</span>';
    echo '    </div>';
    echo '  </div>';
    echo '</div>';
    
} else {
    echo '<div class="empty-state">';
    echo '  <i>📋</i>';
    echo '  <p>Nenhum protocolo encontrado para este operador no período selecionado.</p>';
    echo '</div>';
}

echo '</div>'; // Fecha div.detalhes-operador

// Estilos para os detalhes do operador
echo '<style>
.detalhes-operador {
    padding: 20px;
    max-width: 100%;
    margin: 0 auto;
}

.detalhes-operador h3 {
    color: #333;
    margin-bottom: 10px;
    border-bottom: 2px solid #6C5CE7;
    padding-bottom: 10px;
}

.periodo-consulta {
    color: #666;
    margin-bottom: 20px;
    font-style: italic;
}

.protocolos-lista {
    margin-bottom: 30px;
}

.protocolos-lista h4 {
    color: #444;
    margin: 20px 0 10px 0;
}

.tabela-container {
    overflow-x: auto;
    margin-bottom: 20px;
}

.tabela-detalhes {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.tabela-detalhes th, 
.tabela-detalhes td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #e0e0e0;
}

.tabela-detalhes th {
    background-color: #f5f5f5;
    font-weight: 600;
    color: #444;
}

.tabela-detalhes tr:hover {
    background-color: #f9f9f9;
}

.estatisticas {
    margin-top: 30px;
    padding: 20px;
    background-color: #f8f9fa;
    border-radius: 8px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.stat-item {
    background: white;
    padding: 20px 15px;
    border-radius: 6px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    position: relative;
}

.stat-icon {
    font-size: 24px;
    color: #6C5CE7;
    margin-bottom: 10px;
    display: block;
}

.stat-value {
    display: block;
    font-size: 24px;
    font-weight: 600;
    color: #6C5CE7;
    margin: 5px 0;
    line-height: 1.2;
}

.stat-label {
    color: #666;
    font-size: 14px;
    display: block;
    margin-top: 5px;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 15px;
    display: block;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .tabela-detalhes {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
    }
}
</style>';
?>
