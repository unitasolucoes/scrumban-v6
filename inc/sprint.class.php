<?php

class PluginScrumbanSprint extends CommonDBTM {
    
    static $rightname = 'scrumban_board';
    
    static function getTypeName($nb = 0) {
        return _n('Sprint', 'Sprints', $nb, 'scrumban');
    }
    
    static function getIcon() {
        return 'fas fa-calendar-alt';
    }
    
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if ($item->getType() == 'PluginScrumbanBoard') {
            global $DB;
            $count = $DB->request([
                'COUNT' => 'cpt',
                'FROM' => $this->getTable(),
                'WHERE' => ['boards_id' => $item->getField('id')]
            ])->current()['cpt'];
            
            return self::createTabEntry(self::getTypeName($count), $count);
        }
        return '';
    }
    
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if ($item->getType() == 'PluginScrumbanBoard') {
            self::showForBoard($item);
        }
        return true;
    }
    
    function showForm($ID, $options = []) {
        $this->initForm($ID, $options);
        $this->showFormHeader($options);
        
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
        
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Descrição', 'scrumban') . "</td>";
        echo "<td colspan='3'>";
        echo "<textarea name='description' rows='3' cols='80'>" . $this->fields['description'] . "</textarea>";
        echo "</td>";
        echo "</tr>";
        
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Data de Início', 'scrumban') . "</td>";
        echo "<td>";
        Html::showDateTimeField('date_start', ['value' => $this->fields['date_start']]);
        echo "</td>";
        echo "<td>" . __('Data de Fim', 'scrumban') . "</td>";
        echo "<td>";
        Html::showDateTimeField('date_end', ['value' => $this->fields['date_end']]);
        echo "</td>";
        echo "</tr>";
        
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Ativo', 'scrumban') . "</td>";
        echo "<td>";
        Dropdown::showYesNo('is_active', $this->fields['is_active']);
        echo "</td>";
        echo "<td colspan='2'></td>";
        echo "</tr>";
        
        $this->showFormButtons($options);
        return true;
    }
    
    function prepareInputForAdd($input) {
        $input['date_creation'] = $_SESSION['glpi_currenttime'];
        
        if (!isset($input['entities_id'])) {
            $input['entities_id'] = $_SESSION['glpiactive_entity'];
        }
        
        return $input;
    }
    
    function prepareInputForUpdate($input) {
        $input['date_mod'] = $_SESSION['glpi_currenttime'];
        return $input;
    }
    
    function post_addItem() {
        if ($this->fields['is_active']) {
            $this->setAsActiveSprint();
        }
    }
    
    function post_updateItem($history = 1) {
        if ($this->fields['is_active']) {
            $this->setAsActiveSprint();
        }
    }
    
    function setAsActiveSprint() {
        global $DB;
        
        $DB->update(
            $this->getTable(),
            ['is_active' => 0],
            [
                'boards_id' => $this->fields['boards_id'],
                'id' => ['!=', $this->fields['id']]
            ]
        );
    }
    
    static function showForBoard(PluginScrumbanBoard $board) {
        global $DB;
        
        $board_id = $board->fields['id'];
        $user_id = Session::getLoginUserID();
        
        $can_manage = PluginScrumbanBoard::canUserManageBoard($user_id, $board_id);
        
        echo "<div class='spaced'>";
        
        if ($can_manage) {
            echo "<div class='center mb-3'>";
            echo "<a href='" . PluginScrumbanSprint::getFormURL() . "?boards_id=$board_id' class='btn btn-primary'>";
            echo "<i class='fas fa-plus'></i> " . __('Novo Sprint', 'scrumban');
            echo "</a>";
            echo "</div>";
        }
        
        $iterator = $DB->request([
            'SELECT' => ['s.*', new \QueryExpression('COUNT(' . $DB->quoteName('c.id') . ') as card_count')],
            'FROM' => 'glpi_plugin_scrumban_sprints AS s',
            'LEFT JOIN' => [
                'glpi_plugin_scrumban_cards AS c' => [
                    'ON' => [
                        'c' => 'sprint_id',
                        's' => 'id'
                    ]
                ]
            ],
            'WHERE' => ['s.boards_id' => $board_id],
            'GROUPBY' => 's.id',
            'ORDER' => ['s.is_active DESC', 's.date_start DESC']
        ]);
        
        if (count($iterator) == 0) {
            echo "<p class='text-center text-muted'>" . __('Nenhum sprint criado ainda.', 'scrumban') . "</p>";
        } else {
            echo "<div class='row'>";
            
            foreach ($iterator as $sprint) {
                echo "<div class='col-md-6 col-lg-4 mb-4'>";
                echo "<div class='card" . ($sprint['is_active'] ? ' border-success' : '') . "'>";
                
                if ($sprint['is_active']) {
                    echo "<div class='card-header bg-success text-white'>";
                    echo "<h6 class='mb-0'><i class='fas fa-play'></i> " . __('Sprint Ativo', 'scrumban') . "</h6>";
                    echo "</div>";
                }
                
                echo "<div class='card-body'>";
                echo "<h5 class='card-title'>" . $sprint['name'] . "</h5>";
                
                if ($sprint['description']) {
                    echo "<p class='card-text text-muted'>" . nl2br($sprint['description']) . "</p>";
                }
                
                if ($sprint['date_start']) {
                    echo "<div class='mb-2'>";
                    echo "<small class='text-muted'>";
                    echo "<i class='fas fa-calendar'></i> " . Html::convDateTime($sprint['date_start']);
                    if ($sprint['date_end']) {
                        echo " - " . Html::convDateTime($sprint['date_end']);
                    }
                    echo "</small>";
                    echo "</div>";
                }
                
                echo "<div class='row text-center'>";
                echo "<div class='col-6'>";
                echo "<h6 class='text-primary mb-0'>" . $sprint['card_count'] . "</h6>";
                echo "<small class='text-muted'>" . __('Cards', 'scrumban') . "</small>";
                echo "</div>";
                echo "<div class='col-6'>";
                
                $progress = self::getSprintProgress($sprint['id']);
                echo "<h6 class='text-success mb-0'>" . $progress . "%</h6>";
                echo "<small class='text-muted'>" . __('Concluído', 'scrumban') . "</small>";
                echo "</div>";
                echo "</div>";
                
                if ($sprint['card_count'] > 0) {
                    echo "<div class='mt-2'>";
                    echo "<div class='progress' style='height: 5px;'>";
                    echo "<div class='progress-bar bg-success' style='width: $progress%'></div>";
                    echo "</div>";
                    echo "</div>";
                }
                
                echo "</div>";
                
                if ($can_manage) {
                    echo "<div class='card-footer'>";
                    echo "<div class='btn-group btn-group-sm w-100'>";
                    echo "<a href='" . PluginScrumbanSprint::getFormURLWithID($sprint['id']) . "' class='btn btn-outline-primary'>";
                    echo "<i class='fas fa-edit'></i> " . __('Editar', 'scrumban');
                    echo "</a>";
                    
                    if (!$sprint['is_active']) {
                        echo "<button type='button' class='btn btn-outline-success' onclick='activateSprint(" . $sprint['id'] . ")'>";
                        echo "<i class='fas fa-play'></i> " . __('Ativar', 'scrumban');
                        echo "</button>";
                    } else {
                        echo "<button type='button' class='btn btn-outline-warning' onclick='deactivateSprint(" . $sprint['id'] . ")'>";
                        echo "<i class='fas fa-pause'></i> " . __('Desativar', 'scrumban');
                        echo "</button>";
                    }
                    
                    echo "</div>";
                    echo "</div>";
                }
                
                echo "</div>";
                echo "</div>";
            }
            
            echo "</div>";
        }
        
        echo "</div>";
    }
    
    static function getSprintProgress($sprint_id) {
        global $DB;
        
        $total_result = $DB->request([
            'COUNT' => 'cpt',
            'FROM' => 'glpi_plugin_scrumban_cards',
            'WHERE' => ['sprint_id' => $sprint_id]
        ]);
        $total = $total_result->current()['cpt'];
        
        if ($total == 0) {
            return 0;
        }
        
        $done_result = $DB->request([
            'COUNT' => 'cpt',
            'FROM' => 'glpi_plugin_scrumban_cards',
            'WHERE' => [
                'sprint_id' => $sprint_id,
                'status' => 'done'
            ]
        ]);
        $done = $done_result->current()['cpt'];
        
        return round(($done / $total) * 100);
    }
    
    static function getActiveSprint($board_id) {
        global $DB;
        
        $iterator = $DB->request([
            'FROM' => 'glpi_plugin_scrumban_sprints',
            'WHERE' => [
                'boards_id' => $board_id,
                'is_active' => 1
            ],
            'LIMIT' => 1
        ]);
        
        if (count($iterator) > 0) {
            return $iterator->current();
        }
        
        return null;
    }
    
    function activate() {
        $this->setAsActiveSprint();
        return $this->update(['id' => $this->fields['id'], 'is_active' => 1]);
    }
    
    function deactivate() {
        return $this->update(['id' => $this->fields['id'], 'is_active' => 0]);
    }
    
    function getDetailedStats() {
        global $DB;
        
        $stats = [
            'total' => 0,
            'backlog' => 0,
            'todo' => 0,
            'em_execucao' => 0,
            'review' => 0,
            'done' => 0,
            'story_points_total' => 0,
            'story_points_done' => 0
        ];
        
        $iterator = $DB->request([
            'SELECT' => [
                'status',
                new \QueryExpression('COUNT(*) as count'),
                new \QueryExpression('SUM(' . $DB->quoteName('story_points') . ') as points')
            ],
            'FROM' => 'glpi_plugin_scrumban_cards',
            'WHERE' => ['sprint_id' => $this->fields['id']],
            'GROUPBY' => 'status'
        ]);
        
        foreach ($iterator as $data) {
            $stats[$data['status']] = $data['count'];
            $stats['total'] += $data['count'];
            $stats['story_points_total'] += $data['points'] ?: 0;
            
            if ($data['status'] == 'done') {
                $stats['story_points_done'] = $data['points'] ?: 0;
            }
        }
        
        return $stats;
    }
    
    static function getSprintsForBoard($board_id) {
        global $DB;
        
        $sprints = [];
        $iterator = $DB->request([
            'FROM' => 'glpi_plugin_scrumban_sprints',
            'WHERE' => ['boards_id' => $board_id],
            'ORDER' => ['is_active DESC', 'date_creation DESC']
        ]);
        
        foreach ($iterator as $data) {
            $sprints[] = $data;
        }
        
        return $sprints;
    }
    
    function addCard($card_id) {
        global $DB;
        
        return $DB->update(
            'glpi_plugin_scrumban_cards',
            ['sprint_id' => $this->fields['id']],
            ['id' => $card_id]
        );
    }
    
    function removeCard($card_id) {
        global $DB;
        
        return $DB->update(
            'glpi_plugin_scrumban_cards',
            ['sprint_id' => 0],
            ['id' => $card_id, 'sprint_id' => $this->fields['id']]
        );
    }
    
    function getCards() {
        global $DB;
        
        $cards = [];
        $iterator = $DB->request([
            'SELECT' => [
                'c.*',
                'ua.realname as assigned_name',
                'ua.firstname as assigned_firstname',
                'ur.realname as requester_name',
                'ur.firstname as requester_firstname'
            ],
            'FROM' => 'glpi_plugin_scrumban_cards AS c',
            'LEFT JOIN' => [
                'glpi_users AS ua' => [
                    'ON' => [
                        'ua' => 'id',
                        'c' => 'users_id_assigned'
                    ]
                ],
                'glpi_users AS ur' => [
                    'ON' => [
                        'ur' => 'id',
                        'c' => 'users_id_requester'
                    ]
                ]
            ],
            'WHERE' => ['c.sprint_id' => $this->fields['id']],
            'ORDER' => ['c.status', 'c.priority DESC', 'c.id']
        ]);
        
        foreach ($iterator as $data) {
            $cards[] = $data;
        }
        
        return $cards;
    }
    
    // CORREÇÃO: Removido 'static' da linha 415
    function canDeleteItem() {
        global $DB;
        
        $count = $DB->request([
            'COUNT' => 'cpt',
            'FROM' => 'glpi_plugin_scrumban_cards',
            'WHERE' => ['sprint_id' => $this->fields['id']]
        ])->current()['cpt'];
        
        return $count == 0;
    }
}