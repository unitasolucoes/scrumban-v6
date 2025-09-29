<?php

include '../../../inc/includes.php';

Html::header(__('Sprints Scrumban', 'scrumban'), $_SERVER['PHP_SELF'], "tools", "pluginscrumbanmenu", "sprints");

if (!Session::haveRight('scrumban_board', READ)) {
    Html::displayRightError();
}

$user_id = Session::getLoginUserID();

// Obter ID do sprint se especificado
$sprint_id = $_GET['id'] ?? null;

// Se foi especificado um sprint, mostrar detalhes
if ($sprint_id) {
    $sprint = new PluginScrumbanSprint();
    if (!$sprint->getFromDB($sprint_id)) {
        Html::displayErrorAndDie(__('Sprint não encontrado', 'scrumban'));
    }
    
    // Verificar acesso ao quadro do sprint
    if (!PluginScrumbanTeam::canUserAccessBoard($user_id, $sprint->fields['boards_id'])) {
        Html::displayRightError();
    }
    
    include 'sprint_detail.php';
    exit;
}

// Processar ações
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'create_sprint':
            if (Session::haveRight('scrumban_board', CREATE)) {
                $sprint = new PluginScrumbanSprint();
                if ($sprint->add($_POST)) {
                    Session::addMessageAfterRedirect(__('Sprint criado com sucesso', 'scrumban'), true);
                } else {
                    Session::addMessageAfterRedirect(__('Erro ao criar sprint', 'scrumban'), false, ERROR);
                }
            }
            Html::redirect($_SERVER['PHP_SELF']);
            break;
            
        case 'activate_sprint':
            if (Session::haveRight('scrumban_board', UPDATE)) {
                $sprint = new PluginScrumbanSprint();
                if ($sprint->getFromDB($_POST['sprint_id']) && $sprint->activate()) {
                    Session::addMessageAfterRedirect(__('Sprint ativado', 'scrumban'), true);
                } else {
                    Session::addMessageAfterRedirect(__('Erro ao ativar sprint', 'scrumban'), false, ERROR);
                }
            }
            Html::redirect($_SERVER['PHP_SELF']);
            break;
            
        case 'deactivate_sprint':
            if (Session::haveRight('scrumban_board', UPDATE)) {
                $sprint = new PluginScrumbanSprint();
                if ($sprint->getFromDB($_POST['sprint_id']) && $sprint->deactivate()) {
                    Session::addMessageAfterRedirect(__('Sprint desativado', 'scrumban'), true);
                } else {
                    Session::addMessageAfterRedirect(__('Erro ao desativar sprint', 'scrumban'), false, ERROR);
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
echo "<h4 class='mb-0'><i class='fas fa-calendar-alt'></i> " . __('Sprints Scrumban', 'scrumban') . "</h4>";

// Seletores
echo "<div class='d-flex align-items-center'>";

// Seletor de equipe
$teams = PluginScrumbanTeam::getTeamsForUser($user_id);
if (!empty($teams)) {
    echo "<div class='mr-3'>";
    echo "<label class='mr-2 mb-0'>" . __('Equipe:', 'scrumban') . "</label>";
    echo "<select id='team_filter' class='form-control form-control-sm' style='width: 150px;'>";
    echo "<option value=''>" . __('Todas as equipes', 'scrumban') . "</option>";
    foreach ($teams as $team) {
        echo "<option value='" . $team['id'] . "'>" . $team['name'] . "</option>";
    }
    echo "</select>";
    echo "</div>";
}

// Seletor de quadro
echo "<div class='mr-3'>";
echo "<label class='mr-2 mb-0'>" . __('Quadro:', 'scrumban') . "</label>";
echo "<select id='board_filter' class='form-control form-control-sm' style='width: 150px;'>";
echo "<option value=''>" . __('Todos os quadros', 'scrumban') . "</option>";
$boards = PluginScrumbanBoard::getBoardsForUser($user_id);
foreach ($boards as $board) {
    echo "<option value='" . $board['id'] . "'>" . $board['name'] . "</option>";
}
echo "</select>";
echo "</div>";

if (Session::haveRight('scrumban_board', CREATE)) {
    echo "<button type='button' class='btn btn-primary' onclick='showNewSprintModal()'>";
    echo "<i class='fas fa-plus'></i> " . __('Novo Sprint', 'scrumban');
    echo "</button>";
}

echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

// Obter sprints do usuário
global $DB;
$query = "SELECT DISTINCT s.*, b.name as board_name, t.name as team_name
          FROM glpi_plugin_scrumban_sprints s
          INNER JOIN glpi_plugin_scrumban_boards b ON b.id = s.boards_id
          LEFT JOIN glpi_plugin_scrumban_teams t ON t.id = b.teams_id
          INNER JOIN glpi_plugin_scrumban_team_boards tb ON tb.boards_id = b.id
          INNER JOIN glpi_plugin_scrumban_team_members tm ON tm.teams_id = tb.teams_id
          WHERE tm.users_id = '$user_id'
          ORDER BY s.is_active DESC, s.date_start DESC";

$result = $DB->query($query);

if ($DB->numrows($result) == 0) {
    echo "<div class='row'>";
    echo "<div class='col-md-12'>";
    echo "<div class='card'>";
    echo "<div class='card-body text-center'>";
    echo "<i class='fas fa-calendar-alt fa-4x text-muted mb-3'></i>";
    echo "<h5>" . __('Nenhum sprint encontrado', 'scrumban') . "</h5>";
    echo "<p class='text-muted'>" . __('Não há sprints nos quadros das suas equipes.', 'scrumban') . "</p>";
    
    if (Session::haveRight('scrumban_board', CREATE)) {
        echo "<button type='button' class='btn btn-primary' onclick='showNewSprintModal()'>";
        echo "<i class='fas fa-plus'></i> " . __('Criar Primeiro Sprint', 'scrumban');
        echo "</button>";
    }
    
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
} else {
    // Agrupar sprints por status
    $active_sprints = [];
    $inactive_sprints = [];
    
    while ($sprint = $DB->fetchAssoc($result)) {
        $sprint['stats'] = PluginScrumbanSprint::getSprintProgress($sprint['id']);
        $sprint['card_count'] = countElementsInTable('glpi_plugin_scrumban_cards', ['sprint_id' => $sprint['id']]);
        
        if ($sprint['is_active']) {
            $active_sprints[] = $sprint;
        } else {
            $inactive_sprints[] = $sprint;
        }
    }
    
    // Sprints Ativos
    if (!empty($active_sprints)) {
        echo "<div class='row mb-4'>";
        echo "<div class='col-md-12'>";
        echo "<h5><i class='fas fa-play text-success'></i> " . __('Sprints Ativos', 'scrumban') . "</h5>";
        echo "</div>";
        echo "</div>";
        
        echo "<div class='row mb-4'>";
        foreach ($active_sprints as $sprint) {
            showSprintCard($sprint, true);
        }
        echo "</div>";
    }
    
    // Sprints Inativos
    if (!empty($inactive_sprints)) {
        echo "<div class='row mb-4'>";
        echo "<div class='col-md-12'>";
        echo "<h5><i class='fas fa-pause text-secondary'></i> " . __('Sprints Inativos', 'scrumban') . "</h5>";
        echo "</div>";
        echo "</div>";
        
        echo "<div class='row'>";
        foreach ($inactive_sprints as $sprint) {
            showSprintCard($sprint, false);
        }
        echo "</div>";
    }
}

echo "</div>";

// Modal para novo sprint
if (Session::haveRight('scrumban_board', CREATE)) {
    echo "<div class='modal fade' id='newSprintModal' tabindex='-1'>";
    echo "<div class='modal-dialog'>";
    echo "<div class='modal-content'>";
    echo "<div class='modal-header'>";
    echo "<h5 class='modal-title'>" . __('Novo Sprint', 'scrumban') . "</h5>";
    echo "<button type='button' class='close' data-dismiss='modal'>&times;</button>";
    echo "</div>";
    echo "<form method='post' action='" . $_SERVER['PHP_SELF'] . "'>";
    echo "<div class='modal-body'>";
    
    echo "<div class='form-group'>";
    echo "<label>" . __('Nome do Sprint', 'scrumban') . "</label>";
    echo "<input type='text' name='name' class='form-control' required placeholder='Sprint 1 - Janeiro 2024'>";
    echo "</div>";
    
    echo "<div class='form-group'>";
    echo "<label>" . __('Quadro', 'scrumban') . "</label>";
    echo "<select name='boards_id' class='form-control' required>";
    echo "<option value=''>" . __('Selecione um quadro', 'scrumban') . "</option>";
    foreach ($boards as $board) {
        if (PluginScrumbanBoard::canUserManageBoard($user_id, $board['id'])) {
            echo "<option value='" . $board['id'] . "'>" . $board['name'] . "</option>";
        }
    }
    echo "</select>";
    echo "</div>";
    
    echo "<div class='form-group'>";
    echo "<label>" . __('Descrição', 'scrumban') . "</label>";
    echo "<textarea name='description' class='form-control' rows='3' placeholder='Objetivos e metas do sprint...'></textarea>";
    echo "</div>";
    
    echo "<div class='row'>";
    echo "<div class='col-md-6'>";
    echo "<div class='form-group'>";
    echo "<label>" . __('Data de Início', 'scrumban') . "</label>";
    echo "<input type='datetime-local' name='date_start' class='form-control'>";
    echo "</div>";
    echo "</div>";
    echo "<div class='col-md-6'>";
    echo "<div class='form-group'>";
    echo "<label>" . __('Data de Fim', 'scrumban') . "</label>";
    echo "<input type='datetime-local' name='date_end' class='form-control'>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='form-check'>";
    echo "<input type='checkbox' name='is_active' class='form-check-input' id='is_active' value='1'>";
    echo "<label class='form-check-label' for='is_active'>" . __('Iniciar como sprint ativo', 'scrumban') . "</label>";
    echo "</div>";
    
    echo "<input type='hidden' name='action' value='create_sprint'>";
    echo "</div>";
    echo "<div class='modal-footer'>";
    echo "<button type='button' class='btn btn-secondary' data-dismiss='modal'>" . __('Cancelar', 'scrumban') . "</button>";
    echo "<button type='submit' class='btn btn-primary'>" . __('Criar Sprint', 'scrumban') . "</button>";
    echo "</div>";
    echo "</form>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
}

// JavaScript
echo "<script>
function showNewSprintModal() {
    $('#newSprintModal').modal('show');
}

function activateSprint(sprintId) {
    if (confirm('Ativar este sprint? Isso desativará outros sprints do mesmo quadro.')) {
        var form = $('<form method=\"post\" action=\"" . $_SERVER['PHP_SELF'] . "\">');
        form.append('<input type=\"hidden\" name=\"action\" value=\"activate_sprint\">');
        form.append('<input type=\"hidden\" name=\"sprint_id\" value=\"' + sprintId + '\">');
        $('body').append(form);
        form.submit();
    }
}

function deactivateSprint(sprintId) {
    if (confirm('Desativar este sprint?')) {
        var form = $('<form method=\"post\" action=\"" . $_SERVER['PHP_SELF'] . "\">');
        form.append('<input type=\"hidden\" name=\"action\" value=\"deactivate_sprint\">');
        form.append('<input type=\"hidden\" name=\"sprint_id\" value=\"' + sprintId + '\">');
        $('body').append(form);
        form.submit();
    }
}

$(document).ready(function() {
    // Filtros
    $('#team_filter, #board_filter').change(function() {
        applyFilters();
    });
    
    // Auto-definir data de início como hoje
    var today = new Date();
    var dateString = today.toISOString().slice(0, 16);
    $('input[name=\"date_start\"]').val(dateString);
    
    // Auto-definir data de fim como 2 semanas depois
    var twoWeeksLater = new Date(today.getTime() + (14 * 24 * 60 * 60 * 1000));
    var endDateString = twoWeeksLater.toISOString().slice(0, 16);
    $('input[name=\"date_end\"]').val(endDateString);
});

function applyFilters() {
    var teamFilter = $('#team_filter').val();
    var boardFilter = $('#board_filter').val();
    
    $('.sprint-card').each(function() {
        var card = $(this);
        var show = true;
        
        if (teamFilter && card.data('team-id') != teamFilter) {
            show = false;
        }
        
        if (boardFilter && card.data('board-id') != boardFilter) {
            show = false;
        }
        
        card.parent().toggle(show);
    });
}
</script>";

Html::footer();

/**
 * Função para renderizar card do sprint
 */
function showSprintCard($sprint, $is_active) {
    $user_id = Session::getLoginUserID();
    $can_manage = PluginScrumbanBoard::canUserManageBoard($user_id, $sprint['boards_id']);
    
    echo "<div class='col-md-6 col-lg-4 mb-4' data-team-id='" . ($sprint['team_id'] ?? '') . "' data-board-id='" . $sprint['boards_id'] . "'>";
    echo "<div class='card sprint-card" . ($is_active ? ' sprint-active' : '') . "'>";
    
    // Header do sprint
    if ($is_active) {
        echo "<div class='card-header bg-success text-white'>";
        echo "<div class='d-flex justify-content-between align-items-center'>";
        echo "<h6 class='mb-0'><i class='fas fa-play'></i> " . __('Sprint Ativo', 'scrumban') . "</h6>";
        echo "<span class='badge badge-light'>" . $sprint['stats'] . "% " . __('Concluído', 'scrumban') . "</span>";
        echo "</div>";
        echo "</div>";
    }
    
    echo "<div class='card-body'>";
    
    // Nome e descrição
    echo "<h5 class='card-title'>" . $sprint['name'] . "</h5>";
    echo "<p class='text-muted mb-2'><i class='fas fa-columns'></i> " . $sprint['board_name'];
    if ($sprint['team_name']) {
        echo " • " . $sprint['team_name'];
    }
    echo "</p>";
    
    if ($sprint['description']) {
        echo "<p class='card-text'>" . nl2br($sprint['description']) . "</p>";
    }
    
    // Datas
    if ($sprint['date_start'] || $sprint['date_end']) {
        echo "<div class='sprint-dates mb-3'>";
        echo "<i class='fas fa-calendar'></i> ";
        if ($sprint['date_start']) {
            echo Html::convDate($sprint['date_start']);
        }
        if ($sprint['date_start'] && $sprint['date_end']) {
            echo " - ";
        }
        if ($sprint['date_end']) {
            echo Html::convDate($sprint['date_end']);
        }
        echo "</div>";
    }
    
    // Estatísticas
    echo "<div class='sprint-stats'>";
    echo "<div class='sprint-stat'>";
    echo "<div class='sprint-stat-value'>" . $sprint['card_count'] . "</div>";
    echo "<div class='sprint-stat-label'>" . __('Cards', 'scrumban') . "</div>";
    echo "</div>";
    echo "<div class='sprint-stat'>";
    echo "<div class='sprint-stat-value'>" . $sprint['stats'] . "%</div>";
    echo "<div class='sprint-stat-label'>" . __('Progresso', 'scrumban') . "</div>";
    echo "</div>";
    
    // Story Points se houver
    if ($sprint['card_count'] > 0) {
        global $DB;
        $points_query = "SELECT SUM(story_points) as total_points, 
                                SUM(CASE WHEN status = 'done' THEN story_points ELSE 0 END) as done_points
                         FROM glpi_plugin_scrumban_cards 
                         WHERE sprint_id = '" . $sprint['id'] . "'";
        $points_result = $DB->query($points_query);
        $points_data = $DB->fetchAssoc($points_result);
        
        if ($points_data['total_points'] > 0) {
            echo "<div class='sprint-stat'>";
            echo "<div class='sprint-stat-value'>" . ($points_data['done_points'] ?? 0) . "/" . $points_data['total_points'] . "</div>";
            echo "<div class='sprint-stat-label'>Story Points</div>";
            echo "</div>";
        }
    }
    echo "</div>";
    
    // Barra de progresso
    if ($sprint['card_count'] > 0) {
        echo "<div class='sprint-progress mb-3'>";
        echo "<div class='sprint-progress-bar' style='width: " . $sprint['stats'] . "%'></div>";
        echo "</div>";
    }
    
    echo "</div>";
    
    // Footer com ações
    echo "<div class='card-footer'>";
    echo "<div class='btn-group btn-group-sm w-100'>";
    
    // Botão Ver Detalhes
    echo "<a href='" . $_SERVER['PHP_SELF'] . "?id=" . $sprint['id'] . "' class='btn btn-outline-primary'>";
    echo "<i class='fas fa-eye'></i> " . __('Detalhes', 'scrumban');
    echo "</a>";
    
    // Botão Editar (se pode gerenciar)
    if ($can_manage) {
        echo "<a href='" . PluginScrumbanSprint::getFormURLWithID($sprint['id']) . "' class='btn btn-outline-secondary'>";
        echo "<i class='fas fa-edit'></i> " . __('Editar', 'scrumban');
        echo "</a>";
    }
    
    // Botão Ativar/Desativar
    if ($can_manage) {
        if (!$is_active) {
            echo "<button type='button' class='btn btn-outline-success' onclick='activateSprint(" . $sprint['id'] . ")'>";
            echo "<i class='fas fa-play'></i> " . __('Ativar', 'scrumban');
            echo "</button>";
        } else {
            echo "<button type='button' class='btn btn-outline-warning' onclick='deactivateSprint(" . $sprint['id'] . ")'>";
            echo "<i class='fas fa-pause'></i> " . __('Pausar', 'scrumban');
            echo "</button>";
        }
    }
    
    echo "</div>";
    echo "</div>";
    
    echo "</div>";
    echo "</div>";
}