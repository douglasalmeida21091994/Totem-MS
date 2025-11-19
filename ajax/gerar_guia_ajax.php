<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// === CONFIGURAÇÃO CORRETA PARA A NOVA API ===
$API_URL  = "https://ws.smilesaude.com.br/api/inserirGuiaConsulta";
$API_KEY  = "7xK9pL2mN5vR8jQ3wB6tZ4dF1hC0gYsA";

// Pré-flight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {

    // PHP 5.3 não aceita json_decode(null) com operador ??
    $rawInput = file_get_contents("php://input");
    $inputData = json_decode($rawInput, true);

    if (!$inputData || !is_array($inputData)) {
        throw new Exception("JSON inválido ou ausente no corpo da requisição.");
    }

    // Recuperação dos campos com validação PHP 5.3
    $chaveBeneficiario = isset($inputData['chave_beneficiario']) ? $inputData['chave_beneficiario'] : '';
    $executante        = isset($inputData['executante']) ? $inputData['executante'] : '';
    $solicitante       = isset($inputData['solicitante']) ? $inputData['solicitante'] : '';
    $idUnidade         = isset($inputData['id_unidade']) ? $inputData['id_unidade'] : '';
    $especialidade     = isset($inputData['nome_especialidade']) ? $inputData['nome_especialidade'] : '';

    // ===== VALIDAÇÕES OBRIGATÓRIAS =====
    if (
        empty($chaveBeneficiario) ||
        empty($executante) ||
        empty($solicitante) ||
        empty($idUnidade) ||
        empty($especialidade)
    ) {
        throw new Exception("Parâmetros obrigatórios ausentes para geração da guia.");
    }

    if (!is_numeric($chaveBeneficiario) || !is_numeric($idUnidade)) {
        throw new Exception("Chave de beneficiário e ID de unidade devem ser numéricos.");
    }

    // ===== MONTAGEM DO JSON ENVIADO =====
    $bodyArray = array(
        "id"             => (int)$chaveBeneficiario,
        "executante"     => $executante,
        "solicitante"    => $solicitante,
        "local"          => (int)$idUnidade,
        "especialidade"  => $especialidade,

        // CAMPOS FIXOS
        "operador"       => 1892654,
        "regime"         => "E",
        "natureza"       => "A",
        "id_cid"         => "",
        "hipotese"       => "CONSULTA ELETIVA",
        "acidente"       => "2",
        "tipodoenca"     => null,
        "tempo"          => "0",
        "unidade"        => "",
        "status"         => "P",
        "tipoconsulta"   => null,
        "localemissao"   => 5,
        "digital"        => null,
        "acomodacao"     => 0,
        "motivoDigital"      => null,
        "idMotivoDigital"    => null,
        "atendimentoRN"      => "N",
        "tipoAtendimento"    => "4",

        "procedimentos" => array(
            array(
                "codigo"     => "10101012",
                "quantidade" => 1,
                "dente"      => null,
                "face"       => null
            )
        )
    );

    $bodyJson = json_encode($bodyArray);

    // ===== CHAMADA cURL =====
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $API_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyJson);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'X-API-KEY: ' . $API_KEY
    ));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new Exception("Erro na requisição cURL: " . $curlError);
    }

    $responseData = json_decode($response, true);

    if (!$responseData) {
        throw new Exception("Erro ao decodificar JSON da API. Resposta recebida: " . $response);
    }

    // ===== RETORNO =====
    echo json_encode(array(
        "sucesso"        => true,
        "statusCode"     => $httpCode,
        "resposta_api"   => $responseData,
        "debug_request"  => $bodyArray
    ));

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode(array(
        "sucesso"       => false,
        "erro"          => $e->getMessage(),
        "debug_request" => isset($inputData) ? $inputData : null
    ));
}
