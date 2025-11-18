<?php
/**
 * API para gerenciamento de atendimentos e agendamentos
 */

date_default_timezone_set('America/Sao_Paulo');

// Corrige timezone da sessão MySQL também
$con = @mysqli_connect('localhost', 'root', '', 'totem_saude');
if ($con) {
    $con->query("SET time_zone = '-03:00'");
    $con->close();
}

 
// Desabilita exibição de erros para não corromper o JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);
ob_start();

try {
    require_once '../config.php';
    header('Content-Type: application/json; charset=UTF-8');
    session_start();

    /**
     * Conexão com o banco de dados
     */
    function conectarBanco() {
    global $config_banco;

    $conexao = new mysqli(
        $config_banco['host'],
        $config_banco['usuario'],
        $config_banco['senha'],
        $config_banco['banco']
    );

    if ($conexao->connect_error) {
        http_response_code(500);
        die(json_encode(['sucesso' => false, 'mensagem' => 'Erro ao conectar: ' . $conexao->connect_error]));
    }

    $conexao->set_charset($config_banco['charset']);
    $conexao->query("SET time_zone = '-03:00'");

    return $conexao;
}


    // Determina ação (POST, GET ou JSON bruto)
    $acao = $_POST['acao'] ?? $_GET['acao'] ?? '';
    if (empty($acao)) {
        $json = file_get_contents('php://input');
        $dados = json_decode($json, true);
        if (isset($dados['acao'])) {
            $acao = $dados['acao'];
            $_POST = $dados;
        }
    }

    // Log básico
    $log = [
        'data_hora' => date('Y-m-d H:i:s'),
        'acao'      => $acao,
        'post'      => $_POST,
        'get'       => $_GET,
        'headers'   => function_exists('getallheaders') ? getallheaders() : []
    ];

    // Controle de ações
// Controle de ações
switch ($acao) {

    case 'listar_fila':
        listarFila();
        break;

    case 'chamar_paciente':
        $id = intval($_POST['id_paciente'] ?? 0);

        if ($id > 0) {
            // Atualiza no banco
            atualizarStatus($id, 'chamado');

            // Atualiza também na sessão (caso esteja usando a fila em memória)
            if (isset($_SESSION['fila_atendimento'])) {
                foreach ($_SESSION['fila_atendimento'] as $key => $paciente) {
                    if ($paciente['id'] == $id) {
                        $_SESSION['fila_atendimento'][$key]['status'] = 'chamado';
                        $_SESSION['fila_atendimento'][$key]['chamado_em'] = date('d/m/Y H:i:s');
                        break;
                    }
                }
            }
        } else {
            respostaErro('ID do paciente inválido');
        }
        break;

    case 'chamar_novamente':
        $id = intval($_POST['id_paciente'] ?? 0);
        if ($id > 0) {
            atualizarStatus($id, 'chamado');
            // Não precisa fazer echo aqui pois atualizarStatus já retorna o JSON
        } else {
            respostaErro('ID do paciente inválido');
        }
        break;

    case 'iniciar_atendimento':
        $id = intval($_POST['id_paciente'] ?? 0);
        if ($id > 0) {
            $conexao = conectarBanco();
            $conexao->begin_transaction();

            try {
                // Atualiza o status para 'atendido' e registra o horário
                $sql = "UPDATE atendimentos SET 
                    status = 'atendido',
                    atendido_em = NOW(),
                    hora_inicio_atendimento = NOW()
                WHERE id = ?";
                
                $stmt = $conexao->prepare($sql);
                $stmt->bind_param('i', $id);
                $stmt->execute();

                if ($stmt->affected_rows > 0) {
                    $conexao->commit();
                    echo json_encode(['sucesso' => true, 'mensagem' => 'Atendimento iniciado com sucesso']);
                } else {
                    throw new Exception('Não foi possível atualizar o status do atendimento');
                }

                // Atualiza também na sessão se existir
                if (isset($_SESSION['fila_atendimento'])) {
                    foreach ($_SESSION['fila_atendimento'] as $key => $paciente) {
                        if ($paciente['id'] == $id) {
                            $_SESSION['fila_atendimento'][$key]['status'] = 'atendido';
                            $_SESSION['fila_atendimento'][$key]['atendido_em'] = date('d/m/Y H:i:s');
                            $_SESSION['fila_atendimento'][$key]['hora_inicio_atendimento'] = date('d/m/Y H:i:s');
                            break;
                        }
                    }
                }

            } catch (Exception $e) {
                $conexao->rollback();
                respostaErro('Erro ao iniciar atendimento: ' . $e->getMessage());
            }

            $conexao->close();
        } else {
            respostaErro('ID do paciente inválido');
        }
        break;

    case 'marcar_atendido':
        $id = intval($_POST['id_paciente'] ?? 0);
        if ($id > 0) atualizarStatus($id, 'atendido');
        else respostaErro('ID do paciente inválido');
        break;

    case 'cancelar_chamada':
        $id = intval($_POST['id_paciente'] ?? 0);
        if ($id > 0) atualizarStatus($id, 'cancelar_chamada');
        else respostaErro('ID do paciente inválido');
        break;

    case 'adicionar_fila':
        adicionarFila();
        break;

    case 'confirmar_agendamento':
        confirmarAgendamento();
        break;

    case 'listar_agendamentos':
        listarAgendamentos();
        break;

    default:
        respostaErro('Ação inválida: ' . $acao);
        break;
}


} catch (Exception $e) {
    http_response_code(500);
    $resposta = [
        'sucesso' => false,
        'mensagem' => 'Erro interno no servidor',
        'erro' => [
            'mensagem' => $e->getMessage(),
            'arquivo'  => $e->getFile(),
            'linha'    => $e->getLine(),
        ]
    ];

    if (ob_get_level() > 0) ob_clean();
    echo json_encode($resposta, JSON_UNESCAPED_UNICODE);
    exit;
}

// Limpa qualquer saída extra
$output = ob_get_clean();
if (!empty($output)) echo $output;


/* ================================================================
   FUNÇÕES AUXILIARES
   ================================================================ */

function respostaErro($msg, $codigo = 400) {
    http_response_code($codigo);
    echo json_encode(['sucesso' => false, 'mensagem' => $msg]);
    exit;
}


/**
 * Lista a fila de atendimentos
 */
function listarFila() {
    date_default_timezone_set('America/Sao_Paulo');
    $conexao = conectarBanco();

    $sql = "
        SELECT 
            id, nome, tipo_atendimento, prioridade, 
            data_atendimento, hora_atendimento,
            DATE_FORMAT(data_atendimento, '%d/%m/%Y') AS data_formatada,
            TIME_FORMAT(hora_atendimento, '%H:%i') AS hora_formatada,
            status
        FROM atendimentos 
        WHERE status IN ('aguardando','chamado')
        ORDER BY 
            CASE 
                WHEN status = 'chamado' THEN 0
                WHEN prioridade = 'Preferencial' THEN 1
                ELSE 2
            END,
            data_criacao ASC
    ";

    $resultado = $conexao->query($sql);
    if (!$resultado) respostaErro('Erro: ' . $conexao->error, 500);

    $fila = [];
    while ($r = $resultado->fetch_assoc()) {
        $dataHora = new DateTime($r['data_atendimento'] . ' ' . $r['hora_atendimento'], new DateTimeZone('America/Sao_Paulo'));
        $timestamp = $dataHora->getTimestamp();

        $fila[] = [
            'id' => $r['id'],
            'nome' => $r['nome'],
            'tipo_atendimento' => $r['tipo_atendimento'],
            'prioridade' => $r['prioridade'],
            'data' => $r['data_formatada'],
            'hora' => $r['hora_formatada'],
            'status' => $r['status'],
            'timestamp' => $timestamp
        ];
    }

    echo json_encode(['sucesso' => true, 'fila' => $fila]);
    $conexao->close();
}



/**
 * Atualiza status do paciente
 */
function atualizarStatus($id, $status) {
    $conexao = conectarBanco();
    
    // Inicia uma transação para garantir a integridade dos dados
    $conexao->begin_transaction();
    
    try {
        // Se estiver marcando como 'chamado', registra o horário da chamada
        if ($status === 'chamado') {
            // Primeiro, verifica se o paciente existe
            $check = $conexao->prepare("SELECT status FROM atendimentos WHERE id = ?");
            $check->bind_param('i', $id);
            $check->execute();
            $result = $check->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('Paciente não encontrado');
            }
            
            $paciente = $result->fetch_assoc();
            
            // Atualiza o status para 'chamado' e define a data/hora da chamada
            $stmt = $conexao->prepare("UPDATE atendimentos SET status = 'chamado', chamado_em = NOW() WHERE id = ?");
            $stmt->bind_param('i', $id);
            
            $check->close();
        } 
        // Se estiver confirmando atendimento, verifica se já foi chamado
        elseif ($status === 'em_atendimento') {
            // Verifica se o paciente foi chamado a menos de 5 minutos atrás
            $stmt = $conexao->prepare("UPDATE atendimentos SET status = 'em_atendimento', atendido_em = NOW() 
                                     WHERE id = ? AND (status = 'chamado' OR status = 'em_atendimento') AND chamado_em >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
            $stmt->bind_param('i', $id);
            
            if (!$stmt->execute() || $stmt->affected_rows === 0) {
                throw new Exception('Paciente não foi chamado recentemente ou o tempo para atendimento expirou. Por favor, chame o paciente novamente.');
            }
            
            $conexao->commit();
            
            // Limpa qualquer saída anterior
            if (ob_get_level() > 0) ob_clean();
            
            echo json_encode(['sucesso' => true]);
            $stmt->close();
            $conexao->close();
            return;
        }
        // Para cancelar chamada, volta para 'aguardando'
        elseif ($status === 'cancelar_chamada') {
            $stmt = $conexao->prepare("UPDATE atendimentos SET status = 'aguardando', chamado_em = NULL WHERE id = ?");
            $stmt->bind_param('i', $id);
        }
        // Para outros status (como 'atendido')
        else {
            $stmt = $conexao->prepare("UPDATE atendimentos SET status = ? WHERE id = ?");
            $stmt->bind_param('si', $status, $id);
        }
        
        if (!$stmt->execute()) {
            throw new Exception('Erro ao atualizar status: ' . $stmt->error);
        }
        
        $conexao->commit();
        
        // Limpa qualquer saída anterior
        if (ob_get_level() > 0) ob_clean();
        
        echo json_encode(['sucesso' => true]);
        
    } catch (Exception $e) {
        $conexao->rollback();
        respostaErro($e->getMessage(), 400);
        if (isset($stmt)) $stmt->close();
        $conexao->close();
        return;
    }
    
    if (isset($stmt)) $stmt->close();
    $conexao->close();
}


/**
 * Adiciona um paciente à fila, evitando duplicidade
 */
function adicionarFila() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respostaErro('Método não permitido', 405);
    }

    $json = file_get_contents('php://input');
    $dados = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) $dados = $_POST;

    if (isset($dados['dados']) && is_string($dados['dados'])) {
        $extra = json_decode($dados['dados'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $dados = array_merge($dados, $extra);
        }
    }

    $nome = trim($dados['nome'] ?? '');
    $tipo = trim($dados['tipo_atendimento'] ?? '');
    $prioridade = trim($dados['prioridade'] ?? '');
    $cpf = preg_replace('/\D/', '', $dados['cpf'] ?? ''); // 👈 novo campo opcional

    if (empty($nome) || empty($tipo) || empty($prioridade)) {
        respostaErro('Dados incompletos para adicionar à fila.');
    }

    $conexao = conectarBanco();

    // 🔎 Verifica se o paciente já está na fila e ainda não foi atendido
    $sqlCheck = "
        SELECT id, nome, status 
        FROM atendimentos 
        WHERE nome = ?
         AND status NOT IN ('atendido', 'cancelado')

        LIMIT 1
    ";
    $stmt = $conexao->prepare($sqlCheck);
    $stmt->bind_param('s', $nome);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $existe = $resultado->fetch_assoc();
    $stmt->close();

    if ($existe) {
        echo json_encode([
            'sucesso' => false,
            'mensagem' => "Você já possui uma senha em andamento. Aguarde ser chamado(a) no painel."
        ]);
        $conexao->close();
        return;
    }

    // ✅ Se não existe, adiciona à fila normalmente
    $stmt = $conexao->prepare("
        INSERT INTO atendimentos (nome, tipo_atendimento, prioridade, cpf, data_atendimento, hora_atendimento, status)
VALUES (?, ?, ?, ?, DATE(NOW()), TIME(NOW()), 'aguardando')


    ");
    $stmt->bind_param('ssss', $nome, $tipo, $prioridade, $cpf);

    if ($stmt->execute()) {
        $id = $conexao->insert_id;
        $res = $conexao->query("
            SELECT id, nome, tipo_atendimento, prioridade,
                   DATE_FORMAT(data_atendimento, '%d/%m/%Y') AS data_formatada,
                   TIME_FORMAT(hora_atendimento, '%H:%i') AS hora_formatada,
                   status, data_criacao AS timestamp
            FROM atendimentos WHERE id = {$id}
        ");
        $paciente = $res->fetch_assoc();

        echo json_encode([
            'sucesso' => true,
            'paciente' => [
                'id' => $paciente['id'],
                'nome' => $paciente['nome'],
                'tipo_atendimento' => $paciente['tipo_atendimento'],
                'prioridade' => $paciente['prioridade'],
                'data' => $paciente['data_formatada'],
                'hora' => $paciente['hora_formatada'],
                'status' => $paciente['status'],
                'timestamp' => strtotime($r['data_atendimento'] . ' ' . $r['hora_atendimento']) - (3 * 3600)


            ]
        ]);
    } else {
        respostaErro('Erro ao adicionar paciente: ' . $stmt->error, 500);
    }

    $stmt->close();
    $conexao->close();
}



/**
 * 🩺 Lista agendamentos reais do paciente (Consulta/Exame/Terapia)
 */
function listarAgendamentos() {
    $cpf = preg_replace('/\D/', '', $_GET['cpf'] ?? '');
    if (empty($cpf)) {
        respostaErro('CPF não informado.');
    }

    $conexao = conectarBanco();

    $sql = "
        SELECT 
            a.id,
            ta.nome AS type,
            p.nome AS paciente,
            pr.nome AS profissional,
            pr.especialidade,
            IFNULL(s.descricao, s.numero) AS local,
            TIME_FORMAT(a.hora_agendamento, '%H:%i') AS horario,
            a.status,
            DATE_FORMAT(a.data_agendamento, '%d/%m/%Y') AS data
        FROM agendamentos a
        INNER JOIN pacientes p ON a.paciente_id = p.id
        LEFT JOIN profissionais pr ON a.profissional_id = pr.id
        LEFT JOIN salas s ON a.sala_id = s.id
        INNER JOIN tipos_atendimento ta ON a.tipo_atendimento_id = ta.id
        WHERE REPLACE(REPLACE(p.cpf, '.', ''), '-', '') = ?
          AND a.data_agendamento = CURDATE()
          AND a.status IN ('agendado','confirmado')
        ORDER BY a.hora_agendamento ASC
    ";

    $stmt = $conexao->prepare($sql);
    $stmt->bind_param('s', $cpf);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $agendamentos = [];
    while ($r = $resultado->fetch_assoc()) {
        $agendamentos[] = [
            'id' => $r['id'],
            'type' => $r['type'],
            'profissional' => $r['profissional'] ?: '—',
            'especialidade' => $r['especialidade'] ?: '—',
            'horario' => $r['horario'],
            'local' => $r['local'] ?: '—',
            'data' => $r['data'],
            'status' => $r['status']
        ];
    }

    echo json_encode(['sucesso' => true, 'agendamentos' => $agendamentos], JSON_UNESCAPED_UNICODE);
    $stmt->close();
    $conexao->close();
}


/**
 * Confirma um agendamento (sem adicionar à fila de atendentes)
 */
function confirmarAgendamento() {
    $conexao = conectarBanco();

    $agendamento_id = intval($_POST['agendamento_id'] ?? 0);
    if ($agendamento_id <= 0) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'ID de agendamento inválido']);
        return;
    }

    // Busca dados do agendamento
    $sql = "
        SELECT 
            a.id AS agendamento_id,
            p.nome AS paciente,
            ta.nome AS tipo_atendimento,
            pr.nome AS profissional,
            IFNULL(s.descricao, s.numero) AS sala
        FROM agendamentos a
        INNER JOIN pacientes p ON a.paciente_id = p.id
        LEFT JOIN profissionais pr ON a.profissional_id = pr.id
        LEFT JOIN salas s ON a.sala_id = s.id
        INNER JOIN tipos_atendimento ta ON a.tipo_atendimento_id = ta.id
        WHERE a.id = ?
    ";

    $stmt = $conexao->prepare($sql);
    $stmt->bind_param('i', $agendamento_id);
    $stmt->execute();
    $dados = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$dados) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Agendamento não encontrado']);
        return;
    }

    // Atualiza status do agendamento para "confirmado"
    $update = $conexao->prepare("UPDATE agendamentos SET status = 'confirmado' WHERE id = ?");
    $update->bind_param('i', $agendamento_id);
    $update->execute();
    $update->close();

    // ✅ NÃO adiciona o paciente à fila (não insere em atendimentos)
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Check-in confirmado. Aguarde o atendimento na sala.',
        'paciente' => [
            'nome' => $dados['paciente'],
            'tipo_atendimento' => $dados['tipo_atendimento'],
            'profissional' => $dados['profissional'] ?: '—',
            'sala' => $dados['sala'] ?: '—'
        ]
    ]);
}

?>
