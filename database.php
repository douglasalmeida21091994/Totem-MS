<?php
/**
 * Conexão com Banco de Dados - Clínica Mais Saúde
 * Compatível com PHP 5.4 e MySQL
 */

require_once 'config.php';

class Database {
    private $host;
    private $usuario;
    private $senha;
    private $banco;
    private $conexao;
    
    public function __construct() {
        global $config_banco;
        $this->host = $config_banco['host'];
        $this->usuario = $config_banco['usuario'];
        $this->senha = $config_banco['senha'];
        $this->banco = $config_banco['banco'];
    }
    
    /**
     * Conectar ao banco de dados usando PDO
     */
    public function conectar() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->banco};charset=utf8";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->conexao = new PDO($dsn, $this->usuario, $this->senha, $options);
            return $this->conexao;
        } catch (PDOException $e) {
            die('Erro na conexão com o banco: ' . $e->getMessage());
        }
    }
    
    /**
     * Executar query SELECT
     */
    public function select($sql, $params = []) {
        try {
            $stmt = $this->conexao->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Erro na consulta SQL: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Executar query INSERT e retornar ID inserido
     */
    public function insert($sql, $params = []) {
        try {
            $stmt = $this->conexao->prepare($sql);
            $stmt->execute($params);
            return $this->conexao->lastInsertId();
        } catch (PDOException $e) {
            error_log('Erro na consulta SQL: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Executar query UPDATE/DELETE e retornar número de linhas afetadas
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->conexao->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log('Erro na consulta SQL: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Buscar uma única linha
     */
    public function selectOne($sql, $params = []) {
        $dados = $this->select($sql, $params);
        return (!empty($dados)) ? $dados[0] : false;
    }
    
    /**
     * Executar query com escape de strings
     */
    public function query($sql, $params = []) {
        // Escapar parâmetros se fornecidos
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $params[$key] = $this->conexao->quote($value);
            }
            $sql = vsprintf($sql, $params);
        }
        
        return $this->conexao->query($sql);
    }
    
    /**
     * Fechar conexão
     */
    public function fechar() {
        $this->conexao = null;
    }
    
    /**
     * Iniciar transação
     */
    public function beginTransaction() {
        $this->conexao->beginTransaction();
    }
    
    /**
     * Confirmar transação
     */
    public function commit() {
        $this->conexao->commit();
    }
    
    /**
     * Cancelar transação
     */
    public function rollback() {
        $this->conexao->rollBack();
    }
    
    /**
     * Obter último ID inserido
     */
    public function lastInsertId() {
        return $this->conexao->lastInsertId();
        mysql_query("ROLLBACK", $this->conexao);
    }
}

/**
 * Funções auxiliares para operações comuns do sistema
 */
class TotemDAO {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
        $this->db->conectar();
    }
    
    /**
     * Buscar paciente por CPF
     */
    public function buscarPacientePorCPF($cpf) {
        $cpf = preg_replace('/\D/', '', $cpf);
        $sql = "SELECT * FROM pacientes WHERE REPLACE(REPLACE(cpf, '.', ''), '-', '') = :cpf AND status = 'ativo'";
        return $this->db->selectOne($sql, [':cpf' => $cpf]);
    }
    
    /**
     * Inserir nova senha de atendimento
     */
    public function inserirSenhaAtendimento($dados) {
        $sql = "INSERT INTO senhas_atendimento (
            numero_senha, paciente_id, tipo_atendimento_id, prioridade, 
            status, data_atendimento, hora_chegada
        ) VALUES (:numero_senha, :paciente_id, :tipo_atendimento_id, :prioridade, 'aguardando', CURDATE(), NOW())";
        
        $params = [
            ':numero_senha' => $dados['numero_senha'],
            ':paciente_id' => intval($dados['paciente_id']),
            ':tipo_atendimento_id' => intval($dados['tipo_atendimento_id']),
            ':prioridade' => $dados['prioridade']
        ];
        
        return $this->db->insert($sql, $params);
    }
    
    /**
     * Listar fila de atendimento atual
     */
    public function listarFilaAtendimento() {
        $sql = "SELECT * FROM v_fila_atendimento ORDER BY prioridade ASC, hora_chegada ASC";
        return $this->db->select($sql);
    }
    
    /**
     * Marcar senha como atendida
     */
    public function marcarSenhaAtendida($senhaId, $atendenteResponsavel = null) {
        $sql = "UPDATE senhas_atendimento 
                SET status = 'atendido', 
                    hora_atendido = NOW(),
                    atendente_responsavel = :atendente,
                    tempo_espera = TIMESTAMPDIFF(MINUTE, hora_chegada, NOW())
                WHERE id = :id";
        
        $params = [
            ':atendente' => $atendenteResponsavel,
            ':id' => intval($senhaId)
        ];
        
        return $this->db->execute($sql, $params);
    }
    
    /**
     * Buscar agendamentos do paciente para hoje
     */
    public function buscarAgendamentosHoje($pacienteId, $tipoServico = null) {
        $sql = "SELECT * FROM v_agendamentos_hoje WHERE 1=1";
        $params = array();
        
        if ($pacienteId) {
            // Por enquanto busca todos, depois pode filtrar por paciente
            // $sql .= " AND paciente_id = %d";
            // $params[] = intval($pacienteId);
        }
        
        if ($tipoServico) {
            $sql .= " AND LOWER(tipo_atendimento) LIKE '%s'";
            $params[] = '%' . strtolower($tipoServico) . '%';
        }
        
        $sql .= " ORDER BY hora_agendamento ASC";
        
        if (!empty($params)) {
            return $this->db->select(vsprintf($sql, $params));
        } else {
            return $this->db->select($sql);
        }
    }
    
    /**
     * Inserir log do sistema
     */
    public function inserirLog($acao, $descricao = null, $pacienteId = null, $atendenteId = null) {
        $sql = "INSERT INTO logs_sistema (acao, descricao, paciente_id, atendente_id, ip_usuario, user_agent) 
                VALUES (:acao, :descricao, :paciente_id, :atendente_id, :ip, :user_agent)";
        
        $params = [
            ':acao' => $acao,
            ':descricao' => $descricao,
            ':paciente_id' => $pacienteId,
            ':atendente_id' => $atendenteId,
            ':ip' => $_SERVER['REMOTE_ADDR'],
            ':user_agent' => $_SERVER['HTTP_USER_AGENT']
        ];
        
        return $this->db->insert($sql, $params);
    }
    
    /**
     * Buscar tipos de atendimento
     */
    public function buscarTiposAtendimento() {
        $sql = "SELECT * FROM tipos_atendimento WHERE status = 'ativo' ORDER BY nome";
        return $this->db->select($sql);
    }
    
    /**
     * Buscar configuração do sistema
     */
    public function buscarConfiguracao($chave) {
        $sql = "SELECT valor FROM configuracoes WHERE chave = :chave";
        $resultado = $this->db->selectOne($sql, [':chave' => $chave]);
        return $resultado ? $resultado['valor'] : null;
    }
    
    /**
     * Fechar conexão
     */
    public function fechar() {
        $this->db->fechar();
    }
}

/**
 * Funções de utilidade para geração de senhas
 */
class SenhaUtil {
    private $dao;
    
    public function __construct() {
        $this->dao = new TotemDAO();
    }
    
    /**
     * Gerar próximo número de senha
     */
    public function gerarProximaSenha($tipoAtendimento) {
        // Mapear tipo para letra
        $letras = array(
            'consulta' => 'C',
            'exame' => 'E', 
            'terapia' => 'T',
            'atendimento-geral' => 'G',
            'agendar-consulta' => 'A',
            'resultados' => 'R',
            'informacoes' => 'I'
        );
        
        $letra = isset($letras[$tipoAtendimento]) ? $letras[$tipoAtendimento] : 'G';
        
        // Buscar último número usado hoje para esta letra
        $sql = "SELECT MAX(CAST(SUBSTRING(numero_senha, 2) AS UNSIGNED)) as ultimo_num
                FROM senhas_atendimento 
                WHERE numero_senha LIKE '{$letra}%' 
                AND data_atendimento = CURDATE()";
        
        $resultado = $this->dao->db->selectOne($sql);
        $proximoNumero = ($resultado && $resultado['ultimo_num']) ? $resultado['ultimo_num'] + 1 : 1;
        
        return sprintf('%s%02d', $letra, $proximoNumero);
    }
}

// Função global para obter instância do DAO
function obterDAO() {
    static $dao = null;
    if ($dao === null) {
        $dao = new TotemDAO();
    }
    return $dao;
}

// Função global para obter utilitário de senhas
function obterSenhaUtil() {
    static $util = null;
    if ($util === null) {
        $util = new SenhaUtil();
    }
    return $util;
}
?>
