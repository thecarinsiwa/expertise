<?php
/**
 * Gestion des utilisateurs (CRUD) – Administration
 */
$pageTitle = 'Utilisateurs – Administration';
$currentNav = 'users';
require_once __DIR__ . '/inc/auth.php';
require __DIR__ . '/inc/db.php';

$userList = [];
$detail = null;
$dashStats = ['total' => 0, 'active' => 0];
$error = '';
$success = '';
$organisations = [];
$allRoles = [];

if ($pdo) {
    $action = $_GET['action'] ?? 'list';
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    $organisations = $pdo->query("SELECT id, name FROM organisation WHERE is_active = 1 ORDER BY name")->fetchAll();
    $allRoles = $pdo->query("SELECT id, name, code FROM role ORDER BY name")->fetchAll();

    // --- TRAITEMENT POST ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['delete_id'])) {
            $delId = (int) $_POST['delete_id'];
            if ($delId !== (int) ($_SESSION['admin_id'] ?? 0)) {
                $pdo->prepare("DELETE FROM user WHERE id = ?")->execute([$delId]);
                header('Location: user.php?msg=deleted');
                exit;
            }
            $error = 'Vous ne pouvez pas supprimer votre propre compte.';
        }
        if (isset($_POST['save_user'])) {
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $password = $_POST['password'] ?? '';
            $organisation_id = !empty($_POST['organisation_id']) ? (int) $_POST['organisation_id'] : null;
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $role_ids = isset($_POST['role_ids']) && is_array($_POST['role_ids']) ? array_map('intval', array_filter($_POST['role_ids'])) : [];

            if (!$first_name || !$last_name || !$email) {
                $error = 'Nom, prénom et e-mail sont obligatoires.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Adresse e-mail invalide.';
            } elseif ($id > 0) {
                // Mise à jour
                $check = $pdo->prepare("SELECT id FROM user WHERE email = ? AND id != ?");
                $check->execute([$email, $id]);
                if ($check->fetch()) {
                    $error = 'Cette adresse e-mail est déjà utilisée par un autre compte.';
                } else {
                    if ($password !== '') {
                        if (strlen($password) < 8) {
                            $error = 'Le mot de passe doit contenir au moins 8 caractères.';
                        } else {
                            $hash = password_hash($password, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("UPDATE user SET first_name = ?, last_name = ?, email = ?, phone = ?, password_hash = ?, organisation_id = ?, is_active = ? WHERE id = ?");
                            $stmt->execute([$first_name, $last_name, $email, $phone ?: null, $hash, $organisation_id, $is_active, $id]);
                        }
                    } else {
                        $stmt = $pdo->prepare("UPDATE user SET first_name = ?, last_name = ?, email = ?, phone = ?, organisation_id = ?, is_active = ? WHERE id = ?");
                        $stmt->execute([$first_name, $last_name, $email, $phone ?: null, $organisation_id, $is_active, $id]);
                    }
                    if (!$error) {
                        $pdo->prepare("DELETE FROM user_role WHERE user_id = ?")->execute([$id]);
                        if (!empty($role_ids)) {
                            $ins = $pdo->prepare("INSERT INTO user_role (user_id, role_id) VALUES (?, ?)");
                            foreach ($role_ids as $rid) {
                                if ($rid > 0) $ins->execute([$id, $rid]);
                            }
                        }
                        $success = 'Utilisateur mis à jour.';
                        $action = 'view';
                    }
                }
            } else {
                // Création
                if ($password === '') {
                    $error = 'Un mot de passe est obligatoire pour créer un utilisateur.';
                } elseif (strlen($password) < 8) {
                    $error = 'Le mot de passe doit contenir au moins 8 caractères.';
                } else {
                    $check = $pdo->prepare("SELECT id FROM user WHERE email = ?");
                    $check->execute([$email]);
                    if ($check->fetch()) {
                        $error = 'Cette adresse e-mail est déjà utilisée.';
                    } else {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO user (first_name, last_name, email, phone, password_hash, organisation_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$first_name, $last_name, $email, $phone ?: null, $hash, $organisation_id, $is_active]);
                        $newId = (int) $pdo->lastInsertId();
                        if (!empty($role_ids)) {
                            $ins = $pdo->prepare("INSERT INTO user_role (user_id, role_id) VALUES (?, ?)");
                            foreach ($role_ids as $rid) {
                                if ($rid > 0) $ins->execute([$newId, $rid]);
                            }
                        }
                        header('Location: user.php?id=' . $newId . '&msg=created');
                        exit;
                    }
                }
            }
        }
    }

    if ($id > 0) {
        $stmt = $pdo->prepare("
            SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.organisation_id, u.is_active, u.email_verified_at, u.last_login_at, u.created_at, u.updated_at,
                   o.name AS organisation_name
            FROM user u
            LEFT JOIN organisation o ON u.organisation_id = o.id
            WHERE u.id = ?
        ");
        $stmt->execute([$id]);
        $detail = $stmt->fetch();
        if ($detail) {
            $stmtRoles = $pdo->prepare("SELECT r.id, r.name, r.code FROM role r JOIN user_role ur ON r.id = ur.role_id WHERE ur.user_id = ?");
            $stmtRoles->execute([$id]);
            $detail->roles = $stmtRoles->fetchAll();
            $stmtStaff = $pdo->prepare("SELECT s.id FROM staff s WHERE s.user_id = ?");
            $stmtStaff->execute([$id]);
            $detail->staff_count = $stmtStaff->rowCount();
        }
    }
    if (!$detail && $action !== 'add') {
        $dashStats['total'] = (int) $pdo->query("SELECT COUNT(*) FROM user")->fetchColumn();
        $dashStats['active'] = (int) $pdo->query("SELECT COUNT(*) FROM user WHERE is_active = 1")->fetchColumn();
        $userList = $pdo->query("
            SELECT u.id, u.first_name, u.last_name, u.email, u.is_active, u.created_at,
                   o.name AS organisation_name,
                   (SELECT COUNT(*) FROM user_role WHERE user_id = u.id) AS role_count,
                   (SELECT COUNT(*) FROM staff WHERE user_id = u.id) AS staff_count
            FROM user u
            LEFT JOIN organisation o ON u.organisation_id = o.id
            ORDER BY u.last_name ASC, u.first_name ASC
        ")->fetchAll();
    }
}
require __DIR__ . '/inc/header.php';
$isForm = ($action === 'add') || ($action === 'edit' && $detail);
$detailRoleIds = $detail && !empty($detail->roles) ? array_column($detail->roles, 'id') : [];
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Tableau de bord</a></li>
        <li class="breadcrumb-item"><a href="user.php" class="text-decoration-none">Utilisateurs</a></li>
        <?php if ($action === 'add'): ?>
            <li class="breadcrumb-item active">Nouveau</li>
        <?php elseif ($action === 'edit' && $detail): ?>
            <li class="breadcrumb-item active">Modifier</li>
        <?php elseif ($detail): ?>
            <li class="breadcrumb-item active"><?= htmlspecialchars($detail->last_name . ' ' . $detail->first_name) ?></li>
        <?php endif; ?>
    </ol>
</nav>

<header class="admin-header">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h1>
                <?php
                if ($action === 'add') echo 'Nouvel utilisateur';
                elseif ($action === 'edit' && $detail) echo 'Modifier l\'utilisateur';
                elseif ($detail) echo 'Fiche utilisateur';
                else echo 'Utilisateurs';
                ?>
            </h1>
            <p>
                <?php
                if ($detail) echo htmlspecialchars($detail->first_name . ' ' . $detail->last_name);
                elseif ($action === 'add') echo 'Créer un compte utilisateur.';
                else echo 'Gestion des comptes utilisateurs.';
                ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($detail && !$isForm): ?>
                <a href="user.php?action=edit&id=<?= (int) $detail->id ?>" class="btn btn-admin-primary"><i class="bi bi-pencil me-1"></i> Modifier</a>
                <?php if ((int)($detail->id) !== (int)($_SESSION['admin_id'] ?? 0)): ?>
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal"><i class="bi bi-trash me-1"></i> Supprimer</button>
                <?php endif; ?>
                <a href="user.php" class="btn btn-admin-outline ms-auto"><i class="bi bi-arrow-left me-1"></i> Liste</a>
            <?php elseif ($isForm): ?>
                <a href="user.php<?= $id ? '?id=' . $id : '' ?>" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Annuler</a>
            <?php else: ?>
                <a href="user.php?action=add" class="btn btn-admin-primary"><i class="bi bi-person-plus me-1"></i> Nouvel utilisateur</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success">L'utilisateur a été supprimé.</div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
    <div class="alert alert-success">Utilisateur créé.</div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($isForm): ?>
    <div class="admin-card admin-section-card mb-4">
        <form method="POST" action="<?= $id ? 'user.php?action=edit&id=' . $id : 'user.php?action=add' ?>">
            <input type="hidden" name="save_user" value="1">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Prénom *</label>
                    <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($detail->first_name ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Nom *</label>
                    <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($detail->last_name ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">E-mail *</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($detail->email ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Téléphone</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($detail->phone ?? '') ?>" placeholder="+243...">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Mot de passe <?= $id ? '(laisser vide pour ne pas modifier)' : '*' ?></label>
                    <input type="password" name="password" class="form-control" placeholder="<?= $id ? 'Ne pas changer' : 'Minimum 8 caractères' ?>" <?= !$id ? 'required' : '' ?> autocomplete="new-password">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Organisation</label>
                    <select name="organisation_id" class="form-select">
                        <option value="">-- Aucune --</option>
                        <?php foreach ($organisations as $o): ?>
                            <option value="<?= (int) $o->id ?>" <?= ($detail && (int)($detail->organisation_id ?? 0) === (int)$o->id) ? 'selected' : '' ?>><?= htmlspecialchars($o->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check">
                        <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" <?= ($detail && $detail->is_active) || !$detail ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Compte actif</label>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Rôles</label>
                    <div class="border rounded p-3 bg-light" style="max-height: 200px; overflow-y: auto;">
                        <div class="d-flex flex-wrap gap-3">
                            <?php foreach ($allRoles as $r): ?>
                                <div class="form-check">
                                    <input type="checkbox" name="role_ids[]" id="role_<?= (int) $r->id ?>" class="form-check-input" value="<?= (int) $r->id ?>" <?= in_array($r->id, $detailRoleIds) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="role_<?= (int) $r->id ?>"><?= htmlspecialchars($r->name) ?> <code class="small"><?= htmlspecialchars($r->code) ?></code></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (empty($allRoles)): ?>
                            <p class="text-muted small mb-0">Aucun rôle défini.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-admin-primary"><i class="bi bi-check-lg me-1"></i> Enregistrer</button>
                    <a href="user.php<?= $id ? '?id=' . $id : '' ?>" class="btn btn-secondary">Annuler</a>
                </div>
            </div>
        </form>
    </div>
<?php elseif ($detail): ?>
    <div class="admin-card admin-section-card mb-4">
        <h5 class="card-title"><i class="bi bi-person-vcard"></i> Identité</h5>
        <table class="admin-table mb-4">
            <tr><th style="width:180px;">Nom / Prénom</th><td><?= htmlspecialchars($detail->last_name . ' ' . $detail->first_name) ?></td></tr>
            <tr><th>E-mail</th><td><?= htmlspecialchars($detail->email) ?></td></tr>
            <tr><th>Téléphone</th><td><?= htmlspecialchars($detail->phone ?? '—') ?></td></tr>
            <tr><th>Organisation</th><td><?= htmlspecialchars($detail->organisation_name ?? '—') ?></td></tr>
            <tr><th>Statut</th><td><?= $detail->is_active ? '<span class="badge bg-success-subtle text-success border">Actif</span>' : '<span class="badge bg-secondary-subtle text-secondary border">Inactif</span>' ?></td></tr>
            <tr><th>Dernière connexion</th><td><?= $detail->last_login_at ? date('d/m/Y H:i', strtotime($detail->last_login_at)) : '—' ?></td></tr>
            <tr><th>Créé le</th><td><?= $detail->created_at ? date('d/m/Y H:i', strtotime($detail->created_at)) : '—' ?></td></tr>
        </table>
        <?php if (!empty($detail->roles)): ?>
            <h5 class="card-title"><i class="bi bi-shield-lock"></i> Rôles</h5>
            <p class="mb-3">
                <?php foreach ($detail->roles as $r): ?>
                    <a href="roles.php?id=<?= (int) $r->id ?>" class="badge bg-primary me-1 text-decoration-none"><?= htmlspecialchars($r->name) ?></a>
                <?php endforeach; ?>
            </p>
        <?php endif; ?>
        <?php if (!empty($detail->staff_count)): ?>
            <p class="mb-0"><a href="staff.php?user_id=<?= (int) $detail->id ?>" class="btn btn-sm btn-admin-outline"><i class="bi bi-people me-1"></i> Voir la fiche personnel</a></p>
        <?php endif; ?>
        <div class="mt-4 d-flex gap-2">
            <a href="user.php?action=edit&id=<?= (int) $detail->id ?>" class="btn btn-admin-primary"><i class="bi bi-pencil me-1"></i> Modifier</a>
            <?php if ((int)($detail->id) !== (int)($_SESSION['admin_id'] ?? 0)): ?>
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal"><i class="bi bi-trash me-1"></i> Supprimer</button>
            <?php endif; ?>
            <a href="user.php" class="btn btn-admin-outline ms-auto"><i class="bi bi-arrow-left me-1"></i> Liste</a>
        </div>
    </div>
    <?php if ((int)($detail->id) !== (int)($_SESSION['admin_id'] ?? 0)): ?>
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Supprimer cet utilisateur ?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    Le compte de <strong><?= htmlspecialchars($detail->first_name . ' ' . $detail->last_name) ?></strong> (<?= htmlspecialchars($detail->email) ?>) sera supprimé définitivement. Les fiches personnel et rôles liés seront également supprimés.
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
<?php elseif (count($userList) > 0): ?>
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-4">
            <div class="admin-card text-center p-3 h-100">
                <div class="text-muted small text-uppercase mb-1 fw-bold">Total utilisateurs</div>
                <div class="h3 mb-0 fw-bold"><?= $dashStats['total'] ?></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="admin-card text-center p-3 h-100">
                <div class="text-muted small text-uppercase mb-1 fw-bold">Actifs</div>
                <div class="h3 mb-0 fw-bold text-success"><?= $dashStats['active'] ?></div>
            </div>
        </div>
    </div>
    <div class="admin-card admin-section-card">
        <div class="table-responsive">
            <table class="admin-table table table-hover" id="userTable">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Organisation</th>
                        <th>Rôles</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userList as $u): ?>
                        <tr>
                            <td>
                                <h6 class="mb-0"><a href="user.php?id=<?= (int) $u->id ?>"><?= htmlspecialchars($u->last_name . ' ' . $u->first_name) ?></a></h6>
                                <span class="text-muted x-small"><?= htmlspecialchars($u->email) ?></span>
                            </td>
                            <td><?= htmlspecialchars($u->organisation_name ?? '—') ?></td>
                            <td><span class="badge bg-primary"><?= (int) $u->role_count ?></span> <?= $u->is_active ? '<span class="badge bg-success-subtle text-success border x-small">Actif</span>' : '' ?></td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                        <li><a class="dropdown-item" href="user.php?id=<?= (int) $u->id ?>"><i class="bi bi-eye me-2"></i> Voir</a></li>
                                        <li><a class="dropdown-item" href="user.php?action=edit&id=<?= (int) $u->id ?>"><i class="bi bi-pencil me-2"></i> Modifier</a></li>
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
            <i class="bi bi-people d-block mb-3" style="font-size: 3rem;"></i>
            <h5>Aucun utilisateur</h5>
            <p class="text-muted mb-4">Créez le premier compte utilisateur.</p>
            <a href="user.php?action=add" class="btn btn-admin-primary"><i class="bi bi-person-plus me-1"></i> Nouvel utilisateur</a>
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
</style>

<footer class="admin-main-footer">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <a href="index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Tableau de bord</a>
        <span class="small text-muted">&copy; <?= date('Y') ?> Expertise</span>
    </div>
</footer>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('userTable') && typeof $ !== 'undefined' && $.fn.DataTable) {
            $('#userTable').DataTable({
                language: { url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json" },
                order: [[0, "asc"]],
                pageLength: 10,
                dom: '<"d-flex justify-content-between align-items-center mb-3"f>t<"d-flex justify-content-between align-items-center mt-3"ip>'
            });
        }
    });
</script>

<?php require __DIR__ . '/inc/footer.php'; ?>
