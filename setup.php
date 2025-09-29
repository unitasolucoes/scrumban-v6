<?php

define('PLUGIN_SCRUMBAN_VERSION', '2.0.0');

/**
 * Init hooks of the plugin.
 * REQUIRED
 */
function plugin_init_scrumban() {
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['scrumban'] = true;
    
    // Registrar classes
    Plugin::registerClass('PluginScrumbanProfile', [
        'addtabon' => 'Profile'
    ]);
    
    Plugin::registerClass('PluginScrumbanTeam', [
        'addtabon' => 'User'
    ]);
    
    Plugin::registerClass('PluginScrumbanBoard');
    Plugin::registerClass('PluginScrumbanCard');
    Plugin::registerClass('PluginScrumbanSprint');
    Plugin::registerClass('PluginScrumbanTeamMember');
    Plugin::registerClass('PluginScrumbanTeamBoard');

    if (Session::getLoginUserID()) {
        
        // Registrar item no menu TOOLS
        $PLUGIN_HOOKS['menu_toadd']['scrumban'] = ['tools' => 'PluginScrumbanMenu'];
        
        // Adicionar CSS e JS
        $PLUGIN_HOOKS['add_css']['scrumban'] = 'css/scrumban.css';
        $PLUGIN_HOOKS['add_javascript']['scrumban'] = 'js/scrumban.js';
        
        // Config page (apenas para admins)
        if (Session::haveRight('config', UPDATE)) {
            $PLUGIN_HOOKS['config_page']['scrumban'] = 'front/config.php';
        }
    }
}

/**
 * Get the name and the version of the plugin
 * REQUIRED
 */
function plugin_version_scrumban() {
    return [
        'name'           => 'Scrumban',
        'version'        => PLUGIN_SCRUMBAN_VERSION,
        'author'         => 'Unitá Soluções Digitais',
        'license'        => 'GPLv2+',
        'homepage'       => '',
        'requirements'   => [
            'glpi' => [
                'min' => '10.0',
                'max' => '10.1'
            ]
        ]
    ];
}

/**
 * Check pre-requisites before install
 * OPTIONNAL, but recommanded
 */
function plugin_scrumban_check_prerequisites() {
    if (version_compare(GLPI_VERSION, '10.0', '<')) {
        echo "Este plugin requer GLPI >= 10.0";
        return false;
    }
    return true;
}

/**
 * Check configuration process
 */
function plugin_scrumban_check_config($verbose = false) {
    return true;
}

/**
 * Install process for plugin : need to return true if succeeded
 */
function plugin_scrumban_install() {
    $migration = new Migration(PLUGIN_SCRUMBAN_VERSION);
    
    // Instalar tabelas do banco de dados
    include_once(GLPI_ROOT . "/plugins/scrumban/hook.php");
    plugin_scrumban_install_database($migration);
    
    // Instalar direitos de perfil
    include_once(GLPI_ROOT . "/plugins/scrumban/inc/profile.class.php");
    PluginScrumbanProfile::install($migration);
    
    return true;
}

/**
 * Uninstall process for plugin. Should return true if succeeded
 */
function plugin_scrumban_uninstall() {
    global $DB;
    
    // Remover tabelas na ordem correta (respeitando foreign keys)
    $tables = [
        'glpi_plugin_scrumban_history',
        'glpi_plugin_scrumban_comments',
        'glpi_plugin_scrumban_cards',
        'glpi_plugin_scrumban_sprints',
        'glpi_plugin_scrumban_team_boards',
        'glpi_plugin_scrumban_team_members',
        'glpi_plugin_scrumban_boards',
        'glpi_plugin_scrumban_teams',
        'glpi_plugin_scrumban_profiles'
    ];
    
    foreach ($tables as $table) {
        if ($DB->tableExists($table)) {
            $DB->query("DROP TABLE `$table`");
        }
    }
    
    // Desinstalar direitos de perfil
    include_once(GLPI_ROOT . "/plugins/scrumban/inc/profile.class.php");
    PluginScrumbanProfile::uninstall();
    
    // Limpar cache de menu
    if (class_exists('PluginScrumbanMenu')) {
        PluginScrumbanMenu::removeRightsFromSession();
    }
    
    return true;
}