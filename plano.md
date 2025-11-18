# Melhorias para o Sistema de Fila

## Gerenciamento de Atendimentos

### Fluxo de Atendimento

1. **Iniciar Atendimento**
   - Ao clicar em "Iniciar Atendimento", o status do paciente deve ser alterado de 'aguardando' para 'atendido'
   - O registro deve ser movido para o histórico de atendimentos
   - Deve ser registrado o horário de início do atendimento


### Considerações Técnicas
- Garantir que as mudanças de status sejam refletidas em tempo real
- Manter um log de todas as transições de status
- Validar permissões antes de permitir mudanças de status

## Remoção de Pacientes da Fila

### Objetivo
Implementar uma funcionalidade que permita aos atendentes remover pacientes da fila quando necessário, como em casos de desistência ou quando o paciente for chamado mas não comparecer.

### Funcionalidades Propostas

1. **Botão "Remover da Fila"**
   - Adicionar um botão ao lado de cada paciente na fila de atendimento
   - Acesso restrito apenas para usuários autenticados (atendentes)

2. **Confirmação de Remoção**
   - Modal de confirmação antes de remover o paciente
   - Opção para adicionar um motivo da remoção (opcional)
   - Registro da remoção no banco de dados para auditoria

3. **Feedback Visual**
   - Mensagem de confirmação após a remoção
   - Atualização em tempo real da fila de espera

4. **Registro de Atividades**
   - Manter um histórico de remoções com:
     - Data e hora
     - Nome do paciente
     - CPF (se disponível)
     - Motivo da remoção
     - Atendente responsável

### Considerações Técnicas
- Garantir que a remoção seja refletida em tempo real para todos os dispositivos conectados
- Implementar validações para evitar remoções acidentais
- Considerar a criação de permissões específicas para esta funcionalidade

### Próximos Passos
1. [ ] Definir o fluxo completo da funcionalidade
2. [ ] Criar as rotas e controladores necessários
3. [ ] Desenvolver a interface do usuário
4. [ ] Implementar a lógica de remoção no backend
5. [ ] Testar a funcionalidade
6. [ ] Documentar o uso para os atendentes

### Observações
- Esta funcionalidade deve ser intuitiva e de fácil acesso para os atendentes
- É importante manter um registro de auditoria de todas as remoções
- Considerar adicionar relatórios periódicos de pacientes removidos da fila
