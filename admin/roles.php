<?php
$pageTitle = 'Rôles & Accès – Administration';
$currentNav = 'roles';
require_once __DIR__ . '/inc/auth.php';
require __DIR__ . '/inc/db.php';

$rolesList = [];
$detail = null;

if ($pdo) {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id > 0) {
        $stmt = $pdo->prepare("
            SELECT r.id, r.name, r.code, r.description, r.is_system, r.organisation_id, r.created_at,
                   o.name AS organisation_name
            FROM role r
            LEFT JOIN organisation o ON r.organisation_id = o.id
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        $detail = $stmt->fetch();
        if ($detail) {
            $stmtPerm = $pdo->prepare("
                SELECT p.id, p.name, p.code, p.module
                FROM permission p
                JOIN role_permission rp ON p.id = rp.permission_id
                WHERE rp.role_id = ?
                ORDER BY p.module ASC, p.code ASC
            ");
            $stmtPerm->execute([$id]);
            $detail->permissions = $stmtPerm->fetchAll();
            $stmtUsers = $pdo->prepare("
                SELECT u.id, u.first_name, u.last_name, u.email
                FROM user u
                JOIN user_role ur ON u.id = ur.user_id
                WHERE ur.role_id = ?
                ORDER BY u.last_name ASC
            ");
            $stmtUsers->execute([$id]);
            $detail->users = $stmtUsers->fetchAll();
        }
    }
    if (!$detail) {
        $stmt = $pdo->query("
            SELECT r.id, r.name, r.code, r.is_system, r.created_at,
                   o.name AS organisation_name,
                   (SELECT COUNT(*) FROM role_permission WHERE role_id = r.id) AS perm_count,
                   (SELECT COUNT(*) FROM user_role WHERE role_id = r.id) AS user_count
            FROM role r
            LEFT JOIN organisation o ON r.organisation_id = o.id
            ORDER BY r.name ASC
        ");
        if ($stmt) {
            $rolesList = $stmt->fetchAll();
        }
    }
}
require __DIR__ . '/inc/header.php';
?>

<header class="admin-header">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h1><?= $detail ? 'Détail du rôle' : 'Rôles & Accès' ?></h1>
            <p><?= $detail ? htmlspecialchars($detail->name) : 'Gestion des rôles et permissions.' ?></p>
        </div>
        <?php if ($detail): ?>
            <a href="roles.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Retour à la liste</a>
        <?php endif; ?>
    </div>
</header>

<?php if ($detail): ?>
    <div class="admin-card admin-section-card mb-4">
        <h5 class="card-title"><i class="bi bi-shield-lock"></i> Rôle</h5>
        <table class="admin-table mb-4">
            <tr>
                <th style="width:180px;">Nom</th>
                <td><?= htmlspecialchars($detail->name) ?></td>
            </tr>
            <tr>
                <th>Code</th>
                <td><code><?= htmlspecialchars($detail->code) ?></code></td>
            </tr>
            <tr>
                <th>Organisation</th>
                <td><?= htmlspecialchars($detail->organisation_name ?? 'Toutes') ?></td>
            </tr>
            <tr>
                <th>Rôle système</th>
                <td><?= $detail->is_system ? 'Oui' : 'Non' ?></td>
            </tr>
            <?php if (!empty($detail->description)): ?>
            <tr>
                <th>Description</th>
                <td><?= nl2br(htmlspecialchars($detail->description)) ?></td>
            </tr>
            <?php endif; ?>
        </table>

        <h5 class="card-title"><i class="bi bi-key"></i> Permissions (<?= count($detail->permissions) ?>)</h5>
        <?php if (!empty($detail->permissions)): ?>
            <?php
            $byModule = [];
            foreach ($detail->permissions as $p) {
                $m = $p->module ?: 'Autre';
                if (!isset($byModule[$m])) $byModule[$m] = [];
                $byModule[$m][] = $p;
            }
            ?>
            <?php foreach ($byModule as $module => $perms): ?>
                <p class="mb-1"><strong><?= htmlspecialchars($module) ?></strong></p>
                <p class="mb-3">
                    <?php foreach ($perms as $p): ?>
                        <span class="badge bg-secondary me-1" title="<?= htmlspecialchars($p->name) ?>"><?= htmlspecialchars($p->code) ?></span>
                    <?php endforeach; ?>
                </p>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-muted">Aucune permission associée.</p>
        <?php endif; ?>

        <h5 class="card-title mt-3"><i class="bi bi-people"></i> Utilisateurs avec ce rôle (<?= count($detail->users) ?>)</h5>
        <?php if (!empty($detail->users)): ?>
            <ul class="list-unstyled mb-0">
                <?php foreach ($detail->users as $u): ?>
                    <li><a href="staff.php?user_id=<?= (int) $u->id ?>"><?= htmlspecialchars($u->last_name . ' ' . $u->first_name) ?></a> – <?= htmlspecialchars($u->email) ?></li>
                <?php endforeach; ?>
            </ul>
            <p class="small text-muted mt-2">Le lien ouvre la fiche personnel si un enregistrement staff existe pour cet utilisateur ; sinon la liste du personnel s'affiche.</p>
        <?php else: ?>
            <p class="text-muted">Aucun utilisateur avec ce rôle.</p>
        <?php endif; ?>

        <div class="mt-4">
            <a href="roles.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Retour à la liste</a>
        </div>
    </div>
<?php elseif (count($rolesList) > 0): ?>
    <div class="admin-card admin-section-card">
        <table class="admin-table table-hover">
            <thead>
                <tr>
                    <th>Rôle</th>
                    <th>Code</th>
                    <th>Organisation</th>
                    <th>Permissions</th>
                    <th>Utilisateurs</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rolesList as $r): ?>
                    <tr>
                        <td><a href="roles.php?id=<?= (int) $r->id ?>"><?= htmlspecialchars($r->name) ?></a></td>
                        <td><code><?= htmlspecialchars($r->code) ?></code></td>
                        <td><?= htmlspecialchars($r->organisation_name ?? '—') ?></td>
                        <td><?= (int) $r->perm_count ?></td>
                        <td><?= (int) $r->user_count ?></td>
                        <td><a href="roles.php?id=<?= (int) $r->id ?>" class="btn btn-sm btn-light border"><i class="bi bi-eye"></i></a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="admin-card admin-section-card">
        <div class="admin-empty">
            <i class="bi bi-shield-lock d-block"></i>
            Aucun rôle défini. Les rôles peuvent être créés via le schéma ou les paramètres.
        </div>
    </div>
<?php endif; ?>

<footer class="admin-main-footer">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <a href="index.php" class="text-muted text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Tableau de bord</a>
        <span><?= date('Y') ?></span>
    </div>
</footer>

<?php require __DIR__ . '/inc/footer.php'; ?>
