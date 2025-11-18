<?php
require_once 'config.php';

// Capturar parâmetros
$protocolo = isset($_GET['protocolo']) ? intval($_GET['protocolo']) : 0;
$classificacao = isset($_GET['classificacao']) ? $_GET['classificacao'] : '';

if ($protocolo <= 0) {
    die("Protocolo inválido.");
}

// Consulta detalhada
$sql = "
SELECT 
    CRMATEND.NNUMEATEND,
    CRMCLATE.CDESCCLATE AS CLASSIFICACAO,
    CRMMENSA.NNUMEMENSA,
    DATE_FORMAT(CRMMENSA.DDATAMENSA, '%d/%m/%Y %H:%i:%s') AS DATA_MENSAGEM,
    USU.CNOMEUSUA AS ORIGEM,
    CRMMENSA.CTEXTMENSA AS OBSERVACAO
FROM CRMATEND
    JOIN CRMCLATE ON CRMATEND.NNUMECLATE = CRMCLATE.NNUMECLATE
    JOIN CRMMENSA ON CRMATEND.NNUMEATEND = CRMMENSA.NNUMEATEND
    LEFT JOIN SEGUSUA USU ON CRMMENSA.NORIGUSUA = USU.NNUMEUSUA
WHERE CRMATEND.NNUMEPROT = ?
ORDER BY CRMMENSA.DDATAMENSA DESC;
";

// Usando prepared statement (mais seguro)
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $protocolo);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Detalhes do Protocolo <?php echo $protocolo; ?></title>
<style>
:root {
    --primary-color: #6C5CE7;
    --primary-dark: #5F4DD0;
    --background-light: #F5F5F5;
    --background-white: #FFFFFF;
    --text-dark: #2D3436;
    --text-light: #636E72;
    --border-radius-md: 10px;
    --shadow-medium: 0 4px 15px rgba(0,0,0,0.15);
}

body {
    font-family: "Segoe UI", Arial, sans-serif;
    background: var(--background-light);
    margin: 0;
    padding: 20px;
    color: var(--text-dark);
}

.container {
    max-width: 900px;
    margin: 0 auto;
    background: var(--background-white);
    padding: 25px 30px;
    border-radius: var(--border-radius-md);
    box-shadow: var(--shadow-medium);
}

h1 {
    color: var(--primary-dark);
    text-align: center;
    margin-bottom: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

th, td {
    padding: 10px;
    text-align: left;
}

th {
    background: var(--primary-color);
    color: white;
}

tr:nth-child(even) {
    background: var(--background-light);
}

a.voltar {
    display: inline-block;
    margin-bottom: 15px;
    color: var(--primary-dark);
    text-decoration: none;
    font-weight: 600;
}
a.voltar:hover {
    text-decoration: underline;
}
</style>
</head>
<body>

<div class="container">
    <a href="relatorio.php" class="voltar">← Voltar</a>
    <h1>Protocolo Nº <?php echo htmlspecialchars($protocolo); ?></h1>
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Origem (Usuário)</th>
                <th>Observação</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['DATA_MENSAGEM']); ?></td>
                        <td><?php echo htmlspecialchars($row['ORIGEM']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($row['OBSERVACAO'])); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="3" style="text-align:center; color:var(--text-light);">
                    Nenhuma observação registrada para este protocolo.
                </td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
