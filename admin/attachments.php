<?php
$pageTitle = 'Pièces jointes – Administration';
$currentNav = 'attachments';
require_once __DIR__ . '/inc/auth.php';
require __DIR__ . '/inc/db.php';

$list = [];
$stats = ['total' => 0];
$error = '';
$success = '';
$type_filter = isset($_GET['type']) ? trim($_GET['type']) : null;

if ($pdo) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
        $delId = (int) $_POST['delete_id'];
        try {
            $row = $pdo->prepare("SELECT file_path FROM attachment WHERE id = ?");
            $row->execute([$delId]);
            $f = $row->fetch();
            $pdo->prepare("DELETE FROM attachment WHERE id = ?")->execute([$delId]);
            if ($f && !empty($f->file_path)) {
                $path = __DIR__ . '/../' . $f->file_path;
                if (is_file($path)) @unlink($path);
            }
            $success = 'Pièce jointe supprimée.';
        } catch (PDOException $e) {
            $error = 'Impossible de supprimer.';
        }
    }

    $stats['total'] = (int) $pdo->query("SELECT COUNT(*) FROM attachment")->fetchColumn();

    $sql = "
        SELECT a.*, u.first_name, u.last_name, u.email
        FROM attachment a
        JOIN user u ON a.uploaded_by_user_id = u.id
        WHERE 1=1
    ";
    $params = [];
    if ($type_filter !== null && $type_filter !== '') {
        $sql .= " AND a.attachable_type = ?";
        $params[] = $type_filter;
    }
    $sql .= " ORDER BY a.created_at DESC";
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
        <li class="breadcrumb-item active">Pièces jointes</li>
    </ol>
</nav>

<header class="admin-header">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h1>Pièces jointes</h1>
            <p class="text-muted mb-0">Fichiers attachés aux entités (message, projet, mission, etc.).</p>
        </div>
        <div class="d-flex gap-2">
            <a href="comments.php" class="btn btn-admin-outline"><i class="bi bi-chat-quote me-1"></i> Commentaires</a>
            <a href="announcements.php" class="btn btn-admin-outline"><i class="bi bi-megaphone me-1"></i> Annonces</a>
        </div>
    </div>
</header>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="admin-card p-3 d-flex flex-wrap gap-2 align-items-center bg-light border shadow-sm">
            <span class="text-muted small fw-bold text-uppercase me-2"><i class="bi bi-link-45deg me-1"></i> Raccourcis</span>
            <a href="comments.php" class="btn btn-sm btn-admin-outline"><i class="bi bi-chat-quote me-1"></i> Commentaires</a>
            <a href="communication_history.php" class="btn btn-sm btn-admin-outline"><i class="bi bi-clock-history me-1"></i> Historique</a>
            <span class="text-muted small fw-bold text-uppercase ms-2 me-2"><i class="bi bi-funnel me-1"></i> Filtre</span>
            <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
                <select name="type" class="form-select form-select-sm" style="max-width:220px;" onchange="this.form.submit()">
                    <option value="">— Tous les types —</option>
                    <?php
                    $dynTypes = $pdo ? $pdo->query("SELECT DISTINCT attachable_type FROM attachment ORDER BY attachable_type")->fetchAll(PDO::FETCH_COLUMN) : [];
                    foreach ($dynTypes as $t):
                    ?>
                        <option value="<?= htmlspecialchars($t) ?>" <?= $type_filter === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($type_filter !== null && $type_filter !== ''): ?>
                    <a href="attachments.php" class="btn btn-sm btn-outline-secondary">Tout afficher</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-4">
        <div class="admin-card text-center p-3 h-100">
            <div class="text-muted small text-uppercase mb-1 fw-bold">Total pièces jointes</div>
            <div class="h3 mb-0 fw-bold"><?= $stats['total'] ?></div>
        </div>
    </div>
</div>

<?php if (count($list) > 0): ?>
    <div class="admin-card admin-section-card">
        <div class="table-responsive">
            <table class="admin-table table table-hover" id="attachmentsTable">
                <thead>
                    <tr>
                        <th>Fichier</th>
                        <th>Type / Entité</th>
                        <th>ID entité</th>
                        <th>Uploadé par</th>
                        <th>Taille</th>
                        <th>Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($list as $a): ?>
                        <tr>
                            <td>
                                <a href="../<?= htmlspecialchars($a->file_path) ?>" target="_blank" rel="noopener" class="text-decoration-none">
                                    <i class="bi bi-file-earmark me-1"></i><?= htmlspecialchars($a->file_name) ?>
                                </a>
                            </td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($a->attachable_type) ?></span></td>
                            <td><code><?= (int) $a->attachable_id ?></code></td>
                            <td><?= htmlspecialchars($a->last_name . ' ' . $a->first_name) ?></td>
                            <td class="text-muted small"><?= $a->file_size ? number_format($a->file_size / 1024, 1) . ' Ko' : '—' ?></td>
                            <td class="text-muted small"><?= $a->created_at ? date('d/m/Y H:i', strtotime($a->created_at)) : '—' ?></td>
                            <td class="text-end">
                                <a href="../<?= htmlspecialchars($a->file_path) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-download"></i></a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette pièce jointe ?');">
                                    <input type="hidden" name="delete_id" value="<?= (int) $a->id ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <style>.admin-table th{background:#f8f9fa;padding:0.75rem;} .admin-table td{padding:0.75rem;}</style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('attachmentsTable') && typeof $ !== 'undefined' && $.fn.DataTable) {
            $('#attachmentsTable').DataTable({ language: { url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json" }, order: [[5, "desc"]], pageLength: 15 });
        }
    });
    </script>
<?php else: ?>
    <div class="admin-card admin-section-card">
        <p class="admin-empty py-4 mb-0"><i class="bi bi-paperclip"></i> Aucune pièce jointe enregistrée.</p>
    </div>
<?php endif; ?>

<footer class="admin-main-footer mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <a href="comments.php" class="text-muted text-decoration-none small"><i class="bi bi-chat-quote me-1"></i> Commentaires</a>
        <a href="communication_history.php" class="text-muted text-decoration-none small"><i class="bi bi-clock-history me-1"></i> Historique</a>
        <span class="small text-muted">&copy; <?= date('Y') ?> Expertise</span>
    </div>
</footer>
<?php require __DIR__ . '/inc/footer.php'; ?>
