<?php
$pageTitle = 'Bailleurs de fonds – Administration';
$currentNav = 'bailleurs';
require_once __DIR__ . '/inc/auth.php';
require __DIR__ . '/inc/db.php';

$list = [];
$detail = null;
$stats = ['total' => 0, 'active' => 0, 'projects_linked' => 0];
$error = '';
$success = '';

if ($pdo) {
    $action = $_GET['action'] ?? 'list';
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    try {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['delete_id'])) {
                $delId = (int) $_POST['delete_id'];
                $pdo->prepare("DELETE FROM bailleur WHERE id = ?")->execute([$delId]);
                header('Location: bailleurs.php?msg=deleted');
                exit;
            }
            if (isset($_POST['save_bailleur'])) {
                $name = trim($_POST['name'] ?? '');
                $code = trim($_POST['code'] ?? '') ?: null;
                $description = trim($_POST['description'] ?? '') ?: null;
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                $logo = null;
                if ($id > 0) {
                    $cur = $pdo->prepare("SELECT logo FROM bailleur WHERE id = ?");
                    $cur->execute([$id]);
                    $row = $cur->fetch();
                    if ($row && !empty($row->logo)) $logo = $row->logo;
                }
                if (!empty($_FILES['logo']['name'])) {
                    $target_dir = __DIR__ . '/../uploads/bailleurs/';
                    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                    $file_ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                    $file_name = 'logo_' . ($id ?: 'new') . '_' . time() . '_' . uniqid() . '.' . $file_ext;
                    if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_dir . $file_name)) {
                        $logo = 'uploads/bailleurs/' . $file_name;
                    }
                }
                if ($name === '') {
                    $error = 'Le nom est obligatoire.';
                } else {
                    if ($id > 0) {
                        $pdo->prepare("UPDATE bailleur SET name = ?, code = ?, description = ?, logo = ?, is_active = ? WHERE id = ?")->execute([$name, $code, $description, $logo, $is_active, $id]);
                        $success = 'Bailleur mis à jour.';
                        $action = 'view';
                        $stmt = $pdo->prepare("SELECT * FROM bailleur WHERE id = ?");
                        $stmt->execute([$id]);
                        $detail = $stmt->fetch();
                    } else {
                        $pdo->prepare("INSERT INTO bailleur (name, code, description, logo, is_active) VALUES (?, ?, ?, ?, ?)")->execute([$name, $code, $description, $logo, $is_active]);
                        header('Location: bailleurs.php?id=' . $pdo->lastInsertId() . '&msg=created');
                        exit;
                    }
                }
            }
        }

        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT * FROM bailleur WHERE id = ?");
            $stmt->execute([$id]);
            $detail = $stmt->fetch();
        }

        if (!$detail || $action === 'list') {
            $stats['total'] = (int) $pdo->query("SELECT COUNT(*) FROM bailleur")->fetchColumn();
            $stats['active'] = (int) $pdo->query("SELECT COUNT(*) FROM bailleur WHERE is_active = 1")->fetchColumn();
            $stats['projects_linked'] = (int) $pdo->query("SELECT COUNT(*) FROM project_bailleur")->fetchColumn();
            $stmt = $pdo->query("
                SELECT b.id, b.name, b.code, b.logo, b.is_active,
                       (SELECT COUNT(*) FROM project_bailleur pb WHERE pb.bailleur_id = b.id) AS projects_count
                FROM bailleur b ORDER BY b.name
            ");
            if ($stmt) $list = $stmt->fetchAll();
        }
    } catch (PDOException $e) {
        $error = 'Erreur base de données. Les tables bailleur / project_bailleur existent-elles ?';
    }
}
require __DIR__ . '/inc/header.php';

$isForm = ($action === 'add') || ($action === 'edit' && $detail);
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Tableau de bord</a></li>
        <li class="breadcrumb-item"><a href="bailleurs.php" class="text-decoration-none">Bailleurs de fonds</a></li>
        <?php if ($action === 'add'): ?>
            <li class="breadcrumb-item active">Nouveau bailleur</li>
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
                if ($action === 'add') echo 'Nouveau bailleur';
                elseif ($action === 'edit' && $detail) echo 'Modifier le bailleur';
                elseif ($detail) echo htmlspecialchars($detail->name);
                else echo 'Bailleurs de fonds';
                ?>
            </h1>
            <p class="text-muted mb-0">
                <?php
                if ($detail && !$isForm) echo 'Fiche bailleur de fonds';
                elseif ($action === 'add') echo 'Créer un nouveau bailleur de fonds.';
                else echo 'Gestion des bailleurs de fonds associés aux projets.';
                ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($detail && $action !== 'edit'): ?>
                <a href="bailleurs.php?action=edit&id=<?= (int) $detail->id ?>" class="btn btn-admin-primary"><i class="bi bi-pencil me-1"></i> Modifier</a>
                <a href="bailleurs.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Liste</a>
            <?php elseif ($isForm): ?>
                <a href="bailleurs.php<?= $id ? '?id=' . $id : '' ?>" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Annuler</a>
            <?php else: ?>
                <a href="bailleurs.php?action=add" class="btn btn-admin-primary"><i class="bi bi-bank me-1"></i> Nouveau bailleur</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success">Le bailleur a été supprimé.</div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
    <div class="alert alert-success">Bailleur créé.</div>
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
                <div class="mt-2"><span class="badge bg-secondary">Bailleurs</span></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="admin-card text-center p-3 h-100">
                <div class="text-muted small text-uppercase mb-1 fw-bold">Actifs</div>
                <div class="h3 mb-0 fw-bold text-primary"><?= $stats['active'] ?></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="admin-card text-center p-3 h-100">
                <div class="text-muted small text-uppercase mb-1 fw-bold">Liaisons projets</div>
                <div class="h3 mb-0 fw-bold text-muted"><?= $stats['projects_linked'] ?></div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($isForm): ?>
    <div class="admin-card admin-section-card">
        <h5 class="card-title mb-4"><i class="bi bi-bank"></i> <?= $id ? 'Modifier le bailleur' : 'Nouveau bailleur' ?></h5>
        <form method="POST" action="<?= $id ? 'bailleurs.php?action=edit&id=' . $id : 'bailleurs.php?action=add' ?>" enctype="multipart/form-data">
            <input type="hidden" name="save_bailleur" value="1">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-bold">Logo</label>
                    <?php if ($detail && !empty($detail->logo)): ?>
                        <div class="mb-2">
                            <img src="../<?= htmlspecialchars($detail->logo) ?>" alt="Logo" class="rounded border" style="max-height: 80px; object-fit: contain;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="logo" class="form-control" accept="image/*">
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-bold">Nom *</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($detail->name ?? '') ?>" required placeholder="Nom du bailleur">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Code</label>
                    <input type="text" name="code" class="form-control" value="<?= htmlspecialchars($detail->code ?? '') ?>" placeholder="ex: BAIL-01">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Description</label>
                    <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($detail->description ?? '') ?></textarea>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" <?= ($detail->is_active ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Actif</label>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-admin-primary"><i class="bi bi-check-lg me-1"></i> Enregistrer</button>
                </div>
            </div>
        </form>
    </div>
<?php elseif ($detail): ?>
    <?php if (!empty($detail->logo)): ?>
    <div class="mb-4 rounded overflow-hidden border shadow-sm bg-light d-inline-block p-2">
        <img src="../<?= htmlspecialchars($detail->logo) ?>" alt="Logo" style="max-height: 120px; max-width: 200px; object-fit: contain;">
    </div>
    <?php endif; ?>
    <div class="admin-card admin-section-card mb-4">
        <h5 class="card-title"><i class="bi bi-bank"></i> Informations bailleur</h5>
        <table class="admin-table mb-0">
            <tr><th style="width:180px;">Nom</th><td><?= htmlspecialchars($detail->name) ?></td></tr>
            <tr><th>Code</th><td><?= htmlspecialchars($detail->code ?? '—') ?></td></tr>
            <tr><th>Description</th><td><?= nl2br(htmlspecialchars($detail->description ?? '—')) ?></td></tr>
            <tr><th>Statut</th><td><span class="badge bg-secondary"><?= $detail->is_active ? 'Actif' : 'Inactif' ?></span></td></tr>
        </table>
        <div class="mt-4 d-flex gap-2">
            <a href="bailleurs.php?action=edit&id=<?= (int) $detail->id ?>" class="btn btn-admin-primary"><i class="bi bi-pencil me-1"></i> Modifier</a>
            <a href="bailleurs.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Liste</a>
            <button type="button" class="btn btn-outline-danger ms-auto" data-bs-toggle="modal" data-bs-target="#deleteBailleurModal"><i class="bi bi-trash me-1"></i> Supprimer</button>
        </div>
    </div>

    <!-- Modal Suppression -->
    <div class="modal fade" id="deleteBailleurModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Supprimer ce bailleur ?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    Le bailleur <strong><?= htmlspecialchars($detail->name) ?></strong> sera supprimé. Les liaisons avec les projets seront retirées. Cette action est irréversible.
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
            <table class="admin-table table table-hover" id="bailleursTable">
                <thead>
                    <tr>
                        <th style="width:70px;">Logo</th>
                        <th>Bailleur</th>
                        <th>Code</th>
                        <th>Projets</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($list as $b): ?>
                        <tr>
                            <td>
                                <?php if (!empty($b->logo)): ?>
                                    <img src="../<?= htmlspecialchars($b->logo) ?>" alt="" class="rounded border" style="height: 40px; width: 40px; object-fit: contain;">
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="bailleurs.php?id=<?= (int) $b->id ?>"><?= htmlspecialchars($b->name) ?></a>
                            </td>
                            <td><?= htmlspecialchars($b->code ?? '—') ?></td>
                            <td><span class="badge bg-secondary"><?= (int) ($b->projects_count ?? 0) ?></span></td>
                            <td><?= $b->is_active ? '<span class="badge bg-secondary">Actif</span>' : '<span class="badge bg-secondary">Inactif</span>' ?></td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                        <li><a class="dropdown-item" href="bailleurs.php?id=<?= (int) $b->id ?>"><i class="bi bi-eye me-2"></i> Voir</a></li>
                                        <li><a class="dropdown-item" href="bailleurs.php?action=edit&id=<?= (int) $b->id ?>"><i class="bi bi-pencil me-2"></i> Modifier</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" onsubmit="return confirm('Supprimer ce bailleur ?');">
                                                <input type="hidden" name="delete_id" value="<?= (int) $b->id ?>">
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
        <p class="admin-empty py-4 mb-0"><i class="bi bi-inbox"></i> Aucun bailleur. <a href="bailleurs.php?action=add">Créer un bailleur</a> pour pouvoir l’associer aux projets.</p>
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
    if (document.getElementById('bailleursTable') && typeof $ !== 'undefined' && $.fn.DataTable) {
        $('#bailleursTable').DataTable({
            language: { url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json" },
            order: [[1, "asc"]],
            pageLength: 10,
            dom: '<"d-flex justify-content-between align-items-center mb-3"f>t<"d-flex justify-content-between align-items-center mt-3"ip>'
        });
    }
});
</script>
<?php endif; ?>

<footer class="admin-main-footer mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <a href="projects.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Projets & Tâches</a>
        <span class="small text-muted">&copy; <?= date('Y') ?> Expertise</span>
    </div>
</footer>
<?php require __DIR__ . '/inc/footer.php'; ?>
