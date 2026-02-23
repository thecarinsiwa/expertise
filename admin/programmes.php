<?php
$pageTitle = 'Programmes – Administration';
$currentNav = 'programmes';
require_once __DIR__ . '/inc/auth.php';
require __DIR__ . '/inc/db.php';

$list = [];
$detail = null;
$stats = ['total' => 0, 'active' => 0];
$error = '';
$success = '';
$portfolios = [];

if ($pdo) {
    try {
        $portfolios = $pdo->query("SELECT id, name FROM portfolio WHERE is_active = 1 ORDER BY name")->fetchAll();
    } catch (PDOException $e) {
        $portfolios = [];
    }

    $action = $_GET['action'] ?? 'list';
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['delete_id'])) {
            $delId = (int) $_POST['delete_id'];
            try {
                $pdo->prepare("DELETE FROM programme WHERE id = ?")->execute([$delId]);
                header('Location: programmes.php?msg=deleted');
                exit;
            } catch (PDOException $e) {
                $error = 'Impossible de supprimer : des projets sont peut-être liés à ce programme.';
            }
        }
        if (isset($_POST['save_programme'])) {
            $portfolio_id = (int) ($_POST['portfolio_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $code = trim($_POST['code'] ?? '') ?: null;
            $description = trim($_POST['description'] ?? '') ?: null;
            $start_date = trim($_POST['start_date'] ?? '') ?: null;
            $end_date = trim($_POST['end_date'] ?? '') ?: null;
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            if ($name === '' || $portfolio_id <= 0) {
                $error = 'Le nom et le portfolio sont obligatoires.';
            } else {
                try {
                    if ($id > 0) {
                        $pdo->prepare("UPDATE programme SET portfolio_id = ?, name = ?, code = ?, description = ?, start_date = ?, end_date = ?, is_active = ? WHERE id = ?")
                            ->execute([$portfolio_id, $name, $code, $description, $start_date, $end_date, $is_active, $id]);
                        $success = 'Programme mis à jour.';
                        $action = 'view';
                    } else {
                        $pdo->prepare("INSERT INTO programme (portfolio_id, name, code, description, start_date, end_date, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)")
                            ->execute([$portfolio_id, $name, $code, $description, $start_date, $end_date, $is_active]);
                        header('Location: programmes.php?id=' . $pdo->lastInsertId() . '&msg=created');
                        exit;
                    }
                } catch (PDOException $e) {
                    $error = 'Erreur : ' . $e->getMessage();
                }
            }
        }
    }

    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT p.*, pf.name AS portfolio_name FROM programme p LEFT JOIN portfolio pf ON p.portfolio_id = pf.id WHERE p.id = ?");
        $stmt->execute([$id]);
        $detail = $stmt->fetch();
    }

    if (!$detail || $action === 'list') {
        $stats['total'] = (int) $pdo->query("SELECT COUNT(*) FROM programme")->fetchColumn();
        $stats['active'] = (int) $pdo->query("SELECT COUNT(*) FROM programme WHERE is_active = 1")->fetchColumn();
        $stmt = $pdo->query("
            SELECT p.id, p.name, p.code, p.is_active, p.start_date, p.end_date, pf.name AS portfolio_name,
                   (SELECT COUNT(*) FROM project pr WHERE pr.programme_id = p.id) AS projects_count
            FROM programme p
            LEFT JOIN portfolio pf ON p.portfolio_id = pf.id
            ORDER BY p.name
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
        <?php if ($action === 'add'): ?>
            <li class="breadcrumb-item active">Nouveau programme</li>
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
                if ($action === 'add') echo 'Nouveau programme';
                elseif ($action === 'edit' && $detail) echo 'Modifier le programme';
                elseif ($detail) echo htmlspecialchars($detail->name);
                else echo 'Programmes';
                ?>
            </h1>
            <p class="text-muted mb-0">
                <?php
                if ($detail && !$isForm) echo 'Fiche programme';
                elseif ($action === 'add') echo 'Créer un programme pour y associer des projets.';
                else echo 'Configuration des programmes (portfolios).';
                ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($detail && $action !== 'edit'): ?>
                <a href="programmes.php?action=edit&id=<?= (int) $detail->id ?>" class="btn btn-admin-primary"><i class="bi bi-pencil me-1"></i> Modifier</a>
                <a href="programmes.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Liste</a>
            <?php elseif ($isForm): ?>
                <a href="portfolios.php" class="btn btn-admin-outline" target="_blank"><i class="bi bi-folder me-1"></i> Configuration des portfolios</a>
                <a href="programmes.php<?= $id ? '?id=' . $id : '' ?>" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Annuler</a>
            <?php else: ?>
                <a href="projects.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Projets</a>
                <a href="portfolios.php" class="btn btn-admin-outline"><i class="bi bi-folder me-1"></i> Configuration des portfolios</a>
                <a href="programmes.php?action=add" class="btn btn-admin-primary"><i class="bi bi-plus-lg me-1"></i> Nouveau programme</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success">Le programme a été supprimé.</div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
    <div class="alert alert-success">Programme créé.</div>
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
                <div class="mt-2"><span class="badge bg-secondary">Programmes</span></div>
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
        <h5 class="card-title mb-4"><i class="bi bi-folder2"></i> <?= $id ? 'Modifier le programme' : 'Nouveau programme' ?></h5>
        <form method="POST" action="<?= $id ? 'programmes.php?action=edit&id=' . $id : 'programmes.php?action=add' ?>">
            <input type="hidden" name="save_programme" value="1">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Portfolio *</label>
                    <select name="portfolio_id" class="form-select" required>
                        <option value="">— Choisir —</option>
                        <?php foreach ($portfolios as $pf): ?>
                            <option value="<?= (int) $pf->id ?>" <?= ($detail && $detail->portfolio_id == $pf->id) ? 'selected' : '' ?>><?= htmlspecialchars($pf->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted"><a href="portfolios.php" target="_blank">Configuration des portfolios</a></small>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Nom *</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($detail->name ?? '') ?>" required placeholder="Nom du programme">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Code</label>
                    <input type="text" name="code" class="form-control" value="<?= htmlspecialchars($detail->code ?? '') ?>" placeholder="ex: PROG-01">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Date début</label>
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($detail->start_date ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Date fin</label>
                    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($detail->end_date ?? '') ?>">
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
        <h5 class="card-title"><i class="bi bi-folder2"></i> Informations programme</h5>
        <table class="admin-table mb-0">
            <tr><th style="width:180px;">Nom</th><td><?= htmlspecialchars($detail->name) ?></td></tr>
            <tr><th>Code</th><td><?= htmlspecialchars($detail->code ?? '—') ?></td></tr>
            <tr><th>Portfolio</th><td><?= htmlspecialchars($detail->portfolio_name ?? '—') ?></td></tr>
            <tr><th>Description</th><td><?= nl2br(htmlspecialchars($detail->description ?? '—')) ?></td></tr>
            <tr><th>Dates</th><td><?= $detail->start_date ? date('d/m/Y', strtotime($detail->start_date)) : '—' ?> — <?= $detail->end_date ? date('d/m/Y', strtotime($detail->end_date)) : '—' ?></td></tr>
            <tr><th>Statut</th><td><span class="badge bg-secondary"><?= $detail->is_active ? 'Actif' : 'Inactif' ?></span></td></tr>
        </table>
        <div class="mt-4 d-flex gap-2">
            <a href="programmes.php?action=edit&id=<?= (int) $detail->id ?>" class="btn btn-admin-primary"><i class="bi bi-pencil me-1"></i> Modifier</a>
            <a href="programmes.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Liste</a>
            <button type="button" class="btn btn-outline-danger ms-auto" data-bs-toggle="modal" data-bs-target="#deleteProgrammeModal"><i class="bi bi-trash me-1"></i> Supprimer</button>
        </div>
    </div>
    <div class="modal fade" id="deleteProgrammeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Supprimer ce programme ?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">Le programme <strong><?= htmlspecialchars($detail->name) ?></strong> sera supprimé. Les projets liés ne seront pas supprimés mais n'auront plus de programme. Cette action est irréversible.</div>
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
            <table class="admin-table table table-hover" id="programmesTable">
                <thead>
                    <tr>
                        <th>Programme</th>
                        <th>Portfolio</th>
                        <th>Code</th>
                        <th>Projets</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($list as $pr): ?>
                        <tr>
                            <td><a href="programmes.php?id=<?= (int) $pr->id ?>"><?= htmlspecialchars($pr->name) ?></a></td>
                            <td><?= htmlspecialchars($pr->portfolio_name ?? '—') ?></td>
                            <td><?= htmlspecialchars($pr->code ?? '—') ?></td>
                            <td><span class="badge bg-secondary"><?= (int) ($pr->projects_count ?? 0) ?></span></td>
                            <td><?= $pr->is_active ? '<span class="badge bg-secondary">Actif</span>' : '<span class="badge bg-secondary">Inactif</span>' ?></td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                        <li><a class="dropdown-item" href="programmes.php?id=<?= (int) $pr->id ?>"><i class="bi bi-eye me-2"></i> Voir</a></li>
                                        <li><a class="dropdown-item" href="programmes.php?action=edit&id=<?= (int) $pr->id ?>"><i class="bi bi-pencil me-2"></i> Modifier</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" onsubmit="return confirm('Supprimer ce programme ?');">
                                                <input type="hidden" name="delete_id" value="<?= (int) $pr->id ?>">
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
        if (document.getElementById('programmesTable') && typeof $ !== 'undefined' && $.fn.DataTable) {
            $('#programmesTable').DataTable({ language: { url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json" }, order: [[1, "asc"]], pageLength: 10, dom: '<"d-flex justify-content-between align-items-center mb-3"f>t<"d-flex justify-content-between align-items-center mt-3"ip>' });
        }
    });
    </script>
<?php else: ?>
    <div class="admin-card admin-section-card">
        <p class="admin-empty py-4 mb-0"><i class="bi bi-inbox"></i> Aucun programme. <a href="programmes.php?action=add">Créer un programme</a> pour l'associer aux projets. <?php if (empty($portfolios)): ?> <strong><a href="portfolios.php?action=add">Créez d'abord un portfolio</a></strong>.<?php endif; ?></p>
    </div>
<?php endif; ?>

<footer class="admin-main-footer mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <a href="projects.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Projets & Tâches</a>
        <span class="small text-muted">&copy; <?= date('Y') ?> Expertise</span>
    </div>
</footer>
<?php require __DIR__ . '/inc/footer.php'; ?>
