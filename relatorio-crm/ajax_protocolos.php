<?php
require_once 'config.php';

// Configuração de cabeçalhos para CORS
header('Content-Type: text/html; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Verificar se as datas foram fornecidas
$dataInicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-30 days'));
$dataFim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');

// Consulta para obter os protocolos
$query = "
    SELECT 
        P.NNUMEPROT as protocolo,
        DATE_FORMAT(P.DDATAPROT, '%d/%m/%Y %H:%i') as data_hora,
        U.CNOMEUSUA as operador,
        GD.CDESCGRUPO as grupo_destino,
        P.CCODIPROT as codigo_protocolo
    FROM CRMPROT P
    LEFT JOIN CRMATEND A ON P.NNUMEPROT = A.NNUMEPROT
    LEFT JOIN CRMGRRES GR ON A.NNUMECLATE = GR.NNUMECLATE
    LEFT JOIN CRMGRUPO GD ON GD.NNUMEGRUPO = GR.NNUMEGRUPO
    LEFT JOIN SEGUSUA U ON P.NOPERUSUA = U.NNUMEUSUA
    WHERE P.DDATAPROT BETWEEN ? AND ?
    ORDER BY P.DDATAPROT DESC
    LIMIT 1000
";

$stmt = $conn->prepare($query);
$stmt->bind_param('ss', $dataInicio, $dataFim);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    echo '<div class="card-grid">';
    
    while ($row = $result->fetch_assoc()) {
        echo '<div class="card">';
        echo '  <div class="card-header">';
        echo '    <h3 class="card-title">Protocolo #' . htmlspecialchars($row['protocolo']) . '</h3>';
        echo '    <span class="card-badge">' . htmlspecialchars($row['grupo_destino'] ?? 'N/A') . '</span>';
        echo '  </div>';
        echo '  <div class="card-body">';
        echo '    <p class="card-text"><strong>Data/Hora:</strong> ' . htmlspecialchars($row['data_hora']) . '</p>';
        echo '    <p class="card-text"><strong>Operador:</strong> ' . htmlspecialchars($row['operador'] ?? 'N/A') . '</p>';
        echo '    <p class="card-text"><strong>Código:</strong> ' . htmlspecialchars($row['codigo_protocolo']) . '</p>';
        echo '  </div>';
        echo '  <div class="card-footer">';
        echo '    <span>Detalhes do protocolo</span>';
        echo '    <div class="card-actions">';
        echo '      <button class="btn-icon ver-detalhes" title="Visualizar" data-protocolo="' . htmlspecialchars($row['codigo_protocolo']) . '"><i class="eva eva-eye-outline"></i></button>';
        echo '    </div>';
        echo '  </div>';
        echo '</div>';
    }
    
    echo '</div>'; // Fecha card-grid
    
    if ($result->num_rows == 1000) {
        echo '<p class="text-muted">Mostrando os 1000 protocolos mais recentes. Use os filtros para refinar sua busca.</p>';
    }
} else {
    echo '<div class="empty-state">';
    echo '  <i>📋</i>';
    echo '  <p>Nenhum protocolo encontrado no período selecionado.</p>';
    echo '</div>';
}

$stmt->close();
$conn->close();
?>
