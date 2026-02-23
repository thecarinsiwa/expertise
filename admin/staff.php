<?php
$pageTitle = 'Personnel – Administration';
$currentNav = 'staff';
require_once __DIR__ . '/inc/auth.php';
require __DIR__ . '/inc/db.php';

$staffList = [];
$detail = null;
$dashStats = ['total' => 0, 'active' => 0, 'inactive' => 0];
$error = '';
$success = '';
$employmentTypes = [
    'full_time' => 'Temps plein',
    'part_time' => 'Temps partiel',
    'contract' => 'Contrat',
    'intern' => 'Stagiaire',
    'freelance' => 'Freelance',
];
$organisations = [];
$allUsers = [];

if ($pdo) {
    $action = $_GET['action'] ?? 'list';
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

    $organisations = $pdo->query("SELECT id, name FROM organisation WHERE is_active = 1 ORDER BY name")->fetchAll();
    $allUsers = $pdo->query("SELECT id, first_name, last_name, email FROM user ORDER BY last_name, first_name")->fetchAll();

    // Redirection user_id -> id (fiche staff)
    if ($userId > 0 && $action === 'list') {
        $stmt = $pdo->prepare("SELECT id FROM staff WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if ($row) {
            header('Location: staff.php?id=' . (int) $row->id);
            exit;
        }
    }

    // --- TRAITEMENT POST ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['delete_id'])) {
            $delId = (int) $_POST['delete_id'];
            $pdo->prepare("DELETE FROM staff WHERE id = ?")->execute([$delId]);
            header('Location: staff.php?msg=deleted');
            exit;
        }
        if (isset($_POST['save_staff'])) {
            $user_id = (int) ($_POST['user_id'] ?? 0);
            $organisation_id = (int) ($_POST['organisation_id'] ?? 0) ?: null;
            $employee_number = trim($_POST['employee_number'] ?? '');
            $hire_date = !empty($_POST['hire_date']) ? $_POST['hire_date'] : null;
            $employment_type = $_POST['employment_type'] ?? 'full_time';
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            if (!in_array($employment_type, array_keys($employmentTypes))) $employment_type = 'full_time';
            if (!$organisation_id && $organisations) $organisation_id = (int) $organisations[0]->id;
            if (!$organisation_id) $organisation_id = 1;

            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE staff SET organisation_id = ?, employee_number = ?, hire_date = ?, employment_type = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$organisation_id, $employee_number ?: null, $hire_date, $employment_type, $is_active, $id]);
                $success = 'Fiche personnel mise à jour.';
                $action = 'view';
            } else {
                if (!$user_id) $error = 'Veuillez sélectionner un utilisateur.';
                else {
                    $exists = $pdo->prepare("SELECT id FROM staff WHERE user_id = ? AND organisation_id = ?");
                    $exists->execute([$user_id, $organisation_id]);
                    if ($exists->fetch()) $error = 'Cet utilisateur est déjà membre du personnel pour cette organisation.';
                    else {
                        $stmt = $pdo->prepare("INSERT INTO staff (user_id, organisation_id, employee_number, hire_date, employment_type, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$user_id, $organisation_id, $employee_number ?: null, $hire_date, $employment_type, $is_active]);
                        header('Location: staff.php?id=' . $pdo->lastInsertId() . '&msg=created');
                        exit;
                    }
                }
            }
        }
    }

    if ($id > 0) {
        $stmt = $pdo->prepare("
            SELECT s.id, s.user_id, s.organisation_id, s.employee_number, s.hire_date, s.employment_type, s.is_active, s.created_at,
                   u.first_name, u.last_name, u.email, u.phone, u.is_active AS user_active, u.last_login_at,
                   o.name AS organisation_name
            FROM staff s
            JOIN user u ON s.user_id = u.id
            LEFT JOIN organisation o ON s.organisation_id = o.id
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        $detail = $stmt->fetch();
        if ($detail) {
            $stmtAssign = $pdo->prepare("
                SELECT a.id, a.start_date, a.end_date, a.is_primary, p.title AS position_title, u.name AS unit_name
                FROM assignment a
                LEFT JOIN position p ON a.position_id = p.id
                LEFT JOIN unit u ON a.unit_id = u.id
                WHERE a.staff_id = ?
                ORDER BY a.is_primary DESC, a.start_date DESC
            ");
            $stmtAssign->execute([$id]);
            $detail->assignments = $stmtAssign->fetchAll();
            $stmtRoles = $pdo->prepare("
                SELECT r.id, r.name, r.code FROM role r
                JOIN user_role ur ON r.id = ur.role_id WHERE ur.user_id = ?
            ");
            $stmtRoles->execute([$detail->user_id]);
            $detail->roles = $stmtRoles->fetchAll();
        }
    }
    if (!$detail && $action !== 'add') {
        $dashStats['total'] = (int) $pdo->query("SELECT COUNT(*) FROM staff")->fetchColumn();
        $dashStats['active'] = (int) $pdo->query("SELECT COUNT(*) FROM staff WHERE is_active = 1")->fetchColumn();
        $dashStats['inactive'] = $dashStats['total'] - $dashStats['active'];
        $stmt = $pdo->query("
            SELECT s.id, s.employee_number, s.hire_date, s.employment_type, s.is_active,
                   u.first_name, u.last_name, u.email,
                   o.name AS organisation_name,
                   (SELECT p.title FROM assignment a JOIN position p ON a.position_id = p.id WHERE a.staff_id = s.id AND a.is_primary = 1 LIMIT 1) AS primary_position
            FROM staff s
            JOIN user u ON s.user_id = u.id
            LEFT JOIN organisation o ON s.organisation_id = o.id
            ORDER BY u.last_name ASC, u.first_name ASC
        ");
        if ($stmt) $staffList = $stmt->fetchAll();
    }
}
require __DIR__ . '/inc/header.php';

$isForm = ($action === 'add') || ($action === 'edit' && $detail);
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Tableau de bord</a></li>
        <li class="breadcrumb-item"><a href="staff.php" class="text-decoration-none">Personnel</a></li>
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
                if ($action === 'add') echo 'Ajouter un membre du personnel';
                elseif ($action === 'edit' && $detail) echo 'Modifier la fiche';
                elseif ($detail) echo 'Fiche personnel';
                else echo 'Personnel';
                ?>
            </h1>
            <p>
                <?php
                if ($detail) echo htmlspecialchars($detail->first_name . ' ' . $detail->last_name);
                elseif ($action === 'add') echo 'Associer un utilisateur au personnel.';
                else echo 'Gestion du personnel (RH).';
                ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($detail && $action !== 'edit'): ?>
                <a href="staff.php?action=edit&id=<?= (int) $detail->id ?>" class="btn btn-admin-primary"><i class="bi bi-pencil me-1"></i> Modifier</a>
                <a href="staff.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Liste</a>
            <?php elseif ($isForm): ?>
                <a href="staff.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Annuler</a>
            <?php else: ?>
                <a href="staff.php?action=add" class="btn btn-admin-primary"><i class="bi bi-person-plus me-1"></i> Ajouter au personnel</a>
                <a href="register.php" class="btn btn-admin-outline"><i class="bi bi-person-plus me-1"></i> Nouvel utilisateur</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success">Le membre du personnel a été supprimé.</div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
    <div class="alert alert-success">Membre du personnel créé.</div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($isForm): ?>
    <!-- Formulaire Ajout / Édition -->
    <div class="admin-card admin-section-card">
        <form method="POST" action="<?= $id ? 'staff.php?action=edit&id=' . $id : 'staff.php?action=add' ?>">
            <input type="hidden" name="save_staff" value="1">
            <div class="row g-3">
                <?php if (!$id): ?>
                <div class="col-12">
                    <label class="form-label fw-bold">Utilisateur *</label>
                    <select name="user_id" class="form-select" required>
                        <option value="">-- Sélectionner un utilisateur --</option>
                        <?php foreach ($allUsers as $u): ?>
                            <option value="<?= (int) $u->id ?>"><?= htmlspecialchars($u->last_name . ' ' . $u->first_name) ?> – <?= htmlspecialchars($u->email) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                    <div class="col-12">
                        <p class="text-muted mb-0"><strong>Utilisateur :</strong> <?= htmlspecialchars($detail->last_name . ' ' . $detail->first_name) ?> (<?= htmlspecialchars($detail->email) ?>)</p>
                    </div>
                <?php endif; ?>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Organisation</label>
                    <select name="organisation_id" class="form-select">
                        <?php foreach ($organisations as $o): ?>
                            <option value="<?= (int) $o->id ?>" <?= ($detail && $detail->organisation_id == $o->id) ? 'selected' : '' ?>><?= htmlspecialchars($o->name) ?></option>
                        <?php endforeach; ?>
                        <?php if (empty($organisations)): ?>
                            <option value="1">Organisation par défaut</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">N° employé</label>
                    <input type="text" name="employee_number" class="form-control" value="<?= htmlspecialchars($detail->employee_number ?? '') ?>" placeholder="ex: EMP-001">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Type de contrat</label>
                    <select name="employment_type" class="form-select">
                        <?php foreach ($employmentTypes as $k => $v): ?>
                            <option value="<?= $k ?>" <?= (($detail->employment_type ?? '') === $k) ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Date d'embauche</label>
                    <input type="date" name="hire_date" class="form-control" value="<?= $detail->hire_date ?? '' ?>">
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" <?= ($detail && $detail->is_active) || !$detail ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Actif</label>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-admin-primary"><i class="bi bi-check-lg me-1"></i> Enregistrer</button>
                    <a href="staff.php<?= $id ? '?id=' . $id : '' ?>" class="btn btn-secondary">Annuler</a>
                </div>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php if ($detail && !$isForm): ?>
    <!-- Vue détail -->
    <div class="admin-card admin-section-card mb-4">
        <h5 class="card-title"><i class="bi bi-person-vcard"></i> Identité & compte</h5>
        <table class="admin-table mb-4">
            <tr>
                <th style="width:180px;">Nom / Prénom</th>
                <td><?= htmlspecialchars($detail->last_name . ' ' . $detail->first_name) ?></td>
            </tr>
            <tr>
                <th>Email</th>
                <td><?= htmlspecialchars($detail->email) ?></td>
            </tr>
            <tr>
                <th>Téléphone</th>
                <td><?= htmlspecialchars($detail->phone ?? '—') ?></td>
            </tr>
            <tr>
                <th>Organisation</th>
                <td><?= htmlspecialchars($detail->organisation_name ?? '—') ?></td>
            </tr>
            <tr>
                <th>Dernière connexion</th>
                <td><?= $detail->last_login_at ? date('d/m/Y H:i', strtotime($detail->last_login_at)) : '—' ?></td>
            </tr>
        </table>

        <h5 class="card-title"><i class="bi bi-briefcase"></i> RH</h5>
        <table class="admin-table mb-4">
            <tr>
                <th style="width:180px;">N° employé</th>
                <td><?= htmlspecialchars($detail->employee_number ?? '—') ?></td>
            </tr>
            <tr>
                <th>Type de contrat</th>
                <td><?= $employmentTypes[$detail->employment_type] ?? $detail->employment_type ?></td>
            </tr>
            <tr>
                <th>Date d'embauche</th>
                <td><?= $detail->hire_date ? date('d/m/Y', strtotime($detail->hire_date)) : '—' ?></td>
            </tr>
            <tr>
                <th>Statut</th>
                <td>
                    <?php if ($detail->is_active): ?><span class="badge bg-success-subtle text-success border">Actif</span><?php else: ?><span class="badge bg-secondary-subtle text-secondary border">Inactif</span><?php endif; ?>
                </td>
            </tr>
        </table>

        <?php if (!empty($detail->roles)): ?>
            <h5 class="card-title"><i class="bi bi-shield-lock"></i> Rôles</h5>
            <p class="mb-4">
                <?php foreach ($detail->roles as $r): ?>
                    <a href="roles.php?id=<?= (int) $r->id ?>" class="badge bg-primary me-1 text-decoration-none"><?= htmlspecialchars($r->name) ?></a>
                <?php endforeach; ?>
            </p>
        <?php endif; ?>

        <?php if (!empty($detail->assignments)): ?>
            <h5 class="card-title mt-3"><i class="bi bi-diagram-3"></i> Affectations / Postes</h5>
            <div class="table-responsive">
                <table class="admin-table table table-hover">
                    <thead>
                        <tr>
                            <th>Poste</th>
                            <th>Unité</th>
                            <th>Début</th>
                            <th>Fin</th>
                            <th>Principal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detail->assignments as $a): ?>
                            <tr>
                                <td><?= htmlspecialchars($a->position_title ?? '—') ?></td>
                                <td><?= htmlspecialchars($a->unit_name ?? '—') ?></td>
                                <td><?= $a->start_date ? date('d/m/Y', strtotime($a->start_date)) : '—' ?></td>
                                <td><?= $a->end_date ? date('d/m/Y', strtotime($a->end_date)) : '—' ?></td>
                                <td><?= $a->is_primary ? 'Oui' : 'Non' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="mt-4 d-flex gap-2">
            <a href="staff.php?action=edit&id=<?= (int) $detail->id ?>" class="btn btn-admin-primary"><i class="bi bi-pencil me-1"></i> Modifier</a>
            <a href="staff.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Liste</a>
            <button type="button" class="btn btn-outline-danger ms-auto" data-bs-toggle="modal" data-bs-target="#deleteStaffModal"><i class="bi bi-trash me-1"></i> Supprimer</button>
        </div>
    </div>

    <!-- Modal suppression -->
    <div class="modal fade" id="deleteStaffModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Supprimer ce membre du personnel ?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    La fiche de <strong><?= htmlspecialchars($detail->first_name . ' ' . $detail->last_name) ?></strong> sera supprimée. Les affectations et contrats liés seront également supprimés. Cette action est irréversible.
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
<?php elseif (count($staffList) > 0): ?>
    <!-- Liste du personnel -->
    <div class="admin-card admin-section-card">
        <div class="table-responsive">
            <table class="admin-table table table-hover" id="staffTable">
                <thead>
                    <tr>
                        <th>Personnel</th>
                        <th>Organisation / Poste</th>
                        <th>Type</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staffList as $s): ?>
                        <tr>
                            <td>
                                <h6 class="mb-0"><a href="staff.php?id=<?= (int) $s->id ?>"><?= htmlspecialchars($s->last_name . ' ' . $s->first_name) ?></a></h6>
                                <div class="d-flex gap-2 align-items-center mt-1">
                                    <span class="text-muted x-small"><?= htmlspecialchars($s->email) ?></span>
                                    <?php if ($s->employee_number): ?>
                                        <span class="opacity-25 text-muted small">|</span>
                                        <span class="text-muted x-small">N° <?= htmlspecialchars($s->employee_number) ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="small fw-medium text-primary mb-1"><?= htmlspecialchars($s->organisation_name ?? '—') ?></div>
                                <span class="text-muted x-small"><?= htmlspecialchars($s->primary_position ?? '—') ?></span>
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary border x-small"><?= $employmentTypes[$s->employment_type] ?? $s->employment_type ?></span>
                                <br>
                                <?= $s->is_active ? '<span class="badge bg-success-subtle text-success border x-small mt-1">Actif</span>' : '<span class="badge bg-secondary-subtle text-secondary border x-small mt-1">Inactif</span>' ?>
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                        <li><a class="dropdown-item" href="staff.php?id=<?= (int) $s->id ?>"><i class="bi bi-eye me-2"></i> Voir</a></li>
                                        <li><a class="dropdown-item" href="staff.php?action=edit&id=<?= (int) $s->id ?>"><i class="bi bi-pencil me-2"></i> Modifier</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" onsubmit="return confirm('Supprimer ce membre du personnel ?');">
                                                <input type="hidden" name="delete_id" value="<?= (int) $s->id ?>">
                                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i> Supprimer</button>
                                            </form>
                                        </li>
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
            <h5>Aucun personnel enregistré</h5>
            <p class="text-muted mb-4">Créez des comptes utilisateurs puis associez-les au personnel.</p>
            <a href="register.php" class="btn btn-admin-primary"><i class="bi bi-person-plus me-1"></i> Créer un utilisateur</a>
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
        if (document.getElementById('staffTable') && typeof $ !== 'undefined' && $.fn.DataTable) {
            $('#staffTable').DataTable({
                language: { url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json" },
                order: [[0, "asc"]],
                pageLength: 10,
                dom: '<"d-flex justify-content-between align-items-center mb-3"f>t<"d-flex justify-content-between align-items-center mt-3"ip>'
            });
        }
    });
</script>

<?php require __DIR__ . '/inc/footer.php'; ?>
