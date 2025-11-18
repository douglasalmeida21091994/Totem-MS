<?php
/**
 * Acesso Rápido para Atendentes
 * Sistema em PHP 5.4
 */

require_once 'config.php';

// Verificação básica de acesso (você pode adicionar autenticação mais robusta)
$acessoPermitido = true; // Em produção, implementar autenticação

if (!$acessoPermitido) {
    header('Location: index.php');
    exit;
}

// Redirecionar diretamente para a tela de atendentes
header('Location: atendentes.php');
exit;
?>
