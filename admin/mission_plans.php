<?php
/**
 * Gestion des Plannings de Missions (Steps/Étapes)
 */
require_once __DIR__ . '/inc/auth.php';
require_permission('admin.mission_plans.view');
require __DIR__ . '/inc/db.php';

$pageTitle = 'Plannings de Mission – Administration';
$currentNav = 'missions';

$mission_id = isset($_GET['mission_id']) ? (int) $_GET['mission_id'] : 0;

$sql = "
    SELECT m.id, m.title, m.reference, (SELECT COUNT(*) FROM mission_plan WHERE mission_id = m.id) as steps_count
    FROM mission m
";
$params = [];

if ($mission_id > 0) {
    $sql .= " WHERE m.id = ?";
    $params[] = $mission_id;
}

$sql .= " ORDER BY m.created_at DESC";
$missions = $pdo->prepare($sql);
$missions->execute($params);
$missions = $missions->fetchAll();

require __DIR__ . '/inc/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0 fw-bold">Plannings & Étapes</h1>
        <p class="text-muted small">Suivi du déroulement séquentiel des missions.</p>
    </div>
</div>

<div class="row g-4">
    <?php foreach ($missions as $m): ?>
        <div class="col-md-6 col-xl-4">
            <div class="admin-card p-3 h-100 d-flex flex-column shadow-sm border">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="badge bg-admin-primary-subtle text-admin-primary border px-2 extra-small">
                        <?= $m->reference ?: 'SANS RÉF' ?>
                    </span>
                    <span class="badge bg-light text-muted border px-2 extra-small">
                        <?= $m->steps_count ?> Étapes
                    </span>
                </div>
                <h3 class="h6 fw-bold mb-3">
                    <?= htmlspecialchars($m->title) ?>
                </h3>

                <div class="mt-auto d-flex justify-content-between align-items-center">
                    <div class="small text-muted">ID: #
                        <?= $m->id ?>
                    </div>
                    <a href="missions.php?action=edit&id=<?= $m->id ?>#tab-steps" class="btn btn-sm btn-admin-outline">
                        Gérer le plan <i class="bi bi-chevron-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="mt-4">
    <a href="missions.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Retour aux missions</a>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>