<?php

// Lista de quadros - incluído por board.php quando nenhum quadro específico é selecionado

$user_id = Session::getLoginUserID();

Html::header(__('Quadros Scrumban', 'scrumban'), $_SERVER['PHP_SELF'], "tools", "pluginscrumbanmenu", "boards");

echo "<div class='container-fluid'>";

// Header
echo "<div class='row mb-4'>";
echo "<div class='col-md-12'>";
echo "<div class='card'>";
echo "<div class='card-header d-flex justify-content-between align-items-center'>";
echo "<h4 class='mb-0'><i class='fas fa-columns'></i> " . __('Quadros Scrumban', 'scrumban') . "</h4>";

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

if (Session::haveRight('scrumban_board', CREATE)) {
    echo "<button type='button' class='btn btn-primary' onclick='showNewBoardModal()'>";
    echo "<i class='fas fa-plus'></i> " . __('Novo Quadro', 'scrumban');
    echo "</button>";
}

echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

// Obter quadros do usuário
$boards = PluginScrumbanBoard::getBoardsForUser($user_id);

if (empty($boards)) {
    echo "<div class='row'>";
    echo "<div class='col-md-12'>";
    echo "<div class='card'>";
    echo "<div class='card-body text-center'>";
    echo "<i class='fas fa-columns fa-4x text-muted mb-3'></i>";
    echo "<h5>" . __('Nenhum quadro disponível', 'scrumban') . "</h5>";
    echo "<p class='text-muted'>" . __('Você não tem acesso a nenhum quadro ainda.', 'scrumban') . "</p>";
    
    if (Session::haveRight('scrumban_board', CREATE)) {
        echo "<button type='button' class='btn btn-primary' onclick='showNewBoardModal()'>";
        echo "<i class='fas fa-plus'></i> " . __('Criar Primeiro Quadro', 'scrumban');
        echo "</button>";
    }
    
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
} else {
    // Grid de quadros
    echo "<div class='row'>";
    
    foreach ($boards as $board) {
        $board_obj = new PluginScrumbanBoard();
        $board_obj->getFromDB($board['id']);
        $stats = $board_obj->getStats();
        
        echo "<div class='col-md-6 col-lg-4 mb-4' data-team-id='" . ($board['team_id'] ?? '') . "'>";
        echo "<div class='card h-100 dashboard-card'>";
        
        // Header do quadro
        echo "<div class='card-header d-flex justify-content-between align-items-start'>";
        echo "<div>";
        echo "<h5 class='card-title mb-1'>" . $board['name'] . "</h5>";
        if ($board['team_name']) {
            echo "<small class='text-muted'><i class='fas fa-users'></i> " . $board['team_name'] . "</small>";
        }
        echo "</div>";
        
        // Badge de permissão
        $permission_badge = '';
        $permission_text = '';
        if ($board['can_manage']) {
            $permission_badge = 'success';
            $permission_text = 'Gerenciar';
        } elseif ($board['can_edit']) {
            $permission_badge = 'primary';
            $permission_text = 'Editar';
        } else {
            $permission_badge = 'secondary';
            $permission_text = 'Visualizar';
        }
        echo "<span class='badge badge-$permission_badge'>$permission_text</span>";
        echo "</div>";
        
        echo "<div class='card-body'>";
        
        // Descrição
        if ($board['description']) {
            echo "<p class='card-text text-muted'>" . substr($board['description'], 0, 100) . (strlen($board['description']) > 100 ? '...' : '') . "</p>";
        }
        
        // Estatísticas em mini-cards
        echo "<div class='row text-center mb-3'>";
        
        echo "<div class='col-3'>";
        echo "<div class='text-center'>";
        echo "<h6 class='text-secondary mb-0'>" . $stats['total_cards'] . "</h6>";
        echo "<small class='text-muted'>Total</small>";
        echo "</div>";
        echo "</div>";
        
        echo "<div class='col-3'>";
        echo "<div class='text-center'>";
        echo "<h6 class='text-warning mb-0'>" . $stats['em_execucao'] . "</h6>";
        echo "<small class='text-muted'>Execução</small>";
        echo "</div>";
        echo "</div>";
        
        echo "<div class='col-3'>";
        echo "<div class='text-center'>";
        echo "<h6 class='text-primary mb-0'>" . $stats['review'] . "</h6>";
        echo "<small class='text-muted'>Review</small>";
        echo "</div>";
        echo "</div>";
        
        echo "<div class='col-3'>";
        echo "<div class='text-center'>";
        echo "<h6 class='text-success mb-0'>" . $stats['done'] . "</h6>";
        echo "<small class='text-muted'>Feitos</small>";
        echo "</div>";
        echo "</div>";
        
        echo "</div>";
        
        // Barra de progresso
        if ($stats['total_cards'] > 0) {
            $progress = round(($stats['done'] / $stats['total_cards']) * 100);
            echo "<div class='progress mb-3' style='height: 6px;'>";
            echo "<div class='progress-bar bg-success' style='width: $progress%'></div>";
            echo "</div>";
            echo "<small class='text-muted'>$progress% concluído</small>";
        } else {
            echo "<div class='text-center text-muted'>";
            echo "<small>" . __('Nenhum card ainda', 'scrumban') . "</small>";
            echo "</div>";
        }
        
        echo "</div>";
        
        // Footer com ações
        echo "<div class='card-footer'>";
        echo "<div class='btn-group btn-group-sm w-100'>";
        
        // Botão Ver Kanban
        echo "<a href='" . $_SERVER['PHP_SELF'] . "?id=" . $board['id'] . "' class='btn btn-primary'>";
        echo "<i class='fas fa-eye'></i> " . __('Ver Kanban', 'scrumban');
        echo "</a>";
        
        // Botão Gerenciar
        if ($board['can_manage']) {
            echo "<a href='" . PluginScrumbanBoard::getFormURLWithID($board['id']) . "' class='btn btn-outline-secondary'>";
            echo "<i class='fas fa-cog'></i> " . __('Configurar', 'scrumban');
            echo "</a>";
        }
        
        // Botão Cards
        echo "<a href='" . PluginScrumbanCard::getFormURL() . "?boards_id=" . $board['id'] . "' class='btn btn-outline-info'>";
        echo "<i class='fas fa-sticky-note'></i> " . __('Cards', 'scrumban');
        echo "</a>";
        
        echo "</div>";
        echo "</div>";
        
        echo "</div>";
        echo "</div>";
    }
    
    echo "</div>";
}

// Todas os quadros (para admins)
if (Session::haveRight('config', UPDATE)) {
    echo "<div class='row mt-5'>";
    echo "<div class='col-md-12'>";
    echo "<div class='card'>";
    echo "<div class='card-header'>";
    echo "<h5><i class='fas fa-columns'></i> " . __('Todos os Quadros (Admin)', 'scrumban') . "</h5>";
    echo "</div>";
    echo "<div class='card-body'>";
    
    global $DB;
    $query = "SELECT b.*, t.name as team_name, u.realname as creator_name, u.firstname as creator_firstname,
                     COUNT(c.id) as card_count
              FROM glpi_plugin_scrumban_boards b
              LEFT JOIN glpi_plugin_scrumban_teams t ON t.id = b.teams_id
              LEFT JOIN glpi_users u ON u.id = b.users_id_created
              LEFT JOIN glpi_plugin_scrumban_cards c ON c.boards_id = b.id
              WHERE b.is_active = 1
              GROUP BY b.id
              ORDER BY b.name";
    
    $result = $DB->query($query);
    
    if ($DB->numrows($result) > 0) {
        echo "<table class='table table-striped'>";
        echo "<thead>";
        echo "<tr>";
        echo "<th>" . __('Nome', 'scrumban') . "</th>";
        echo "<th>" . __('Equipe', 'scrumban') . "</th>";
        echo "<th>" . __('Criador', 'scrumban') . "</th>";
        echo "<th>" . __('Cards', 'scrumban') . "</th>";
        echo "<th>" . __('Visibilidade', 'scrumban') . "</th>";
        echo "<th>" . __('Criado em', 'scrumban') . "</th>";
        echo "<th>" . __('Ações', 'scrumban') . "</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";
        
        while ($board_data = $DB->fetchAssoc($result)) {
            echo "<tr>";
            echo "<td>";
            echo "<strong>" . $board_data['name'] . "</strong>";
            if ($board_data['description']) {
                echo "<br><small class='text-muted'>" . substr($board_data['description'], 0, 50) . "...</small>";
            }
            echo "</td>";
            echo "<td>";
            if ($board_data['team_name']) {
                echo $board_data['team_name'];
            } else {
                echo "<span class='text-muted'>-</span>";
            }
            echo "</td>";
            echo "<td>";
            if ($board_data['creator_name']) {
                echo $board_data['creator_firstname'] . " " . $board_data['creator_name'];
            } else {
                echo "<span class='text-muted'>-</span>";
            }
            echo "</td>";
            echo "<td><span class='badge badge-info'>" . $board_data['card_count'] . "</span></td>";
            echo "<td>";
            $visibility_badges = [
                'public' => ['color' => 'success', 'text' => 'Público'],
                'team' => ['color' => 'primary', 'text' => 'Equipe'],
                'private' => ['color' => 'warning', 'text' => 'Privado']
            ];
            $vis = $visibility_badges[$board_data['visibility']];
            echo "<span class='badge badge-" . $vis['color'] . "'>" . $vis['text'] . "</span>";
            echo "</td>";
            echo "<td>" . Html::convDateTime($board_data['date_creation']) . "</td>";
            echo "<td>";
            echo "<a href='" . $_SERVER['PHP_SELF'] . "?id=" . $board_data['id'] . "' class='btn btn-sm btn-outline-primary mr-1'>";
            echo "<i class='fas fa-eye'></i>";
            echo "</a>";
            echo "<a href='" . PluginScrumbanBoard::getFormURLWithID($board_data['id']) . "' class='btn btn-sm btn-outline-secondary'>";
            echo "<i class='fas fa-edit'></i>";
            echo "</a>";
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</tbody>";
        echo "</table>";
    } else {
        echo "<p class='text-muted'>" . __('Nenhum quadro criado ainda.', 'scrumban') . "</p>";
    }
    
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
}

echo "</div>";

// Modal para novo quadro
if (Session::haveRight('scrumban_board', CREATE)) {
    echo "<div class='modal fade' id='newBoardModal' tabindex='-1'>";
    echo "<div class='modal-dialog'>";
    echo "<div class='modal-content'>";
    echo "<div class='modal-header'>";
    echo "<h5 class='modal-title'>" . __('Novo Quadro', 'scrumban') . "</h5>";
    echo "<button type='button' class='close' data-dismiss='modal'>&times;</button>";
    echo "</div>";
    echo "<form method='post' action='" . PluginScrumbanBoard::getFormURL() . "'>";
    echo "<div class='modal-body'>";
    
    echo "<div class='form-group'>";
    echo "<label>" . __('Nome do Quadro', 'scrumban') . "</label>";
    echo "<input type='text' name='name' class='form-control' required placeholder='Desenvolvimento Web'>";
    echo "</div>";
    
    echo "<div class='form-group'>";
    echo "<label>" . __('Descrição', 'scrumban') . "</label>";
    echo "<textarea name='description' class='form-control' rows='3' placeholder='Quadro para gerenciar desenvolvimento...'></textarea>";
    echo "</div>";
    
    echo "<div class='form-group'>";
    echo "<label>" . __('Equipe Proprietária', 'scrumban') . "</label>";
    echo "<select name='teams_id' class='form-control'>";
    echo "<option value='0'>" . __('Nenhuma equipe específica', 'scrumban') . "</option>";
    foreach ($teams as $team) {
        if (in_array($team['role'], ['admin', 'lead'])) {
            echo "<option value='" . $team['id'] . "'>" . $team['name'] . "</option>";
        }
    }
    echo "</select>";
    echo "</div>";
    
    echo "<div class='form-group'>";
    echo "<label>" . __('Visibilidade', 'scrumban') . "</label>";
    echo "<select name='visibility' class='form-control'>";
    echo "<option value='team'>" . __('Equipe - Apenas membros da equipe', 'scrumban') . "</option>";
    echo "<option value='public'>" . __('Público - Todos podem ver', 'scrumban') . "</option>";
    echo "<option value='private'>" . __('Privado - Apenas você', 'scrumban') . "</option>";
    echo "</select>";
    echo "</div>";
    
    echo "<input type='hidden' name='action' value='add'>";
    echo "</div>";
    echo "<div class='modal-footer'>";
    echo "<button type='button' class='btn btn-secondary' data-dismiss='modal'>" . __('Cancelar', 'scrumban') . "</button>";
    echo "<button type='submit' class='btn btn-primary'>" . __('Criar Quadro', 'scrumban') . "</button>";
    echo "</div>";
    echo "</form>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
}

// JavaScript
echo "<script>
function showNewBoardModal() {
    $('#newBoardModal').modal('show');
}

$(document).ready(function() {
    // Filtro por equipe
    $('#team_filter').change(function() {
        var teamId = $(this).val();
        
        $('.dashboard-card').parent().each(function() {
            var card = $(this);
            var show = true;
            
            if (teamId && card.data('team-id') != teamId) {
                show = false;
            }
            
            card.toggle(show);
        });
    });
    
    // Focar no campo nome quando modal abrir
    $('#newBoardModal').on('shown.bs.modal', function() {
        $(this).find('input[name=\"name\"]').focus();
    });
});
</script>";

Html::footer();