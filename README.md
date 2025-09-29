# Plugin Scrumban para GLPI 🚀

Sistema completo de gestão de projetos estilo Kanban com controle de equipes e permissões granulares para GLPI.

## 📋 Funcionalidades

### 🎯 Sistema de Equipes
- **Criação e gestão de equipes** com diferentes níveis de permissão
- **3 níveis hierárquicos**: Membro, Líder e Administrador
- **Associação flexível**: Usuários podem pertencer a múltiplas equipes
- **Controle granular**: Cada equipe tem acesso apenas aos quadros designados

### 📊 Quadros Kanban
- **5 colunas padrão**: Backlog, A Fazer, Em Execução, Review, Concluído
- **Drag & Drop** intuitivo para movimentação de cards
- **Filtros avançados** por equipe, responsável, tipo, prioridade, sprint
- **Múltiplas equipes por quadro** com diferentes permissões

### 🎫 Sistema de Cards Detalhado
- **Informações completas**: Nome, descrição, tipo, prioridade, responsável, solicitante
- **Story Points** para estimativas
- **Datas de planejamento e conclusão**
- **Labels personalizáveis**
- **Sistema de anexos**
- **Seção de desenvolvimento**: Branch, Pull Request, Commits
- **DoR/DoD com percentuais** de progresso
- **Cenários de teste** com status (Pendente/Passou/Falhou)
- **Sistema de comentários** com histórico
- **Log completo de alterações**

### 🏃‍♂️ Gerenciamento de Sprints
- **Criação e gestão de sprints** por quadro
- **Apenas um sprint ativo** por quadro
- **Estatísticas de progresso** em tempo real
- **Associação de cards** aos sprints

### 🔒 Controle de Acesso
- **Integração total** com usuários do GLPI
- **Verificação em múltiplas camadas**: equipe + quadro
- **3 níveis de visibilidade**: Público, Equipe, Privado
- **Permissões granulares**: Visualizar, Editar, Gerenciar

### 📱 Interface Moderna
- **Design responsivo** para desktop e mobile
- **Modal detalhado** para visualização/edição de cards
- **Seletores inteligentes** de equipe e quadro
- **Dashboard com estatísticas** personalizadas
- **Notificações toast** para feedback do usuário

## 🛠️ Requisitos

- **GLPI**: 10.0 ou superior
- **PHP**: 7.4 ou superior
- **MySQL**: 5.7 ou superior
- **Apache/Nginx** com mod_rewrite habilitado
- **Extensões PHP**: json, pdo_mysql, gd

## 📦 Instalação

### 1. Download e Extração
```bash
cd /var/www/html/glpi/plugins/
git clone https://github.com/unita/scrumban.git
# ou baixe e extraia o ZIP
```

### 2. Configuração de Permissões
```bash
chown -R www-data:www-data scrumban/
chmod -R 755 scrumban/
```

### 3. Instalação no GLPI
1. Acesse **Configurar > Plugins**
2. Localize o plugin **Scrumban**
3. Clique em **Instalar**
4. Clique em **Ativar**

### 4. Configuração de Perfis
1. Acesse **Administração > Perfis**
2. Edite os perfis desejados
3. Na aba **Scrumban**, configure as permissões:
   - **Scrumban Geral**: Leitura/Escrita
   - **Equipes**: Leitura/Escrita  
   - **Quadros**: Leitura/Escrita
   - **Cards**: Leitura/Escrita

## 🚀 Uso Rápido

### 1️⃣ Criar uma Equipe
```
Ferramentas → Scrumban → Equipes → "Nova Equipe"
↓
Preencher: Nome, Descrição, Gerente
↓ 
Salvar (você vira Admin automaticamente)
```

### 2️⃣ Adicionar Membros
```
Lista de Equipes → "Gerenciar" → Aba "Membros"
↓
"Adicionar Membro" → Selecionar usuário → Definir papel
↓
Salvar
```

### 3️⃣ Criar e Associar Quadro
```
Ferramentas → Scrumban → Quadros → "Novo Quadro"
↓
Preencher dados e associar à equipe
↓
Ou: Gerenciar Equipe → Aba "Quadros" → "Adicionar Quadro"
```

### 4️⃣ Trabalhar no Kanban
```
Ferramentas → Scrumban → Dashboard
↓
Selecionar Equipe e Quadro
↓
Criar cards, arrastar entre colunas, adicionar comentários
```

## 🏗️ Estrutura do Banco de Dados

### Tabelas Principais
- `glpi_plugin_scrumban_teams` - Equipes
- `glpi_plugin_scrumban_team_members` - Membros das equipes
- `glpi_plugin_scrumban_boards` - Quadros Kanban
- `glpi_plugin_scrumban_team_boards` - Associação equipe-quadro
- `glpi_plugin_scrumban_cards` - Cards do projeto
- `glpi_plugin_scrumban_sprints` - Sprints
- `glpi_plugin_scrumban_comments` - Comentários dos cards
- `glpi_plugin_scrumban_history` - Histórico de alterações

### Migração Automática
O plugin detecta automaticamente instalações anteriores e executa migração preservando todos os dados existentes.

## 🎨 Personalização

### CSS Customizado
```css
/* Adicione em css/custom.css */
.kanban-card {
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.priority-CRITICAL .card {
    border-left: 5px solid #dc3545;
}
```

### JavaScript Customizado
```javascript
// Adicione em js/custom.js
$(document).ready(function() {
    // Personalizar comportamento do drag & drop
    $('.kanban-cards').sortable({
        // opções customizadas
    });
});
```

## 🔧 Configurações Avançadas

### Configuração de Permissões
```php
// Em inc/config.class.php
define('SCRUMBAN_ADMIN_ONLY_CREATE_TEAMS', false);
define('SCRUMBAN_AUTO_ASSIGN_CREATOR', true);
define('SCRUMBAN_DEFAULT_VISIBILITY', 'team');
```

### Hooks Disponíveis
```php
// Hook após criação de card
Plugin::doHook('plugin_scrumban_card_created', $card_data);

// Hook após mudança de status
Plugin::doHook('plugin_scrumban_status_changed', $status_data);

// Hook após adição de comentário
Plugin::doHook('plugin_scrumban_comment_added', $comment_data);
```

## 📊 Exemplos de Uso

### Cenário: Software House
```
🎨 Equipe Frontend → Quadros: "Website", "App Mobile"
⚙️ Equipe Backend → Quadros: "API", "Database"  
🧪 Equipe QA → Todos os quadros (apenas visualizar)
```

### Cenário: Departamento de TI
```
🖥️ Infraestrutura → Quadros: "Servidores", "Rede"
💻 Desenvolvimento → Quadros: "Sistemas Internos"
🎫 Suporte → Quadros: "Incidentes", "Mudanças"
```

## 🐛 Resolução de Problemas

### Problema: "Nenhum quadro disponível"
**Solução:**
1. Verifique se está em alguma equipe
2. Confirme se a equipe tem quadros associados
3. Contate um administrador para ser adicionado

### Problema: "Acesso negado"
**Solução:**
1. Confirme seu papel na equipe
2. Verifique as permissões do quadro
3. Pode ser necessário permissão de Líder/Admin

### Problema: Drag & Drop não funciona
**Solução:**
1. Verifique se tem permissão de edição
2. Confirme se o JavaScript está carregado
3. Teste em outro navegador

### Problema: Cards não aparecem
**Solução:**
```sql
-- Verificar dados
SELECT * FROM glpi_plugin_scrumban_cards WHERE boards_id = X;

-- Verificar permissões
SELECT * FROM glpi_plugin_scrumban_team_members WHERE users_id = Y;
```

## 📱 API e Integrações

### Endpoints AJAX Disponíveis
```
POST /plugins/scrumban/ajax/card.php
- get_card_details
- update_status  
- add_comment
- create

POST /plugins/scrumban/ajax/team.php
- add_member
- remove_member
- change_role
- add_board
```

### Exemplo de Integração
```javascript
// Criar card via API
$.ajax({
    url: '/plugins/scrumban/ajax/card.php',
    type: 'POST',
    data: {
        action: 'create',
        boards_id: 1,
        name: 'Novo Card',
        type: 'task',
        priority: 'NORMAL'
    },
    success: function(response) {
        console.log('Card criado:', response);
    }
});
```

## 🔮 Roadmap

### Versão 2.1 (Próxima)
- [ ] API REST completa
- [ ] Notificações por email
- [ ] Templates de equipe
- [ ] Relatórios em PDF

### Versão 2.2 (Futuro)
- [ ] Integração com LDAP/AD
- [ ] Single Sign-On (SSO)
- [ ] App mobile
- [ ] Webhooks

## 🤝 Contribuindo

### Como Contribuir
1. Fork o repositório
2. Crie uma branch: `git checkout -b feature/nova-funcionalidade`
3. Commit: `git commit -am 'Adiciona nova funcionalidade'`
4. Push: `git push origin feature/nova-funcionalidade`
5. Abra um Pull Request

### Padrões de Código
- Seguir PSR-12 para PHP
- Usar ESLint para JavaScript
- Comentários em português
- Testes unitários obrigatórios

## 📄 Licença

Este projeto está licenciado sob a GPL v2+ - veja o arquivo [LICENSE](LICENSE) para detalhes.

## 👥 Equipe

**Desenvolvido por:** Unitá Soluções Digitais  
**Contato:** contato@unita.com.br  
**Website:** https://unita.com.br

## 🙏 Agradecimentos

- Comunidade GLPI pelo framework robusto
- Bootstrap pela interface responsiva
- jQuery UI pelo drag & drop
- Font Awesome pelos ícones

---

**⭐ Se este plugin foi útil para você, considere dar uma estrela no GitHub!**