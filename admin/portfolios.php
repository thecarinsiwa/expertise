<?php
$pageTitle = 'Portfolios – Administration';
$currentNav = 'portfolios';
require_once __DIR__ . '/inc/auth.php';
require_permission('admin.portfolios.view');
require __DIR__ . '/inc/db.php';

$list = [];
$detail = null;
$stats = ['total' => 0, 'active' => 0];
$error = '';
$success = '';
$organisations = [];

if ($pdo) {
    try {
        $organisations = $pdo->query("SELECT id, name FROM organisation WHERE is_active = 1 ORDER BY name")->fetchAll();
    } catch (PDOException $e) {
        $organisations = [];
    }

    $action = $_GET['action'] ?? 'list';
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['delete_id'])) {
            $delId = (int) $_POST['delete_id'];
            try {
                $pdo->prepare("DELETE FROM portfolio WHERE id = ?")->execute([$delId]);
                header('Location: portfolios.php?msg=deleted');
                exit;
            } catch (PDOException $e) {
                $error = 'Impossible de supprimer : des programmes sont liés à ce portfolio.';
            }
        }
        if (isset($_POST['save_portfolio'])) {
            $organisation_id = (int) ($_POST['organisation_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $code = trim($_POST['code'] ?? '') ?: null;
            $description = trim($_POST['description'] ?? '') ?: null;
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            if ($name === '' || $organisation_id <= 0) {
                $error = 'Le nom et l\'organisation sont obligatoires.';
            } else {
                try {
                    if ($id > 0) {
                        $pdo->prepare("UPDATE portfolio SET organisation_id = ?, name = ?, code = ?, description = ?, is_active = ? WHERE id = ?")
                            ->execute([$organisation_id, $name, $code, $description, $is_active, $id]);
                        $success = 'Portfolio mis à jour.';
                        $action = 'view';
                    } else {
                        $pdo->prepare("INSERT INTO portfolio (organisation_id, name, code, description, is_active) VALUES (?, ?, ?, ?, ?)")
                            ->execute([$organisation_id, $name, $code, $description, $is_active]);
                        header('Location: portfolios.php?id=' . $pdo->lastInsertId() . '&msg=created');
                        exit;
                    }
                } catch (PDOException $e) {
                    $error = 'Erreur : ' . $e->getMessage();
                }
            }
        }
    }

    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT pf.*, o.name AS organisation_name FROM portfolio pf LEFT JOIN organisation o ON pf.organisation_id = o.id WHERE pf.id = ?");
        $stmt->execute([$id]);
        $detail = $stmt->fetch();
    }

    if (!$detail || $action === 'list') {
        $stats['total'] = (int) $pdo->query("SELECT COUNT(*) FROM portfolio")->fetchColumn();
        $stats['active'] = (int) $pdo->query("SELECT COUNT(*) FROM portfolio WHERE is_active = 1")->fetchColumn();
        $stmt = $pdo->query("
            SELECT pf.id, pf.name, pf.code, pf.is_active, o.name AS organisation_name,
                   (SELECT COUNT(*) FROM programme p WHERE p.portfolio_id = pf.id) AS programmes_count
            FROM portfolio pf
            LEFT JOIN organisation o ON pf.organisation_id = o.id
            ORDER BY pf.name
        ");
        if ($stmt) $list = $stmt->fetchAll();
    }
}
require __DIR__ . '/inc/header.php';

$isForm = ($action === 'add') || ($action === 'edit' && $detail);
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Tableau de bord</a></li>
        <li class="breadcrumb-item"><a href="projects.php" class="text-decoration-none">Projets & Tâches</a></li>
        <li class="breadcrumb-item"><a href="programmes.php" class="text-decoration-none">Programmes</a></li>
        <li class="breadcrumb-item"><a href="portfolios.php" class="text-decoration-none">Portfolios</a></li>
        <?php if ($action === 'add'): ?>
            <li class="breadcrumb-item active">Nouveau portfolio</li>
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
                if ($action === 'add') echo 'Nouveau portfolio';
                elseif ($action === 'edit' && $detail) echo 'Modifier le portfolio';
                elseif ($detail) echo htmlspecialchars($detail->name);
                else echo 'Portfolios';
                ?>
            </h1>
            <p class="text-muted mb-0">
                <?php
                if ($detail && !$isForm) echo 'Fiche portfolio';
                elseif ($action === 'add') echo 'Créer un portfolio pour y rattacher des programmes.';
                else echo 'Configuration des portfolios.';
                ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($detail && $action !== 'edit'): ?>
                <a href="portfolios.php?action=edit&id=<?= (int) $detail->id ?>" class="btn btn-admin-primary"><i class="bi bi-pencil me-1"></i> Modifier</a>
                <a href="portfolios.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Liste</a>
            <?php elseif ($isForm): ?>
                <a href="portfolios.php<?= $id ? '?id=' . $id : '' ?>" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Annuler</a>
            <?php else: ?>
                <a href="programmes.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Programmes</a>
                <a href="portfolios.php?action=add" class="btn btn-admin-primary"><i class="bi bi-plus-lg me-1"></i> Nouveau portfolio</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success">Le portfolio a été supprimé.</div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
    <div class="alert alert-success">Portfolio créé.</div>
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
                <div class="mt-2"><span class="badge bg-secondary">Portfolios</span></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="admin-card text-center p-3 h-100">
                <div class="text-muted small text-uppercase mb-1 fw-bold">Actifs</div>
                <div class="h3 mb-0 fw-bold text-primary"><?= $stats['active'] ?></div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($isForm): ?>
    <div class="admin-card admin-section-card">
        <h5 class="card-title mb-4"><i class="bi bi-folder"></i> <?= $id ? 'Modifier le portfolio' : 'Nouveau portfolio' ?></h5>
        <form method="POST" action="<?= $id ? 'portfolios.php?action=edit&id=' . $id : 'portfolios.php?action=add' ?>">
            <input type="hidden" name="save_portfolio" value="1">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Organisation *</label>
                    <select name="organisation_id" class="form-select" required>
                        <option value="">— Choisir —</option>
                        <?php foreach ($organisations as $o): ?>
                            <option value="<?= (int) $o->id ?>" <?= ($detail && $detail->organisation_id == $o->id) ? 'selected' : '' ?>><?= htmlspecialchars($o->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Nom *</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($detail->name ?? '') ?>" required placeholder="Nom du portfolio">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Code</label>
                    <input type="text" name="code" class="form-control" value="<?= htmlspecialchars($detail->code ?? '') ?>" placeholder="ex: PORT-01">
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
    <div class="admin-card admin-section-card mb-4">
        <h5 class="card-title"><i class="bi bi-folder"></i> Informations portfolio</h5>
        <table class="admin-table mb-0">
            <tr><th style="width:180px;">Nom</th><td><?= htmlspecialchars($detail->name) ?></td></tr>
            <tr><th>Code</th><td><?= htmlspecialchars($detail->code ?? '—') ?></td></tr>
            <tr><th>Organisation</th><td><?= htmlspecialchars($detail->organisation_name ?? '—') ?></td></tr>
            <tr><th>Description</th><td><?= nl2br(htmlspecialchars($detail->description ?? '—')) ?></td></tr>
            <tr><th>Statut</th><td><span class="badge bg-secondary"><?= $detail->is_active ? 'Actif' : 'Inactif' ?></span></td></tr>
        </table>
        <div class="mt-4 d-flex gap-2">
            <a href="portfolios.php?action=edit&id=<?= (int) $detail->id ?>" class="btn btn-admin-primary"><i class="bi bi-pencil me-1"></i> Modifier</a>
            <a href="portfolios.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Liste</a>
            <button type="button" class="btn btn-outline-danger ms-auto" data-bs-toggle="modal" data-bs-target="#deletePortfolioModal"><i class="bi bi-trash me-1"></i> Supprimer</button>
        </div>
    </div>
    <div class="modal fade" id="deletePortfolioModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Supprimer ce portfolio ?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">Le portfolio <strong><?= htmlspecialchars($detail->name) ?></strong> sera supprimé. Les programmes liés seront également supprimés. Cette action est irréversible.</div>
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
            <table class="admin-table table table-hover" id="portfoliosTable">
                <thead>
                    <tr>
                        <th>Portfolio</th>
                        <th>Organisation</th>
                        <th>Code</th>
                        <th>Programmes</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($list as $pf): ?>
                        <tr>
                            <td><a href="portfolios.php?id=<?= (int) $pf->id ?>"><?= htmlspecialchars($pf->name) ?></a></td>
                            <td><?= htmlspecialchars($pf->organisation_name ?? '—') ?></td>
                            <td><?= htmlspecialchars($pf->code ?? '—') ?></td>
                            <td><span class="badge bg-secondary"><?= (int) ($pf->programmes_count ?? 0) ?></span></td>
                            <td><?= $pf->is_active ? '<span class="badge bg-secondary">Actif</span>' : '<span class="badge bg-secondary">Inactif</span>' ?></td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                        <li><a class="dropdown-item" href="portfolios.php?id=<?= (int) $pf->id ?>"><i class="bi bi-eye me-2"></i> Voir</a></li>
                                        <li><a class="dropdown-item" href="portfolios.php?action=edit&id=<?= (int) $pf->id ?>"><i class="bi bi-pencil me-2"></i> Modifier</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" onsubmit="return confirm('Supprimer ce portfolio et ses programmes ?');">
                                                <input type="hidden" name="delete_id" value="<?= (int) $pf->id ?>">
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
    <style>.breadcrumb{font-size:0.85rem;} .admin-table th{background:#f8f9fa;padding:1rem 0.5rem;} .admin-table td{padding:1rem 0.5rem;}</style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('portfoliosTable') && typeof $ !== 'undefined' && $.fn.DataTable) {
            $('#portfoliosTable').DataTable({ language: { url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json" }, order: [[1, "asc"]], pageLength: 10, dom: '<"d-flex justify-content-between align-items-center mb-3"f>t<"d-flex justify-content-between align-items-center mt-3"ip>' });
        }
    });
    </script>
<?php else: ?>
    <div class="admin-card admin-section-card">
        <p class="admin-empty py-4 mb-0"><i class="bi bi-inbox"></i> Aucun portfolio. <a href="portfolios.php?action=add">Créer un portfolio</a> pour pouvoir y rattacher des programmes.</p>
    </div>
<?php endif; ?>

<footer class="admin-main-footer mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <a href="programmes.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Programmes</a>
        <span class="small text-muted">&copy; <?= date('Y') ?> Expertise</span>
    </div>
</footer>
<?php require __DIR__ . '/inc/footer.php'; ?>
