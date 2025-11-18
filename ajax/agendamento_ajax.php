<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// require_once '../config.php';

// Configurações de autenticação Smile Saúde
$config_smilesaude = array(
    'api_key' => 'gG8Pj2zYc5sH6qWbE9xR4tA1nF3mQ7wL'
);

// Verifica se é uma requisição OPTIONS (pré-flight do CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Verifica se a chave_beneficiario foi fornecida
    $chaveBeneficiario = isset($_POST['chave_beneficiario']) ? trim($_POST['chave_beneficiario']) : '';
    
    if (empty($chaveBeneficiario)) {
        throw new Exception('Chave do beneficiário não fornecida');
    }
    
    // Verifica se a chave da API está configurada
    if (!isset($config_smilesaude['api_key'])) {
        throw new Exception('API key não configurada');
    }
    
    // Configura a URL da API
    $apiUrl = 'https://ws.smilesaude.com.br/api/agendamentoSequencial';
    $queryParams = http_build_query(['beneficiario' => $chaveBeneficiario]);
    
    // Inicializa cURL
    $ch = curl_init();
    
    // Configura as opções do cURL
    curl_setopt_array($ch, [
        CURLOPT_URL => "$apiUrl?$queryParams",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-API-KEY: ' . $config_smilesaude['api_key']
        ],
        CURLOPT_SSL_VERIFYPEER => false, // Apenas para desenvolvimento
        CURLOPT_SSL_VERIFYHOST => 0,     // Apenas para desenvolvimento
        CURLOPT_TIMEOUT => 30
    ]);
    
    // Executa a requisição
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    // Verifica se houve erro na requisição
    if ($error) {
        throw new Exception("Erro na requisição: $error");
    }
    
    // Decodifica a resposta
    $responseData = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Erro ao decodificar a resposta da API');
    }
    
    // Retorna a resposta da API
    http_response_code($httpCode);
    echo json_encode($responseData);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'erro' => $e->getMessage()
    ]);
}
