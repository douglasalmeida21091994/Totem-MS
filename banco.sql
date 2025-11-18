

-- ============================================
-- TABELA DE PACIENTES
-- ============================================
CREATE TABLE IF NOT EXISTS `pacientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `cpf` varchar(14) NOT NULL UNIQUE,
  `data_nascimento` date DEFAULT NULL,
  `matricula` varchar(50) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `endereco` text DEFAULT NULL,
  `status` enum('ativo','inativo') DEFAULT 'ativo',
  `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cpf` (`cpf`),
  KEY `idx_matricula` (`matricula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================
-- TABELA DE PROFISSIONAIS/MÉDICOS
-- ============================================
CREATE TABLE IF NOT EXISTS `profissionais` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `especialidade` varchar(255) NOT NULL,
  `crm` varchar(20) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `status` enum('ativo','inativo') DEFAULT 'ativo',
  `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_especialidade` (`especialidade`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================
-- TABELA DE SALAS
-- ============================================
CREATE TABLE IF NOT EXISTS `salas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero` varchar(10) NOT NULL,
  `tipo` enum('consulta','exame','terapia','laboratorio') DEFAULT 'consulta',
  `descricao` varchar(255) DEFAULT NULL,
  `status` enum('ativo','inativo') DEFAULT 'ativo',
  `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_numero` (`numero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================
-- TABELA DE TIPOS DE ATENDIMENTO
-- ============================================
CREATE TABLE IF NOT EXISTS `tipos_atendimento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `prioridade_padrao` enum('normal','preferencial') DEFAULT 'normal',
  `tempo_medio_atendimento` int(11) DEFAULT 15 COMMENT 'em minutos',
  `status` enum('ativo','inativo') DEFAULT 'ativo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================
-- TABELA DE AGENDAMENTOS
-- ============================================
CREATE TABLE IF NOT EXISTS `agendamentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` int(11) NOT NULL,
  `profissional_id` int(11) DEFAULT NULL,
  `sala_id` int(11) DEFAULT NULL,
  `tipo_atendimento_id` int(11) NOT NULL,
  `data_agendamento` date NOT NULL,
  `hora_agendamento` time NOT NULL,
  `status` enum('agendado','confirmado','cancelado','realizado','falta') DEFAULT 'agendado',
  `observacoes` text DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_paciente` (`paciente_id`),
  KEY `idx_profissional` (`profissional_id`),
  KEY `idx_sala` (`sala_id`),
  KEY `idx_data_hora` (`data_agendamento`, `hora_agendamento`),
  KEY `idx_status` (`status`),
  FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`profissional_id`) REFERENCES `profissionais` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`sala_id`) REFERENCES `salas` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`tipo_atendimento_id`) REFERENCES `tipos_atendimento` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================
-- TABELA DE SENHAS/FILA DE ATENDIMENTO
-- ============================================
CREATE TABLE IF NOT EXISTS `senhas_atendimento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_senha` varchar(10) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `tipo_atendimento_id` int(11) NOT NULL,
  `prioridade` enum('normal','preferencial') DEFAULT 'normal',
  `status` enum('aguardando','chamado','atendido','cancelado') DEFAULT 'aguardando',
  `data_atendimento` date NOT NULL,
  `hora_chegada` time NOT NULL,
  `hora_chamado` time DEFAULT NULL,
  `hora_atendido` time DEFAULT NULL,
  `tempo_espera` int(11) DEFAULT NULL COMMENT 'em minutos',
  `atendente_responsavel` varchar(255) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_numero_senha` (`numero_senha`),
  KEY `idx_paciente` (`paciente_id`),
  KEY `idx_status` (`status`),
  KEY `idx_data_status` (`data_atendimento`, `status`),
  KEY `idx_prioridade` (`prioridade`),
  FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tipo_atendimento_id`) REFERENCES `tipos_atendimento` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================
-- TABELA DE ATENDIMENTOS (alternativa/atualizada)
-- ============================================
CREATE TABLE IF NOT EXISTS `atendimentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` int(11) NOT NULL,
  `tipo_atendimento_id` int(11) NOT NULL,
  `prioridade` enum('normal','preferencial') DEFAULT 'normal',
  `status` enum('aguardando','chamado','atendido','cancelado') DEFAULT 'aguardando',
  `numero_senha` varchar(10) DEFAULT NULL,
  `data_atendimento` date DEFAULT NULL,
  `hora_chegada` time DEFAULT NULL,
  `hora_chamado` time DEFAULT NULL,
  `hora_atendido` time DEFAULT NULL,
  `tempo_espera` int(11) DEFAULT NULL,
  `atendente_responsavel` varchar(255) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_paciente` (`paciente_id`),
  KEY `idx_tipo_atendimento` (`tipo_atendimento_id`),
  KEY `idx_data_status` (`data_atendimento`, `status`),
  FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tipo_atendimento_id`) REFERENCES `tipos_atendimento` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================
-- TABELA DE CHECK-INS
-- ============================================
CREATE TABLE IF NOT EXISTS `checkins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agendamento_id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `data_checkin` date NOT NULL,
  `hora_checkin` time NOT NULL,
  `status` enum('confirmado','falta') DEFAULT 'confirmado',
  `observacoes` text DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_agendamento` (`agendamento_id`),
  KEY `idx_paciente` (`paciente_id`),
  KEY `idx_data_checkin` (`data_checkin`),
  FOREIGN KEY (`agendamento_id`) REFERENCES `agendamentos` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================
-- TABELA DE ATENDENTES
-- ============================================
CREATE TABLE IF NOT EXISTS `atendentes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `usuario` varchar(50) NOT NULL UNIQUE,
  `senha` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `nivel_acesso` enum('operador','supervisor','admin') DEFAULT 'operador',
  `status` enum('ativo','inativo') DEFAULT 'ativo',
  `ultimo_login` timestamp NULL DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================
-- TABELA DE LOGS DO SISTEMA
-- ============================================
CREATE TABLE IF NOT EXISTS `logs_sistema` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `acao` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `paciente_id` int(11) DEFAULT NULL,
  `atendente_id` int(11) DEFAULT NULL,
  `ip_usuario` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `data_acao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_acao` (`acao`),
  KEY `idx_data_acao` (`data_acao`),
  KEY `idx_paciente` (`paciente_id`),
  KEY `idx_atendente` (`atendente_id`),
  FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`atendente_id`) REFERENCES `atendentes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================
-- TABELA DE CONFIGURAÇÕES DO SISTEMA
-- ============================================
CREATE TABLE IF NOT EXISTS `configuracoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chave` varchar(100) NOT NULL UNIQUE,
  `valor` text DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `categoria` varchar(50) DEFAULT 'geral',
  `data_atualizacao` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_chave` (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================
-- DADOS INICIAIS
-- ============================================

-- Inserir tipos de atendimento padrão
INSERT IGNORE INTO `tipos_atendimento` (`nome`, `descricao`, `prioridade_padrao`, `tempo_medio_atendimento`) VALUES
('Consulta', 'Consulta médica geral', 'normal', 30),
('Exame', 'Realização de exames', 'normal', 20),
('Terapia', 'Sessões de terapia', 'normal', 45),
('Atendimento Geral', 'Atendimento administrativo geral', 'normal', 15),
('Agendar Consulta', 'Marcação de consultas', 'normal', 10),
('Resultados de Exames', 'Retirada de resultados', 'normal', 5),
('Informações e Suporte', 'Esclarecimentos e informações', 'normal', 10);

-- Inserir salas padrão
INSERT IGNORE INTO `salas` (`numero`, `tipo`, `descricao`) VALUES
('Sala 1', 'consulta', 'Sala de consulta médica'),
('Sala 2', 'consulta', 'Sala de consulta médica'),
('Sala 3', 'consulta', 'Sala de consulta médica'),
('Sala 4', 'exame', 'Sala de exames'),
('Sala 5', 'terapia', 'Sala de terapia'),
('Laboratório', 'laboratorio', 'Laboratório de análises');

-- Inserir profissionais padrão
INSERT IGNORE INTO `profissionais` (`nome`, `especialidade`, `crm`) VALUES
('Dra. Ana Silva', 'Cardiologia', 'CRM-12345'),
('Dr. Bruno Lima', 'Clínico Geral', 'CRM-67890'),
('Dr. Carlos Mendes', 'Fisioterapia', 'CREF-11111'),
('Dra. Paula Rocha', 'Psicologia', 'CRP-22222'),
('Laboratório', 'Diagnóstico por Imagem', NULL);

-- Inserir atendente padrão (senha: admin123)
INSERT IGNORE INTO `atendentes` (`nome`, `usuario`, `senha`, `email`, `nivel_acesso`) VALUES
('Administrador', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@clinica.com', 'admin');

-- Inserir configurações padrão
INSERT IGNORE INTO `configuracoes` (`chave`, `valor`, `descricao`, `categoria`) VALUES
('nome_clinica', 'Clínica Mais Saúde', 'Nome da clínica', 'geral'),
('tempo_timeout', '600', 'Tempo de timeout em segundos', 'sistema'),
('url_api_smile', 'https://ws.smilesaude.com.br/api/', 'URL da API Smile Saúde', 'integracoes'),
('token_biodoc', '', 'Token de autenticação BioDoc', 'integracoes'),
('horario_funcionamento', '08:00-18:00', 'Horário de funcionamento da clínica', 'geral'),
('mensagem_totem', 'Seja bem-vindo(a) ao nosso sistema de autoatendimento!', 'Mensagem de boas-vindas', 'geral');

-- Inserir paciente de exemplo
INSERT IGNORE INTO `pacientes` (`nome`, `cpf`, `data_nascimento`, `matricula`) VALUES
('João Silva', '123.456.789-01', '1980-03-12', '123456'),
('Maria Santos', '987.654.321-00', '1992-07-25', '789012'),
('Mateus Marques Da Silva', '111.222.333-44', '1985-05-15', '555666');

-- ============================================
-- VIEWS PARA CONSULTAS
-- ============================================

-- View para fila de atendimento atual
CREATE OR REPLACE VIEW `v_fila_atendimento` AS
SELECT 
    sa.id,
    sa.numero_senha,
    sa.prioridade,
    sa.status,
    sa.data_atendimento,
    sa.hora_chegada,
    sa.hora_chamado,
    sa.hora_atendido,
    sa.tempo_espera,
    p.nome as nome_paciente,
    p.cpf,
    ta.nome as tipo_atendimento
FROM senhas_atendimento sa
INNER JOIN pacientes p ON sa.paciente_id = p.id
INNER JOIN tipos_atendimento ta ON sa.tipo_atendimento_id = ta.id
WHERE sa.data_atendimento = CURDATE() 
AND sa.status IN ('aguardando', 'chamado')
ORDER BY 
    CASE WHEN sa.prioridade = 'preferencial' THEN 1 ELSE 2 END,
    sa.hora_chegada ASC;

-- View para agendamentos do dia
CREATE OR REPLACE VIEW `v_agendamentos_hoje` AS
SELECT 
    a.id,
    a.data_agendamento,
    a.hora_agendamento,
    a.status,
    p.nome as nome_paciente,
    p.cpf,
    pr.nome as nome_profissional,
    pr.especialidade,
    s.numero as sala,
    ta.nome as tipo_atendimento
FROM agendamentos a
INNER JOIN pacientes p ON a.paciente_id = p.id
LEFT JOIN profissionais pr ON a.profissional_id = pr.id
LEFT JOIN salas s ON a.sala_id = s.id
INNER JOIN tipos_atendimento ta ON a.tipo_atendimento_id = ta.id
WHERE a.data_agendamento = CURDATE()
ORDER BY a.hora_agendamento ASC;

-- ============================================
-- PROCEDURES E FUNCTIONS
-- ============================================

DELIMITER //

-- Function para gerar próximo número de senha
CREATE OR REPLACE FUNCTION gerar_proxima_senha(p_tipo CHAR(1))
RETURNS VARCHAR(10)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE proximo_numero INT DEFAULT 1;
    DECLARE senha_gerada VARCHAR(10);
    DECLARE letra CHAR(1);
    
    -- Define a letra baseada no tipo (C=Consulta, E=Exame, T=Terapia, G=Geral)
    SET letra = CASE p_tipo
        WHEN 'C' THEN 'C'
        WHEN 'E' THEN 'E' 
        WHEN 'T' THEN 'T'
        ELSE 'G'
    END;
    
    -- Busca o próximo número disponível para hoje
    SELECT COALESCE(MAX(CAST(SUBSTRING(numero_senha, 2) AS UNSIGNED)), 0) + 1
    INTO proximo_numero
    FROM senhas_atendimento 
    WHERE numero_senha LIKE CONCAT(letra, '%') 
    AND data_atendimento = CURDATE();
    
    SET senha_gerada = CONCAT(letra, LPAD(proximo_numero, 2, '0'));
    
    RETURN senha_gerada;
END //

-- Procedure para limpar dados antigos (manter apenas últimos 30 dias)
CREATE OR REPLACE PROCEDURE limpar_dados_antigos()
BEGIN
    DELETE FROM logs_sistema WHERE data_acao < DATE_SUB(NOW(), INTERVAL 30 DAY);
    DELETE FROM senhas_atendimento WHERE data_atendimento < DATE_SUB(NOW(), INTERVAL 30 DAY) AND status = 'atendido';
END //

DELIMITER ;

-- ============================================
-- TRIGGERS PARA AUDITORIA
-- ============================================

DELIMITER //

-- Trigger para log de inserção de senhas
DROP TRIGGER IF EXISTS tr_senhas_atendimento_insert //
CREATE TRIGGER tr_senhas_atendimento_insert 
AFTER INSERT ON senhas_atendimento
FOR EACH ROW
BEGIN
    INSERT INTO logs_sistema (acao, descricao, paciente_id, data_acao) 
    VALUES (
        'NOVA_SENHA', 
        CONCAT('Nova senha gerada: ', NEW.numero_senha, ' - Tipo: ', NEW.tipo_atendimento_id),
        NEW.paciente_id,
        NOW()
    );
END //

-- Trigger para log de atualização de senhas (status)
DROP TRIGGER IF EXISTS tr_senhas_atendimento_update //
CREATE TRIGGER tr_senhas_atendimento_update 
AFTER UPDATE ON senhas_atendimento
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO logs_sistema (acao, descricao, paciente_id, data_acao) 
        VALUES (
            'MUDANCA_STATUS_SENHA',
            CONCAT('Senha ', NEW.numero_senha, ' mudou de ', OLD.status, ' para ', NEW.status),
            NEW.paciente_id,
            NOW()
        );
    END IF;
END //

DELIMITER ;

-- ============================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- ============================================

-- Índices compostos para melhor performance
CREATE INDEX IF NOT EXISTS idx_senhas_data_status_prioridade ON senhas_atendimento (data_atendimento, status, prioridade);
CREATE INDEX IF NOT EXISTS idx_agendamentos_data_status ON agendamentos (data_agendamento, status);
CREATE INDEX IF NOT EXISTS idx_logs_data_acao ON logs_sistema (data_acao);

-- ============================================
-- FIM DO SCRIPT DE CRIAÇÃO DO BANCO DE DADOS
-- ============================================