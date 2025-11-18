<?php
/**
 * Totem Auto Atendimento - Clínica Mais Saúde
 * Versão compatível com PHP 7.4 / 8.x (Hostinger)
 * Totalmente em Português Brasileiro 🇧🇷
 */

// Sessão segura
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cabeçalhos básicos
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Funções auxiliares (com proteção contra duplicação)
if (!function_exists('gerarNumeroSenha')) {
    function gerarNumeroSenha() {
        $letras = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numeroAleatorio = rand(10, 99);
        $letraAleatoria = $letras[rand(0, strlen($letras) - 1)];
        return $letraAleatoria . $numeroAleatorio;
    }
}

if (!function_exists('obterDataAtual')) {
    function obterDataAtual() {
        return date('d/m/Y');
    }
}

if (!function_exists('obterHoraAtual')) {
    function obterHoraAtual() {
        return date('H:i:s');
    }
}
