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

    // Lê o corpo JSON enviado pelo front-end
    $inputData = json_decode(file_get_contents("php://input"), true);

    if (!$inputData) {
        throw new Exception("JSON inválido ou ausente no corpo da requisição.");
    }

    // Campos recebidos
    $chaveBeneficiario  = $inputData['chave_beneficiario']  ?? '';
    $executante         = $inputData['executante']          ?? '';
    $solicitante        = $inputData['solicitante']         ?? '';
    $idUnidade          = $inputData['id_unidade']          ?? '';
    $especialidade      = $inputData['nome_especialidade']  ?? '';

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
    $bodyArray = [
        "id"             => (int)$chaveBeneficiario,
        "executante"     => $executante,
        "solicitante"    => $solicitante,
        "local"          => (int)$idUnidade,
        "especialidade"  => $especialidade,

        // CAMPOS FIXOS
        "operador"       => 719539,
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

        // PROCEDIMENTO FIXO
        "procedimentos" => [
            [
                "codigo"     => "10101012",
                "quantidade" => 1,
                "dente"      => null,
                "face"       => null
            ]
        ]
    ];

    $bodyJson = json_encode($bodyArray, JSON_UNESCAPED_UNICODE);

    // ===== CHAMADA cURL =====
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $API_URL,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $bodyJson,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-API-KEY: ' . $API_KEY
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_TIMEOUT        => 30
    ]);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new Exception("Erro na requisição cURL: $curlError");
    }

    // Decodifica resposta
    $responseData = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Erro ao decodificar JSON da API.");
    }

    // ===== RETORNO FINAL (com debug estruturado) =====
    echo json_encode([
        "sucesso"        => true,
        "statusCode"     => $httpCode,
        "resposta_api"   => $responseData,
        "debug_request"  => $bodyArray
    ]);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        "sucesso"       => false,
        "erro"          => $e->getMessage(),
        "debug_request" => $inputData ?? null
    ]);
}
