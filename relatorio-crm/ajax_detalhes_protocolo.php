<?php
require_once 'config.php';

// Verificar se o protocolo foi fornecido
if (!isset($_GET['protocolo'])) {
    die("Protocolo não especificado.");
}

$protocolo = $_GET['protocolo'];

// Consulta para obter os detalhes do protocolo
$sql = "
    SELECT 
        P.NNUMEPROT as id_protocolo,
        P.CCODIPROT as codigo_protocolo,
        DATE_FORMAT(P.DDATAPROT, '%d/%m/%Y %H:%i') as data_hora,
        U.CNOMEUSUA as operador,
        GD.CDESCGRUPO as grupo_destino,
        C.CDESCCLATE as classificacao,
        M.CTEXTMENSA as mensagem,
        DATE_FORMAT(M.DDATAMENSA, '%d/%m/%Y %H:%i:%s') as data_mensagem,
        (SELECT CNOMEUSUA FROM SEGUSUA WHERE NNUMEUSUA = M.NORIGUSUA) as usuario_origem,
        (SELECT CNOMEUSUA FROM SEGUSUA WHERE NNUMEUSUA = M.NDESTUSUA) as usuario_destino
    FROM CRMPROT P
    LEFT JOIN SEGUSUA U ON P.NOPERUSUA = U.NNUMEUSUA
    LEFT JOIN CRMATEND A ON P.NNUMEPROT = A.NNUMEPROT
    LEFT JOIN CRMGRRES GR ON A.NNUMECLATE = GR.NNUMECLATE
    LEFT JOIN CRMGRUPO GD ON GR.NNUMEGRUPO = GD.NNUMEGRUPO
    LEFT JOIN CRMCLATE C ON A.NNUMECLATE = C.NNUMECLATE
    LEFT JOIN CRMMENSA M ON A.NNUMEATEND = M.NNUMEATEND
    WHERE P.CCODIPROT = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $protocolo);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $protocolo = $result->fetch_assoc();
    
    // Estilo para o modal
    echo '<style>
    .detalhes-protocolo {
        max-width: 800px;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        min-height: 100%;
    }
    .detalhes-header {
        border-bottom: 2px solid #6C5CE7;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
    .detalhes-header h2 {
        color: #6C5CE7;
        margin: 0;
    }
    .detalhes-info {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-bottom: 20px;
    }
    .detalhes-info p {
        margin: 5px 0;
    }
    .detalhes-info strong {
        color: #6C5CE7;
    }
    .mensagens {
        margin-top: 20px;
        flex-grow: 1;
    }
    .mensagem {
        background-color: #f8f9fa;
        border-left: 4px solid #6C5CE7;
        padding: 10px 15px;
        margin-bottom: 10px;
        border-radius: 0 4px 4px 0;
    }
    .mensagem-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
        font-size: 0.9em;
        color: #666;
    }
    .mensagem-conteudo {
        white-space: pre-line;
    }
    .modal-footer {
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid #eee;
        text-align: center;
    }
    .btn-close {
        background-color: #6C5CE7;
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        transition: background-color 0.3s;
    }
    .btn-close:hover {
        background-color: #5F4DD0;
    }
    </style>';
    
    // Conteúdo do modal
    echo '<div class="detalhes-protocolo">';
    echo '  <div class="detalhes-header">';
    echo '    <h2>Protocolo #' . htmlspecialchars($protocolo['codigo_protocolo']) . '</h2>';
    echo '  </div>';
    
    echo '  <div class="detalhes-info">';
    echo '    <div>';
    echo '      <p><strong>Data/Hora:</strong> ' . htmlspecialchars($protocolo['data_hora']) . '</p>';
    echo '      <p><strong>Operador:</strong> ' . htmlspecialchars($protocolo['operador'] ?? 'N/A') . '</p>';
    echo '    </div>';
    echo '    <div>';
    echo '      <p><strong>Grupo de Destino:</strong> ' . htmlspecialchars($protocolo['grupo_destino'] ?? 'N/A') . '</p>';
    echo '      <p><strong>Classificação:</strong> ' . htmlspecialchars($protocolo['classificacao'] ?? 'N/A') . '</p>';
    echo '    </div>';
    echo '  </div>';
    
    // Se houver mensagem, exibe
    if (!empty($protocolo['mensagem'])) {
        echo '<div class="mensagens">';
        echo '  <h3>Mensagens</h3>';
        echo '  <div class="mensagem">';
        echo '    <div class="mensagem-header">';
        echo '      <span>De: ' . htmlspecialchars($protocolo['usuario_origem'] ?? 'Sistema') . '</span>';
        echo '      <span>' . htmlspecialchars($protocolo['data_mensagem']) . '</span>';
        echo '    </div>';
        echo '    <div class="mensagem-conteudo">' . nl2br(htmlspecialchars($protocolo['mensagem'])) . '</div>';
        echo '  </div>';
        echo '</div>'; // fecha mensagens
    }
    
    // Rodapé com botão Fechar
    echo '<div class="modal-footer">';
    echo '  <button class="btn-close" onclick="fecharModalDetalhes()">Fechar</button>';
    echo '</div>';
    
    echo '</div>'; // fecha detalhes-protocolo
} else {
    echo '<p>Nenhum detalhe encontrado para o protocolo informado.</p>';
    echo '<button class="btn-close" onclick="fecharModalDetalhes()">Fechar</button>';
}
?>
