<?php
/**
 * Gestion des Frais de Missions
 */
require_once __DIR__ . '/inc/auth.php';
require_permission('admin.mission_expenses.view');
require __DIR__ . '/inc/db.php';

$pageTitle = 'Frais de Mission – Administration';
$currentNav = 'missions';

$mission_id = isset($_GET['mission_id']) ? (int)$_GET['mission_id'] : 0;

$sql = "
    SELECT me.*, m.title as mission_title, u.first_name, u.last_name
    FROM mission_expense me
    JOIN mission m ON me.mission_id = m.id
    JOIN user u ON me.user_id = u.id
";
$params = [];

if ($mission_id > 0) {
    $sql .= " WHERE me.mission_id = ?";
    $params[] = $mission_id;
}

$sql .= " ORDER BY me.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$expenses = $stmt->fetchAll();

$sqlTotal = "SELECT status, SUM(amount) as total FROM mission_expense";
if ($mission_id > 0) {
    $sqlTotal .= " WHERE mission_id = ?";
    $totals = $pdo->prepare($sqlTotal . " GROUP BY status");
    $totals->execute([$mission_id]);
} else {
    $totals = $pdo->query($sqlTotal . " GROUP BY status");
}
$totals = $totals->fetchAll();

$sumTotal = 0;
foreach ($totals as $t)
    $sumTotal += $t->total;

require __DIR__ . '/inc/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0 fw-bold">Frais de Mission</h1>
        <p class="text-muted small">Suivi des dépenses et notes de frais terrain.</p>
    </div>
    <div class="text-end">
        <div class="small text-muted mb-0">Total engagé</div>
        <div class="h4 fw-bold mb-0 text-admin-primary">
            <?= number_format($sumTotal, 0, ',', ' ') ?> <small>XOF</small>
        </div>
    </div>
</div>

<div class="admin-card p-0 overflow-hidden mb-4">
    <table class="table table-hover align-middle mb-0">
        <thead class="bg-light">
            <tr>
                <th class="ps-4">Mission</th>
                <th>Bénéficiaire</th>
                <th>Catégorie</th>
                <th>Montant</th>
                <th>Statut</th>
                <th class="pe-4 text-end">Justificatif</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($expenses as $e): ?>
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold extra-small text-truncate" style="max-width: 150px;">
                            <?= htmlspecialchars($e->mission_title) ?>
                        </div>
                    </td>
                    <td class="small">
                        <?= htmlspecialchars($e->first_name . ' ' . $e->last_name) ?>
                    </td>
                    <td><span class="badge bg-light text-muted border">
                            <?= htmlspecialchars($e->category) ?>
                        </span></td>
                    <td class="fw-bold text-dark">
                        <?= number_format($e->amount, 0, ',', ' ') ?>
                        <?= $e->currency ?>
                    </td>
                    <td>
                        <span
                            class="badge bg-<?= $e->status === 'approved' ? 'success' : ($e->status === 'rejected' ? 'danger' : 'warning') ?>-subtle text-<?= $e->status === 'approved' ? 'success' : ($e->status === 'rejected' ? 'danger' : 'warning') ?> border px-2">
                            <?= ucfirst($e->status) ?>
                        </span>
                    </td>
                    <td class="pe-4 text-end">
                        <?php if ($e->receipt_url): ?>
                            <a href="../<?= $e->receipt_url ?>" target="_blank" class="btn btn-sm btn-outline-admin-sidebar"><i
                                    class="bi bi-file-earmark-pdf"></i></a>
                        <?php else: ?>
                            <span class="text-muted italic small">Aucun</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($expenses)): ?>
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted small">Aucune dépense enregistrée.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="mt-4">
    <a href="missions.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Retour aux missions</a>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>