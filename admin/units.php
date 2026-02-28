<?php
$pageTitle = 'Unités & Services – Administration';
$currentNav = 'units';
require_once __DIR__ . '/inc/auth.php';
require_permission('admin.units.view');
require __DIR__ . '/inc/db.php';

$organisations = [];
$currentOrg = null;
$organisationId = isset($_GET['organisation_id']) ? (int) $_GET['organisation_id'] : 0;
$departments = [];
$services = [];
$units = [];
$error = '';
$success = '';
$formType = $_GET['type'] ?? ''; // department | service | unit
$formAction = $_GET['form_action'] ?? ''; // add | edit
$formId = isset($_GET['form_id']) ? (int) $_GET['form_id'] : 0;
$editDepartment = null;
$editService = null;
$editUnit = null;
$allUsers = [];

if ($pdo) {
    $organisations = $pdo->query("SELECT id, name, code FROM organisation ORDER BY name")->fetchAll();
    $allUsers = $pdo->query("SELECT id, first_name, last_name FROM user ORDER BY last_name, first_name")->fetchAll();

    if ($organisationId > 0) {
        $stmt = $pdo->prepare("SELECT id, name, code FROM organisation WHERE id = ?");
        $stmt->execute([$organisationId]);
        $currentOrg = $stmt->fetch();
        if ($currentOrg) {
            $departments = $pdo->prepare("SELECT d.id, d.name, d.code, d.photo, d.is_active, d.head_user_id, u.first_name, u.last_name FROM department d LEFT JOIN user u ON d.head_user_id = u.id WHERE d.organisation_id = ? ORDER BY d.name");
            $departments->execute([$organisationId]);
            $departments = $departments->fetchAll();

            $stmtSvc = $pdo->prepare("
                SELECT s.id, s.name, s.code, s.photo, s.is_active, s.department_id, d.name AS department_name
                FROM service s
                JOIN department d ON s.department_id = d.id
                WHERE d.organisation_id = ?
                ORDER BY d.name, s.name
            ");
            $stmtSvc->execute([$organisationId]);
            $services = $stmtSvc->fetchAll();

            $stmtUnit = $pdo->prepare("
                SELECT u.id, u.name, u.code, u.photo, u.is_active, u.service_id, s.name AS service_name, d.name AS department_name
                FROM unit u
                JOIN service s ON u.service_id = s.id
                JOIN department d ON s.department_id = d.id
                WHERE d.organisation_id = ?
                ORDER BY d.name, s.name, u.name
            ");
            $stmtUnit->execute([$organisationId]);
            $units = $stmtUnit->fetchAll();
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $organisationId = (int) ($_POST['organisation_id'] ?? $organisationId);
        if ($organisationId <= 0 && $currentOrg) $organisationId = (int) $currentOrg->id;

        if (isset($_POST['delete_department'])) {
            require_permission('admin.units.delete');
            $id = (int) $_POST['delete_department'];
            $pdo->prepare("DELETE FROM department WHERE id = ? AND organisation_id = ?")->execute([$id, $organisationId]);
            header('Location: units.php?organisation_id=' . $organisationId . '&msg=deleted');
            exit;
        }
        if (isset($_POST['delete_service'])) {
            require_permission('admin.units.delete');
            $id = (int) $_POST['delete_service'];
            $pdo->prepare("DELETE FROM service WHERE id = ?")->execute([$id]);
            header('Location: units.php?organisation_id=' . $organisationId . '&msg=deleted');
            exit;
        }
        if (isset($_POST['delete_unit'])) {
            require_permission('admin.units.delete');
            $id = (int) $_POST['delete_unit'];
            $pdo->prepare("DELETE FROM unit WHERE id = ?")->execute([$id]);
            header('Location: units.php?organisation_id=' . $organisationId . '&msg=deleted');
            exit;
        }

        if (isset($_POST['save_department'])) {
            $id = (int) ($_POST['department_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $code = trim($_POST['code'] ?? '') ?: null;
            $description = trim($_POST['description'] ?? '') ?: null;
            $head_user_id = (int) ($_POST['head_user_id'] ?? 0) ?: null;
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $photo = null;
            if ($id > 0) {
                $cur = $pdo->prepare("SELECT photo FROM department WHERE id = ? AND organisation_id = ?");
                $cur->execute([$id, $organisationId]);
                $row = $cur->fetch();
                if ($row && !empty($row->photo)) $photo = $row->photo;
            }
            if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $target_dir = __DIR__ . '/../uploads/departments/';
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $fname = 'dept_' . ($id ?: 'new') . '_' . time() . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_dir . $fname)) $photo = 'uploads/departments/' . $fname;
                }
            }
            if ($name !== '') {
                if ($id > 0) {
                    require_permission('admin.units.modify');
                    $pdo->prepare("UPDATE department SET name = ?, code = ?, description = ?, photo = ?, head_user_id = ?, is_active = ? WHERE id = ? AND organisation_id = ?")
                        ->execute([$name, $code, $description, $photo, $head_user_id, $is_active, $id, $organisationId]);
                    $success = 'Département mis à jour.';
                } else {
                    require_permission('admin.units.add');
                    $pdo->prepare("INSERT INTO department (organisation_id, name, code, description, photo, head_user_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)")
                        ->execute([$organisationId, $name, $code, $description, $photo, $head_user_id, $is_active]);
                    $success = 'Département créé.';
                }
            } else {
                $error = 'Le nom du département est obligatoire.';
            }
        }

        if (isset($_POST['save_service'])) {
            $id = (int) ($_POST['service_id'] ?? 0);
            $department_id = (int) ($_POST['department_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $code = trim($_POST['code'] ?? '') ?: null;
            $description = trim($_POST['description'] ?? '') ?: null;
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $photo = null;
            if ($id > 0) {
                $cur = $pdo->prepare("SELECT photo FROM service WHERE id = ?");
                $cur->execute([$id]);
                $row = $cur->fetch();
                if ($row && !empty($row->photo)) $photo = $row->photo;
            }
            if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $target_dir = __DIR__ . '/../uploads/services/';
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $fname = 'svc_' . ($id ?: 'new') . '_' . time() . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_dir . $fname)) $photo = 'uploads/services/' . $fname;
                }
            }
            if ($name !== '' && $department_id > 0) {
                if ($id > 0) {
                    require_permission('admin.units.modify');
                    $pdo->prepare("UPDATE service SET department_id = ?, name = ?, code = ?, description = ?, photo = ?, is_active = ? WHERE id = ?")
                        ->execute([$department_id, $name, $code, $description, $photo, $is_active, $id]);
                    $success = 'Service mis à jour.';
                } else {
                    require_permission('admin.units.add');
                    $pdo->prepare("INSERT INTO service (department_id, name, code, description, photo, is_active) VALUES (?, ?, ?, ?, ?, ?)")
                        ->execute([$department_id, $name, $code, $description, $photo, $is_active]);
                    $success = 'Service créé.';
                }
            } else {
                $error = 'Nom et département obligatoires.';
            }
        }

        if (isset($_POST['save_unit'])) {
            $id = (int) ($_POST['unit_id'] ?? 0);
            $service_id = (int) ($_POST['service_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $code = trim($_POST['code'] ?? '') ?: null;
            $description = trim($_POST['description'] ?? '') ?: null;
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $photo = null;
            if ($id > 0) {
                $cur = $pdo->prepare("SELECT photo FROM unit WHERE id = ?");
                $cur->execute([$id]);
                $row = $cur->fetch();
                if ($row && !empty($row->photo)) $photo = $row->photo;
            }
            if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $target_dir = __DIR__ . '/../uploads/units/';
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $fname = 'unit_' . ($id ?: 'new') . '_' . time() . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_dir . $fname)) $photo = 'uploads/units/' . $fname;
                }
            }
            if ($name !== '' && $service_id > 0) {
                if ($id > 0) {
                    require_permission('admin.units.modify');
                    $pdo->prepare("UPDATE unit SET service_id = ?, name = ?, code = ?, description = ?, photo = ?, is_active = ? WHERE id = ?")
                        ->execute([$service_id, $name, $code, $description, $photo, $is_active, $id]);
                    $success = 'Unité mise à jour.';
                } else {
                    require_permission('admin.units.add');
                    $pdo->prepare("INSERT INTO unit (service_id, name, code, description, photo, is_active) VALUES (?, ?, ?, ?, ?, ?)")
                        ->execute([$service_id, $name, $code, $description, $photo, $is_active]);
                    $success = 'Unité créée.';
                }
            } else {
                $error = 'Nom et service obligatoires.';
            }
        }

        if ($success || $error) {
            if ($organisationId > 0) {
                $stmt = $pdo->prepare("SELECT id, name, code FROM organisation WHERE id = ?");
                $stmt->execute([$organisationId]);
                $currentOrg = $stmt->fetch();
                $departments = $pdo->prepare("SELECT d.id, d.name, d.code, d.photo, d.is_active, d.head_user_id, u.first_name, u.last_name FROM department d LEFT JOIN user u ON d.head_user_id = u.id WHERE d.organisation_id = ? ORDER BY d.name");
                $departments->execute([$organisationId]);
                $departments = $departments->fetchAll();
                $stmtSvc = $pdo->prepare("SELECT s.id, s.name, s.code, s.photo, s.is_active, s.department_id, d.name AS department_name FROM service s JOIN department d ON s.department_id = d.id WHERE d.organisation_id = ? ORDER BY d.name, s.name");
                $stmtSvc->execute([$organisationId]);
                $services = $stmtSvc->fetchAll();
                $stmtUnit = $pdo->prepare("SELECT u.id, u.name, u.code, u.photo, u.is_active, u.service_id, s.name AS service_name, d.name AS department_name FROM unit u JOIN service s ON u.service_id = s.id JOIN department d ON s.department_id = d.id WHERE d.organisation_id = ? ORDER BY d.name, s.name, u.name");
                $stmtUnit->execute([$organisationId]);
                $units = $stmtUnit->fetchAll();
            }
        }
    }

    if ($formAction === 'edit' && $formId > 0) {
        if ($formType === 'department') {
            $stmt = $pdo->prepare("SELECT * FROM department WHERE id = ? AND organisation_id = ?");
            $stmt->execute([$formId, $organisationId]);
            $editDepartment = $stmt->fetch();
        } elseif ($formType === 'service') {
            $stmt = $pdo->prepare("SELECT * FROM service WHERE id = ?");
            $stmt->execute([$formId]);
            $editService = $stmt->fetch();
        } elseif ($formType === 'unit') {
            $stmt = $pdo->prepare("SELECT * FROM unit WHERE id = ?");
            $stmt->execute([$formId]);
            $editUnit = $stmt->fetch();
        }
    }
}
require __DIR__ . '/inc/header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Tableau de bord</a></li>
        <li class="breadcrumb-item"><a href="organisations.php" class="text-decoration-none">Structure</a></li>
        <li class="breadcrumb-item active">Unités & Services</li>
    </ol>
</nav>

<header class="admin-header">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h1>Unités & Services</h1>
            <p class="text-muted mb-0">Départements, services et unités par organisation.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="organisations.php" class="btn btn-admin-outline"><i class="bi bi-building me-1"></i> Organisations</a>
        </div>
    </div>
</header>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success">Élément supprimé.</div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="admin-card admin-section-card mb-4">
    <label class="form-label fw-bold">Organisation</label>
    <form method="GET" class="d-flex gap-2 align-items-end flex-wrap">
        <select name="organisation_id" class="form-select" style="max-width:320px;" onchange="this.form.submit()">
            <option value="">— Choisir une organisation —</option>
            <?php foreach ($organisations as $o): ?>
                <option value="<?= (int) $o->id ?>" <?= $organisationId === (int) $o->id ? 'selected' : '' ?>><?= htmlspecialchars($o->name) ?><?= $o->code ? ' (' . htmlspecialchars($o->code) . ')' : '' ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-admin-primary">Actualiser</button>
    </form>
</div>

<?php if (!$currentOrg): ?>
    <div class="admin-card admin-section-card text-center py-5 text-muted">
        Sélectionnez une organisation pour gérer ses départements, services et unités.
    </div>
<?php else: ?>

    <!-- Départements -->
    <div class="admin-card admin-section-card mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0"><i class="bi bi-building me-2"></i>Départements</h5>
            <a href="units.php?organisation_id=<?= $organisationId ?>&type=department&form_action=add" class="btn btn-sm btn-admin-primary">Ajouter un département</a>
        </div>
        <?php if ($formType === 'department' && ($formAction === 'add' || $editDepartment)): ?>
            <form method="POST" class="mb-4 p-3 bg-light rounded" enctype="multipart/form-data">
                <input type="hidden" name="organisation_id" value="<?= $organisationId ?>">
                <input type="hidden" name="save_department" value="1">
                <input type="hidden" name="department_id" value="<?= $editDepartment ? (int) $editDepartment->id : 0 ?>">
                <div class="row g-2">
                    <div class="col-md-4"><label class="form-label">Nom *</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editDepartment->name ?? '') ?>" required></div>
                    <div class="col-md-2"><label class="form-label">Code</label><input type="text" name="code" class="form-control" value="<?= htmlspecialchars($editDepartment->code ?? '') ?>"></div>
                    <div class="col-md-4"><label class="form-label">Responsable</label><select name="head_user_id" class="form-select"><option value="">—</option><?php foreach ($allUsers as $u): ?><option value="<?= (int)$u->id ?>" <?= ($editDepartment && $editDepartment->head_user_id == $u->id) ? 'selected' : '' ?>><?= htmlspecialchars($u->last_name . ' ' . $u->first_name) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-2 d-flex align-items-end"><div class="form-check"><input type="checkbox" name="is_active" id="dept_active" class="form-check-input" value="1" <?= (!$editDepartment || $editDepartment->is_active) ? 'checked' : '' ?>><label class="form-check-label" for="dept_active">Actif</label></div></div>
                    <div class="col-12">
                        <label class="form-label">Photo</label>
                        <?php if (!empty(isset($editDepartment) ? $editDepartment->photo : null)): ?><div class="mb-1"><img src="../<?= htmlspecialchars($editDepartment->photo) ?>" alt="" class="rounded border" style="height:40px;width:40px;object-fit:cover;"></div><?php endif; ?>
                        <input type="file" name="photo" class="form-control form-control-sm" accept="image/jpeg,image/png,image/gif,image/webp">
                    </div>
                    <div class="col-12"><label class="form-label">Description</label>
                        <textarea name="description" id="department_description" class="form-control"><?= htmlspecialchars(isset($editDepartment) ? ($editDepartment->description ?? '') : '') ?></textarea>
                        <script type="application/json" id="department_description_data"><?= json_encode(isset($editDepartment) ? ($editDepartment->description ?? '') : '') ?></script>
                    </div>
                    <div class="col-12"><button type="submit" class="btn btn-admin-primary btn-sm">Enregistrer</button> <a href="units.php?organisation_id=<?= $organisationId ?>" class="btn btn-secondary btn-sm">Annuler</a></div>
                </div>
            </form>
        <?php endif; ?>
        <?php if (count($departments) > 0): ?>
            <div class="table-responsive">
                <table class="admin-table table table-hover table-sm">
                    <thead><tr><th>Photo</th><th>Nom</th><th>Code</th><th>Responsable</th><th>Statut</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($departments as $d): ?>
                            <tr>
                                <td><?php if (!empty($d->photo)): ?><img src="../<?= htmlspecialchars($d->photo) ?>" alt="" class="rounded" style="width:40px;height:40px;object-fit:cover;"><?php else: ?><span class="text-muted small">—</span><?php endif; ?></td>
                                <td><?= htmlspecialchars($d->name) ?></td>
                                <td><?= htmlspecialchars($d->code ?? '—') ?></td>
                                <td><?= $d->first_name ? htmlspecialchars($d->last_name . ' ' . $d->first_name) : '—' ?></td>
                                <td><?= $d->is_active ? '<span class="badge bg-success-subtle text-success border">Actif</span>' : '<span class="badge bg-secondary">Inactif</span>' ?></td>
                                <td class="text-end">
                                    <a href="units.php?organisation_id=<?= $organisationId ?>&type=department&form_action=edit&form_id=<?= (int)$d->id ?>" class="btn btn-sm btn-light border">Modifier</a>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce département et ses services/unités ?');">
                                        <input type="hidden" name="organisation_id" value="<?= $organisationId ?>">
                                        <input type="hidden" name="delete_department" value="<?= (int)$d->id ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted mb-0">Aucun département. <a href="units.php?organisation_id=<?= $organisationId ?>&type=department&form_action=add">Ajouter un département</a></p>
        <?php endif; ?>
    </div>

    <!-- Services -->
    <div class="admin-card admin-section-card mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0"><i class="bi bi-gear me-2"></i>Services</h5>
            <a href="units.php?organisation_id=<?= $organisationId ?>&type=service&form_action=add" class="btn btn-sm btn-admin-primary">Ajouter un service</a>
        </div>
        <?php if ($formType === 'service' && ($formAction === 'add' || $editService)): ?>
            <form method="POST" class="mb-4 p-3 bg-light rounded" enctype="multipart/form-data">
                <input type="hidden" name="organisation_id" value="<?= $organisationId ?>">
                <input type="hidden" name="save_service" value="1">
                <input type="hidden" name="service_id" value="<?= $editService ? (int) $editService->id : 0 ?>">
                <div class="row g-2">
                    <div class="col-md-4"><label class="form-label">Département *</label><select name="department_id" class="form-select" required><?php foreach ($departments as $d): ?><option value="<?= (int)$d->id ?>" <?= ($editService && $editService->department_id == $d->id) ? 'selected' : '' ?>><?= htmlspecialchars($d->name) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-4"><label class="form-label">Nom *</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editService->name ?? '') ?>" required></div>
                    <div class="col-md-2"><label class="form-label">Code</label><input type="text" name="code" class="form-control" value="<?= htmlspecialchars($editService->code ?? '') ?>"></div>
                    <div class="col-md-2 d-flex align-items-end"><div class="form-check"><input type="checkbox" name="is_active" id="svc_active" class="form-check-input" value="1" <?= (!$editService || $editService->is_active) ? 'checked' : '' ?>><label class="form-check-label" for="svc_active">Actif</label></div></div>
                    <div class="col-12">
                        <label class="form-label">Photo</label>
                        <?php if (!empty(isset($editService) ? $editService->photo : null)): ?><div class="mb-1"><img src="../<?= htmlspecialchars($editService->photo) ?>" alt="" class="rounded border" style="height:40px;width:40px;object-fit:cover;"></div><?php endif; ?>
                        <input type="file" name="photo" class="form-control form-control-sm" accept="image/jpeg,image/png,image/gif,image/webp">
                    </div>
                    <div class="col-12"><label class="form-label">Description</label>
                        <textarea name="description" id="service_description" class="form-control"><?= htmlspecialchars(isset($editService) ? ($editService->description ?? '') : '') ?></textarea>
                        <script type="application/json" id="service_description_data"><?= json_encode(isset($editService) ? ($editService->description ?? '') : '') ?></script>
                    </div>
                    <div class="col-12"><button type="submit" class="btn btn-admin-primary btn-sm">Enregistrer</button> <a href="units.php?organisation_id=<?= $organisationId ?>" class="btn btn-secondary btn-sm">Annuler</a></div>
                </div>
            </form>
        <?php endif; ?>
        <?php if (count($services) > 0): ?>
            <div class="table-responsive">
                <table class="admin-table table table-hover table-sm">
                    <thead><tr><th>Photo</th><th>Nom</th><th>Code</th><th>Département</th><th>Statut</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($services as $s): ?>
                            <tr>
                                <td><?php if (!empty($s->photo)): ?><img src="../<?= htmlspecialchars($s->photo) ?>" alt="" class="rounded" style="width:40px;height:40px;object-fit:cover;"><?php else: ?><span class="text-muted small">—</span><?php endif; ?></td>
                                <td><?= htmlspecialchars($s->name) ?></td>
                                <td><?= htmlspecialchars($s->code ?? '—') ?></td>
                                <td><?= htmlspecialchars($s->department_name ?? '—') ?></td>
                                <td><?= $s->is_active ? '<span class="badge bg-success-subtle text-success border">Actif</span>' : '<span class="badge bg-secondary">Inactif</span>' ?></td>
                                <td class="text-end">
                                    <a href="units.php?organisation_id=<?= $organisationId ?>&type=service&form_action=edit&form_id=<?= (int)$s->id ?>" class="btn btn-sm btn-light border">Modifier</a>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce service et ses unités ?');">
                                        <input type="hidden" name="organisation_id" value="<?= $organisationId ?>">
                                        <input type="hidden" name="delete_service" value="<?= (int)$s->id ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted mb-0">Aucun service. Créez d'abord un département, puis <a href="units.php?organisation_id=<?= $organisationId ?>&type=service&form_action=add">ajouter un service</a>.</p>
        <?php endif; ?>
    </div>

    <!-- Unités -->
    <div class="admin-card admin-section-card mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0"><i class="bi bi-diagram-3 me-2"></i>Unités</h5>
            <a href="units.php?organisation_id=<?= $organisationId ?>&type=unit&form_action=add" class="btn btn-sm btn-admin-primary">Ajouter une unité</a>
        </div>
        <?php if ($formType === 'unit' && ($formAction === 'add' || $editUnit)): ?>
            <form method="POST" class="mb-4 p-3 bg-light rounded" enctype="multipart/form-data">
                <input type="hidden" name="organisation_id" value="<?= $organisationId ?>">
                <input type="hidden" name="save_unit" value="1">
                <input type="hidden" name="unit_id" value="<?= $editUnit ? (int) $editUnit->id : 0 ?>">
                <div class="row g-2">
                    <div class="col-md-4"><label class="form-label">Service *</label><select name="service_id" class="form-select" required><?php foreach ($services as $s): ?><option value="<?= (int)$s->id ?>" <?= ($editUnit && $editUnit->service_id == $s->id) ? 'selected' : '' ?>><?= htmlspecialchars($s->department_name . ' → ' . $s->name) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-4"><label class="form-label">Nom *</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editUnit->name ?? '') ?>" required></div>
                    <div class="col-md-2"><label class="form-label">Code</label><input type="text" name="code" class="form-control" value="<?= htmlspecialchars($editUnit->code ?? '') ?>"></div>
                    <div class="col-md-2 d-flex align-items-end"><div class="form-check"><input type="checkbox" name="is_active" id="unit_active" class="form-check-input" value="1" <?= (!$editUnit || $editUnit->is_active) ? 'checked' : '' ?>><label class="form-check-label" for="unit_active">Actif</label></div></div>
                    <div class="col-12">
                        <label class="form-label">Photo</label>
                        <?php if (!empty(isset($editUnit) ? $editUnit->photo : null)): ?><div class="mb-1"><img src="../<?= htmlspecialchars($editUnit->photo) ?>" alt="" class="rounded border" style="height:40px;width:40px;object-fit:cover;"></div><?php endif; ?>
                        <input type="file" name="photo" class="form-control form-control-sm" accept="image/jpeg,image/png,image/gif,image/webp">
                    </div>
                    <div class="col-12"><label class="form-label">Description</label>
                        <textarea name="description" id="unit_description" class="form-control"><?= htmlspecialchars(isset($editUnit) ? ($editUnit->description ?? '') : '') ?></textarea>
                        <script type="application/json" id="unit_description_data"><?= json_encode(isset($editUnit) ? ($editUnit->description ?? '') : '') ?></script>
                    </div>
                    <div class="col-12"><button type="submit" class="btn btn-admin-primary btn-sm">Enregistrer</button> <a href="units.php?organisation_id=<?= $organisationId ?>" class="btn btn-secondary btn-sm">Annuler</a></div>
                </div>
            </form>
        <?php endif; ?>
        <?php if (count($units) > 0): ?>
            <div class="table-responsive">
                <table class="admin-table table table-hover table-sm">
                    <thead><tr><th>Photo</th><th>Nom</th><th>Code</th><th>Service</th><th>Statut</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($units as $u): ?>
                            <tr>
                                <td><?php if (!empty($u->photo)): ?><img src="../<?= htmlspecialchars($u->photo) ?>" alt="" class="rounded" style="width:40px;height:40px;object-fit:cover;"><?php else: ?><span class="text-muted small">—</span><?php endif; ?></td>
                                <td><?= htmlspecialchars($u->name) ?></td>
                                <td><?= htmlspecialchars($u->code ?? '—') ?></td>
                                <td><?= htmlspecialchars($u->service_name ?? '—') ?></td>
                                <td><?= $u->is_active ? '<span class="badge bg-success-subtle text-success border">Actif</span>' : '<span class="badge bg-secondary">Inactif</span>' ?></td>
                                <td class="text-end">
                                    <a href="units.php?organisation_id=<?= $organisationId ?>&type=unit&form_action=edit&form_id=<?= (int)$u->id ?>" class="btn btn-sm btn-light border">Modifier</a>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette unité ?');">
                                        <input type="hidden" name="organisation_id" value="<?= $organisationId ?>">
                                        <input type="hidden" name="delete_unit" value="<?= (int)$u->id ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted mb-0">Aucune unité. Créez d'abord un service, puis <a href="units.php?organisation_id=<?= $organisationId ?>&type=unit&form_action=add">ajouter une unité</a>.</p>
        <?php endif; ?>
    </div>

<?php endif; ?>

<?php if ($currentOrg && ($formType === 'department' && ($formAction === 'add' || $editDepartment)) || ($formType === 'service' && ($formAction === 'add' || $editService)) || ($formType === 'unit' && ($formAction === 'add' || $editUnit))): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof $ === 'undefined' || !$.fn.summernote) return;
    var editorId = null;
    var dataId = null;
    <?php if ($formType === 'department'): ?>editorId = 'department_description'; dataId = 'department_description_data';<?php elseif ($formType === 'service'): ?>editorId = 'service_description'; dataId = 'service_description_data';<?php else: ?>editorId = 'unit_description'; dataId = 'unit_description_data';<?php endif; ?>
    if (editorId && $('#' + editorId).length) {
        $('#' + editorId).summernote({
            placeholder: 'Description...',
            tabsize: 2,
            height: 200,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'picture']],
                ['view', ['codeview', 'help']]
            ],
            callbacks: {
                onImageUpload: function(files) {
                    for (var i = 0; i < files.length; i++) {
                        var data = new FormData();
                        data.append('file', files[i]);
                        $.ajax({
                            url: 'upload_units_image.php',
                            data: data,
                            processData: false,
                            contentType: false,
                            type: 'POST',
                            success: function(res) {
                                if (res && res.url) $('#' + editorId).summernote('insertImage', res.url);
                            },
                            error: function(xhr) {
                                try {
                                    var r = (xhr.responseJSON || {});
                                    alert(r.error || 'Erreur lors de l\'upload de l\'image.');
                                } catch (e) { alert('Erreur lors de l\'upload de l\'image.'); }
                            }
                        });
                    }
                }
            }
        });
        var dataEl = document.getElementById(dataId);
        if (dataEl) {
            try {
                var content = JSON.parse(dataEl.textContent);
                if (content) $('#' + editorId).summernote('code', content);
            } catch (e) {}
        }
    }
});
</script>
<?php endif; ?>

<style>
    .admin-table th { background: #f8f9fa; padding: 0.6rem 0.5rem; font-size: 0.85rem; }
    .admin-table td { padding: 0.6rem 0.5rem; font-size: 0.9rem; }
</style>

<footer class="admin-main-footer">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <a href="organisations.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Organisations</a>
        <span class="small text-muted">&copy; <?= date('Y') ?> Expertise</span>
    </div>
</footer>
<?php require __DIR__ . '/inc/footer.php'; ?>
