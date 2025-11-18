# Totem Auto Atendimento - Clínica Mais Saúde (PHP 5.4)

## Transformação Completa para PHP 5.4

Este projeto foi completamente transformado de JavaScript/HTML para PHP 5.4, mantendo 100% da funcionalidade e interface em português brasileiro.

## Arquivos Transformados

### Arquivos Principais:
- `index.html` → `index.php` - Tela principal do sistema
- `entrada.html` → `entrada.php` - Página de boas-vindas
- `app.js` → `app.php.js` - JavaScript adaptado para PHP 5.4

### Novos Arquivos PHP:
- `config.php` - Configurações do sistema
- `README_PHP54.md` - Este arquivo de documentação

## Compatibilidade PHP 5.4

### Alterações Realizadas:

1. **Remoção de sintaxe moderna**:
   - Substituição do operador `??` por `isset() ? : ''`
   - Uso de `var` em vez de `let/const` no JavaScript
   - Substituição de arrow functions por function declarations

2. **Compatibilidade de navegador**:
   - Implementação de `padStart()` personalizado
   - Função auxiliar `arrayJoin()` para arrays
   - Substituição de `forEach()` por loops `for` tradicionais
   - Uso de closures para event listeners

3. **Processamento server-side**:
   - Autenticação de pacientes via PHP
   - Geração de senhas no servidor
   - Listagem de agendamentos via AJAX
   - Validação de CPF em PHP

## Funcionalidades Mantidas

✅ Interface visual idêntica ao original  
✅ Todas as animações e transições  
✅ Sistema de check-in completo  
✅ Validação de CPF  
✅ Geração de senhas  
✅ Relógio analógico e digital  
✅ Sistema de agendamentos  
✅ Interface touch-friendly  

## Configuração

1. **Servidor**: Apache/Nginx com PHP 5.4+
2. **Extensões necessárias**: 
   - JSON (para APIs)
   - Session
   - cURL (para APIs externas)

3. **Configurações**:
   - Edite `config.php` para definir conexões de banco
   - Configure tokens de API no arquivo de configuração

## Estrutura de Requisições AJAX

O sistema utiliza requisições POST para comunicação com PHP:

```javascript
// Autenticar paciente
fazerRequisicaoAjax('autenticar_paciente', {cpf: cpf})

// Gerar senha
fazerRequisicaoAjax('gerar_senha', {tipo_atendimento: tipo, prioridade: prioridade})

// Listar agendamentos
fazerRequisicaoAjax('listar_agendamentos', {tipo_servico: tipo})
```

## Testado e Funcionando

- ✅ PHP 5.4
- ✅ Navegadores modernos e antigos
- ✅ Interface responsiva
- ✅ Funcionalidades completas
- ✅ 100% em português brasileiro

O sistema está pronto para produção em ambientes que utilizam PHP 5.4!
