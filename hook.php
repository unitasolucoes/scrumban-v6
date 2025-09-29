<?php

function plugin_scrumban_install_database($migration) {
    global $DB;
    
    // Tabela de Equipes
    $table = 'glpi_plugin_scrumban_teams';
    if (!$DB->tableExists($table)) {
        $migration->displayMessage("Criando tabela $table");
        
        $query = "CREATE TABLE `$table` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `description` text,
            `manager_id` int(11) unsigned DEFAULT NULL,
            `is_active` tinyint(1) DEFAULT 1,
            `entities_id` int(11) unsigned DEFAULT NULL,
            `is_recursive` tinyint(1) DEFAULT 0,
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `manager_id` (`manager_id`),
            KEY `entities_id` (`entities_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $DB->query($query) or die("Erro ao criar tabela $table: " . $DB->error());
    }
    
    // Tabela de Membros da Equipe
    $table = 'glpi_plugin_scrumban_team_members';
    if (!$DB->tableExists($table)) {
        $migration->displayMessage("Criando tabela $table");
        
        $query = "CREATE TABLE `$table` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `teams_id` int(11) unsigned NOT NULL,
            `users_id` int(11) unsigned NOT NULL,
            `role` enum('member','lead','admin') DEFAULT 'member',
            `date_creation` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_team_user` (`teams_id`, `users_id`),
            KEY `teams_id` (`teams_id`),
            KEY `users_id` (`users_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $DB->query($query) or die("Erro ao criar tabela $table: " . $DB->error());
    }
    
    // Tabela de Quadros
    $table = 'glpi_plugin_scrumban_boards';
    if (!$DB->tableExists($table)) {
        $migration->displayMessage("Criando tabela $table");
        
        $query = "CREATE TABLE `$table` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `description` text,
            `teams_id` int(11) unsigned DEFAULT NULL,
            `visibility` enum('public','team','private') DEFAULT 'team',
            `users_id_created` int(11) unsigned DEFAULT NULL,
            `entities_id` int(11) unsigned DEFAULT NULL,
            `is_recursive` tinyint(1) DEFAULT 0,
            `is_active` tinyint(1) DEFAULT 1,
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `teams_id` (`teams_id`),
            KEY `users_id_created` (`users_id_created`),
            KEY `entities_id` (`entities_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $DB->query($query) or die("Erro ao criar tabela $table: " . $DB->error());
    }
    
    // Tabela de Associação Equipe-Quadro
    $table = 'glpi_plugin_scrumban_team_boards';
    if (!$DB->tableExists($table)) {
        $migration->displayMessage("Criando tabela $table");
        
        $query = "CREATE TABLE `$table` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `teams_id` int(11) unsigned NOT NULL,
            `boards_id` int(11) unsigned NOT NULL,
            `can_edit` tinyint(1) DEFAULT 1,
            `can_manage` tinyint(1) DEFAULT 0,
            `date_creation` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_team_board` (`teams_id`, `boards_id`),
            KEY `teams_id` (`teams_id`),
            KEY `boards_id` (`boards_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $DB->query($query) or die("Erro ao criar tabela $table: " . $DB->error());
    }
    
    // Tabela de Cards
    $table = 'glpi_plugin_scrumban_cards';
    if (!$DB->tableExists($table)) {
        $migration->displayMessage("Criando tabela $table");
        
        $query = "CREATE TABLE `$table` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `boards_id` int(11) unsigned NOT NULL,
            `name` varchar(255) NOT NULL,
            `description` text,
            `status` enum('backlog','todo','em-execucao','review','done') DEFAULT 'backlog',
            `type` enum('feature','bug','task','story') DEFAULT 'task',
            `priority` enum('LOW','NORMAL','HIGH','CRITICAL') DEFAULT 'NORMAL',
            `story_points` int(11) unsigned DEFAULT NULL,
            `users_id_assigned` int(11) unsigned DEFAULT NULL,
            `users_id_requester` int(11) unsigned DEFAULT NULL,
            `users_id_created` int(11) unsigned DEFAULT NULL,
            `date_planned` timestamp NULL DEFAULT NULL,
            `date_completion` timestamp NULL DEFAULT NULL,
            `sprint_id` int(11) unsigned DEFAULT NULL,
            `labels` text,
            `acceptance_criteria` text,
            `test_scenarios` text,
            `branch` varchar(255) DEFAULT NULL,
            `pull_request` varchar(255) DEFAULT NULL,
            `commits` text,
            `dor_percentage` int(11) unsigned DEFAULT 0,
            `dod_percentage` int(11) unsigned DEFAULT 0,
            `entities_id` int(11) unsigned DEFAULT NULL,
            `is_recursive` tinyint(1) DEFAULT 0,
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `boards_id` (`boards_id`),
            KEY `users_id_assigned` (`users_id_assigned`),
            KEY `users_id_requester` (`users_id_requester`),
            KEY `users_id_created` (`users_id_created`),
            KEY `sprint_id` (`sprint_id`),
            KEY `entities_id` (`entities_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $DB->query($query) or die("Erro ao criar tabela $table: " . $DB->error());
    }
    
    // Tabela de Sprints
    $table = 'glpi_plugin_scrumban_sprints';
    if (!$DB->tableExists($table)) {
        $migration->displayMessage("Criando tabela $table");
        
        $query = "CREATE TABLE `$table` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `boards_id` int(11) unsigned NOT NULL,
            `name` varchar(255) NOT NULL,
            `description` text,
            `date_start` timestamp NULL DEFAULT NULL,
            `date_end` timestamp NULL DEFAULT NULL,
            `is_active` tinyint(1) DEFAULT 0,
            `entities_id` int(11) unsigned DEFAULT NULL,
            `is_recursive` tinyint(1) DEFAULT 0,
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `boards_id` (`boards_id`),
            KEY `entities_id` (`entities_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $DB->query($query) or die("Erro ao criar tabela $table: " . $DB->error());
    }
    
    // Tabela de Perfis
    $table = 'glpi_plugin_scrumban_profiles';
    if (!$DB->tableExists($table)) {
        $migration->displayMessage("Criando tabela $table");
        
        $query = "CREATE TABLE `$table` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `profiles_id` int(11) unsigned NOT NULL,
            `scrumban` char(1) DEFAULT NULL,
            `scrumban_team` char(1) DEFAULT NULL,
            `scrumban_board` char(1) DEFAULT NULL,
            `scrumban_card` char(1) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unicity` (`profiles_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $DB->query($query) or die("Erro ao criar tabela $table: " . $DB->error());
    }
    
    // Tabela de Comentários dos Cards
    $table = 'glpi_plugin_scrumban_comments';
    if (!$DB->tableExists($table)) {
        $migration->displayMessage("Criando tabela $table");
        
        $query = "CREATE TABLE `$table` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `cards_id` int(11) unsigned NOT NULL,
            `users_id` int(11) unsigned NOT NULL,
            `comment` text NOT NULL,
            `date_creation` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `cards_id` (`cards_id`),
            KEY `users_id` (`users_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $DB->query($query) or die("Erro ao criar tabela $table: " . $DB->error());
    }
    
    // Tabela de Histórico dos Cards
    $table = 'glpi_plugin_scrumban_history';
    if (!$DB->tableExists($table)) {
        $migration->displayMessage("Criando tabela $table");
        
        $query = "CREATE TABLE `$table` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `cards_id` int(11) unsigned NOT NULL,
            `users_id` int(11) unsigned NOT NULL,
            `field` varchar(255) NOT NULL,
            `old_value` text,
            `new_value` text,
            `action` varchar(50) NOT NULL,
            `date_creation` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `cards_id` (`cards_id`),
            KEY `users_id` (`users_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $DB->query($query) or die("Erro ao criar tabela $table: " . $DB->error());
    }
    
    // Adicionar chaves estrangeiras (foreign keys) se necessário
    addForeignKeys($migration);
    
    $migration->executeMigration();
}

/**
 * Adicionar chaves estrangeiras para manter integridade referencial
 */
function addForeignKeys($migration) {
    global $DB;
    
    // Nota: O GLPI geralmente não usa FK explícitas, mas mantemos a estrutura
    // para compatibilidade futura se necessário
    
    $migration->displayMessage("Verificando integridade referencial");
    
    // Verificar se todas as tabelas foram criadas corretamente
    $tables = [
        'glpi_plugin_scrumban_teams',
        'glpi_plugin_scrumban_team_members', 
        'glpi_plugin_scrumban_boards',
        'glpi_plugin_scrumban_team_boards',
        'glpi_plugin_scrumban_cards',
        'glpi_plugin_scrumban_sprints',
        'glpi_plugin_scrumban_comments',
        'glpi_plugin_scrumban_history'
    ];
    
    foreach ($tables as $table) {
        if (!$DB->tableExists($table)) {
            die("Erro: Tabela $table não foi criada corretamente");
        }
    }
    
    $migration->displayMessage("Todas as tabelas foram criadas com sucesso");
}