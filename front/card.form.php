<?php

include '../../../inc/includes.php';

Session::checkRight('scrumban_card', READ);

global $DB;
$user_id = Session::getLoginUserID();

$card_id = $_GET['id'] ?? 0;
$board_id = $_GET['board_id'] ?? 0;

// Processar submissão
if (isset($_POST['add']) || isset($_POST['update'])) {
    $data = [
        'title' => $_POST['title'],
        'description' => $_POST['description'] ?? '',
        'boards_id' => $_POST['boards_id'],
        'status' => $_POST['status'] ?? 'backlog',
        'priority' => $_POST['priority'] ?? 2,
        'users_id_assigned' => $_POST['users_id_assigned'] ?? 0,
        'users_id_requester' => $_POST['users_id_requester'] ?? $user_id,
        'story_points' => $_POST['story_points'] ?? 0,
        'sprint_id' => $_POST['sprint_id'] ?? 0,
        'entities_id' => $_SESSION['glpiactive_entity']
    ];
    
    if (isset($_POST['add'])) {
        $data['date_creation'] = date('Y-m-d H:i:s');
        $result = $DB->insert('glpi_plugin_scrumban_cards', $data);
        
        if ($result) {
            Session::addMessageAfterRedirect('Card criado com sucesso!', true, INFO);
            Html::redirect('board.php?id=' . $data['boards_id']);
        }
    } else {
        $data['date_mod'] = date('Y-m-d H:i:s');
        $result = $DB->update('glpi_plugin_scrumban_cards', $data, ['id' => $card_id]);
        
        if ($result) {
            Session::addMessageAfterRedirect('Card atualizado com sucesso!', true, INFO);
            Html::redirect('board.php?id=' . $data['boards_id']);
        }
    }
    exit;
}

// Buscar card se for edição
$card = null;
if ($card_id > 0) {
    $iterator = $DB->request([
        'FROM' => 'glpi_plugin_scrumban_cards',
        'WHERE' => ['id' => $card_id]
    ]);
    
    if (count($iterator) > 0) {
        $card = $iterator->current();
        $board_id = $card['boards_id'];
    }
}

// Buscar quadros disponíveis
$boards = [];
$iterator = $DB->request([
    'FROM' => 'glpi_plugin_scrumban_boards',
    'WHERE' => ['is_active' => 1],
    'ORDER' => 'name'
]);

foreach ($iterator as $data) {
    $boards[$data['id']] = $data['name'];
}

// Buscar sprints do quadro
$sprints = [];
if ($board_id > 0) {
    $iterator = $DB->request([
        'FROM' => 'glpi_plugin_scrumban_sprints',
        'WHERE' => ['boards_id' => $board_id, 'is_active' => 1],
        'ORDER' => 'name'
    ]);
    
    foreach ($iterator as $data) {
        $sprints[$data['id']] = $data['name'];
    }
}

Html::header($card ? 'Editar Card' : 'Novo Card', $_SERVER['PHP_SELF'], "tools", "pluginscrumbanmenu");

echo "<div class='container-fluid mt-3'>";
echo "<form method='post'>";

echo "<div class='card'>";
echo "<div class='card-header'>";
echo "<h4>" . ($card ? "<i class='fas fa-edit'></i> Editar Card" : "<i class='fas fa-plus'></i> Novo Card") . "</h4>";
echo "</div>";

echo "<div class='card-body'>";

// Título
echo "<div class='row mb-3'>";
echo "<div class='col-md-8'>";
echo "<label class='form-label'>Título *</label>";
echo "<input type='text' name='title' class='form-control' value='" . htmlspecialchars($card['title'] ?? '') . "' required>";
echo "</div>";

// Prioridade
echo "<div class='col-md-4'>";
echo "<label class='form-label'>Prioridade</label>";
echo "<select name='priority' class='form-select'>";
$priorities = [1 => 'Baixa', 2 => 'Normal', 3 => 'Alta', 4 => 'Urgente'];
foreach ($priorities as $value => $label) {
    $selected = ($card['priority'] ?? 2) == $value ? 'selected' : '';
    echo "<option value='$value' $selected>$label</option>";
}
echo "</select>";
echo "</div>";
echo "</div>";

// Descrição
echo "<div class='mb-3'>";
echo "<label class='form-label'>Descrição</label>";
echo "<textarea name='description' class='form-control' rows='5'>" . htmlspecialchars($card['description'] ?? '') . "</textarea>";
echo "</div>";

// Quadro, Status e Story Points
echo "<div class='row mb-3'>";

// Quadro
echo "<div class='col-md-4'>";
echo "<label class='form-label'>Quadro *</label>";
echo "<select name='boards_id' class='form-select' required>";
echo "<option value=''>Selecione...</option>";
foreach ($boards as $id => $name) {
    $selected = ($card['boards_id'] ?? $board_id) == $id ? 'selected' : '';
    echo "<option value='$id' $selected>" . htmlspecialchars($name) . "</option>";
}
echo "</select>";
echo "</div>";

// Status
echo "<div class='col-md-4'>";
echo "<label class='form-label'>Status</label>";
echo "<select name='status' class='form-select'>";
$statuses = [
    'backlog' => 'Backlog',
    'todo' => 'A Fazer',
    'em-execucao' => 'Em Execução',
    'review' => 'Review',
    'done' => 'Concluído'
];
foreach ($statuses as $value => $label) {
    $selected = ($card['status'] ?? 'backlog') == $value ? 'selected' : '';
    echo "<option value='$value' $selected>$label</option>";
}
echo "</select>";
echo "</div>";

// Story Points
echo "<div class='col-md-4'>";
echo "<label class='form-label'>Story Points</label>";
echo "<input type='number' name='story_points' class='form-control' value='" . ($card['story_points'] ?? 0) . "' min='0'>";
echo "</div>";

echo "</div>";

// Responsável e Solicitante
echo "<div class='row mb-3'>";

echo "<div class='col-md-6'>";
echo "<label class='form-label'>Responsável</label>";
User::dropdown([
    'name' => 'users_id_assigned',
    'value' => $card['users_id_assigned'] ?? 0,
    'right' => 'all'
]);
echo "</div>";

echo "<div class='col-md-6'>";
echo "<label class='form-label'>Solicitante</label>";
User::dropdown([
    'name' => 'users_id_requester',
    'value' => $card['users_id_requester'] ?? $user_id,
    'right' => 'all'
]);
echo "</div>";

echo "</div>";

// Sprint
if (!empty($sprints)) {
    echo "<div class='mb-3'>";
    echo "<label class='form-label'>Sprint</label>";
    echo "<select name='sprint_id' class='form-select'>";
    echo "<option value='0'>Sem sprint</option>";
    foreach ($sprints as $id => $name) {
        $selected = ($card['sprint_id'] ?? 0) == $id ? 'selected' : '';
        echo "<option value='$id' $selected>" . htmlspecialchars($name) . "</option>";
    }
    echo "</select>";
    echo "</div>";
}

echo "</div>";

// Footer com botões
echo "<div class='card-footer'>";
echo "<div class='d-flex justify-content-between'>";

echo "<a href='board.php" . ($board_id ? "?id=$board_id" : "") . "' class='btn btn-secondary'>";
echo "<i class='fas fa-arrow-left'></i> Voltar";
echo "</a>";

if ($card) {
    echo "<button type='submit' name='update' class='btn btn-primary'>";
    echo "<i class='fas fa-save'></i> Atualizar Card";
    echo "</button>";
} else {
    echo "<button type='submit' name='add' class='btn btn-success'>";
    echo "<i class='fas fa-plus'></i> Criar Card";
    echo "</button>";
}

echo "</div>";
echo "</div>";

echo "</div>";
echo "</form>";
echo "</div>";

Html::footer();