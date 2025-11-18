<?php
/**
 * Configurações do Sistema - Clínica Mais Saúde
 * Compatível com PHP 5.4
 */

// Configurações básicas
define('SYSTEM_VERSION', '1.0.0');
define('SYSTEM_NAME', 'Totem Auto Atendimento - Clínica Mais Saúde');
define('CLINICA_NOME', 'Clínica Mais Saúde');

// Configurações de sessão
ini_set('session.cookie_lifetime', 0);
ini_set('session.gc_maxlifetime', 3600);

// Função auxiliar para compatibilidade com PHP 5.4
function obterValorPadrao($array, $chave, $padrao = '') {
    return isset($array[$chave]) ? $array[$chave] : $padrao;
}

// Configurações de banco de dados
$config_banco = array(
    'host' => 'localhost',
    'usuario' => 'root',
    'senha' => '',
    'banco' => 'totem_saude',
    'charset' => 'utf8'
);

// URLs das APIs externas
$config_apis = array(
    'smile_saude' => 'https://ws.smilesaude.com.br/api/',
    'biodoc' => 'https://api.biodoc.com.br/api/'
);

// Configurações de autenticação BioDoc
$config_biodoc = array(
    'token' => 'S2xNaFlKRXIyVEx3WFphMEM5bnRmeGhtcGVkcHVrMjZobm9DLzRxMzFUbTYvNU5pOFI4Zmo4WjFuWkxJL2Qrdg=='
);

// Cria a conexão MySQL compatível com PHP 5.4
$conn = mysqli_connect(
    $config_banco['host'],
    $config_banco['usuario'],
    $config_banco['senha'],
    $config_banco['banco']
);

// Define o charset da conexão
if ($conn) {
    mysqli_set_charset($conn, $config_banco['charset']);
} else {
    die('Erro ao conectar ao banco de dados: ' . mysqli_connect_error());
}

?>
