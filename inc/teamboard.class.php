<?php

class PluginScrumbanTeamBoard extends CommonDBTM {
    static $rightname = 'scrumban_team';
    public $dohistory = true;

    static function getTypeName($nb = 0) {
        return _n('Quadro da Equipe', 'Quadros da Equipe', $nb, 'scrumban');
    }

    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if ($item->getType() == 'PluginScrumbanTeam') {
            $nb = countElementsInTable($this->getTable(), ['teams_id' => $item->getID()]);
            return self::createTabEntry(self::getTypeName($nb), $nb);
        }

        return '';
    }

    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if ($item->getType() == 'PluginScrumbanTeam') {
            self::showForTeam($item);
        }

        return true;
    }

    static function showForTeam(PluginScrumbanTeam $team) {
        global $DB;

        $team_id    = $team->fields['id'];
        $current_id = Session::getLoginUserID();
        $can_manage = $team->canUserManage($current_id);
        $stats      = self::getTeamBoardsStats($team_id);

        echo "<div class='spaced'>";

        if ($can_manage) {
            echo "<div class='right mb-2'>";
            echo "<button type='button' class='btn btn-primary' data-trigger='scrumban-open-add-board' data-team-id='" . $team_id . "'>";
            echo "<i class='fas fa-plus'></i> " . __('Associar quadro', 'scrumban');
            echo "</button>";
            echo "</div>";
        }

        echo "<table class='tab_cadre_fixehov'>";
        echo "<thead>";
        echo "<tr>";
        echo "<th>" . __('Nome', 'scrumban') . "</th>";
        echo "<th>" . __('Descrição', 'scrumban') . "</th>";
        echo "<th>" . __('Status', 'scrumban') . "</th>";
        echo "<th>" . __('Pode editar', 'scrumban') . "</th>";
        echo "<th>" . __('Pode gerenciar', 'scrumban') . "</th>";
        echo "<th>" . __('Cards', 'scrumban') . "</th>";
        echo "<th>" . __('Ações', 'scrumban') . "</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";

        $query = "SELECT tb.*, b.name, b.description, b.is_active, b.visibility\n                  FROM " . $DB->quoteName('glpi_plugin_scrumban_team_boards') . " tb\n                  INNER JOIN " . $DB->quoteName('glpi_plugin_scrumban_boards') . " b ON (b.id = tb.boards_id)\n                  WHERE tb.teams_id = '" . (int)$team_id . "'\n                  ORDER BY b.name";

        $result = $DB->query($query);
        if ($DB->numrows($result) === 0) {
            echo "<tr class='tab_bg_1'><td colspan='7' class='center'>" . __('Nenhum quadro associado', 'scrumban') . "</td></tr>";
        } else {
            while ($board = $DB->fetchAssoc($result)) {
                $board_id = (int)$board['boards_id'];
                $board_stats = $stats[$board_id] ?? ['card_count' => 0, 'done' => 0, 'in_progress' => 0];

                echo "<tr class='tab_bg_1'>";
                echo "<td>" . Html::link(Html::clean($board['name']), PluginScrumbanBoard::getFormURLWithID($board_id)) . "</td>";
                $description = trim((string)$board['description']);
                echo "<td>" . ($description !== '' ? Html::clean($description) : '-') . "</td>";
                echo "<td>" . ($board['is_active'] ? "<span class='badge badge-success'>" . __('Ativo') . "</span>" : "<span class='badge badge-secondary'>" . __('Inativo') . "</span>") . "</td>";
                echo "<td class='center'>" . self::renderPermissionBadge($board['can_edit']) . "</td>";
                echo "<td class='center'>" . self::renderPermissionBadge($board['can_manage']) . "</td>";
                $total_cards = (int)$board_stats['card_count'];
                $done_cards  = (int)$board_stats['done'];
                $in_progress = (int)$board_stats['in_progress'];
                echo "<td class='center'>";
                echo sprintf(__('%1$d cards (%2$d concluídos)', 'scrumban'), $total_cards, $done_cards);
                if ($in_progress > 0) {
                    echo "<br><small class='text-muted'>" . sprintf(__('Em andamento: %d', 'scrumban'), $in_progress) . "</small>";
                }
                echo "</td>";
                echo "<td class='center'>";

                if ($can_manage) {
                    echo "<div class='btn-group'>";
                    echo "<button type='button' class='btn btn-sm btn-outline-primary scrumban-edit-board-permissions' data-team-board-id='" . (int)$board['id'] . "' data-can-edit='" . (int)$board['can_edit'] . "' data-can-manage='" . (int)$board['can_manage'] . "'>";
                    echo "<i class='fas fa-user-shield'></i>";
                    echo "</button>";
                    echo "<button type='button' class='btn btn-sm btn-outline-danger scrumban-remove-board' data-team-board-id='" . (int)$board['id'] . "'>";
                    echo "<i class='fas fa-times'></i>";
                    echo "</button>";
                    echo "</div>";
                } else {
                    echo "<span class='text-muted'>" . __('Sem permissão', 'scrumban') . "</span>";
                }

                echo "</td>";
                echo "</tr>";
            }
        }

        echo "</tbody>";
        echo "</table>";
        echo "</div>";

        if ($can_manage) {
            self::showAddBoardModal($team_id);
            self::showEditPermissionsModal();
        }
    }

    static function showAddBoardModal($team_id) {
        global $DB;

        $current_id = Session::getLoginUserID();
        $boards      = PluginScrumbanBoard::getBoardsForUser($current_id);
        $already     = [];

        $res = $DB->query("SELECT boards_id FROM " . $DB->quoteName('glpi_plugin_scrumban_team_boards') . " WHERE teams_id = '" . (int)$team_id . "'");
        while ($row = $DB->fetchAssoc($res)) {
            $already[] = (int)$row['boards_id'];
        }

        echo "<div id='scrumbanAddBoardModal' class='modal' tabindex='-1' role='dialog' data-team-id='" . (int)$team_id . "' style='display:none;'>";
        echo "  <div class='modal-dialog' role='document'>";
        echo "    <div class='modal-content'>";
        echo "      <div class='modal-header'>";
        echo "        <h5 class='modal-title'><i class='fas fa-columns'></i> " . __('Associar quadro', 'scrumban') . "</h5>";
        echo "        <button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button>";
        echo "      </div>";
        echo "      <div class='modal-body'>";
        echo "        <form id='scrumbanAddBoardForm'>";
        echo "          <input type='hidden' name='teams_id' value='" . (int)$team_id . "'>";
        echo "          <div class='form-group'>";
        echo "            <label>" . __('Quadro', 'scrumban') . "</label>";
        $board_options = [0 => __('Selecione um quadro', 'scrumban')];
        foreach ($boards as $board) {
            if (in_array((int)$board['id'], $already)) {
                continue;
            }
            $board_options[$board['id']] = $board['name'];
        }
        Dropdown::showFromArray('boards_id', $board_options);
        echo "          </div>";
        echo "          <div class='form-group'>";
        echo "            <label class='d-block'>" . __('Permissões', 'scrumban') . "</label>";
        echo "            <div class='custom-control custom-checkbox'>";
        echo "              <input type='checkbox' class='custom-control-input' id='scrumbanBoardCanEdit' name='can_edit' value='1'>";
        echo "              <label class='custom-control-label' for='scrumbanBoardCanEdit'>" . __('Pode editar cards', 'scrumban') . "</label>";
        echo "            </div>";
        echo "            <div class='custom-control custom-checkbox'>";
        echo "              <input type='checkbox' class='custom-control-input' id='scrumbanBoardCanManage' name='can_manage' value='1'>";
        echo "              <label class='custom-control-label' for='scrumbanBoardCanManage'>" . __('Pode gerenciar quadro', 'scrumban') . "</label>";
        echo "            </div>";
        echo "          </div>";
        echo "        </form>";
        echo "      </div>";
        echo "      <div class='modal-footer'>";
        echo "        <button type='button' class='btn btn-secondary' data-dismiss='modal'>" . __('Cancelar') . "</button>";
        echo "        <button type='button' class='btn btn-primary' data-action='scrumban-confirm-add-board'>" . __('Associar', 'scrumban') . "</button>";
        echo "      </div>";
        echo "    </div>";
        echo "  </div>";
        echo "</div>";
    }

    static function showEditPermissionsModal() {
        echo "<div id='scrumbanEditBoardPermissionsModal' class='modal' tabindex='-1' role='dialog' style='display:none;'>";
        echo "  <div class='modal-dialog' role='document'>";
        echo "    <div class='modal-content'>";
        echo "      <div class='modal-header'>";
        echo "        <h5 class='modal-title'><i class='fas fa-user-shield'></i> " . __('Permissões do quadro', 'scrumban') . "</h5>";
        echo "        <button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button>";
        echo "      </div>";
        echo "      <div class='modal-body'>";
        echo "        <form id='scrumbanEditBoardPermissionsForm'>";
        echo "          <input type='hidden' name='id' value=''>";
        echo "          <div class='custom-control custom-checkbox'>";
        echo "            <input type='checkbox' class='custom-control-input' id='scrumbanEditBoardCanEdit' name='can_edit' value='1'>";
        echo "            <label class='custom-control-label' for='scrumbanEditBoardCanEdit'>" . __('Pode editar cards', 'scrumban') . "</label>";
        echo "          </div>";
        echo "          <div class='custom-control custom-checkbox mt-2'>";
        echo "            <input type='checkbox' class='custom-control-input' id='scrumbanEditBoardCanManage' name='can_manage' value='1'>";
        echo "            <label class='custom-control-label' for='scrumbanEditBoardCanManage'>" . __('Pode gerenciar quadro', 'scrumban') . "</label>";
        echo "          </div>";
        echo "        </form>";
        echo "      </div>";
        echo "      <div class='modal-footer'>";
        echo "        <button type='button' class='btn btn-secondary' data-dismiss='modal'>" . __('Cancelar') . "</button>";
        echo "        <button type='button' class='btn btn-primary' data-action='scrumban-confirm-edit-board'>" . __('Salvar', 'scrumban') . "</button>";
        echo "      </div>";
        echo "    </div>";
        echo "  </div>";
        echo "</div>";
    }

    static function teamHasBoard($team_id, $board_id) {
        return countElementsInTable('glpi_plugin_scrumban_team_boards', [
            'teams_id'  => (int)$team_id,
            'boards_id' => (int)$board_id
        ]) > 0;
    }

    static function addBoardToTeam($team_id, $board_id, $can_edit, $can_manage) {
        $association = new self();

        $data = [
            'teams_id'      => (int)$team_id,
            'boards_id'     => (int)$board_id,
            'can_edit'      => $can_manage ? 1 : (int)$can_edit,
            'can_manage'    => $can_manage ? 1 : (int)$can_manage,
            'date_creation' => $_SESSION['glpi_currenttime']
        ];

        return (bool)$association->add($data);
    }

    function removeBoard() {
        if (empty($this->fields['id'])) {
            return false;
        }

        return $this->delete(['id' => $this->fields['id']]);
    }

    function updatePermissions($can_edit, $can_manage) {
        if (empty($this->fields['id'])) {
            return false;
        }

        if ($can_manage) {
            $can_edit = 1;
        }

        return $this->update([
            'id'        => $this->fields['id'],
            'can_edit'  => (int)$can_edit,
            'can_manage'=> (int)$can_manage,
            'date_mod'  => $_SESSION['glpi_currenttime']
        ]);
    }

    static function getTeamBoardsStats($team_id) {
        global $DB;

        $stats = [];
        $query = "SELECT b.id AS board_id, COUNT(c.id) AS card_count,\n                         SUM(CASE WHEN c.status = 'done' THEN 1 ELSE 0 END) AS done_count,\n                         SUM(CASE WHEN c.status NOT IN ('done', 'backlog') THEN 1 ELSE 0 END) AS in_progress\n                  FROM " . $DB->quoteName('glpi_plugin_scrumban_team_boards') . " tb\n                  INNER JOIN " . $DB->quoteName('glpi_plugin_scrumban_boards') . " b ON (b.id = tb.boards_id)\n                  LEFT JOIN " . $DB->quoteName('glpi_plugin_scrumban_cards') . " c ON (c.boards_id = b.id)\n                  WHERE tb.teams_id = '" . (int)$team_id . "'\n                  GROUP BY b.id";

        $result = $DB->query($query);
        while ($row = $DB->fetchAssoc($result)) {
            $stats[(int)$row['board_id']] = [
                'card_count'  => (int)$row['card_count'],
                'done'        => (int)$row['done_count'],
                'in_progress' => (int)$row['in_progress']
            ];
        }

        return $stats;
    }

    private static function renderPermissionBadge($value) {
        if ($value) {
            return "<span class='badge badge-success'><i class='fas fa-check'></i></span>";
        }

        return "<span class='badge badge-secondary'><i class='fas fa-times'></i></span>";
    }
}
