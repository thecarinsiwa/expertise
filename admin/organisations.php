<?php
$pageTitle = 'Organisations – Administration';
$currentNav = 'organisations';
require_once __DIR__ . '/inc/auth.php';
require __DIR__ . '/inc/db.php';

$list = [];
$detail = null;
$stats = ['total' => 0, 'active' => 0];
$error = '';
$success = '';

if ($pdo) {
    $action = $_GET['action'] ?? 'list';
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['delete_id'])) {
            $delId = (int) $_POST['delete_id'];
            try {
                $pdo->prepare("DELETE FROM organisation WHERE id = ?")->execute([$delId]);
                header('Location: organisations.php?msg=deleted');
                exit;
            } catch (PDOException $e) {
                $error = 'Impossible de supprimer : des enregistrements dépendent de cette organisation.';
            }
        }
        if (isset($_POST['save_organisation'])) {
            $name = trim($_POST['name'] ?? '');
            $code = trim($_POST['code'] ?? '') ?: null;
            $description = trim($_POST['description'] ?? '') ?: null;
            $address = trim($_POST['address'] ?? '') ?: null;
            $phone = trim($_POST['phone'] ?? '') ?: null;
            $email = trim($_POST['email'] ?? '') ?: null;
            $website = trim($_POST['website'] ?? '') ?: null;
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            if ($name === '') {
                $error = 'Le nom est obligatoire.';
            } else {
                if ($id > 0) {
                    $stmt = $pdo->prepare("UPDATE organisation SET name = ?, code = ?, description = ?, address = ?, phone = ?, email = ?, website = ?, is_active = ? WHERE id = ?");
                    $stmt->execute([$name, $code, $description, $address, $phone, $email, $website, $is_active, $id]);
                    $success = 'Organisation mise à jour.';
                    $action = 'view';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO organisation (name, code, description, address, phone, email, website, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $code, $description, $address, $phone, $email, $website, $is_active]);
                    header('Location: organisations.php?id=' . $pdo->lastInsertId() . '&msg=created');
                    exit;
                }
            }
        }
    }

    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM organisation WHERE id = ?");
        $stmt->execute([$id]);
        $detail = $stmt->fetch();
        if ($detail) {
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM department WHERE organisation_id = ?");
            $stmtCount->execute([$id]);
            $detail->count_departments = (int) $stmtCount->fetchColumn();
            $stmtStaff = $pdo->prepare("SELECT COUNT(*) FROM staff WHERE organisation_id = ?");
            $stmtStaff->execute([$id]);
            $detail->count_staff = (int) $stmtStaff->fetchColumn();
            $stmtUser = $pdo->prepare("SELECT COUNT(*) FROM user WHERE organisation_id = ?");
            $stmtUser->execute([$id]);
            $detail->count_users = (int) $stmtUser->fetchColumn();
        }
    }

    if (!$detail || $action === 'list') {
        $stats['total'] = (int) $pdo->query("SELECT COUNT(*) FROM organisation")->fetchColumn();
        $stats['active'] = (int) $pdo->query("SELECT COUNT(*) FROM organisation WHERE is_active = 1")->fetchColumn();
        $stmt = $pdo->query("SELECT id, name, code, is_active, created_at FROM organisation ORDER BY name");
        if ($stmt) $list = $stmt->fetchAll();
    }
}
require __DIR__ . '/inc/header.php';

$isForm = ($action === 'add') || ($action === 'edit' && $detail);
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Tableau de bord</a></li>
        <li class="breadcrumb-item"><a href="organisations.php" class="text-decoration-none">Structure</a></li>
        <li class="breadcrumb-item"><a href="organisations.php" class="text-decoration-none">Organisations</a></li>
        <?php if ($action === 'add'): ?>
            <li class="breadcrumb-item active">Nouvelle</li>
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
                if ($action === 'add') echo 'Nouvelle organisation';
                elseif ($action === 'edit' && $detail) echo 'Modifier l\'organisation';
                elseif ($detail) echo htmlspecialchars($detail->name);
                else echo 'Organisations';
                ?>
            </h1>
            <p class="text-muted mb-0">
                <?php
                if ($detail && !$isForm) echo 'Fiche organisation';
                elseif ($action === 'add') echo 'Créer une nouvelle organisation.';
                else echo 'Gestion des organisations.';
                ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($detail && $action !== 'edit'): ?>
                <a href="organisations.php?action=edit&id=<?= (int) $detail->id ?>" class="btn btn-admin-primary"><i class="bi bi-pencil me-1"></i> Modifier</a>
                <a href="units.php?organisation_id=<?= (int) $detail->id ?>" class="btn btn-admin-outline"><i class="bi bi-diagram-3 me-1"></i> Unités & Services</a>
                <a href="organisations.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Liste</a>
            <?php elseif ($isForm): ?>
                <a href="organisations.php<?= $id ? '?id=' . $id : '' ?>" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Annuler</a>
            <?php else: ?>
                <a href="organisations.php?action=add" class="btn btn-admin-primary"><i class="bi bi-building-add me-1"></i> Nouvelle organisation</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success">L'organisation a été supprimée.</div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
    <div class="alert alert-success">Organisation créée.</div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (!$detail && !$isForm): ?>
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-4">
            <div class="admin-card text-center p-3 h-100">
                <div class="text-muted small text-uppercase mb-1 fw-bold">Total</div>
                <div class="h3 mb-0 fw-bold"><?= $stats['total'] ?></div>
                <div class="mt-2"><span class="badge bg-secondary-subtle text-secondary border">Organisations</span></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="admin-card text-center p-3 h-100">
                <div class="text-muted small text-uppercase mb-1 fw-bold">Actives</div>
                <div class="h3 mb-0 fw-bold text-success"><?= $stats['active'] ?></div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($isForm): ?>
    <div class="admin-card admin-section-card">
        <form method="POST" action="<?= $id ? 'organisations.php?action=edit&id=' . $id : 'organisations.php?action=add' ?>">
            <input type="hidden" name="save_organisation" value="1">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label fw-bold">Nom *</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($detail->name ?? '') ?>" required placeholder="Nom de l'organisation">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Code</label>
                    <input type="text" name="code" class="form-control" value="<?= htmlspecialchars($detail->code ?? '') ?>" placeholder="ex: ORG-01">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Description</label>
                    <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($detail->description ?? '') ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Adresse</label>
                    <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($detail->address ?? '') ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Téléphone</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($detail->phone ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($detail->email ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Site web</label>
                    <input type="url" name="website" class="form-control" value="<?= htmlspecialchars($detail->website ?? '') ?>" placeholder="https://">
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" name="is_active" id="is_active_org" class="form-check-input" value="1" <?= ($detail && $detail->is_active) || !$detail ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active_org">Organisation active</label>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-admin-primary"><i class="bi bi-check-lg me-1"></i> Enregistrer</button>
                    <a href="organisations.php<?= $id ? '?id=' . $id : '' ?>" class="btn btn-secondary">Annuler</a>
                </div>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php if ($detail && !$isForm): ?>
    <div class="admin-card admin-section-card mb-4">
        <h5 class="card-title"><i class="bi bi-building"></i> Informations</h5>
        <table class="admin-table mb-4">
            <tr><th style="width:180px;">Nom</th><td><?= htmlspecialchars($detail->name) ?></td></tr>
            <tr><th>Code</th><td><?= htmlspecialchars($detail->code ?? '—') ?></td></tr>
            <tr><th>Description</th><td><?= nl2br(htmlspecialchars($detail->description ?? '—')) ?></td></tr>
            <tr><th>Adresse</th><td><?= nl2br(htmlspecialchars($detail->address ?? '—')) ?></td></tr>
            <tr><th>Téléphone</th><td><?= htmlspecialchars($detail->phone ?? '—') ?></td></tr>
            <tr><th>Email</th><td><?= htmlspecialchars($detail->email ?? '—') ?></td></tr>
            <tr><th>Site web</th><td><?= $detail->website ? '<a href="' . htmlspecialchars($detail->website) . '" target="_blank" rel="noopener">' . htmlspecialchars($detail->website) . '</a>' : '—' ?></td></tr>
            <tr><th>Statut</th><td><?= $detail->is_active ? '<span class="badge bg-success-subtle text-success border">Active</span>' : '<span class="badge bg-secondary-subtle text-secondary border">Inactive</span>' ?></td></tr>
        </table>
        <h5 class="card-title"><i class="bi bi-diagram-3"></i> Synthèse</h5>
        <div class="row g-2 mb-4">
            <div class="col-auto"><span class="badge bg-primary"><?= (int)($detail->count_departments ?? 0) ?> département(s)</span></div>
            <div class="col-auto"><span class="badge bg-info"><?= (int)($detail->count_staff ?? 0) ?> personnel</span></div>
            <div class="col-auto"><span class="badge bg-secondary"><?= (int)($detail->count_users ?? 0) ?> utilisateur(s)</span></div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <a href="organisations.php?action=edit&id=<?= (int) $detail->id ?>" class="btn btn-admin-primary"><i class="bi bi-pencil me-1"></i> Modifier</a>
            <a href="units.php?organisation_id=<?= (int) $detail->id ?>" class="btn btn-admin-outline"><i class="bi bi-diagram-3 me-1"></i> Unités & Services</a>
            <a href="organisations.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Liste</a>
            <button type="button" class="btn btn-outline-danger ms-auto" data-bs-toggle="modal" data-bs-target="#deleteOrgModal"><i class="bi bi-trash me-1"></i> Supprimer</button>
        </div>
    </div>
    <div class="modal fade" id="deleteOrgModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Supprimer cette organisation ?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    L'organisation <strong><?= htmlspecialchars($detail->name) ?></strong> et toutes les données liées (départements, services, unités) pourront être supprimées. Cette action est irréversible.
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="delete_id" value="<?= (int) $detail->id ?>">
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php elseif (count($list) > 0): ?>
    <div class="admin-card admin-section-card">
        <div class="table-responsive">
            <table class="admin-table table table-hover" id="orgTable">
                <thead>
                    <tr>
                        <th>Organisation</th>
                        <th>Code</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($list as $o): ?>
                        <tr>
                            <td>
                                <a href="organisations.php?id=<?= (int) $o->id ?>"><?= htmlspecialchars($o->name) ?></a>
                            </td>
                            <td><?= htmlspecialchars($o->code ?? '—') ?></td>
                            <td><?= $o->is_active ? '<span class="badge bg-success-subtle text-success border">Active</span>' : '<span class="badge bg-secondary-subtle text-secondary border">Inactive</span>' ?></td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                        <li><a class="dropdown-item" href="organisations.php?id=<?= (int) $o->id ?>"><i class="bi bi-eye me-2"></i> Voir</a></li>
                                        <li><a class="dropdown-item" href="organisations.php?action=edit&id=<?= (int) $o->id ?>"><i class="bi bi-pencil me-2"></i> Modifier</a></li>
                                        <li><a class="dropdown-item" href="units.php?organisation_id=<?= (int) $o->id ?>"><i class="bi bi-diagram-3 me-2"></i> Unités & Services</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" onsubmit="return confirm('Supprimer cette organisation ?');">
                                                <input type="hidden" name="delete_id" value="<?= (int) $o->id ?>">
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
    <div class="admin-card admin-section-card text-center py-5">
        <p class="text-muted mb-3">Aucune organisation.</p>
        <a href="organisations.php?action=add" class="btn btn-admin-primary"><i class="bi bi-building-add me-1"></i> Créer une organisation</a>
    </div>
<?php endif; ?>

<style>
    .breadcrumb { font-size: 0.85rem; }
    .admin-table th { background: #f8f9fa; padding: 1rem 0.5rem; }
    .admin-table td { padding: 1rem 0.5rem; }
</style>
<?php if (!empty($list)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('orgTable') && typeof $ !== 'undefined' && $.fn.DataTable) {
        $('#orgTable').DataTable({
            language: { url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json" },
            order: [[0, "asc"]],
            pageLength: 10,
            dom: '<"d-flex justify-content-between align-items-center mb-3"f>t<"d-flex justify-content-between align-items-center mt-3"ip>'
        });
    }
});
</script>
<?php endif; ?>

<footer class="admin-main-footer">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <a href="index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Tableau de bord</a>
        <span class="small text-muted">&copy; <?= date('Y') ?> Expertise</span>
    </div>
</footer>
<?php require __DIR__ . '/inc/footer.php'; ?>
