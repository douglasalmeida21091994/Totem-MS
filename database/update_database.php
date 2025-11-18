<?php
/**
 * Script para atualização do banco de dados
 * Executa as alterações necessárias na estrutura do banco de dados
 */

// Define o diretório raiz do projeto
$rootDir = dirname(__DIR__);

// Carrega o arquivo de configuração
require_once $rootDir . '/config.php';

try {
    // Conecta ao banco de dados
    $conexao = new mysqli(
        $config_banco['host'],
        $config_banco['usuario'],
        $config_banco['senha'],
        $config_banco['banco']
    );

    if ($conexao->connect_error) {
        die("Erro na conexão: " . $conexao->connect_error);
    }

    // Define o charset
    $conexao->set_charset($config_banco['charset']);

    // Lê o arquivo SQL
    $sqlFile = $rootDir . '/database/update_tables.sql';
    $sql = file_get_contents($sqlFile);
    
    if ($sql === false) {
        die("Erro ao ler o arquivo de atualização: " . $sqlFile);
    }

    // Executa as queries
    if ($conexao->multi_query($sql)) {
        do {
            // Libera os resultados
            if ($result = $conexao->store_result()) {
                $result->free();
            }
            
            // Próximo resultado
            if (!$conexao->more_results()) break;
            $conexao->next_result();
        } while (true);
        
        echo "Banco de dados atualizado com sucesso!\n";
    } else {
        echo "Erro ao executar a atualização: " . $conexao->error . "\n";
    }

    $conexao->close();

} catch (Exception $e) {
    die("Erro: " . $e->getMessage() . "\n");
}

echo "Atualização concluída. Verifique se não houve erros acima.\n";
?>
