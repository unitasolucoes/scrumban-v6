<?php

include '../../../inc/includes.php';

// Configurar cabeçalho JSON
header('Content-Type: application/json');

// Verificar permissões básicas
if (!Session::haveRight('scrumban_team', READ)) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit;
}

// Obter ação
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Router de ações
switch ($action) {
    case 'add_member':
        addMember();
        break;
        
    case 'remove_member':
        removeMember();
        break;
        
    case 'change_role':
        changeRole();
        break;
        
    case 'add_board':
        addBoard();
        break;
        
    case 'remove_board':
        removeBoard();
        break;
        
    case 'update_permissions':
        updatePermissions();
        break;
        
    case 'create_team':
        createTeam();
        break;
        
    case 'update_team':
        updateTeam();
        break;
        
    case 'delete_team':
        deleteTeam();
        break;
        
    case 'get_team_info':
        getTeamInfo();
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Ação inválida']);
        break;
}

/**
 * Adicionar membro à equipe
 */
function addMember() {
    // Verificar permissões
    if (!Session::haveRight('scrumban_team', UPDATE)) {
        echo json_encode(['success' => false, 'error' => 'Sem permissão para atualizar']);
        return;
    }
    
    // Validar parâmetros
    $teams_id = (int)($_POST['teams_id'] ?? 0);
    $users_id = (int)($_POST['users_id'] ?? 0);
    $role = $_POST['role'] ?? 'member';
    
    if (!$teams_id || !$users_id) {
        echo json_encode(['success' => false, 'error' => 'Parâmetros obrigatórios não fornecidos']);
        return;
    }
    
    // Validar papel
    $valid_roles = ['member', 'lead', 'admin'];
    if (!in_array($role, $valid_roles)) {
        echo json_encode(['success' => false, 'error' => 'Papel inválido']);
        return;
    }
    
    // Verificar se equipe existe
    $team = new PluginScrumbanTeam();
    if (!$team->getFromDB($teams_id)) {
        echo json_encode(['success' => false, 'error' => 'Equipe não encontrada']);
        return;
    }
    
    // Verificar permissão para gerenciar equipe
    if (!$team->canUserManage(Session::getLoginUserID())) {
        echo json_encode(['success' => false, 'error' => 'Sem permissão para gerenciar esta equipe']);
        return;
    }
    
    // Verificar se usuário já é membro
    if (PluginScrumbanTeamMember::isUserInTeam($users_id, $teams_id)) {
        echo json_encode(['success' => false, 'error' => 'Usuário já é membro desta equipe']);
        return;
    }
    
    // Adicionar membro
    if (PluginScrumbanTeamMember::addMemberToTeam($teams_id, $users_id, $role)) {
        echo json_encode(['success' => true, 'message' => 'Membro adicionado com sucesso']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao adicionar membro']);
    }
}

/**
 * Remover membro da equipe
 */
function removeMember() {
    // Verificar permissões
    if (!Session::haveRight('scrumban_team', UPDATE)) {
        echo json_encode(['success' => false, 'error' => 'Sem permissão para atualizar']);
        return;
    }
    
    // Validar parâmetros
    $member_id = (int)($_POST['member_id'] ?? 0);
    
    if (!$member_id) {
        echo json_encode(['success' => false, 'error' => 'ID do membro não fornecido']);
        return;
    }
    
    // Verificar se membro existe
    $member = new PluginScrumbanTeamMember();
    if (!$member->getFromDB($member_id)) {
        echo json_encode(['success' => false, 'error' => 'Membro não encontrado']);
        return;
    }
    
    // Verificar permissão para gerenciar equipe
    $team = new PluginScrumbanTeam();
    if (!$team->getFromDB($member->fields['teams_id'])) {
        echo json_encode(['success' => false, 'error' => 'Equipe não encontrada']);
        return;
    }
    
    if (!$team->canUserManage(Session::getLoginUserID())) {
        echo json_encode(['success' => false, 'error' => 'Sem permissão para gerenciar esta equipe']);
        return;
    }
    
    // Não pode remover a si mesmo
    if ($member->fields['users_id'] == Session::getLoginUserID()) {
        echo json_encode(['success' => false, 'error' => 'Você não pode remover a si mesmo da equipe']);
        return;
    }
    
    // Tentar remover membro
    if ($member->removeMember()) {
        echo json_encode(['success' => true, 'message' => 'Membro removido com sucesso']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Não é possível remover este membro (pode ser o último administrador)']);
    }
}

/**
 * Alterar papel do membro
 */
function changeRole() {
    // Verificar permissões
    if (!Session::haveRight('scrumban_team', UPDATE)) {
        echo json_encode(['success' => false, 'error' => 'Sem permissão para atualizar']);
        return;
    }
    
    // Validar parâmetros
    $member_id = (int)($_POST['member_id'] ?? 0);
    $new_role = $_POST['role'] ?? '';
    
    if (!$member_id || !$new_role) {
        echo json_encode(['success' => false, 'error' => 'Parâmetros obrigatórios não fornecidos']);
        return;
    }
    
    // Validar papel
    $valid_roles = ['member', 'lead', 'admin'];
    if (!in_array($new_role, $valid_roles)) {
        echo json_encode(['success' => false, 'error' => 'Papel inválido']);
        return;
    }
    
    // Verificar se membro existe
    $member = new PluginScrumbanTeamMember();
    if (!$member->getFromDB($member_id)) {
        echo json_encode(['success' => false, 'error' => 'Membro não encontrado']);
        return;
    }
    
    // Verificar se usuário é admin da equipe (apenas admins podem alterar papéis)
    $user_role = PluginScrumbanTeam::getUserRole(Session::getLoginUserID(), $member->fields['teams_id']);
    if ($user_role != 'admin') {
        echo json_encode(['success' => false, 'error' => 'Apenas administradores podem alterar papéis']);
        return;
    }
    
    // Não pode alterar o próprio papel
    if ($member->fields['users_id'] == Session::getLoginUserID()) {
        echo json_encode(['success' => false, 'error' => 'Você não pode alterar seu próprio papel']);
        return;
    }
    
    // Tentar alterar papel
    if ($member->changeRole($new_role)) {
        echo json_encode(['success' => true, 'message' => 'Papel alterado com sucesso']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Não é possível alterar este papel (pode ser o último administrador)']);
    }
}

/**
 * Adicionar quadro à equipe
 */
function addBoard() {
    // Verificar permissões
    if (!Session::haveRight('scrumban_team', UPDATE)) {
        echo json_encode(['success' => false, 'error' => 'Sem permissão para atualizar']);
        return;
    }
    
    // Validar parâmetros
    $teams_id = (int)($_POST['teams_id'] ?? 0);
    $boards_id = (int)($_POST['boards_id'] ?? 0);
    $can_edit = isset($_POST['can_edit']) ? 1 : 0;
    $can_manage = isset($_POST['can_manage']) ? 1 : 0;
    
    if (!$teams_id || !$boards_id) {
        echo json_encode(['success' => false, 'error' => 'Parâmetros obrigatórios não fornecidos']);
        return;
    }
    
    // Verificar se equipe existe e permissão para gerenciar
    $team = new PluginScrumbanTeam();
    if (!$team->getFromDB($teams_id)) {
        echo json_encode(['success' => false, 'error' => 'Equipe não encontrada']);
        return;
    }
    
    if (!$team->canUserManage(Session::getLoginUserID())) {
        echo json_encode(['success' => false, 'error' => 'Sem permissão para gerenciar esta equipe']);
        return;
    }
    
    // Verificar se usuário tem acesso ao quadro
    if (!PluginScrumbanTeam::canUserAccessBoard(Session::getLoginUserID(), $boards_id)) {
        echo json_encode(['success' => false, 'error' => 'Sem acesso a este quadro']);
        return;
    }
    
    // Verificar se já está associado
    if (PluginScrumbanTeamBoard::teamHasBoard($teams_id, $boards_id)) {
        echo json_encode(['success' => false, 'error' => 'Quadro já está associado a esta equipe']);
        return;
    }
    
    // Adicionar quadro
    if (PluginScrumbanTeamBoard::addBoardToTeam($teams_id, $boards_id, $can_edit, $can_manage)) {
        echo json_encode(['success' => true, 'message' => 'Quadro associado com sucesso']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao associar quadro']);
    }
}

/**
 * Remover quadro da equipe
 */
function removeBoard() {
    // Verificar permissões
    if (!Session::haveRight('scrumban_team', UPDATE)) {
        echo json_encode(['success' => false, 'error' => 'Sem permissão para atualizar']);
        return;
    }
    
    // Validar parâmetros
    $team_board_id = (int)($_POST['team_board_id'] ?? 0);
    
    if (!$team_board_id) {
        echo json_encode(['success' => false, 'error' => 'ID da associação não fornecido']);
        return;
    }
    
    // Verificar se associação existe
    $team_board = new PluginScrumbanTeamBoard();
    if (!$team_board->getFromDB($team_board_id)) {
        echo json_encode(['success' => false, 'error' => 'Associação não encontrada']);
        return;
    }
    
    // Verificar permissão para gerenciar equipe
    $team = new PluginScrumbanTeam();
    if (!$team->getFromDB($team_board->fields['teams_id'])) {
        echo json_encode(['success' => false, 'error' => 'Equipe não encontrada']);
        return;
    }
    
    if (!$team->canUserManage(Session::getLoginUserID())) {
        echo json_encode(['success' => false, 'error' => 'Sem permissão para gerenciar esta equipe']);
        return;
    }
    
    // Remover associação
    if ($team_board->removeBoard()) {
        echo json_encode(['success' => true, 'message' => 'Quadro removido da equipe com sucesso']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao remover quadro da equipe']);
    }
}

/**
 * Atualizar permissões do quadro na equipe
 */
function updatePermissions() {
    // Verificar permissões
    if (!Session::haveRight('scrumban_team', UPDATE)) {
        echo json_encode(['success' => false, 'error' => 'Sem permissão para atualizar']);
        return;
    }
    
    // Validar parâmetros
    $team_board_id = (int)($_POST['id'] ?? 0);
    $can_edit = isset($_POST['can_edit']) ? 1 : 0;
    $can_manage = isset($_POST['can_manage']) ? 1 : 0;
    
    if (!$team_board_id) {
        echo json_encode(['success' => false, 'error' => 'ID da associação não fornecido']);
        return;
    }
    
    // Verificar se associação existe
    $team_board = new PluginScrumbanTeamBoard();
    if (!$team_board->getFromDB($team_board_id)) {
        echo json_encode(['success' => false, 'error' => 'Associação não encontrada']);
        return;
    }
    
    // Verificar permissão para gerenciar equipe
    $team = new PluginScrumbanTeam();
    if (!$team->getFromDB($team_board->fields['teams_id'])) {
        echo json_encode(['success' => false, 'error' => 'Equipe não encontrada']);
        return;
    }
    
    if (!$team->canUserManage(Session::getLoginUserID())) {
        echo json_encode(['success' => false, 'error' => 'Sem permissão para gerenciar esta equipe']);
        return;
    }
    
    // Atualizar permissões
    if ($team_board->updatePermissions($can_edit, $can_manage)) {
        echo json_encode(['success' => true, 'message' => 'Permissões atualizadas com sucesso']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao atualizar permissões']);
    }
}

/**
 * Criar nova equipe
 */
function createTeam() {
    // Verificar permissões
    if (!Session::haveRight('scrumban_team', CREATE)) {
        echo json_encode(['success' => false, 'error' => 'Sem permissão para criar']);
        return;
    }
    
    // Validar parâmetros
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $manager_id = (int)($_POST['manager_id'] ?? Session::getLoginUserID());
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Nome é obrigatório']);
        return;
    }
    
    if (strlen($name) > 255) {
        echo json_encode(['success' => false, 'error' => 'Nome muito longo (máximo 255 caracteres)']);
        return;
    }
    
    // Preparar dados
    $data = [
        'name' => $name,
        'description' => $description,
        'manager_id' => $manager_id,
        'is_active' => 1
    ];
    
    // Criar equipe
    $team = new PluginScrumbanTeam();
    if ($team->add($data)) {
        echo json_encode([
            'success' => true, 
            'team_id' => $team->fields['id'], 
            'message' => 'Equipe criada com sucesso'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao criar equipe']);
    }
}

/**
 * Atualizar equipe existente
 */
function updateTeam() {
    // Verificar permissões
    if (!Session::haveRight('scrumban_team', UPDATE)) {
        echo json_encode(['success' => false, 'error' => 'Sem permissão para atualizar']);
        return;
    }
    
    // Validar parâmetros
    $team_id = (int)($_POST['team_id'] ?? 0);
    
    if (!$team_id) {
        echo json_encode(['success' => false, 'error' => 'ID da equipe não fornecido']);
        return;
    }
    
    // Verificar se equipe existe e permissão
    $team = new PluginScrumbanTeam();
    if (!$team->getFromDB($team_id)) {
        echo json_encode(['success' => false, 'error' => 'Equipe não encontrada']);
        return;
    }
    
    if (!$team->canUserManage(Session::getLoginUserID())) {
        echo json_encode(['success' => false, 'error' => 'Sem permissão para gerenciar esta equipe']);
        return;
    }
    
    // Preparar dados para atualização
    $data = ['id' => $team_id];
    
    if (isset($_POST['name'])) {
        $name = trim($_POST['name']);
        if (empty($name)) {
            echo json_encode(['success' => false, 'error' => 'Nome não pode estar vazio']);
            return;
        }
        if (strlen($name) > 255) {
            echo json_encode(['success' => false, 'error' => 'Nome muito longo (máximo 255 caracteres)']);
            return;
        }
        $data['name'] = $name;
    }
    
    if (isset($_POST['description'])) {
        $data['description'] = trim($_POST['description']);
    }
    
    if (isset($_POST['manager_id'])) {
        $data['manager_id'] = (int)$_POST['manager_id'];
    }
    
    if (isset($_POST['is_active'])) {
        $data['is_active'] = $_POST['is_active'] ? 1 : 0;
    }
    
    // Atualizar equipe
    if ($team->update($data)) {
        echo json_encode(['success' => true, 'message' => 'Equipe atualizada com sucesso']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao atualizar equipe']);
    }
}

/**
 * Excluir equipe
 */
function deleteTeam() {
    // Verificar permissões
    if (!Session::haveRight('scrumban_team', DELETE)) {
        echo json_encode(['success' => false, 'error' => 'Sem permissão para excluir']);
        return;
    }
    
    // Validar parâmetros
    $team_id = (int)($_POST['team_id'] ?? 0);
    
    if (!$team_id) {
        echo json_encode(['success' => false, 'error' => 'ID da equipe não fornecido']);
        return;
    }
    
    // Verificar se equipe existe
    $team = new PluginScrumbanTeam();
    if (!$team->getFromDB($team_id)) {
        echo json_encode(['success' => false, 'error' => 'Equipe não encontrada']);
        return;
    }
    
    // Verificar se usuário é admin da equipe
    $user_role = PluginScrumbanTeam::getUserRole(Session::getLoginUserID(), $team_id);
    if ($user_role != 'admin') {
        echo json_encode(['success' => false, 'error' => 'Apenas administradores podem excluir equipes']);
        return;
    }
    
    // Verificar se há quadros associados
    global $DB;
    $board_count = countElementsInTable('glpi_plugin_scrumban_team_boards', ['teams_id' => $team_id]);
    
    if ($board_count > 0) {
        echo json_encode(['success' => false, 'error' => 'Não é possível excluir equipe que possui quadros associados']);
        return;
    }
    
    // Excluir equipe (isso também exclui os membros via cascata)
    if ($team->delete(['id' => $team_id])) {
        echo json_encode(['success' => true, 'message' => 'Equipe excluída com sucesso']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao excluir equipe']);
    }
}

/**
 * Obter informações da equipe
 */
function getTeamInfo() {
    // Validar parâmetros
    $team_id = (int)($_GET['team_id'] ?? 0);
    
    if (!$team_id) {
        echo json_encode(['success' => false, 'error' => 'ID da equipe não fornecido']);
        return;
    }
    
    // Verificar se equipe existe
    $team = new PluginScrumbanTeam();
    if (!$team->getFromDB($team_id)) {
        echo json_encode(['success' => false, 'error' => 'Equipe não encontrada']);
        return;
    }
    
    // Verificar se usuário tem acesso à equipe
    $user_role = PluginScrumbanTeam::getUserRole(Session::getLoginUserID(), $team_id);
    if (!$user_role) {
        echo json_encode(['success' => false, 'error' => 'Sem acesso a esta equipe']);
        return;
    }
    
    // Obter informações da equipe
    $team_info = [
        'id' => $team->fields['id'],
        'name' => $team->fields['name'],
        'description' => $team->fields['description'],
        'manager_id' => $team->fields['manager_id'],
        'is_active' => $team->fields['is_active'],
        'user_role' => $user_role,
        'can_manage' => in_array($user_role, ['admin', 'lead'])
    ];
    
    // Obter estatísticas
    $stats = PluginScrumbanTeamBoard::getTeamBoardsStats($team_id);
    $team_info['stats'] = [
        'total_boards' => count($stats),
        'total_cards' => array_sum(array_column($stats, 'card_count'))
    ];
    
    echo json_encode(['success' => true, 'team' => $team_info]);
}