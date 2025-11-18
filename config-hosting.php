<?php
/**
 * Configurações do Sistema - Clínica Mais Saúde (Produção Hostinger)
 * Compatível com PHP 5.4+
 */

// Inicia a sessão
session_start();

// Detecta se está em ambiente local
$host = $_SERVER['HTTP_HOST'];
$isLocal = in_array($host, ['localhost', '127.0.0.1', 'mds-solution.local.com']);

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

// Configurações de banco de dados para cada ambiente
if ($isLocal) {
    // Configurações locais
    define('SITE_URL', 'http://localhost/TOTEM-MAIS-SAUDE');
    
    $config_banco = array(
        'host' => 'localhost',
        'usuario' => 'root',
        'senha' => '',
        'banco' => 'totem_saude',
        'charset' => 'utf8'
    );
} else {
    // Configurações de produção (Hostinger)
    define('SITE_URL', 'https://seu-dominio.com'); // Substitua pelo seu domínio real
    
    $config_banco = array(
        'host' => 'localhost', // Ou o host específico do seu banco na Hostinger
        'usuario' => 'seu_usuario_bd', // Seu usuário do banco de dados
        'senha' => 'sua_senha_bd', // Sua senha do banco de dados
        'banco' => 'seu_banco_de_dados', // Nome do seu banco de dados
        'charset' => 'utf8'
    );
}

// URLs das APIs externas (pode variar entre ambientes se necessário)
$config_apis = array(
    'smile_saude' => 'https://ws.smilesaude.com.br/api/',
    'biodoc' => 'https://api.biodoc.com.br/api/'
);

// Configurações de autenticação BioDoc
$config_biodoc = array(
    'token' => 'S2xNaFlKRXIyVEx3WFphMEM5bnRmeGhtcGVkcHVrMjZobm5DLzRxMzFUbTYvNU5pOFI4Zmo4WjFuWkxJL2Qrdg=='
);

// Configurações de autenticação Smile Saúde
$config_smilesaude = array(
    'api_key' => 'gG8Pj2zYc5sH6qWbE9xR4tA1nF3mQ7wL'
);

// Cria a conexão MySQL compatível com PHP 5.4
$conn = mysqli_connect(
    $config_banco['host'],
    $config_banco['usuario'],
    $config_banco['senha'],
    $config_banco['banco']
);

// Verifica a conexão
if (mysqli_connect_errno()) {
    die("Falha na conexão com o banco de dados: " . mysqli_connect_error());
}

// Define o charset da conexão
mysqli_set_charset($conn, $config_banco['charset']);

// Funções auxiliares
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit();
    }
}

function redirect($path) {
    header('Location: ' . SITE_URL . $path);
    exit();
}

function sanitize($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return $text;
}
?>
