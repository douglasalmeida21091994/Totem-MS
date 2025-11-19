<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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
    // Lê o corpo da requisição (POST em JSON)
    $inputData = json_decode(file_get_contents("php://input"), true);

    $idAtendimento     = isset($inputData['id_atendimento']) ? trim($inputData['id_atendimento']) : '';
    $chaveBeneficiario = isset($inputData['chave_beneficiario']) ? trim($inputData['chave_beneficiario']) : '';

    // ======= Validação robusta =======
    if (
        empty($idAtendimento) ||
        empty($chaveBeneficiario) ||
        !is_numeric($idAtendimento) ||
        !is_numeric($chaveBeneficiario) ||
        $idAtendimento <= 0 ||
        $chaveBeneficiario <= 0
    ) {
        throw new Exception('Parâmetros obrigatórios inválidos para confirmação de agendamento.');
    }

    // Verifica se a key está configurada
    if (!isset($config_smilesaude['api_key'])) {
        throw new Exception('API key não configurada');
    }

    // URL de confirmação
    $apiUrl = 'https://ws.smilesaude.com.br/api/confirmarAgendamentoTotem';

    // Corpo JSON enviado à API
    $bodyJson = json_encode([
        'id_atendimento'     => (int)$idAtendimento,
        'chave_beneficiario' => (int)$chaveBeneficiario
    ]);

    // Inicializa cURL
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $bodyJson,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-API-KEY: ' . $config_smilesaude['api_key']
        ],
        CURLOPT_SSL_VERIFYPEER => false, // Desenvolvimento
        CURLOPT_SSL_VERIFYHOST => 0,     // Desenvolvimento
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);

    curl_close($ch);

    if ($error) {
        throw new Exception("Erro na requisição: $error");
    }

    $responseData = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Erro ao decodificar a resposta da API');
    }

    http_response_code($httpCode);
    echo json_encode($responseData);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'erro' => $e->getMessage()
    ]);
}