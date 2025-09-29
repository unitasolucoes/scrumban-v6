<?php

include '../../../inc/includes.php';

header('Content-Type: application/json');

if (!Session::haveRight('scrumban_card', READ)) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_card_details':
        getCardDetails();
        break;
        
    case 'update_status':
        updateCardStatus();
        break;
        
    case 'add_comment':
        addComment();
        break;
        
    case 'create':
        createCard();
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Ação inválida']);
        break;
}

function getCardDetails() {
    $card_id = (int)$_POST['card_id'];
    
    $card = new PluginScrumbanCard();
    if (!$card->getFromDB($card_id)) {
        echo json_encode(['success' => false, 'error' => 'Card não encontrado']);
        return;
    }
    
    // Verificar acesso ao quadro do card
    if (!PluginScrumbanTeam::canUserAccessBoard(Session::getLoginUserID(), $card->fields['boards_id'])) {
        echo json_encode(['success' => false, 'error' => 'Acesso negado']);
        return;
    }
    
    // Retornar HTML do modal em vez de JSON
    header('Content-Type: text/html');
    echo $card->renderCardModal();
}

function updateCardStatus() {
    if (!Session::haveRight('scrumban_card', UPDATE)) {
        echo json_encode(['success' => false, 'error' => 'Sem permissão para editar']);
        return;
    }
    
    $card_id = (int)$_POST['card_id'];
    $new_status = $_POST['status'];
    
    // Validar status
    $valid_statuses = ['backlog', 'todo', 'em-execucao', 'review', 'done'];
    if (!in_array($new_status, $valid_statuses)) {
        echo json_encode(['success' => false, 'error' => 'Status inválido']);
        return;
    }
    
    $card = new PluginScrumbanCard();
    if (!$card->getFromDB($card_id)) {
        echo json_encode(['success' => false, 'error' => 'Card não encontrado']);
        return;
    }
    
    // Verificar permissão de edição no quadro
    if (!PluginScrumbanBoard::canUserEditBoard(Session::getLoginUserID(), $card->fields['boards_id'])) {
        echo json_encode(['success' => false, 'error' => 'Sem permissão para editar este quadro']);
        return;
    }
    
    // Atualizar status
    if ($card->updateStatus($new_status)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao atualizar status']);
    }
}

function addComment() {
    if (!Session::haveRight('scrumban_card', UPDATE)) {
        echo json_encode(['success' => false, 'error' => 'Sem permissão para comentar']);
        return;
    }
    
    $card_id = (int)$_POST['card_id'];
    $comment_text = trim($_POST['comment']);
    
    if (empty($comment_text)) {
        echo json_encode(['success' => false, 'error' => 'Comentário não pode estar vazio']);
        return;
    }
    
    $card = new PluginScrumbanCard();
    if (!$card->getFromDB($card_id)) {
        echo json_encode(['success' => false, 'error' => 'Card não encontrado']);
        return;
    }
    
    // Verificar acesso ao quadro
    if (!PluginScrumbanTeam::canUserAccessBoard(Session::getLoginUserID(), $card->fields['boards_id'])) {
        echo json_encode(['success' => false, 'error' => 'Acesso negado']);
        return;
    }
    
    // Adicionar comentário
    if ($card->addComment($comment_text)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao adicionar comentário']);
    }
}

function createCard() {
    if (!Session::haveRight('scrumban_card', CREATE)) {
        echo json_encode(['success' => false, 'error' => 'Sem permissão para criar cards']);
        return;
    }
    
    $boards_id = (int)$_POST['boards_id'];
    
    // Verificar permissão de edição no quadro
    if (!PluginScrumbanBoard::canUserEditBoard(Session::getLoginUserID(), $boards_id)) {
        echo json_encode(['success' => false, 'error' => 'Sem permissão para criar cards neste quadro']);
        return;
    }
    
    // Validar dados obrigatórios
    if (empty($_POST['name'])) {
        echo json_encode(['success' => false, 'error' => 'Nome é obrigatório']);
        return;
    }
    
    // Preparar dados do card
    $data = [
        'boards_id' => $boards_id,
        'name' => $_POST['name'],
        'description' => $_POST['description'] ?? '',
        'type' => $_POST['type'] ?? 'task',
        'priority' => $_POST['priority'] ?? 'NORMAL',
        'status' => $_POST['status'] ?? 'backlog',
        'story_points' => !empty($_POST['story_points']) ? (int)$_POST['story_points'] : null,
        'users_id_assigned' => !empty($_POST['users_id_assigned']) ? (int)$_POST['users_id_assigned'] : null,
        'users_id_requester' => !empty($_POST['users_id_requester']) ? (int)$_POST['users_id_requester'] : null
    ];
    
    // Criar card
    $card = new PluginScrumbanCard();
    if ($card->add($data)) {
        echo json_encode(['success' => true, 'card_id' => $card->fields['id']]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao criar card']);
    }
}