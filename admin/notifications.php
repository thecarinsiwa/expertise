<?php
$pageTitle = 'Notifications – Administration';
$currentNav = 'notifications';
require_once __DIR__ . '/inc/auth.php';
require __DIR__ . '/inc/db.php';

$list = [];
$stats = ['total' => 0, 'unread' => 0];
$error = '';
$success = '';
$users = [];

if ($pdo) {
    try {
        $users = $pdo->query("SELECT id, first_name, last_name, email FROM user WHERE is_active = 1 ORDER BY last_name, first_name")->fetchAll();
    } catch (PDOException $e) {
        $users = [];
    }

    $user_filter = isset($_GET['user_id']) ? (int) $_GET['user_id'] : null;
    $read_filter = (isset($_GET['read']) && $_GET['read'] !== '') ? (int) $_GET['read'] : null; // 0=non lu, 1=lu

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['delete_id'])) {
            $delId = (int) $_POST['delete_id'];
            try {
                $pdo->prepare("DELETE FROM notification WHERE id = ?")->execute([$delId]);
                $success = 'Notification supprimée.';
            } catch (PDOException $e) {
                $error = 'Impossible de supprimer.';
            }
        }
        if (isset($_POST['mark_read_id'])) {
            $rid = (int) $_POST['mark_read_id'];
            try {
                $pdo->prepare("UPDATE notification SET is_read = 1, read_at = NOW() WHERE id = ?")->execute([$rid]);
                $success = 'Marquée comme lue.';
            } catch (PDOException $e) {
                $error = 'Erreur.';
            }
        }
        if (isset($_POST['create_notification'])) {
            $user_id = (int) ($_POST['user_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $body = trim($_POST['body'] ?? '') ?: null;
            $type = trim($_POST['type'] ?? '') ?: null;
            $related_entity_type = trim($_POST['related_entity_type'] ?? '') ?: null;
            $related_entity_id = trim($_POST['related_entity_id'] ?? '') !== '' ? (int) $_POST['related_entity_id'] : null;
            if ($user_id > 0 && $title !== '') {
                try {
                    $pdo->prepare("INSERT INTO notification (user_id, title, body, type, related_entity_type, related_entity_id) VALUES (?, ?, ?, ?, ?, ?)")
                        ->execute([$user_id, $title, $body, $type, $related_entity_type, $related_entity_id]);
                    $success = 'Notification créée.';
                } catch (PDOException $e) {
                    $error = 'Erreur : ' . $e->getMessage();
                }
            } else {
                $error = 'Destinataire et titre obligatoires.';
            }
        }
    }

    $stats['total'] = (int) $pdo->query("SELECT COUNT(*) FROM notification")->fetchColumn();
    $stats['unread'] = (int) $pdo->query("SELECT COUNT(*) FROM notification WHERE is_read = 0")->fetchColumn();

    $sql = "
        SELECT n.*, u.first_name, u.last_name, u.email
        FROM notification n
        JOIN user u ON n.user_id = u.id
        WHERE 1=1
    ";
    $params = [];
    if ($user_filter > 0) {
        $sql .= " AND n.user_id = ?";
        $params[] = $user_filter;
    }
    if ($read_filter === 0) {
        $sql .= " AND n.is_read = 0";
    } elseif ($read_filter === 1) {
        $sql .= " AND n.is_read = 1";
    }
    $sql .= " ORDER BY n.created_at DESC";
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
        <li class="breadcrumb-item active">Notifications</li>
    </ol>
</nav>

<header class="admin-header">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h1>Notifications</h1>
            <p class="text-muted mb-0">Consulter et gérer les notifications utilisateurs.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="announcements.php" class="btn btn-admin-outline"><i class="bi bi-megaphone me-1"></i> Annonces</a>
            <button type="button" class="btn btn-admin-primary" data-bs-toggle="modal" data-bs-target="#createNotificationModal"><i class="bi bi-plus-lg me-1"></i> Envoyer une notification</button>
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
            <a href="announcements.php" class="btn btn-sm btn-admin-outline"><i class="bi bi-megaphone me-1"></i> Annonces</a>
            <a href="channels.php" class="btn btn-sm btn-admin-outline"><i class="bi bi-chat-dots me-1"></i> Canaux</a>
            <span class="text-muted small fw-bold text-uppercase ms-2 me-2"><i class="bi bi-funnel me-1"></i> Filtres</span>
            <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
                <select name="user_id" class="form-select form-select-sm" style="max-width:220px;" onchange="this.form.submit()">
                    <option value="">— Tous les utilisateurs —</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= (int) $u->id ?>" <?= $user_filter === (int) $u->id ? 'selected' : '' ?>><?= htmlspecialchars($u->last_name . ' ' . $u->first_name) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="read" class="form-select form-select-sm" style="max-width:160px;" onchange="this.form.submit()">
                    <option value="">— Toutes —</option>
                    <option value="0" <?= $read_filter === 0 ? 'selected' : '' ?>>Non lues</option>
                    <option value="1" <?= $read_filter === 1 ? 'selected' : '' ?>>Lues</option>
                </select>
                <?php if ($user_filter || $read_filter !== null): ?>
                    <a href="notifications.php" class="btn btn-sm btn-outline-secondary">Réinitialiser</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="admin-card text-center p-3 h-100">
            <div class="text-muted small text-uppercase mb-1 fw-bold">Total</div>
            <div class="h3 mb-0 fw-bold"><?= $stats['total'] ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-card text-center p-3 h-100">
            <div class="text-muted small text-uppercase mb-1 fw-bold">Non lues</div>
            <div class="h3 mb-0 fw-bold text-warning"><?= $stats['unread'] ?></div>
        </div>
    </div>
</div>

<?php if (count($list) > 0): ?>
    <div class="admin-card admin-section-card">
        <div class="table-responsive">
            <table class="admin-table table table-hover" id="notificationsTable">
                <thead>
                    <tr>
                        <th>Destinataire</th>
                        <th>Titre</th>
                        <th>Type</th>
                        <th>Lu</th>
                        <th>Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($list as $n): ?>
                        <tr class="<?= !$n->is_read ? 'table-warning' : '' ?>">
                            <td><?= htmlspecialchars($n->last_name . ' ' . $n->first_name) ?> <span class="text-muted small">(<?= htmlspecialchars($n->email) ?>)</span></td>
                            <td><?= htmlspecialchars($n->title) ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($n->type ?: '—') ?></span></td>
                            <td><?= $n->is_read ? '<span class="badge bg-success">Lu</span>' : '<span class="badge bg-warning text-dark">Non lu</span>' ?></td>
                            <td class="text-muted small"><?= $n->created_at ? date('d/m/Y H:i', strtotime($n->created_at)) : '—' ?></td>
                            <td class="text-end">
                                <?php if (!$n->is_read): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="mark_read_id" value="<?= (int) $n->id ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Marquer comme lu"><i class="bi bi-check2"></i></button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette notification ?');">
                                    <input type="hidden" name="delete_id" value="<?= (int) $n->id ?>">
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
        if (document.getElementById('notificationsTable') && typeof $ !== 'undefined' && $.fn.DataTable) {
            $('#notificationsTable').DataTable({ language: { url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json" }, order: [[4, "desc"]], pageLength: 15 });
        }
    });
    </script>
<?php else: ?>
    <div class="admin-card admin-section-card">
        <p class="admin-empty py-4 mb-0"><i class="bi bi-bell"></i> Aucune notification. <button type="button" class="btn btn-link p-0" data-bs-toggle="modal" data-bs-target="#createNotificationModal">Envoyer une notification</button></p>
    </div>
<?php endif; ?>

<div class="modal fade" id="createNotificationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title fw-bold">Envoyer une notification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="create_notification" value="1">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Destinataire *</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">— Choisir —</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= (int) $u->id ?>"><?= htmlspecialchars($u->last_name . ' ' . $u->first_name) ?> (<?= htmlspecialchars($u->email) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Titre *</label>
                        <input type="text" name="title" class="form-control" required placeholder="Titre de la notification">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Contenu</label>
                        <textarea name="body" class="form-control" rows="2" placeholder="Message (optionnel)"></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small">Type</label>
                            <input type="text" name="type" class="form-control form-control-sm" placeholder="ex: info, alert">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Entité liée (type)</label>
                            <input type="text" name="related_entity_type" class="form-control form-control-sm" placeholder="ex: announcement">
                        </div>
                        <div class="col-12">
                            <label class="form-label small">ID entité liée</label>
                            <input type="number" name="related_entity_id" class="form-control form-control-sm" placeholder="Optionnel">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-admin-primary">Envoyer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<footer class="admin-main-footer mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <a href="announcements.php" class="text-muted text-decoration-none small"><i class="bi bi-megaphone me-1"></i> Communication</a>
        <span class="small text-muted">&copy; <?= date('Y') ?> Expertise</span>
    </div>
</footer>
<?php require __DIR__ . '/inc/footer.php'; ?>
