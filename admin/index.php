<?php
/**
 * Back-office Expertise – Tableau de bord
 */
$pageTitle = 'Administration – Expertise';
$currentNav = 'dashboard';

require_once __DIR__ . '/inc/auth.php'; // Protection avant tout
require_permission('admin.dashboard');
require __DIR__ . '/inc/db.php';

$stats = [
    'organisations' => 0,
    'missions' => 0,
    'announcements' => 0,
    'users' => 0,
    'projects' => 0,
    'tasks' => 0,
    'staff' => 0,
    'documents' => 0,
];
$recentMissions = [];
$recentAnnouncements = [];
$activeProjects = [];

if ($pdo) {
    try {
        $stats['organisations'] = (int) $pdo->query("SELECT COUNT(*) FROM organisation")->fetchColumn();
        $stats['missions'] = (int) $pdo->query("SELECT COUNT(*) FROM mission")->fetchColumn();
        $stats['announcements'] = (int) $pdo->query("SELECT COUNT(*) FROM announcement")->fetchColumn();
        $stats['users'] = (int) $pdo->query("SELECT COUNT(*) FROM user")->fetchColumn();
        $stats['projects'] = (int) $pdo->query("SELECT COUNT(*) FROM project")->fetchColumn();
        $stats['tasks'] = (int) $pdo->query("SELECT COUNT(*) FROM task WHERE status IN ('todo', 'in_progress')")->fetchColumn();
        $stats['staff'] = (int) $pdo->query("SELECT COUNT(*) FROM staff WHERE is_active = 1")->fetchColumn();
        $stats['documents'] = (int) $pdo->query("SELECT COUNT(*) FROM document")->fetchColumn();

        $stmt = $pdo->query("
            SELECT id, title, location, start_date, updated_at
            FROM mission
            ORDER BY updated_at DESC, start_date DESC
            LIMIT 5
        ");
        if ($stmt)
            $recentMissions = $stmt->fetchAll();

        $stmt = $pdo->query("
            SELECT id, title, published_at, created_at
            FROM announcement
            ORDER BY COALESCE(published_at, created_at) DESC, created_at DESC
            LIMIT 5
        ");
        if ($stmt)
            $recentAnnouncements = $stmt->fetchAll();

        $stmt = $pdo->query("
            SELECT id, name, status, start_date, priority
            FROM project
            ORDER BY created_at DESC
            LIMIT 4
        ");
        if ($stmt)
            $activeProjects = $stmt->fetchAll();

        // Données pour les graphiques
        $missionStatusStats = $pdo->query("
            SELECT ms.name as label, COUNT(m.id) as value 
            FROM mission_status ms 
            LEFT JOIN mission m ON m.mission_status_id = ms.id 
            GROUP BY ms.id
        ")->fetchAll();

        $projectStatusStats = $pdo->query("
            SELECT status as label, COUNT(*) as value 
            FROM project 
            GROUP BY status
        ")->fetchAll();
    } catch (PDOException $e) {
        // erreur silencieuse – les stats restent à 0
    }
}

require __DIR__ . '/inc/header.php';
?>

<style>
    .admin-stat-card {
        padding: 1.5rem;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
    }

    .admin-stat-card .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .admin-stat-card .stat-icon.organisations {
        background: rgba(29, 28, 62, 0.1);
        color: var(--admin-sidebar);
    }

    .admin-stat-card .stat-icon.missions {
        background: rgba(255, 193, 7, 0.1);
        color: var(--admin-accent);
    }

    .admin-stat-card .stat-icon.projects {
        background: rgba(25, 135, 84, 0.1);
        color: #198754;
    }

    .admin-stat-card .stat-icon.staff {
        background: rgba(253, 126, 20, 0.12);
        color: #fd7e14;
    }

    .admin-stat-card .stat-icon.documents {
        background: rgba(0, 113, 188, 0.1);
        color: #0071BC;
    }

    .admin-stat-card .stat-icon.users {
        background: rgba(111, 66, 193, 0.12);
        color: #6f42c1;
    }

    .admin-stat-card .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--admin-text);
        line-height: 1.2;
    }

    .admin-stat-card .stat-label {
        font-size: 0.85rem;
        color: var(--admin-muted);
        margin-top: 0.25rem;
    }

    .admin-section-card .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .admin-section-card .section-header .card-title {
        margin-bottom: 0;
    }

    .admin-section-card .list-group-item {
        border: none;
        border-bottom: 1px solid #eee;
        padding: 0.75rem 0;
        font-size: 0.9rem;
    }

    .admin-section-card .list-group-item:last-child {
        border-bottom: none;
    }

    .admin-section-card .list-group-item a {
        color: var(--admin-text);
        text-decoration: none;
        font-weight: 500;
    }

    .admin-section-card .list-group-item a:hover {
        color: var(--admin-accent);
    }

    .admin-section-card .list-group-item .badge {
        font-size: 0.7rem;
        font-weight: 600;
    }

    .chart-container {
        position: relative;
        height: 250px;
        width: 100%;
    }
</style>

<header class="admin-header">
    <h1>Tableau de bord</h1>
    <p>Vue d'ensemble de votre back-office Expertise.</p>
</header>

<section class="mb-4">
    <div class="row g-3">
        <!-- Rangée 1 -->
        <div class="col-sm-6 col-xl-3">
            <div class="admin-card admin-stat-card">
                <div>
                    <div class="stat-value"><?= $stats['organisations'] ?></div>
                    <div class="stat-label">Organisations</div>
                </div>
                <div class="stat-icon organisations"><i class="bi bi-building"></i></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="admin-card admin-stat-card">
                <div>
                    <div class="stat-value"><?= $stats['projects'] ?></div>
                    <div class="stat-label">Projets Actifs</div>
                </div>
                <div class="stat-icon projects"><i class="bi bi-kanban"></i></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="admin-card admin-stat-card">
                <div>
                    <div class="stat-value"><?= $stats['missions'] ?></div>
                    <div class="stat-label">Missions</div>
                </div>
                <div class="stat-icon missions"><i class="bi bi-geo-alt"></i></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="admin-card admin-stat-card">
                <div>
                    <div class="stat-value"><?= $stats['staff'] ?></div>
                    <div class="stat-label">Effectifs (Staff)</div>
                </div>
                <div class="stat-icon staff"><i class="bi bi-person-badge"></i></div>
            </div>
        </div>
        <!-- Rangée 2 -->
        <div class="col-sm-6 col-xl-3">
            <div class="admin-card admin-stat-card">
                <div>
                    <div class="stat-value"><?= $stats['tasks'] ?></div>
                    <div class="stat-label">Tâches en cours</div>
                </div>
                <div class="stat-icon"><i class="bi bi-check2-square text-info"></i></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="admin-card admin-stat-card">
                <div>
                    <div class="stat-value"><?= $stats['documents'] ?></div>
                    <div class="stat-label">Fichiers & docs</div>
                </div>
                <div class="stat-icon documents"><i class="bi bi-file-earmark-text"></i></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="admin-card admin-stat-card">
                <div>
                    <div class="stat-value"><?= $stats['announcements'] ?></div>
                    <div class="stat-label">Annonces</div>
                </div>
                <div class="stat-icon announcements"><i class="bi bi-megaphone"></i></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="admin-card admin-stat-card">
                <div>
                    <div class="stat-value"><?= $stats['users'] ?></div>
                    <div class="stat-label">Utilisateurs App</div>
                </div>
                <div class="stat-icon users"><i class="bi bi-people"></i></div>
            </div>
        </div>
    </div>
</section>

<!-- Graphiques Statistiques -->
<section class="mb-4">
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="admin-card admin-section-card">
                <h2 class="card-title"><i class="bi bi-pie-chart"></i> Répartition des Missions</h2>
                <div class="chart-container">
                    <canvas id="missionChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="admin-card admin-section-card">
                <h2 class="card-title"><i class="bi bi-bar-chart"></i> État des Projets</h2>
                <div class="chart-container">
                    <canvas id="projectChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="admin-card admin-section-card h-100">
            <div class="section-header">
                <h2 class="card-title"><i class="bi bi-kanban"></i> Projets récents</h2>
                <a href="#" class="btn btn-sm btn-admin-outline">Tout voir</a>
            </div>
            <div class="row g-3">
                <?php if (count($activeProjects) > 0): ?>
                    <?php foreach ($activeProjects as $p): ?>
                        <div class="col-md-6">
                            <div class="p-3 border rounded-3 h-100 bg-light-subtle">
                                <div class="d-flex justify-content-between mb-2">
                                    <span
                                        class="badge text-bg-<?= $p->priority === 'critical' ? 'danger' : ($p->priority === 'high' ? 'warning' : 'info') ?>"><?= strtoupper($p->priority) ?></span>
                                    <span class="small text-muted"><?= date('d/m/Y', strtotime($p->start_date)) ?></span>
                                </div>
                                <h3 class="h6 mb-1 text-truncate"><?= htmlspecialchars($p->name) ?></h3>
                                <p class="small text-muted mb-2">Statut: <span
                                        class="fw-medium text-dark"><?= ucfirst(str_replace('_', ' ', $p->status)) ?></span></p>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" style="width: 35%"></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-4 text-muted">Aun projet en cours.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="admin-card admin-section-card h-100">
            <h2 class="card-title"><i class="bi bi-lightning"></i> Actions rapides</h2>
            <div class="d-grid gap-2">
                <a href="missions.php" class="btn btn-admin-primary text-start"><i class="bi bi-plus-circle me-2"></i>
                    Nouvelle mission</a>
                <a href="#" class="btn btn-admin-outline text-start"><i class="bi bi-plus-circle me-2"></i> Nouveau
                    projet</a>
                <a href="announcements.php" class="btn btn-admin-outline text-start"><i
                        class="bi bi-megaphone me-2"></i> Publier une annonce</a>
                <a href="#" class="btn btn-admin-outline text-start"><i class="bi bi-person-plus me-2"></i> Recruter un
                    membre</a>
            </div>

            <hr class="my-4">

            <h2 class="card-title h6"><i class="bi bi-link-45deg"></i> Raccourcis</h2>
            <ul class="list-group list-group-flush small">
                <li class="list-group-item px-0 d-flex justify-content-between">
                    <a href="../">Site public</a>
                    <i class="bi bi-box-arrow-up-right"></i>
                </li>
                <li class="list-group-item px-0 d-flex justify-content-between">
                    <a href="#">Base documentaire</a>
                    <i class="bi bi-folder-symlink"></i>
                </li>
            </ul>
        </div>
    </div>
</div>

<div class="row g-4 mt-0">
    <div class="col-lg-6">
        <div class="admin-card admin-section-card">
            <div class="section-header">
                <h2 class="card-title"><i class="bi bi-geo-alt"></i> Dernières missions</h2>
                <a href="missions.php" class="btn btn-sm btn-admin-outline">Voir tout</a>
            </div>
            <?php if (count($recentMissions) > 0): ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Lieu</th>
                            <th>Mise à jour</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentMissions as $m): ?>
                            <tr>
                                <td><a href="missions.php?id=<?= (int) $m->id ?>"><?= htmlspecialchars($m->title) ?></a></td>
                                <td class="text-muted"><?= htmlspecialchars($m->location ?: '—') ?></td>
                                <td class="text-muted"><?= $m->updated_at ? date('d/m/Y', strtotime($m->updated_at)) : '—' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="admin-empty">
                    <i class="bi bi-inbox d-block"></i>
                    Aucune mission pour le moment.
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="admin-card admin-section-card">
            <div class="section-header">
                <h2 class="card-title"><i class="bi bi-megaphone"></i> Dernières annonces</h2>
                <a href="announcements.php" class="btn btn-sm btn-admin-outline">Voir tout</a>
            </div>
            <?php if (count($recentAnnouncements) > 0): ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Publication</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentAnnouncements as $a): ?>
                            <tr>
                                <td><a href="announcements.php?id=<?= (int) $a->id ?>"><?= htmlspecialchars($a->title) ?></a>
                                </td>
                                <td class="text-muted">
                                    <?= ($a->published_at ? date('d/m/Y', strtotime($a->published_at)) : ($a->created_at ? date('d/m/Y', strtotime($a->created_at)) : '—')) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="admin-empty">
                    <i class="bi bi-megaphone d-block"></i>
                    Aucune annonce pour le moment.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<footer class="admin-main-footer">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span>Expertise Admin &middot; Back-office</span>
        <span><?= date('Y') ?></span>
    </div>
</footer>

<?php require __DIR__ . '/inc/footer.php'; ?>

<!-- Initialisation des Graphiques -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Graphique des Missions
        const missionCtx = document.getElementById('missionChart').getContext('2d');
        new Chart(missionCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($missionStatusStats, 'label')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($missionStatusStats, 'value')) ?>,
                    backgroundColor: ['#1D1C3E', '#FFC107', '#0071BC', '#198754', '#fd7e14', '#6f42c1'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, padding: 15, font: { family: 'Montserrat' } } }
                },
                cutout: '70%'
            }
        });

        // Graphique des Projets
        const projectCtx = document.getElementById('projectChart').getContext('2d');
        new Chart(projectCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_map(function ($l) {
                    return ucfirst(str_replace('_', ' ', $l)); }, array_column($projectStatusStats, 'label'))) ?>,
                datasets: [{
                    label: 'Nombre de projets',
                    data: <?= json_encode(array_column($projectStatusStats, 'value')) ?>,
                    backgroundColor: '#FFC107',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { display: false }, ticks: { stepSize: 1 } },
                    x: { grid: { display: false } }
                }
            }
        });
    });
</script>