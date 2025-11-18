<?php
/**
 * Script de Instalação do Banco de Dados
 * Totem Auto Atendimento - Clínica Mais Saúde
 * Compatível com PHP 5.4
 */

require_once 'config.php';

// Verificar se já foi instalado
$arquivo_verificacao = 'instalado.txt';
if (file_exists($arquivo_verificacao)) {
    die('<h1>O sistema já foi instalado!</h1><p><a href="index.php">Ir para o sistema</a></p>');
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - <?php echo CLINICA_NOME; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .step {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .success { border-color: #4CAF50; background-color: #f1f8e9; }
        .error { border-color: #f44336; background-color: #ffebee; }
        .warning { border-color: #ff9800; background-color: #fff3e0; }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover { background-color: #45a049; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Instalação do Sistema - <?php echo CLINICA_NOME; ?></h1>
        
        <?php
        $passos_completos = 0;
        $total_passos = 5;
        
        // PASSO 1: Verificar PHP
        echo '<div class="step">';
        echo '<h3>Passo 1: Verificando PHP</h3>';
        
        $php_version = phpversion();
        $php_ok = version_compare($php_version, '5.4.0', '>=');
        
        if ($php_ok) {
            echo '<p class="success">✅ PHP ' . $php_version . ' - OK!</p>';
            $passos_completos++;
        } else {
            echo '<p class="error">❌ PHP ' . $php_version . ' - Versão muito antiga! Necessário PHP 5.4+</p>';
        }
        echo '</div>';
        
        // PASSO 2: Verificar extensões
        echo '<div class="step">';
        echo '<h3>Passo 2: Verificando Extensões PHP</h3>';
        
        $extensoes_necessarias = array('mysql', 'session', 'json');
        $extensoes_ok = true;
        
        foreach ($extensoes_necessarias as $ext) {
            if (extension_loaded($ext)) {
                echo '<p class="success">✅ ' . $ext . ' - OK!</p>';
            } else {
                echo '<p class="error">❌ ' . $ext . ' - Não encontrada!</p>';
                $extensoes_ok = false;
            }
        }
        
        if ($extensoes_ok) {
            $passos_completos++;
        }
        echo '</div>';
        
        // PASSO 3: Verificar configuração do banco
        echo '<div class="step">';
        echo '<h3>Passo 3: Configuração do Banco de Dados</h3>';
        
        $config_ok = true;
        
        if (empty($config_banco['host'])) {
            echo '<p class="error">❌ Host do banco não configurado!</p>';
            $config_ok = false;
        }
        if (empty($config_banco['banco'])) {
            echo '<p class="error">❌ Nome do banco não configurado!</p>';
            $config_ok = false;
        }
        
        if ($config_ok) {
            echo '<p class="success">✅ Configuração do banco - OK!</p>';
            echo '<p><strong>Host:</strong> ' . $config_banco['host'] . '</p>';
            echo '<p><strong>Banco:</strong> ' . $config_banco['banco'] . '</p>';
            echo '<p><strong>Usuário:</strong> ' . $config_banco['usuario'] . '</p>';
            $passos_completos++;
        }
        echo '</div>';
        
        // PASSO 4: Tentar conectar ao banco
        echo '<div class="step">';
        echo '<h3>Passo 4: Testando Conexão</h3>';
        
        if ($config_ok) {
            $conexao = mysql_connect($config_banco['host'], $config_banco['usuario'], $config_banco['senha']);
            
            if ($conexao) {
                echo '<p class="success">✅ Conexão com MySQL - OK!</p>';
                
                // Tentar selecionar o banco
                if (mysql_select_db($config_banco['banco'], $conexao)) {
                    echo '<p class="success">✅ Banco "' . $config_banco['banco'] . '" acessível!</p>';
                    $passos_completos++;
                } else {
                    echo '<p class="warning">⚠️ Banco "' . $config_banco['banco'] . '" não existe. Será criado automaticamente.</p>';
                    
                    // Tentar criar o banco
                    if (mysql_query("CREATE DATABASE IF NOT EXISTS `" . $config_banco['banco'] . "` CHARACTER SET utf8 COLLATE utf8_general_ci", $conexao)) {
                        echo '<p class="success">✅ Banco criado com sucesso!</p>';
                        mysql_select_db($config_banco['banco'], $conexao);
                        $passos_completos++;
                    } else {
                        echo '<p class="error">❌ Erro ao criar banco: ' . mysql_error() . '</p>';
                    }
                }
                
                mysql_close($conexao);
            } else {
                echo '<p class="error">❌ Erro na conexão: ' . mysql_error() . '</p>';
            }
        }
        echo '</div>';
        
        // PASSO 5: Executar instalação do banco
        echo '<div class="step">';
        echo '<h3>Passo 5: Instalação das Tabelas</h3>';
        
        if ($passos_completos >= 4) {
            if (isset($_POST['instalar'])) {
                // Carregar arquivo SQL
                $sql_content = file_get_contents('database.sql');
                
                if ($sql_content === false) {
                    echo '<p class="error">❌ Erro ao ler arquivo database.sql</p>';
                } else {
                    $conexao = mysql_connect($config_banco['host'], $config_banco['usuario'], $config_banco['senha']);
                    mysql_select_db($config_banco['banco'], $conexao);
                    mysql_set_charset('utf8', $conexao);
                    
                    // Dividir SQL em comandos individuais
                    $comandos = explode(';', $sql_content);
                    $comandos_executados = 0;
                    $erros = array();
                    
                    foreach ($comandos as $comando) {
                        $comando = trim($comando);
                        if (empty($comando) || substr($comando, 0, 2) == '--') {
                            continue;
                        }
                        
                        if (mysql_query($comando)) {
                            $comandos_executados++;
                        } else {
                            $erros[] = mysql_error() . " - Comando: " . substr($comando, 0, 100) . "...";
                        }
                    }
                    
                    mysql_close($conexao);
                    
                    if (empty($erros)) {
                        echo '<p class="success">✅ Banco de dados instalado com sucesso!</p>';
                        echo '<p>Comandos executados: ' . $comandos_executados . '</p>';
                        $passos_completos++;
                        
                        // Criar arquivo de verificação
                        file_put_contents($arquivo_verificacao, date('Y-m-d H:i:s') . ' - Instalação completa');
                        
                        echo '<p class="success"><strong>🎉 Instalação concluída!</strong></p>';
                        echo '<p><a href="index.php"><button>Ir para o Sistema</button></a></p>';
                    } else {
                        echo '<p class="error">❌ Alguns erros foram encontrados:</p>';
                        echo '<pre>' . implode("\n", $erros) . '</pre>';
                    }
                }
            } else {
                echo '<p>Pronto para instalar as tabelas do banco de dados.</p>';
                echo '<form method="post">';
                echo '<button type="submit" name="instalar">Instalar Banco de Dados</button>';
                echo '</form>';
                echo '<p><small>Isso irá criar todas as tabelas, dados iniciais e configurações do sistema.</small></p>';
            }
        } else {
            echo '<p class="warning">⚠️ Complete os passos anteriores antes de instalar o banco.</p>';
        }
        echo '</div>';
        
        // Resumo
        echo '<div class="step">';
        echo '<h3>Resumo da Instalação</h3>';
        echo '<p>Passos completos: <strong>' . $passos_completos . '/' . $total_passos . '</strong></p>';
        
        if ($passos_completos == $total_passos) {
            echo '<p class="success">✅ Sistema pronto para uso!</p>';
        } else {
            echo '<p class="warning">⚠️ Ainda há passos pendentes.</p>';
        }
        echo '</div>';
        
        // Instruções
        if ($passos_completos < $total_passos && !isset($_POST['instalar'])) {
            echo '<div class="step">';
            echo '<h3>📋 Instruções de Instalação</h3>';
            echo '<ol>';
            echo '<li><strong>Configure o banco:</strong> Edite o arquivo <code>config.php</code> com as credenciais corretas</li>';
            echo '<li><strong>Execute este script:</strong> acesse <code>install.php</code> no navegador</li>';
            echo '<li><strong>Teste o sistema:</strong> acesse <code>index.php</code> para usar o totem</li>';
            echo '</ol>';
            echo '<p><strong>Dados de acesso padrão:</strong></p>';
            echo '<ul>';
            echo '<li>Usuário: <code>admin</code></li>';
            echo '<li>Senha: <code>admin123</code></li>';
            echo '</ul>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
