<?php
$pageTitle = 'Sécurité – Administration';
$currentNav = 'security';
require_once __DIR__ . '/inc/auth.php';
require __DIR__ . '/inc/db.php';

$sessions = [];
$activityLog = [];
$auditLog = [];
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'sessions';
$dashStats = ['sessions' => 0, 'activity' => 0, 'audit' => 0];
$error = '';
$success = '';

if ($pdo) {
    // --- TRAITEMENT POST (suppressions) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['delete_session_id'])) {
            $sid = $_POST['delete_session_id'] ?? '';
            if (preg_match('/^[a-zA-Z0-9_-]+$/', $sid)) {
                try {
                    $pdo->prepare("DELETE FROM session WHERE id = ?")->execute([$sid]);
                    header('Location: security.php?tab=sessions&msg=session_revoked');
                    exit;
                } catch (PDOException $e) {
                    $error = 'Impossible de révoquer la session.';
                }
            }
        }
        if (isset($_POST['delete_activity_id'])) {
            $aid = (int) ($_POST['delete_activity_id'] ?? 0);
            if ($aid > 0) {
                try {
                    $pdo->prepare("DELETE FROM activity_log WHERE id = ?")->execute([$aid]);
                    header('Location: security.php?tab=activity&msg=activity_deleted');
                    exit;
                } catch (PDOException $e) {
                    $error = 'Impossible de supprimer l\'entrée.';
                }
            }
        }
        if (isset($_POST['delete_audit_id'])) {
            $aid = (int) ($_POST['delete_audit_id'] ?? 0);
            if ($aid > 0) {
                try {
                    $pdo->prepare("DELETE FROM audit WHERE id = ?")->execute([$aid]);
                    header('Location: security.php?tab=audit&msg=audit_deleted');
                    exit;
                } catch (PDOException $e) {
                    $error = 'Impossible de supprimer l\'entrée d\'audit.';
                }
            }
        }
        if (isset($_POST['clear_old_activity'])) {
            $days = (int) ($_POST['days'] ?? 90);
            if ($days >= 1 && $days <= 365) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
                    $stmt->execute([$days]);
                    header('Location: security.php?tab=activity&msg=activity_cleared');
                    exit;
                } catch (PDOException $e) {
                    $error = 'Impossible de purger le journal.';
                }
            }
        }
        if (isset($_POST['clear_old_audit'])) {
            $days = (int) ($_POST['days'] ?? 90);
            if ($days >= 1 && $days <= 365) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM audit WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
                    $stmt->execute([$days]);
                    header('Location: security.php?tab=audit&msg=audit_cleared');
                    exit;
                } catch (PDOException $e) {
                    $error = 'Impossible de purger l\'audit.';
                }
            }
        }
    }

    try {
        $dashStats['sessions'] = (int) $pdo->query("SELECT COUNT(*) FROM session")->fetchColumn();
        $stmt = $pdo->query("
            SELECT s.id, s.user_id, s.ip_address, s.last_activity, s.created_at,
                   u.first_name, u.last_name, u.email
            FROM session s
            LEFT JOIN user u ON s.user_id = u.id
            ORDER BY s.last_activity DESC
            LIMIT 100
        ");
        if ($stmt) $sessions = $stmt->fetchAll();
    } catch (PDOException $e) { /* table may not exist */ }

    try {
        $dashStats['activity'] = (int) $pdo->query("SELECT COUNT(*) FROM activity_log")->fetchColumn();
        $stmt = $pdo->query("
            SELECT al.id, al.user_id, al.action, al.subject_type, al.subject_id, al.description, al.ip_address, al.created_at,
                   u.first_name, u.last_name, u.email
            FROM activity_log al
            LEFT JOIN user u ON al.user_id = u.id
            ORDER BY al.created_at DESC
            LIMIT 100
        ");
        if ($stmt) $activityLog = $stmt->fetchAll();
    } catch (PDOException $e) { /* table may not exist */ }

    try {
        $dashStats['audit'] = (int) $pdo->query("SELECT COUNT(*) FROM audit")->fetchColumn();
        $stmt = $pdo->query("
            SELECT a.id, a.user_id, a.auditable_type, a.auditable_id, a.event, a.old_values, a.new_values, a.ip_address, a.created_at,
                   u.first_name, u.last_name, u.email
            FROM audit a
            LEFT JOIN user u ON a.user_id = u.id
            ORDER BY a.created_at DESC
            LIMIT 100
        ");
        if ($stmt) $auditLog = $stmt->fetchAll();
    } catch (PDOException $e) { /* table may not exist */ }
}
require __DIR__ . '/inc/header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Tableau de bord</a></li>
        <li class="breadcrumb-item"><a href="security.php" class="text-decoration-none">Sécurité</a></li>
        <?php if ($tab !== 'sessions'): ?>
            <li class="breadcrumb-item active"><?= $tab === 'activity' ? 'Journal d\'activité' : 'Audit' ?></li>
        <?php endif; ?>
    </ol>
</nav>

<header class="admin-header">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h1>Sécurité</h1>
            <p>Sessions, journal d'activité et audit.</p>
        </div>
    </div>
</header>

<?php if (isset($_GET['msg'])): ?>
    <?php
    $msg = $_GET['msg'];
    if ($msg === 'session_revoked') echo '<div class="alert alert-success">Session révoquée.</div>';
    elseif ($msg === 'activity_deleted') echo '<div class="alert alert-success">Entrée du journal supprimée.</div>';
    elseif ($msg === 'audit_deleted') echo '<div class="alert alert-success">Entrée d\'audit supprimée.</div>';
    elseif ($msg === 'activity_cleared') echo '<div class="alert alert-success">Anciennes entrées du journal purgées.</div>';
    elseif ($msg === 'audit_cleared') echo '<div class="alert alert-success">Anciennes entrées d\'audit purgées.</div>';
    ?>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Cartes statistiques -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-4">
        <div class="admin-card text-center p-3 h-100">
            <div class="text-muted small text-uppercase mb-1 fw-bold">Sessions actives</div>
            <div class="h3 mb-0 fw-bold text-info"><?= $dashStats['sessions'] ?></div>
            <div class="mt-2"><a href="security.php?tab=sessions" class="badge bg-info-subtle text-info border text-decoration-none">Voir</a></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="admin-card text-center p-3 h-100">
            <div class="text-muted small text-uppercase mb-1 fw-bold">Journal d'activité</div>
            <div class="h3 mb-0 fw-bold text-warning"><?= $dashStats['activity'] ?></div>
            <div class="mt-2"><a href="security.php?tab=activity" class="badge bg-warning-subtle text-warning border text-decoration-none">Voir</a></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="admin-card text-center p-3 h-100">
            <div class="text-muted small text-uppercase mb-1 fw-bold">Entrées d'audit</div>
            <div class="h3 mb-0 fw-bold text-secondary"><?= $dashStats['audit'] ?></div>
            <div class="mt-2"><a href="security.php?tab=audit" class="badge bg-secondary-subtle text-secondary border text-decoration-none">Voir</a></div>
        </div>
    </div>
</div>

<!-- Onglets -->
<div class="admin-card p-0 overflow-hidden mb-4">
    <div class="admin-header-tabs px-4 pt-3 bg-light border-bottom">
        <ul class="nav nav-tabs border-0" role="tablist">
            <li class="nav-item">
                <a class="nav-link<?= $tab === 'sessions' ? ' active' : '' ?>" href="security.php?tab=sessions"><i class="bi bi-laptop me-1"></i> Sessions</a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?= $tab === 'activity' ? ' active' : '' ?>" href="security.php?tab=activity"><i class="bi bi-journal-text me-1"></i> Journal d'activité</a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?= $tab === 'audit' ? ' active' : '' ?>" href="security.php?tab=audit"><i class="bi bi-clipboard2-data me-1"></i> Audit</a>
            </li>
        </ul>
    </div>

    <div class="p-4">
        <?php if ($tab === 'sessions'): ?>
            <?php if (count($sessions) > 0): ?>
                <div class="table-responsive">
                    <table class="admin-table table table-hover" id="sessionsTable">
                        <thead>
                            <tr>
                                <th>Utilisateur</th>
                                <th>IP</th>
                                <th>Dernière activité</th>
                                <th>Créée le</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessions as $s): ?>
                                <tr>
                                    <td>
                                        <?php if ($s->user_id): ?>
                                            <h6 class="mb-0"><a href="staff.php?user_id=<?= (int) $s->user_id ?>"><?= htmlspecialchars($s->last_name . ' ' . $s->first_name) ?></a></h6>
                                            <span class="text-muted x-small"><?= htmlspecialchars($s->email) ?></span>
                                        <?php else: ?>
                                            — (user_id <?= (int) $s->user_id ?>)
                                        <?php endif; ?>
                                    </td>
                                    <td><code class="x-small"><?= htmlspecialchars($s->ip_address ?? '—') ?></code></td>
                                    <td class="x-small"><?= $s->last_activity ? date('d/m/Y H:i', $s->last_activity) : '—' ?></td>
                                    <td class="x-small"><?= $s->created_at ? date('d/m/Y H:i', strtotime($s->created_at)) : '—' ?></td>
                                    <td class="text-end">
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Révoquer cette session ? L\'utilisateur devra se reconnecter.');">
                                            <input type="hidden" name="delete_session_id" value="<?= htmlspecialchars($s->id) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Révoquer"><i class="bi bi-x-circle"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="admin-empty py-5">
                    <i class="bi bi-laptop d-block mb-3" style="font-size: 3rem;"></i>
                    <h5>Aucune session enregistrée</h5>
                    <p class="text-muted mb-0">Ou table <code>session</code> absente.</p>
                </div>
            <?php endif; ?>

        <?php elseif ($tab === 'activity'): ?>
            <?php if (count($activityLog) > 0): ?>
                <div class="d-flex justify-content-end mb-3">
                    <form method="POST" class="d-inline-flex align-items-center gap-2" onsubmit="return confirm('Supprimer toutes les entrées du journal de plus de N jours ?');">
                        <input type="hidden" name="clear_old_activity" value="1">
                        <label class="small text-muted mb-0">Purger les entrées de plus de</label>
                        <select name="days" class="form-select form-select-sm" style="width: auto;">
                            <option value="30">30 jours</option>
                            <option value="90" selected>90 jours</option>
                            <option value="180">180 jours</option>
                            <option value="365">1 an</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-outline-warning"><i class="bi bi-trash me-1"></i> Purger</button>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="admin-table table table-hover" id="activityTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Utilisateur</th>
                                <th>Action</th>
                                <th>Sujet</th>
                                <th>Description</th>
                                <th>IP</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activityLog as $a): ?>
                                <tr>
                                    <td class="x-small"><?= $a->created_at ? date('d/m/Y H:i', strtotime($a->created_at)) : '—' ?></td>
                                    <td><?= $a->user_id ? htmlspecialchars($a->last_name . ' ' . $a->first_name) : '—' ?></td>
                                    <td><code class="x-small"><?= htmlspecialchars($a->action) ?></code></td>
                                    <td class="x-small"><?= htmlspecialchars($a->subject_type ?? '—') ?> #<?= (int) ($a->subject_id ?? 0) ?></td>
                                    <td class="x-small"><?= htmlspecialchars(mb_substr($a->description ?? '', 0, 60)) ?><?= mb_strlen($a->description ?? '') > 60 ? '…' : '' ?></td>
                                    <td class="x-small"><?= htmlspecialchars($a->ip_address ?? '—') ?></td>
                                    <td class="text-end">
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette entrée ?');">
                                            <input type="hidden" name="delete_activity_id" value="<?= (int) $a->id ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="admin-empty py-5">
                    <i class="bi bi-journal-text d-block mb-3" style="font-size: 3rem;"></i>
                    <h5>Aucune entrée dans le journal d'activité</h5>
                    <p class="text-muted mb-0">Ou table <code>activity_log</code> absente.</p>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <?php if (count($auditLog) > 0): ?>
                <div class="d-flex justify-content-end mb-3">
                    <form method="POST" class="d-inline-flex align-items-center gap-2" onsubmit="return confirm('Supprimer toutes les entrées d\'audit de plus de N jours ?');">
                        <input type="hidden" name="clear_old_audit" value="1">
                        <label class="small text-muted mb-0">Purger les entrées de plus de</label>
                        <select name="days" class="form-select form-select-sm" style="width: auto;">
                            <option value="30">30 jours</option>
                            <option value="90" selected>90 jours</option>
                            <option value="180">180 jours</option>
                            <option value="365">1 an</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-trash me-1"></i> Purger</button>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="admin-table table table-hover" id="auditTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Utilisateur</th>
                                <th>Événement</th>
                                <th>Entité</th>
                                <th>IP</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($auditLog as $a): ?>
                                <tr>
                                    <td class="x-small"><?= $a->created_at ? date('d/m/Y H:i', strtotime($a->created_at)) : '—' ?></td>
                                    <td><?= $a->user_id ? htmlspecialchars($a->last_name . ' ' . $a->first_name) : '—' ?></td>
                                    <td><code class="x-small"><?= htmlspecialchars($a->event) ?></code></td>
                                    <td class="x-small"><?= htmlspecialchars($a->auditable_type) ?> #<?= (int) $a->auditable_id ?></td>
                                    <td class="x-small"><?= htmlspecialchars($a->ip_address ?? '—') ?></td>
                                    <td class="text-end">
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette entrée d\'audit ?');">
                                            <input type="hidden" name="delete_audit_id" value="<?= (int) $a->id ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="admin-empty py-5">
                    <i class="bi bi-clipboard2-data d-block mb-3" style="font-size: 3rem;"></i>
                    <h5>Aucune entrée d'audit</h5>
                    <p class="text-muted mb-0">Ou table <code>audit</code> absente.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
    .breadcrumb { font-size: 0.85rem; margin-bottom: 0; }
    .breadcrumb-item a { color: var(--admin-muted); }
    .breadcrumb-item.active { color: var(--admin-accent); font-weight: 600; }
    .x-small { font-size: 0.75rem; }
    .admin-table th { background: #f8f9fa; padding: 1rem 0.5rem; }
    .admin-table td { padding: 1rem 0.5rem; }
    .nav-tabs .nav-link { color: var(--admin-muted); font-weight: 500; padding: 1rem 1.5rem; }
    .nav-tabs .nav-link.active { background: transparent !important; border-bottom: 3px solid var(--admin-accent) !important; color: var(--admin-sidebar) !important; }
    .dataTables_filter input { border-radius: 8px; border: 1.5px solid #dde1e7; padding: 0.4rem 0.8rem; margin-left: 0.5rem; outline: none; }
    .dataTables_filter input:focus { border-color: var(--admin-sidebar); }
    .dataTables_wrapper .pagination .page-item.active .page-link { background-color: var(--admin-sidebar); border-color: var(--admin-sidebar); color: #fff; }
    .dataTables_wrapper .pagination .page-link { color: var(--admin-sidebar); border-radius: 6px; margin: 0 2px; }
    .dataTables_info { font-size: 0.85rem; color: var(--admin-muted); }
</style>

<footer class="admin-main-footer">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <a href="index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Tableau de bord</a>
        <span class="small text-muted">&copy; <?= date('Y') ?> Expertise</span>
    </div>
</footer>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof $ === 'undefined' || !$.fn.DataTable) return;
        var opts = { language: { url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json" }, order: [[0, "desc"]], pageLength: 10, dom: '<"d-flex justify-content-between align-items-center mb-3"f>t<"d-flex justify-content-between align-items-center mt-3"ip>' };
        if ($('#sessionsTable').length) $('#sessionsTable').DataTable(opts);
        if ($('#activityTable').length) $('#activityTable').DataTable(opts);
        if ($('#auditTable').length) $('#auditTable').DataTable(opts);
    });
</script>

<?php require __DIR__ . '/inc/footer.php'; ?>
