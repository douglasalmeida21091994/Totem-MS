<?php
/**
 * Arquivo de Integração do Banco de Dados
 * Este arquivo adapta o sistema atual para usar o banco de dados
 * Mantém compatibilidade com PHP 5.4
 */

// Verificar se as classes do banco estão disponíveis
if (!class_exists('Database')) {
    require_once 'database.php';
}

/**
 * Adaptar função de autenticação de paciente para usar banco
 */
function autenticarPacienteBanco($cpf) {
    $dao = obterDAO();
    
    // Buscar paciente no banco
    $paciente = $dao->buscarPacientePorCPF($cpf);
    
    if ($paciente) {
        // Converter dados do banco para formato esperado pelo sistema
        $dadosPaciente = array(
            'nome' => $paciente['nome'],
            'matricula' => $paciente['matricula'],
            'cpf' => formatarCPF($paciente['cpf']),
            'data_nascimento' => date('d/m/Y', strtotime($paciente['data_nascimento'])),
            'contratos' => array(
                array(
                    'familia' => array(
                        array('data_de_nascimento' => date('d/m/Y', strtotime($paciente['data_nascimento'])))
                    )
                )
            )
        );
        
        // Log da autenticação
        $dao->inserirLog('AUTENTICACAO', 'Paciente autenticado via CPF', $paciente['id']);
        
        return array('sucesso' => true, 'dados' => $dadosPaciente);
    }
    
    return array('sucesso' => false, 'mensagem' => 'Paciente não encontrado');
}

/**
 * Adaptar função de geração de senha para usar banco
 */
function gerarSenhaBanco($tipoAtendimento, $prioridade, $pacienteId) {
    $dao = obterDAO();
    $senhaUtil = obterSenhaUtil();
    
    // Buscar tipo de atendimento no banco
    $tipos = $dao->buscarTiposAtendimento();
    $tipoId = 1; // Default para "Atendimento Geral"
    
    foreach ($tipos as $tipo) {
        if (strtolower($tipo['nome']) == strtolower($tipoAtendimento)) {
            $tipoId = $tipo['id'];
            break;
        }
    }
    
    // Gerar número da senha
    $numeroSenha = $senhaUtil->gerarProximaSenha($tipoAtendimento);
    
    // Inserir no banco
    $senhaData = array(
        'numero_senha' => $numeroSenha,
        'paciente_id' => $pacienteId,
        'tipo_atendimento_id' => $tipoId,
        'prioridade' => $prioridade
    );
    
    $senhaId = $dao->inserirSenhaAtendimento($senhaData);
    
    if ($senhaId) {
        // Buscar dados do paciente para resposta
        $paciente = $dao->db->selectOne("SELECT nome FROM pacientes WHERE id = " . intval($pacienteId));
        $nomePaciente = $paciente ? $paciente['nome'] : 'Paciente';
        
        $senha = array(
            'numero' => $numeroSenha,
            'tipo' => $tipoAtendimento,
            'prioridade' => $prioridade,
            'data' => obterDataAtual(),
            'hora' => obterHoraAtual(),
            'paciente' => $nomePaciente
        );
        
        // Log da geração de senha
        $dao->inserirLog('GERACAO_SENHA', 'Nova senha gerada: ' . $numeroSenha, $pacienteId);
        
        return array('sucesso' => true, 'senha' => $senha);
    }
    
    return array('sucesso' => false, 'mensagem' => 'Erro ao gerar senha');
}

/**
 * Adaptar função de listar agendamentos para usar banco
 */
function listarAgendamentosBanco($tipoServico = null, $pacienteId = null) {
    $dao = obterDAO();
    
    $agendamentos = $dao->buscarAgendamentosHoje($pacienteId, $tipoServico);
    
    $agendamentosFormatados = array();
    foreach ($agendamentos as $ag) {
        $agendamentosFormatados[] = array(
            'id' => $ag['id'],
            'tipo' => $ag['tipo_atendimento'],
            'profissional' => $ag['nome_profissional'] ? $ag['nome_profissional'] : 'Não definido',
            'especialidade' => $ag['especialidade'] ? $ag['especialidade'] : $ag['tipo_atendimento'],
            'horario' => date('H:i', strtotime($ag['hora_agendamento'])),
            'sala' => $ag['sala'] ? $ag['sala'] : 'A definir'
        );
    }
    
    return $agendamentosFormatados;
}

/**
 * Migrar dados de sessão para banco (opcional)
 */
function sincronizarFilaComBanco() {
    if (!isset($_SESSION['fila_atendimento']) || empty($_SESSION['fila_atendimento'])) {
        return;
    }
    
    $dao = obterDAO();
    
    // Buscar fila atual do banco
    $filaBanco = $dao->listarFilaAtendimento();
    
    // Converter para formato do sistema
    $filaFormatada = array();
    foreach ($filaBanco as $item) {
        $filaFormatada[] = array(
            'id' => $item['id'],
            'nome' => $item['nome_paciente'],
            'tipo_atendimento' => $item['tipo_atendimento'],
            'prioridade' => ucfirst($item['prioridade']),
            'data' => date('d/m/Y', strtotime($item['data_atendimento'])),
            'hora' => date('H:i:s', strtotime($item['hora_chegada'])),
            'status' => $item['status'],
            'timestamp' => strtotime($item['data_atendimento'] . ' ' . $item['hora_chegada'])
        );
    }
    
    // Atualizar sessão com dados do banco
    $_SESSION['fila_atendimento'] = $filaFormatada;
}

/**
 * Verificar se o banco está configurado e funcionando
 */
function verificarBancoConfigurado() {
    try {
        $dao = obterDAO();
        $resultado = $dao->db->selectOne("SELECT 1 as teste");
        return ($resultado && $resultado['teste'] == 1);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Atualizar paciente na sessão com dados do banco
 */
function atualizarPacienteSessaoDoBanco($cpf) {
    $resultado = autenticarPacienteBanco($cpf);
    
    if ($resultado['sucesso']) {
        $_SESSION['paciente'] = $resultado['dados'];
        
        // Adicionar ID do banco para uso posterior
        $dao = obterDAO();
        $pacienteBanco = $dao->buscarPacientePorCPF($cpf);
        if ($pacienteBanco) {
            $_SESSION['paciente']['id_banco'] = $pacienteBanco['id'];
        }
        
        return true;
    }
    
    return false;
}

// Verificar se deve usar banco ou sistema atual
$usarBanco = file_exists('instalado.txt') && verificarBancoConfigurado();

if ($usarBanco) {
    // Substituir funções originais se banco estiver funcionando
    if (!function_exists('autenticarPacienteOriginal')) {
        // Salvar referência às funções originais se necessário
        // As funções do index.php serão adaptadas diretamente
    }
}
?>
