<?php
$pageTitle = 'Expertises - Administration';
$currentNav = 'expertises';
require_once __DIR__ . '/inc/auth.php';
require_permission('admin.expertises.view');
require __DIR__ . '/inc/db.php';

$organisation_id = 1;
$list = [];
$detail = null;
$error = '';
$success = '';
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($pdo) {
    try {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['delete_id']) && has_permission('admin.expertises.delete')) {
                $deleteId = (int) $_POST['delete_id'];
                $pdo->prepare("DELETE FROM expertise_item WHERE id = ? AND organisation_id = ?")->execute([$deleteId, $organisation_id]);
                header('Location: expertises.php?msg=deleted');
                exit;
            }

            if (isset($_POST['save_expertise']) && (has_permission('admin.expertises.add') || ($id > 0 && has_permission('admin.expertises.modify')))) {
                $title = trim($_POST['title'] ?? '');
                $summary = trim($_POST['summary'] ?? '') ?: null;
                $description = trim($_POST['description'] ?? '') ?: null;
                $display_order = (int) ($_POST['display_order'] ?? 0);
                $is_active = isset($_POST['is_active']) ? 1 : 0;

                if ($title === '') {
                    $error = 'Le titre est obligatoire.';
                } else {
                    if ($id > 0) {
                        $pdo->prepare("
                            UPDATE expertise_item
                            SET title = ?, summary = ?, description = ?, display_order = ?, is_active = ?, updated_at = NOW()
                            WHERE id = ? AND organisation_id = ?
                        ")->execute([$title, $summary, $description, $display_order, $is_active, $id, $organisation_id]);
                        $success = 'Expertise mise a jour.';
                    } else {
                        $pdo->prepare("
                            INSERT INTO expertise_item (organisation_id, title, summary, description, display_order, is_active)
                            VALUES (?, ?, ?, ?, ?, ?)
                        ")->execute([$organisation_id, $title, $summary, $description, $display_order, $is_active]);
                        header('Location: expertises.php?id=' . $pdo->lastInsertId() . '&msg=created');
                        exit;
                    }
                }
            }
        }

        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT * FROM expertise_item WHERE id = ? AND organisation_id = ?");
            $stmt->execute([$id, $organisation_id]);
            $detail = $stmt->fetch();
        }

        $searchQ = trim($_GET['q'] ?? '');
        if ($searchQ !== '') {
            $like = '%' . $searchQ . '%';
            $stmt = $pdo->prepare("
                SELECT id, title, summary, display_order, is_active, updated_at
                FROM expertise_item
                WHERE organisation_id = ?
                  AND (title LIKE ? OR summary LIKE ?)
                ORDER BY display_order ASC, title ASC
            ");
            $stmt->execute([$organisation_id, $like, $like]);
            $list = $stmt->fetchAll();
        } else {
            $stmt = $pdo->prepare("
                SELECT id, title, summary, display_order, is_active, updated_at
                FROM expertise_item
                WHERE organisation_id = ?
                ORDER BY display_order ASC, title ASC
            ");
            $stmt->execute([$organisation_id]);
            $list = $stmt->fetchAll();
        }
    } catch (PDOException $e) {
        if (!(isset($_GET['migrated']) && $_GET['migrated'] === '1')) {
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `expertise_item` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `organisation_id` INT UNSIGNED NOT NULL,
                    `title` VARCHAR(255) NOT NULL,
                    `summary` VARCHAR(500) DEFAULT NULL,
                    `description` LONGTEXT,
                    `display_order` INT NOT NULL DEFAULT 0,
                    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_expertise_org` (`organisation_id`),
                    KEY `idx_expertise_active` (`is_active`),
                    KEY `idx_expertise_order` (`display_order`),
                    CONSTRAINT `fk_expertise_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisation` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                $pdo->exec("INSERT IGNORE INTO `permission` (`module`, `code`, `name`) VALUES
                    ('Expertises', 'admin.expertises.view', 'Expertises - Voir'),
                    ('Expertises', 'admin.expertises.add', 'Expertises - Ajout'),
                    ('Expertises', 'admin.expertises.modify', 'Expertises - Modifier'),
                    ('Expertises', 'admin.expertises.delete', 'Expertises - Supprimer')");
                $pdo->exec("INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`) SELECT 1, id FROM `permission` WHERE `code` LIKE 'admin.expertises.%'");
                $pdo->exec("INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`) SELECT 2, id FROM `permission` WHERE `code` LIKE 'admin.expertises.%'");
                header('Location: expertises.php?migrated=1');
                exit;
            } catch (PDOException $e2) {
                $error = 'Erreur base de donnees (migration echouee) : ' . $e2->getMessage();
            }
        } else {
            $error = 'Erreur base de donnees : ' . $e->getMessage();
        }
    }
}

require __DIR__ . '/inc/header.php';
$isForm = ($action === 'add') || ($action === 'edit' && $detail);
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Tableau de bord</a></li>
        <li class="breadcrumb-item"><a href="expertises.php" class="text-decoration-none">Expertises</a></li>
        <?php if ($action === 'add'): ?>
            <li class="breadcrumb-item active">Nouvelle expertise</li>
        <?php elseif ($action === 'edit' && $detail): ?>
            <li class="breadcrumb-item active">Modifier</li>
        <?php elseif ($detail): ?>
            <li class="breadcrumb-item active"><?= htmlspecialchars($detail->title ?? '') ?></li>
        <?php endif; ?>
    </ol>
</nav>

<header class="admin-header">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h1>
                <?php
                if ($action === 'add') echo 'Nouvelle expertise';
                elseif ($action === 'edit' && $detail) echo 'Modifier l\'expertise';
                elseif ($detail) echo htmlspecialchars($detail->title ?? 'Expertise');
                else echo 'Expertises';
                ?>
            </h1>
            <p class="text-muted mb-0">Gestion du contenu de la page Expertise.</p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($detail && $action !== 'edit'): ?>
                <?php if (has_permission('admin.expertises.modify')): ?>
                    <a href="expertises.php?action=edit&id=<?= (int) $detail->id ?>" class="btn btn-admin-primary"><i class="bi bi-pencil me-1"></i> Modifier</a>
                <?php endif; ?>
                <a href="expertises.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Liste</a>
            <?php elseif ($isForm): ?>
                <a href="expertises.php<?= $id ? '?id=' . $id : '' ?>" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Annuler</a>
            <?php elseif (has_permission('admin.expertises.add')): ?>
                <a href="expertises.php?action=add" class="btn btn-admin-primary"><i class="bi bi-plus-lg me-1"></i> Nouvelle expertise</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success">L'expertise a ete supprimee.</div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
    <div class="alert alert-success">Expertise creee.</div>
<?php endif; ?>
<?php if (isset($_GET['migrated']) && $_GET['migrated'] === '1'): ?>
    <div class="alert alert-success">Module Expertises initialise avec succes.</div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($isForm): ?>
    <div class="admin-card admin-section-card">
        <h5 class="card-title mb-4"><i class="bi bi-stars"></i> <?= $id ? 'Modifier l\'expertise' : 'Nouvelle expertise' ?></h5>
        <form method="POST" action="<?= $id ? 'expertises.php?action=edit&id=' . $id : 'expertises.php?action=add' ?>">
            <input type="hidden" name="save_expertise" value="1">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label fw-bold">Titre *</label>
                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($detail->title ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Ordre d'affichage</label>
                    <input type="number" name="display_order" class="form-control" value="<?= (int) ($detail->display_order ?? 0) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Resume</label>
                    <input type="text" name="summary" class="form-control" maxlength="500" value="<?= htmlspecialchars($detail->summary ?? '') ?>" placeholder="Resume court de l'expertise">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Description</label>
                    <textarea name="description" id="expertise_description" class="form-control" rows="7"></textarea>
                    <script type="application/json" id="expertise_description_data"><?= json_encode(isset($detail->description) ? $detail->description : '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?= (!isset($detail->is_active) || (int) $detail->is_active === 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Visible sur la page publique</label>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-admin-primary"><i class="bi bi-check-lg me-1"></i> Enregistrer</button>
                </div>
            </div>
        </form>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof $ !== 'undefined' && $.fn.summernote) {
            $('#expertise_description').summernote({
                placeholder: 'Description detaillee de l\'expertise...',
                tabsize: 2,
                height: 220,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ]
            });
            var dataEl = document.getElementById('expertise_description_data');
            if (dataEl) {
                try {
                    var content = JSON.parse(dataEl.textContent);
                    if (content) $('#expertise_description').summernote('code', content);
                } catch (e) {}
            }
        }
    });
    </script>
<?php elseif ($detail): ?>
    <div class="admin-card admin-section-card mb-4">
        <h5 class="card-title"><i class="bi bi-stars"></i> Details</h5>
        <table class="admin-table mb-0">
            <tr><th style="width:200px;">Titre</th><td><?= htmlspecialchars($detail->title ?? '') ?></td></tr>
            <tr><th>Resume</th><td><?= htmlspecialchars($detail->summary ?? '—') ?></td></tr>
            <tr><th>Ordre</th><td><?= (int) ($detail->display_order ?? 0) ?></td></tr>
            <tr><th>Visible</th><td><?= (int) ($detail->is_active ?? 0) ? 'Oui' : 'Non' ?></td></tr>
            <tr><th>Description</th><td><?= !empty($detail->description) ? $detail->description : '—' ?></td></tr>
        </table>
        <div class="mt-4 d-flex gap-2">
            <?php if (has_permission('admin.expertises.modify')): ?>
                <a href="expertises.php?action=edit&id=<?= (int) $detail->id ?>" class="btn btn-admin-primary"><i class="bi bi-pencil me-1"></i> Modifier</a>
            <?php endif; ?>
            <a href="expertises.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Liste</a>
            <?php if (has_permission('admin.expertises.delete')): ?>
                <button type="button" class="btn btn-outline-danger ms-auto" data-bs-toggle="modal" data-bs-target="#deleteExpertiseModal"><i class="bi bi-trash me-1"></i> Supprimer</button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (has_permission('admin.expertises.delete')): ?>
    <div class="modal fade" id="deleteExpertiseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Supprimer cette expertise ?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    L'expertise <strong><?= htmlspecialchars($detail->title ?? '') ?></strong> sera supprimee.
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
    <?php endif; ?>
<?php else: ?>
    <form method="GET" action="expertises.php" class="row g-2 mb-4">
        <div class="col-md-6">
            <label class="form-label visually-hidden">Recherche</label>
            <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" placeholder="Rechercher une expertise...">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-admin-outline"><i class="bi bi-search me-1"></i> Filtrer</button>
        </div>
    </form>

    <div class="admin-card admin-section-card">
        <?php if (count($list) > 0): ?>
            <div class="table-responsive">
                <table class="admin-table table table-hover">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Resume</th>
                            <th>Ordre</th>
                            <th>Visible</th>
                            <th>Mise a jour</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($list as $item): ?>
                            <tr>
                                <td><a href="expertises.php?id=<?= (int) $item->id ?>"><?= htmlspecialchars($item->title) ?></a></td>
                                <td><?= htmlspecialchars($item->summary ?? '—') ?></td>
                                <td><?= (int) $item->display_order ?></td>
                                <td><?= (int) $item->is_active ? '<span class="badge bg-success-subtle text-success border">Oui</span>' : '<span class="badge bg-secondary">Non</span>' ?></td>
                                <td><?= !empty($item->updated_at) ? date('d/m/Y H:i', strtotime($item->updated_at)) : '—' ?></td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="expertises.php?id=<?= (int) $item->id ?>"><i class="bi bi-eye me-2"></i> Voir</a></li>
                                            <?php if (has_permission('admin.expertises.modify')): ?>
                                                <li><a class="dropdown-item" href="expertises.php?action=edit&id=<?= (int) $item->id ?>"><i class="bi bi-pencil me-2"></i> Modifier</a></li>
                                            <?php endif; ?>
                                            <?php if (has_permission('admin.expertises.delete')): ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST" onsubmit="return confirm('Supprimer cette expertise ?');">
                                                        <input type="hidden" name="delete_id" value="<?= (int) $item->id ?>">
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
        <?php else: ?>
            <p class="admin-empty py-4 mb-0"><i class="bi bi-inbox"></i> Aucune expertise. <?php if (has_permission('admin.expertises.add')): ?><a href="expertises.php?action=add">Creer une expertise</a><?php endif; ?></p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<footer class="admin-main-footer mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <a href="index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Tableau de bord</a>
        <span class="small text-muted">&copy; <?= date('Y') ?> Expertise</span>
    </div>
</footer>
<?php require __DIR__ . '/inc/footer.php'; ?>
