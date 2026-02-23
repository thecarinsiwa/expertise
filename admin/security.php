<?php
$pageTitle = 'Sécurité – Administration';
$currentNav = 'security';
require_once __DIR__ . '/inc/auth.php';
require __DIR__ . '/inc/db.php';

$sessions = [];
$activityLog = [];
$auditLog = [];
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'sessions';

if ($pdo) {
    try {
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

<header class="admin-header">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h1>Sécurité</h1>
            <p>Sessions, journal d'activité et audit.</p>
        </div>
    </div>
</header>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link<?= $tab === 'sessions' ? ' active' : '' ?>" href="security.php?tab=sessions">Sessions</a>
    </li>
    <li class="nav-item">
        <a class="nav-link<?= $tab === 'activity' ? ' active' : '' ?>" href="security.php?tab=activity">Journal d'activité</a>
    </li>
    <li class="nav-item">
        <a class="nav-link<?= $tab === 'audit' ? ' active' : '' ?>" href="security.php?tab=audit">Audit</a>
    </li>
</ul>

<?php if ($tab === 'sessions'): ?>
    <div class="admin-card admin-section-card">
        <h5 class="card-title"><i class="bi bi-laptop"></i> Sessions actives (<?= count($sessions) ?>)</h5>
        <?php if (count($sessions) > 0): ?>
            <table class="admin-table table-hover">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>IP</th>
                        <th>Dernière activité</th>
                        <th>Créée le</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sessions as $s): ?>
                        <tr>
                            <td>
                                <?php if ($s->user_id): ?>
                                    <a href="staff.php?user_id=<?= (int) $s->user_id ?>"><?= htmlspecialchars($s->last_name . ' ' . $s->first_name) ?></a>
                                    <br><small class="text-muted"><?= htmlspecialchars($s->email) ?></small>
                                <?php else: ?>
                                    — (user_id <?= (int) $s->user_id ?>)
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($s->ip_address ?? '—') ?></td>
                            <td><?= $s->last_activity ? date('d/m/Y H:i', $s->last_activity) : '—' ?></td>
                            <td><?= $s->created_at ? date('d/m/Y H:i', strtotime($s->created_at)) : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="admin-empty">
                <i class="bi bi-laptop d-block"></i>
                Aucune session enregistrée ou table absente.
            </div>
        <?php endif; ?>
    </div>
<?php elseif ($tab === 'activity'): ?>
    <div class="admin-card admin-section-card">
        <h5 class="card-title"><i class="bi bi-journal-text"></i> Journal d'activité (<?= count($activityLog) ?>)</h5>
        <?php if (count($activityLog) > 0): ?>
            <table class="admin-table table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Utilisateur</th>
                        <th>Action</th>
                        <th>Sujet</th>
                        <th>Description</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activityLog as $a): ?>
                        <tr>
                            <td><?= $a->created_at ? date('d/m/Y H:i', strtotime($a->created_at)) : '—' ?></td>
                            <td>
                                <?php if ($a->user_id): ?>
                                    <?= htmlspecialchars($a->last_name . ' ' . $a->first_name) ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td><code><?= htmlspecialchars($a->action) ?></code></td>
                            <td><?= htmlspecialchars($a->subject_type ?? '—') ?> #<?= (int) ($a->subject_id ?? 0) ?></td>
                            <td><?= htmlspecialchars(mb_substr($a->description ?? '', 0, 80)) ?><?= mb_strlen($a->description ?? '') > 80 ? '…' : '' ?></td>
                            <td><?= htmlspecialchars($a->ip_address ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="admin-empty">
                <i class="bi bi-journal-text d-block"></i>
                Aucune entrée dans le journal d'activité ou table absente.
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="admin-card admin-section-card">
        <h5 class="card-title"><i class="bi bi-clipboard2-data"></i> Audit (<?= count($auditLog) ?>)</h5>
        <?php if (count($auditLog) > 0): ?>
            <table class="admin-table table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Utilisateur</th>
                        <th>Événement</th>
                        <th>Entité</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($auditLog as $a): ?>
                        <tr>
                            <td><?= $a->created_at ? date('d/m/Y H:i', strtotime($a->created_at)) : '—' ?></td>
                            <td><?= $a->user_id ? htmlspecialchars($a->last_name . ' ' . $a->first_name) : '—' ?></td>
                            <td><code><?= htmlspecialchars($a->event) ?></code></td>
                            <td><?= htmlspecialchars($a->auditable_type) ?> #<?= (int) $a->auditable_id ?></td>
                            <td><?= htmlspecialchars($a->ip_address ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="admin-empty">
                <i class="bi bi-clipboard2-data d-block"></i>
                Aucune entrée d'audit ou table absente.
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<footer class="admin-main-footer">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <a href="index.php" class="text-muted text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Tableau de bord</a>
        <span><?= date('Y') ?></span>
    </div>
</footer>

<?php require __DIR__ . '/inc/footer.php'; ?>
