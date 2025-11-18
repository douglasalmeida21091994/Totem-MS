<?php
require_once 'config.php';

// Configuração de cabeçalhos para CORS
header('Content-Type: text/html; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Verificar se as datas foram fornecidas
$dataInicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-30 days'));
$dataFim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');

// Consulta para obter os operadores ativos
$query = "
    SELECT 
        U.NNUMEUSUA as id_operador,
        U.CNOMEUSUA as nome_operador,
        COUNT(DISTINCT P.NNUMEPROT) as total_protocolos,
        MAX(P.DDATAPROT) as ultimo_atendimento
    FROM SEGUSUA U
    LEFT JOIN CRMPROT P ON U.NNUMEUSUA = P.NOPERUSUA
    WHERE P.DDATAPROT BETWEEN ? AND ?
    GROUP BY U.NNUMEUSUA, U.CNOMEUSUA
    ORDER BY total_protocolos DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param('ss', $dataInicio, $dataFim);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    echo '<div class="card-grid">';
    
    while ($row = $result->fetch_assoc()) {
        $ultimoAtendimento = !empty($row['ultimo_atendimento']) ? 
            date('d/m/Y H:i', strtotime($row['ultimo_atendimento'])) : 'Nenhum';
            
        echo '<div class="card">';
        echo '  <div class="card-header">';
        echo '    <h3 class="card-title">' . htmlspecialchars($row['nome_operador']) . '</h3>';
        echo '    <span class="card-badge">' . number_format($row['total_protocolos'], 0, ',', '.') . ' protocolos</span>';
        echo '  </div>';
        echo '  <div class="card-body">';
        echo '    <p class="card-text"><strong>ID:</strong> ' . htmlspecialchars($row['id_operador']) . '</p>';
        echo '    <p class="card-text"><strong>Último atendimento:</strong> ' . $ultimoAtendimento . '</p>';
        echo '  </div>';
        echo '  <div class="card-footer">';
        echo '    <span>Detalhes do operador</span>';
        echo '    <div class="card-actions">';
        echo '      <button class="btn-icon" title="Ver histórico">📊</button>';
        echo '      <button class="btn-icon" title="Contatar">✉️</button>';
        echo '    </div>';
        echo '  </div>';
        echo '</div>';
    }
    
    echo '</div>'; // Fecha card-grid
} else {
    echo '<div class="empty-state">';
    echo '  <i>👤</i>';
    echo '  <p>Nenhum operador encontrado no período selecionado.</p>';
    echo '</div>';
}

$stmt->close();
$conn->close();
?>
