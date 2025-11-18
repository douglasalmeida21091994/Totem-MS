# 📊 Estrutura do Banco de Dados - Totem Auto Atendimento

## 🚀 Instalação Rápida

### 1. Configuração
Edite o arquivo `config.php` com suas credenciais de banco:
```php
$config_banco = array(
    'host' => 'localhost',
    'usuario' => 'seu_usuario',
    'senha' => 'sua_senha',
    'banco' => 'totem_saude'
);
```

### 2. Instalação Automática
Acesse: `http://localhost/install.php`

### 3. Instalação Manual
Execute o arquivo `database.sql` no seu MySQL.

---

## 📋 Estrutura das Tabelas

### 👥 **pacientes**
Armazena dados dos pacientes do sistema.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | INT | Chave primária |
| `nome` | VARCHAR(255) | Nome completo |
| `cpf` | VARCHAR(14) | CPF formatado |
| `data_nascimento` | DATE | Data de nascimento |
| `matricula` | VARCHAR(50) | Matrícula do paciente |
| `telefone` | VARCHAR(20) | Telefone de contato |
| `email` | VARCHAR(255) | Email |
| `endereco` | TEXT | Endereço completo |
| `status` | ENUM | ativo/inativo |

### 🏥 **profissionais**
Cadastro dos profissionais (médicos, terapeutas, etc.).

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | INT | Chave primária |
| `nome` | VARCHAR(255) | Nome do profissional |
| `especialidade` | VARCHAR(255) | Especialidade médica |
| `crm` | VARCHAR(20) | CRM/CREF |
| `telefone` | VARCHAR(20) | Telefone |
| `email` | VARCHAR(255) | Email |

### 🏢 **salas**
Cadastro das salas de atendimento.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | INT | Chave primária |
| `numero` | VARCHAR(10) | Número da sala |
| `tipo` | ENUM | consulta/exame/terapia/laboratorio |
| `descricao` | VARCHAR(255) | Descrição da sala |

### 📝 **tipos_atendimento**
Tipos de atendimento disponíveis no totem.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | INT | Chave primária |
| `nome` | VARCHAR(100) | Nome do tipo |
| `descricao` | TEXT | Descrição detalhada |
| `prioridade_padrao` | ENUM | normal/preferencial |
| `tempo_medio_atendimento` | INT | Tempo em minutos |

### 📅 **agendamentos**
Agendamentos de consultas e exames.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | INT | Chave primária |
| `paciente_id` | INT | FK para pacientes |
| `profissional_id` | INT | FK para profissionais |
| `sala_id` | INT | FK para salas |
| `tipo_atendimento_id` | INT | FK para tipos_atendimento |
| `data_agendamento` | DATE | Data agendada |
| `hora_agendamento` | TIME | Horário agendado |
| `status` | ENUM | agendado/confirmado/cancelado/realizado/falta |

### 🎫 **senhas_atendimento**
Senhas geradas pelo totem.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | INT | Chave primária |
| `numero_senha` | VARCHAR(10) | Número da senha |
| `paciente_id` | INT | FK para pacientes |
| `tipo_atendimento_id` | INT | FK para tipos_atendimento |
| `prioridade` | ENUM | normal/preferencial |
| `status` | ENUM | aguardando/chamado/atendido/cancelado |
| `data_atendimento` | DATE | Data do atendimento |
| `hora_chegada` | TIME | Hora que chegou |
| `hora_chamado` | TIME | Hora que foi chamado |
| `hora_atendido` | TIME | Hora que foi atendido |
| `tempo_espera` | INT | Tempo de espera em minutos |

### ✅ **checkins**
Check-ins realizados pelos pacientes.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | INT | Chave primária |
| `agendamento_id` | INT | FK para agendamentos |
| `paciente_id` | INT | FK para pacientes |
| `data_checkin` | DATE | Data do check-in |
| `hora_checkin` | TIME | Hora do check-in |
| `status` | ENUM | confirmado/falta |

### 👨‍💼 **atendentes**
Usuários do sistema (recepção).

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | INT | Chave primária |
| `nome` | VARCHAR(255) | Nome do atendente |
| `usuario` | VARCHAR(50) | Login |
| `senha` | VARCHAR(255) | Senha (hash) |
| `email` | VARCHAR(255) | Email |
| `nivel_acesso` | ENUM | operador/supervisor/admin |

### 📊 **logs_sistema**
Logs de todas as ações do sistema.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | INT | Chave primária |
| `acao` | VARCHAR(100) | Tipo de ação |
| `descricao` | TEXT | Descrição detalhada |
| `paciente_id` | INT | FK para pacientes |
| `atendente_id` | INT | FK para atendentes |
| `ip_usuario` | VARCHAR(45) | IP do usuário |
| `data_acao` | TIMESTAMP | Data/hora da ação |

### ⚙️ **configuracoes**
Configurações do sistema.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | INT | Chave primária |
| `chave` | VARCHAR(100) | Chave da configuração |
| `valor` | TEXT | Valor da configuração |
| `descricao` | TEXT | Descrição |
| `categoria` | VARCHAR(50) | Categoria |

---

## 🔍 Views Criadas

### **v_fila_atendimento**
View da fila atual de atendimento com dados dos pacientes.

### **v_agendamentos_hoje**
View dos agendamentos do dia atual.

---

## ⚡ Funções e Procedures

### **gerar_proxima_senha(tipo)**
Gera o próximo número de senha baseado no tipo (C, E, T, G, etc.).

### **limpar_dados_antigos()**
Remove dados antigos (logs com mais de 30 dias).

---

## 🔐 Triggers

### **tr_senhas_atendimento_insert**
Registra no log quando uma nova senha é criada.

### **tr_senhas_atendimento_update**
Registra no log quando o status de uma senha muda.

---

## 📝 Dados Iniciais

O script cria automaticamente:

### Tipos de Atendimento:
- Consulta
- Exame
- Terapia
- Atendimento Geral
- Agendar Consulta
- Resultados de Exames
- Informações e Suporte

### Salas:
- Sala 1, 2, 3 (Consulta)
- Sala 4 (Exame)
- Sala 5 (Terapia)
- Laboratório

### Profissionais:
- Dra. Ana Silva (Cardiologia)
- Dr. Bruno Lima (Clínico Geral)
- Dr. Carlos Mendes (Fisioterapia)
- Dra. Paula Rocha (Psicologia)
- Laboratório (Diagnóstico por Imagem)

### Atendente Padrão:
- **Usuário:** admin
- **Senha:** admin123

### Pacientes de Exemplo:
- João Silva (123.456.789-01)
- Maria Santos (987.654.321-00)
- Mateus Marques Da Silva (111.222.333-44)

---

## 🔧 Uso no PHP

```php
// Incluir classes
require_once 'database.php';

// Conectar e usar
$dao = obterDAO();
$paciente = $dao->buscarPacientePorCPF('12345678901');

// Inserir senha
$senhaUtil = obterSenhaUtil();
$numeroSenha = $senhaUtil->gerarProximaSenha('consulta');

// Listar fila
$fila = $dao->listarFilaAtendimento();
```

---

## 🛠️ Manutenção

### Limpeza Automática
Execute periodicamente:
```sql
CALL limpar_dados_antigos();
```

### Backup
```bash
mysqldump -u root -p totem_saude > backup_totem_$(date +%Y%m%d).sql
```

### Restauração
```bash
mysql -u root -p totem_saude < backup_totem_20240101.sql
```

---

## 📞 Suporte

Para dúvidas sobre a estrutura do banco ou problemas de instalação, consulte os logs do sistema ou entre em contato com o administrador.
