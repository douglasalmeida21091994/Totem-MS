<?php
/**
 * Tela Interna para Atendentes - Clínica Mais Saúde
 * Sistema em PHP 5.4 - 100% em Português Brasileiro
 */

require_once 'config.php';

if (isset($_GET['acao']) && $_GET['acao'] == 'contar') {
    // usa a conexão que existir ($conn ou $mysqli)
    if (isset($conn)) {
        $db = $conn;
    } elseif (isset($mysqli)) {
        $db = $mysqli;
    } else {
        die('Erro: conexão com o banco não encontrada.');
    }

    $sql = "SELECT COUNT(*) AS total FROM atendimentos WHERE status = 'aguardando'";
    $result = mysqli_query($db, $sql);
    $row = mysqli_fetch_assoc($result);
    echo $row['total'];
    exit;
}
// Configurações básicas
session_start();
header('Content-Type: text/html; charset=UTF-8');

// Funções auxiliares PHP 5.4
function obterDataAtual() {
    return date('d/m/Y');
}

function obterHoraAtual() {
    return date('H:i:s');
}

function obterDataHoraCompleta() {
    return date('d/m/Y H:i:s');
}

// Processar requisições AJAX
if (isset($_POST['acao'])) {
    header('Content-Type: application/json; charset=UTF-8');
    
    switch ($_POST['acao']) {
        case 'listar_fila':
            // Simular fila de pacientes (em produção, viria do banco de dados)
            $fila = array();
            
            // Verificar se existe dados na sessão
            if (isset($_SESSION['fila_atendimento'])) {
                $fila = $_SESSION['fila_atendimento'];
            }
            
            // Adicionar alguns pacientes de exemplo se não houver dados
            if (empty($fila)) {
                $fila = array(
                    array(
                        'id' => '1',
                        'nome' => 'Mateus Marques Da Silva',
                        'tipo_atendimento' => 'Informações e Suporte',
                        'prioridade' => 'Preferencial',
                        'data' => obterDataAtual(),
                        'hora' => '17:30:56',
                        'status' => 'aguardando',
                        'timestamp' => time()
                    ),
                    array(
                        'id' => '2',
                        'nome' => 'Maria Silva Santos',
                        'tipo_atendimento' => 'Atendimento Geral',
                        'prioridade' => 'Normal',
                        'data' => obterDataAtual(),
                        'hora' => '17:25:30',
                        'status' => 'aguardando',
                        'timestamp' => time() - 300
                    )
                );
                $_SESSION['fila_atendimento'] = $fila;
            }
            
            echo json_encode(array('sucesso' => true, 'fila' => $fila));
            exit;
            
        case 'marcar_atendido':
            $idPaciente = $_POST['id_paciente'];
            
            if (isset($_SESSION['fila_atendimento'])) {
                foreach ($_SESSION['fila_atendimento'] as $key => $paciente) {
                    if ($paciente['id'] == $idPaciente) {
                        $_SESSION['fila_atendimento'][$key]['status'] = 'atendido';
                        $_SESSION['fila_atendimento'][$key]['atendido_em'] = obterDataHoraCompleta();
                        break;
                    }
                }
            }
            
            echo json_encode(array('sucesso' => true));
            exit;
            
        case 'adicionar_fila':
            $dados = json_decode($_POST['dados'], true);
            
            if (!isset($_SESSION['fila_atendimento'])) {
                $_SESSION['fila_atendimento'] = array();
            }
            
            $novoPaciente = array(
                'id' => uniqid(),
                'nome' => $dados['nome'],
                'tipo_atendimento' => $dados['tipo_atendimento'],
                'prioridade' => $dados['prioridade'],
                'data' => obterDataAtual(),
                'hora' => obterHoraAtual(),
                'status' => 'aguardando',
                'timestamp' => time()
            );
            
            // Compatibilidade PHP 5.4 - adicionar no início da fila
            $filaTemporaria = array($novoPaciente);
            $_SESSION['fila_atendimento'] = array_merge($filaTemporaria, $_SESSION['fila_atendimento']);
            
            echo json_encode(array('sucesso' => true, 'paciente' => $novoPaciente));
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tela Interna - Atendentes | <?php echo CLINICA_NOME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
/* Estilos para os botões */
.button-container {
    display: flex;
    gap: 10px;
    margin-top: 10px;
    flex-wrap: wrap;
}

.acao-botao {
    padding: 8px 12px;
    border: none;
    border-radius: 4px;
    color: white;
    font-weight: 500;
    font-size: 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    flex: 1;
    min-width: 120px;
}

.acao-botao i {
    margin-right: 5px;
}

/* Cores específicas para cada botão */
.iniciar-atendimento {
    background-color: #4CAF50 !important;
}

.chamar-novamente {
    background-color: #2196F3 !important;
}

.cancelar {
    background-color: #f44336 !important;
}

/* Efeito hover */
.acao-botao:not(:disabled):hover {
    opacity: 0.9;
    transform: translateY(-1px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

/* Botão desabilitado */
.acao-botao:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
        :root {
            --primary-color: #6C5CE7;
            --primary-dark: #5F4DD0;
            --primary-light: #A29BFE;
            --secondary-color: #00CEC9;
            --secondary-dark: #00B5B1;
            --accent-color: #FD79A8;
            --success-color: #4CD137;
            --warning-color: #fb0;
            --danger-color: #E84118;
            --text-dark: #2D3436;
            --text-light: #636E72;
            --background-light: #F5F5F5;
            --background-white: #FFFFFF;
            --shadow-light: 0 2px 10px rgba(0, 0, 0, 0.1);
            --shadow-medium: 0 4px 15px rgba(0, 0, 0, 0.15);
            --shadow-large: 0 8px 30px rgba(0, 0, 0, 0.2);
            --border-radius-sm: 5px;
            --border-radius-md: 10px;
            --border-radius-lg: 15px;
            --transition-fast: 0.2s ease;
            --transition-normal: 0.3s ease;
            --transition-slow: 0.5s ease;
        }
        

        body {
            background-color: var(--background-light);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--text-dark);
            margin: 0;
            padding: 0;
        }
        
        .atendentes-container {
            /* max-width: 1200px; */
            margin: 0 auto;
            padding: 20px;
            min-height: 100vh;
        }
        
        .atendentes-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: var(--background-white);
            padding: 2rem;
            border-radius: var(--border-radius-lg);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-medium);
        }
        
        .atendentes-header h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 700;
        }
        
        .atendentes-header p {
            margin: 0.5rem 0 0;
            opacity: 0.9;
            font-size: 1rem;
        }
        
        .refresh-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: var(--background-white);
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius-md);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: var(--transition-normal);
            backdrop-filter: blur(10px);
        }
        
        .refresh-btn:hover, 
        .help-btn:hover,
        .history-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        .help-btn,
        .history-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: var(--background-white);
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius-md);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: var(--transition-normal);
            backdrop-filter: blur(10px);
        }
        
        /* Estilos do Modal de Ajuda */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: var(--border-radius-lg);
            max-width: 800px;
            width: 100%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: var(--shadow-large);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .modal-header h2 {
            margin: 0;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-light);
            transition: color 0.2s;
        }
        
        .close-modal:hover {
            color: var(--danger-color);
        }
        
        /* Estilos para o histórico de atendimentos */
        .history-content {
            margin-top: 20px;
        }
        
        .history-filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--text-dark);
        }
        
        .form-control {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius-sm);
            font-size: 14px;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: var(--border-radius-sm);
            cursor: pointer;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-outline-secondary {
            background-color: transparent;
            border: 1px solid #ddd;
            color: var(--text-dark);
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            border-radius: var(--border-radius-md);
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .history-header {
            display: flex;
            background-color: #f5f5f5;
            font-weight: 600;
            border-bottom: 2px solid #eee;
        }
        
        .history-row {
            display: flex;
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s;
        }
        
        .history-row:hover {
            background-color: #f9f9f9;
        }
        
        .history-cell {
            padding: 12px 15px;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            word-break: break-word;
        }
        
        .history-header .history-cell {
            font-weight: 600;
            background-color: #f5f5f5;
        }
        
        .history-loading,
        .no-results {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-light);
        }
        
        .history-loading i,
        .no-results i {
            font-size: 2rem;
            margin-bottom: 15px;
            color: var(--primary-color);
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge.normal {
            background-color: #e3f2fd;
            color: #1565c0;
        }
        
        .badge.preferencial {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .badge.atendido {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .history-summary {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .summary-item {
            text-align: center;
            padding: 10px 20px;
            background-color: #f9f9f9;
            border-radius: var(--border-radius-md);
            min-width: 200px;
        }
        
        .summary-label {
            display: block;
            font-size: 0.85rem;
            color: var(--text-light);
            margin-bottom: 5px;
        }
        
        .summary-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .help-section {
            margin-bottom: 25px;
        }
        
        .help-section h3 {
            color: var(--primary-dark);
            margin-top: 0;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .help-section p {
            margin: 8px 0;
            line-height: 1.6;
            color: var(--text-dark);
        }
        
        .help-section ul {
            padding-left: 20px;
            margin: 10px 0;
        }
        
        .help-section li {
            margin-bottom: 8px;
            line-height: 1.5;
        }
        
        .status-example {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: 500;
            margin-right: 5px;
        }
        
        .normal { background-color: #e3f2fd; color: #1565c0; }
        .preferencial { background-color: #e8f5e9; color: #2e7d32; }
        .chamado { background-color: #fff8e1; color: #ff8f00; }
        .atendido { background-color: #f5f5f5; color: #616161; text-decoration: line-through; }
        
      .fila-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  justify-content: center;
  align-items: start;
  gap: 2rem;
  margin: 0 auto;
  padding: 0 2rem;
  max-width: 1400px;
  transition: all 0.4s ease;
}

/* Transição suave no reposicionamento */
.paciente-card {
    background: white;
    border-radius: var(--border-radius-md);
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: var(--shadow-light);
    /* Transições específicas - NÃO inclui background-color e border para evitar delay nas cores de alerta */
    transition: opacity 0.4s ease-in-out, transform 0.4s ease-in-out, max-height 0.4s ease-in-out, margin 0.4s ease-in-out;
    border-left: 4px solid var(--primary-color);
    position: relative;
    overflow: hidden;
    opacity: 0;
    transform: translateY(20px);
    max-height: 0;
    margin-bottom: 0;
    padding-top: 0;
    padding-bottom: 0;
    border: none;
}

/* Quando entra */
.paciente-card.entering {
    animation: fadeInUp 0.5s ease-out forwards;
    opacity: 0;
    max-height: 500px; /* Valor maior que o máximo esperado */
    margin-bottom: 1rem;
    padding: 1.5rem;
    border-left: 4px solid var(--primary-color);
}

/* Classe para card visível */
.paciente-card.entered {
    opacity: 1;
    transform: translateY(0);
    max-height: 500px; /* Valor maior que o máximo esperado */
    margin-bottom: 1rem;
    padding: 1.5rem;
    border-left: 4px solid var(--primary-color);
    /* Transições específicas - NÃO inclui background-color e border para evitar delay nas cores de alerta */
    transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out, max-height 0.3s ease-in-out;
}

/* Classe para animação de saída */
.paciente-card.exiting {
    opacity: 0;
    transform: translateY(-20px);
    max-height: 0;
    margin-bottom: 0;
    padding-top: 0;
    padding-bottom: 0;
    border: none;
    overflow: hidden;
    transition: all 0.4s ease-in-out;
}

/* Animação para os cards */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

        .paciente-card.preferencial {
            border-left-color: var(--success-color);
            background: linear-gradient(90deg, rgba(253, 121, 168, 0.05) 0%, rgba(255, 255, 255, 1) 100%);
            box-shadow: 0 4px 15px rgba(253, 121, 168, 0.15);
        }
        
        .paciente-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .paciente-nome {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-dark);
            margin: 0;
            line-height: 1.3;
        }
        
        .prioridade {
            background: var(--primary-color);
            color: var(--background-white);
            padding: 0.4rem 1rem;
            border-radius: var(--border-radius-md);
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .tipo-atendimento {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .tipo-atendimento i {
            color: var(--primary-color);
        }
        
        .dados-atendimento {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
            background: var(--background-light);
            padding: 1rem;
            border-radius: var(--border-radius-md);
        }
        
        .dado-item {
            text-align: center;
        }
        
        .dado-label {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-bottom: 0.25rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .dado-valor {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 1rem;
        }
        
        .acao-botao {
            width: 100%;
            padding: 0.875rem;
            border: none;
            transition: all 0.3s ease;
            border-radius: var(--border-radius-md);
            background: var(--primary-color);
            color: var(--background-white);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition-fast);
        }
        
        .acao-botao[disabled],
        .acao-botao:disabled,
        .acao-botao.atendido {
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        /* Estilo específico para o botão de chamar novamente quando estiver desabilitado */
        .acao-botao.chamar-novamente:disabled {
            background-color: #90caf9 !important;
        }
        
        /* Estilo para o contador de tempo */
        .contador-tempo {
            font-weight: bold;
            min-width: 15px;
            display: inline-block;
            text-align: center;
        }
        
        .acao-botao.atendido:hover {
            background: var(--success-color);
            transform: none;
            box-shadow: none;
        }
        
        /* Estilo para o botão Chamar Novamente */
        .acao-botao.chamar-novamente {
            background-color: #2196F3;
            cursor: pointer;
        }
        .acao-botao.chamar-novamente:hover {
            background-color: #42a5f5;
        }
        
        /* Efeito visual para quando o paciente é chamado novamente */
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(33, 150, 243, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(33, 150, 243, 0); }
            100% { box-shadow: 0 0 0 0 rgba(33, 150, 243, 0); }
        }
        
        .paciente-card.chamado-novamente {
            animation: pulse 1s;
            position: relative;
            z-index: 1;
        }
        
        .status-info {
            text-align: center;
            margin-top: 1rem;
            padding: 2rem;
            background: var(--background-white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-light);
            border: 2px dashed var(--primary-light);
        }
        
        .status-info i {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .status-aguardando {
            color: var(--warning-color);
            font-weight: 600;
        }
        
        .status-atendido {
            color: var(--success-color);
            font-weight: 600;
        }
        
        .tempo-espera {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-top: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .tempo-espera i {
            color: var(--primary-color);
        }
        
        @media (max-width: 768px) {
            .fila-container {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .atendentes-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
                padding: 1.5rem;
            }
            
            .atendentes-container {
                padding: 1rem;
            }
            
            .paciente-card {
                padding: 1rem;
            }
        }
        
        /* Animação de loading */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .fa-spin {
            animation: spin 1s linear infinite;
        }
           /* === ALERTAS DE TEMPO DE ESPERA === */

/* 🟡 Alerta AMARELO (>= 5 min e < 10 min) */
.paciente-card.alerta-amarelo {
    border: 2px solid #ffcc00 !important;
    background-color: #fff9e6 !important;
    box-shadow: 0 0 8px rgba(255, 204, 0, 0.25);
    animation: pulsarAmareloSuave 5s ease-in-out infinite;
}

@keyframes pulsarAmareloSuave {
    0% {
        background-color: #fff9e6;
        box-shadow: 0 0 8px rgba(255, 204, 0, 0.25);
        filter: brightness(1);
    }
    50% {
        background-color: #fff7cc;
        box-shadow: 0 0 16px rgba(255, 204, 0, 0.35);
        filter: brightness(1.05);
    }
    100% {
        background-color: #fff9e6;
        box-shadow: 0 0 8px rgba(255, 204, 0, 0.25);
        filter: brightness(1);
    }
}

/* 🔴 Alerta VERMELHO (>= 10 min) */
.paciente-card.alerta-vermelho {
    border: 2px solid #ff4d4d !important;
    background-color: #ffeaea !important;
    box-shadow: 0 0 10px rgba(255, 0, 0, 0.3);
    animation: pulsarVermelhoSuave 5s ease-in-out infinite;
}

@keyframes pulsarVermelhoSuave {
    0% {
        background-color: #ffeaea;
        box-shadow: 0 0 10px rgba(255, 0, 0, 0.3);
        filter: brightness(1);
    }
    50% {
        background-color: #ffdcdc;
        box-shadow: 0 0 20px rgba(255, 0, 0, 0.4);
        filter: brightness(1.05);
    }
    100% {
        background-color: #ffeaea;
        box-shadow: 0 0 10px rgba(255, 0, 0, 0.3);
        filter: brightness(1);
    }
}

/* Centraliza e ajusta o card quando há apenas 1 paciente */
.fila-container.single .paciente-card {
    max-width: 500px;
    margin: 0 auto;
}

/* Mantém o comportamento normal com múltiplos cards */
.fila-container.multiple {
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    justify-items: center;
}

.contador-fila {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    background-color: #f4f4f8;
    border: 1px solid #d6d6e0;
    border-radius: 10px;
    padding: 10px 20px;
    font-family: 'Arial', sans-serif;
    font-size: 18px;
    color: #333;
    margin: 20px auto;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.contador-label {
    font-weight: 600;
}

.contador-numero {
    font-weight: bold;
    color: #6a5acd;
    font-size: 22px;
}

/* Alinha o nome do paciente e o selo de prioridade na mesma linha, estável */
.card-header {
    display: flex;
    align-items: baseline; /* garante alinhamento pela linha de base do texto */
    justify-content: space-between;
    margin-bottom: 12px;
    min-height: 28px; /* mantém altura uniforme mesmo em nomes curtos */
}

/* Nome do paciente */
.paciente-nome {
    font-size: 1.05rem;
    font-weight: 600;
    color: #222;
    margin: 0;
    flex: 1;
    line-height: 1.4;
    word-break: break-word;
}

/* Selo de prioridade */
.tag-prioridade {
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
    color: #fff;
    text-transform: uppercase;
    white-space: nowrap;
    margin-left: 10px;
    flex-shrink: 0;
}

.tag-prioridade.preferencial {
    background-color: #28c76f; /* verde elegante */
}

.tag-prioridade.normal {
    background-color: #7367f0; /* roxo coerente com o tema */
}

/* Campo de busca */
.search-container {
    max-width: 600px;
    margin: 20px auto;
    padding: 0 20px;
}

.search-box {
    position: relative;
    width: 100%;
}

.search-box input {
    width: 100%;
    padding: 12px 45px 12px 45px;
    border: 2px solid #e0e0e0;
    border-radius: 25px;
    font-size: 16px;
    transition: all 0.3s ease;
    background-color: #f8f9fa;
    box-sizing: border-box;
}

.search-box input:focus {
    outline: none;
    border-color: #6C5CE7;
    background-color: white;
    box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.1);
}

.search-box .search-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
    font-size: 18px;
    pointer-events: none;
}

.search-box .clear-icon {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
    font-size: 18px;
    cursor: pointer;
    display: none;
    transition: color 0.2s;
}

.search-box .clear-icon:hover {
    color: #6C5CE7;
}

.search-box input:not(:placeholder-shown) ~ .clear-icon {
    display: block;
}

/* Card oculto pela busca */
.paciente-card.hidden-by-search {
    display: none !important;
}

/* Mensagem quando não há resultados */
.no-results-message {
    text-align: center;
    padding: 40px 20px;
    color: #666;
    font-size: 18px;
}

.no-results-message i {
    font-size: 48px;
    color: #ccc;
    margin-bottom: 15px;
    display: block;
}



    </style>
</head>
<body>
    <div class="atendentes-container">
        <div class="atendentes-header">
            <div>
                <h1><i class="fas fa-users"></i> Fila de Atendimento</h1>
                <p>Sistema interno para atendentes</p>
            </div>
            <div class="header-actions">
                <button class="help-btn" id="helpBtn" title="Ajuda">
                    <i class="fas fa-question-circle"></i> Ajuda
                </button>
                <button class="history-btn" id="historyBtn" title="Histórico de Atendimentos">
                    <i class="fas fa-history"></i> Histórico
                </button>
                <button class="refresh-btn" onclick="carregarFila()">
                    <i class="fas fa-sync-alt"></i>
                    Atualizar
                </button>
            </div>
        </div>
        
        <div class="contador-fila">
            <span class="contador-label">Pacientes aguardando:</span>
            <span class="contador-numero" id="contadorAguardando">0</span>
        </div>

        <!-- Campo de busca -->
        <div class="search-container">
            <div class="search-box">
                <i class="fas fa-search search-icon"></i>
                <input 
                    type="text" 
                    id="searchInput" 
                    placeholder="Buscar paciente pelo nome..." 
                    autocomplete="off"
                >
                <i class="fas fa-times clear-icon" id="clearSearch"></i>
            </div>
        </div>

        <div class="fila-container" id="fila-container">
            <!-- Pacientes serão carregados aqui via JavaScript -->
            <div class="status-info">
                <i class="fas fa-spinner fa-spin"></i>
                Carregando fila de atendimento...
            </div>
        </div>
        <div id="loading" style="display: none;">Carregando...</div>
    </div>

    <!-- Modal de Ajuda -->
    <div class="modal-overlay" id="helpModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-question-circle"></i> Guia de Ajuda - Sistema de Fila de Atendimento</h2>
                <button class="close-modal" id="closeHelpModal">&times;</button>
            </div>
            
            <div class="help-section">
                <h3><i class="fas fa-info-circle"></i> Visão Geral</h3>
                <p>Bem-vindo(a) ao Sistema de Fila de Atendimento. Esta ferramenta permite gerenciar a fila de pacientes que aguardam atendimento na clínica.</p>
                <p>O painel exibe os pacientes em tempo real, permitindo que você visualize e gerencie a ordem de atendimento de forma eficiente.</p>
            </div>
            
            <div class="help-section">
                <h3><i class="fas fa-list-ol"></i> Entendendo a Fila</h3>
                <p>A fila é organizada por prioridade e horário de chegada:</p>
                <ul>
                    <li><strong>Pacientes Preferenciais</strong> têm prioridade na fila</li>
                    <li>Dentro de cada prioridade, a ordem é determinada pelo horário de chegada</li>
                    <li>O tempo de espera é calculado automaticamente</li>
                </ul>
            </div>
            
            <div class="help-section">
                <h3><i class="fas fa-tags"></i> Status dos Pacientes</h3>
                <p>Cada paciente pode estar em um dos seguintes status:</p>
                <ul>
                    <li><span class="status-example normal">Aguardando</span> - Paciente aguardando na fila</li>
                    <li><span class="status-example chamado">Chamado</span> - Paciente foi chamado para atendimento</li>
                    <li><span class="status-example atendido">Atendido</span> - Atendimento concluído</li>
                    <li><span class="status-example preferencial">Preferencial</span> - Paciente com atendimento prioritário</li>
                </ul>
            </div>
            
            <div class="help-section">
                <h3><i class="fas fa-bell"></i> Alertas de Tempo</h3>
                <p>O sistema monitora o tempo de espera e destaca os pacientes que estão aguardando há mais tempo:</p>
                <ul>
                    <li>Até 5 minutos: Sem destaque</li>
                    <li>5-10 minutos: Borda amarela</li>
                    <li>10-15 minutos: Borda laranja</li>
                    <li>Acima de 15 minutos: Borda vermelha</li>
                </ul>
            </div>
            
            <div class="help-section">
                <h3><i class="fas fa-mouse-pointer"></i> Ações Disponíveis</h3>
                <p>Dependendo do status do paciente, diferentes botões de ação estarão disponíveis:</p>
                <ul>
                    <li><strong>Chamar Paciente</strong> - Inicia o atendimento do próximo paciente</li>
                    <li><strong>Iniciar Atendimento</strong> - Confirma que o atendimento foi iniciado</li>
                    <li><strong>Chamar Novamente</strong> - Chama o paciente mais uma vez</li>
                    <li><strong>Cancelar</strong> - Remove o paciente da fila</li>
                </ul>
            </div>
            
            <div class="help-section">
                <h3><i class="fas fa-sync-alt"></i> Atualizações em Tempo Real</h3>
                <p>O painel é atualizado automaticamente a cada 5 segundos. Você também pode usar o botão <strong>Atualizar</strong> para forçar uma atualização imediata.</p>
            </div>
            
            <div class="help-section">
                <h3><i class="fas fa-search"></i> Pesquisa</h3>
                <p>Use a barra de pesquisa no topo da página para encontrar rapidamente pacientes pelo nome.</p>
            </div>
            
            <div class="help-section">
                <h3><i class="fas fa-headset"></i> Suporte</h3>
                <p>Se precisar de ajuda adicional, entre em contato com o suporte técnico pelo telefone (XX) XXXX-XXXX ou pelo email suporte@clinica.com.br</p>
            </div>
        </div>
    </div>

    <!-- Modal de Histórico -->
    <div class="modal-overlay" id="historyModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-history"></i> Histórico de Atendimentos</h2>
                <button class="close-modal" id="closeHistoryModal">&times;</button>
            </div>
            <div class="history-content">
                <div class="history-filters">
                    <div class="filter-group">
                        <label for="filterDate">Data:</label>
                        <input type="date" id="filterDate" class="form-control">
                    </div>
                    <div class="filter-group">
                        <label for="filterSearch">Pesquisar:</label>
                        <input type="text" id="filterSearch" class="form-control" placeholder="Nome do paciente...">
                    </div>
                    <button id="applyFilters" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                    <button id="clearFilters" class="btn btn-outline-secondary">
                        <i class="fas fa-eraser"></i> Limpar
                    </button>
                </div>
                <div class="history-list" id="historyList">
                    <div class="history-loading">
                        <i class="fas fa-spinner fa-spin"></i> Carregando histórico...
                    </div>
                </div>
                <div class="history-summary">
                    <div class="summary-item">
                        <span class="summary-label">Total de Atendimentos:</span>
                        <span class="summary-value" id="totalAtendimentos">0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
// Variável para controlar a primeira carga e evitar múltiplas chamadas simultâneas
let primeiraCarga = true;
let carregandoFila = false;

function iniciarAtendimento(id) {
    Swal.fire({
        title: 'Iniciar Atendimento',
        text: 'Deseja iniciar o atendimento deste paciente?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4CAF50',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sim, iniciar atendimento',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'ajax/atendimento_ajax.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (this.status === 200) {
                    try {
                        var r = JSON.parse(this.responseText);
                        if (r.sucesso) {
                            // Remove o card do paciente da tela com animação
                            var card = document.querySelector(`.paciente-card[data-id="${id}"]`);
                            if (card) {
                                card.classList.add('exiting');
                                // Remove o card após a animação terminar
                                setTimeout(() => {
                                    card.remove();
                                    // Recarrega a fila para atualizar os contadores
                                    carregarFila();
                                }, 400);
                            }
                            
                            Swal.fire({
                                title: 'Atendimento Iniciado!',
                                text: 'O atendimento foi iniciado com sucesso.',
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire(
                                'Erro!',
                                'Ocorreu um erro: ' + (r.mensagem || 'Erro desconhecido'),
                                'error'
                            );
                        }
                    } catch(e) {
                        console.error('Erro:', e);
                        Swal.fire(
                            'Erro!',
                            'Erro ao processar a resposta do servidor.',
                            'error'
                        );
                    }
                }
            };
            xhr.send('acao=iniciar_atendimento&id_paciente=' + encodeURIComponent(id));
        }
    });
}

function cancelarChamada(id) {
    Swal.fire({
        title: 'Cancelar Chamada',
        text: 'Deseja cancelar a chamada deste paciente?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f44336',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, cancelar',
        cancelButtonText: 'Voltar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            var card = document.querySelector(`.paciente-card[data-id="${id}"]`);
            
            // Mostra o loading enquanto atualiza
            Swal.fire({
                title: 'Aguarde...',
                text: 'Atualizando status do paciente',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'ajax/atendimento_ajax.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                Swal.close();
                if (this.status === 200) {
                    try {
                        var r = JSON.parse(this.responseText);
                        if (r.sucesso) {
                            Swal.fire({
                                title: 'Chamada Cancelada!',
                                text: 'O paciente voltou para a fila de espera.',
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            
                            // Recarrega a fila para garantir que tudo está sincronizado e reordenado
                            carregarFila();
                        } else {
                            Swal.fire(
                                'Erro!',
                                'Ocorreu um erro: ' + (r.mensagem || 'Erro desconhecido'),
                                'error'
                            );
                        }
                    } catch(e) {
                        console.error('Erro:', e);
                        Swal.fire(
                            'Erro!',
                            'Erro ao processar a resposta do servidor: ' + e.message,
                            'error'
                        );
                    }
                } else {
                    Swal.fire(
                        'Erro!',
                        'Erro na conexão com o servidor. Status: ' + this.status,
                        'error'
                    );
                }
            };
            
            xhr.onerror = function() {
                Swal.close();
                Swal.fire(
                    'Erro de Conexão',
                    'Não foi possível conectar ao servidor. Verifique sua conexão.',
                    'error'
                );
            };
            
            // Envia a requisição para cancelar a chamada
            xhr.send('acao=cancelar_chamada&id_paciente=' + encodeURIComponent(id));
        }
    });
}


function carregarFila() {
    // Evita múltiplas chamadas simultâneas
    if (carregandoFila) return;
    
    carregandoFila = true;
    var container = document.getElementById('fila-container');

    try {
        // Exibe o loading somente na primeira carga
        if (primeiraCarga && !container.querySelector('.paciente-card')) {
            container.innerHTML = `
                <div class="status-info" id="loading-message">
                    <i class="fas fa-spinner fa-spin"></i>
                    Carregando fila de atendimento...
                </div>`;
        }

        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'ajax/atendimento_ajax.php?acao=listar_fila&_=' + new Date().getTime(), true);
        xhr.timeout = 10000; // 10 segundos de timeout
        
        xhr.onload = function() {
            try {
                if (this.status === 200) {
                    var resposta = JSON.parse(this.responseText);
                    if (resposta.sucesso) {
                        // Remove o texto de carregamento caso exista
                        var msg = document.getElementById('loading-message');
                        if (msg) msg.remove();

                        // Atualiza o contador de pacientes aguardando
                        var contador = document.getElementById('contadorAguardando');
                        if (contador) {
                            contador.textContent = resposta.fila.filter(p => p.status === 'aguardando').length;
                        }

                        // Atualiza a tabela com os dados recebidos
                        atualizarTabelaFila(resposta.fila);
                        primeiraCarga = false;
                        
                        // Verifica e recria os botões após atualizar a fila
                        setTimeout(function() {
                            verificarERecriarBotoes();
                        }, 200);
                        
                        // Verifica novamente após mais tempo para garantir
                        setTimeout(function() {
                            verificarERecriarBotoes();
                        }, 500);
                    } else {
                        console.error('Erro ao carregar fila:', resposta.mensagem || 'Erro desconhecido');
                        // Não limpa o conteúdo se já houver cartões
                        if (container.children.length === 0) {
                            container.innerHTML = `
                                <div class="status-info">
                                    <i class="fas fa-exclamation-triangle"></i><br>
                                    ${resposta.mensagem || 'Erro ao carregar a fila'}
                                </div>`;
                        }
                    }
                } else {
                    console.error('Erro na requisição:', this.status);
                    // Não limpa o conteúdo se já houver cartões
                    if (container.children.length === 0) {
                        container.innerHTML = `
                            <div class="status-info">
                                <i class="fas fa-exclamation-triangle"></i><br>
                                Erro na conexão com o servidor (${this.status})
                            </div>`;
                    }
                }
            } catch (e) {
                console.error('Erro ao processar resposta:', e);
                // Não limpa o conteúdo se já houver cartões
                if (container.children.length === 0) {
                    container.innerHTML = `
                        <div class="status-info">
                            <i class="fas fa-exclamation-triangle"></i><br>
                            Erro ao processar os dados
                        </div>`;
                }
            } finally {
                carregandoFila = false;
            }
        };

        xhr.onerror = function() {
            console.error('Erro de rede ao carregar fila');
            // Não limpa o conteúdo se já houver cartões
            if (container.children.length === 0) {
                container.innerHTML = `
                    <div class="status-info">
                        <i class="fas fa-exclamation-triangle"></i><br>
                        Erro de conexão. Verifique sua rede.
                    </div>`;
            }
            carregandoFila = false;
        };

        xhr.ontimeout = function() {
            console.error('Tempo limite excedido ao carregar fila');
            // Não limpa o conteúdo se já houver cartões
            if (container.children.length === 0) {
                container.innerHTML = `
                    <div class="status-info">
                        <i class="fas fa-exclamation-triangle"></i><br>
                        Tempo limite excedido. Tentando novamente...
                    </div>`;
            }
            carregandoFila = false;
        };

        xhr.send();
    } catch (e) {
        console.error('Erro ao carregar fila:', e);
        carregandoFila = false;
    }
}


// Configura o intervalo de atualização
setInterval(function() {
    carregarFila();
}, 10000);

setInterval(atualizarTempos, 1000);

// Atualiza imediatamente quando o usuário retorna à aba
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) atualizarTempos();
});




// ------------------------
// Renderiza os cards com animações suaves
function atualizarTabelaFila(fila) {
    var container = document.getElementById('fila-container');
    var agora = Math.floor(Date.now() / 1000);

    // 🔹 Se não houver pacientes, exibe o aviso e encerra
    if (!fila || fila.length === 0) {
        // Só atualiza se não houver aviso ou se o conteúdo for diferente
        if (!container.querySelector('.status-info')) {
            container.innerHTML = `
                <div class="status-info">
                    <i class="fas fa-calendar-check"></i><br>
                    Nenhum paciente aguardando atendimento
                </div>`;
        }
        
        var contador = document.getElementById('contadorAguardando');
        if (contador) contador.textContent = '0';
        return;
    } else {
        // Remove o aviso se existir e houver pacientes
        var avisoVazio = container.querySelector('.status-info');
        if (avisoVazio) {
            container.removeChild(avisoVazio);
        }
    }

    // Ordenar a fila por prioridade e data de criação
    var filaOrdenada = fila.slice().sort(function(a, b) {
        // Primeiro ordena por status (chamado primeiro, depois em atendimento, depois outros)
        var statusOrder = {
            'chamado': 0,
            'em_atendimento': 1,
            'aguardando': 2
        };
        
        var aStatus = statusOrder[a.status] !== undefined ? statusOrder[a.status] : 2;
        var bStatus = statusOrder[b.status] !== undefined ? statusOrder[b.status] : 2;
        
        if (aStatus !== bStatus) {
            return aStatus - bStatus;
        }
        
        // Depois por prioridade (preferencial primeiro)
        if (a.prioridade === 'Preferencial' && b.prioridade !== 'Preferencial') return -1;
        if (a.prioridade !== 'Preferencial' && b.prioridade === 'Preferencial') return 1;
        
        // Por fim, por data de criação (mais antigo primeiro)
        return a.timestamp - b.timestamp;
    });

    // Mapear cartões existentes
    var existingCards = Array.from(container.querySelectorAll('.paciente-card'));
    var existingCardsMap = new Map(existingCards.map(card => [card.getAttribute('data-id'), card]));
    var cardsToRemove = new Set(Array.from(existingCardsMap.keys()));
    
    // Criar ou atualizar cartões na ordem correta
    filaOrdenada.forEach(function(paciente, index) {
        var cardId = paciente.id;
        var card = existingCardsMap.get(cardId);
        var isNewCard = !card;
        
        // Remove da lista de cartões para remoção
        cardsToRemove.delete(cardId);
        
        // Se o cartão existe, verifica se precisa ser atualizado
        if (!isNewCard) {
            // Verifica se o cartão já está no estado correto
            var currentStatus = card.getAttribute('data-status') || 'aguardando';
            
            // Se o status mudou, remove o card para que seja recriado na posição correta
            if (String(currentStatus).toLowerCase() !== String(paciente.status).toLowerCase()) {
                // Remove o card antigo imediatamente (sem animação para evitar duplicação)
                card.remove();
                
                // Marca como novo para ser recriado
                isNewCard = true;
                card = null;
            }
            
            // Se o status não mudou, apenas atualiza o número e o tempo
            if (!isNewCard && String(currentStatus).toLowerCase() === String(paciente.status).toLowerCase()) {
                
                // Atualiza o número da fila
                var numeroFila = card.querySelector('.numero-fila');
                if (numeroFila) {
                    numeroFila.textContent = index + 1;
                }
                
                // Atualiza o tempo de espera
                var tempoElement = card.querySelector('.tempo-espera');
                if (tempoElement) {
                    var tempoEspera = calcularTempoEspera(paciente.timestamp);
                    tempoElement.innerHTML = '<i class="fas fa-clock"></i> Aguardando há ' + tempoEspera;
                }
                
                // Atualiza as classes de alerta baseado no tempo de espera
                var agora = Math.floor(Date.now() / 1000);
                var tempoMinutos = Math.floor((agora - paciente.timestamp) / 60);
                
                if (tempoMinutos >= 10) {
                    card.classList.add('alerta-vermelho');
                    card.classList.remove('alerta-amarelo');
                } else if (tempoMinutos >= 5) {
                    card.classList.add('alerta-amarelo');
                    card.classList.remove('alerta-vermelho');
                } else {
                    card.classList.remove('alerta-vermelho', 'alerta-amarelo');
                }
                
                // Garante que os botões estejam corretos para status 'chamado'
                if (paciente.status.toLowerCase() === 'chamado') {
                    var buttonContainer = card.querySelector('.button-container');
                    
                    // Se não tiver container de botões ou estiver vazio, recria
                    if (!buttonContainer || buttonContainer.children.length === 0) {
                        
                        // Remove container vazio se existir
                        if (buttonContainer) {
                            buttonContainer.remove();
                        }
                        
                        // Cria novo container
                        var newButtonContainer = document.createElement('div');
                        newButtonContainer.className = 'button-container';
                        newButtonContainer.style.marginTop = '10px';
                        newButtonContainer.style.display = 'flex';
                        newButtonContainer.style.gap = '10px';
                        newButtonContainer.style.flexWrap = 'wrap';
                        
                        // Adiciona os botões
                        newButtonContainer.innerHTML = `
                            <button class="acao-botao iniciar-atendimento" onclick="iniciarAtendimento('${paciente.id}')" style="background-color: #4CAF50; flex: 1; min-width: 120px;">
                                <i class="fas fa-user-md"></i> Iniciar Atendimento
                            </button>
                            <button id="chamarNovamente_${paciente.id}" class="acao-botao chamar-novamente" onclick="chamarNovamente('${paciente.id}')" style="background-color: #2196F3; flex: 1; min-width: 120px;" disabled>
                                <i class="fas fa-bell"></i> <span class="contador-tempo">5</span>s
                            </button>
                            <button class="acao-botao cancelar" onclick="cancelarChamada('${paciente.id}')" style="background-color: #f44336; flex: 1; min-width: 120px;">
                                <i class="fas fa-times"></i> Cancelar
                            </button>`;
                        
                        // Adiciona antes do tempo de espera
                        var tempoEl = card.querySelector('.tempo-espera');
                        if (tempoEl) {
                            tempoEl.parentNode.insertBefore(newButtonContainer, tempoEl);
                        } else {
                            card.appendChild(newButtonContainer);
                        }
                    }
                }
                
                return; // Não recria o card
            }

            
            // Se chegou aqui, o status mudou, então vamos recriar o cartão
        }
        
        var tempoEspera = calcularTempoEspera(paciente.timestamp);
        var isPref = paciente.prioridade.toLowerCase() === 'preferencial';
        var isAtendido = paciente.status === 'atendido';
        var tempoMinutos = Math.floor((agora - paciente.timestamp) / 60);
        var alertaClass = '';
        
        if (tempoMinutos >= 10) alertaClass = ' alerta-vermelho';
        else if (tempoMinutos >= 5) alertaClass = ' alerta-amarelo';
        
        // Cria um novo cartão ou reutiliza o existente
        if (isNewCard) {
            card = document.createElement('div');
            // Aplica todas as classes imediatamente na criação
            card.className = 'paciente-card' + (isPref ? ' preferencial' : '') + alertaClass;
            card.setAttribute('data-id', cardId);
            card.setAttribute('data-status', paciente.status);
        } else {
            // Atualiza o status do cartão existente
            card.setAttribute('data-status', paciente.status);
            // Atualiza as classes do cartão
            card.className = 'paciente-card' + (isPref ? ' preferencial' : '') + alertaClass;
        }
        
        // IMPORTANTE: Só recria o HTML do card se for novo ou se o status mudou
        // Cria/atualiza a estrutura do cartão
        card.innerHTML = `
            <div class="card-header" style="position: relative;">
                <div class="numero-fila" style="position: absolute; left: -15px; top: -15px; background-color: #6C5CE7; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">${index + 1}</div>
                <h3 class="paciente-nome" style="margin-left: 25px;">Paciente: ${paciente.nome}</h3>
                <span class="tag-prioridade ${isPref ? 'preferencial' : 'normal'}">${paciente.prioridade.toUpperCase()}</span>
            </div>`;
            
        // Adiciona o status de chamado se necessário
        var existingStatus = card.querySelector('.status-atendimento');
        if (paciente.status === 'chamado') {
            if (!existingStatus) {
                var statusHtml = `
                    <div class="status-atendimento" style="color: #ff9800; font-weight: bold; margin: 5px 0 10px 0; display: block;">
                        <i class="fas fa-bell" style="color: #ff9800; margin-right: 5px;"></i> Chamado para atendimento
                    </div>`;
                // Insere após o cabeçalho do card
                var cardHeader = card.querySelector('.card-header');
                if (cardHeader) {
                    cardHeader.insertAdjacentHTML('afterend', statusHtml);
                } else {
                    card.insertAdjacentHTML('afterbegin', statusHtml);
                }
            }
        } else if (existingStatus) {
            // Remove o status se não for mais 'chamado'
            existingStatus.remove();
        }
        
        // Adiciona o resto do conteúdo do cartão
        card.insertAdjacentHTML('beforeend', `
            <div class="tipo-atendimento"><i class="fas fa-info-circle"></i> ${paciente.tipo_atendimento}</div>
            <div class="dados-atendimento">
                <div><i class="far fa-calendar-alt" style="color:#6C5CE7;margin-right:6px;"></i><strong>Data:</strong> ${paciente.data}</div>
                <div><i class="far fa-clock" style="color:#6C5CE7;margin-right:6px;"></i><strong>Hora:</strong> ${paciente.hora}</div>
            </div>`);
            
        // Remove botões existentes
        var existingButtons = card.querySelector('.button-container');
        if (existingButtons) {
            existingButtons.remove();
        }
        
        // Adiciona os botões de ação com base no status
        var buttonContainer = document.createElement('div');
        buttonContainer.className = 'button-container';
        buttonContainer.style.marginTop = '10px';
        buttonContainer.style.display = 'flex';
        buttonContainer.style.gap = '10px';
        buttonContainer.style.flexWrap = 'wrap';
        
        if (paciente.status === 'chamado') {
            // Botões para status 'chamado'
            buttonContainer.innerHTML = `
                <button class="acao-botao iniciar-atendimento" onclick="iniciarAtendimento('${paciente.id}')" style="background-color: #4CAF50; flex: 1; min-width: 120px;">
                    <i class="fas fa-user-md"></i> Iniciar Atendimento
                </button>
                <button id="chamarNovamente_${paciente.id}" class="acao-botao chamar-novamente" onclick="chamarNovamente('${paciente.id}')" style="background-color: #2196F3; flex: 1; min-width: 120px;" disabled>
                    <i class="fas fa-bell"></i> <span class="contador-tempo">5</span>s
                </button>
                <button class="acao-botao cancelar" onclick="cancelarChamada('${paciente.id}')" style="background-color: #f44336; flex: 1; min-width: 120px;">
                    <i class="fas fa-times"></i> Cancelar
                </button>`;
            
            // Inicia a contagem regressiva para habilitar o botão de chamar novamente
            var contador = 5;
            var botaoId = `chamarNovamente_${paciente.id}`;
            var intervalo = setInterval(function() {
                var botaoChamarNovamente = document.getElementById(botaoId);
                // Verifica se o botão ainda existe no DOM
                if (!botaoChamarNovamente) {
                    clearInterval(intervalo);
                    return;
                }
                
                contador--;
                if (contador <= 0) {
                    clearInterval(intervalo);
                    if (botaoChamarNovamente) {
                        botaoChamarNovamente.innerHTML = '<i class="fas fa-bell"></i> Chamar Novamente';
                        botaoChamarNovamente.disabled = false;
                        botaoChamarNovamente.style.opacity = '1';
                    }
                } else if (botaoChamarNovamente) {
                    var contadorElement = botaoChamarNovamente.querySelector('.contador-tempo');
                    if (contadorElement) {
                        contadorElement.textContent = contador;
                    }
                }
            }, 1000);
            
        } else if (paciente.status === 'atendido') {
            // Botão para status 'atendido'
            buttonContainer.innerHTML = `
                <button class="acao-botao atendido" disabled style="background-color: #9e9e9e; flex: 1; min-width: 100%;">
                    <i class="fas fa-user-check"></i> Em Atendimento
                </button>`;
        } else {
            // Botão para status padrão (aguardando)
            buttonContainer.innerHTML = `
                <button class="acao-botao" onclick="marcarAtendido('${paciente.id}')" style="background-color: #6C5CE7; flex: 1; min-width: 100%;">
                    <i class="fas fa-bell"></i> Chamar Paciente
                </button>`;
        }
        
        // Adiciona o container de botões ao card
        var tempoElement = card.querySelector('.tempo-espera');
        if (tempoElement) {
            tempoElement.parentNode.insertBefore(buttonContainer, tempoElement);
        } else {
            // Se não houver elemento de tempo, adiciona ao final do card
            card.appendChild(buttonContainer);
        }
        
        // Adiciona o tempo de espera
        card.insertAdjacentHTML('beforeend', `
            <div class="tempo-espera" data-timestamp="${paciente.timestamp}" style="margin-top: 10px;">
                <i class="fas ${paciente.status === 'atendido' ? 'fa-check' : 'fa-clock'}"></i>
                ${paciente.status !== 'atendido' ? 'Aguardando há ' + tempoEspera : 'Atendido em ' + (paciente.atendido_em || '')}
            </div>
        `);
        
        // Garante que as classes de alerta estejam aplicadas após criar todo o conteúdo
        if (tempoMinutos >= 10) {
            card.classList.add('alerta-vermelho');
            card.classList.remove('alerta-amarelo');
        } else if (tempoMinutos >= 5) {
            card.classList.add('alerta-amarelo');
            card.classList.remove('alerta-vermelho');
        } else {
            card.classList.remove('alerta-vermelho', 'alerta-amarelo');
        }
        
        // Se for um cartão novo, adiciona ao container na posição correta
        if (isNewCard) {
            // Adiciona a classe 'entering' para animação de entrada
            card.classList.add('entering');
            
            // Encontra a posição correta para inserir o novo cartão
            // Procura o primeiro card que deveria vir DEPOIS deste na ordenação
            var nextCard = null;
            var containerChildren = Array.from(container.children);
            
            for (var i = 0; i < containerChildren.length; i++) {
                var sibling = containerChildren[i];
                var siblingId = sibling.getAttribute('data-id');
                
                // Encontra o índice deste sibling na fila ordenada
                var siblingIndex = filaOrdenada.findIndex(p => p.id === siblingId);
                
                // Se o sibling tem um índice maior que o atual, este é o próximo card
                if (siblingIndex > index) {
                    nextCard = sibling;
                    break;
                }
            }
            
            if (nextCard) {
                container.insertBefore(card, nextCard);
            } else {
                container.appendChild(card);
            }
            
            // Após um pequeno delay, muda para 'entered' para completar a animação
            setTimeout(function() {
                card.classList.remove('entering');
                card.classList.add('entered');
                
                // Reaplica as classes de alerta após a animação para garantir que não foram removidas
                var tempoMinutosCheck = Math.floor((Math.floor(Date.now() / 1000) - paciente.timestamp) / 60);
                if (tempoMinutosCheck >= 10) {
                    card.classList.add('alerta-vermelho');
                    card.classList.remove('alerta-amarelo');
                } else if (tempoMinutosCheck >= 5) {
                    card.classList.add('alerta-amarelo');
                    card.classList.remove('alerta-vermelho');
                }
            }, 50);
            
            var tempoEspera = calcularTempoEspera(paciente.timestamp);
            var isAtendido = paciente.status === 'atendido';
            var tempoMinutos = Math.floor((agora - paciente.timestamp) / 60);
            
            // Atualiza o tempo de espera
            var tempoElement = card.querySelector('.tempo-espera');
            if (tempoElement) {
                tempoElement.innerHTML = `
                    <i class="fas ${isAtendido ? 'fa-check' : 'fa-clock'}"></i>
                    ${!isAtendido ? 'Aguardando há ' + tempoEspera : 'Atendido em ' + (paciente.atendido_em || '')}
                `;
            }
            
            // Atualiza o botão de ação
            var buttonContainer = card.querySelector('.button-container');
            var buttonElement = card.querySelector('.acao-botao:not(.atendido)');
            
            if (paciente.status === 'chamado') {
                // Se o status for 'chamado', exibe os botões de Iniciar Atendimento e Cancelar
                if (!buttonContainer) {
                    buttonContainer = document.createElement('div');
                    buttonContainer.className = 'button-container';
                    buttonContainer.style.marginTop = '10px';
                    buttonContainer.style.display = 'flex';
                    buttonContainer.style.gap = '10px';
                    
                    var lastElement = card.querySelector('.tempo-espera');
                    if (lastElement) {
                        lastElement.after(buttonContainer);
                    } else {
                        card.appendChild(buttonContainer);
                    }
                }
                
                buttonContainer.innerHTML = `
                    <button class="acao-botao iniciar-atendimento" onclick="iniciarAtendimento('${paciente.id}')" style="background-color: #4CAF50; flex: 1; min-width: 120px;">
                        <i class="fas fa-user-md"></i> Iniciar Atendimento
                    </button>
                    <button id="chamarNovamente_${paciente.id}" class="acao-botao chamar-novamente" onclick="chamarNovamente('${paciente.id}')" style="background-color: #2196F3; flex: 1; min-width: 120px;">
                        <i class="fas fa-bell"></i> Chamar Novamente
                    </button>
                    <button class="acao-botao cancelar" onclick="cancelarChamada('${paciente.id}')" style="background-color: #f44336; flex: 1; min-width: 120px;">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                `;
                
                // Remove o botão de chamar se ainda existir
                if (buttonElement) {
                    buttonElement.remove();
                }
                
                // Adiciona o status de chamado
                var statusElement = card.querySelector('.status-atendimento');
                if (!statusElement) {
                    statusElement = document.createElement('div');
                    statusElement.className = 'status-atendimento';
                    // Insere o status logo após o card-header
                    var cardHeader = card.querySelector('.card-header');
                    if (cardHeader) {
                        // Verifica se já existe um status-atendimento após o header
                        var nextSibling = cardHeader.nextElementSibling;
                        if (nextSibling && nextSibling.classList.contains('status-atendimento')) {
                            nextSibling.remove(); // Remove duplicado se existir
                        }
                        // Insere o status imediatamente após o header
                        cardHeader.insertAdjacentElement('afterend', statusElement);
                    } else {
                        // Fallback: adiciona no início do card
                        card.prepend(statusElement);
                    }
                }
                statusElement.innerHTML = '<i class="fas fa-bell" style="color: #ff9800; margin-right: 5px;"></i> Chamado para atendimento';
                statusElement.style.color = '#ff9800';
                statusElement.style.fontWeight = 'bold';
                statusElement.style.margin = '5px 0';
                statusElement.style.display = 'block';
            } else if (isAtendido) {
                if (buttonElement) {
                    buttonElement.outerHTML = '<button class="acao-botao atendido" disabled><i class="fas fa-user-check"></i> Em Atendimento</button>';
                }
            } else if (buttonElement) {
                buttonElement.outerHTML = `<button class="acao-botao" onclick="marcarAtendido('${paciente.id}')"><i class="fas fa-bell"></i> Chamar Paciente</button>`;
            }
            
            // Atualiza classes de alerta (não sobrescreve, apenas adiciona)
            if (paciente.prioridade.toLowerCase() === 'preferencial') {
                card.classList.add('preferencial');
            }
            if (tempoMinutos >= 10) {
                card.classList.add('alerta-vermelho');
                card.classList.remove('alerta-amarelo');
            } else if (tempoMinutos >= 5) {
                card.classList.add('alerta-amarelo');
                card.classList.remove('alerta-vermelho');
            } else {
                card.classList.remove('alerta-vermelho', 'alerta-amarelo');
            }
        }
    });

    // Remove os cards que não estão mais na fila (foram excluídos ou mudaram de status)
    cardsToRemove.forEach(function(cardId) {
        var cardToRemove = existingCardsMap.get(cardId);
        if (cardToRemove) {
            cardToRemove.classList.add('exiting');
            setTimeout(function() {
                cardToRemove.remove();
            }, 400);
        }
    });
    
    // Ajusta o layout baseado no número de cartões
    if (fila.length === 1) {
        container.classList.add('single');
        container.classList.remove('multiple');
    } else {
        container.classList.add('multiple');
        container.classList.remove('single');
    }
    
    // Atualiza o contador de pacientes na fila
    document.getElementById('contadorAguardando').textContent = fila.filter(p => p.status !== 'atendido').length;
}




// ------------------------
// Chamar paciente novamente
// ------------------------
function chamarNovamente(id) {
    var botaoChamarNovamente = document.getElementById(`chamarNovamente_${id}`);
    
    // Se o botão estiver desabilitado (em contagem regressiva), não faz nada
    if (botaoChamarNovamente && botaoChamarNovamente.disabled) {
        return;
    }
    
    // Mostra o modal de confirmação
    Swal.fire({
        title: 'Chamar Paciente Novamente',
        text: 'Deseja chamar este paciente novamente?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sim, chamar novamente',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Desabilita o botão e inicia a contagem regressiva
            if (botaoChamarNovamente) {
                botaoChamarNovamente.innerHTML = '<i class="fas fa-bell"></i> <span class="contador-tempo">5</span>s';
                botaoChamarNovamente.disabled = true;
                botaoChamarNovamente.style.opacity = '0.7';
                
                // Inicia a contagem regressiva
                var contador = 5;
                var botaoId = `chamarNovamente_${id}`;
                var intervalo = setInterval(function() {
                    var botaoChamarNovamente = document.getElementById(botaoId);
                    // Verifica se o botão ainda existe no DOM
                    if (!botaoChamarNovamente) {
                        clearInterval(intervalo);
                        return;
                    }
                    
                    contador--;
                    if (contador <= 0) {
                        clearInterval(intervalo);
                        if (botaoChamarNovamente) {
                            botaoChamarNovamente.innerHTML = '<i class="fas fa-bell"></i> Chamar Novamente';
                            botaoChamarNovamente.disabled = false;
                            botaoChamarNovamente.style.opacity = '1';
                        }
                    } else if (botaoChamarNovamente) {
                        var contadorElement = botaoChamarNovamente.querySelector('.contador-tempo');
                        if (contadorElement) {
                            contadorElement.textContent = contador;
                        }
                    } else {
                        clearInterval(intervalo);
                    }
                }, 1000);
            }
            
            // Envia a requisição para o servidor
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'ajax/atendimento_ajax.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (this.status === 200) {
                    try {
                        var r = JSON.parse(this.responseText);
                        if (r.sucesso) {
                            // Feedback visual
                            var card = document.querySelector(`.paciente-card[data-id="${id}"]`);
                            if (card) {
                                card.classList.add('chamado-novamente');
                                setTimeout(() => card.classList.remove('chamado-novamente'), 1000);
                            }
                            
                            // Mostra mensagem de sucesso
                            Swal.fire({
                                title: 'Paciente Chamado!',
                                text: 'O paciente foi chamado novamente para atendimento.',
                                icon: 'success',
                                showConfirmButton: false,
                                showCloseButton: false,
                                timer: 3000,
                                timerProgressBar: true
                            });
                        } else {
                            // Se houver erro, reativa o botão
                            if (botaoChamarNovamente) {
                                botaoChamarNovamente.disabled = false;
                                botaoChamarNovamente.style.opacity = '1';
                            }
                            
                            Swal.fire(
                                'Erro!',
                                'Ocorreu um erro ao chamar o paciente novamente: ' + (r.mensagem || 'Erro desconhecido'),
                                'error'
                            );
                        }
                    } catch(e) {
                        console.error('Erro:', e);
                        // Se houver erro, reativa o botão
                        if (botaoChamarNovamente) {
                            botaoChamarNovamente.disabled = false;
                            botaoChamarNovamente.style.opacity = '1';
                        }
                        
                        Swal.fire(
                            'Erro!',
                            'Erro ao processar a resposta do servidor.',
                            'error'
                        );
                    }
                }
            };
            
            xhr.onerror = function() {
                // Se houver erro de conexão, reativa o botão
                if (botaoChamarNovamente) {
                    botaoChamarNovamente.disabled = false;
                    botaoChamarNovamente.style.opacity = '1';
                }
                
                Swal.fire(
                    'Erro de Conexão',
                    'Não foi possível conectar ao servidor. Verifique sua conexão.',
                    'error'
                );
            };
            
            xhr.send('acao=chamar_novamente&id_paciente=' + encodeURIComponent(id));
        }
    });
}

// ------------------------
// Marcar paciente como atendido
// ------------------------
function marcarAtendido(id) {
    Swal.fire({
        title: 'Chamar Próximo Paciente',
        text: 'Deseja chamar este paciente para atendimento?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sim, chamar paciente',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostra o loading enquanto atualiza
            Swal.fire({
                title: 'Aguarde...',
                text: 'Atualizando status do paciente',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Envia a requisição para atualizar o status
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'ajax/atendimento_ajax.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                Swal.close();
                if (this.status === 200) {
                    try {
                        var r = JSON.parse(this.responseText);
                        if (r.sucesso) {
                            // Atualiza a interface para refletir a mudança
                            var card = document.querySelector(`.paciente-card[data-id="${id}"]`);
                            if (card) {
                                // Atualiza o status do card
                                card.setAttribute('data-status', 'chamado');
                                
                                // Remove botões existentes
                                var existingButtons = card.querySelector('.button-container');
                                if (existingButtons) {
                                    existingButtons.remove();
                                }
                                
                                // Cria o container para os botões
                                var buttonContainer = document.createElement('div');
                                buttonContainer.className = 'button-container';
                                buttonContainer.style.marginTop = '10px';
                                buttonContainer.style.display = 'flex';
                                buttonContainer.style.gap = '10px';
                                buttonContainer.style.flexWrap = 'wrap';
                                
                                // Adiciona os botões de iniciar, chamar novamente e cancelar
                                buttonContainer.innerHTML = `
                                    <button class="acao-botao iniciar-atendimento" onclick="iniciarAtendimento('${id}')" style="background-color: #4CAF50; flex: 1; min-width: 120px;">
                                        <i class="fas fa-user-md"></i> Iniciar Atendimento
                                    </button>
                                    <button id="chamarNovamente_${id}" class="acao-botao chamar-novamente" onclick="chamarNovamente('${id}')" style="background-color: #2196F3; flex: 1; min-width: 120px;" disabled>
                                        <i class="fas fa-bell"></i> <span class="contador-tempo">5</span>s
                                    </button>
                                    <button class="acao-botao cancelar" onclick="cancelarChamada('${id}')" style="background-color: #f44336; flex: 1; min-width: 120px;">
                                        <i class="fas fa-times"></i> Cancelar
                                    </button>
                                `;
                                
                                // Adiciona o container de botões ao card
                                var lastElement = card.querySelector('.tempo-espera');
                                if (lastElement) {
                                    lastElement.parentNode.insertBefore(buttonContainer, lastElement);
                                } else {
                                    card.appendChild(buttonContainer);
                                }
                                
                                // Inicia a contagem regressiva para habilitar o botão
                                var contador = 5;
                                var botaoId = `chamarNovamente_${id}`;
                                var intervalo = setInterval(function() {
                                    var botaoChamarNovamente = document.getElementById(botaoId);
                                    // Verifica se o botão ainda existe no DOM
                                    if (!botaoChamarNovamente) {
                                        clearInterval(intervalo);
                                        return;
                                    }
                                    
                                    contador--;
                                    if (contador <= 0) {
                                        clearInterval(intervalo);
                                        botaoChamarNovamente.innerHTML = '<i class="fas fa-bell"></i> Chamar Novamente';
                                        botaoChamarNovamente.disabled = false;
                                        botaoChamarNovamente.style.opacity = '1';
                                    } else {
                                        var contadorElement = botaoChamarNovamente.querySelector('.contador-tempo');
                                        if (contadorElement) {
                                            contadorElement.textContent = contador;
                                        }
                                    }
                                }, 1000);
                                
                                // Atualiza o status no card para refletir que foi chamado
                                var statusElement = card.querySelector('.status-atendimento');
                                if (!statusElement) {
                                    statusElement = document.createElement('div');
                                    statusElement.className = 'status-atendimento';
                                    // Insere o status logo após o card-header e antes do próximo irmão
                                    var cardHeader = card.querySelector('.card-header');
                                    if (cardHeader && cardHeader.nextSibling) {
                                        cardHeader.parentNode.insertBefore(statusElement, cardHeader.nextSibling);
                                    } else if (cardHeader) {
                                        cardHeader.after(statusElement);
                                    } else {
                                        card.prepend(statusElement);
                                    }
                                }
                                statusElement.innerHTML = '<i class="fas fa-bell" style="color: #ff9800; margin-right: 5px;"></i> Chamado para atendimento';
                                statusElement.style.color = '#ff9800';
                                statusElement.style.fontWeight = 'bold';
                                statusElement.style.margin = '5px 0';
                                // Garante que o elemento de status fique visível e na posição correta
                                statusElement.style.display = 'block';
                            }
                            
                            Swal.fire({
                                title: 'Paciente Chamado!',
                                text: 'O paciente foi chamado para atendimento.',
                                icon: 'success',
                                showConfirmButton: false,
                                showCloseButton: false,
                                allowOutsideClick: false,
                                timer: 3000,
                                timerProgressBar: true
                            });
                        } else {
                            Swal.fire('Erro', r.mensagem || 'Erro ao chamar paciente', 'error');
                        }
                    } catch (e) {
                        console.error('Erro ao processar resposta:', e);
                        Swal.fire('Erro', 'Ocorreu um erro ao processar a resposta do servidor', 'error');
                    }
                } else {
                    Swal.fire('Erro', 'Erro na comunicação com o servidor', 'error');
                }
            };
            
            xhr.onerror = function() {
                Swal.close();
                Swal.fire('Erro', 'Erro na comunicação com o servidor', 'error');
            };
            
            xhr.send(`acao=chamar_paciente&id_paciente=${id}`);
        }
    });
}

// Função para recriar o botão 'Chamar Paciente' para pacientes em status 'chamado'
function recriarBotoesChamarPaciente() {
    var cards = document.querySelectorAll('.paciente-card[data-status="chamado"]');
    
    cards.forEach(function(card) {
        var id = card.getAttribute('data-id');
        var buttonContainer = card.querySelector('.button-container');
        
        // Se não encontrar o container de botões, cria um
        if (!buttonContainer) {
            buttonContainer = document.createElement('div');
            buttonContainer.className = 'button-container';
            card.appendChild(buttonContainer);
        }
        
        // Verifica se o botão já existe
        var botaoExistente = buttonContainer.querySelector('.btn-chamar-novamente');
        if (!botaoExistente) {
            var botaoChamarNovamente = document.createElement('button');
            botaoChamarNovamente.className = 'btn btn-warning btn-chamar-novamente';
            botaoChamarNovamente.innerHTML = '<i class="fas fa-bell"></i> Chamar Novamente';
            botaoChamarNovamente.onclick = function() { chamarNovamente(id); };
            buttonContainer.appendChild(botaoChamarNovamente);
        }
    });
}

// Função para verificar e recriar botões quando necessário
function verificarERecriarBotoes() {
    // Encontra todos os cards de pacientes
    var cards = document.querySelectorAll('.paciente-card');
    
    cards.forEach(function(card) {
        var status = card.getAttribute('data-status');
        var id = card.getAttribute('data-id');
        
        // Se for um paciente com status 'chamado', verifica os botões
        if (status === 'chamado') {
            var buttonContainer = card.querySelector('.button-container');
            
            // Se não encontrar o container de botões ou estiver vazio, recria
            if (!buttonContainer || buttonContainer.children.length === 0) {
                
                // Remove o container existente se estiver vazio
                if (buttonContainer) {
                    buttonContainer.remove();
                }
                
                // Cria um novo container de botões
                var newButtonContainer = document.createElement('div');
                newButtonContainer.className = 'button-container';
                newButtonContainer.style.marginTop = '10px';
                newButtonContainer.style.display = 'flex';
                newButtonContainer.style.gap = '10px';
                newButtonContainer.style.flexWrap = 'wrap';
                
                // Adiciona os botões
                newButtonContainer.innerHTML = `
                    <button class="acao-botao iniciar-atendimento" onclick="iniciarAtendimento('${id}')" style="background-color: #4CAF50; flex: 1; min-width: 120px;">
                        <i class="fas fa-user-md"></i> Iniciar Atendimento
                    </button>
                    <button id="chamarNovamente_${id}" class="acao-botao chamar-novamente" onclick="chamarNovamente('${id}')" style="background-color: #2196F3; flex: 1; min-width: 120px;">
                        <i class="fas fa-bell"></i> Chamar Novamente
                    </button>
                    <button class="acao-botao cancelar" onclick="cancelarChamada('${id}')" style="background-color: #f44336; flex: 1; min-width: 120px;">
                        <i class="fas fa-times"></i> Cancelar
                    </button>`;
                
                // Adiciona o container ao card
                var tempoElement = card.querySelector('.tempo-espera');
                if (tempoElement) {
                    tempoElement.parentNode.insertBefore(newButtonContainer, tempoElement);
                } else {
                    card.appendChild(newButtonContainer);
                }
            }
        }
    });
}


// Função para atualizar os tempos de espera em tempo real
function atualizarTempos() {
    var agora = Math.floor(Date.now() / 1000);
    
    document.querySelectorAll('.tempo-espera[data-timestamp]').forEach(function(el) {
        var ts = parseInt(el.getAttribute('data-timestamp'));
        if (!ts || isNaN(ts)) return; // Valida se o timestamp é válido
        
        var dif = agora - ts;
        
        // CRÍTICO: Evita valores negativos que causam contagens incorretas
        if (dif < 0) {
            dif = 0;
        }
        
        var min = Math.floor(dif / 60);
        var seg = dif % 60;
        
        // Só atualiza se não estiver atendido
        var cardAtendido = el.closest('.paciente-card[data-status="atendido"]');
        if (!cardAtendido) {
            // Atualiza o texto com a formatação correta
            el.innerHTML = '<i class="fas fa-clock"></i> Aguardando há ' + 
                          min + ' min' + (min !== 1 ? 's' : '') + ' e ' + 
                          seg + ' seg' + (seg !== 1 ? '' : '');
        }
        
        // Atualiza classes de alerta do card pai em tempo real
        var card = el.closest('.paciente-card');
        if (card && !cardAtendido) {
            // Remove todas as classes de alerta primeiro
            card.classList.remove('alerta-vermelho', 'alerta-amarelo');
            
            // Aplica a classe correta baseada no tempo
            if (min >= 10) {
                card.classList.add('alerta-vermelho');
            } else if (min >= 5) {
                card.classList.add('alerta-amarelo');
            }
        }
    });
}


// ============================================
// CORREÇÃO NA FUNÇÃO calcularTempoEspera()
// Substituir a função completa no arquivo atendentes.php
// ============================================

function calcularTempoEspera(ts) {
    // Timestamp já vem correto do servidor (em segundos Unix)
    var agora = Math.floor(Date.now() / 1000);
    var dif = agora - ts;

    // Evita valores negativos
    if (dif < 0) dif = 0;

    var min = Math.floor(dif / 60);
    var seg = dif % 60;

    if (min > 0) {
        return min + ' min' + (min > 1 ? 's' : '') + ' e ' + seg + ' seg';
    }
    return seg + ' segundo' + (seg !== 1 ? 's' : '');
}

// ============================================
// CORREÇÃO NA FUNÇÃO atualizarTempos()
// Substituir a função completa no arquivo atendentes.php
// ============================================

function atualizarTempos() {
    var agora = Math.floor(Date.now() / 1000);
    
    document.querySelectorAll('.tempo-espera[data-timestamp]').forEach(function(el) {
        var ts = parseInt(el.getAttribute('data-timestamp'));
        if (!ts || isNaN(ts)) return; // Valida se o timestamp é válido
        
        var dif = agora - ts;
        
        // CRÍTICO: Evita valores negativos que causam contagens incorretas
        if (dif < 0) {
            dif = 0;
        }
        
        var min = Math.floor(dif / 60);
        var seg = dif % 60;
        
        // Só atualiza se não estiver atendido
        var cardAtendido = el.closest('.paciente-card[data-status="atendido"]');
        if (!cardAtendido) {
            // Atualiza o texto com a formatação correta
            el.innerHTML = '<i class="fas fa-clock"></i> Aguardando há ' + 
                          min + ' min' + (min !== 1 ? 's' : '') + ' e ' + 
                          seg + ' seg' + (seg !== 1 ? '' : '');
        }
        
        // Atualiza classes de alerta do card pai em tempo real
        var card = el.closest('.paciente-card');
        if (card && !cardAtendido) {
            // Remove todas as classes de alerta primeiro
            card.classList.remove('alerta-vermelho', 'alerta-amarelo');
            
            // Aplica a classe correta baseada no tempo
            if (min >= 10) {
                card.classList.add('alerta-vermelho');
            } else if (min >= 5) {
                card.classList.add('alerta-amarelo');
            }
        }
    });
}

// ------------------------
// Atualiza contador em tempo real
// ------------------------
function atualizarTempos() {
    var agora = Math.floor(Date.now() / 1000);
    
    document.querySelectorAll('.tempo-espera[data-timestamp]').forEach(function(el) {
        var ts = parseInt(el.getAttribute('data-timestamp'));
        if (!ts || isNaN(ts)) return; // Valida se o timestamp é válido
        
        var dif = agora - ts;
        
        // CRÍTICO: Evita valores negativos que causam contagens incorretas
        if (dif < 0) {
            dif = 0;
        }
        
        var min = Math.floor(dif / 60);
        var seg = dif % 60;
        
        // Só atualiza se não estiver atendido
        var cardAtendido = el.closest('.paciente-card[data-status="atendido"]');
        if (!cardAtendido) {
            // Atualiza o texto com a formatação correta
            el.innerHTML = '<i class="fas fa-clock"></i> Aguardando há ' + 
                          min + ' min' + (min !== 1 ? 's' : '') + ' e ' + 
                          seg + ' seg' + (seg !== 1 ? '' : '');
        }
        
        // Atualiza classes de alerta do card pai em tempo real
        var card = el.closest('.paciente-card');
        if (card && !cardAtendido) {
            // Remove todas as classes de alerta primeiro
            card.classList.remove('alerta-vermelho', 'alerta-amarelo');
            
            // Aplica a classe correta baseada no tempo
            if (min >= 10) {
                card.classList.add('alerta-vermelho');
            } else if (min >= 5) {
                card.classList.add('alerta-amarelo');
            }
        }
    });
}

// Função para gerenciar o carregamento da fila com tratamento de erros
function gerenciarAtualizacoes() {
    // Carrega a fila imediatamente
    carregarFila();
    
    // Atualiza os tempos em tempo real a cada segundo
    setInterval(atualizarTempos, 1000);
    
    // Atualiza a fila a cada 5 segundos (reduzido de 10 para 5 segundos)
    setInterval(carregarFila, 5000);
    
    // Atualiza a fila quando a página volta a ficar visível
    var visibilidade = (function() {
        var stateKey, eventKey, keys = {
            hidden: 'visibilitychange',
            webkitHidden: 'webkitvisibilitychange',
            mozHidden: 'mozvisibilitychange',
            msHidden: 'msvisibilitychange'
        };
        for (stateKey in keys) {
            if (stateKey in document) {
                eventKey = keys[stateKey];
                break;
            }
        }
        return function(c) {
            if (c) document.addEventListener(eventKey, c);
            return !document[stateKey];
        };
    })();
    
    visibilidade(function() {
        if (!document.hidden) {
            carregarFila();
        }
    });
}

// Inicializa quando o DOM estiver pronto
if (document.readyState === 'loading') {
   // Inicialização
document.addEventListener('DOMContentLoaded', function() {
        // Configura os modais
    const helpModal = document.getElementById('helpModal');
    const helpBtn = document.getElementById('helpBtn');
    const closeHelpModal = document.getElementById('closeHelpModal');
    const historyModal = document.getElementById('historyModal');
    const historyBtn = document.getElementById('historyBtn');
    const closeHistoryModal = document.getElementById('closeHistoryModal');
    
    // Abre o modal de ajuda
    helpBtn.addEventListener('click', function() {
        helpModal.style.display = 'flex';
    });
    
    // Fecha o modal de ajuda
    closeHelpModal.addEventListener('click', function() {
        helpModal.style.display = 'none';
    });
    
    // Abre o modal de histórico
    historyBtn.addEventListener('click', function() {
        historyModal.style.display = 'flex';
        carregarHistoricoAtendimentos();
    });
    
    // Fecha o modal de histórico
    closeHistoryModal.addEventListener('click', function() {
        historyModal.style.display = 'none';
    });
    
    // Fecha os modais ao clicar fora do conteúdo
    window.addEventListener('click', function(event) {
        if (event.target === helpModal) {
            helpModal.style.display = 'none';
        }
        if (event.target === historyModal) {
            historyModal.style.display = 'none';
        }
    });
    
    // Função para carregar o histórico de atendimentos
    function carregarHistoricoAtendimentos() {
        const historyList = document.getElementById('historyList');
        historyList.innerHTML = '<div class="history-loading"><i class="fas fa-spinner fa-spin"></i> Carregando histórico...</div>';
        
        // Obtém os valores dos filtros
        const filtroData = document.getElementById('filterDate').value;
        const filtroBusca = document.getElementById('filterSearch').value;
        
        // Cria os parâmetros da requisição
        const params = new URLSearchParams();
        if (filtroData) params.append('data', filtroData);
        if (filtroBusca) params.append('busca', filtroBusca);
        
        // Faz a requisição AJAX para o servidor
        fetch(`ajax/historico_ajax.php?${params.toString()}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro ao carregar histórico');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Atualiza a interface com os dados reais
                    atualizarListaHistorico(data.data);
                    atualizarResumo(data.data);
                } else {
                    throw new Error(data.message || 'Erro ao processar os dados');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                historyList.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        ${error.message || 'Erro ao carregar o histórico de atendimentos'}
                    </div>
                `;
            });
    }
    
    // Função para atualizar a lista de histórico
    function atualizarListaHistorico(historico) {
        const historyList = document.getElementById('historyList');
        
        if (historico.length === 0) {
            historyList.innerHTML = `
                <div class="no-results">
                    <i class="fas fa-inbox"></i>
                    <p>Nenhum atendimento encontrado</p>
                </div>
            `;
            return;
        }
        
        // Ordena por data/hora mais recente primeiro
        historico.sort((a, b) => new Date(b.atendidoEm) - new Date(a.atendidoEm));
        
        let html = `
            <div class="history-table">
                <div class="history-header">
                    <div class="history-cell">Paciente</div>
                    <div class="history-cell">Tipo</div>
                    <div class="history-cell">Prioridade</div>
                    <div class="history-cell">Data/Hora</div>
                    <div class="history-cell">Status</div>
                </div>
        `;
        
        historico.forEach(item => {
            const dataHora = new Date(item.atendidoEm);
            const dataFormatada = dataHora.toLocaleDateString('pt-BR');
            const horaFormatada = dataHora.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            
            html += `
                <div class="history-row">
                    <div class="history-cell">${item.nome}</div>
                    <div class="history-cell">${item.tipoAtendimento}</div>
                    <div class="history-cell">
                        <span class="badge ${item.prioridade.toLowerCase() === 'preferencial' ? 'preferencial' : 'normal'}">
                            ${item.prioridade}
                        </span>
                    </div>
                    <div class="history-cell">${dataFormatada} ${horaFormatada}</div>
                    <div class="history-cell">
                        <span class="badge atendido">Atendido</span>
                    </div>
                </div>
            `;
        });
        
        html += '</div>'; // Fecha a tabela
        historyList.innerHTML = html;
    }
    
    // Função para atualizar o resumo
    function atualizarResumo(historico) {
        document.getElementById('totalAtendimentos').textContent = historico.length;
    }
    
    // Adiciona evento aos botões de filtro
    document.addEventListener('DOMContentLoaded', function() {
        const applyFiltersBtn = document.getElementById('applyFilters');
        const clearFiltersBtn = document.getElementById('clearFilters');
        const filterSearch = document.getElementById('filterSearch');
        
        // Aplicar filtros ao clicar no botão ou pressionar Enter no campo de busca
        const aplicarFiltros = () => {
            carregarHistoricoAtendimentos();
        };
        
        if (applyFiltersBtn) {
            applyFiltersBtn.addEventListener('click', aplicarFiltros);
        }
        
        // Aplica filtro ao pressionar Enter no campo de busca
        if (filterSearch) {
            filterSearch.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    aplicarFiltros();
                }
            });
        }
        
        // Limpar filtros
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', function() {
                document.getElementById('filterDate').value = '';
                document.getElementById('filterSearch').value = '';
                carregarHistoricoAtendimentos();
            });
        }
        
        // Define a data atual como padrão no filtro de data
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('filterDate').value = today;
        
        // Carrega os dados iniciais
        carregarHistoricoAtendimentos();
    });

    // Carrega a fila ao carregar a página
    carregarFila();
    // Verifica os botões após um pequeno atraso
    setTimeout(verificarERecriarBotoes, 300);
});
} else {
    gerenciarAtualizacoes();
    // Verifica os botões após um pequeno atraso
    setTimeout(verificarERecriarBotoes, 300);
}

// Também verifica quando a janela terminar de carregar completamente
window.addEventListener('load', function() {
    // Verifica os botões após um pequeno atraso
    setTimeout(function() {
        verificarERecriarBotoes();
    }, 300);
    
    // Verifica novamente para garantir
    setTimeout(function() {
        verificarERecriarBotoes();
    }, 800);
    
    // Terceira verificação final
    setTimeout(function() {
        verificarERecriarBotoes();
    }, 1500);
});


</script>

<script>
function atualizarContador() {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', window.location.href.split('?')[0] + '?acao=contar', true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            document.getElementById('contadorAguardando').innerText = xhr.responseText;
        }
    };
    xhr.send();
}

// Atualiza a cada 5 segundos
setInterval(atualizarContador, 5000);
atualizarContador();
</script>

<script>
// Função de busca de pacientes e animações
document.addEventListener("DOMContentLoaded", () => {
    const searchInput = document.getElementById('searchInput');
    const clearSearch = document.getElementById('clearSearch');
    const container = document.getElementById('fila-container');
    
    // Função para normalizar texto (remove acentos e converte para minúsculas)
    function normalizeText(text) {
        return text
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }
    
    // Função para filtrar pacientes
    function filterPatients() {
        const searchTerm = normalizeText(searchInput.value.trim());
        const cards = container.querySelectorAll('.paciente-card');
        let visibleCount = 0;
        
        // Remove mensagem de "sem resultados" se existir
        const noResultsMsg = container.querySelector('.no-results-message');
        if (noResultsMsg) {
            noResultsMsg.remove();
        }
        
        cards.forEach(card => {
            const patientName = card.querySelector('.paciente-nome');
            if (patientName) {
                const normalizedName = normalizeText(patientName.textContent);
                
                if (searchTerm === '' || normalizedName.includes(searchTerm)) {
                    card.classList.remove('hidden-by-search');
                    visibleCount++;
                } else {
                    card.classList.add('hidden-by-search');
                }
            }
        });
        
        // Mostra mensagem se não houver resultados
        if (visibleCount === 0 && cards.length > 0 && searchTerm !== '') {
            const noResultsDiv = document.createElement('div');
            noResultsDiv.className = 'no-results-message';
            noResultsDiv.innerHTML = `
                <i class="fas fa-search"></i>
                <p>Nenhum paciente encontrado com o nome "<strong>${searchInput.value}</strong>"</p>
                <p style="font-size: 14px; color: #999; margin-top: 10px;">Tente buscar por outro nome</p>
            `;
            container.appendChild(noResultsDiv);
        }
        
        // Atualiza visibilidade do botão de limpar
        updateClearButton();
    }
    
    // Função para atualizar visibilidade do botão de limpar
    function updateClearButton() {
        if (searchInput.value.trim() !== '') {
            clearSearch.style.display = 'block';
        } else {
            clearSearch.style.display = 'none';
        }
    }
    
    // Função para limpar busca
    function clearSearchField() {
        searchInput.value = '';
        filterPatients();
        searchInput.focus();
    }
    
    // Event listeners para busca
    searchInput.addEventListener('input', filterPatients);
    searchInput.addEventListener('keyup', updateClearButton);
    clearSearch.addEventListener('click', clearSearchField);
    
    // Limpar busca ao pressionar ESC
    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            clearSearchField();
        }
    });
    
    // Observer único para animações e filtro de busca
    const observer = new MutationObserver(mutations => {
        let hasCardChanges = false;
        
        // Verifica se houve mudanças em cards (não em classes)
        mutations.forEach(mutation => {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(node => {
                    if (node.classList && node.classList.contains('paciente-card')) {
                        node.classList.add('show');
                        hasCardChanges = true;
                    }
                });
                mutation.removedNodes.forEach(node => {
                    if (node.classList && node.classList.contains('paciente-card')) {
                        node.classList.add('hide');
                        hasCardChanges = true;
                    }
                });
            }
        });
        
        // Aplica animação apenas se houve mudanças reais em cards
        if (hasCardChanges) {
            container.classList.add('animate');
            setTimeout(() => container.classList.remove('animate'), 600);
            
            // Reaplica filtro de busca se houver texto no campo
            if (searchInput.value.trim() !== '') {
                // Usa setTimeout para evitar loop infinito
                setTimeout(() => filterPatients(), 10);
            }
        }
    });
    
    // Observa apenas mudanças diretas nos filhos (não subtree)
    observer.observe(container, { childList: true });
});
</script>


</body>
</html>
