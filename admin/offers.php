<?php
/**
 * Gestion des offres (Nos offres) – CRUD + candidatures
 */
$pageTitle = 'Nos offres – Administration';
$currentNav = 'offers';
require_once __DIR__ . '/inc/auth.php';
require_permission('admin.offers.view');
require __DIR__ . '/inc/db.php';

$list = [];
$detail = null;
$applications = [];
$stats = ['total' => 0, 'draft' => 0, 'published' => 0, 'closed' => 0];
$error = '';
$success = '';
$organisation_id = 1;
$missions = [];
$projects = [];
$action = 'list';
$id = 0;

$statusLabels = [
    'draft' => 'Brouillon',
    'published' => 'Publiée',
    'closed' => 'Clôturée',
];
$applicationStatusLabels = [
    'pending' => 'En attente',
    'reviewed' => 'Examinée',
    'accepted' => 'Acceptée',
    'rejected' => 'Refusée',
];

if ($pdo) {
    try {
        $missions = $pdo->query("SELECT id, title FROM mission WHERE organisation_id = $organisation_id ORDER BY title")->fetchAll();
        $projects = $pdo->query("SELECT id, name FROM project WHERE organisation_id = $organisation_id ORDER BY name")->fetchAll();
    } catch (PDOException $e) {
        $missions = [];
        $projects = [];
    }

    try {
        $action = $_GET['action'] ?? 'list';
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['delete_id'])) {
            $delId = (int) $_POST['delete_id'];
            if (has_permission('admin.offers.delete')) {
                $pdo->prepare("DELETE FROM offer WHERE id = ? AND organisation_id = ?")->execute([$delId, $organisation_id]);
                header('Location: offers.php?msg=deleted');
                exit;
            }
        }
        if (isset($_POST['save_offer']) && (has_permission('admin.offers.add') || ($id > 0 && has_permission('admin.offers.modify')))) {
            $title = trim($_POST['title'] ?? '');
            $reference = trim($_POST['reference'] ?? '') ?: null;
            $description = trim($_POST['description'] ?? '') ?: null;
            $mission_id = !empty($_POST['mission_id']) ? (int) $_POST['mission_id'] : null;
            $project_id = !empty($_POST['project_id']) ? (int) $_POST['project_id'] : null;
            $status = in_array($_POST['status'] ?? '', ['draft', 'published', 'closed']) ? $_POST['status'] : 'draft';
            $published_at = !empty($_POST['published_at']) ? $_POST['published_at'] : null;
            $deadline_at = !empty($_POST['deadline_at']) ? $_POST['deadline_at'] : null;

            $cover_image = null;
            if ($id > 0) {
                $row = $pdo->prepare("SELECT cover_image FROM offer WHERE id = ?");
                $row->execute([$id]);
                $r = $row->fetch();
                if ($r && !empty($r->cover_image)) $cover_image = $r->cover_image;
            }
            if (!empty($_FILES['cover_image']['name'])) {
                $target_dir = __DIR__ . '/../uploads/offers/';
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                $ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
                $file_name = 'cover_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $target_dir . $file_name)) {
                    $cover_image = 'uploads/offers/' . $file_name;
                }
            }

            if ($title === '') {
                $error = 'Le titre est obligatoire.';
            } else {
                if ($id > 0) {
                    $pdo->prepare("
                        UPDATE offer SET title = ?, reference = ?, description = ?, cover_image = COALESCE(?, cover_image),
                        mission_id = ?, project_id = ?, status = ?, published_at = ?, deadline_at = ?, updated_at = NOW()
                        WHERE id = ? AND organisation_id = ?
                    ")->execute([$title, $reference, $description, $cover_image, $mission_id, $project_id, $status, $published_at, $deadline_at, $id, $organisation_id]);
                    $success = 'Offre mise à jour.';
                    $stmt = $pdo->prepare("SELECT o.*, m.title AS mission_title, p.name AS project_name FROM offer o LEFT JOIN mission m ON o.mission_id = m.id LEFT JOIN project p ON o.project_id = p.id WHERE o.id = ? AND o.organisation_id = ?");
                    $stmt->execute([$id, $organisation_id]);
                    $detail = $stmt->fetch();
                } else {
                    $pdo->prepare("
                        INSERT INTO offer (organisation_id, title, reference, description, cover_image, mission_id, project_id, status, published_at, deadline_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ")->execute([$organisation_id, $title, $reference, $description, $cover_image, $mission_id, $project_id, $status, $published_at, $deadline_at]);
                    header('Location: offers.php?id=' . $pdo->lastInsertId() . '&msg=created');
                    exit;
                }
            }
        }
        if (isset($_POST['update_application_status'])) {
            $app_id = (int) ($_POST['application_id'] ?? 0);
            $new_status = $_POST['application_status'] ?? '';
            if ($app_id && in_array($new_status, ['pending', 'reviewed', 'accepted', 'rejected'])) {
                $pdo->prepare("UPDATE offer_application SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$new_status, $app_id]);
                $success = 'Statut de la candidature mis à jour.';
            }
        }
    }

    if ($id > 0 && !$detail) {
        $stmt = $pdo->prepare("SELECT o.*, m.title AS mission_title, p.name AS project_name FROM offer o LEFT JOIN mission m ON o.mission_id = m.id LEFT JOIN project p ON o.project_id = p.id WHERE o.id = ? AND o.organisation_id = ?");
        $stmt->execute([$id, $organisation_id]);
        $detail = $stmt->fetch();
    }
    if ($detail && $id > 0) {
        $stmt = $pdo->prepare("
            SELECT a.id, a.user_id, a.message, a.cv_path, a.status, a.created_at,
                   u.first_name, u.last_name, u.email
            FROM offer_application a
            JOIN user u ON u.id = a.user_id
            WHERE a.offer_id = ?
            ORDER BY a.created_at DESC
        ");
        $stmt->execute([$id]);
        $applications = $stmt->fetchAll();
    }

    if (!$detail || $action === 'list') {
        $statusFilter = isset($_GET['status']) && isset($statusLabels[$_GET['status']]) ? $_GET['status'] : '';
        $searchQ = trim($_GET['q'] ?? '');
        $whereCount = "offer.organisation_id = $organisation_id";
        $whereList = "o.organisation_id = $organisation_id";
        $params = [];
        if ($statusFilter !== '') { $whereCount .= " AND offer.status = ?"; $whereList .= " AND o.status = ?"; $params[] = $statusFilter; }
        if ($searchQ !== '') { $like = '%' . $searchQ . '%'; $whereCount .= " AND (offer.title LIKE ? OR offer.reference LIKE ?)"; $whereList .= " AND (o.title LIKE ? OR o.reference LIKE ?)"; $params[] = $like; $params[] = $like; }
        $q = $pdo->prepare("SELECT COUNT(*) FROM offer WHERE $whereCount");
        $params ? $q->execute($params) : $q->execute();
        $stats['total'] = (int) $q->fetchColumn();
        foreach (['draft', 'published', 'closed'] as $s) {
            $stats[$s] = (int) $pdo->query("SELECT COUNT(*) FROM offer WHERE organisation_id = $organisation_id AND status = '$s'")->fetchColumn();
        }
        $sql = "SELECT o.id, o.title, o.reference, o.status, o.published_at, o.deadline_at, o.updated_at,
                       o.mission_id, o.project_id, m.title AS mission_title, p.name AS project_name,
                       (SELECT COUNT(*) FROM offer_application oa WHERE oa.offer_id = o.id) AS applications_count
                FROM offer o
                LEFT JOIN mission m ON o.mission_id = m.id
                LEFT JOIN project p ON o.project_id = p.id
                WHERE $whereList
                ORDER BY o.updated_at DESC";
        $q = $pdo->prepare($sql);
        $params ? $q->execute($params) : $q->execute();
        $list = $q->fetchAll();
    }
    } catch (PDOException $e) {
        if ($pdo && !(isset($_GET['migrated']) && $_GET['migrated'] === '1')) {
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `offer` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `organisation_id` INT UNSIGNED NOT NULL,
                    `title` VARCHAR(255) NOT NULL,
                    `reference` VARCHAR(100) DEFAULT NULL,
                    `description` LONGTEXT,
                    `cover_image` VARCHAR(500) DEFAULT NULL,
                    `mission_id` INT UNSIGNED DEFAULT NULL,
                    `project_id` INT UNSIGNED DEFAULT NULL,
                    `status` ENUM('draft','published','closed') NOT NULL DEFAULT 'draft',
                    `published_at` DATE DEFAULT NULL,
                    `deadline_at` DATE DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_offer_organisation` (`organisation_id`),
                    KEY `idx_offer_mission` (`mission_id`),
                    KEY `idx_offer_project` (`project_id`),
                    KEY `idx_offer_status` (`status`),
                    CONSTRAINT `fk_offer_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisation` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_offer_mission` FOREIGN KEY (`mission_id`) REFERENCES `mission` (`id`) ON DELETE SET NULL,
                    CONSTRAINT `fk_offer_project` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                $pdo->exec("CREATE TABLE IF NOT EXISTS `offer_application` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `offer_id` INT UNSIGNED NOT NULL,
                    `user_id` INT UNSIGNED NOT NULL,
                    `message` TEXT DEFAULT NULL,
                    `cv_path` VARCHAR(500) DEFAULT NULL,
                    `status` ENUM('pending','reviewed','accepted','rejected') NOT NULL DEFAULT 'pending',
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_offer_application_offer_user` (`offer_id`, `user_id`),
                    KEY `idx_offer_application_offer` (`offer_id`),
                    KEY `idx_offer_application_user` (`user_id`),
                    CONSTRAINT `fk_offer_application_offer` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_offer_application_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                $pdo->exec("INSERT IGNORE INTO `permission` (`module`, `code`, `name`) VALUES ('Nos offres', 'admin.offers.view', 'Nos offres – Voir'), ('Nos offres', 'admin.offers.add', 'Nos offres – Ajout'), ('Nos offres', 'admin.offers.modify', 'Nos offres – Modifier'), ('Nos offres', 'admin.offers.delete', 'Nos offres – Supprimer')");
                $pdo->exec("INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`) SELECT 1, id FROM `permission` WHERE `code` LIKE 'admin.offers.%'");
                $pdo->exec("INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`) SELECT 2, id FROM `permission` WHERE `code` LIKE 'admin.offers.%'");
                header('Location: offers.php?migrated=1');
                exit;
            } catch (PDOException $e2) {
                $error = 'Erreur base de données (migration échouée) : ' . $e2->getMessage();
            }
        } else {
            if (isset($_GET['migrated']) && $_GET['migrated'] === '1') {
                $error = 'Erreur base de données après migration : ' . $e->getMessage();
            } elseif ($pdo) {
                $error = 'Erreur base de données (migration échouée) : ' . $e->getMessage();
            } else {
                $error = 'Erreur base de données. Exécutez database/add_offers_module.sql si les tables offer / offer_application n\'existent pas.';
            }
        }
    }
}

require __DIR__ . '/inc/header.php';
$isForm = ($action === 'add') || ($action === 'edit' && $detail);
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Tableau de bord</a></li>
        <li class="breadcrumb-item"><a href="offers.php" class="text-decoration-none">Nos offres</a></li>
        <?php if ($action === 'add'): ?>
            <li class="breadcrumb-item active">Nouvelle offre</li>
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
                if ($action === 'add') echo 'Nouvelle offre';
                elseif ($action === 'edit' && $detail) echo 'Modifier l\'offre';
                elseif ($detail) echo htmlspecialchars($detail->title ?? 'Offre');
                else echo 'Nos offres';
                ?>
            </h1>
            <p class="text-muted mb-0">
                <?php
                if ($detail && !$isForm) echo 'Offre publiée sur le site ; les clients peuvent postuler depuis leur espace.';
                elseif ($action === 'add') echo 'Créer une offre (mission, projet ou standalone).';
                else echo 'Offres liées à une mission, un projet ou indépendantes.';
                ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($detail && $action !== 'edit'): ?>
                <?php if (has_permission('admin.offers.modify')): ?>
                    <a href="offers.php?action=edit&id=<?= (int) $detail->id ?>" class="btn btn-admin-primary"><i class="bi bi-pencil me-1"></i> Modifier</a>
                <?php endif; ?>
                <a href="offers.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Liste</a>
            <?php elseif ($isForm): ?>
                <a href="offers.php<?= $id ? '?id=' . $id : '' ?>" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Annuler</a>
            <?php elseif (has_permission('admin.offers.add')): ?>
                <a href="offers.php?action=add" class="btn btn-admin-primary"><i class="bi bi-plus-lg me-1"></i> Nouvelle offre</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success">L'offre a été supprimée.</div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
    <div class="alert alert-success">Offre créée.</div>
<?php endif; ?>
<?php if (isset($_GET['migrated']) && $_GET['migrated'] === '1'): ?>
    <div class="alert alert-success">Tables Nos offres créées avec succès. Vous pouvez maintenant gérer les offres.</div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (!$detail && !$isForm): ?>
    <form method="get" action="offers.php" class="row g-2 mb-4">
        <div class="col-md-4">
            <label class="form-label visually-hidden">Statut</label>
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">Tous les statuts</option>
                <?php foreach ($statusLabels as $k => $v): ?>
                    <option value="<?= $k ?>" <?= (isset($_GET['status']) && $_GET['status'] === $k) ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label visually-hidden">Recherche</label>
            <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" placeholder="Titre ou référence…">
            <?php if (isset($_GET['status'])): ?><input type="hidden" name="status" value="<?= htmlspecialchars($_GET['status']) ?>"><?php endif; ?>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-admin-outline"><i class="bi bi-search me-1"></i> Filtrer</button>
        </div>
    </form>
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="admin-card text-center p-3 h-100">
                <div class="text-muted small text-uppercase mb-1 fw-bold">Total</div>
                <div class="h3 mb-0 fw-bold"><?= $stats['total'] ?></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="admin-card text-center p-3 h-100">
                <div class="text-muted small text-uppercase mb-1 fw-bold">Brouillons</div>
                <div class="h3 mb-0 fw-bold text-secondary"><?= $stats['draft'] ?></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="admin-card text-center p-3 h-100">
                <div class="text-muted small text-uppercase mb-1 fw-bold">Publiées</div>
                <div class="h3 mb-0 fw-bold text-success"><?= $stats['published'] ?></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="admin-card text-center p-3 h-100">
                <div class="text-muted small text-uppercase mb-1 fw-bold">Clôturées</div>
                <div class="h3 mb-0 fw-bold text-muted"><?= $stats['closed'] ?></div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($isForm): ?>
    <div class="admin-card admin-section-card">
        <h5 class="card-title mb-4"><i class="bi bi-briefcase"></i> <?= $id ? 'Modifier l\'offre' : 'Nouvelle offre' ?></h5>
        <form method="POST" action="<?= $id ? 'offers.php?action=edit&id=' . $id : 'offers.php?action=add' ?>" enctype="multipart/form-data">
            <input type="hidden" name="save_offer" value="1">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label fw-bold">Titre *</label>
                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($detail->title ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Référence</label>
                    <input type="text" name="reference" class="form-control" value="<?= htmlspecialchars($detail->reference ?? '') ?>" placeholder="ex: OFF-2024-001">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Photo de couverture</label>
                    <?php if (!empty($detail->cover_image)): ?>
                        <div class="mb-2">
                            <img src="../<?= htmlspecialchars($detail->cover_image) ?>" alt="Couverture" class="rounded border" style="height: 100px; object-fit: cover;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="cover_image" class="form-control" accept="image/*">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Liée à une mission</label>
                    <select name="mission_id" class="form-select">
                        <option value="">— Aucune —</option>
                        <?php foreach ($missions as $m): ?>
                            <option value="<?= (int) $m->id ?>" <?= (($detail->mission_id ?? '') == $m->id) ? 'selected' : '' ?>><?= htmlspecialchars($m->title) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Liée à un projet</label>
                    <select name="project_id" class="form-select">
                        <option value="">— Aucun —</option>
                        <?php foreach ($projects as $pr): ?>
                            <option value="<?= (int) $pr->id ?>" <?= (($detail->project_id ?? '') == $pr->id) ? 'selected' : '' ?>><?= htmlspecialchars($pr->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Statut</label>
                    <select name="status" class="form-select">
                        <?php foreach ($statusLabels as $k => $v): ?>
                            <option value="<?= $k ?>" <?= (($detail->status ?? 'draft') === $k) ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Date de publication</label>
                    <input type="date" name="published_at" class="form-control" value="<?= htmlspecialchars($detail->published_at ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Date limite</label>
                    <input type="date" name="deadline_at" class="form-control" value="<?= htmlspecialchars($detail->deadline_at ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Description</label>
                    <textarea name="description" id="offer_description" class="form-control" rows="6" placeholder="Description de l'offre (texte enrichi)"></textarea>
                    <script type="application/json" id="offer_description_data"><?= json_encode(isset($detail->description) ? $detail->description : '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
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
        $('#offer_description').summernote({
            placeholder: 'Description de l\'offre (texte enrichi)...',
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
        var dataEl = document.getElementById('offer_description_data');
        if (dataEl) {
            try {
                var content = JSON.parse(dataEl.textContent);
                if (content) $('#offer_description').summernote('code', content);
            } catch (e) {}
        }
    }
});
</script>
<?php elseif ($detail): ?>
    <style>.offer-description-html img { max-width: 100%; height: auto; } .offer-description-html table { border-collapse: collapse; } .offer-description-html td, .offer-description-html th { border: 1px solid #dee2e6; padding: .25rem .5rem; }</style>
    <?php if (!empty($detail->cover_image)): ?>
        <div class="mb-4 rounded overflow-hidden border shadow-sm">
            <img src="../<?= htmlspecialchars($detail->cover_image) ?>" alt="" class="img-fluid" style="max-height: 200px; width: 100%; object-fit: cover;">
        </div>
    <?php endif; ?>
    <div class="admin-card admin-section-card mb-4">
        <h5 class="card-title"><i class="bi bi-briefcase"></i> Informations</h5>
        <table class="admin-table mb-0">
            <tr><th style="width:180px;">Titre</th><td><?= htmlspecialchars($detail->title) ?></td></tr>
            <tr><th>Référence</th><td><?= htmlspecialchars($detail->reference ?? '—') ?></td></tr>
            <tr><th>Mission</th><td><?= $detail->mission_id ? htmlspecialchars($detail->mission_title ?? '') : '—' ?></td></tr>
            <tr><th>Projet</th><td><?= $detail->project_id ? htmlspecialchars($detail->project_name ?? '') : '—' ?></td></tr>
            <tr><th>Statut</th><td><span class="badge bg-secondary"><?= $statusLabels[$detail->status] ?? $detail->status ?></span></td></tr>
            <tr><th>Publication</th><td><?= $detail->published_at ? date('d/m/Y', strtotime($detail->published_at)) : '—' ?></td></tr>
            <tr><th>Date limite</th><td><?= $detail->deadline_at ? date('d/m/Y', strtotime($detail->deadline_at)) : '—' ?></td></tr>
            <tr><th>Description</th><td><div class="offer-description-html"><?= isset($detail->description) && $detail->description !== '' ? $detail->description : '—' ?></div></td></tr>
        </table>
        <div class="mt-4 d-flex gap-2">
            <?php if (has_permission('admin.offers.modify')): ?>
                <a href="offers.php?action=edit&id=<?= (int) $detail->id ?>" class="btn btn-admin-primary"><i class="bi bi-pencil me-1"></i> Modifier</a>
            <?php endif; ?>
            <a href="offers.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Liste</a>
            <?php if (has_permission('admin.offers.delete')): ?>
                <button type="button" class="btn btn-outline-danger ms-auto" data-bs-toggle="modal" data-bs-target="#deleteOfferModal"><i class="bi bi-trash me-1"></i> Supprimer</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="admin-card admin-section-card mb-4">
        <h5 class="card-title"><i class="bi bi-people"></i> Candidatures (<?= count($applications) ?>)</h5>
        <?php if (count($applications) > 0): ?>
            <div class="table-responsive">
                <table class="admin-table table table-hover">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>CV</th>
                            <th>Profil</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars(trim(($app->first_name ?? '') . ' ' . ($app->last_name ?? ''))) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($app->email ?? '') ?></small>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($app->created_at)) ?></td>
                                <td><span class="badge bg-secondary"><?= $applicationStatusLabels[$app->status] ?? $app->status ?></span></td>
                                <td><?= !empty($app->cv_path) ? '<a href="../' . htmlspecialchars($app->cv_path) . '" target="_blank"><i class="bi bi-file-earmark-pdf"></i> CV</a>' : '—' ?></td>
                                <td><a href="candidate_profile.php?user_id=<?= (int) $app->user_id ?>" target="_blank" class="btn btn-sm btn-admin-outline"><i class="bi bi-person-badge me-1"></i>Voir le profil</a></td>
                                <td class="text-end">
                                    <?php if (has_permission('admin.offers.modify')): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="update_application_status" value="1">
                                            <input type="hidden" name="application_id" value="<?= (int) $app->id ?>">
                                            <select name="application_status" class="form-select form-select-sm d-inline-block" style="width:auto;" onchange="this.form.submit()">
                                                <?php foreach ($applicationStatusLabels as $k => $v): ?>
                                                    <option value="<?= $k ?>" <?= $app->status === $k ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if (!empty($app->message)): ?>
                                <tr><td colspan="6" class="small text-muted bg-light"><?= nl2br(htmlspecialchars($app->message)) ?></td></tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted mb-0">Aucune candidature pour le moment.</p>
        <?php endif; ?>
    </div>

    <?php if (has_permission('admin.offers.delete')): ?>
    <div class="modal fade" id="deleteOfferModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Supprimer cette offre ?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    L'offre <strong><?= htmlspecialchars($detail->title) ?></strong> et toutes les candidatures associées seront supprimées. Cette action est irréversible.
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
<?php elseif (count($list) > 0): ?>
    <div class="admin-card admin-section-card">
        <div class="table-responsive">
            <table class="admin-table table table-hover">
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Référence</th>
                        <th>Lien</th>
                        <th>Statut</th>
                        <th>Date limite</th>
                        <th>Candidatures</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($list as $o): ?>
                        <tr>
                            <td><a href="offers.php?id=<?= (int) $o->id ?>"><?= htmlspecialchars($o->title) ?></a></td>
                            <td><?= htmlspecialchars($o->reference ?? '—') ?></td>
                            <td class="small">
                                <?php if (!empty($o->mission_id)): ?>Mission<?php elseif (!empty($o->project_id)): ?>Projet<?php else: ?>—<?php endif; ?>
                            </td>
                            <td><span class="badge bg-secondary"><?= $statusLabels[$o->status] ?? $o->status ?></span></td>
                            <td><?= $o->deadline_at ? date('d/m/Y', strtotime($o->deadline_at)) : '—' ?></td>
                            <td><span class="badge bg-primary"><?= (int) ($o->applications_count ?? 0) ?></span></td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="offers.php?id=<?= (int) $o->id ?>"><i class="bi bi-eye me-2"></i> Voir</a></li>
                                        <?php if (has_permission('admin.offers.modify')): ?>
                                            <li><a class="dropdown-item" href="offers.php?action=edit&id=<?= (int) $o->id ?>"><i class="bi bi-pencil me-2"></i> Modifier</a></li>
                                        <?php endif; ?>
                                        <?php if (has_permission('admin.offers.delete')): ?>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form method="POST" onsubmit="return confirm('Supprimer cette offre ?');">
                                                    <input type="hidden" name="delete_id" value="<?= (int) $o->id ?>">
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
        <p class="admin-empty py-4 mb-0"><i class="bi bi-inbox"></i> Aucune offre. <?php if (has_permission('admin.offers.add')): ?><a href="offers.php?action=add">Créer une offre</a><?php endif; ?></p>
    </div>
<?php endif; ?>

<footer class="admin-main-footer mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <a href="missions.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Missions</a>
        <span class="small text-muted">&copy; <?= date('Y') ?> Expertise</span>
    </div>
</footer>
<?php require __DIR__ . '/inc/footer.php'; ?>
