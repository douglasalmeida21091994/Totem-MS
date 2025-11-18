<?php
/**
 * Endpoint para buscar histórico de atendimentos
 */

// Habilitar exibição de erros para depuração
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configurações de cabeçalho para permitir requisições AJAX
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Inclui o arquivo de configuração do banco de dados
$rootPath = dirname(dirname(__FILE__));
require_once($rootPath . '/config.php');
require_once($rootPath . '/database.php');

// Inicializa a resposta
$response = [
    'success' => false,
    'message' => '',
    'data' => []
];

try {
    // Obtém a instância do DAO
    $database = new Database();
    $conexao = $database->conectar();
    
    if (!$conexao) {
        throw new Exception('Não foi possível conectar ao banco de dados');
    }
    
    // Verifica se a conexão foi bem-sucedida

    // Função para formatar a data no padrão brasileiro
    function formatarData($data) {
        return date('d/m/Y', strtotime($data));
    }

    // Função para formatar a hora
    function formatarHora($hora) {
        return date('H:i', strtotime($hora));
    }

    // Obtém os parâmetros de filtro
    $filtroData = isset($_GET['data']) ? $_GET['data'] : '';
    $filtroBusca = isset($_GET['busca']) ? trim($_GET['busca']) : '';

    // Inicializa arrays para condições e parâmetros
    $where = [];
    $params = [];
    
    // Base da consulta SQL
    $sql = "SELECT 
                id,
                nome AS paciente_nome,
                tipo_atendimento,
                prioridade,
                data_atendimento,
                hora_atendimento,
                status,
                atendido_em,
                hora_inicio_atendimento
            FROM atendimentos 
            WHERE status = 'atendido'";

    // Adiciona filtro por data, se fornecido
    if (!empty($filtroData)) {
        $where[] = "DATE(data_atendimento) = ?";
        $params[] = $filtroData;
    }

    // Adiciona filtro por busca (nome do paciente), se fornecido
    if (!empty($filtroBusca)) {
        $where[] = "nome LIKE ?";
        $params[] = "%$filtroBusca%";
    }

    // Adiciona as condições WHERE à consulta, se houver
    if (!empty($where)) {
        $sql .= " AND " . implode(" AND ", $where);
    }

    // Ordena por data e hora do atendimento (mais recentes primeiro)
    $sql .= " ORDER BY data_atendimento DESC, hora_atendimento DESC";
    
    // Usa $params para a consulta
    $queryParams = $params;
    
    // Executa a consulta
    try {
        // Log para depuração
        error_log('Executando consulta: ' . $sql);
        error_log('Parâmetros: ' . print_r($params, true));
        
        // Usa os parâmetros corretos na consulta
        $resultados = $database->select($sql, $params);
        
        if ($resultados === false) {
            error_log('A consulta retornou false');
            $resultados = [];
        } else if (empty($resultados)) {
            error_log('A consulta não retornou resultados');
        } else {
            error_log('Resultados encontrados: ' . count($resultados));
        }
        
    } catch (Exception $e) {
        error_log('Erro na consulta SQL: ' . $e->getMessage());
        throw new Exception('Erro ao buscar histórico: ' . $e->getMessage());
    }

    // Se não houver resultados, definir mensagem
    if (empty($resultados)) {
        $response['message'] = 'Nenhum registro encontrado';
    }
    
    // Formata os resultados
    $historico = [];
    if (is_array($resultados)) {
        foreach ($resultados as $item) {
            $historico[] = [
                'id' => $item['id'],
                'nome' => $item['paciente_nome'],
                'tipoAtendimento' => $item['tipo_atendimento'],
                'prioridade' => ucfirst(strtolower($item['prioridade'])),
                'data' => formatarData($item['data_atendimento']),
                'hora' => formatarHora($item['hora_atendimento']),
                'status' => 'atendido',
                'atendidoEm' => $item['atendido_em'],
                'horaInicioAtendimento' => $item['hora_inicio_atendimento'] ? date('d/m/Y H:i:s', strtotime($item['hora_inicio_atendimento'])) : null,
                'tempoEspera' => $item['hora_inicio_atendimento'] ? round((strtotime($item['hora_inicio_atendimento']) - strtotime($item['data_atendimento'] . ' ' . $item['hora_atendimento'])) / 60) : null
            ];
        }
    }

    // Retorna a resposta com sucesso
    $response['success'] = true;
    $response['data'] = $historico;
    $response['message'] = 'Dados carregados com sucesso';
    
} catch (Exception $e) {
    // Em caso de erro, retorna mensagem de erro
    $response['message'] = 'Erro ao buscar histórico: ' . $e->getMessage();
    http_response_code(500);
}

// Retorna a resposta em formato JSON
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// Encerra a execução
exit;
