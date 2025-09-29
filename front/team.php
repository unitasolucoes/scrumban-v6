<?php

include '../../../inc/includes.php';

Html::header(__('Equipes Scrumban', 'scrumban'), $_SERVER['PHP_SELF'], "tools", "pluginscrumbanmenu", "teams");

if (!Session::haveRight('scrumban_team', READ)) {
    Html::displayRightError();
}

$user_id = Session::getLoginUserID();

// Processar ações
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'create_team':
            if (Session::haveRight('scrumban_team', CREATE)) {
                $team = new PluginScrumbanTeam();
                if ($team->add($_POST)) {
                    Session::addMessageAfterRedirect(__('Equipe criada com sucesso', 'scrumban'), true);
                } else {
                    Session::addMessageAfterRedirect(__('Erro ao criar equipe', 'scrumban'), false, ERROR);
                }
            }
            Html::redirect($_SERVER['PHP_SELF']);
            break;
    }
}

echo "<div class='container-fluid'>";

// Header
echo "<div class='row mb-4'>";
echo "<div class='col-md-12'>";
echo "<div class='card'>";
echo "<div class='card-header d-flex justify-content-between align-items-center'>";
echo "<h4 class='mb-0'><i class='fas fa-users'></i> " . __('Equipes Scrumban', 'scrumban') . "</h4>";

if (Session::haveRight('scrumban_team', CREATE)) {
    echo "<button type='button' class='btn btn-primary' onclick='showNewTeamModal()'>";
    echo "<i class='fas fa-plus'></i> " . __('Nova Equipe', 'scrumban');
    echo "</button>";
}

echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

// Obter equipes do usuário
$teams = PluginScrumbanTeam::getTeamsForUser($user_id);

if (empty($teams)) {
    echo "<div class='row'>";
    echo "<div class='col-md-12'>";
    echo "<div class='card'>";
    echo "<div class='card-body text-center'>";
    echo "<i class='fas fa-users fa-4x text-muted mb-3'></i>";
    echo "<h5>" . __('Nenhuma equipe encontrada', 'scrumban') . "</h5>";
    echo "<p class='text-muted'>" . __('Você ainda não faz parte de nenhuma equipe ou não foi criada nenhuma equipe.', 'scrumban') . "</p>";
    
    if (Session::haveRight('scrumban_team', CREATE)) {
        echo "<button type='button' class='btn btn-primary' onclick='showNewTeamModal()'>";
        echo "<i class='fas fa-plus'></i> " . __('Criar Primeira Equipe', 'scrumban');
        echo "</button>";
    }
    
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
} else {
    // Grid de equipes
    echo "<div class='row'>";
    
    foreach ($teams as $team) {
        echo "<div class='col-md-6 col-lg-4 mb-4'>";
        echo "<div class='card h-100'>";
        echo "<div class='card-body'>";
        
        // Header do card da equipe
        echo "<div class='d-flex justify-content-between align-items-start mb-3'>";
        echo "<h5 class='card-title mb-0'>" . $team['name'] . "</h5>";
        echo PluginScrumbanTeamMember::getRoleBadge($team['role']);
        echo "</div>";
        
        // Descrição
        if ($team['description']) {
            echo "<p class='card-text text-muted'>" . nl2br($team['description']) . "</p>";
        }
        
        // Estatísticas da equipe
        $stats = PluginScrumbanTeamBoard::getTeamBoardsStats($team['id']);
        $total_boards = count($stats);
        $total_cards = array_sum(array_column($stats, 'card_count'));
        
        echo "<div class='row text-center mb-3'>";
        echo "<div class='col-6'>";
        echo "<div class='border-right'>";
        echo "<h6 class='text-primary mb-0'>$total_boards</h6>";
        echo "<small class='text-muted'>" . __('Quadros', 'scrumban') . "</small>";
        echo "</div>";
        echo "</div>";
        echo "<div class='col-6'>";
        echo "<h6 class='text-success mb-0'>$total_cards</h6>";
        echo "<small class='text-muted'>" . __('Cards', 'scrumban') . "</small>";
        echo "</div>";
        echo "</div>";
        
        // Ações
        echo "<div class='card-footer bg-transparent'>";
        echo "<div class='btn-group btn-group-sm w-100'>";
        
        echo "<a href='" . PluginScrumbanTeam::getFormURLWithID($team['id']) . "' class='btn btn-outline-primary'>";
        echo "<i class='fas fa-eye'></i> " . __('Ver', 'scrumban');
        echo "</a>";
        
        if (in_array($team['role'], ['admin', 'lead'])) {
            echo "<a href='" . PluginScrumbanTeam::getFormURLWithID($team['id']) . "' class='btn btn-outline-secondary'>";
            echo "<i class='fas fa-cog'></i> " . __('Gerenciar', 'scrumban');
            echo "</a>";
        }
        
        // Link para quadros da equipe
        if ($total_boards > 0) {
            echo "<a href='" . $CFG_GLPI['root_doc'] . "/plugins/scrumban/front/board.php?team_id=" . $team['id'] . "' class='btn btn-outline-success'>";
            echo "<i class='fas fa-columns'></i> " . __('Quadros', 'scrumban');
            echo "</a>";
        }
        
        echo "</div>";
        echo "</div>";
        
        echo "</div>";
        echo "</div>";
        echo "</div>";
    }
    
    echo "</div>";
}

// Todas as equipes (para admins)
if (Session::haveRight('config', UPDATE)) {
    echo "<div class='row mt-5'>";
    echo "<div class='col-md-12'>";
    echo "<div class='card'>";
    echo "<div class='card-header'>";
    echo "<h5><i class='fas fa-users-cog'></i> " . __('Todas as Equipes (Admin)', 'scrumban') . "</h5>";
    echo "</div>";
    echo "<div class='card-body'>";
    
    global $DB;
    $query = "SELECT t.*, u.realname as manager_name, u.firstname as manager_firstname,
                     COUNT(tm.id) as member_count
              FROM glpi_plugin_scrumban_teams t
              LEFT JOIN glpi_users u ON u.id = t.manager_id
              LEFT JOIN glpi_plugin_scrumban_team_members tm ON tm.teams_id = t.id
              WHERE t.is_active = 1
              GROUP BY t.id
              ORDER BY t.name";
    
    $result = $DB->query($query);
    
    if ($DB->numrows($result) > 0) {
        echo "<table class='table table-striped'>";
        echo "<thead>";
        echo "<tr>";
        echo "<th>" . __('Nome', 'scrumban') . "</th>";
        echo "<th>" . __('Gerente', 'scrumban') . "</th>";
        echo "<th>" . __('Membros', 'scrumban') . "</th>";
        echo "<th>" . __('Criada em', 'scrumban') . "</th>";
        echo "<th>" . __('Ações', 'scrumban') . "</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";
        
        while ($team_data = $DB->fetchAssoc($result)) {
            echo "<tr>";
            echo "<td>";
            echo "<strong>" . $team_data['name'] . "</strong>";
            if ($team_data['description']) {
                echo "<br><small class='text-muted'>" . $team_data['description'] . "</small>";
            }
            echo "</td>";
            echo "<td>";
            if ($team_data['manager_name']) {
                echo $team_data['manager_firstname'] . " " . $team_data['manager_name'];
            } else {
                echo "<span class='text-muted'>-</span>";
            }
            echo "</td>";
            echo "<td><span class='badge badge-info'>" . $team_data['member_count'] . "</span></td>";
            echo "<td>" . Html::convDateTime($team_data['date_creation']) . "</td>";
            echo "<td>";
            echo "<a href='" . PluginScrumbanTeam::getFormURLWithID($team_data['id']) . "' class='btn btn-sm btn-outline-primary'>";
            echo "<i class='fas fa-edit'></i> " . __('Editar', 'scrumban');
            echo "</a>";
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</tbody>";
        echo "</table>";
    } else {
        echo "<p class='text-muted'>" . __('Nenhuma equipe criada ainda.', 'scrumban') . "</p>";
    }
    
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
}

echo "</div>";

// Modal para nova equipe
if (Session::haveRight('scrumban_team', CREATE)) {
    echo "<div class='modal fade' id='newTeamModal' tabindex='-1'>";
    echo "<div class='modal-dialog'>";
    echo "<div class='modal-content'>";
    echo "<div class='modal-header'>";
    echo "<h5 class='modal-title'>" . __('Nova Equipe', 'scrumban') . "</h5>";
    echo "<button type='button' class='close' data-dismiss='modal'>&times;</button>";
    echo "</div>";
    echo "<form method='post' action='" . $_SERVER['PHP_SELF'] . "'>";
    echo "<div class='modal-body'>";
    
    echo "<div class='form-group'>";
    echo "<label>" . __('Nome da Equipe', 'scrumban') . "</label>";
    echo "<input type='text' name='name' class='form-control' required>";
    echo "</div>";
    
    echo "<div class='form-group'>";
    echo "<label>" . __('Descrição', 'scrumban') . "</label>";
    echo "<textarea name='description' class='form-control' rows='3'></textarea>";
    echo "</div>";
    
    echo "<div class='form-group'>";
    echo "<label>" . __('Gerente', 'scrumban') . "</label>";
    User::dropdown(['name' => 'manager_id', 'value' => $user_id]);
    echo "</div>";
    
    echo "<input type='hidden' name='action' value='create_team'>";
    echo "</div>";
    echo "<div class='modal-footer'>";
    echo "<button type='button' class='btn btn-secondary' data-dismiss='modal'>" . __('Cancelar', 'scrumban') . "</button>";
    echo "<button type='submit' class='btn btn-primary'>" . __('Criar Equipe', 'scrumban') . "</button>";
    echo "</div>";
    echo "</form>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
}

// JavaScript
echo "<script>
function showNewTeamModal() {
    $('#newTeamModal').modal('show');
}

$(document).ready(function() {
    // Focar no campo nome quando modal abrir
    $('#newTeamModal').on('shown.bs.modal', function() {
        $(this).find('input[name=\"name\"]').focus();
    });
});
</script>";

Html::footer();