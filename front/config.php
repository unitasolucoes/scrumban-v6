<?php

include ('../../../inc/includes.php');

Session::checkRight('config', UPDATE);

Html::header(
    __('Configuração Scrumban', 'scrumban'), 
    $_SERVER['PHP_SELF'], 
    "config", 
    "plugins"
);

echo "<div class='container-fluid'>";
echo "<div class='row'>";
echo "<div class='col-md-12'>";

echo "<div class='card mt-4'>";
echo "<div class='card-header'>";
echo "<h3><i class='fas fa-cog'></i> " . __('Configuração do Plugin Scrumban', 'scrumban') . "</h3>";
echo "</div>";
echo "<div class='card-body'>";

echo "<h4>Informações do Plugin</h4>";
echo "<table class='table table-striped'>";
echo "<tr><td><strong>Versão:</strong></td><td>" . PLUGIN_SCRUMBAN_VERSION . "</td></tr>";
echo "<tr><td><strong>Autor:</strong></td><td>Unitá Soluções Digitais</td></tr>";
echo "<tr><td><strong>Status:</strong></td><td><span class='badge badge-success'>Ativo</span></td></tr>";
echo "</table>";

echo "<hr>";

echo "<h4>Estatísticas</h4>";

global $DB;

// Contar equipes
$teams_result = $DB->request([
    'COUNT' => 'cpt',
    'FROM' => 'glpi_plugin_scrumban_teams',
    'WHERE' => ['is_active' => 1]
]);
$teams_count = $teams_result->current()['cpt'];

// Contar quadros
$boards_result = $DB->request([
    'COUNT' => 'cpt',
    'FROM' => 'glpi_plugin_scrumban_boards',
    'WHERE' => ['is_active' => 1]
]);
$boards_count = $boards_result->current()['cpt'];

// Contar cards
$cards_result = $DB->request([
    'COUNT' => 'cpt',
    'FROM' => 'glpi_plugin_scrumban_cards'
]);
$cards_count = $cards_result->current()['cpt'];

// Contar sprints
$sprints_result = $DB->request([
    'COUNT' => 'cpt',
    'FROM' => 'glpi_plugin_scrumban_sprints'
]);
$sprints_count = $sprints_result->current()['cpt'];

echo "<div class='row'>";
echo "<div class='col-md-3'>";
echo "<div class='card bg-primary text-white'>";
echo "<div class='card-body text-center'>";
echo "<h2>$teams_count</h2>";
echo "<p>Equipes Ativas</p>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "<div class='col-md-3'>";
echo "<div class='card bg-info text-white'>";
echo "<div class='card-body text-center'>";
echo "<h2>$boards_count</h2>";
echo "<p>Quadros Ativos</p>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "<div class='col-md-3'>";
echo "<div class='card bg-success text-white'>";
echo "<div class='card-body text-center'>";
echo "<h2>$cards_count</h2>";
echo "<p>Cards Criados</p>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "<div class='col-md-3'>";
echo "<div class='card bg-warning text-white'>";
echo "<div class='card-body text-center'>";
echo "<h2>$sprints_count</h2>";
echo "<p>Sprints</p>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "<hr>";

echo "<h4>Ações</h4>";
echo "<div class='btn-group' role='group'>";
echo "<a href='" . Plugin::getWebDir('scrumban') . "/front/dashboard.php' class='btn btn-primary'>";
echo "<i class='fas fa-tachometer-alt'></i> Ir para Dashboard";
echo "</a>";
echo "<a href='" . Plugin::getWebDir('scrumban') . "/front/team.php' class='btn btn-secondary'>";
echo "<i class='fas fa-users'></i> Gerenciar Equipes";
echo "</a>";
echo "<a href='" . Plugin::getWebDir('scrumban') . "/front/board.php' class='btn btn-secondary'>";
echo "<i class='fas fa-columns'></i> Gerenciar Quadros";
echo "</a>";
echo "</div>";

echo "</div>";
echo "</div>";

echo "</div>";
echo "</div>";
echo "</div>";

Html::footer();