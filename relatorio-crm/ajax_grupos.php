<?php
require_once 'config.php';

// Configuração de cabeçalhos para CORS
header('Content-Type: text/html; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Verificar se as datas foram fornecidas
$dataInicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-30 days'));
$dataFim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');

// Consulta para obter os grupos de destino
$query = "
    SELECT 
        GD.NNUMEGRUPO as id_grupo,
        GD.CDESCGRUPO as nome_grupo,
        COUNT(DISTINCT P.NNUMEPROT) as total_protocolos,
        COUNT(DISTINCT P.NOPERUSUA) as total_operadores,
        MAX(P.DDATAPROT) as ultimo_atendimento
    FROM CRMGRUPO GD
    LEFT JOIN CRMGRRES GR ON GD.NNUMEGRUPO = GR.NNUMEGRUPO
    LEFT JOIN CRMATEND A ON GR.NNUMECLATE = A.NNUMECLATE
    LEFT JOIN CRMPROT P ON A.NNUMEPROT = P.NNUMEPROT AND P.DDATAPROT BETWEEN ? AND ?
    GROUP BY GD.NNUMEGRUPO, GD.CDESCGRUPO
    HAVING total_protocolos > 0
    ORDER BY total_protocolos DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param('ss', $dataInicio, $dataFim);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    echo '<div class="card-grid">';
    
    // Cores para os badges dos grupos
    $colors = ['#6C5CE7', '#00CEC9', '#FF7675', '#74B9FF', '#A29BFE', '#55EFC4', '#FFEAA7', '#FDCB6E'];
    $colorIndex = 0;
    
    while ($row = $result->fetch_assoc()) {
        $ultimoAtendimento = !empty($row['ultimo_atendimento']) ? 
            date('d/m/Y H:i', strtotime($row['ultimo_atendimento'])) : 'Nenhum';
            
        $color = $colors[$colorIndex % count($colors)];
        $colorIndex++;
            
        echo '<div class="card" style="border-left-color: ' . $color . ';">';
        echo '  <div class="card-header">';
        echo '    <h3 class="card-title">' . htmlspecialchars($row['nome_grupo']) . '</h3>';
        echo '    <span class="card-badge" style="background: ' . $color . '20; color: ' . $color . ';">' . 
             number_format($row['total_protocolos'], 0, ',', '.') . ' protocolos</span>';
        echo '  </div>';
        echo '  <div class="card-body">';
        echo '    <p class="card-text"><strong>ID do Grupo:</strong> ' . htmlspecialchars($row['id_grupo']) . '</p>';
        echo '    <p class="card-text"><strong>Operadores ativos:</strong> ' . number_format($row['total_operadores'], 0, ',', '.') . '</p>';
        echo '    <p class="card-text"><strong>Último atendimento:</strong> ' . $ultimoAtendimento . '</p>';
        echo '  </div>';
        echo '  <div class="card-footer">';
        echo '    <span>Detalhes do grupo</span>';
        echo '    <div class="card-actions">';
        echo '      <button class="btn-icon" title="Ver operadores">👥</button>';
        echo '      <button class="btn-icon" title="Ver relatório">📈</button>';
        echo '    </div>';
        echo '  </div>';
        echo '</div>';
    }
    
    echo '</div>'; // Fecha card-grid
} else {
    echo '<div class="empty-state">';
    echo '  <i>🏷️</i>';
    echo '  <p>Nenhum grupo encontrado no período selecionado.</p>';
    echo '</div>';
}

$stmt->close();
$conn->close();
?>
