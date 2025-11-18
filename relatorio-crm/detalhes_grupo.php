<?php
// Incluir configurações de conexão
require_once 'config.php';

// Verificar se a conexão foi estabelecida corretamente
if (mysqli_connect_errno()) {
    die("Erro na conexão: " . mysqli_connect_error());
}

// Verificar parâmetros obrigatórios
if (!isset($_GET['operador']) || !isset($_GET['grupo_destino'])) {
    die("Parâmetros inválidos.");
}

// Obter parâmetros
$operador = $_GET['operador'];
$grupoDestino = $_GET['grupo_destino'];
$dataInicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-7 days'));
$dataFim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');

// Se houver lista de protocolos específica, usá-la
$protocolosEspecificos = [];
if (isset($_GET['protocolos']) && !empty($_GET['protocolos'])) {
    $protocolos = explode(',', $_GET['protocolos']);
    foreach ($protocolos as $protocolo) {
        // Manter a formatação original do protocolo (com hífens e espaços)
        $protocolo = trim($protocolo);
        if (!empty($protocolo)) {
            $protocolosEspecificos[] = $protocolo;
        }
    }
    $placeholders = rtrim(str_repeat('?,', count($protocolosEspecificos)), ',');
}

// Consulta para obter os detalhes dos protocolos
$sql = "
    SELECT 
        DATE_FORMAT(CRMPROT.DDATAPROT, '%d/%m/%Y') AS DATA_ABERTURA,
        CRMPROT.CCODIPROT AS NUMERO_LOTE,
        USU.CNOMEUSUA AS OPERADOR,
        GRUPO_DEST.CDESCGRUPO AS GRUPO_DESTINO,
        CRMCLATE.CDESCCLATE AS CLASSIFICACAO,
        CRMMENSA.CTEXTMENSA AS OBSERVACAO,
        DATE_FORMAT(CRMMENSA.DDATAMENSA, '%d/%m/%Y %H:%i:%s') AS DATA_MENSAGEM,
        USU_MSG.CNOMEUSUA AS USUARIO_MENSAGEM
    FROM CRMPROT
         JOIN CRMATEND ON CRMPROT.NNUMEPROT = CRMATEND.NNUMEPROT
         JOIN SEGUSUA USU ON USU.NNUMEUSUA = CRMPROT.NOPERUSUA
         JOIN CRMGRUSU GRUUSU ON GRUUSU.NNUMEUSUA = USU.NNUMEUSUA
         JOIN CRMGRUPO GRUPO_ORIG ON GRUPO_ORIG.NNUMEGRUPO = GRUUSU.NNUMEGRUPO
         JOIN CRMGRRES GRRES ON CRMATEND.NNUMECLATE = GRRES.NNUMECLATE
         JOIN CRMGRUPO GRUPO_DEST ON GRUPO_DEST.NNUMEGRUPO = GRRES.NNUMEGRUPO
         LEFT JOIN CRMCLATE ON CRMATEND.NNUMECLATE = CRMCLATE.NNUMECLATE
         LEFT JOIN CRMMENSA ON CRMATEND.NNUMEATEND = CRMMENSA.NNUMEATEND
         LEFT JOIN SEGUSUA USU_MSG ON CRMMENSA.NORIGUSUA = USU_MSG.NNUMEUSUA
    WHERE CRMPROT.DDATAPROT BETWEEN ? AND ?
      AND GRUPO_ORIG.CDESCGRUPO = 'ATENDIMENTO'
      AND USU.CNOMEUSUA = ?
      AND GRUPO_DEST.CDESCGRUPO = ?
      AND USU_MSG.CNOMEUSUA = ?";

// Se houver protocolos específicos, adicionar filtro
if (!empty($protocolosEspecificos)) {
    $sql .= " AND CRMPROT.CCODIPROT IN ($placeholders)";
}

$sql .= " ORDER BY CRMMENSA.DDATAMENSA DESC";

// Preparar e executar a consulta
$stmt = $conn->prepare($sql);

// Preparar parâmetros
$params = [$dataInicio, $dataFim, $operador, $grupoDestino, $operador];
$tipos = "sssss";

// Adicionar protocolos específicos, se houver
if (!empty($protocolosEspecificos)) {
    $tipos .= str_repeat('s', count($protocolosEspecificos));
    $params = array_merge($params, $protocolosEspecificos);
}

$stmt->bind_param($tipos, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Detalhes dos Protocolos - Smile Saúde</title>
<style>
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
    }

    body {
        font-family: "Segoe UI", Arial, sans-serif;
        background: var(--background-light);
        margin: 0;
        padding: 20px;
        color: var(--text-dark);
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        background: var(--background-white);
        padding: 25px 30px;
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-medium);
    }

    h1, h2 {
        color: var(--primary-dark);
    }

    h1 {
        text-align: center;
        margin-bottom: 10px;
    }

    .info-box {
        background-color: #f8f9fa;
        border-left: 4px solid var(--primary-color);
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 0 4px 4px 0;
    }

    .info-box p {
        margin: 5px 0;
    }

    .info-box strong {
        color: var(--primary-dark);
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
        font-size: 14px;
    }

    th, td {
        padding: 10px 12px;
        text-align: left;
        border-bottom: 1px solid #dee2e6;
    }

    th {
        background: var(--primary-color);
        color: #fff;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 500;
    }

    tr:nth-child(even) {
        background-color: var(--background-light);
    }

    tr:hover {
        background-color: var(--primary-light);
        transition: background-color 0.2s;
    }

    .voltar {
        display: inline-block;
        margin-bottom: 15px;
        padding: 8px 15px;
        background-color: var(--primary-color);
        color: white;
        text-decoration: none;
        border-radius: 4px;
        transition: background-color 0.3s;
    }

    .voltar:hover {
        background-color: var(--primary-dark);
        text-decoration: none;
    }

    .ver-historico {
        display: inline-block;
        padding: 5px 10px;
        background-color: var(--primary-color);
        color: white;
        text-decoration: none;
        border-radius: 4px;
        font-size: 12px;
        transition: background-color 0.3s;
    }

    .ver-historico:hover {
        background-color: var(--primary-dark);
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

    .observacao {
        max-width: 400px;
        white-space: normal;
        word-wrap: break-word;
    }

    /* Estilos para o botão Ver Histórico */
    .ver-historico {
        background-color: var(--primary-color);
        color: white;
        border: none;
        border-radius: 4px;
        padding: 5px 10px;
        cursor: pointer;
        font-size: 12px;
        text-decoration: none;
        transition: background-color 0.3s;
    }

    .ver-historico:hover {
        background-color: var(--primary-dark);
        text-decoration: none;
        color: white;
    }
    
    .ver-historico.light {
        background-color: transparent;
        color: var(--primary-color);
        text-decoration: underline;
        padding: 0 5px;
    }
    
    .ver-historico.light:hover {
        background-color: transparent;
        color: var(--primary-dark);
    }

    /* Estilos para o modal */
    #modalHistorico {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.6);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    #modalConteudo {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        width: 80%;
        max-width: 800px;
        max-height: 80vh;
        overflow-y: auto;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        position: relative;
    }

    #fecharModal {
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 24px;
        font-weight: bold;
        color: #666;
        cursor: pointer;
    }

    #fecharModal:hover {
        color: var(--primary-color);
    }
</style>
</head>
<body>
<div class="container">
    <a href="relatorio.php" class="voltar">&larr; Voltar ao Relatório</a>
    <h1>Detalhes dos Protocolos</h1>
    
    <div class="info-box">
        <p><strong>Operador:</strong> <?php echo htmlspecialchars($operador); ?></p>
        <p><strong>Grupo Destino:</strong> <?php echo htmlspecialchars($grupoDestino); ?></p>
        <p><strong>Período:</strong> <?php echo date('d/m/Y', strtotime($dataInicio)); ?> a <?php echo date('d/m/Y', strtotime($dataFim)); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Data Abertura</th>
                <th>Protocolo</th>
                <th>Observação</th>
                <th>Data Mensagem</th>
                <th>Usuário</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if ($result && $result->num_rows > 0): 
                while ($row = $result->fetch_assoc()): 
            ?>
                    <tr>
                        <td><?php echo $row['DATA_ABERTURA'] !== null ? htmlspecialchars($row['DATA_ABERTURA']) : '-'; ?></td>
                        <td><?php echo $row['NUMERO_LOTE'] !== null ? htmlspecialchars($row['NUMERO_LOTE']) : 'N/A'; ?></td>
                        <td class="observacao"><?php echo $row['OBSERVACAO'] !== null ? nl2br(htmlspecialchars($row['OBSERVACAO'])) : '-'; ?></td>
                        <td><?php echo $row['DATA_MENSAGEM'] !== null ? htmlspecialchars($row['DATA_MENSAGEM']) : '-'; ?></td>
                        <td><?php echo $row['USUARIO_MENSAGEM'] !== null ? htmlspecialchars($row['USUARIO_MENSAGEM']) : '-'; ?></td>
                        <td>
                            <button class="ver-historico" data-protocolo="<?php echo htmlspecialchars($row['NUMERO_LOTE']); ?>">
                                Ver Histórico
                            </button>
                        </td>
                    </tr>
                <?php 
                endwhile; 
            else: 
            ?>
                <tr>
                    <td colspan="6" style="text-align:center; color:var(--text-light);">
                        Nenhum registro encontrado.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        © <?php echo date('Y'); ?> - Smile Saúde | Detalhes de Protocolos
    </div>
    
    <!-- Modal para exibir o histórico -->
    <div id="modalHistorico">
        <div id="modalConteudo">
            <span id="fecharModal">&times;</span>
            <h2>Histórico de Mensagens</h2>
            <div id="historicoDetalhes">Carregando...</div>
        </div>
    </div>
</div>

<script>
// Função para abrir o modal
function abrirModal(protocolo) {
    const modal = document.getElementById('modalHistorico');
    const modalConteudo = document.getElementById('historicoDetalhes');
    
    // Exibir o modal
    modal.style.display = 'flex';
    
    // Carregar o histórico via AJAX
    fetch(`mensagens_protocolo.php?protocolo=${encodeURIComponent(protocolo)}`)
        .then(response => response.text())
        .then(html => {
            modalConteudo.innerHTML = html || '<p>Nenhuma mensagem encontrada para este protocolo.</p>';
        })
        .catch(error => {
            console.error('Erro ao carregar o histórico:', error);
            modalConteudo.innerHTML = '<p>Erro ao carregar o histórico. Por favor, tente novamente.</p>';
        });
}

// Fechar o modal quando clicar no X
document.getElementById('fecharModal').addEventListener('click', function() {
    document.getElementById('modalHistorico').style.display = 'none';
});

// Fechar o modal quando clicar fora do conteúdo
window.addEventListener('click', function(event) {
    const modal = document.getElementById('modalHistorico');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
});

// Adicionar event listeners para os botões de histórico
document.querySelectorAll('.ver-historico').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        const protocolo = this.getAttribute('data-protocolo');
        if (protocolo) {
            abrirModal(protocolo);
        }
    });
});
</script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
