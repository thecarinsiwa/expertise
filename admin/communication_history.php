<?php
$pageTitle = 'Historique Communication – Administration';
$currentNav = 'communication_history';
require_once __DIR__ . '/inc/auth.php';
require_permission('admin.communication_history.view');
require __DIR__ . '/inc/db.php';

$list = [];
$stats = ['total' => 0];
$entity_filter = isset($_GET['entity_type']) ? trim($_GET['entity_type']) : null;
$user_filter = isset($_GET['user_id']) ? (int) $_GET['user_id'] : null;
$users = [];

if ($pdo) {
    try {
        $users = $pdo->query("SELECT id, first_name, last_name, email FROM user ORDER BY last_name, first_name")->fetchAll();
    } catch (PDOException $e) {
        $users = [];
    }

    $stats['total'] = (int) $pdo->query("SELECT COUNT(*) FROM communication_history")->fetchColumn();

    $sql = "
        SELECT ch.*, u.first_name, u.last_name, u.email
        FROM communication_history ch
        LEFT JOIN user u ON ch.user_id = u.id
        WHERE 1=1
    ";
    $params = [];
    if ($entity_filter !== null && $entity_filter !== '') {
        $sql .= " AND ch.entity_type = ?";
        $params[] = $entity_filter;
    }
    if ($user_filter > 0) {
        $sql .= " AND ch.user_id = ?";
        $params[] = $user_filter;
    }
    $sql .= " ORDER BY ch.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $list = $stmt->fetchAll();
}
require __DIR__ . '/inc/header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Tableau de bord</a></li>
        <li class="breadcrumb-item"><a href="announcements.php">Communication</a></li>
        <li class="breadcrumb-item active">Historique</li>
    </ol>
</nav>

<header class="admin-header">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h1>Historique Communication</h1>
            <p class="text-muted mb-0">Journal des actions sur les entités de communication (lecture seule).</p>
        </div>
        <div class="d-flex gap-2">
            <a href="attachments.php" class="btn btn-admin-outline"><i class="bi bi-paperclip me-1"></i> Pièces jointes</a>
            <a href="announcements.php" class="btn btn-admin-outline"><i class="bi bi-megaphone me-1"></i> Annonces</a>
        </div>
    </div>
</header>

<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="admin-card p-3 d-flex flex-wrap gap-2 align-items-center bg-light border shadow-sm">
            <span class="text-muted small fw-bold text-uppercase me-2"><i class="bi bi-link-45deg me-1"></i> Raccourcis</span>
            <a href="attachments.php" class="btn btn-sm btn-admin-outline"><i class="bi bi-paperclip me-1"></i> Pièces jointes</a>
            <a href="announcements.php" class="btn btn-sm btn-admin-outline"><i class="bi bi-megaphone me-1"></i> Annonces</a>
            <span class="text-muted small fw-bold text-uppercase ms-2 me-2"><i class="bi bi-funnel me-1"></i> Filtres</span>
            <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
                <select name="entity_type" class="form-select form-select-sm" style="max-width:200px;" onchange="this.form.submit()">
                    <option value="">— Type d'entité —</option>
                    <?php
                    $entityTypes = $pdo ? $pdo->query("SELECT DISTINCT entity_type FROM communication_history ORDER BY entity_type")->fetchAll(PDO::FETCH_COLUMN) : [];
                    foreach ($entityTypes as $t):
                    ?>
                        <option value="<?= htmlspecialchars($t) ?>" <?= $entity_filter === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="user_id" class="form-select form-select-sm" style="max-width:220px;" onchange="this.form.submit()">
                    <option value="">— Utilisateur —</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= (int) $u->id ?>" <?= $user_filter === (int) $u->id ? 'selected' : '' ?>><?= htmlspecialchars($u->last_name . ' ' . $u->first_name) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($entity_filter !== null && $entity_filter !== '' || $user_filter > 0): ?>
                    <a href="communication_history.php" class="btn btn-sm btn-outline-secondary">Réinitialiser</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-4">
        <div class="admin-card text-center p-3 h-100">
            <div class="text-muted small text-uppercase mb-1 fw-bold">Total entrées</div>
            <div class="h3 mb-0 fw-bold"><?= $stats['total'] ?></div>
        </div>
    </div>
</div>

<?php if (count($list) > 0): ?>
    <div class="admin-card admin-section-card">
        <div class="table-responsive">
            <table class="admin-table table table-hover" id="historyTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Utilisateur</th>
                        <th>Entité (type)</th>
                        <th>ID entité</th>
                        <th>Action</th>
                        <th>Métadonnées</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($list as $h): ?>
                        <tr>
                            <td class="text-muted small"><?= $h->created_at ? date('d/m/Y H:i:s', strtotime($h->created_at)) : '—' ?></td>
                            <td><?= $h->user_id ? htmlspecialchars($h->last_name . ' ' . $h->first_name) : '<em>—</em>' ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($h->entity_type) ?></span></td>
                            <td><code><?= (int) $h->entity_id ?></code></td>
                            <td><span class="badge bg-light text-dark"><?= htmlspecialchars($h->action) ?></span></td>
                            <td class="small text-break" style="max-width:200px;"><?= $h->metadata ? htmlspecialchars(is_string($h->metadata) ? $h->metadata : json_encode($h->metadata)) : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <style>.admin-table th{background:#f8f9fa;padding:0.75rem;} .admin-table td{padding:0.75rem;}</style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('historyTable') && typeof $ !== 'undefined' && $.fn.DataTable) {
            $('#historyTable').DataTable({ language: { url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json" }, order: [[0, "desc"]], pageLength: 20 });
        }
    });
    </script>
<?php else: ?>
    <div class="admin-card admin-section-card">
        <p class="admin-empty py-4 mb-0"><i class="bi bi-clock-history"></i> Aucune entrée dans l'historique de communication.</p>
    </div>
<?php endif; ?>

<footer class="admin-main-footer mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <a href="attachments.php" class="text-muted text-decoration-none small"><i class="bi bi-paperclip me-1"></i> Pièces jointes</a>
        <span class="small text-muted">&copy; <?= date('Y') ?> Expertise</span>
    </div>
</footer>
<?php require __DIR__ . '/inc/footer.php'; ?>
