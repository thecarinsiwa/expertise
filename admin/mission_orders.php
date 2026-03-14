<?php
/**
 * Gestion des Ordres de Missions
 */
require_once __DIR__ . '/inc/auth.php';
require_permission('admin.mission_orders.view');
require __DIR__ . '/inc/db.php';

$pageTitle = 'Ordres de Mission – Administration';
$currentNav = 'missions';

$mission_id = isset($_GET['mission_id']) ? (int) $_GET['mission_id'] : 0;

$sql = "
    SELECT m.id, m.title, m.reference, mo.id as order_id, mo.order_number, mo.issue_date, mo.status
    FROM mission m
    LEFT JOIN mission_order mo ON m.id = mo.mission_id
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
        <h1 class="h3 mb-0 fw-bold">Ordres de Mission</h1>
        <p class="text-muted small">Gestion des documents officiels d'intervention.</p>
    </div>
</div>

<div class="admin-card p-0 overflow-hidden">
    <table class="table table-hover align-middle mb-0">
        <thead class="bg-light">
            <tr>
                <th class="ps-4">Mission</th>
                <th>Référence</th>
                <th>N° Ordre</th>
                <th>Date Émission</th>
                <th>Statut</th>
                <th class="pe-4 text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($missions as $m): ?>
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold">
                            <?= htmlspecialchars($m->title) ?>
                        </div>
                        <div class="text-muted extra-small">ID: #
                            <?= $m->id ?>
                        </div>
                    </td>
                    <td>
                        <?= htmlspecialchars($m->reference ?: '—') ?>
                    </td>
                    <td>
                        <?php if ($m->order_id): ?>
                            <span class="badge bg-light text-dark border">
                                <?= htmlspecialchars($m->order_number) ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted small italic">Non généré</span>
                        <?php endif; ?>
                    </td>
                    <td class="small text-muted">
                        <?= $m->issue_date ? date('d/m/Y', strtotime($m->issue_date)) : '—' ?>
                    </td>
                    <td>
                        <?php if ($m->status): ?>
                            <span
                                class="badge bg-<?= $m->status === 'signed' ? 'success' : 'warning' ?>-subtle text-<?= $m->status === 'signed' ? 'success' : 'warning' ?> border px-2">
                                <?= ucfirst($m->status) ?>
                            </span>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td class="pe-4 text-end">
                        <?php if ($m->order_id): ?>
                            <a href="#" class="btn btn-sm btn-outline-admin-sidebar" title="Imprimer"><i
                                    class="bi bi-printer"></i></a>
                            <a href="#" class="btn btn-sm btn-outline-admin-sidebar" title="Modifier"><i
                                    class="bi bi-pencil"></i></a>
                        <?php else: ?>
                            <a href="#" class="btn btn-sm btn-admin-primary">Générer</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="mt-4">
    <a href="missions.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Retour aux missions</a>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>