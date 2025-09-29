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

    static function getTypeBadge($type) {
        $types = [
            'feature' => __('Funcionalidade', 'scrumban'),
            'bug'      => __('Bug', 'scrumban'),
            'task'     => __('Tarefa', 'scrumban'),
            'story'    => __('História', 'scrumban')
        ];

        $label = $types[$type] ?? ucfirst($type);
        return "<span class='badge badge-pill badge-info'>" . Html::clean($label) . "</span>";
    }

    static function getPriorityBadge($priority) {
        $labels = [
            'LOW'      => __('Baixa', 'scrumban'),
            'NORMAL'   => __('Normal', 'scrumban'),
            'HIGH'     => __('Alta', 'scrumban'),
            'CRITICAL' => __('Crítica', 'scrumban')
        ];

        $label = $labels[$priority] ?? strtoupper($priority);
        $color = self::getPriorityColor($priority);
        return "<span class='badge badge-pill badge-" . $color . "'>" . Html::clean($label) . "</span>";
    }

    static function getStatusBadge($status) {
        $map = [
            'backlog'      => ['label' => __('Backlog', 'scrumban'), 'class' => 'secondary'],
            'todo'         => ['label' => __('A Fazer', 'scrumban'), 'class' => 'info'],
            'em-execucao'  => ['label' => __('Em Execução', 'scrumban'), 'class' => 'primary'],
            'review'       => ['label' => __('Review', 'scrumban'), 'class' => 'warning'],
            'done'         => ['label' => __('Concluído', 'scrumban'), 'class' => 'success']
        ];

        $info = $map[$status] ?? ['label' => $status, 'class' => 'secondary'];
        return "<span class='badge badge-pill badge-" . $info['class'] . "'>" . Html::clean($info['label']) . "</span>";
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
            return __('Erro ao carregar detalhes do card', 'scrumban');
        }

        $card      = $details['card'];
        $comments  = $details['comments'];
        $history   = $details['history'];
        $now       = strtotime($_SESSION['glpi_currenttime'] ?? 'now');
        $assigned  = $this->formatPersonName($card['assigned_firstname'], $card['assigned_name']);
        $requester = $this->formatPersonName($card['requester_firstname'], $card['requester_name']);
        $story     = $card['story_points'] !== null && $card['story_points'] !== '' ? $card['story_points'] : __('Não informado', 'scrumban');
        $planned   = $card['date_planned'] ? Html::convDateTime($card['date_planned']) : __('Não informado', 'scrumban');
        $planned_ts = $card['date_planned'] ? strtotime($card['date_planned']) : null;
        $completion = $card['date_completion'] ? Html::convDateTime($card['date_completion']) : __('Não informado', 'scrumban');
        $attachments_count = countElementsInTable('glpi_documents_items', [
            'itemtype' => 'PluginScrumbanCard',
            'items_id' => (int)$card['id']
        ]);

        $html = "<div class='card-modal-container'>";

        // Header
        $html .= "<div class='card-modal-header'>";
        $html .= "  <div class='card-id-title'>";
        $html .= "    <h3>#" . (int)$card['id'] . ' ' . Html::clean($card['name']) . "</h3>";
        $html .= "    <span class='card-board-name'>" . Html::clean($card['board_name']) . "</span>";
        $html .= "  </div>";
        $html .= "  <div class='card-badges'>";
        $html .= self::getTypeBadge($card['type']);
        $html .= self::getPriorityBadge($card['priority']);
        $html .= self::getStatusBadge($card['status']);
        $html .= "  </div>";
        $html .= "</div>";

        // Informações principais
        $html .= "<div class='card-section two-columns'>";
        $html .= "  <div class='column'>";
        $html .= "    <table class='card-info-table'>";
        $html .= "      <tr><th>" . __('Responsável', 'scrumban') . ":</th><td>" . Html::clean($assigned) . "</td></tr>";
        $html .= "      <tr><th>" . __('Solicitante', 'scrumban') . ":</th><td>" . Html::clean($requester) . "</td></tr>";
        $html .= "      <tr><th>" . __('Story Points', 'scrumban') . ":</th><td>" . Html::clean($story) . "</td></tr>";
        $html .= "    </table>";
        $html .= "  </div>";
        $html .= "  <div class='column'>";
        $html .= "    <table class='card-info-table'>";
        $html .= "      <tr><th>" . __('Criado em', 'scrumban') . ":</th><td>" . Html::convDateTime($card['date_creation']) . "</td></tr>";
        $html .= "      <tr><th>" . __('Data planejada', 'scrumban') . ":</th><td>" . $planned;
        if ($planned_ts && $planned_ts < $now && $card['status'] !== 'done') {
            $html .= " <span class='badge badge-danger'>" . __('Atrasado', 'scrumban') . "</span>";
        }
        $html .= "</td></tr>";
        $html .= "      <tr><th>" . __('Data de conclusão', 'scrumban') . ":</th><td>" . $completion . "</td></tr>";
        $html .= "    </table>";
        $html .= "  </div>";
        $html .= "</div>";

        // Informações adicionais
        $labels = $this->prepareLabels($card['labels']);
        $html .= "<div class='card-section'>";
        $html .= "  <div class='card-meta-row'><strong>" . __('Sprint', 'scrumban') . ":</strong> " . Html::clean($card['sprint_name'] ?: __('Sem sprint', 'scrumban')) . "</div>";
        $html .= "  <div class='card-meta-row'><strong>" . __('Labels', 'scrumban') . ":</strong> " . ($labels ?: "<span class='text-muted'>" . __('Nenhuma label', 'scrumban') . "</span>") . "</div>";
        $html .= "  <div class='card-meta-row card-attachments'><strong>" . sprintf(__('Anexos (%d)', 'scrumban'), $attachments_count) . ":</strong>";
        $html .= "    <button type='button' class='btn btn-sm btn-outline-primary ml-2' data-action='scrumban-add-attachment'>" . __('Adicionar anexos', 'scrumban') . "</button>";
        $html .= "  </div>";
        $html .= "</div>";

        // Desenvolvimento
        $html .= "<div class='card-section'>";
        $html .= "  <h4>" . __('Desenvolvimento', 'scrumban') . "</h4>";
        if ($card['branch'] || $card['pull_request'] || $card['commits']) {
            if ($card['branch']) {
                $html .= "  <p><strong>" . __('Branch', 'scrumban') . ":</strong> <code>" . Html::clean($card['branch']) . "</code></p>";
            }
            if ($card['pull_request']) {
                $html .= "  <p><strong>PR:</strong> <code>" . Html::clean($card['pull_request']) . "</code></p>";
            }
            if ($card['commits']) {
                $html .= "  <div class='form-group'>";
                $html .= "    <label>" . __('Commits', 'scrumban') . "</label>";
                $html .= "    <textarea class='form-control' rows='4' readonly>" . Html::clean($card['commits']) . "</textarea>";
                $html .= "  </div>";
            }
        } else {
            $html .= "  <p class='text-muted'>" . __('Nenhuma informação de desenvolvimento registrada', 'scrumban') . "</p>";
        }
        $html .= "</div>";

        // Critérios (DoR/DoD)
        $dor = (int)($card['dor_percentage'] ?? 0);
        $dod = (int)($card['dod_percentage'] ?? 0);
        $html .= "<div class='card-section two-columns'>";
        $html .= "  <div class='column'>";
        $html .= "    <strong>DoR</strong><span class='badge badge-light ml-1'>" . $dor . "%</span>";
        $html .= "    <div class='progress mt-2'><div class='progress-bar' style='width: " . $dor . "%'></div></div>";
        $html .= "  </div>";
        $html .= "  <div class='column'>";
        $html .= "    <strong>DoD</strong><span class='badge badge-light ml-1'>" . $dod . "%</span>";
        $html .= "    <div class='progress mt-2'><div class='progress-bar bg-success' style='width: " . $dod . "%'></div></div>";
        $html .= "  </div>";
        $html .= "</div>";

        // Critérios de aceitação
        $html .= "<div class='card-section'>";
        $html .= "  <h4>" . __('Critérios de aceitação', 'scrumban') . "</h4>";
        $criteria = $this->parseChecklist($card['acceptance_criteria']);
        if ($criteria) {
            $html .= "  <ul class='card-checklist'>";
            foreach ($criteria as $criterion) {
                $html .= "    <li><label><input type='checkbox' disabled" . ($criterion['checked'] ? ' checked' : '') . "> " . Html::clean($criterion['label']) . "</label></li>";
            }
            $html .= "  </ul>";
        } else {
            $html .= "  <p class='text-muted'>" . __('Nenhum critério cadastrado', 'scrumban') . "</p>";
        }
        $html .= "  <button type='button' class='btn btn-sm btn-outline-primary mt-2' data-action='scrumban-add-acceptance'>" . __('Adicionar critério', 'scrumban') . "</button>";
        $html .= "</div>";

        // Cenários de teste
        $html .= "<div class='card-section'>";
        $html .= "  <h4>" . __('Cenários de teste', 'scrumban') . "</h4>";
        $scenarios = $this->parseScenarios($card['test_scenarios']);
        if ($scenarios) {
            $html .= "  <ol class='card-scenarios'>";
            foreach ($scenarios as $scenario) {
                $html .= "    <li><span class='scenario-name'>" . Html::clean($scenario['name']) . "</span> " . $scenario['badge'] . "</li>";
            }
            $html .= "  </ol>";
        } else {
            $html .= "  <p class='text-muted'>" . __('Nenhum cenário de teste registrado', 'scrumban') . "</p>";
        }
        $html .= "  <button type='button' class='btn btn-sm btn-outline-primary mt-2' data-action='scrumban-add-scenario'>" . __('Adicionar cenário de teste', 'scrumban') . "</button>";
        $html .= "</div>";

        // Comentários
        $html .= "<div class='card-section'>";
        $html .= "  <h4>" . sprintf(__('Comentários (%d)', 'scrumban'), count($comments)) . "</h4>";
        if ($comments) {
            $html .= "  <div class='card-comments'>";
            foreach ($comments as $comment) {
                $name = $this->formatPersonName($comment['firstname'], $comment['realname']);
                $initials = $this->getInitials($comment['firstname'], $comment['realname']);
                $html .= "    <div class='card-comment'>";
                $html .= "      <div class='comment-avatar'>" . Html::clean($initials) . "</div>";
                $html .= "      <div class='comment-body'>";
                $html .= "        <div class='comment-header'><strong>" . Html::clean($name) . "</strong><span>" . Html::convDateTime($comment['date_creation']) . "</span></div>";
                $html .= "        <div class='comment-text'>" . nl2br(Html::clean($comment['comment'])) . "</div>";
                $html .= "      </div>";
                $html .= "    </div>";
            }
            $html .= "  </div>";
        } else {
            $html .= "  <p class='text-muted'>" . __('Nenhum comentário até o momento', 'scrumban') . "</p>";
        }

        $html .= "  <form id='addCommentForm' class='card-comment-form mt-3'>";
        $html .= "    <textarea class='form-control' name='comment' rows='3' placeholder='" . __('Escreva seu comentário...', 'scrumban') . "'></textarea>";
        $html .= "    <input type='hidden' name='card_id' value='" . (int)$card['id'] . "'>";
        $html .= "    <div class='text-right mt-2'>";
        $html .= "      <button type='submit' class='btn btn-primary'>" . __('Adicionar comentário', 'scrumban') . "</button>";
        $html .= "    </div>";
        $html .= "  </form>";
        $html .= "</div>";

        // Histórico
        $html .= "<div class='card-section'>";
        $html .= "  <h4>" . __('Histórico', 'scrumban') . "</h4>";
        if ($history) {
            $html .= "  <div class='card-timeline'>";
            foreach ($history as $hist) {
                $emoji = $this->getHistoryEmoji($hist['action']);
                $description = $this->getHistoryDescription($hist);
                $actor = $this->formatPersonName($hist['firstname'], $hist['realname']);
                $html .= "    <div class='timeline-entry'>";
                $html .= "      <div class='timeline-icon'>" . Html::clean($emoji) . "</div>";
                $html .= "      <div class='timeline-body'>";
                $html .= "        <div class='timeline-title'>" . Html::clean($description) . "</div>";
                $html .= "        <div class='timeline-meta'>" . Html::clean($actor) . " • " . Html::convDateTime($hist['date_creation']) . "</div>";
                $html .= "      </div>";
                $html .= "    </div>";
            }
            $html .= "  </div>";
        } else {
            $html .= "  <p class='text-muted'>" . __('Nenhum histórico registrado', 'scrumban') . "</p>";
        }
        $html .= "</div>";

        $html .= "</div>";

        return $html;
    }

    private function formatPersonName($firstname, $lastname) {
        $firstname = trim((string)$firstname);
        $lastname  = trim((string)$lastname);

        if ($firstname === '' && $lastname === '') {
            return __('Não informado', 'scrumban');
        }

        return trim($firstname . ' ' . $lastname);
    }

    private function prepareLabels($labels) {
        if (!$labels) {
            return '';
        }

        $parts = array_filter(array_map('trim', preg_split('/[,;]+/', $labels)));
        if (!$parts) {
            return '';
        }

        $html = '';
        foreach ($parts as $label) {
            $html .= "<span class='badge badge-secondary mr-1'>" . Html::clean($label) . "</span>";
        }

        return $html;
    }

    private function parseChecklist($text) {
        if (!$text) {
            return [];
        }

        $items = [];
        foreach (preg_split('/\r?\n/', $text) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $checked = false;
            if (preg_match('/^\[(x|✔)\]\s*(.+)$/i', $line, $matches)) {
                $checked = true;
                $label   = $matches[2];
            } elseif (preg_match('/^\[\s?\]\s*(.+)$/', $line, $matches)) {
                $label = $matches[1];
            } else {
                $label = $line;
            }

            $items[] = ['label' => $label, 'checked' => $checked];
        }

        return $items;
    }

    private function parseScenarios($text) {
        if (!$text) {
            return [];
        }

        $scenarios = [];
        foreach (preg_split('/\r?\n/', $text) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $status = 'pendente';
            $name   = $line;

            if (strpos($line, '|') !== false) {
                [$name, $status] = array_map('trim', explode('|', $line, 2));
            } elseif (strpos($line, '-') !== false) {
                [$name, $status] = array_map('trim', explode('-', $line, 2));
            }

            $status_key = strtolower($status);
            $badge = $this->getScenarioBadge($status_key);

            $scenarios[] = [
                'name'  => $name,
                'badge' => $badge
            ];
        }

        return $scenarios;
    }

    private function getScenarioBadge($status) {
        $map = [
            'passou'   => ['label' => __('Passou', 'scrumban'), 'class' => 'success'],
            'aprovado' => ['label' => __('Passou', 'scrumban'), 'class' => 'success'],
            'falhou'   => ['label' => __('Falhou', 'scrumban'), 'class' => 'danger'],
            'erro'     => ['label' => __('Falhou', 'scrumban'), 'class' => 'danger'],
            'pendente' => ['label' => __('Pendente', 'scrumban'), 'class' => 'warning'],
        ];

        $info = $map[$status] ?? ['label' => ucfirst($status ?: __('Pendente', 'scrumban')), 'class' => 'secondary'];
        return "<span class='badge badge-" . $info['class'] . "'>" . Html::clean($info['label']) . "</span>";
    }

    private function getInitials($firstname, $lastname) {
        $firstname = trim((string)$firstname);
        $lastname  = trim((string)$lastname);

        $initials = '';
        if ($firstname !== '') {
            $initials .= mb_substr($firstname, 0, 1);
        }
        if ($lastname !== '') {
            $initials .= mb_substr($lastname, 0, 1);
        }

        return $initials !== '' ? strtoupper($initials) : '??';
    }

    private function getHistoryEmoji($action) {
        $map = [
            'create'        => '🎯',
            'status_change' => '📋',
            'comment'       => '💬',
            'update'        => '✏️',
            'pull_request'  => '✏️',
            'branch'        => '✏️'
        ];

        return $map[$action] ?? '⬤';
    }

    private function getHistoryDescription($history) {
        switch ($history['action']) {
            case 'create':
                return __('Card criado', 'scrumban');
            case 'status_change':
                return sprintf(__('Status alterado de "%1$s" para "%2$s"', 'scrumban'), self::getStatusName($history['old_value']), self::getStatusName($history['new_value']));
            case 'comment':
                return __('Comentário adicionado', 'scrumban');
            case 'branch':
                return __('Branch criada', 'scrumban');
            case 'pull_request':
                return __('Pull Request criado', 'scrumban');
            case 'update':
                $field_names = [
                    'name' => __('Nome', 'scrumban'),
                    'description' => __('Descrição', 'scrumban'),
                    'priority' => __('Prioridade', 'scrumban'),
                    'users_id_assigned' => __('Responsável', 'scrumban'),
                    'story_points' => __('Story Points', 'scrumban'),
                    'branch' => __('Branch', 'scrumban'),
                    'pull_request' => 'Pull Request'
                ];
                $field_name = $field_names[$history['field']] ?? $history['field'];
                return sprintf(__('Campo "%s" alterado', 'scrumban'), $field_name);
            default:
                return sprintf(__('Ação: %s', 'scrumban'), $history['action']);
        }
    }
}