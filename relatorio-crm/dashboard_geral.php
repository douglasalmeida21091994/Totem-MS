<?php
// Incluir configurações de conexão
require_once 'config.php';

// Verificar se a conexão foi estabelecida corretamente
if (mysqli_connect_errno()) {
    die("Erro na conexão: " . mysqli_connect_error());
}

// Definir datas padrão (últimos 30 dias)
$dataFim = date('Y-m-d');
$dataInicio = date('Y-m-d', strtotime('-30 days'));

// Verificar se há filtros de data
if (!empty($_GET['data_inicio'])) {
    $dataInicio = $_GET['data_inicio'];
}
if (!empty($_GET['data_fim'])) {
    $dataFim = $_GET['data_fim'];
}

// Consulta para obter estatísticas gerais
$sqlEstatisticas = "
    SELECT 
        COUNT(DISTINCT P.NNUMEPROT) as total_protocolos,
        COUNT(DISTINCT P.NOPERUSUA) as total_operadores,
        COUNT(DISTINCT GD.NNUMEGRUPO) as total_grupos
    FROM CRMPROT P
    LEFT JOIN CRMATEND A ON P.NNUMEPROT = A.NNUMEPROT
    LEFT JOIN CRMGRRES GR ON A.NNUMECLATE = GR.NNUMECLATE
    LEFT JOIN CRMGRUPO GD ON GD.NNUMEGRUPO = GR.NNUMEGRUPO
    WHERE P.DDATAPROT BETWEEN ? AND ?
";

$stmt = $conn->prepare($sqlEstatisticas);
$stmt->bind_param('ss', $dataInicio, $dataFim);
$stmt->execute();
$resultEstatisticas = $stmt->get_result()->fetch_assoc();

// Consulta para protocolos por operador
$sqlOperadores = "
    SELECT 
        U.CNOMEUSUA as operador,
        COUNT(DISTINCT P.NNUMEPROT) as total_protocolos
    FROM CRMPROT P
    JOIN SEGUSUA U ON U.NNUMEUSUA = P.NOPERUSUA
    LEFT JOIN CRMATEND A ON P.NNUMEPROT = A.NNUMEPROT
    WHERE P.DDATAPROT BETWEEN ? AND ?
    GROUP BY U.CNOMEUSUA
    ORDER BY total_protocolos DESC
    LIMIT 10
";

$stmt = $conn->prepare($sqlOperadores);
$stmt->bind_param('ss', $dataInicio, $dataFim);
$stmt->execute();
$resultOperadores = $stmt->get_result();

// Consulta para protocolos por grupo de destino
$sqlGrupos = "
    SELECT 
        COALESCE(GD.CDESCGRUPO, 'NÃO DEFINIDO') as grupo_destino,
        COUNT(DISTINCT P.NNUMEPROT) as total_protocolos
    FROM CRMPROT P
    LEFT JOIN CRMATEND A ON P.NNUMEPROT = A.NNUMEPROT
    LEFT JOIN CRMGRRES GR ON A.NNUMECLATE = GR.NNUMECLATE
    LEFT JOIN CRMGRUPO GD ON GD.NNUMEGRUPO = GR.NNUMEGRUPO
    WHERE P.DDATAPROT BETWEEN ? AND ?
    GROUP BY GD.CDESCGRUPO
    ORDER BY total_protocolos DESC
";

$stmt = $conn->prepare($sqlGrupos);
$stmt->bind_param('ss', $dataInicio, $dataFim);
$stmt->execute();
$resultGrupos = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Geral - Smile Saúde</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js">
    <link href="https://cdn.jsdelivr.net/npm/eva-icons@1.1.3/style/eva-icons.min.css" rel="stylesheet">
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
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        h1 {
            color: var(--primary-dark);
            margin: 0;
        }

        .card {
            background: var(--background-white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-medium);
            padding: 20px;
            margin-bottom: 30px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .card-title {
            margin: 0;
            color: var(--primary-dark);
            font-size: 1.2em;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            text-align: center;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(108, 92, 231, 0.1);
        }

        .stat-card.clickable:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 15px 30px rgba(108, 92, 231, 0.2);
            background: white;
        }

        .stat-icon {
            font-size: 48px;
            margin-bottom: 15px;
            display: inline-block;
            background: linear-gradient(135deg, #6C5CE7, #A29BFE);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            color: #6C5CE7; /* Fallback para navegadores que não suportam background-clip: text */
            transform: perspective(500px) rotateX(0deg) rotateY(0deg);
            transition: transform 0.5s ease;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .stat-card:hover .stat-icon {
            transform: perspective(500px) rotateX(5deg) rotateY(5deg);
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: perspective(500px) rotateX(5deg) rotateY(5deg) translateY(0); }
            50% { transform: perspective(500px) rotateX(5deg) rotateY(5deg) translateY(-10px); }
        }

        .stat-value {
            font-size: 2.2em;
            font-weight: 700;
            color: #2d3436;
            margin: 10px 0 5px;
            position: relative;
            display: inline-block;
        }

        .stat-value::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 40px;
            height: 3px;
            background: linear-gradient(to right, #6C5CE7, #A29BFE);
            border-radius: 3px;
        }

        /* Estilos do Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            overflow: auto;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 800px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .modal-header h2 {
            margin: 0;
            color: var(--primary-dark);
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
        }

        .modal-list table {
            width: 100%;
            border-collapse: collapse;
        }

        .modal-list th, 
        .modal-list td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .modal-list th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .modal-list tr:hover {
            background-color: #f5f5f5;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            color: var(--primary-color);
            margin: 10px 0;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 0.9em;
        }

        .btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background: var(--primary-dark);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--text-dark);
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
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
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--text-dark);
        }

        .filter-group input[type="date"] {
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }

        .btn-filter {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-filter:hover {
            background: var(--primary-dark);
        }

        /* Estilos para os cards nos modais */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 10px;
        }

        .card {
            background: var(--background-white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-medium);
            padding: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .card-title {
            margin: 0;
            color: var(--text-dark);
            font-size: 1.1rem;
            font-weight: 600;
        }

        .card-badge {
            background: var(--primary-light);
            color: var(--primary-dark);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .card-body {
            margin-bottom: 10px;
        }

        .card-text {
            color: var(--text-light);
            margin: 5px 0;
            font-size: 0.95rem;
        }

        .card-text strong {
            color: var(--text-dark);
            margin-right: 5px;
        }

        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #eee;
            font-size: 0.85rem;
            color: var(--text-light);
        }

        .card-actions {
            display: flex;
            gap: 10px;
        }

        .btn-icon {
            background: none;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            font-size: 1.1rem;
            padding: 5px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }

        .btn-icon:hover {
            background: rgba(108, 92, 231, 0.1);
        }

        .empty-state {
            text-align: center;
            padding: 30px 20px;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .empty-state p {
            margin: 10px 0 0;
        }

        @media (max-width: 768px) {
            .card-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Dashboard Geral - CRM</h1>
            <a href="relatorio.php" class="btn btn-outline">Voltar para Relatórios</a>
        </div>

        <div class="filtro-data">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="data_inicio">Data Início:</label>
                        <input type="date" id="data_inicio" name="data_inicio" value="<?= htmlspecialchars($dataInicio) ?>">
                    </div>
                    <div class="filter-group">
                        <label for="data_fim">Data Fim:</label>
                        <input type="date" id="data_fim" name="data_fim" value="<?= htmlspecialchars($dataFim) ?>">
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="btn-filter">Filtrar</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="stats-container">
            <div class="stat-card clickable" onclick="abrirModal('protocolos')">
                <i class="eva eva-file-text-outline stat-icon"></i>
                <div class="stat-value"><?= number_format($resultEstatisticas['total_protocolos'] ?? 0, 0, ',', '.') ?></div>
                <div class="stat-label">Total de Protocolos</div>
            </div>
            <div class="stat-card clickable" onclick="abrirModal('operadores')">
                <i class="eva eva-people-outline stat-icon"></i>
                <div class="stat-value"><?= number_format($resultEstatisticas['total_operadores'] ?? 0, 0, ',', '.') ?></div>
                <div class="stat-label">Operadores Ativos</div>
            </div>
            <div class="stat-card clickable" onclick="abrirModal('grupos')">
                <i class="eva eva-layers-outline stat-icon"></i>
                <div class="stat-value"><?= number_format($resultEstatisticas['total_grupos'] ?? 0, 0, ',', '.') ?></div>
                <div class="stat-label">Grupos de Destino</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Top 10 Operadores</h2>
            </div>
            <div class="chart-container">
                <canvas id="operadoresChart"></canvas>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Operador</th>
                        <th>Total de Protocolos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $resultOperadores->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['operador']) ?></td>
                        <td><?= number_format($row['total_protocolos'], 0, ',', '.') ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Protocolos por Grupo de Destino</h2>
            </div>
            <div class="chart-container">
                <canvas id="gruposChart"></canvas>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Grupo de Destino</th>
                        <th>Total de Protocolos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $resultGrupos->data_seek(0);
                    while ($row = $resultGrupos->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['grupo_destino']) ?></td>
                        <td>
                            <?= number_format($row['total_protocolos'], 0, ',', '.') ?>
                            <button class="btn-icon" title="Ver operadores" onclick="verOperadores('<?= htmlspecialchars(addslashes($row['grupo_destino'])) ?>')">
                                <i class="eva eva-people-outline"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div id="modalProtocolos" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Detalhes dos Protocolos</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div id="protocolosList" class="modal-list">
                    <p>Carregando protocolos...</p>
                </div>
            </div>
        </div>
    </div>

    <div id="modalOperadores" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Operadores Ativos</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div id="operadoresList" class="modal-list">
                    <p>Carregando operadores...</p>
                </div>
            </div>
        </div>
    </div>

    <div id="modalGrupos" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Grupos de Destino</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div id="gruposList" class="modal-list">
                    <p>Carregando grupos...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Operadores do Grupo -->
    <div id="modalOperadoresGrupo" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h2>Operadores do Grupo: <span id="nomeGrupo"></span></h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div id="operadoresGrupoList" class="modal-list">
                    <p>Carregando operadores...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Dados para os gráficos (serão preenchidos com PHP)
        const operadoresData = {
            labels: [<?php 
                $resultOperadores->data_seek(0);
                $labels = [];
                while($row = $resultOperadores->fetch_assoc()) {
                    $labels[] = '"' . addslashes($row['operador']) . '"';
                }
                echo implode(',', $labels);
            ?>],
            datasets: [{
                label: 'Total de Protocolos',
                data: [<?php 
                    $resultOperadores->data_seek(0);
                    $data = [];
                    while($row = $resultOperadores->fetch_assoc()) {
                        $data[] = $row['total_protocolos'];
                    }
                    echo implode(',', $data);
                ?>],
                backgroundColor: 'rgba(0, 206, 201, 0.7)',
                borderColor: 'rgba(0, 206, 201, 1)',
                borderWidth: 1,
                label: 'Total de Protocolos',
                yAxisID: 'y'
            }]
        };

        const gruposData = {
            labels: [<?php 
                $resultGrupos->data_seek(0);
                $labels = [];
                while($row = $resultGrupos->fetch_assoc()) {
                    $labels[] = '"' . addslashes($row['grupo_destino']) . '"';
                }
                echo implode(',', $labels);
            ?>],
            datasets: [{
                label: 'Protocolos por Grupo',
                data: [<?php 
                    $resultGrupos->data_seek(0);
                    $data = [];
                    while($row = $resultGrupos->fetch_assoc()) {
                        $data[] = $row['total_protocolos'];
                    }
                    echo implode(',', $data);
                ?>],
                backgroundColor: [
                    'rgba(108, 92, 231, 0.7)',
                    'rgba(0, 206, 201, 0.7)',
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 159, 64, 0.7)'
                ],
                borderWidth: 1
            }]
        };

        // Configuração dos gráficos
        document.addEventListener('DOMContentLoaded', function() {
            // Gráfico de operadores
            const operadoresCtx = document.getElementById('operadoresChart').getContext('2d');
            new Chart(operadoresCtx, {
                type: 'bar',
                data: operadoresData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            },
                            type: 'linear',
                            display: true,
                            position: 'left'
                        }
                    }
                }
            });

            // Gráfico de grupos
            const gruposCtx = document.getElementById('gruposChart').getContext('2d');
            new Chart(gruposCtx, {
                type: 'pie',
                data: gruposData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        }
                    }
                }
            });
        });
    </script>
    
    <!-- Script para os Modais -->
    <script>
        // Função para abrir o modal
        function abrirModal(tipo) {
            const modal = document.getElementById(`modal${tipo.charAt(0).toUpperCase() + tipo.slice(1)}`);
            if (modal) {
                modal.style.display = 'block';
                carregarDadosModal(tipo);
            }
        }

        // Função para fechar o modal
        function fecharModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
            }
        }

        // Função para carregar operadores do grupo
        function verOperadores(grupo) {
            const modal = document.getElementById('modalOperadoresGrupo');
            const nomeGrupo = document.getElementById('nomeGrupo');
            const operadoresList = document.getElementById('operadoresGrupoList');
            
            nomeGrupo.textContent = grupo;
            operadoresList.innerHTML = '<p>Carregando operadores...</p>';
            modal.style.display = 'block';
            
            // Fazer requisição para obter os operadores do grupo
            fetch(`ajax_operadores_grupo.php?grupo=${encodeURIComponent(grupo)}`)
                .then(response => response.text())
                .then(html => {
                    operadoresList.innerHTML = html;
                })
                .catch(error => {
                    console.error('Erro ao carregar operadores:', error);
                    operadoresList.innerHTML = '<p>Erro ao carregar operadores. Tente novamente.</p>';
                });
        }

        // Fechar modal ao clicar no X
        document.querySelectorAll('.close').forEach(closeBtn => {
            closeBtn.onclick = function() {
                this.closest('.modal').style.display = 'none';
            };
        });

        // Fechar modal ao clicar fora dele
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        };

        // Função para carregar os dados do modal via AJAX
        function carregarDadosModal(tipo) {
            const container = document.getElementById(`${tipo}List`);
            if (!container) return;

            // Mostrar indicador de carregamento
            container.innerHTML = '<p>Carregando dados...</p>';

            // Obter as datas atuais do formulário
            const dataInicio = document.getElementById('data_inicio').value;
            const dataFim = document.getElementById('data_fim').value;

            // Fazer a requisição AJAX
            fetch(`ajax_${tipo}.php?data_inicio=${encodeURIComponent(dataInicio)}&data_fim=${encodeURIComponent(dataFim)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erro na requisição');
                    }
                    return response.text();
                })
                .then(html => {
                    container.innerHTML = html;
                })
                .catch(error => {
                    console.error('Erro ao carregar os dados:', error);
                    container.innerHTML = '<p>Erro ao carregar os dados. Tente novamente.</p>';
                });
        }
    </script>
    
    <!-- Modal de Detalhes do Operador -->
    <div id="modalDetalhesOperador" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Detalhes do Operador</h2>
                <span class="close" onclick="fecharModalDetalhes()">&times;</span>
            </div>
            <div class="modal-body" id="detalhesOperadorConteudo">
                <p>Carregando detalhes do operador...</p>
            </div>
        </div>
    </div>

    <script>
        // Função para exibir os detalhes do operador
        function exibirDetalhesOperador(nomeOperador, grupo) {
            const modal = document.getElementById('modalDetalhesOperador');
            const conteudo = document.getElementById('detalhesOperadorConteudo');
            
            // Exibe o modal com mensagem de carregamento
            modal.style.display = 'block';
            conteudo.innerHTML = '<p>Carregando detalhes de ' + nomeOperador + '...</p>';
            
            // Obter as datas atuais do formulário
            const dataInicio = document.getElementById('data_inicio').value || '';
            const dataFim = document.getElementById('data_fim').value || '';
            
            // Fazer requisição para obter os detalhes do operador
            fetch(`ajax_detalhes_operador.php?operador=${encodeURIComponent(nomeOperador)}&grupo=${encodeURIComponent(grupo)}&data_inicio=${encodeURIComponent(dataInicio)}&data_fim=${encodeURIComponent(dataFim)}`)
                .then(response => response.text())
                .then(html => {
                    conteudo.innerHTML = html;
                })
                .catch(error => {
                    console.error('Erro ao carregar detalhes do operador:', error);
                    conteudo.innerHTML = '<p>Erro ao carregar os detalhes do operador. Tente novamente.</p>';
                });
        }
        
        // Função para exibir os detalhes do protocolo
        function abrirDetalhesProtocolo(protocolo) {
            const modal = document.createElement('div');
            modal.id = 'modalDetalhesProtocolo';
            modal.className = 'modal';
            modal.style.display = 'block';
            
            modal.innerHTML = `
                <div class="modal-content" style="max-width: 900px; margin: 50px auto;">
                    <div class="modal-header">
                        <h2>Detalhes do Protocolo</h2>
                        <span class="close" onclick="this.closest('.modal').style.display='none';">&times;</span>
                    </div>
                    <div class="modal-body">
                        <p>Carregando detalhes do protocolo ${protocolo}...</p>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Fazer requisição para obter os detalhes do protocolo
            fetch(`ajax_detalhes_protocolo.php?protocolo=${encodeURIComponent(protocolo)}`)
                .then(response => response.text())
                .then(html => {
                    const conteudo = modal.querySelector('.modal-body');
                    if (conteudo) {
                        conteudo.innerHTML = html;
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar detalhes do protocolo:', error);
                    const conteudo = modal.querySelector('.modal-body');
                    if (conteudo) {
                        conteudo.innerHTML = '<p>Erro ao carregar os detalhes do protocolo. Tente novamente.</p>';
                    }
                });
                
            // Fechar ao clicar fora do modal
            modal.onclick = function(event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                    document.body.removeChild(modal);
                }
            };
        }
        
        // Adicionar evento de delegação para os botões de visualização
        document.addEventListener('click', function(event) {
            // Verificar se o clique foi em um botão de visualização
            const btn = event.target.closest('.ver-detalhes');
            if (btn) {
                event.preventDefault();
                const protocolo = btn.getAttribute('data-protocolo');
                if (protocolo) {
                    abrirDetalhesProtocolo(protocolo);
                }
            }
            
            // Fechar modal ao clicar no X
            const closeBtn = event.target.closest('.close');
            if (closeBtn) {
                const modal = closeBtn.closest('.modal');
                if (modal) {
                    modal.style.display = 'none';
                    if (modal.id === 'modalDetalhesProtocolo') {
                        document.body.removeChild(modal);
                    }
                }
            }
        });
        
        // Função para fechar o modal de detalhes
        function fecharModalDetalhes() {
            const modal = document.getElementById('modalDetalhesOperador');
            if (modal) {
                modal.style.display = 'none';
            }
            
            const protocoloModal = document.getElementById('modalDetalhesProtocolo');
            if (protocoloModal) {
                protocoloModal.style.display = 'none';
                document.body.removeChild(protocoloModal);
            }
        }
        
        // Fechar o modal ao clicar fora dele
        window.onclick = function(event) {
            const modal = document.getElementById('modalDetalhesOperador');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        };
    </script>
</body>
</html>
