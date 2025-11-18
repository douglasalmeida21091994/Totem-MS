<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Verifica se a chave da API está configurada
    if (!isset($config_smilesaude['api_key'])) {
        throw new Exception('API key não configurada');
    }
    
    // Retorna apenas a chave da API
    echo json_encode([
        'success' => true,
        'api_key' => $config_smilesaude['api_key']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
