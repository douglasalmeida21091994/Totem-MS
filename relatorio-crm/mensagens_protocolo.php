<?php
require_once 'config.php';

if (!isset($_GET['protocolo'])) {
    die('Protocolo inválido.');
}

$protocolo = $_GET['protocolo'];

$sql = "
SELECT 
    DATE_FORMAT(M.DDATAMENSA, '%d/%m/%Y %H:%i:%s') AS DATA,
    M.DDATAMENSA AS DATA_ORIGINAL,
    M.CTEXTMENSA AS MENSAGEM,
    U.CNOMEUSUA AS USUARIO
FROM CRMMENSA M
JOIN CRMATEND A ON A.NNUMEATEND = M.NNUMEATEND
JOIN CRMPROT P ON P.NNUMEPROT = A.NNUMEPROT
LEFT JOIN SEGUSUA U ON M.NORIGUSUA = U.NNUMEUSUA
WHERE P.CCODIPROT = ?
ORDER BY M.DDATAMENSA DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $protocolo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo '<table style="width:100%; border-collapse:collapse;">';
    echo '<tr><th>Data</th><th>Usuário</th><th>Mensagem</th></tr>';
    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['DATA']) . '</td>';
        echo '<td>' . htmlspecialchars($row['USUARIO']) . '</td>';
        echo '<td>' . nl2br(htmlspecialchars($row['MENSAGEM'])) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
} else {
    echo '<p>Nenhuma mensagem encontrada para este protocolo.</p>';
}

$stmt->close();
$conn->close();
?>
