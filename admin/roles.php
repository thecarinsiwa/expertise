<?php
$pageTitle = 'Rôles & Accès – Administration';
$currentNav = 'roles';
require_once __DIR__ . '/inc/auth.php';
require_permission('admin.roles.view');
require __DIR__ . '/inc/db.php';

$rolesList = [];
$detail = null;
$dashStats = ['total' => 0];
$error = '';
$success = '';
$organisations = [];
$allPermissions = [];

if ($pdo) {
    $action = $_GET['action'] ?? 'list';
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    $organisations = $pdo->query("SELECT id, name FROM organisation WHERE is_active = 1 ORDER BY name")->fetchAll();
    $allPermissions = $pdo->query("SELECT id, name, code, module FROM permission ORDER BY module, code")->fetchAll();

    // --- TRAITEMENT POST ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['delete_id'])) {
            require_permission('admin.roles.delete');
            $delId = (int) $_POST['delete_id'];
            $pdo->prepare("DELETE FROM role WHERE id = ?")->execute([$delId]);
            header('Location: roles.php?msg=deleted');
            exit;
        }
        if (isset($_POST['save_role'])) {
            $name = trim($_POST['name'] ?? '');
            $code = trim($_POST['code'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $organisation_id = !empty($_POST['organisation_id']) ? (int) $_POST['organisation_id'] : null;
            $permission_ids = isset($_POST['permission_ids']) && is_array($_POST['permission_ids']) ? array_map('intval', $_POST['permission_ids']) : [];

            if (!$name || !$code) {
                $error = 'Nom et code sont obligatoires.';
            } else {
                if ($id > 0) {
                    require_permission('admin.roles.modify');
                    $stmt = $pdo->prepare("UPDATE role SET name = ?, code = ?, description = ?, organisation_id = ? WHERE id = ?");
                    $stmt->execute([$name, $code, $description ?: null, $organisation_id, $id]);
                    $pdo->prepare("DELETE FROM role_permission WHERE role_id = ?")->execute([$id]);
                    if (!empty($permission_ids)) {
                        $ins = $pdo->prepare("INSERT INTO role_permission (role_id, permission_id) VALUES (?, ?)");
                        foreach ($permission_ids as $pid) {
                            if ($pid > 0) $ins->execute([$id, $pid]);
                        }
                    }
                    $success = 'Rôle mis à jour.';
                    $action = 'view';
                } else {
                    require_permission('admin.roles.add');
                    $stmt = $pdo->prepare("INSERT INTO role (name, code, description, organisation_id, is_system) VALUES (?, ?, ?, ?, 0)");
                    $stmt->execute([$name, $code, $description ?: null, $organisation_id]);
                    $newId = (int) $pdo->lastInsertId();
                    if (!empty($permission_ids)) {
                        $ins = $pdo->prepare("INSERT INTO role_permission (role_id, permission_id) VALUES (?, ?)");
                        foreach ($permission_ids as $pid) {
                            if ($pid > 0) $ins->execute([$newId, $pid]);
                        }
                    }
                    header('Location: roles.php?id=' . $newId . '&msg=created');
                    exit;
                }
            }
        }
    }

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
    if (!$detail && $action !== 'add') {
        $dashStats['total'] = (int) $pdo->query("SELECT COUNT(*) FROM role")->fetchColumn();
        $stmt = $pdo->query("
            SELECT r.id, r.name, r.code, r.is_system, r.created_at,
                   o.name AS organisation_name,
                   (SELECT COUNT(*) FROM role_permission WHERE role_id = r.id) AS perm_count,
                   (SELECT COUNT(*) FROM user_role WHERE role_id = r.id) AS user_count
            FROM role r
            LEFT JOIN organisation o ON r.organisation_id = o.id
            ORDER BY r.name ASC
        ");
        if ($stmt) $rolesList = $stmt->fetchAll();
    }
}
require __DIR__ . '/inc/header.php';
$isForm = ($action === 'add') || ($action === 'edit' && $detail);
$detailPermIds = $detail && !empty($detail->permissions) ? array_column($detail->permissions, 'id') : [];
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Tableau de bord</a></li>
        <li class="breadcrumb-item"><a href="roles.php" class="text-decoration-none">Rôles & Accès</a></li>
        <?php if ($action === 'add'): ?>
            <li class="breadcrumb-item active">Nouveau</li>
        <?php elseif ($action === 'edit' && $detail): ?>
            <li class="breadcrumb-item active">Modifier</li>
        <?php elseif ($detail): ?>
            <li class="breadcrumb-item active"><?= htmlspecialchars($detail->name) ?></li>
        <?php endif; ?>
    </ol>
</nav>

<header class="admin-header">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h1>
                <?php
                if ($action === 'add') echo 'Nouveau rôle';
                elseif ($action === 'edit' && $detail) echo 'Modifier le rôle';
                elseif ($detail) echo 'Détail du rôle';
                else echo 'Rôles & Accès';
                ?>
            </h1>
            <p><?= $detail ? htmlspecialchars($detail->name) : ($action === 'add' ? 'Créer un rôle et associer des permissions.' : 'Gestion des rôles et permissions.'); ?></p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($detail && !$isForm): ?>
                <?php if (!$detail->is_system): ?>
                    <?php if (has_permission('admin.roles.modify')): ?><a href="roles.php?action=edit&id=<?= (int) $detail->id ?>" class="btn btn-admin-primary"><i class="bi bi-pencil me-1"></i> Modifier</a><?php endif; ?>
                    <?php if (has_permission('admin.roles.delete')): ?><button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteRoleModal"><i class="bi bi-trash me-1"></i> Supprimer</button><?php endif; ?>
                <?php endif; ?>
                <a href="roles.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Liste</a>
            <?php elseif ($isForm): ?>
                <a href="roles.php<?= $id ? '?id=' . $id : '' ?>" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Annuler</a>
            <?php else: ?>
                <?php if (has_permission('admin.roles.add')): ?><a href="roles.php?action=add" class="btn btn-admin-primary"><i class="bi bi-plus-lg me-1"></i> Créer un rôle</a><?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success">Le rôle a été supprimé.</div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
    <div class="alert alert-success">Rôle créé.</div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($isForm && (($action === 'add' && has_permission('admin.roles.add')) || ($action === 'edit' && has_permission('admin.roles.modify')))): ?>
    <!-- Formulaire Ajout / Édition rôle -->
    <div class="admin-card admin-section-card mb-4">
        <form method="POST" action="<?= $id ? 'roles.php?action=edit&id=' . $id : 'roles.php?action=add' ?>">
            <input type="hidden" name="save_role" value="1">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Nom du rôle *</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($detail->name ?? '') ?>" required placeholder="ex: Administrateur">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Code *</label>
                    <input type="text" name="code" class="form-control" value="<?= htmlspecialchars($detail->code ?? '') ?>" required placeholder="ex: admin" <?= $detail && $detail->is_system ? 'readonly' : '' ?>>
                    <div class="form-text">Identifiant technique unique (minuscules, sans espaces).</div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Description</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Optionnel"><?= htmlspecialchars($detail->description ?? '') ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Organisation</label>
                    <select name="organisation_id" class="form-select">
                        <option value="">-- Toutes / Global --</option>
                        <?php foreach ($organisations as $o): ?>
                            <option value="<?= (int) $o->id ?>" <?= ($detail && (int)($detail->organisation_id ?? 0) === (int)$o->id) ? 'selected' : '' ?>><?= htmlspecialchars($o->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Permissions</label>
                    <div class="border rounded p-3 bg-light permissions-block">
                        <?php if (!empty($allPermissions)): ?>
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-3 pb-2 border-bottom">
                            <input type="text" id="permFilter" class="form-control form-control-sm" placeholder="Filtrer les permissions…" style="max-width: 220px;">
                            <span class="text-muted small">|</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="permCheckAll">Tout cocher</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="permUncheckAll">Tout décocher</button>
                        </div>
                        <div id="permissionsList" style="max-height: 380px; overflow-y: auto;">
                        <?php
                        $byModule = [];
                        foreach ($allPermissions as $p) {
                            $m = $p->module ?: 'Autre';
                            if (!isset($byModule[$m])) $byModule[$m] = [];
                            $byModule[$m][] = $p;
                        }
                        $actionOrder = ['view' => 0, 'add' => 1, 'modify' => 2, 'delete' => 3];
                        foreach ($byModule as $module => $perms):
                            usort($perms, function ($a, $b) use ($actionOrder) {
                                $suffixA = preg_replace('/^admin\.[^.]+\./', '', $a->code);
                                $suffixB = preg_replace('/^admin\.[^.]+\./', '', $b->code);
                                return ($actionOrder[$suffixA] ?? 99) <=> ($actionOrder[$suffixB] ?? 99);
                            });
                        ?>
                            <div class="permission-module mb-3" data-module="<?= htmlspecialchars($module) ?>">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <strong class="d-block"><?= htmlspecialchars($module) ?></strong>
                                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none perm-module-check" data-module="<?= htmlspecialchars($module) ?>" title="Tout cocher pour ce bloc">Cocher tout</button>
                                    <span class="text-muted">·</span>
                                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none perm-module-uncheck" data-module="<?= htmlspecialchars($module) ?>" title="Tout décocher pour ce bloc">Décocher tout</button>
                                </div>
                                <div class="d-flex flex-wrap gap-2 gap-md-3">
                                    <?php foreach ($perms as $p): ?>
                                        <div class="form-check permission-item" data-name="<?= htmlspecialchars(mb_strtolower($p->name)) ?>" data-code="<?= htmlspecialchars(mb_strtolower($p->code)) ?>">
                                            <input type="checkbox" name="permission_ids[]" id="perm_<?= (int) $p->id ?>" class="form-check-input perm-cb" value="<?= (int) $p->id ?>" data-module="<?= htmlspecialchars($module) ?>" <?= in_array($p->id, $detailPermIds) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="perm_<?= (int) $p->id ?>" title="Code : <?= htmlspecialchars($p->code) ?>"><?= htmlspecialchars($p->name) ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                            <p class="text-muted small mb-0">Aucune permission définie en base. Créez-en via le schéma ou les paramètres.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-admin-primary"><i class="bi bi-check-lg me-1"></i> Enregistrer</button>
                    <a href="roles.php<?= $id ? '?id=' . $id : '' ?>" class="btn btn-secondary">Annuler</a>
                </div>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php if (!$detail && count($rolesList) > 0): ?>
    <!-- Cartes statistiques -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="admin-card text-center p-3 h-100">
                <div class="text-muted small text-uppercase mb-1 fw-bold">Total rôles</div>
                <div class="h3 mb-0 fw-bold"><?= $dashStats['total'] ?></div>
                <div class="mt-2"><span class="badge bg-secondary-subtle text-secondary border">Définis</span></div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($detail && !$isForm): ?>
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
                        <span class="badge bg-secondary-subtle text-secondary border me-1" title="Code : <?= htmlspecialchars($p->code) ?>"><?= htmlspecialchars($p->name) ?></span>
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
                    <li><a href="staff.php?user_id=<?= (int) $u->id ?>"><?= htmlspecialchars($u->last_name . ' ' . $u->first_name) ?></a> – <span class="text-muted"><?= htmlspecialchars($u->email) ?></span></li>
                <?php endforeach; ?>
            </ul>
            <p class="small text-muted mt-2">Le lien ouvre la fiche personnel si un enregistrement staff existe ; sinon la liste du personnel.</p>
        <?php else: ?>
            <p class="text-muted">Aucun utilisateur avec ce rôle.</p>
        <?php endif; ?>

        <div class="mt-4 d-flex gap-2">
            <?php if (!$detail->is_system): ?>
                <?php if (has_permission('admin.roles.modify')): ?><a href="roles.php?action=edit&id=<?= (int) $detail->id ?>" class="btn btn-admin-primary"><i class="bi bi-pencil me-1"></i> Modifier</a><?php endif; ?>
                <?php if (has_permission('admin.roles.delete')): ?><button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteRoleModal"><i class="bi bi-trash me-1"></i> Supprimer</button><?php endif; ?>
            <?php endif; ?>
            <a href="roles.php" class="btn btn-admin-outline ms-auto"><i class="bi bi-arrow-left me-1"></i> Liste</a>
        </div>
    </div>

    <?php if (!$detail->is_system && has_permission('admin.roles.delete')): ?>
    <div class="modal fade" id="deleteRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Supprimer ce rôle ?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    Le rôle <strong><?= htmlspecialchars($detail->name) ?></strong> sera supprimé. Les affectations utilisateurs à ce rôle seront perdues. Cette action est irréversible.
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="delete_id" value="<?= (int) $detail->id ?>">
                        <button type="submit" class="btn btn-danger">Supprimer définitivement</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
<?php elseif (count($rolesList) > 0): ?>
    <!-- Liste des rôles -->
    <div class="admin-card admin-section-card">
        <div class="table-responsive">
            <table class="admin-table table table-hover" id="rolesTable">
                <thead>
                    <tr>
                        <th>Rôle</th>
                        <th>Organisation</th>
                        <th>Permissions</th>
                        <th>Utilisateurs</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rolesList as $r): ?>
                        <tr>
                            <td>
                                <h6 class="mb-0"><a href="roles.php?id=<?= (int) $r->id ?>"><?= htmlspecialchars($r->name) ?></a></h6>
                                <div class="d-flex gap-2 align-items-center mt-1">
                                    <span class="text-muted x-small"><code><?= htmlspecialchars($r->code) ?></code></span>
                                    <?php if ($r->is_system): ?>
                                        <span class="badge bg-warning-subtle text-warning border x-small">Système</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><span class="text-muted"><?= htmlspecialchars($r->organisation_name ?? '—') ?></span></td>
                            <td><span class="badge bg-primary"><?= (int) $r->perm_count ?></span></td>
                            <td><span class="badge bg-info-subtle text-info border"><?= (int) $r->user_count ?></span></td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                        <li><a class="dropdown-item" href="roles.php?id=<?= (int) $r->id ?>"><i class="bi bi-eye me-2"></i> Voir</a></li>
                                        <?php if (has_permission('admin.roles.modify')): ?><li><a class="dropdown-item" href="roles.php?action=edit&id=<?= (int) $r->id ?>"><i class="bi bi-pencil me-2"></i> Modifier</a></li><?php endif; ?>
                                        <?php if (!$r->is_system && has_permission('admin.roles.delete')): ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" onsubmit="return confirm('Supprimer ce rôle ?');">
                                                <input type="hidden" name="delete_id" value="<?= (int) $r->id ?>">
                                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i> Supprimer</button>
                                            </form>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="admin-card admin-section-card">
        <div class="admin-empty py-5">
            <i class="bi bi-shield-lock d-block mb-3" style="font-size: 3rem;"></i>
            <h5>Aucun rôle défini</h5>
            <p class="text-muted mb-4">Les rôles peuvent être créés via le schéma de la base ou les paramètres applicatifs.</p>
        </div>
    </div>
<?php endif; ?>

<style>
    .breadcrumb { font-size: 0.85rem; margin-bottom: 0; }
    .breadcrumb-item a { color: var(--admin-muted); }
    .breadcrumb-item.active { color: var(--admin-accent); font-weight: 600; }
    .x-small { font-size: 0.75rem; }
    .admin-table th { background: #f8f9fa; padding: 1rem 0.5rem; }
    .admin-table td { padding: 1rem 0.5rem; }
    .dataTables_filter input { border-radius: 8px; border: 1.5px solid #dde1e7; padding: 0.4rem 0.8rem; margin-left: 0.5rem; outline: none; }
    .dataTables_filter input:focus { border-color: var(--admin-sidebar); }
    .dataTables_wrapper .pagination .page-item.active .page-link { background-color: var(--admin-sidebar); border-color: var(--admin-sidebar); color: #fff; }
    .dataTables_wrapper .pagination .page-link { color: var(--admin-sidebar); border-radius: 6px; margin: 0 2px; }
    .dataTables_info { font-size: 0.85rem; color: var(--admin-muted); }
    .permissions-block .permission-item { transition: opacity .15s ease; }
    .permissions-block .permission-item.perm-hidden { display: none !important; }
    .permissions-block .permission-module.perm-module-hidden { display: none !important; }
</style>

<footer class="admin-main-footer">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <a href="index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Tableau de bord</a>
        <span class="small text-muted">&copy; <?= date('Y') ?> Expertise</span>
    </div>
</footer>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('rolesTable') && typeof $ !== 'undefined' && $.fn.DataTable) {
            $('#rolesTable').DataTable({
                language: { url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json" },
                order: [[0, "asc"]],
                pageLength: 10,
                dom: '<"d-flex justify-content-between align-items-center mb-3"f>t<"d-flex justify-content-between align-items-center mt-3"ip>'
            });
        }

        var permFilter = document.getElementById('permFilter');
        var permissionsList = document.getElementById('permissionsList');
        if (permFilter && permissionsList) {
            permFilter.addEventListener('input', function() {
                var q = (this.value || '').trim().toLowerCase();
                var modules = permissionsList.querySelectorAll('.permission-module');
                modules.forEach(function(mod) {
                    var items = mod.querySelectorAll('.permission-item');
                    var visibleCount = 0;
                    items.forEach(function(item) {
                        var show = !q || (item.dataset.name && item.dataset.name.indexOf(q) !== -1) || (item.dataset.code && item.dataset.code.indexOf(q) !== -1);
                        item.classList.toggle('perm-hidden', !show);
                        if (show) visibleCount++;
                    });
                    mod.classList.toggle('perm-module-hidden', visibleCount === 0);
                });
            });

            var checkAll = document.getElementById('permCheckAll');
            var uncheckAll = document.getElementById('permUncheckAll');
            if (checkAll) checkAll.addEventListener('click', function() {
                permissionsList.querySelectorAll('.perm-cb').forEach(function(cb) {
                    if (!cb.closest('.permission-item') || !cb.closest('.permission-item').classList.contains('perm-hidden')) cb.checked = true;
                });
            });
            if (uncheckAll) uncheckAll.addEventListener('click', function() {
                permissionsList.querySelectorAll('.perm-cb').forEach(function(cb) { cb.checked = false; });
            });

            permissionsList.querySelectorAll('.perm-module-check').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var mod = this.dataset.module;
                    permissionsList.querySelectorAll('.permission-module[data-module="' + mod + '"] .perm-cb').forEach(function(cb) {
                        if (!cb.closest('.permission-item') || !cb.closest('.permission-item').classList.contains('perm-hidden')) cb.checked = true;
                    });
                });
            });
            permissionsList.querySelectorAll('.perm-module-uncheck').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var mod = this.dataset.module;
                    permissionsList.querySelectorAll('.permission-module[data-module="' + mod + '"] .perm-cb').forEach(function(cb) { cb.checked = false; });
                });
            });
        }
    });
</script>

<?php require __DIR__ . '/inc/footer.php'; ?>
