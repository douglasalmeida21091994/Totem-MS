<?php
header('Content-Type: application/json; charset=utf-8');

/* --- PHP 5.3 COMPATÍVEL --- */

/* Lê corpo da requisição */
$raw = file_get_contents('php://input');
if (!$raw) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(array('sucesso' => false, 'mensagem' => 'Request body vazio'));
    exit;
}

/* Decodifica JSON */
$input = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {

    // json_last_error_msg NÃO existe no PHP 5.3
    $jsonError = json_last_error();
    $jsonErrorMessage = 'Erro ao decodificar JSON';

    header('HTTP/1.1 400 Bad Request');
    echo json_encode(array(
        'sucesso' => false,
        'mensagem' => 'JSON inválido',
        'error'    => $jsonErrorMessage . ' (code: '.$jsonError.')'
    ));
    exit;
}

/* Campos obrigatórios */
$required = array('id_atendimento', 'chave_beneficiario', 'numero_guia');
foreach ($required as $r) {
    if (!isset($input[$r])) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(array('sucesso' => false, 'mensagem' => 'Campo faltando: '.$r));
        exit;
    }
}

/* Monta payload */
$payload = array(
    'id_atendimento'     => (int)$input['id_atendimento'],
    'chave_beneficiario' => (int)$input['chave_beneficiario'],
    'numero_guia'        => (int)$input['numero_guia']
);

/* LOG para debug do servidor */
error_log('Encaminha fila payload: ' . json_encode($payload));

/* Configura API */
$apiUrl = 'https://ws.smilesaude.com.br/api/encaminharParaFila';
$apiKey = 'gG8Pj2zYc5sH6qWbE9xR4tA1nF3mQ7wL';

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'X-API-KEY: '.$apiKey
));

/* --- ⚠️ CORREÇÃO OBRIGATÓRIA PARA PHP 5.3 + SSL --- */
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
/* -------------------------------------------------- */

curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$curlErr  = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

/* Erro cURL */
if ($curlErr) {
    header('HTTP/1.1 502 Bad Gateway');
    echo json_encode(array(
        'sucesso'  => false,
        'mensagem' => 'Erro de comunicação com API externa',
        'erro'     => $curlErr
    ));
    exit;
}

/* Parseia a resposta */
$parsed = json_decode($response, true);

$result = array(
    'sucesso'          => ($httpCode >= 200 && $httpCode < 300),
    'http_code'        => $httpCode,
    'resposta_api_raw' => $response
);

if (is_array($parsed)) {
    $result['resposta_api'] = $parsed;
}

echo json_encode($result);
?>
