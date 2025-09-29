<?php

include '../../../inc/includes.php';

header('Content-Type: application/json');

if (!Session::haveRight('scrumban_board', READ)) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'activate':
        activateSprint();
        break;
        
    case 'deactivate':
        deactivateSprint();
        break;
        
    case 'get_sprint_stats':
        getSprintStats();
        break;
        
    case 'update_sprint':
        updateSprint();
        break;
        
    case 'create_sprint':
        createSprint();
        break;
        
    case 'delete_sprint':
        deleteSprint();
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Ação inválida']);
        break;
}

function activateSprint() {
    if (!Session::haveRight('scrumban_board', UPDATE)) {
        echo json_encode(['success' => false, 'error' => 'Sem permissão']);
        return;
    }
    
    $sprint_id = (int)($_POST['sprint_id'] ?? 0);
    
    if (!$sprint_id) {
        echo json_encode(['success' => false, 'error' => 'ID do sprint não fornecido']);
        return;
    }
    
    $sprint = new PluginScrumbanSprint();
    if (!$sprint->getFromDB($sprint_id)) {
        echo json_encode(['success' => false, 'error' => 'Sprint não encontrado']);
        return;
    }
    
    // Verificar permissão no quadro
    if (!PluginScrumbanBoard::canUserManageBoard(Session::getLoginUserID(), $sprint->fields['boards_id'])) {
        echo json_encode(['success' => false, 'error' => 'Sem permissão para gerenciar este quadro']);
        return;
    }
    
    // Ativar sprint
    if ($sprint->activate()) {
        echo json_encode(['success' => true, 'message' => 'Sprint ativado com sucesso']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao ativar sprint']);
    }
}

function deactivateSprint() {
    if (!Session::haveRight('scrumban_board', UPDATE)) {
        echo json_encode(['success' => false, 'error' => 'Sem permissão']);
        return;
    }
    
    $sprint_id = (int)($_POST['sprint_id'] ?? 0);
    
    if (!$sprint_id) {
        echo json_encode(['success' => false, 'error' => 'ID do sprint não fornecido']);
        return;
    }
    
    $sprint = new PluginScrumbanSprint();
    if (!$sprint->getFromDB($sprint_id)) {
        echo json_encode(['success' => false, 'error' => 'Sprint não encontrado']);
        return;
    }
    
    // Verificar permissão no quadro
    if (!PluginScrumbanBoard::canUserManageBoard(Session::getLoginUserID(), $sprint->fields['boards_id'])) {
        echo json_encode(['success' => false, 'error' => 'Sem permissão para gerenciar este quadro']);
        return;
    }
    
    // Desativar sprint
    if ($sprint->deactivate()) {
        echo json_encode(['success' => true, 'message' => 'Sprint desativado com sucesso']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao desativar sprint']);
    }
}

function getSprintStats() {
    $sprint_id = (int)($_GET['sprint_id'] ?? 0);
    
    if (!$sprint_id) {
        echo json_encode(['success' => false, 'error' => 'ID do sprint não fornecido']);
        return;
    }
    
    $sprint = new PluginScrumbanSprint();
    if (!$sprint->getFromDB($sprint_id)) {
        echo json_encode(['success' => false, 'error' => 'Sprint não encontrado']);
        return;
    }
    
    // Verificar acesso ao quadro
    if (!PluginScrumbanTeam::canUserAccessBoard(Session::getLoginUserID(), $sprint->fields['boards_id'])) {
        echo json_encode(['success' => false, 'error' => 'Acesso negado']);
        return;
    }
    
    // Obter estatísticas
    $stats = $sprint->getDetailedStats();
    $progress = PluginScrumbanSprint::getSprintProgress($sprint_id);
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'progress' => $progress
    ]);
}

function updateSprint() {
    if (!Session::haveRight('scrumban_board', UPDATE)) {
        echo json_encode(['success' => false, 'error' => 'Sem permissão']);
        return;
    }
    
    $sprint_id = (int)($_POST['sprint_id'] ?? 0);
    
    if (!$sprint_id) {
        echo json_encode(['success' => false, 'error' => 'ID do sprint não fornecido']);
        return;
    }
    
    $sprint = new PluginScrumbanSprint();
    if (!$sprint->getFromDB($sprint_id)) {
        echo json_encode(['success' => false, 'error' => 'Sprint não encontrado']);
        return;
    }
    
    // Verificar permissão no quadro
    if (!PluginScrumbanBoard::canUserManageBoard(Session::getLoginUserID(), $sprint->fields['boards_id'])) {
        echo json_encode(['success' => false, 'error' => 'Sem permissão para gerenciar este quadro']);
        return;
    }
    
    // Preparar dados para atualização
    $data = ['id' => $sprint_id];
    
    if (isset($_POST['name'])) {
        $data['name'] = $_POST['name'];
    }
    
    if (isset($_POST['description'])) {
        $data['description'] = $_POST['description'];
    }
    
    if (isset($_POST['date_start'])) {
        $data['date_start'] = $_POST['date_start'];
    }
    
    if (isset($_POST['date_end'])) {
        $data['date_end'] = $_POST['date_end'];
    }
    
    if (isset($_POST['is_active'])) {
        $data['is_active'] = $_POST['is_active'] ? 1 : 0;
    }
    
    // Atualizar sprint
    if ($sprint->update($data)) {
        echo json_encode(['success' => true, 'message' => 'Sprint atualizado com sucesso']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao atualizar sprint']);
    }
}

function createSprint() {
    if (!Session::getLoginUserID()) {
        echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
        return;
    }

    $boards_id = (int)($_POST['boards_id'] ?? 0);
    if (!$boards_id) {
        echo json_encode(['success' => false, 'error' => 'ID do quadro obrigatório']);
        return;
    }

    if (!PluginScrumbanBoard::canUserEditBoard(Session::getLoginUserID(), $boards_id)) {
        echo json_encode(['success' => false, 'error' => 'Sem permissão neste quadro']);
        return;
    }

    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        echo json_encode(['success' => false, 'error' => 'Nome é obrigatório']);
        return;
    }

    $data = [
        'boards_id'   => $boards_id,
        'name'        => $name,
        'description' => $_POST['description'] ?? '',
        'date_start'  => $_POST['date_start'] ?? null,
        'date_end'    => $_POST['date_end'] ?? null,
        'is_active'   => isset($_POST['is_active']) ? 1 : 0
    ];

    $sprint = new PluginScrumbanSprint();
    $sprint_id = $sprint->add($data);

    if ($sprint_id) {
        echo json_encode(['success' => true, 'sprint_id' => $sprint_id]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao criar sprint']);
    }
}

function deleteSprint() {
    if (!Session::haveRight('scrumban_board', DELETE)) {
        echo json_encode(['success' => false, 'error' => 'Sem permissão']);
        return;
    }
    
    $sprint_id = (int)($_POST['sprint_id'] ?? 0);
    
    if (!$sprint_id) {
        echo json_encode(['success' => false, 'error' => 'ID do sprint não fornecido']);
        return;
    }
    
    $sprint = new PluginScrumbanSprint();
    if (!$sprint->getFromDB($sprint_id)) {
        echo json_encode(['success' => false, 'error' => 'Sprint não encontrado']);
        return;
    }
    
    // Verificar permissão no quadro
    if (!PluginScrumbanBoard::canUserManageBoard(Session::getLoginUserID(), $sprint->fields['boards_id'])) {
        echo json_encode(['success' => false, 'error' => 'Sem permissão para gerenciar este quadro']);
        return;
    }
    
    // Verificar se há cards no sprint
    global $DB;
    $count = countElementsInTable('glpi_plugin_scrumban_cards', ['sprint_id' => $sprint_id]);
    
    if ($count > 0) {
        echo json_encode(['success' => false, 'error' => 'Não é possível excluir sprint que possui cards']);
        return;
    }
    
    // Deletar sprint
    if ($sprint->delete(['id' => $sprint_id])) {
        echo json_encode(['success' => true, 'message' => 'Sprint excluído com sucesso']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao excluir sprint']);
    }
}