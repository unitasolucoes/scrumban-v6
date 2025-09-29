<?php

include '../../../inc/includes.php';

// Verificar permissões
if (!Session::haveRight('scrumban_board', READ)) {
    Html::displayRightError();
}

$user_id = Session::getLoginUserID();

// Obter ID do quadro
$board_id = $_GET['id'] ?? null;

// Se não foi especificado um quadro, mostrar lista
if (!$board_id) {
    include 'board_list.php';
    exit;
}

// Carregar o quadro
$board = new PluginScrumbanBoard();
if (!$board->getFromDB($board_id)) {
    Html::displayErrorAndDie(__('Quadro não encontrado', 'scrumban'));
}

// Verificar acesso ao quadro
if (!PluginScrumbanTeam::canUserAccessBoard($user_id, $board_id)) {
    Html::displayRightError();
}

$can_edit = PluginScrumbanBoard::canUserEditBoard($user_id, $board_id);
$can_manage = PluginScrumbanBoard::canUserManageBoard($user_id, $board_id);

Html::header($board->fields['name'] . ' - Quadro Kanban', $_SERVER['PHP_SELF'], "tools", "pluginscrumbanmenu", "boards");

echo "<div class='container-fluid'>";

// Header do quadro
echo "<div class='row mb-3'>";
echo "<div class='col-md-12'>";
echo "<div class='card'>";
echo "<div class='card-header d-flex justify-content-between align-items-center'>";

echo "<div>";
echo "<h4 class='mb-0'><i class='fas fa-columns'></i> " . $board->fields['name'] . "</h4>";
if ($board->fields['description']) {
    echo "<small class='text-muted'>" . $board->fields['description'] . "</small>";
}
echo "</div>";

// Controles do header
echo "<div class='d-flex align-items-center'>";

// Seletor de equipe
$teams = PluginScrumbanTeam::getTeamsForUser($user_id);
if (!empty($teams)) {
    echo "<div class='mr-3'>";
    echo "<label class='mr-2 mb-0'>" . __('Equipe:', 'scrumban') . "</label>";
    echo "<select id='team_filter' class='form-control form-control-sm' style='width: 150px;'>";
    echo "<option value=''>" . __('Todas', 'scrumban') . "</option>";
    foreach ($teams as $team) {
        echo "<option value='" . $team['id'] . "'>" . $team['name'] . "</option>";
    }
    echo "</select>";
    echo "</div>";
}

// Seletor de responsável
echo "<div class='mr-3'>";
echo "<label class='mr-2 mb-0'>" . __('Responsável:', 'scrumban') . "</label>";
echo "<select id='assignee_filter' class='form-control form-control-sm' style='width: 150px;'>";
echo "<option value=''>" . __('Todos', 'scrumban') . "</option>";
echo "<option value='$user_id'>" . __('Minhas tarefas', 'scrumban') . "</option>";
// Adicionar outros usuários da equipe
global $DB;
$users_query = "SELECT DISTINCT u.id, u.firstname, u.realname 
                FROM glpi_plugin_scrumban_cards c
                INNER JOIN glpi_users u ON u.id = c.users_id_assigned
                WHERE c.boards_id = '$board_id' AND u.is_active = 1
                ORDER BY u.firstname, u.realname";
$users_result = $DB->query($users_query);
while ($user_data = $DB->fetchAssoc($users_result)) {
    if ($user_data['id'] != $user_id) {
        echo "<option value='" . $user_data['id'] . "'>" . $user_data['firstname'] . " " . $user_data['realname'] . "</option>";
    }
}
echo "</select>";
echo "</div>";

// Botões de ação
if ($can_edit) {
    echo "<button type='button' class='btn btn-primary btn-sm mr-2' onclick='showNewCardModal()'>";
    echo "<i class='fas fa-plus'></i> " . __('Novo Card', 'scrumban');
    echo "</button>";
}

if ($can_manage) {
    echo "<button type='button' class='btn btn-secondary btn-sm mr-2' onclick='showSprintModal()'>";
    echo "<i class='fas fa-calendar'></i> " . __('Sprints', 'scrumban');
    echo "</button>";
}

echo "<button type='button' class='btn btn-info btn-sm' onclick='toggleFilters()'>";
echo "<i class='fas fa-filter'></i> " . __('Filtros', 'scrumban');
echo "</button>";

echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

// Filtros avançados (inicialmente ocultos)
echo "<div id='advanced_filters' class='row mb-3' style='display: none;'>";
echo "<div class='col-md-12'>";
echo "<div class='card'>";
echo "<div class='card-body'>";
echo "<div class='row'>";

echo "<div class='col-md-3'>";
echo "<label>" . __('Tipo:', 'scrumban') . "</label>";
echo "<select id='type_filter' class='form-control form-control-sm'>";
echo "<option value=''>" . __('Todos', 'scrumban') . "</option>";
echo "<option value='feature'>" . __('Funcionalidade', 'scrumban') . "</option>";
echo "<option value='bug'>" . __('Bug', 'scrumban') . "</option>";
echo "<option value='task'>" . __('Tarefa', 'scrumban') . "</option>";
echo "<option value='story'>" . __('História', 'scrumban') . "</option>";
echo "</select>";
echo "</div>";

echo "<div class='col-md-3'>";
echo "<label>" . __('Prioridade:', 'scrumban') . "</label>";
echo "<select id='priority_filter' class='form-control form-control-sm'>";
echo "<option value=''>" . __('Todas', 'scrumban') . "</option>";
echo "<option value='LOW'>" . __('Baixa', 'scrumban') . "</option>";
echo "<option value='NORMAL'>" . __('Normal', 'scrumban') . "</option>";
echo "<option value='HIGH'>" . __('Alta', 'scrumban') . "</option>";
echo "<option value='CRITICAL'>" . __('Crítica', 'scrumban') . "</option>";
echo "</select>";
echo "</div>";

echo "<div class='col-md-3'>";
echo "<label>" . __('Sprint:', 'scrumban') . "</label>";
echo "<select id='sprint_filter' class='form-control form-control-sm'>";
echo "<option value=''>" . __('Todos', 'scrumban') . "</option>";
$sprints = getAllDatasFromTable('glpi_plugin_scrumban_sprints', ['boards_id' => $board_id]);
foreach ($sprints as $sprint) {
    echo "<option value='" . $sprint['id'] . "'>" . $sprint['name'] . "</option>";
}
echo "</select>";
echo "</div>";

echo "<div class='col-md-3'>";
echo "<label>" . __('Texto:', 'scrumban') . "</label>";
echo "<input type='text' id='text_filter' class='form-control form-control-sm' placeholder='Buscar...'>";
echo "</div>";

echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

// Quadro Kanban
echo "<div class='row'>";
echo "<div class='col-md-12'>";
$board->showKanbanBoard();
echo "</div>";
echo "</div>";

echo "</div>";

// Modal para novo card
echo "<div class='modal fade' id='newCardModal' tabindex='-1'>";
echo "<div class='modal-dialog modal-lg'>";
echo "<div class='modal-content'>";
echo "<div class='modal-header'>";
echo "<h5 class='modal-title'>" . __('Novo Card', 'scrumban') . "</h5>";
echo "<button type='button' class='close' data-dismiss='modal'>&times;</button>";
echo "</div>";
echo "<form id='newCardForm'>";
echo "<div class='modal-body'>";

echo "<div class='row'>";
echo "<div class='col-md-6'>";
echo "<div class='form-group'>";
echo "<label>" . __('Nome', 'scrumban') . "</label>";
echo "<input type='text' name='name' class='form-control' required>";
echo "</div>";
echo "</div>";
echo "<div class='col-md-6'>";
echo "<div class='form-group'>";
echo "<label>" . __('Tipo', 'scrumban') . "</label>";
echo "<select name='type' class='form-control'>";
echo "<option value='task'>" . __('Tarefa', 'scrumban') . "</option>";
echo "<option value='feature'>" . __('Funcionalidade', 'scrumban') . "</option>";
echo "<option value='bug'>" . __('Bug', 'scrumban') . "</option>";
echo "<option value='story'>" . __('História', 'scrumban') . "</option>";
echo "</select>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "<div class='row'>";
echo "<div class='col-md-6'>";
echo "<div class='form-group'>";
echo "<label>" . __('Prioridade', 'scrumban') . "</label>";
echo "<select name='priority' class='form-control'>";
echo "<option value='NORMAL'>" . __('Normal', 'scrumban') . "</option>";
echo "<option value='LOW'>" . __('Baixa', 'scrumban') . "</option>";
echo "<option value='HIGH'>" . __('Alta', 'scrumban') . "</option>";
echo "<option value='CRITICAL'>" . __('Crítica', 'scrumban') . "</option>";
echo "</select>";
echo "</div>";
echo "</div>";
echo "<div class='col-md-6'>";
echo "<div class='form-group'>";
echo "<label>" . __('Story Points', 'scrumban') . "</label>";
echo "<input type='number' name='story_points' class='form-control' min='0' max='100'>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "<div class='row'>";
echo "<div class='col-md-6'>";
echo "<div class='form-group'>";
echo "<label>" . __('Responsável', 'scrumban') . "</label>";
echo "<select name='users_id_assigned' class='form-control'>";
echo "<option value=''>" . __('Selecione...', 'scrumban') . "</option>";
// Listar usuários das equipes do quadro
$team_users_query = "SELECT DISTINCT u.id, u.firstname, u.realname
                     FROM glpi_users u
                     INNER JOIN glpi_plugin_scrumban_team_members tm ON tm.users_id = u.id
                     INNER JOIN glpi_plugin_scrumban_team_boards tb ON tb.teams_id = tm.teams_id
                     WHERE tb.boards_id = '$board_id' AND u.is_active = 1
                     ORDER BY u.firstname, u.realname";
$team_users_result = $DB->query($team_users_query);
while ($team_user = $DB->fetchAssoc($team_users_result)) {
    echo "<option value='" . $team_user['id'] . "'>" . $team_user['firstname'] . " " . $team_user['realname'] . "</option>";
}
echo "</select>";
echo "</div>";
echo "</div>";
echo "<div class='col-md-6'>";
echo "<div class='form-group'>";
echo "<label>" . __('Solicitante', 'scrumban') . "</label>";
echo "<select name='users_id_requester' class='form-control'>";
echo "<option value='$user_id'>" . getUserName($user_id) . "</option>";
// Mesmo dropdown que o responsável
$team_users_result = $DB->query($team_users_query);
while ($team_user = $DB->fetchAssoc($team_users_result)) {
    if ($team_user['id'] != $user_id) {
        echo "<option value='" . $team_user['id'] . "'>" . $team_user['firstname'] . " " . $team_user['realname'] . "</option>";
    }
}
echo "</select>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label>" . __('Descrição', 'scrumban') . "</label>";
echo "<textarea name='description' class='form-control' rows='3'></textarea>";
echo "</div>";

echo "<input type='hidden' name='boards_id' value='$board_id'>";
echo "<input type='hidden' name='status' value='backlog'>";

echo "</div>";
echo "<div class='modal-footer'>";
echo "<button type='button' class='btn btn-secondary' data-dismiss='modal'>" . __('Cancelar', 'scrumban') . "</button>";
echo "<button type='submit' class='btn btn-primary'>" . __('Criar Card', 'scrumban') . "</button>";
echo "</div>";
echo "</form>";
echo "</div>";
echo "</div>";
echo "</div>";

// JavaScript
echo "<script>
var boardId = $board_id;
var canEdit = " . ($can_edit ? 'true' : 'false') . ";

$(document).ready(function() {
    // Inicializar drag & drop se pode editar
    if (canEdit) {
        initializeDragDrop();
    }
    
    // Configurar filtros
    setupFilters();
    
    // Form de novo card
    $('#newCardForm').submit(function(e) {
        e.preventDefault();
        createNewCard();
    });
});

function initializeDragDrop() {
    $('.kanban-cards').sortable({
        connectWith: '.kanban-cards',
        placeholder: 'card-placeholder',
        update: function(event, ui) {
            var cardId = ui.item.data('card-id');
            var newStatus = ui.item.closest('.kanban-column').data('status');
            updateCardStatus(cardId, newStatus);
        }
    });
    
    $('.kanban-card').draggable({
        revert: 'invalid',
        helper: 'clone',
        zIndex: 1000
    });
}

function updateCardStatus(cardId, newStatus) {
    $.ajax({
        url: '" . $CFG_GLPI['root_doc'] . "/plugins/scrumban/ajax/card.php',
        type: 'POST',
        data: {
            action: 'update_status',
            card_id: cardId,
            status: newStatus
        },
        success: function(response) {
            if (response.success) {
                showNotification('Status atualizado com sucesso', 'success');
            } else {
                showNotification('Erro ao atualizar status', 'error');
                location.reload(); // Recarregar para reverter a mudança
            }
        }
    });
}

function showCardModal(cardId) {
    $.ajax({
        url: '" . $CFG_GLPI['root_doc'] . "/plugins/scrumban/ajax/card.php',
        type: 'POST',
        data: {
            action: 'get_card_details',
            card_id: cardId
        },
        success: function(response) {
            $('#cardModalBody').html(response);
            $('#cardModal').modal('show');
            setupCardModal();
        }
    });
}

function setupCardModal() {
    // Form de comentário
    $('#addCommentForm').submit(function(e) {
        e.preventDefault();
        var cardId = $(this).find('input[name=card_id]').val();
        var comment = $(this).find('textarea[name=comment]').val();
        
        if (comment.trim()) {
            addComment(cardId, comment);
        }
    });
}

function addComment(cardId, comment) {
    $.ajax({
        url: '" . $CFG_GLPI['root_doc'] . "/plugins/scrumban/ajax/card.php',
        type: 'POST',
        data: {
            action: 'add_comment',
            card_id: cardId,
            comment: comment
        },
        success: function(response) {
            if (response.success) {
                showCardModal(cardId); // Recarregar modal
            } else {
                showNotification('Erro ao adicionar comentário', 'error');
            }
        }
    });
}

function setupFilters() {
    $('#assignee_filter, #type_filter, #priority_filter, #sprint_filter').change(function() {
        applyFilters();
    });
    
    $('#text_filter').on('input', function() {
        applyFilters();
    });
}

function applyFilters() {
    var assignee = $('#assignee_filter').val();
    var type = $('#type_filter').val();
    var priority = $('#priority_filter').val();
    var sprint = $('#sprint_filter').val();
    var text = $('#text_filter').val().toLowerCase();
    
    $('.kanban-card').each(function() {
        var card = $(this);
        var show = true;
        
        // Filtros implementados via data attributes nos cards
        if (assignee && card.data('assignee') != assignee) show = false;
        if (type && card.data('type') != type) show = false;
        if (priority && card.data('priority') != priority) show = false;
        if (sprint && card.data('sprint') != sprint) show = false;
        if (text && !card.text().toLowerCase().includes(text)) show = false;
        
        card.toggle(show);
    });
    
    updateColumnCounts();
}

function updateColumnCounts() {
    $('.kanban-column').each(function() {
        var visible = $(this).find('.kanban-card:visible').length;
        $(this).find('.badge').text(visible);
    });
}

function toggleFilters() {
    $('#advanced_filters').toggle();
}

function showNewCardModal() {
    $('#newCardModal').modal('show');
}

function createNewCard() {
    $.ajax({
        url: '" . $CFG_GLPI['root_doc'] . "/plugins/scrumban/ajax/card.php',
        type: 'POST',
        data: $('#newCardForm').serialize() + '&action=create',
        success: function(response) {
            if (response.success) {
                $('#newCardModal').modal('hide');
                location.reload(); // Recarregar para mostrar o novo card
            } else {
                showNotification('Erro ao criar card', 'error');
            }
        }
    });
}

function showNotification(message, type) {
    // Implementar notificações toast
    console.log(type + ': ' + message);
}
</script>";

// CSS específico do Kanban
echo "<style>
.scrumban-board {
    padding: 20px 0;
}

.kanban-column {
    margin-bottom: 20px;
}

.kanban-cards {
    min-height: 400px;
    padding: 10px;
}

.kanban-card {
    cursor: pointer;
    transition: all 0.2s;
}

.kanban-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.card-placeholder {
    height: 80px;
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    margin: 5px 0;
}

.priority-LOW { border-left: 4px solid #28a745; }
.priority-NORMAL { border-left: 4px solid #17a2b8; }
.priority-HIGH { border-left: 4px solid #ffc107; }
.priority-CRITICAL { border-left: 4px solid #dc3545; }
</style>";

Html::footer();