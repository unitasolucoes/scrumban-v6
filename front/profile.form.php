<?php

include ('../../../inc/includes.php');

Session::checkRight('profile', UPDATE);

if (isset($_POST['update'])) {
    global $DB;
    
    $profiles_id = (int)$_POST['profiles_id'];
    
    if ($profiles_id <= 0) {
        Session::addMessageAfterRedirect(__('ID do perfil inválido'), false, ERROR);
        Html::back();
        exit;
    }
    
    $rights = ['scrumban', 'scrumban_team', 'scrumban_board', 'scrumban_card'];
    
    foreach ($rights as $right) {
        $value = isset($_POST[$right]) ? (int)$_POST[$right] : 0;
        
        // Verificar se já existe
        $iterator = $DB->request([
            'FROM' => 'glpi_profilerights',
            'WHERE' => [
                'profiles_id' => $profiles_id,
                'name' => $right
            ]
        ]);
        
        if (count($iterator) > 0) {
            // Atualizar
            $DB->update('glpi_profilerights', [
                'rights' => $value
            ], [
                'profiles_id' => $profiles_id,
                'name' => $right
            ]);
        } else {
            // Inserir
            $DB->insert('glpi_profilerights', [
                'profiles_id' => $profiles_id,
                'name' => $right,
                'rights' => $value
            ]);
        }
    }
    
    Session::addMessageAfterRedirect(__('Direitos Scrumban atualizados com sucesso'), true, INFO);
    Html::back();
    
} else {
    Html::displayErrorAndDie(__('Acesso direto não permitido'));
}