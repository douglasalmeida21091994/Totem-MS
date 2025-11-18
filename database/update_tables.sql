-- Atualização do banco - Totem Mais Saúde
-- Cria a tabela de atendimentos caso não exista

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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SELECT 'Atualização de banco concluída com sucesso!' AS mensagem;
