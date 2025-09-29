<?php

class PluginScrumbanProfile extends CommonDBTM {
    
    static $rightname = 'profile';
    
    static function getTypeName($nb = 0) {
        return __('Perfil Scrumban', 'scrumban');
    }
    
    /**
     * Instalar direitos iniciais
     */
    static function install(Migration $migration) {
        global $DB;
        
        $rights = [
            ['name' => 'scrumban', 'label' => 'Scrumban Geral'],
            ['name' => 'scrumban_team', 'label' => 'Equipes Scrumban'],
            ['name' => 'scrumban_board', 'label' => 'Quadros Scrumban'],
            ['name' => 'scrumban_card', 'label' => 'Cards Scrumban']
        ];
        
        // Registrar os direitos
        foreach ($rights as $right) {
            ProfileRight::addProfileRights([$right['name']]);
        }
        
        // Dar todos os direitos ao Super-Admin (ID 4)
        foreach ($rights as $right) {
            $iterator = $DB->request([
                'FROM' => 'glpi_profilerights',
                'WHERE' => [
                    'profiles_id' => 4,
                    'name' => $right['name']
                ]
            ]);
            
            if (count($iterator) == 0) {
                $DB->insert('glpi_profilerights', [
                    'profiles_id' => 4,
                    'name' => $right['name'],
                    'rights' => ALLSTANDARDRIGHT
                ]);
            } else {
                $DB->update('glpi_profilerights', [
                    'rights' => ALLSTANDARDRIGHT
                ], [
                    'profiles_id' => 4,
                    'name' => $right['name']
                ]);
            }
        }
        
        return true;
    }
    
    /**
     * Desinstalar direitos
     */
    static function uninstall() {
        global $DB;
        
        $rights = ['scrumban', 'scrumban_team', 'scrumban_board', 'scrumban_card'];
        
        foreach ($rights as $right) {
            $DB->delete('glpi_profilerights', ['name' => $right]);
        }
        
        return true;
    }
    
    /**
     * Adicionar aba no perfil
     */
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if ($item->getType() == 'Profile') {
            return __('Scrumban', 'scrumban');
        }
        return '';
    }
    
    /**
     * Exibir conteúdo da aba
     */
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if ($item->getType() == 'Profile') {
            self::showForProfile($item->getID());
            return true;
        }
        return false;
    }
    
    /**
     * Mostrar formulário de direitos
     */
    static function showForProfile($profiles_id) {
        global $DB;
        
        $profile = new Profile();
        if (!$profile->getFromDB($profiles_id)) {
            return false;
        }
        
        $canedit = Session::haveRight('profile', UPDATE);
        
        echo "<div class='spaced'>";
        
        if ($canedit) {
            echo "<form method='post' action='" . Plugin::getWebDir('scrumban') . "/front/profile.form.php'>";
        }
        
        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr class='tab_bg_1'>";
        echo "<th colspan='5'>" . __('Direitos Scrumban', 'scrumban') . "</th>";
        echo "</tr>";
        
        echo "<tr class='tab_bg_2'>";
        echo "<th>" . __('Módulo', 'scrumban') . "</th>";
        echo "<th>" . __('Nenhum', 'scrumban') . "</th>";
        echo "<th>" . __('Leitura', 'scrumban') . "</th>";
        echo "<th>" . __('Leitura-Escrita', 'scrumban') . "</th>";
        echo "</tr>";
        
        $rights = [
            ['name' => 'scrumban', 'label' => __('Scrumban Geral', 'scrumban')],
            ['name' => 'scrumban_team', 'label' => __('Equipes', 'scrumban')],
            ['name' => 'scrumban_board', 'label' => __('Quadros', 'scrumban')],
            ['name' => 'scrumban_card', 'label' => __('Cards', 'scrumban')]
        ];
        
        foreach ($rights as $right) {
            // Buscar valor atual
            $current_value = 0;
            $iterator = $DB->request([
                'FROM' => 'glpi_profilerights',
                'WHERE' => [
                    'profiles_id' => $profiles_id,
                    'name' => $right['name']
                ]
            ]);
            
            if (count($iterator) > 0) {
                $data = $iterator->current();
                $current_value = $data['rights'];
            }
            
            echo "<tr class='tab_bg_2'>";
            echo "<td>" . $right['label'] . "</td>";
            
            if ($canedit) {
                // Nenhum
                echo "<td class='center'>";
                echo "<input type='radio' name='" . $right['name'] . "' value='0'" . ($current_value == 0 ? ' checked' : '') . ">";
                echo "</td>";
                
                // Leitura
                echo "<td class='center'>";
                echo "<input type='radio' name='" . $right['name'] . "' value='" . READ . "'" . ($current_value == READ ? ' checked' : '') . ">";
                echo "</td>";
                
                // Leitura-Escrita
                $rw_value = READ | CREATE | UPDATE | DELETE | PURGE;
                echo "<td class='center'>";
                echo "<input type='radio' name='" . $right['name'] . "' value='" . $rw_value . "'" . ($current_value >= $rw_value ? ' checked' : '') . ">";
                echo "</td>";
            } else {
                echo "<td class='center'>" . ($current_value == 0 ? 'X' : '') . "</td>";
                echo "<td class='center'>" . ($current_value == READ ? 'X' : '') . "</td>";
                echo "<td class='center'>" . ($current_value >= $rw_value ? 'X' : '') . "</td>";
            }
            
            echo "</tr>";
        }
        
        if ($canedit) {
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='5' class='center'>";
            echo "<input type='hidden' name='profiles_id' value='" . $profiles_id . "'>";
            echo "<input type='submit' name='update' value=\"" . _sx('button', 'Save') . "\" class='btn btn-primary'>";
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        if ($canedit) {
            Html::closeForm();
        }
        
        echo "</div>";
        
        return true;
    }
}