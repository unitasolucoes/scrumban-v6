<?php

include '../../../inc/includes.php';

if (!Session::haveRight('scrumban_team', READ)) {
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_team_boards':
        getTeamBoards();
        break;
        
    case 'get_dashboard_stats':
        getDashboardStats();
        break;
        
    default:
        echo "<option value=''>" . __('Selecione uma equipe primeiro', 'scrumban') . "</option>";
        break;
}

function getTeamBoards() {
    $team_id = $_POST['team_id'] ?? '';
    $user_id = Session::getLoginUserID();
    
    echo "<option value=''>" . __('Todos os quadros', 'scrumban') . "</option>";
    
    if ($team_id) {
        // Obter quadros da equipe específica
        global $DB;
        $query = "SELECT DISTINCT b.id, b.name
                  FROM glpi_plugin_scrumban_boards b
                  INNER JOIN glpi_plugin_scrumban_team_boards tb ON tb.boards_id = b.id
                  WHERE tb.teams_id = '$team_id' AND b.is_active = 1
                  ORDER BY b.name";
        
        $result = $DB->query($query);
        while ($board = $DB->fetchAssoc($result)) {
            echo "<option value='" . $board['id'] . "'>" . $board['name'] . "</option>";
        }
    } else {
        // Obter todos os quadros do usuário
        $boards = PluginScrumbanBoard::getBoardsForUser($user_id);
        foreach ($boards as $board) {
            echo "<option value='" . $board['id'] . "'>" . $board['name'] . "</option>";
        }
    }
}

function getDashboardStats() {
    $user_id = Session::getLoginUserID();
    $team_id = $_POST['team_id'] ?? '';
    $board_id = $_POST['board_id'] ?? '';
    
    global $DB;
    
    $stats = [
        'my_cards' => 0,
        'my_teams' => 0,
        'available_boards' => 0,
        'active_sprints' => 0,
        'status_distribution' => []
    ];
    
    // Construir WHERE clause baseado nos filtros
    $where_conditions = ["c.users_id_assigned = '$user_id'"];
    
    if ($team_id) {
        $where_conditions[] = "tb.teams_id = '$team_id'";
    }
    
    if ($board_id) {
        $where_conditions[] = "c.boards_id = '$board_id'";
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Minhas tarefas
    $query = "SELECT COUNT(*) as count
              FROM glpi_plugin_scrumban_cards c
              INNER JOIN glpi_plugin_scrumban_boards b ON b.id = c.boards_id
              INNER JOIN glpi_plugin_scrumban_team_boards tb ON tb.boards_id = b.id
              INNER JOIN glpi_plugin_scrumban_team_members tm ON tm.teams_id = tb.teams_id
              WHERE $where_clause";
    
    $result = $DB->query($query);
    $data = $DB->fetchAssoc($result);
    $stats['my_cards'] = $data['count'];
    
    // Distribuição por status
    $status_query = "SELECT c.status, COUNT(*) as count
                     FROM glpi_plugin_scrumban_cards c
                     INNER JOIN glpi_plugin_scrumban_boards b ON b.id = c.boards_id
                     INNER JOIN glpi_plugin_scrumban_team_boards tb ON tb.boards_id = b.id
                     INNER JOIN glpi_plugin_scrumban_team_members tm ON tm.teams_id = tb.teams_id
                     WHERE $where_clause
                     GROUP BY c.status";
    
    $status_result = $DB->query($status_query);
    while ($status_data = $DB->fetchAssoc($status_result)) {
        $stats['status_distribution'][$status_data['status']] = $status_data['count'];
    }
    
    // Minhas equipes
    $teams = PluginScrumbanTeam::getTeamsForUser($user_id);
    $stats['my_teams'] = count($teams);
    
    // Quadros disponíveis
    $boards = PluginScrumbanBoard::getBoardsForUser($user_id, $team_id);
    $stats['available_boards'] = count($boards);
    
    // Sprints ativos
    $sprint_query = "SELECT COUNT(*) as count
                     FROM glpi_plugin_scrumban_sprints s
                     INNER JOIN glpi_plugin_scrumban_boards b ON b.id = s.boards_id
                     INNER JOIN glpi_plugin_scrumban_team_boards tb ON tb.boards_id = b.id
                     INNER JOIN glpi_plugin_scrumban_team_members tm ON tm.teams_id = tb.teams_id
                     WHERE s.is_active = 1 AND tm.users_id = '$user_id'";
    
    if ($team_id) {
        $sprint_query .= " AND tb.teams_id = '$team_id'";
    }
    
    if ($board_id) {
        $sprint_query .= " AND s.boards_id = '$board_id'";
    }
    
    $sprint_result = $DB->query($sprint_query);
    $sprint_data = $DB->fetchAssoc($sprint_result);
    $stats['active_sprints'] = $sprint_data['count'];
    
    header('Content-Type: application/json');
    echo json_encode($stats);
}