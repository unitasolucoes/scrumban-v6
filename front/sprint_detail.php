<?php

// Este arquivo é incluído por sprint.php quando um sprint específico é visualizado

$board = new PluginScrumbanBoard();
$board->getFromDB($sprint->fields['boards_id']);

$can_manage = PluginScrumbanBoard::canUserManageBoard($user_id, $sprint->fields['boards_id']);

echo "<div class='container-fluid'>";

// Header do Sprint
echo "<div class='row mb-4'>";
echo "<div class='col-md-12'>";
echo "<div class='card'>";
echo "<div class='card-header'>";
echo "<div class='d-flex justify-content-between align-items-start'>";

echo "<div>";
echo "<h4 class='mb-1'>";
if ($sprint->fields['is_active']) {
    echo "<span class='badge badge-success mr-2'><i class='fas fa-play'></i> ATIVO</span>";
}
echo $sprint->fields['name'];
echo "</h4>";
echo "<p class='mb-0 text-muted'>";
echo "<i class='fas fa-columns'></i> " . $board->fields['name'];
if ($sprint->fields['description']) {
    echo " • " . $sprint->fields['description'];
}
echo "</p>";
echo "</div>";

echo "<div class='text-right'>";
echo "<a href='" . $_SERVER['PHP_SELF'] . "' class='btn btn-outline-secondary mr-2'>";
echo "<i class='fas fa-arrow-left'></i> " . __('Voltar', 'scrumban');
echo "</a>";

if ($can_manage) {
    echo "<a href='" . PluginScrumbanSprint::getFormURLWithID($sprint->fields['id']) . "' class='btn btn-primary'>";
    echo "<i class='fas fa-edit'></i> " . __('Editar Sprint', 'scrumban');
    echo "</a>";
}
echo "</div>";

echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

// Informações do Sprint
echo "<div class='row mb-4'>";

// Coluna esquerda - Datas e Status
echo "<div class='col-md-4'>";
echo "<div class='card'>";
echo "<div class='card-header'>";
echo "<h6><i class='fas fa-info-circle'></i> " . __('Informações', 'scrumban') . "</h6>";
echo "</div>";
echo "<div class='card-body'>";

if ($sprint->fields['date_start'] || $sprint->fields['date_end']) {
    echo "<div class='mb-3'>";
    echo "<strong>" . __('Período:', 'scrumban') . "</strong><br>";
    if ($sprint->fields['date_start']) {
        echo "<i class='fas fa-calendar-plus text-success'></i> " . Html::convDateTime($sprint->fields['date_start']) . "<br>";
    }
    if ($sprint->fields['date_end']) {
        echo "<i class='fas fa-calendar-minus text-danger'></i> " . Html::convDateTime($sprint->fields['date_end']);
        
        // Verificar se está atrasado
        if (strtotime($sprint->fields['date_end']) < time() && $sprint->fields['is_active']) {
            echo " <span class='badge badge-warning ml-1'>" . __('Atrasado', 'scrumban') . "</span>";
        }
    }
    echo "</div>";
}

echo "<div class='mb-3'>";
echo "<strong>" . __('Status:', 'scrumban') . "</strong><br>";
if ($sprint->fields['is_active']) {
    echo "<span class='badge badge-success'><i class='fas fa-play'></i> " . __('Ativo', 'scrumban') . "</span>";
} else {
    echo "<span class='badge badge-secondary'><i class='fas fa-pause'></i> " . __('Inativo', 'scrumban') . "</span>";
}
echo "</div>";

echo "<div>";
echo "<strong>" . __('Criado em:', 'scrumban') . "</strong><br>";
echo Html::convDateTime($sprint->fields['date_creation']);
echo "</div>";

echo "</div>";
echo "</div>";
echo "</div>";

// Coluna central - Estatísticas
echo "<div class='col-md-4'>";
echo "<div class='card'>";
echo "<div class='card-header'>";
echo "<h6><i class='fas fa-chart-bar'></i> " . __('Estatísticas', 'scrumban') . "</h6>";
echo "</div>";
echo "<div class='card-body'>";

$stats = $sprint->getDetailedStats();
$progress = $stats['total'] > 0 ? round(($stats['done'] / $stats['total']) * 100) : 0;

echo "<div class='text-center mb-3'>";
echo "<h3 class='text-primary'>" . $progress . "%</h3>";
echo "<p class='text-muted mb-2'>" . __('Concluído', 'scrumban') . "</p>";
echo "<div class='progress'>";
echo "<div class='progress-bar bg-success' style='width: $progress%'></div>";
echo "</div>";
echo "</div>";

echo "<div class='row text-center'>";
echo "<div class='col-6'>";
echo "<h5 class='text-info'>" . $stats['total'] . "</h5>";
echo "<small class='text-muted'>" . __('Total Cards', 'scrumban') . "</small>";
echo "</div>";
echo "<div class='col-6'>";
echo "<h5 class='text-success'>" . $stats['done'] . "</h5>";
echo "<small class='text-muted'>" . __('Concluídos', 'scrumban') . "</small>";
echo "</div>";
echo "</div>";

if ($stats['story_points_total'] > 0) {
    echo "<hr>";
    echo "<div class='row text-center'>";
    echo "<div class='col-6'>";
    echo "<h5 class='text-warning'>" . $stats['story_points_total'] . "</h5>";
    echo "<small class='text-muted'>Story Points Total</small>";
    echo "</div>";
    echo "<div class='col-6'>";
    echo "<h5 class='text-success'>" . $stats['story_points_done'] . "</h5>";
    echo "<small class='text-muted'>Story Points Feitos</small>";
    echo "</div>";
    echo "</div>";
}

echo "</div>";
echo "</div>";
echo "</div>";

// Coluna direita - Ações Rápidas
echo "<div class='col-md-4'>";
echo "<div class='card'>";
echo "<div class='card-header'>";
echo "<h6><i class='fas fa-bolt'></i> " . __('Ações Rápidas', 'scrumban') . "</h6>";
echo "</div>";
echo "<div class='card-body'>";

echo "<div class='d-grid gap-2'>";

echo "<a href='" . $CFG_GLPI['root_doc'] . "/plugins/scrumban/front/board.php?id=" . $sprint->fields['boards_id'] . "' class='btn btn-primary btn-sm'>";
echo "<i class='fas fa-columns'></i> " . __('Ver Quadro Kanban', 'scrumban');
echo "</a>";

if ($can_manage) {
    if (!$sprint->fields['is_active']) {
        echo "<button type='button' class='btn btn-success btn-sm' onclick='activateSprint(" . $sprint->fields['id'] . ")'>";
        echo "<i class='fas fa-play'></i> " . __('Ativar Sprint', 'scrumban');
        echo "</button>";
    } else {
        echo "<button type='button' class='btn btn-warning btn-sm' onclick='deactivateSprint(" . $sprint->fields['id'] . ")'>";
        echo "<i class='fas fa-pause'></i> " . __('Pausar Sprint', 'scrumban');
        echo "</button>";
    }
}

if (Session::haveRight('scrumban_card', CREATE)) {
    echo "<a href='" . PluginScrumbanCard::getFormURL() . "?boards_id=" . $sprint->fields['boards_id'] . "&sprint_id=" . $sprint->fields['id'] . "' class='btn btn-info btn-sm'>";
    echo "<i class='fas fa-plus'></i> " . __('Novo Card no Sprint', 'scrumban');
    echo "</a>";
}

echo "</div>";

echo "</div>";
echo "</div>";
echo "</div>";

echo "</div>";

// Distribuição por Status
echo "<div class='row mb-4'>";
echo "<div class='col-md-12'>";
echo "<div class='card'>";
echo "<div class='card-header'>";
echo "<h6><i class='fas fa-chart-pie'></i> " . __('Distribuição por Status', 'scrumban') . "</h6>";
echo "</div>";
echo "<div class='card-body'>";

$statuses = [
    'backlog' => ['name' => 'Backlog', 'color' => 'secondary'],
    'todo' => ['name' => 'A Fazer', 'color' => 'info'],
    'em-execucao' => ['name' => 'Em Execução', 'color' => 'warning'],
    'review' => ['name' => 'Review', 'color' => 'primary'],
    'done' => ['name' => 'Concluído', 'color' => 'success']
];

echo "<div class='row'>";
foreach ($statuses as $status => $info) {
    $count = $stats[$status] ?? 0;
    $percentage = $stats['total'] > 0 ? round(($count / $stats['total']) * 100) : 0;
    
    echo "<div class='col-md-2 text-center'>";
    echo "<div class='card bg-" . $info['color'] . " text-white mb-2'>";
    echo "<div class='card-body p-3'>";
    echo "<h4 class='mb-0'>$count</h4>";
    echo "<small>" . $info['name'] . "</small>";
    echo "<div class='mt-1'><small>$percentage%</small></div>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
}
echo "</div>";

echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

// Lista de Cards do Sprint
echo "<div class='row'>";
echo "<div class='col-md-12'>";
echo "<div class='card'>";
echo "<div class='card-header d-flex justify-content-between align-items-center'>";
echo "<h6 class='mb-0'><i class='fas fa-sticky-note'></i> " . __('Cards do Sprint', 'scrumban') . " (" . $stats['total'] . ")</h6>";

// Filtros
echo "<div class='d-flex'>";
echo "<select id='status_filter' class='form-control form-control-sm mr-2' style='width: 120px;'>";
echo "<option value=''>" . __('Todos', 'scrumban') . "</option>";
foreach ($statuses as $status => $info) {
    echo "<option value='$status'>" . $info['name'] . "</option>";
}
echo "</select>";

echo "<select id='assignee_filter' class='form-control form-control-sm' style='width: 120px;'>";
echo "<option value=''>" . __('Todos', 'scrumban') . "</option>";
echo "<option value='$user_id'>" . __('Minhas', 'scrumban') . "</option>";
echo "</select>";
echo "</div>";

echo "</div>";
echo "<div class='card-body'>";

if ($stats['total'] == 0) {
    echo "<div class='text-center text-muted py-4'>";
    echo "<i class='fas fa-sticky-note fa-3x mb-3'></i>";
    echo "<h6>" . __('Nenhum card neste sprint', 'scrumban') . "</h6>";
    echo "<p>" . __('Adicione cards ao sprint para começar a trabalhar.', 'scrumban') . "</p>";
    
    if (Session::haveRight('scrumban_card', CREATE)) {
        echo "<a href='" . PluginScrumbanCard::getFormURL() . "?boards_id=" . $sprint->fields['boards_id'] . "&sprint_id=" . $sprint->fields['id'] . "' class='btn btn-primary'>";
        echo "<i class='fas fa-plus'></i> " . __('Adicionar Primeiro Card', 'scrumban');
        echo "</a>";
    }
    echo "</div>";
} else {
    // Buscar cards do sprint
    global $DB;
    $cards_query = "SELECT c.*, 
                           ua.realname as assigned_name, ua.firstname as assigned_firstname,
                           ur.realname as requester_name, ur.firstname as requester_firstname
                    FROM glpi_plugin_scrumban_cards c
                    LEFT JOIN glpi_users ua ON ua.id = c.users_id_assigned
                    LEFT JOIN glpi_users ur ON ur.id = c.users_id_requester
                    WHERE c.sprint_id = '" . $sprint->fields['id'] . "'
                    ORDER BY 
                        FIELD(c.status, 'em-execucao', 'review', 'todo', 'backlog', 'done'),
                        c.priority DESC,
                        c.date_creation DESC";
    
    $cards_result = $DB->query($cards_query);
    
    echo "<div class='table-responsive'>";
    echo "<table class='table table-hover'>";
    echo "<thead>";
    echo "<tr>";
    echo "<th width='60'>#</th>";
    echo "<th>" . __('Nome', 'scrumban') . "</th>";
    echo "<th width='100'>" . __('Tipo', 'scrumban') . "</th>";
    echo "<th width='100'>" . __('Prioridade', 'scrumban') . "</th>";
    echo "<th width='120'>" . __('Status', 'scrumban') . "</th>";
    echo "<th width='150'>" . __('Responsável', 'scrumban') . "</th>";
    echo "<th width='80'>SP</th>";
    echo "<th width='100'>" . __('Ações', 'scrumban') . "</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    while ($card = $DB->fetchAssoc($cards_result)) {
        $priority_class = PluginScrumbanCard::getPriorityColor($card['priority']);
        
        echo "<tr class='card-row' data-status='" . $card['status'] . "' data-assignee='" . ($card['users_id_assigned'] ?? '') . "'>";
        echo "<td class='text-center'>";
        echo "<span class='badge badge-outline-secondary'>#" . $card['id'] . "</span>";
        echo "</td>";
        echo "<td>";
        echo "<a href='javascript:void(0)' onclick='showCardModal(" . $card['id'] . ")' class='font-weight-bold'>";
        echo $card['name'];
        echo "</a>";
        if ($card['description']) {
            echo "<br><small class='text-muted'>" . substr($card['description'], 0, 100) . (strlen($card['description']) > 100 ? '...' : '') . "</small>";
        }
        echo "</td>";
        echo "<td><span class='badge badge-info'>" . ucfirst($card['type']) . "</span></td>";
        echo "<td><span class='badge badge-$priority_class'>" . $card['priority'] . "</span></td>";
        echo "<td><span class='badge status-" . $card['status'] . "'>" . PluginScrumbanCard::getStatusName($card['status']) . "</span></td>";
        echo "<td>";
        if ($card['assigned_firstname']) {
            echo $card['assigned_firstname'] . " " . $card['assigned_name'];
        } else {
            echo "<span class='text-muted'>-</span>";
        }
        echo "</td>";
        echo "<td class='text-center'>";
        echo $card['story_points'] ? "<span class='badge badge-secondary'>" . $card['story_points'] . "</span>" : "-";
        echo "</td>";
        echo "<td>";
        echo "<button type='button' class='btn btn-sm btn-outline-primary' onclick='showCardModal(" . $card['id'] . ")'>";
        echo "<i class='fas fa-eye'></i>";
        echo "</button>";
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</tbody>";
    echo "</table>";
    echo "</div>";
}

echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "</div>";

// Modal do card (será carregado via AJAX)
$board->showCardModal();

// JavaScript
echo "<script>
function activateSprint(sprintId) {
    if (confirm('Ativar este sprint? Isso desativará outros sprints do mesmo quadro.')) {
        window.location.href = '" . $_SERVER['PHP_SELF'] . "?action=activate_sprint&sprint_id=' + sprintId;
    }
}

function deactivateSprint(sprintId) {
    if (confirm('Desativar este sprint?')) {
        window.location.href = '" . $_SERVER['PHP_SELF'] . "?action=deactivate_sprint&sprint_id=' + sprintId;
    }
}

$(document).ready(function() {
    // Filtros da tabela
    $('#status_filter, #assignee_filter').change(function() {
        var statusFilter = $('#status_filter').val();
        var assigneeFilter = $('#assignee_filter').val();
        
        $('.card-row').each(function() {
            var row = $(this);
            var show = true;
            
            if (statusFilter && row.data('status') != statusFilter) {
                show = false;
            }
            
            if (assigneeFilter && row.data('assignee') != assigneeFilter) {
                show = false;
            }
            
            row.toggle(show);
        });
    });
});
</script>";

Html::footer();