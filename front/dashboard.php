<?php

include '../../../inc/includes.php';

Session::checkRight('scrumban_team', READ);

global $DB;
$user_id = Session::getLoginUserID();

// Buscar estatísticas
$stats = [
    'teams' => 0,
    'boards' => 0,
    'cards' => 0,
    'sprints' => 0
];

// Minhas equipes
$result = $DB->request(['COUNT' => 'cpt', 'FROM' => 'glpi_plugin_scrumban_team_members', 'WHERE' => ['users_id' => $user_id]]);
$stats['teams'] = $result->current()['cpt'];

// Meus quadros (através das equipes)
$result = $DB->request([
    'COUNT' => 'cpt',
    'FROM' => 'glpi_plugin_scrumban_team_boards AS tb',
    'INNER JOIN' => [
        'glpi_plugin_scrumban_team_members AS tm' => [
            'ON' => ['tm' => 'teams_id', 'tb' => 'teams_id']
        ]
    ],
    'WHERE' => ['tm.users_id' => $user_id]
]);
$stats['boards'] = $result->current()['cpt'];

// Meus cards
$result = $DB->request(['COUNT' => 'cpt', 'FROM' => 'glpi_plugin_scrumban_cards', 'WHERE' => ['users_id_assigned' => $user_id]]);
$stats['cards'] = $result->current()['cpt'];

// Sprints ativos
$result = $DB->request(['COUNT' => 'cpt', 'FROM' => 'glpi_plugin_scrumban_sprints', 'WHERE' => ['is_active' => 1]]);
$stats['sprints'] = $result->current()['cpt'];

// Cards por status
$cards_by_status = [
    'backlog' => 0,
    'todo' => 0,
    'em-execucao' => 0,
    'review' => 0,
    'done' => 0
];

$iterator = $DB->request([
    'SELECT' => ['status', new \QueryExpression('COUNT(*) as count')],
    'FROM' => 'glpi_plugin_scrumban_cards',
    'WHERE' => ['users_id_assigned' => $user_id],
    'GROUPBY' => 'status'
]);

foreach ($iterator as $data) {
    $cards_by_status[$data['status']] = $data['count'];
}

Html::header('Dashboard Scrumban', $_SERVER['PHP_SELF'], "tools", "pluginscrumbanmenu", "dashboard");
?>

<style>
.stat-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
}
.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}
.stat-icon {
    font-size: 3rem;
    opacity: 0.8;
}
.action-btn {
    border-radius: 8px;
    padding: 12px 24px;
    font-weight: 500;
    transition: all 0.2s;
    border: none;
}
.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
.status-card {
    border-left: 4px solid;
    border-radius: 8px;
    transition: all 0.2s;
}
.status-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
</style>

<div class="container-fluid mt-4">

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0"><i class="fas fa-tachometer-alt text-primary"></i> Dashboard Scrumban</h2>
            <p class="text-muted">Visão geral das suas atividades</p>
        </div>
    </div>

    <!-- Cards de Estatísticas -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stat-card bg-primary text-white">
                <div class="card-body text-center">
                    <i class="fas fa-users stat-icon mb-3"></i>
                    <h2 class="mb-0"><?php echo $stats['teams']; ?></h2>
                    <p class="mb-0">Equipes</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card stat-card bg-info text-white">
                <div class="card-body text-center">
                    <i class="fas fa-columns stat-icon mb-3"></i>
                    <h2 class="mb-0"><?php echo $stats['boards']; ?></h2>
                    <p class="mb-0">Quadros</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card stat-card bg-success text-white">
                <div class="card-body text-center">
                    <i class="fas fa-sticky-note stat-icon mb-3"></i>
                    <h2 class="mb-0"><?php echo $stats['cards']; ?></h2>
                    <p class="mb-0">Meus Cards</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card stat-card bg-warning text-white">
                <div class="card-body text-center">
                    <i class="fas fa-calendar-alt stat-icon mb-3"></i>
                    <h2 class="mb-0"><?php echo $stats['sprints']; ?></h2>
                    <p class="mb-0">Sprints Ativos</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Status dos Cards -->
    <?php if ($stats['cards'] > 0): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Distribuição dos Meus Cards</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md mb-3">
                            <div class="status-card card" style="border-left-color: #6c757d;">
                                <div class="card-body text-center">
                                    <h3 class="text-secondary mb-1"><?php echo $cards_by_status['backlog']; ?></h3>
                                    <small class="text-muted">Backlog</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md mb-3">
                            <div class="status-card card" style="border-left-color: #17a2b8;">
                                <div class="card-body text-center">
                                    <h3 class="text-info mb-1"><?php echo $cards_by_status['todo']; ?></h3>
                                    <small class="text-muted">A Fazer</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md mb-3">
                            <div class="status-card card" style="border-left-color: #ffc107;">
                                <div class="card-body text-center">
                                    <h3 class="text-warning mb-1"><?php echo $cards_by_status['em-execucao']; ?></h3>
                                    <small class="text-muted">Em Execução</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md mb-3">
                            <div class="status-card card" style="border-left-color: #007bff;">
                                <div class="card-body text-center">
                                    <h3 class="text-primary mb-1"><?php echo $cards_by_status['review']; ?></h3>
                                    <small class="text-muted">Review</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md mb-3">
                            <div class="status-card card" style="border-left-color: #28a745;">
                                <div class="card-body text-center">
                                    <h3 class="text-success mb-1"><?php echo $cards_by_status['done']; ?></h3>
                                    <small class="text-muted">Concluído</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Ações Rápidas -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-bolt"></i> Ações Rápidas</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        
                        <a href="team.php" class="btn btn-primary action-btn">
                            <i class="fas fa-users"></i> Gerenciar Equipes
                        </a>
                        
                        <a href="board.php" class="btn btn-info action-btn text-white">
                            <i class="fas fa-columns"></i> Ver Quadros
                        </a>
                        
                        <a href="card.form.php" class="btn btn-success action-btn">
                            <i class="fas fa-plus"></i> Novo Card
                        </a>
                        
                        <a href="sprint.form.php" class="btn btn-warning action-btn text-white">
                            <i class="fas fa-calendar-plus"></i> Nova Sprint
                        </a>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php Html::footer(); ?>