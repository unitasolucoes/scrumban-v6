<?php

class PluginScrumbanTeamMember extends CommonDBTM {
    static $rightname = 'scrumban_team';
    public $dohistory = true;
    static $logs_for_parent = true;

    static function getTypeName($nb = 0) {
        return _n('Membro da Equipe', 'Membros da Equipe', $nb, 'scrumban');
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
        $user_role  = PluginScrumbanTeam::getUserRole($current_id, $team_id);

        echo "<div class='spaced'>";

        if ($can_manage) {
            echo "<div class='right mb-2'>";
            echo "<button type='button' class='btn btn-primary' data-trigger='scrumban-open-add-member' data-team-id='" . $team_id . "'>";
            echo "<i class='fas fa-user-plus'></i> " . __('Adicionar membro', 'scrumban');
            echo "</button>";
            echo "</div>";
        }

        echo "<table class='tab_cadre_fixehov'>";
        echo "<thead>";
        echo "<tr>";
        echo "<th>" . __('Usuário', 'scrumban') . "</th>";
        echo "<th>" . __('Papel', 'scrumban') . "</th>";
        echo "<th>" . __('Data de entrada', 'scrumban') . "</th>";
        echo "<th>" . __('Ações', 'scrumban') . "</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";

        $query = "SELECT tm.*, u.firstname, u.realname, u.name AS login\n                  FROM " . $DB->quoteName('glpi_plugin_scrumban_team_members') . " tm\n                  LEFT JOIN " . $DB->quoteName('glpi_users') . " u ON (u.id = tm.users_id)\n                  WHERE tm.teams_id = '" . (int)$team_id . "'\n                  ORDER BY FIELD(tm.role, 'admin', 'lead', 'member'), u.realname";

        $result = $DB->query($query);
        if ($DB->numrows($result) === 0) {
            echo "<tr class='tab_bg_1'><td colspan='4' class='center'>" . __('Nenhum membro cadastrado', 'scrumban') . "</td></tr>";
        } else {
            while ($member = $DB->fetchAssoc($result)) {
                $fullname = trim(($member['firstname'] ?? '') . ' ' . ($member['realname'] ?? ''));
                if ($fullname === '') {
                    $fullname = $member['login'];
                }

                echo "<tr class='tab_bg_1'>";
                $user_cell = Html::clean($fullname ?: __('Usuário removido', 'scrumban'));
                if (!empty($member['users_id'])) {
                    $user_cell = Html::link($user_cell, User::getFormURLWithID($member['users_id']));
                }
                echo "<td>" . $user_cell . "</td>";
                echo "<td>" . self::getRoleBadge($member['role']) . "</td>";
                echo "<td>" . ($member['date_creation'] ? Html::convDateTime($member['date_creation']) : '-') . "</td>";
                echo "<td class='center'>";

                if ($can_manage && $member['users_id'] != $current_id) {
                    $role_options = PluginScrumbanTeam::getRoleOptions();
                    echo "<div class='d-flex justify-content-center align-items-center'>";
                    echo "<select class='scrumban-change-role' data-member-id='" . (int)$member['id'] . "' data-team-id='" . $team_id . "'>";
                    foreach ($role_options as $role_key => $role_label) {
                        $selected = $member['role'] === $role_key ? " selected" : "";
                        echo "<option value='" . $role_key . "'" . $selected . ">" . Html::clean($role_label) . "</option>";
                    }
                    echo "</select>";

                    echo "<button type='button' class='btn btn-sm btn-outline-primary ml-1 scrumban-apply-role' data-member-id='" . (int)$member['id'] . "'>";
                    echo "<i class='fas fa-save'></i>";
                    echo "</button>";

                    echo "<button type='button' class='btn btn-sm btn-outline-danger ml-1 scrumban-remove-member' data-member-id='" . (int)$member['id'] . "'>";
                    echo "<i class='fas fa-user-minus'></i>";
                    echo "</button>";
                    echo "</div>";
                } else {
                    if ($member['users_id'] == $current_id) {
                        echo "<span class='text-muted'>" . __('É você', 'scrumban') . "</span>";
                    } else {
                        echo "<span class='text-muted'>" . __('Sem permissão', 'scrumban') . "</span>";
                    }
                }

                echo "</td>";
                echo "</tr>";
            }
        }

        echo "</tbody>";
        echo "</table>";
        echo "</div>";

        if ($can_manage) {
            self::showAddMemberModal($team_id);
        }
    }

    static function showAddMemberModal($team_id) {
        echo "<div id='scrumbanAddMemberModal' class='modal' tabindex='-1' role='dialog' data-team-id='" . (int)$team_id . "' style='display:none;'>";
        echo "  <div class='modal-dialog' role='document'>";
        echo "    <div class='modal-content'>";
        echo "      <div class='modal-header'>";
        echo "        <h5 class='modal-title'><i class='fas fa-user-plus'></i> " . __('Adicionar membro à equipe', 'scrumban') . "</h5>";
        echo "        <button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button>";
        echo "      </div>";
        echo "      <div class='modal-body'>";
        echo "        <form id='scrumbanAddMemberForm'>";
        echo "          <input type='hidden' name='teams_id' value='" . (int)$team_id . "'>";
        echo "          <div class='form-group'>";
        echo "            <label>" . __('Usuário', 'scrumban') . "</label>";
        User::dropdown([
            'name'  => 'users_id',
            'entity' => $_SESSION['glpiactive_entity'] ?? 0,
            'right' => 'all'
        ]);
        echo "          </div>";
        echo "          <div class='form-group'>";
        echo "            <label>" . __('Papel', 'scrumban') . "</label>";
        Dropdown::showFromArray('role', PluginScrumbanTeam::getRoleOptions(), ['value' => 'member']);
        echo "          </div>";
        echo "        </form>";
        echo "      </div>";
        echo "      <div class='modal-footer'>";
        echo "        <button type='button' class='btn btn-secondary' data-dismiss='modal'>" . __('Cancelar') . "</button>";
        echo "        <button type='button' class='btn btn-primary' data-action='scrumban-confirm-add-member'>" . __('Adicionar', 'scrumban') . "</button>";
        echo "      </div>";
        echo "    </div>";
        echo "  </div>";
        echo "</div>";
    }

    static function isUserInTeam($user_id, $team_id) {
        return countElementsInTable('glpi_plugin_scrumban_team_members', [
            'users_id' => (int)$user_id,
            'teams_id' => (int)$team_id
        ]) > 0;
    }

    static function addMemberToTeam($team_id, $user_id, $role) {
        if (self::isUserInTeam($user_id, $team_id)) {
            return false;
        }

        $member = new self();
        $data   = [
            'teams_id'      => (int)$team_id,
            'users_id'      => (int)$user_id,
            'role'          => $role,
            'date_creation' => $_SESSION['glpi_currenttime'],
        ];

        return (bool)$member->add($data);
    }

    function removeMember() {
        if (empty($this->fields['id'])) {
            return false;
        }

        $team_id = (int)$this->fields['teams_id'];

        if ($this->fields['role'] === 'admin') {
            $admins = countElementsInTable('glpi_plugin_scrumban_team_members', [
                'teams_id' => $team_id,
                'role'     => 'admin'
            ]);

            if ($admins <= 1) {
                return false;
            }
        }

        return $this->delete(['id' => $this->fields['id']]);
    }

    function changeRole($new_role) {
        if (empty($this->fields['id'])) {
            return false;
        }

        $team_id = (int)$this->fields['teams_id'];

        if ($this->fields['role'] === 'admin' && $new_role !== 'admin') {
            global $DB;

            $query = "SELECT COUNT(*) AS nb\n                      FROM " . $DB->quoteName('glpi_plugin_scrumban_team_members') . "\n                      WHERE teams_id = '" . $team_id . "'\n                        AND role = 'admin'\n                        AND id <> '" . (int)$this->fields['id'] . "'";
            $result = $DB->query($query);
            $data   = $DB->fetchAssoc($result);

            if ((int)$data['nb'] === 0) {
                return false;
            }
        }

        return $this->update([
            'id'   => $this->fields['id'],
            'role' => $new_role
        ]);
    }

    static function getRoleBadge($role) {
        $labels = PluginScrumbanTeam::getRoleOptions();
        $colors = [
            'member' => 'secondary',
            'lead'   => 'info',
            'admin'  => 'success'
        ];

        $label = $labels[$role] ?? $role;
        $color = $colors[$role] ?? 'secondary';

        return "<span class='badge badge-$color'>" . Html::clean($label) . "</span>";
    }

    function prepareInputForAdd($input) {
        if (!isset($input['date_creation'])) {
            $input['date_creation'] = $_SESSION['glpi_currenttime'];
        }

        return parent::prepareInputForAdd($input);
    }
}
