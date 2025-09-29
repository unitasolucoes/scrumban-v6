<?php

class PluginScrumbanTeam extends CommonDBTM {
    
    static $rightname = 'scrumban_team';
    
    static function getTypeName($nb = 0) {
        return _n('Equipe', 'Equipes', $nb, 'scrumban');
    }
    
    static function getIcon() {
        return 'fas fa-users';
    }
    
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if ($item->getType() == 'User') {
            return __('Equipes Scrumban', 'scrumban');
        }
        return '';
    }
    
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if ($item->getType() == 'User') {
            self::showForUser($item);
        }
        return true;
    }
    
    function defineTabs($options = []) {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab('PluginScrumbanTeamMember', $ong, $options);
        $this->addStandardTab('PluginScrumbanTeamBoard', $ong, $options);
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
        echo "<td>" . __('Gerente', 'scrumban') . "</td>";
        echo "<td>";
        User::dropdown(['name' => 'manager_id', 'value' => $this->fields['manager_id']]);
        echo "</td>";
        echo "<td colspan='2'></td>";
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
        $creator_id = Session::getLoginUserID();

        $member = new PluginScrumbanTeamMember();
        $member->add([
            'teams_id'      => $this->fields['id'],
            'users_id'      => $creator_id,
            'role'          => 'admin',
            'date_creation' => $_SESSION['glpi_currenttime']
        ]);

        if (class_exists('Log')) {
            Log::history(
                $this->fields['id'],
                $this->getType(),
                [0, '', ''],
                0,
                Log::HISTORY_CREATE_ITEM
            );
        }
    }
    
    function prepareInputForUpdate($input) {
        $input['date_mod'] = $_SESSION['glpi_currenttime'];
        return $input;
    }
    
    /**
     * Verificar se o usuário pode acessar um quadro específico
     */
    static function canUserAccessBoard($user_id, $board_id) {
        global $DB;
        
        // Verificar se o usuário é admin do sistema
        if (Session::haveRight('config', UPDATE)) {
            return true;
        }
        
        // Verificar se o usuário criou o quadro
        $board = new PluginScrumbanBoard();
        if ($board->getFromDB($board_id) && $board->fields['users_id_created'] == $user_id) {
            return true;
        }
        
        // Verificar se o quadro é público
        if ($board->fields['visibility'] == 'public') {
            return true;
        }
        
        // Verificar se o usuário faz parte de alguma equipe que tem acesso ao quadro
        $query = "SELECT tb.can_edit, tb.can_manage 
                  FROM glpi_plugin_scrumban_team_boards tb
                  INNER JOIN glpi_plugin_scrumban_team_members tm ON tm.teams_id = tb.teams_id
                  WHERE tb.boards_id = '$board_id' AND tm.users_id = '$user_id'";
        
        $result = $DB->query($query);
        return $DB->numrows($result) > 0;
    }
    
    /**
     * Obter todas as equipes de um usuário
     */
    static function getTeamsForUser($user_id) {
        global $DB;
        
        $teams = [];
        $query = "SELECT t.*, tm.role 
                  FROM glpi_plugin_scrumban_teams t
                  INNER JOIN glpi_plugin_scrumban_team_members tm ON tm.teams_id = t.id
                  WHERE tm.users_id = '$user_id' AND t.is_active = 1
                  ORDER BY t.name";
        
        $result = $DB->query($query);
        while ($data = $DB->fetchAssoc($result)) {
            $teams[] = $data;
        }
        
        return $teams;
    }
    
    /**
     * Obter o papel do usuário em uma equipe específica
     */
    static function getUserRole($user_id, $team_id) {
        global $DB;
        
        $query = "SELECT role FROM glpi_plugin_scrumban_team_members 
                  WHERE users_id = '$user_id' AND teams_id = '$team_id'";
        
        $result = $DB->query($query);
        if ($DB->numrows($result) > 0) {
            $data = $DB->fetchAssoc($result);
            return $data['role'];
        }
        
        return false;
    }
    
    /**
     * Verificar se o usuário pode gerenciar uma equipe
     */
    function canUserManage($user_id) {
        if (Session::haveRight('config', UPDATE)) {
            return true;
        }

        if (!empty($this->fields['users_id_created']) && $this->fields['users_id_created'] == $user_id) {
            return true;
        }

        $role = self::getUserRole($user_id, $this->fields['id']);
        return in_array($role, ['admin', 'lead']);
    }
    
    /**
     * Mostrar equipes para um usuário
     */
    static function showForUser(User $user) {
        $teams = self::getTeamsForUser($user->fields['id']);
        
        echo "<div class='spaced'>";
        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr class='tab_bg_1'>";
        echo "<th>" . __('Equipe', 'scrumban') . "</th>";
        echo "<th>" . __('Papel', 'scrumban') . "</th>";
        echo "<th>" . __('Descrição', 'scrumban') . "</th>";
        echo "</tr>";
        
        foreach ($teams as $team) {
            echo "<tr class='tab_bg_2'>";
            echo "<td><a href='" . PluginScrumbanTeam::getFormURLWithID($team['id']) . "'>" . $team['name'] . "</a></td>";
            echo "<td>" . self::getRoleName($team['role']) . "</td>";
            echo "<td>" . $team['description'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "</div>";
    }
    
    /**
     * Obter nome do papel
     */
    static function getRoleName($role) {
        $roles = [
            'member' => __('Membro', 'scrumban'),
            'lead' => __('Líder', 'scrumban'),
            'admin' => __('Administrador', 'scrumban')
        ];
        
        return $roles[$role] ?? $role;
    }
    
    /**
     * Obter opções de papel
     */
    static function getRoleOptions() {
        return [
            'member' => __('Membro', 'scrumban'),
            'lead' => __('Líder', 'scrumban'),
            'admin' => __('Administrador', 'scrumban')
        ];
    }
}