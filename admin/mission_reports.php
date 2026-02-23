<?php
/**
 * Gestion des Rapports de Missions
 */
require_once __DIR__ . '/inc/auth.php';
require __DIR__ . '/inc/db.php';

$pageTitle = 'Rapports de Mission – Administration';
$currentNav = 'missions';

$mission_id = isset($_GET['mission_id']) ? (int) $_GET['mission_id'] : 0;

$sql = "
    SELECT mr.id, mr.mission_id, m.title as mission_title, mr.report_date, mr.summary, mr.status
    FROM mission_report mr
    JOIN mission m ON mr.mission_id = m.id
";
$params = [];

if ($mission_id > 0) {
    $sql .= " WHERE mr.mission_id = ?";
    $params[] = $mission_id;
}

$sql .= " ORDER BY mr.report_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();

require __DIR__ . '/inc/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0 fw-bold">Rapports de Mission</h1>
        <p class="text-muted small">Synthèses et résultats des interventions terminées.</p>
    </div>
    <a href="#" class="btn btn-admin-primary disabled">
        <i class="bi bi-file-earmark-plus me-1"></i> Nouveau rapport
    </a>
</div>

<div class="admin-card p-0 overflow-hidden">
    <table class="table table-hover align-middle mb-0">
        <thead class="bg-light">
            <tr>
                <th class="ps-4">Date</th>
                <th>Mission</th>
                <th>Résumé</th>
                <th>Statut</th>
                <th class="pe-4 text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reports as $r): ?>
                <tr>
                    <td class="ps-4 small fw-medium">
                        <?= $r->report_date ? date('d/m/Y', strtotime($r->report_date)) : '—' ?>
                    </td>
                    <td class="fw-bold">
                        <?= htmlspecialchars($r->mission_title) ?>
                    </td>
                    <td class="text-truncate small text-muted" style="max-width: 200px;">
                        <?= htmlspecialchars($r->summary) ?>
                    </td>
                    <td>
                        <span
                            class="badge bg-<?= $r->status === 'final' ? 'success' : 'info' ?>-subtle text-<?= $r->status === 'final' ? 'success' : 'info' ?> border px-2">
                            <?= ucfirst($r->status) ?>
                        </span>
                    </td>
                    <td class="pe-4 text-end">
                        <a href="#" class="btn btn-sm btn-outline-admin-sidebar"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($reports)): ?>
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted small">Aucun rapport soumis pour le moment.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="mt-4">
    <a href="missions.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Retour aux missions</a>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>