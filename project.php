<?php
session_start();
$pageTitle = 'Projet';
$organisation = null;
$project = null;
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$baseUrl = ($scriptDir === '/' || $scriptDir === '\\') ? '' : rtrim($scriptDir, '/') . '/';

require_once __DIR__ . '/inc/db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ' . $baseUrl . 'projects.php');
    exit;
}

$statusLabels = [
    'draft' => 'Brouillon',
    'planned' => 'Planifié',
    'in_progress' => 'En cours',
    'on_hold' => 'En pause',
    'completed' => 'Terminé',
    'cancelled' => 'Annulé',
];
$priorityLabels = [
    'low' => 'Basse',
    'medium' => 'Moyenne',
    'high' => 'Haute',
    'critical' => 'Critique',
];

try {
    $stmt = $pdo->query("SELECT id, name, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) {
        $organisation = $row;
        $pageTitle = $row->name;
    }

    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.code, p.description, p.cover_image, p.start_date, p.end_date, p.status, p.priority, p.updated_at,
               pg.name AS programme_name
        FROM project p
        LEFT JOIN programme pg ON p.programme_id = pg.id
        WHERE p.id = ? AND p.organisation_id = (SELECT id FROM organisation WHERE is_active = 1 LIMIT 1)
    ");
    $stmt->execute([$id]);
    $project = $stmt->fetch();

    if (!$project) {
        header('Location: ' . $baseUrl . 'projects.php');
        exit;
    }

    $pageTitle = $project->name . ' — ' . ($organisation ? $organisation->name : 'Expertise');

    $phases = [];
    $stmt = $pdo->prepare("SELECT id, name, sequence, start_date, end_date, description FROM project_phase WHERE project_id = ? ORDER BY sequence ASC, id ASC");
    $stmt->execute([$id]);
    $phases = $stmt->fetchAll();

    $bailleurs = [];
    $stmt = $pdo->prepare("
        SELECT b.id, b.name, b.logo FROM bailleur b
        INNER JOIN project_bailleur pb ON pb.bailleur_id = b.id
        WHERE pb.project_id = ?
    ");
    $stmt->execute([$id]);
    $bailleurs = $stmt->fetchAll();

    $otherProjects = [];
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.code, p.start_date, p.end_date
        FROM project p
        WHERE p.organisation_id = (SELECT id FROM organisation WHERE is_active = 1 LIMIT 1) AND p.id != ?
        ORDER BY p.start_date DESC, p.updated_at DESC
        LIMIT 5
    ");
    $stmt->execute([$id]);
    $otherProjects = $stmt->fetchAll();
} catch (PDOException $e) {
    $pdo = null;
    header('Location: ' . $baseUrl . 'projects.php');
    exit;
}

require_once __DIR__ . '/inc/asset_url.php';
require __DIR__ . '/inc/head.php';
require __DIR__ . '/inc/header.php';
$hasCover = !empty($project->cover_image);
$coverUrl = $hasCover ? client_asset_url($baseUrl, $project->cover_image) : '';
$statusLabel = isset($statusLabels[$project->status]) ? $statusLabels[$project->status] : $project->status;
$priorityLabel = isset($priorityLabels[$project->priority]) ? $priorityLabels[$project->priority] : $project->priority;
?>

    <main class="mission-detail project-detail">
        <?php if ($hasCover): ?>
        <header class="mission-detail-hero">
            <div class="mission-detail-hero-bg" style="background-image: url('<?= htmlspecialchars($coverUrl) ?>');"></div>
            <div class="mission-detail-hero-overlay"></div>
            <div class="container mission-detail-hero-content">
                <nav aria-label="Fil d'Ariane" class="mission-detail-breadcrumb">
                    <a href="<?= $baseUrl ?>index.php"><i class="bi bi-arrow-left"></i> Accueil</a>
                    <span class="mx-1">/</span>
                    <a href="<?= $baseUrl ?>projects.php">Projets</a>
                </nav>
                <span class="mission-detail-badge">Projet</span>
                <?php if (!empty($project->programme_name)): ?>
                <p class="mission-detail-meta-top"><span><?= htmlspecialchars($project->programme_name) ?></span></p>
                <?php endif; ?>
                <h1 class="mission-detail-title"><?= htmlspecialchars($project->name) ?></h1>
                <?php if (!empty($project->code)): ?>
                <p class="mission-detail-ref"><?= htmlspecialchars($project->code) ?></p>
                <?php endif; ?>
                <p class="mission-detail-dates">
                    <?php if ($project->start_date): ?>Du <?= date('d/m/Y', strtotime($project->start_date)) ?><?php endif; ?>
                    <?php if ($project->end_date): ?> au <?= date('d/m/Y', strtotime($project->end_date)) ?><?php endif; ?>
                    <?php if ($project->updated_at): ?> <span class="mission-detail-updated">· Mise à jour <?= date('d M Y', strtotime($project->updated_at)) ?></span><?php endif; ?>
                </p>
            </div>
        </header>
        <?php else: ?>
        <div class="mission-detail-no-hero">
            <div class="container">
                <nav aria-label="Fil d'Ariane" class="mission-detail-breadcrumb">
                    <a href="<?= $baseUrl ?>index.php"><i class="bi bi-arrow-left"></i> Accueil</a>
                    <span class="mx-1">/</span>
                    <a href="<?= $baseUrl ?>projects.php">Projets</a>
                </nav>
                <span class="mission-detail-badge mission-detail-badge--dark">Projet</span>
                <?php if (!empty($project->programme_name)): ?>
                <p class="mission-detail-meta-top mission-detail-meta-top--dark"><span><?= htmlspecialchars($project->programme_name) ?></span></p>
                <?php endif; ?>
                <h1 class="mission-detail-title mission-detail-title--dark"><?= htmlspecialchars($project->name) ?></h1>
                <?php if (!empty($project->code)): ?>
                <p class="mission-detail-ref mission-detail-ref--dark"><?= htmlspecialchars($project->code) ?></p>
                <?php endif; ?>
                <p class="mission-detail-dates mission-detail-dates--dark">
                    <?php if ($project->start_date): ?>Du <?= date('d/m/Y', strtotime($project->start_date)) ?><?php endif; ?>
                    <?php if ($project->end_date): ?> au <?= date('d/m/Y', strtotime($project->end_date)) ?><?php endif; ?>
                    <?php if ($project->updated_at): ?> <span class="mission-detail-updated">· Mise à jour <?= date('d M Y', strtotime($project->updated_at)) ?></span><?php endif; ?>
                </p>
            </div>
        </div>
        <?php endif; ?>

        <div class="mission-detail-body">
            <div class="container">
                <div class="mission-detail-grid">
                    <article class="mission-detail-main">
                        <?php if (!empty($project->description)): ?>
                        <div class="mission-detail-description">
                            <?= client_rewrite_uploads_in_html($baseUrl, $project->description) ?>
                        </div>
                        <?php else: ?>
                        <p class="text-muted">Aucune description pour ce projet.</p>
                        <?php endif; ?>

                        <?php if (count($phases) > 0): ?>
                        <div class="project-detail-phases mt-4 pt-4 border-top">
                            <h2 class="mission-detail-block-title"><i class="bi bi-list-stars"></i> Phases du projet</h2>
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($phases as $phase): ?>
                                <li class="project-detail-phase mb-3 pb-3 border-bottom border-light">
                                    <strong><?= htmlspecialchars($phase->name) ?></strong>
                                    <?php if ($phase->start_date || $phase->end_date): ?>
                                    <span class="text-muted small d-block mt-1">
                                        <?php if ($phase->start_date): ?><?= date('d/m/Y', strtotime($phase->start_date)) ?><?php endif; ?>
                                        <?php if ($phase->end_date): ?> — <?= date('d/m/Y', strtotime($phase->end_date)) ?><?php endif; ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if (!empty($phase->description)): ?>
                                    <p class="small text-muted mt-1 mb-0"><?= nl2br(htmlspecialchars(mb_substr($phase->description, 0, 300))) ?><?= mb_strlen($phase->description) > 300 ? '…' : '' ?></p>
                                    <?php endif; ?>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </article>

                    <aside class="mission-detail-sidebar">
                        <div class="mission-detail-block">
                            <h2 class="mission-detail-block-title"><i class="bi bi-info-circle"></i> Informations</h2>
                            <dl class="project-detail-meta mb-0">
                                <dt>Statut</dt>
                                <dd><?= htmlspecialchars($statusLabel) ?></dd>
                                <dt>Priorité</dt>
                                <dd><?= htmlspecialchars($priorityLabel) ?></dd>
                                <?php if (!empty($project->programme_name)): ?>
                                <dt>Programme</dt>
                                <dd><?= htmlspecialchars($project->programme_name) ?></dd>
                                <?php endif; ?>
                            </dl>
                        </div>

                        <?php if (count($bailleurs) > 0): ?>
                        <div class="mission-detail-block">
                            <h2 class="mission-detail-block-title"><i class="bi bi-building"></i> Bailleurs</h2>
                            <div class="mission-detail-bailleurs">
                                <?php foreach ($bailleurs as $b): ?>
                                <div class="mission-detail-bailleur">
                                    <?php if (!empty($b->logo)): ?>
                                    <img src="<?= htmlspecialchars(client_asset_url($baseUrl, $b->logo)) ?>" alt="<?= htmlspecialchars($b->name) ?>" class="mission-detail-bailleur-logo">
                                    <?php endif; ?>
                                    <span class="mission-detail-bailleur-name"><?= htmlspecialchars($b->name) ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (count($otherProjects) > 0): ?>
                        <div class="mission-detail-block">
                            <h2 class="mission-detail-block-title"><i class="bi bi-folder2"></i> Autres projets</h2>
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($otherProjects as $op): ?>
                                <li class="mb-2 pb-2 border-bottom border-light">
                                    <a href="<?= $baseUrl ?>project.php?id=<?= (int) $op->id ?>" class="text-decoration-none text-dark">
                                        <strong class="d-block"><?= htmlspecialchars($op->name) ?></strong>
                                        <span class="small text-muted"><?= $op->start_date ? date('d/m/Y', strtotime($op->start_date)) : '' ?><?= $op->end_date ? ' — ' . date('d/m/Y', strtotime($op->end_date)) : '' ?></span>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <a href="<?= $baseUrl ?>projects.php" class="btn btn-view-all btn-sm mt-2">Tous les projets</a>
                        </div>
                        <?php endif; ?>
                    </aside>
                </div>
            </div>
        </div>
    </main>

<?php require __DIR__ . '/inc/footer.php'; ?>
