<?php
$pageTitle = 'Commentaires – Administration';
$currentNav = 'comments';
require_once __DIR__ . '/inc/auth.php';
require_permission('admin.comments.view');
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
            $pdo->prepare("DELETE FROM comment WHERE id = ?")->execute([$delId]);
            $success = 'Commentaire supprimé.';
        } catch (PDOException $e) {
            $error = 'Impossible de supprimer (réponses possibles).';
        }
    }

    $stats['total'] = (int) $pdo->query("SELECT COUNT(*) FROM comment")->fetchColumn();

    $sql = "
        SELECT c.*, u.first_name, u.last_name, u.email
        FROM comment c
        JOIN user u ON c.user_id = u.id
        WHERE 1=1
    ";
    $params = [];
    if ($type_filter !== null && $type_filter !== '') {
        $sql .= " AND c.commentable_type = ?";
        $params[] = $type_filter;
    }
    $sql .= " ORDER BY c.created_at DESC";
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
        <li class="breadcrumb-item active">Commentaires</li>
    </ol>
</nav>

<header class="admin-header">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h1>Commentaires</h1>
            <p class="text-muted mb-0">Tous les commentaires (projets, missions, annonces, etc.).</p>
        </div>
        <div class="d-flex gap-2">
            <a href="announcements.php" class="btn btn-admin-outline"><i class="bi bi-megaphone me-1"></i> Annonces</a>
            <a href="attachments.php" class="btn btn-admin-outline"><i class="bi bi-paperclip me-1"></i> Pièces jointes</a>
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
            <a href="attachments.php" class="btn btn-sm btn-admin-outline"><i class="bi bi-paperclip me-1"></i> Pièces jointes</a>
            <a href="announcements.php" class="btn btn-sm btn-admin-outline"><i class="bi bi-megaphone me-1"></i> Annonces</a>
            <span class="text-muted small fw-bold text-uppercase ms-2 me-2"><i class="bi bi-funnel me-1"></i> Filtre</span>
            <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
                <select name="type" class="form-select form-select-sm" style="max-width:220px;" onchange="this.form.submit()">
                    <option value="">— Tous les types —</option>
                    <?php
                    $dynTypes = $pdo ? $pdo->query("SELECT DISTINCT commentable_type FROM comment ORDER BY commentable_type")->fetchAll(PDO::FETCH_COLUMN) : [];
                    foreach ($dynTypes as $t):
                    ?>
                        <option value="<?= htmlspecialchars($t) ?>" <?= $type_filter === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($type_filter !== null && $type_filter !== ''): ?>
                    <a href="comments.php" class="btn btn-sm btn-outline-secondary">Tout afficher</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-4">
        <div class="admin-card text-center p-3 h-100">
            <div class="text-muted small text-uppercase mb-1 fw-bold">Total commentaires</div>
            <div class="h3 mb-0 fw-bold"><?= $stats['total'] ?></div>
        </div>
    </div>
</div>

<?php if (count($list) > 0): ?>
    <div class="admin-card admin-section-card">
        <div class="table-responsive">
            <table class="admin-table table table-hover" id="commentsTable">
                <thead>
                    <tr>
                        <th>Auteur</th>
                        <th>Sur (type)</th>
                        <th>ID entité</th>
                        <th>Contenu</th>
                        <th>Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($list as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c->last_name . ' ' . $c->first_name) ?> <span class="text-muted small">(<?= htmlspecialchars($c->email) ?>)</span></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($c->commentable_type) ?></span></td>
                            <td><code><?= (int) $c->commentable_id ?></code></td>
                            <td class="text-break" style="max-width:280px;"><?= htmlspecialchars(mb_substr($c->body, 0, 80)) ?><?= mb_strlen($c->body) > 80 ? '…' : '' ?></td>
                            <td class="text-muted small"><?= $c->created_at ? date('d/m/Y H:i', strtotime($c->created_at)) : '—' ?></td>
                            <td class="text-end">
                                <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce commentaire ?');">
                                    <input type="hidden" name="delete_id" value="<?= (int) $c->id ?>">
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
        if (document.getElementById('commentsTable') && typeof $ !== 'undefined' && $.fn.DataTable) {
            $('#commentsTable').DataTable({ language: { url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json" }, order: [[4, "desc"]], pageLength: 15 });
        }
    });
    </script>
<?php else: ?>
    <div class="admin-card admin-section-card">
        <p class="admin-empty py-4 mb-0"><i class="bi bi-chat-quote"></i> Aucun commentaire. Les commentaires sont ajoutés sur les projets, missions, annonces, etc.</p>
    </div>
<?php endif; ?>

<footer class="admin-main-footer mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <a href="attachments.php" class="text-muted text-decoration-none small"><i class="bi bi-paperclip me-1"></i> Pièces jointes</a>
        <span class="small text-muted">&copy; <?= date('Y') ?> Expertise</span>
    </div>
</footer>
<?php require __DIR__ . '/inc/footer.php'; ?>
