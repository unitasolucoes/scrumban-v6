<?php

class PluginScrumbanCard extends CommonDBTM {
    
    static $rightname = 'scrumban_card';
    
    static function getTypeName($nb = 0) {
        return _n('Card', 'Cards', $nb, 'scrumban');
    }
    
    static function getIcon() {
        return 'fas fa-sticky-note';
    }
    
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if ($item->getType() == 'PluginScrumbanBoard') {
            $nb = countElementsInTable($this->getTable(), ['boards_id' => $item->getField('id')]);
            return self::createTabEntry(self::getTypeName($nb), $nb);
        }
        return '';
    }
    
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if ($item->getType() == 'PluginScrumbanBoard') {
            self::showForBoard($item);
        }
        return true;
    }
    
    function defineTabs($options = []) {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab('Document_Item', $ong, $options);
        return $ong;
    }
    
    function showForm($ID, $options = []) {
        $this->initForm($ID, $options);
        $this->showFormHeader($options);
        
        // Primeira linha
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Nome', 'scrumban') . "</td>";
        echo "<td>";
        Html::autocompletionTextField($this, 'name', ['value' => $this->fields['name']]);
        echo "</td>";
        echo "<td>" . __('Quadro', 'scrumban') . "</td>";
        echo "<td>";
        $boards = PluginScrumbanBoard::getBoardsForUser(Session::getLoginUserID());
        $board_options = [0 => __('Selecione um quadro', 'scrumban')];
        foreach ($boards as $board) {
            $board_options[$board['id']] = $board['name'];
        }
        Dropdown::showFromArray('boards_id', $board_options, ['value' => $this->fields['boards_id']]);
        echo "</td>";
        echo "</tr>";
        
        // Segunda linha
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Tipo', 'scrumban') . "</td>";
        echo "<td>";
        $type_options = [
            'feature' => __('Funcionalidade', 'scrumban'),
            'bug' => __('Bug', 'scrumban'),
            'task' => __('Tarefa', 'scrumban'),
            'story' => __('História', 'scrumban')
        ];
        Dropdown::showFromArray('type', $type_options, ['value' => $this->fields['type']]);
        echo "</td>";
        echo "<td>" . __('Prioridade', 'scrumban') . "</td>";
        echo "<td>";
        $priority_options = [
            'LOW' => __('Baixa', 'scrumban'),
            'NORMAL' => __('Normal', 'scrumban'),
            'HIGH' => __('Alta', 'scrumban'),
            'CRITICAL' => __('Crítica', 'scrumban')
        ];
        Dropdown::showFromArray('priority', $priority_options, ['value' => $this->fields['priority']]);
        echo "</td>";
        echo "</tr>";
        
        // Terceira linha
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Responsável', 'scrumban') . "</td>";
        echo "<td>";
        User::dropdown(['name' => 'users_id_assigned', 'value' => $this->fields['users_id_assigned']]);
        echo "</td>";
        echo "<td>" . __('Solicitante', 'scrumban') . "</td>";
        echo "<td>";
        User::dropdown(['name' => 'users_id_requester', 'value' => $this->fields['users_id_requester']]);
        echo "</td>";
        echo "</tr>";
        
        // Quarta linha
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Story Points', 'scrumban') . "</td>";
        echo "<td>";
        echo "<input type='number' name='story_points' value='" . $this->fields['story_points'] . "' min='0' max='100'>";
        echo "</td>";
        echo "<td>" . __('Status', 'scrumban') . "</td>";
        echo "<td>";
        $status_options = [
            'backlog' => __('Backlog', 'scrumban'),
            'todo' => __('A Fazer', 'scrumban'),
            'em-execucao' => __('Em Execução', 'scrumban'),
            'review' => __('Review', 'scrumban'),
            'done' => __('Concluído', 'scrumban')
        ];
        Dropdown::showFromArray('status', $status_options, ['value' => $this->fields['status']]);
        echo "</td>";
        echo "</tr>";
        
        // Quinta linha - Datas
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Data Planejada', 'scrumban') . "</td>";
        echo "<td>";
        Html::showDateTimeField('date_planned', ['value' => $this->fields['date_planned']]);
        echo "</td>";
        echo "<td>" . __('Data de Conclusão', 'scrumban') . "</td>";
        echo "<td>";
        Html::showDateTimeField('date_completion', ['value' => $this->fields['date_completion']]);
        echo "</td>";
        echo "</tr>";
        
        // Sexta linha - Sprint e Labels
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Sprint', 'scrumban') . "</td>";
        echo "<td>";
        if ($this->fields['boards_id']) {
            $sprint = new PluginScrumbanSprint();
            $sprint_options = [0 => __('Nenhum sprint', 'scrumban')];
            $sprints = $sprint->find(['boards_id' => $this->fields['boards_id']]);
            foreach ($sprints as $sprint_data) {
                $sprint_options[$sprint_data['id']] = $sprint_data['name'];
            }
            Dropdown::showFromArray('sprint_id', $sprint_options, ['value' => $this->fields['sprint_id']]);
        } else {
            echo __('Selecione um quadro primeiro', 'scrumban');
        }
        echo "</td>";
        echo "<td>" . __('Labels', 'scrumban') . "</td>";
        echo "<td>";
        echo "<input type='text' name='labels' value='" . $this->fields['labels'] . "' placeholder='tag1, tag2, tag3'>";
        echo "</td>";
        echo "</tr>";
        
        // Descrição
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Descrição', 'scrumban') . "</td>";
        echo "<td colspan='3'>";
        echo "<textarea name='description' rows='4' cols='100'>" . $this->fields['description'] . "</textarea>";
        echo "</td>";
        echo "</tr>";
        
        // Critérios de Aceitação
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Critérios de Aceitação', 'scrumban') . "</td>";
        echo "<td colspan='3'>";
        echo "<textarea name='acceptance_criteria' rows='4' cols='100'>" . $this->fields['acceptance_criteria'] . "</textarea>";
        echo "</td>";
        echo "</tr>";
        
        // Cenários de Teste
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Cenários de Teste', 'scrumban') . "</td>";
        echo "<td colspan='3'>";
        echo "<textarea name='test_scenarios' rows='4' cols='100'>" . $this->fields['test_scenarios'] . "</textarea>";
        echo "</td>";
        echo "</tr>";
        
        // Seção de Desenvolvimento
        echo "<tr><td colspan='4'><hr><h3>" . __('Desenvolvimento', 'scrumban') . "</h3></td></tr>";
        
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Branch', 'scrumban') . "</td>";
        echo "<td>";
        echo "<input type='text' name='branch' value='" . $this->fields['branch'] . "' placeholder='feature/new-feature'>";
        echo "</td>";
        echo "<td>" . __('Pull Request', 'scrumban') . "</td>";
        echo "<td>";
        echo "<input type='text' name='pull_request' value='" . $this->fields['pull_request'] . "' placeholder='PR-123'>";
        echo "</td>";
        echo "</tr>";
        
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Commits', 'scrumban') . "</td>";
        echo "<td colspan='3'>";
        echo "<textarea name='commits' rows='3' cols='100'>" . $this->fields['commits'] . "</textarea>";
        echo "</td>";
        echo "</tr>";
        
        // DoR e DoD
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('DoR (%)', 'scrumban') . "</td>";
        echo "<td>";
        echo "<input type='range' name='dor_percentage' min='0' max='100' value='" . $this->fields['dor_percentage'] . "' oninput='updatePercentage(this, \"dor\")'>";
        echo "<span id='dor_value'>" . $this->fields['dor_percentage'] . "%</span>";
        echo "</td>";
        echo "<td>" . __('DoD (%)', 'scrumban') . "</td>";
        echo "<td>";
        echo "<input type='range' name='dod_percentage' min='0' max='100' value='" . $this->fields['dod_percentage'] . "' oninput='updatePercentage(this, \"dod\")'>";
        echo "<span id='dod_value'>" . $this->fields['dod_percentage'] . "%</span>";
        echo "</td>";
        echo "</tr>";
        
        $this->showFormButtons($options);
        
        // JavaScript para atualizar percentuais
        echo "<script>
        function updatePercentage(input, type) {
            document.getElementById(type + '_value').textContent = input.value + '%';
        }
        </script>";
        
        return true;
    }
    
    function prepareInputForAdd($input) {
        $input['date_creation'] = $_SESSION['glpi_currenttime'];
        $input['users_id_created'] = Session::getLoginUserID();
        
        if (!isset($input['entities_id'])) {
            $input['entities_id'] = $_SESSION['glpiactive_entity'];
        }
        
        // Log da criação
        $this->logHistory('create', '', $input['name']);
        
        return $input;
    }
    
    function prepareInputForUpdate($input) {
        $input['date_mod'] = $_SESSION['glpi_currenttime'];
        
        // Log das alterações
        foreach ($input as $field => $new_value) {
            if (isset($this->fields[$field]) && $this->fields[$field] != $new_value) {
                $this->logHistory('update', $field, $this->fields[$field], $new_value);
            }
        }
        
        return $input;
    }
    
    /**
     * Registrar histórico de alterações
     */
    function logHistory($action, $field = '', $old_value = '', $new_value = '') {
        global $DB;
        
        $history = [
            'cards_id' => $this->fields['id'] ?? 0,
            'users_id' => Session::getLoginUserID(),
            'action' => $action,
            'field' => $field,
            'old_value' => $old_value,
            'new_value' => $new_value,
            'date_creation' => $_SESSION['glpi_currenttime']
        ];
        
        $DB->insert('glpi_plugin_scrumban_history', $history);
    }
    
    /**
     * Mostrar cards de um quadro
     */
    static function showForBoard(PluginScrumbanBoard $board) {
        global $DB;
        
        $query = "SELECT c.*, 
                         ua.realname as assigned_name, ua.firstname as assigned_firstname,
                         ur.realname as requester_name, ur.firstname as requester_firstname
                  FROM glpi_plugin_scrumban_cards c
                  LEFT JOIN glpi_users ua ON ua.id = c.users_id_assigned  
                  LEFT JOIN glpi_users ur ON ur.id = c.users_id_requester
                  WHERE c.boards_id = '" . $board->fields['id'] . "'
                  ORDER BY c.date_creation DESC";
        
        $result = $DB->query($query);
        
        echo "<div class='spaced'>";
        
        if (PluginScrumbanBoard::canUserEditBoard(Session::getLoginUserID(), $board->fields['id'])) {
            echo "<div class='center'>";
            echo "<a href='" . PluginScrumbanCard::getFormURL() . "?boards_id=" . $board->fields['id'] . "' class='btn btn-primary'>";
            echo "<i class='fas fa-plus'></i> " . __('Novo Card', 'scrumban');
            echo "</a>";
            echo "</div><br>";
        }
        
        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr class='tab_bg_1'>";
        echo "<th>ID</th>";
        echo "<th>" . __('Nome', 'scrumban') . "</th>";
        echo "<th>" . __('Tipo', 'scrumban') . "</th>";
        echo "<th>" . __('Prioridade', 'scrumban') . "</th>";
        echo "<th>" . __('Status', 'scrumban') . "</th>";
        echo "<th>" . __('Responsável', 'scrumban') . "</th>";
        echo "<th>" . __('Story Points', 'scrumban') . "</th>";
        echo "</tr>";
        
        while ($data = $DB->fetchAssoc($result)) {
            echo "<tr class='tab_bg_2'>";
            echo "<td>#" . $data['id'] . "</td>";
            echo "<td><a href='" . PluginScrumbanCard::getFormURLWithID($data['id']) . "'>" . $data['name'] . "</a></td>";
            echo "<td>" . ucfirst($data['type']) . "</td>";
            echo "<td><span class='badge badge-" . self::getPriorityColor($data['priority']) . "'>" . $data['priority'] . "</span></td>";
            echo "<td>" . self::getStatusName($data['status']) . "</td>";
            echo "<td>" . ($data['assigned_firstname'] ? $data['assigned_firstname'] . " " . $data['assigned_name'] : '-') . "</td>";
            echo "<td>" . ($data['story_points'] ?: '-') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "</div>";
    }
    
    /**
     * Obter cor da prioridade
     */
    static function getPriorityColor($priority) {
        $colors = [
            'LOW' => 'success',
            'NORMAL' => 'info',
            'HIGH' => 'warning', 
            'CRITICAL' => 'danger'
        ];
        return $colors[$priority] ?? 'secondary';
    }
    
    /**
     * Obter nome do status
     */
    static function getStatusName($status) {
        $statuses = [
            'backlog' => __('Backlog', 'scrumban'),
            'todo' => __('A Fazer', 'scrumban'),
            'em-execucao' => __('Em Execução', 'scrumban'),
            'review' => __('Review', 'scrumban'),
            'done' => __('Concluído', 'scrumban')
        ];
        return $statuses[$status] ?? $status;
    }
    
    /**
     * Obter detalhes completos do card para modal
     */
    function getCardDetails() {
        global $DB;
        
        if (!$this->fields['id']) {
            return false;
        }
        
        // Dados básicos do card com informações dos usuários
        $query = "SELECT c.*, 
                         ua.realname as assigned_name, ua.firstname as assigned_firstname,
                         ur.realname as requester_name, ur.firstname as requester_firstname,
                         uc.realname as created_name, uc.firstname as created_firstname,
                         s.name as sprint_name,
                         b.name as board_name
                  FROM glpi_plugin_scrumban_cards c
                  LEFT JOIN glpi_users ua ON ua.id = c.users_id_assigned
                  LEFT JOIN glpi_users ur ON ur.id = c.users_id_requester  
                  LEFT JOIN glpi_users uc ON uc.id = c.users_id_created
                  LEFT JOIN glpi_plugin_scrumban_sprints s ON s.id = c.sprint_id
                  LEFT JOIN glpi_plugin_scrumban_boards b ON b.id = c.boards_id
                  WHERE c.id = '" . $this->fields['id'] . "'";
        
        $result = $DB->query($query);
        $card_data = $DB->fetchAssoc($result);
        
        // Obter comentários
        $comments_query = "SELECT com.*, u.realname, u.firstname 
                          FROM glpi_plugin_scrumban_comments com
                          LEFT JOIN glpi_users u ON u.id = com.users_id
                          WHERE com.cards_id = '" . $this->fields['id'] . "'
                          ORDER BY com.date_creation ASC";
        
        $comments_result = $DB->query($comments_query);
        $comments = [];
        while ($comment = $DB->fetchAssoc($comments_result)) {
            $comments[] = $comment;
        }
        
        // Obter histórico
        $history_query = "SELECT h.*, u.realname, u.firstname 
                         FROM glpi_plugin_scrumban_history h
                         LEFT JOIN glpi_users u ON u.id = h.users_id
                         WHERE h.cards_id = '" . $this->fields['id'] . "'
                         ORDER BY h.date_creation DESC";
        
        $history_result = $DB->query($history_query);
        $history = [];
        while ($hist = $DB->fetchAssoc($history_result)) {
            $history[] = $hist;
        }
        
        return [
            'card' => $card_data,
            'comments' => $comments,
            'history' => $history
        ];
    }
    
    /**
     * Adicionar comentário ao card
     */
    function addComment($comment_text) {
        global $DB;
        
        $comment_data = [
            'cards_id' => $this->fields['id'],
            'users_id' => Session::getLoginUserID(),
            'comment' => $comment_text,
            'date_creation' => $_SESSION['glpi_currenttime']
        ];
        
        $result = $DB->insert('glpi_plugin_scrumban_comments', $comment_data);
        
        if ($result) {
            // Log da adição do comentário
            $this->logHistory('comment', 'comment', '', $comment_text);
            return true;
        }
        
        return false;
    }
    
    /**
     * Atualizar status do card
     */
    function updateStatus($new_status) {
        $old_status = $this->fields['status'];
        
        if ($this->update(['id' => $this->fields['id'], 'status' => $new_status])) {
            // Se movido para "done", definir data de conclusão
            if ($new_status == 'done' && !$this->fields['date_completion']) {
                $this->update(['id' => $this->fields['id'], 'date_completion' => $_SESSION['glpi_currenttime']]);
            }
            
            $this->logHistory('status_change', 'status', $old_status, $new_status);
            return true;
        }
        
        return false;
    }
    
    /**
     * Renderizar modal detalhado do card
     */
    function renderCardModal() {
        $details = $this->getCardDetails();
        if (!$details) {
            return "Erro ao carregar detalhes do card.";
        }
        
        $card = $details['card'];
        $comments = $details['comments'];
        $history = $details['history'];
        
        $html = "<div class='container-fluid'>";
        
        // Header do Card
        $html .= "<div class='row mb-3'>";
        $html .= "<div class='col-md-8'>";
        $html .= "<h4>#" . $card['id'] . " (" . $card['board_name'] . ")</h4>";
        $html .= "<h5>" . $card['name'] . "</h5>";
        $html .= "</div>";
        $html .= "<div class='col-md-4 text-right'>";
        $html .= "<span class='badge badge-" . self::getPriorityColor($card['priority']) . " mr-2'>" . $card['priority'] . "</span>";
        $html .= "<span class='badge badge-info'>" . ucfirst($card['type']) . "</span>";
        $html .= "</div>";
        $html .= "</div>";
        
        // Informações Principais
        $html .= "<div class='row mb-4'>";
        $html .= "<div class='col-md-6'>";
        $html .= "<table class='table table-sm'>";
        $html .= "<tr><td><strong>Responsável:</strong></td><td>" . ($card['assigned_firstname'] ? $card['assigned_firstname'] . " " . $card['assigned_name'] : 'Não informado') . "</td></tr>";
        $html .= "<tr><td><strong>Solicitante:</strong></td><td>" . ($card['requester_firstname'] ? $card['requester_firstname'] . " " . $card['requester_name'] : 'Não informado') . "</td></tr>";
        $html .= "<tr><td><strong>Story Points:</strong></td><td>" . ($card['story_points'] ?: 'Não informado') . "</td></tr>";
        $html .= "</table>";
        $html .= "</div>";
        $html .= "<div class='col-md-6'>";
        $html .= "<table class='table table-sm'>";
        $html .= "<tr><td><strong>Criado em:</strong></td><td>" . Html::convDateTime($card['date_creation']) . "</td></tr>";
        $html .= "<tr><td><strong>Planejado:</strong></td><td>" . ($card['date_planned'] ? Html::convDateTime($card['date_planned']) : 'Não informado') . "</td></tr>";
        $html .= "<tr><td><strong>Conclusão:</strong></td><td>" . ($card['date_completion'] ? Html::convDateTime($card['date_completion']) : 'Será preenchida quando finalizado') . "</td></tr>";
        $html .= "</table>";
        $html .= "</div>";
        $html .= "</div>";
        
        // Sprint e Labels
        if ($card['sprint_name'] || $card['labels']) {
            $html .= "<div class='row mb-3'>";
            if ($card['sprint_name']) {
                $html .= "<div class='col-md-6'><strong>Sprint:</strong> " . $card['sprint_name'] . "</div>";
            }
            if ($card['labels']) {
                $html .= "<div class='col-md-6'><strong>Labels:</strong> ";
                $labels = explode(',', $card['labels']);
                foreach ($labels as $label) {
                    $html .= "<span class='badge badge-secondary mr-1'>" . trim($label) . "</span>";
                }
                $html .= "</div>";
            }
            $html .= "</div>";
        }
        
        // Seção de Desenvolvimento
        if ($card['branch'] || $card['pull_request'] || $card['commits']) {
            $html .= "<div class='card mb-3'>";
            $html .= "<div class='card-header'><h6>Desenvolvimento</h6></div>";
            $html .= "<div class='card-body'>";
            if ($card['branch']) {
                $html .= "<p><strong>Branch:</strong> <code>" . $card['branch'] . "</code></p>";
            }
            if ($card['pull_request']) {
                $html .= "<p><strong>PR:</strong> <code>" . $card['pull_request'] . "</code></p>";
            }
            if ($card['commits']) {
                $html .= "<p><strong>Commits:</strong><br><pre>" . $card['commits'] . "</pre></p>";
            }
            $html .= "</div>";
            $html .= "</div>";
        }
        
        // Critérios (DoR/DoD)
        $html .= "<div class='row mb-3'>";
        $html .= "<div class='col-md-6'>";
        $html .= "<strong>DoR (" . $card['dor_percentage'] . "%):</strong>";
        $html .= "<div class='progress mt-1'>";
        $html .= "<div class='progress-bar' style='width: " . $card['dor_percentage'] . "%'></div>";
        $html .= "</div>";
        $html .= "</div>";
        $html .= "<div class='col-md-6'>";
        $html .= "<strong>DoD (" . $card['dod_percentage'] . "%):</strong>";
        $html .= "<div class='progress mt-1'>";
        $html .= "<div class='progress-bar bg-success' style='width: " . $card['dod_percentage'] . "%'></div>";
        $html .= "</div>";
        $html .= "</div>";
        $html .= "</div>";
        
        // Critérios de Aceitação
        if ($card['acceptance_criteria']) {
            $html .= "<div class='card mb-3'>";
            $html .= "<div class='card-header'><h6>Critérios de Aceitação</h6></div>";
            $html .= "<div class='card-body'>";
            $html .= "<pre>" . $card['acceptance_criteria'] . "</pre>";
            $html .= "</div>";
            $html .= "</div>";
        }
        
        // Cenários de Teste
        if ($card['test_scenarios']) {
            $html .= "<div class='card mb-3'>";
            $html .= "<div class='card-header'><h6>Cenários de Teste</h6></div>";
            $html .= "<div class='card-body'>";
            $html .= "<pre>" . $card['test_scenarios'] . "</pre>";
            $html .= "</div>";
            $html .= "</div>";
        }
        
        // Comentários
        $html .= "<div class='card mb-3'>";
        $html .= "<div class='card-header'><h6>Comentários (" . count($comments) . ")</h6></div>";
        $html .= "<div class='card-body'>";
        
        foreach ($comments as $comment) {
            $html .= "<div class='border-bottom mb-2 pb-2'>";
            $html .= "<div class='d-flex justify-content-between'>";
            $html .= "<strong>" . $comment['firstname'] . " " . $comment['realname'] . "</strong>";
            $html .= "<small class='text-muted'>" . Html::convDateTime($comment['date_creation']) . "</small>";
            $html .= "</div>";
            $html .= "<p class='mt-1'>" . nl2br($comment['comment']) . "</p>";
            $html .= "</div>";
        }
        
        // Formulário para novo comentário
        $html .= "<form id='addCommentForm' class='mt-3'>";
        $html .= "<div class='form-group'>";
        $html .= "<textarea class='form-control' name='comment' rows='3' placeholder='Escreva seu comentário...'></textarea>";
        $html .= "</div>";
        $html .= "<button type='submit' class='btn btn-primary'>Adicionar Comentário</button>";
        $html .= "<input type='hidden' name='card_id' value='" . $card['id'] . "'>";
        $html .= "</form>";
        
        $html .= "</div>";
        $html .= "</div>";
        
        // Histórico
        $html .= "<div class='card'>";
        $html .= "<div class='card-header'><h6>Histórico do Card</h6></div>";
        $html .= "<div class='card-body'>";
        
        foreach ($history as $hist) {
            $icon = $this->getHistoryIcon($hist['action']);
            $description = $this->getHistoryDescription($hist);
            
            $html .= "<div class='d-flex align-items-center mb-2'>";
            $html .= "<i class='" . $icon . " mr-2'></i>";
            $html .= "<div class='flex-grow-1'>";
            $html .= "<strong>" . $description . "</strong><br>";
            $html .= "<small class='text-muted'>" . $hist['firstname'] . " " . $hist['realname'] . " • " . Html::convDateTime($hist['date_creation']) . "</small>";
            $html .= "</div>";
            $html .= "</div>";
        }
        
        $html .= "</div>";
        $html .= "</div>";
        
        $html .= "</div>";
        
        return $html;
    }
    
    /**
     * Obter ícone para o histórico
     */
    private function getHistoryIcon($action) {
        $icons = [
            'create' => 'fas fa-plus-circle text-success',
            'update' => 'fas fa-edit text-primary',
            'status_change' => 'fas fa-exchange-alt text-warning',
            'comment' => 'fas fa-comment text-info'
        ];
        
        return $icons[$action] ?? 'fas fa-circle';
    }
    
    /**
     * Obter descrição para o histórico
     */
    private function getHistoryDescription($history) {
        switch ($history['action']) {
            case 'create':
                return "Card criado";
            case 'status_change':
                return "Status alterado de \"" . self::getStatusName($history['old_value']) . "\" para \"" . self::getStatusName($history['new_value']) . "\"";
            case 'comment':
                return "Comentário adicionado";
            case 'update':
                $field_names = [
                    'name' => 'Nome',
                    'description' => 'Descrição',
                    'priority' => 'Prioridade',
                    'users_id_assigned' => 'Responsável',
                    'story_points' => 'Story Points',
                    'branch' => 'Branch',
                    'pull_request' => 'Pull Request'
                ];
                $field_name = $field_names[$history['field']] ?? $history['field'];
                return "Campo \"$field_name\" alterado";
            default:
                return "Ação: " . $history['action'];
        }
    }
}