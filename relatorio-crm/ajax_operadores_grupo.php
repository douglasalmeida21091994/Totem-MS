<?php
// Incluir configurações de conexão
require_once 'config.php';

// Verificar se o grupo foi informado
if (!isset($_GET['grupo'])) {
    die("Grupo não especificado.");
}

$grupo = $_GET['grupo'];

// Consulta para obter os operadores que enviaram protocolos para o grupo de destino
$sql = "
    SELECT 
        U.CNOMEUSUA as operador,
        COUNT(DISTINCT P.NNUMEPROT) as total_protocolos
    FROM CRMPROT P
    JOIN SEGUSUA U ON P.NOPERUSUA = U.NNUMEUSUA
    JOIN CRMATEND A ON P.NNUMEPROT = A.NNUMEPROT
    JOIN CRMGRRES GR ON A.NNUMECLATE = GR.NNUMECLATE
    JOIN CRMGRUPO G ON GR.NNUMEGRUPO = G.NNUMEGRUPO
    WHERE G.CDESCGRUPO = ?
    GROUP BY U.NNUMEUSUA, U.CNOMEUSUA
    ORDER BY total_protocolos DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $grupo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo '<div class="card-grid">';
    while ($row = $result->fetch_assoc()) {
        echo '<div class="card">';
        echo '  <div class="card-header">';
        echo '    <h3 class="card-title">' . htmlspecialchars($row['operador']) . '</h3>';
        echo '    <span class="card-badge">' . $row['total_protocolos'] . ' protocolos</span>';
        echo '  </div>';
        echo '  <div class="card-body">';
        echo '    <p class="card-text"><strong>Grupo:</strong> ' . htmlspecialchars($grupo) . '</p>';
        echo '  </div>';
        echo '  <div class="card-footer">';
        echo '    <span>Detalhes do operador</span>';
        echo '    <div class="card-actions">';
        echo '      <button class="btn-icon" title="Ver detalhes" onclick="exibirDetalhesOperador(\'' . htmlspecialchars(addslashes($row['operador'])) . '\', \'' . htmlspecialchars(addslashes($grupo)) . '\')"><i class="eva eva-eye-outline"></i></button>';
        echo '    </div>';
        echo '  </div>';
        echo '</div>';
    }
    echo '</div>';
} else {
    echo '<div class="empty-state">';
    echo '  <i class="eva eva-people-outline" style="font-size: 48px; color: #6C5CE7; display: block; margin-bottom: 15px;"></i>';
    echo '  <p>Nenhum operador encontrado neste grupo.</p>';
    echo '</div>';
}
?>