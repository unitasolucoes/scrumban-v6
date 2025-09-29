<?php

class PluginScrumbanBoard extends CommonDBTM {
    
    static $rightname = 'scrumban_board';
    
    static function getTypeName($nb = 0) {
        return _n('Quadro', 'Quadros', $nb, 'scrumban');
    }
    
    static function getIcon() {
        return 'fas fa-columns';
    }
    
    function defineTabs($options = []) {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab('PluginScrumbanCard', $ong, $options);
        $this->addStandardTab('PluginScrumbanSprint', $ong, $options);
        return $ong;
    }
    
    function showForm($ID, $options = []) {
        $this->initForm($ID, $options);
        $this->showFormHeader($options);
        
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Nome', 'scrumban') . "</td>";
        echo "<td>";
        Html::autocompletionTextField($this, 'name', ['value' => $this->fields['name']]);
        echo "</td>";
        echo "<td>" . __('Ativo', 'scrumban') . "</td>";
        echo "<td>";
        Dropdown::showYesNo('is_active', $this->fields['is_active']);
        echo "</td>";
        echo "</tr>";
        
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Descrição', 'scrumban') . "</td>";
        echo "<td colspan='3'>";
        echo "<textarea name='description' rows='4' cols='80'>" . $this->fields['description'] . "</textarea>";
        echo "</td>";
        echo "</tr>";
        
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Equipe Proprietária', 'scrumban') . "</td>";
        echo "<td>";
        $teams = PluginScrumbanTeam::getTeamsForUser(Session::getLoginUserID());
        $team_options = [0 => __('Selecione uma equipe', 'scrumban')];
        foreach ($teams as $team) {
            $team_options[$team['id']] = $team['name'];
        }
        Dropdown::showFromArray('teams_id', $team_options, ['value' => $this->fields['teams_id']]);
        echo "</td>";
        echo "<td>" . __('Visibilidade', 'scrumban') . "</td>";
        echo "<td>";
        $visibility_options = [
            'public' => __('Público', 'scrumban'),
            'team' => __('Equipe', 'scrumban'),
            'private' => __('Privado', 'scrumban')
        ];
        Dropdown::showFromArray('visibility', $visibility_options, ['value' => $this->fields['visibility']]);
        echo "</td>";
        echo "</tr>";
        
        $this->showFormButtons($options);
        return true;
    }
    
    function prepareInputForAdd($input) {
        $input['date_creation'] = $_SESSION['glpi_currenttime'];
        $input['users_id_created'] = Session::getLoginUserID();
        
        if (!isset($input['entities_id'])) {
            $input['entities_id'] = $_SESSION['glpiactive_entity'];
        }
        
        return $input;
    }
    
    function post_addItem() {
        // Se foi associado a uma equipe, criar a associação na tabela team_boards
        if ($this->fields['teams_id'] > 0) {
            $team_board = new PluginScrumbanTeamBoard();
            $team_board->add([
                'teams_id' => $this->fields['teams_id'],
                'boards_id' => $this->fields['id'],
                'can_edit' => 1,
                'can_manage' => 1
            ]);
        }
    }
    
    function prepareInputForUpdate($input) {
        $input['date_mod'] = $_SESSION['glpi_currenttime'];
        return $input;
    }
    
    /**
     * Obter quadros disponíveis para um usuário
     */
    static function getBoardsForUser($user_id, $team_id = null) {
        global $DB;
        
        $boards = [];
        $where_team = $team_id ? "AND tb.teams_id = '$team_id'" : "";
        
        // Quadros que o usuário tem acesso através das equipes
        $query = "SELECT DISTINCT b.*, tb.can_edit, tb.can_manage, t.name as team_name
                  FROM glpi_plugin_scrumban_boards b
                  INNER JOIN glpi_plugin_scrumban_team_boards tb ON tb.boards_id = b.id
                  INNER JOIN glpi_plugin_scrumban_teams t ON t.id = tb.teams_id
                  INNER JOIN glpi_plugin_scrumban_team_members tm ON tm.teams_id = tb.teams_id
                  WHERE tm.users_id = '$user_id' AND b.is_active = 1 $where_team
                  
                  UNION
                  
                  SELECT b.*, 1 as can_edit, 1 as can_manage, 'Criador' as team_name
                  FROM glpi_plugin_scrumban_boards b
                  WHERE b.users_id_created = '$user_id' AND b.is_active = 1
                  
                  UNION
                  
                  SELECT b.*, 0 as can_edit, 0 as can_manage, 'Público' as team_name
                  FROM glpi_plugin_scrumban_boards b
                  WHERE b.visibility = 'public' AND b.is_active = 1
                  
                  ORDER BY name";
        
        $result = $DB->query($query);
        while ($data = $DB->fetchAssoc($result)) {
            $boards[$data['id']] = $data;
        }
        
        return $boards;
    }
    
    /**
     * Verificar se o usuário pode editar um quadro
     */
    static function canUserEditBoard($user_id, $board_id) {
        global $DB;

        if (Session::haveRight('config', UPDATE)) {
            return true;
        }

        $board = new self();
        if (!$board->getFromDB($board_id)) {
            return false;
        }

        if ($board->fields['users_id_created'] == $user_id) {
            return true;
        }

        if ($board->fields['visibility'] === 'public') {
            $query = "SELECT tb.can_edit
                      FROM glpi_plugin_scrumban_team_boards tb
                      INNER JOIN glpi_plugin_scrumban_team_members tm ON tm.teams_id = tb.teams_id
                      WHERE tb.boards_id = '" . (int)$board_id . "'
                        AND tm.users_id = '" . (int)$user_id . "'
                        AND tb.can_edit = 1";

            $result = $DB->query($query);
            if ($DB->numrows($result) > 0) {
                return true;
            }
        }

        $query = "SELECT tb.can_edit
                  FROM glpi_plugin_scrumban_team_boards tb
                  INNER JOIN glpi_plugin_scrumban_team_members tm ON tm.teams_id = tb.teams_id
                  WHERE tb.boards_id = '" . (int)$board_id . "'
                    AND tm.users_id = '" . (int)$user_id . "'
                    AND tb.can_edit = 1";

        $result = $DB->query($query);
        return $DB->numrows($result) > 0;
    }

    /**
     * Verificar se o usuário pode gerenciar um quadro
     */
    static function canUserManageBoard($user_id, $board_id) {
        global $DB;

        if (Session::haveRight('config', UPDATE)) {
            return true;
        }

        $board = new self();
        if (!$board->getFromDB($board_id)) {
            return false;
        }

        if ($board->fields['users_id_created'] == $user_id) {
            return true;
        }

        $query = "SELECT tb.can_manage
                  FROM glpi_plugin_scrumban_team_boards tb
                  INNER JOIN glpi_plugin_scrumban_team_members tm ON tm.teams_id = tb.teams_id
                  WHERE tb.boards_id = '" . (int)$board_id . "'
                    AND tm.users_id = '" . (int)$user_id . "'
                    AND tb.can_manage = 1";

        $result = $DB->query($query);
        return $DB->numrows($result) > 0;
    }
    
    /**
     * Obter estatísticas do quadro
     */
    function getStats() {
        global $DB;
        
        $stats = [
            'total_cards' => 0,
            'backlog' => 0,
            'todo' => 0,
            'em_execucao' => 0,
            'review' => 0,
            'done' => 0
        ];
        
        $query = "SELECT status, COUNT(*) as count 
                  FROM glpi_plugin_scrumban_cards 
                  WHERE boards_id = '" . $this->fields['id'] . "'
                  GROUP BY status";
        
        $result = $DB->query($query);
        while ($data = $DB->fetchAssoc($result)) {
            $stats[$data['status']] = $data['count'];
            $stats['total_cards'] += $data['count'];
        }
        
        return $stats;
    }
    
    /**
     * Renderizar quadro Kanban
     */
    function showKanbanBoard() {
        global $DB;
        
        $user_id = Session::getLoginUserID();
        
        // Verificar acesso
        if (!PluginScrumbanTeam::canUserAccessBoard($user_id, $this->fields['id'])) {
            echo "<div class='alert alert-danger'>" . __('Acesso negado', 'scrumban') . "</div>";
            return;
        }
        
        $can_edit = self::canUserEditBoard($user_id, $this->fields['id']);
        $can_manage = self::canUserManageBoard($user_id, $this->fields['id']);
        
        // Obter cards do quadro
        $query = "SELECT c.*, 
                         ua.realname as assigned_name, ua.firstname as assigned_firstname,
                         ur.realname as requester_name, ur.firstname as requester_firstname,
                         s.name as sprint_name
                  FROM glpi_plugin_scrumban_cards c
                  LEFT JOIN glpi_users ua ON ua.id = c.users_id_assigned
                  LEFT JOIN glpi_users ur ON ur.id = c.users_id_requester
                  LEFT JOIN glpi_plugin_scrumban_sprints s ON s.id = c.sprint_id
                  WHERE c.boards_id = '" . $this->fields['id'] . "'
                  ORDER BY c.id DESC";
        
        $result = $DB->query($query);
        $cards = [];
        while ($data = $DB->fetchAssoc($result)) {
            $cards[$data['status']][] = $data;
        }
        
        $columns = [
            'backlog' => ['name' => 'Backlog', 'color' => 'secondary'],
            'todo' => ['name' => 'A Fazer', 'color' => 'info'],
            'em-execucao' => ['name' => 'Em Execução', 'color' => 'warning'],
            'review' => ['name' => 'Review', 'color' => 'primary'],
            'done' => ['name' => 'Concluído', 'color' => 'success']
        ];
        
        echo "<div class='scrumban-board' data-board-id='" . $this->fields['id'] . "' data-can-edit='$can_edit'>";
        echo "<div class='row'>";
        
        foreach ($columns as $status => $column) {
            $count = count($cards[$status] ?? []);
            echo "<div class='col-md-2'>";
            echo "<div class='card kanban-column' data-status='$status'>";
            echo "<div class='card-header bg-" . $column['color'] . " text-white'>";
            echo "<h6 class='mb-0'>" . $column['name'] . " <span class='badge badge-light'>$count</span></h6>";
            echo "</div>";
            echo "<div class='card-body kanban-cards' style='min-height: 400px;'>";
            
            if (isset($cards[$status])) {
                foreach ($cards[$status] as $card) {
                    $this->renderCard($card);
                }
            }
            
            echo "</div>";
            echo "</div>";
            echo "</div>";
        }
        
        echo "</div>";
        echo "</div>";
        
        // Modal para detalhes do card
        $this->showCardModal();
    }
    
    /**
     * Renderizar um card individual
     */
    private function renderCard($card) {
        $priority_colors = [
            'LOW' => 'success',
            'NORMAL' => 'info', 
            'HIGH' => 'warning',
            'CRITICAL' => 'danger'
        ];
        
        $type_icons = [
            'feature' => 'fas fa-star',
            'bug' => 'fas fa-bug',
            'task' => 'fas fa-tasks',
            'story' => 'fas fa-book'
        ];
        
        echo "<div class='kanban-card mb-2' data-card-id='" . $card['id'] . "' onclick='showCardModal(" . $card['id'] . ")'>";
        echo "<div class='card card-sm'>";
        echo "<div class='card-body p-2'>";
        
        // Header do card
        echo "<div class='d-flex justify-content-between align-items-start mb-1'>";
        echo "<small class='text-muted'>#" . $card['id'] . "</small>";
        echo "<span class='badge badge-" . $priority_colors[$card['priority']] . "'>" . $card['priority'] . "</span>";
        echo "</div>";
        
        // Título
        echo "<h6 class='card-title mb-1' style='font-size: 0.9rem;'>" . $card['name'] . "</h6>";
        
        // Tipo e Story Points
        echo "<div class='d-flex justify-content-between align-items-center mb-1'>";
        echo "<small><i class='" . $type_icons[$card['type']] . "'></i> " . ucfirst($card['type']) . "</small>";
        if ($card['story_points']) {
            echo "<span class='badge badge-secondary'>" . $card['story_points'] . " SP</span>";
        }
        echo "</div>";
        
        // Responsável
        if ($card['assigned_name']) {
            echo "<small class='text-muted'>";
            echo "<i class='fas fa-user'></i> " . $card['assigned_firstname'] . " " . $card['assigned_name'];
            echo "</small>";
        }
        
        // Sprint
        if ($card['sprint_name']) {
            echo "<div><small class='text-info'><i class='fas fa-calendar'></i> " . $card['sprint_name'] . "</small></div>";
        }
        
        echo "</div>";
        echo "</div>";
        echo "</div>";
    }
    
    /**
     * Modal para detalhes do card
     */
    private function showCardModal() {
        echo "<div class='modal fade' id='cardModal' tabindex='-1'>";
        echo "<div class='modal-dialog modal-xl'>";
        echo "<div class='modal-content'>";
        echo "<div class='modal-header'>";
        echo "<h5 class='modal-title'>Detalhes do Card</h5>";
        echo "<button type='button' class='close' data-dismiss='modal'>&times;</button>";
        echo "</div>";
        echo "<div class='modal-body' id='cardModalBody'>";
        echo "Carregando...";
        echo "</div>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
    }
}