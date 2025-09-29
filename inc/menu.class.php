<?php

class PluginScrumbanMenu extends CommonGLPI {
    
    static $rightname = 'scrumban_team';
    
    static function getMenuName() {
        return __('Scrumban', 'scrumban');
    }
    
    static function getMenuContent() {
        global $CFG_GLPI;
        
        $menu = [];
        
        if (Session::haveRight('scrumban_team', READ)) {
            $menu['title'] = self::getMenuName();
            $menu['page'] = Plugin::getWebDir('scrumban') . '/front/dashboard.php';
            $menu['icon'] = 'fas fa-columns';
            
            $menu['options']['dashboard'] = [
                'title' => __('Dashboard', 'scrumban'),
                'page' => Plugin::getWebDir('scrumban') . '/front/dashboard.php',
                'icon' => 'fas fa-tachometer-alt',
                'links' => [
                    'search' => Plugin::getWebDir('scrumban') . '/front/dashboard.php'
                ]
            ];
            
            $menu['options']['teams'] = [
                'title' => __('Equipes', 'scrumban'),
                'page' => Plugin::getWebDir('scrumban') . '/front/team.php',
                'icon' => 'fas fa-users',
                'links' => [
                    'search' => Plugin::getWebDir('scrumban') . '/front/team.php',
                    'add' => Plugin::getWebDir('scrumban') . '/front/team.form.php'
                ]
            ];
            
            $menu['options']['boards'] = [
                'title' => __('Quadros', 'scrumban'),
                'page' => Plugin::getWebDir('scrumban') . '/front/board.php',
                'icon' => 'fas fa-columns',
                'links' => [
                    'search' => Plugin::getWebDir('scrumban') . '/front/board.php',
                    'add' => Plugin::getWebDir('scrumban') . '/front/board.form.php'
                ]
            ];
            
            $menu['options']['cards'] = [
                'title' => __('Cards', 'scrumban'),
                'page' => PluginScrumbanCard::getSearchURL(false),
                'icon' => 'fas fa-sticky-note',
                'links' => [
                    'search' => PluginScrumbanCard::getSearchURL(false),
                    'add' => PluginScrumbanCard::getFormURL(false)
                ]
            ];
            
            $menu['options']['sprints'] = [
                'title' => __('Sprints', 'scrumban'),
                'page' => Plugin::getWebDir('scrumban') . '/front/sprint.php',
                'icon' => 'fas fa-calendar-alt',
                'links' => [
                    'search' => Plugin::getWebDir('scrumban') . '/front/sprint.php',
                    'add' => PluginScrumbanSprint::getFormURL(false)
                ]
            ];
        }
        
        if (count($menu)) {
            return $menu;
        }
        
        return false;
    }
    
    static function removeRightsFromSession() {
        if (isset($_SESSION['glpimenu']['tools']['types']['PluginScrumbanMenu'])) {
            unset($_SESSION['glpimenu']['tools']['types']['PluginScrumbanMenu']);
        }
        if (isset($_SESSION['glpimenu']['tools']['content']['pluginscrumbanmenu'])) {
            unset($_SESSION['glpimenu']['tools']['content']['pluginscrumbanmenu']);
        }
    }
}