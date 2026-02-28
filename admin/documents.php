<?php
/**
 * Gestion documentaire – Catégories, Documents, Versions, Archivage
 */
$pageTitle = 'Documents – Gestion documentaire';
$currentNav = 'documents';
require_once __DIR__ . '/inc/auth.php';
require_permission('admin.documents.view');
require __DIR__ . '/inc/db.php';

$error = '';
$success = '';
$list = [];
$detail = null;
$organisations = [];
$categories = [];
$tab = $_GET['tab'] ?? 'documents'; // documents | categories
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$organisation_id = isset($_GET['organisation_id']) ? (int) $_GET['organisation_id'] : null;
$stats = ['documents' => 0, 'categories' => 0, 'versions' => 0, 'archived' => 0];

if ($pdo) {
    $organisations = $pdo->query("SELECT id, name FROM organisation WHERE is_active = 1 ORDER BY name")->fetchAll();
    $default_org_id = $organisations[0]->id ?? null;

    // ---------- POST: Catégories ----------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '') ?: null;
        $description = trim($_POST['description'] ?? '') ?: null;
        $parent_id = !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;
        $cat_id = isset($_POST['category_id']) ? (int) $_POST['category_id'] : 0;
        $org_id = !empty($_POST['organisation_id']) ? (int) $_POST['organisation_id'] : $default_org_id;

        if ($name === '') {
            $error = 'Le nom de la catégorie est obligatoire.';
        } else {
            try {
                if ($cat_id > 0) {
                    $pdo->prepare("UPDATE document_category SET organisation_id = ?, parent_id = ?, name = ?, code = ?, description = ? WHERE id = ?")
                        ->execute([$org_id, $parent_id, $name, $code, $description, $cat_id]);
                    $success = 'Catégorie mise à jour.';
                } else {
                    $pdo->prepare("INSERT INTO document_category (organisation_id, parent_id, name, code, description) VALUES (?, ?, ?, ?, ?)")
                        ->execute([$org_id, $parent_id, $name, $code, $description]);
                    $success = 'Catégorie créée.';
                }
            } catch (PDOException $e) {
                $error = 'Erreur enregistrement : ' . $e->getMessage();
            }
        }
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
        $delId = (int) $_POST['delete_category'];
        try {
            $pdo->prepare("DELETE FROM document_category WHERE id = ?")->execute([$delId]);
            $success = 'Catégorie supprimée.';
        } catch (PDOException $e) {
            $error = 'Impossible de supprimer (sous-catégories ou documents liés).';
        }
    }

    // ---------- POST: Documents ----------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_document'])) {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '') ?: null;
        $document_type = trim($_POST['document_type'] ?? '') ?: null;
        $doc_org_id = !empty($_POST['organisation_id']) ? (int) $_POST['organisation_id'] : $default_org_id;
        $category_id = !empty($_POST['document_category_id']) ? (int) $_POST['document_category_id'] : null;
        $doc_id = isset($_POST['document_id']) ? (int) $_POST['document_id'] : 0;
        $created_by = $_SESSION['admin_id'] ?? null;

        if ($title === '') {
            $error = 'Le titre du document est obligatoire.';
        } else {
            try {
                if ($doc_id > 0) {
                    $cover_path = null;
                    $cover_file = $_FILES['cover_image'] ?? null;
                    if ($cover_file && !empty($cover_file['tmp_name']) && $cover_file['error'] === UPLOAD_ERR_OK) {
                        $img_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        $ext = strtolower(pathinfo($cover_file['name'], PATHINFO_EXTENSION));
                        if (in_array($ext, $img_ext, true)) {
                            $cover_dir = dirname(__DIR__) . '/uploads/documents/covers/';
                            if (!is_dir($cover_dir)) mkdir($cover_dir, 0755, true);
                            $cover_name = 'doc_cover_' . $doc_id . '_' . date('Ymd_His') . '_' . substr(uniqid(), -6) . '.' . $ext;
                            if (move_uploaded_file($cover_file['tmp_name'], $cover_dir . $cover_name)) {
                                $cover_path = 'uploads/documents/covers/' . $cover_name;
                            }
                        }
                    }
                    $remove_cover = !empty($_POST['remove_cover_image']);
                    if ($remove_cover) {
                        $cover_path = null;
                    }
                    if ($cover_path !== null || $remove_cover) {
                        $pdo->prepare("UPDATE document SET organisation_id = ?, document_category_id = ?, title = ?, description = ?, document_type = ?, cover_image = ?, created_by_user_id = ? WHERE id = ?")
                            ->execute([$doc_org_id, $category_id, $title, $description, $document_type, $cover_path, $created_by, $doc_id]);
                    } else {
                        $pdo->prepare("UPDATE document SET organisation_id = ?, document_category_id = ?, title = ?, description = ?, document_type = ?, created_by_user_id = ? WHERE id = ?")
                            ->execute([$doc_org_id, $category_id, $title, $description, $document_type, $created_by, $doc_id]);
                    }

                    $version_added = false;
                    $file = $_FILES['document_file'] ?? null;
                    if ($file && !empty($file['tmp_name']) && $file['error'] === UPLOAD_ERR_OK) {
                        $allowed_ext = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'odt', 'ods', 'odp', 'csv', 'zip', 'png', 'jpg', 'jpeg', 'gif', 'webp'];
                        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        if (!in_array($ext, $allowed_ext, true)) {
                            $error = 'Type de fichier non autorisé. Autorisés : ' . implode(', ', $allowed_ext);
                        } else {
                            $target_dir = dirname(__DIR__) . '/uploads/documents/';
                            if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
                            $safe_name = 'doc_' . date('Ymd_His') . '_' . substr(uniqid(), -6) . '.' . $ext;
                            $target_path = $target_dir . $safe_name;
                            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                                $uploaded_path = 'uploads/documents/' . $safe_name;
                                $uploaded_file_name = $file['name'];
                                $uploaded_file_size = (int) $file['size'];
                                $uploaded_mime_type = $file['type'];
                                if (function_exists('finfo_open')) {
                                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                                    $uploaded_mime_type = finfo_file($finfo, $target_path);
                                    finfo_close($finfo);
                                }
                                $stmt = $pdo->prepare("SELECT COALESCE(MAX(version_number), 0) + 1 FROM document_version WHERE document_id = ?");
                                $stmt->execute([$doc_id]);
                                $next_ver = (int) $stmt->fetchColumn();
                                $pdo->prepare("INSERT INTO document_version (document_id, version_number, file_path, file_name, file_size, mime_type, created_by_user_id, change_notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                                    ->execute([$doc_id, $next_ver, $uploaded_path, $uploaded_file_name, $uploaded_file_size, $uploaded_mime_type, $created_by, 'Version ajoutée depuis l\'édition']);
                                $ver_id = $pdo->lastInsertId();
                                $pdo->prepare("UPDATE document SET current_version_id = ? WHERE id = ?")->execute([$ver_id, $doc_id]);
                                $version_added = true;
                            } else {
                                $error = 'Impossible d\'enregistrer le fichier sur le serveur.';
                            }
                        }
                    } elseif ($file && $file['error'] !== UPLOAD_ERR_NO_FILE) {
                        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
                            $error = 'Le fichier est trop volumineux.';
                        } else {
                            $error = 'Erreur lors de l\'upload (code ' . $file['error'] . ').';
                        }
                    }

                    if ($error === '') {
                        $success = 'Document mis à jour.' . ($version_added ? ' Nouvelle version enregistrée.' : '');
                        $id = $doc_id;
                        $action = 'view';
                    }
                } else {
                    // Valider l'upload avant d'insérer le document
                    $uploaded_path = null;
                    $uploaded_file_name = null;
                    $uploaded_file_size = null;
                    $uploaded_mime_type = null;
                    $cover_image_for_insert = null;
                    $file = $_FILES['document_file'] ?? null;
                    if ($file && !empty($file['tmp_name']) && $file['error'] === UPLOAD_ERR_OK) {
                        $allowed_ext = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'odt', 'ods', 'odp', 'csv', 'zip', 'png', 'jpg', 'jpeg', 'gif', 'webp'];
                        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        if (!in_array($ext, $allowed_ext, true)) {
                            $error = 'Type de fichier non autorisé. Autorisés : ' . implode(', ', $allowed_ext);
                        } else {
                            $target_dir = dirname(__DIR__) . '/uploads/documents/';
                            if (!is_dir($target_dir)) {
                                mkdir($target_dir, 0755, true);
                            }
                            $safe_name = 'doc_' . date('Ymd_His') . '_' . substr(uniqid(), -6) . '.' . $ext;
                            $target_path = $target_dir . $safe_name;
                            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                                $uploaded_path = 'uploads/documents/' . $safe_name;
                                $uploaded_file_name = $file['name'];
                                $uploaded_file_size = (int) $file['size'];
                                $uploaded_mime_type = $file['type'];
                                if (function_exists('finfo_open')) {
                                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                                    $uploaded_mime_type = finfo_file($finfo, $target_path);
                                    finfo_close($finfo);
                                }
                            } else {
                                $error = 'Impossible d\'enregistrer le fichier sur le serveur.';
                            }
                        }
                    } elseif ($file && $file['error'] !== UPLOAD_ERR_NO_FILE) {
                        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
                            $error = 'Le fichier est trop volumineux.';
                        } else {
                            $error = 'Erreur lors de l\'upload (code ' . $file['error'] . ').';
                        }
                    }
                    $cover_file_new = $_FILES['cover_image'] ?? null;
                    if ($cover_file_new && !empty($cover_file_new['tmp_name']) && $cover_file_new['error'] === UPLOAD_ERR_OK) {
                        $img_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        $ext = strtolower(pathinfo($cover_file_new['name'], PATHINFO_EXTENSION));
                        if (in_array($ext, $img_ext, true)) {
                            $cover_dir = dirname(__DIR__) . '/uploads/documents/covers/';
                            if (!is_dir($cover_dir)) mkdir($cover_dir, 0755, true);
                            $cover_name = 'doc_cover_' . date('Ymd_His') . '_' . substr(uniqid(), -6) . '.' . $ext;
                            if (move_uploaded_file($cover_file_new['tmp_name'], $cover_dir . $cover_name)) {
                                $cover_image_for_insert = 'uploads/documents/covers/' . $cover_name;
                            }
                        }
                    }

                    if ($error === '') {
                        $pdo->prepare("INSERT INTO document (organisation_id, document_category_id, title, description, document_type, cover_image, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?)")
                            ->execute([$doc_org_id, $category_id, $title, $description, $document_type, $cover_image_for_insert, $created_by]);
                        $new_doc_id = (int) $pdo->lastInsertId();

                        if ($uploaded_path) {
                            $pdo->prepare("INSERT INTO document_version (document_id, version_number, file_path, file_name, file_size, mime_type, created_by_user_id, change_notes) VALUES (?, 1, ?, ?, ?, ?, ?, ?)")
                                ->execute([$new_doc_id, $uploaded_path, $uploaded_file_name, $uploaded_file_size, $uploaded_mime_type, $created_by, 'Version initiale']);
                            $ver_id = $pdo->lastInsertId();
                            $pdo->prepare("UPDATE document SET current_version_id = ? WHERE id = ?")->execute([$ver_id, $new_doc_id]);
                        }

                        header('Location: documents.php?tab=documents&id=' . $new_doc_id . '&msg=created');
                        exit;
                    }
                }
            } catch (PDOException $e) {
                $error = 'Erreur enregistrement : ' . $e->getMessage();
            }
        }
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_document'])) {
        $delId = (int) $_POST['delete_document'];
        try {
            $pdo->prepare("DELETE FROM document WHERE id = ?")->execute([$delId]);
            header('Location: documents.php?tab=documents&msg=deleted');
            exit;
        } catch (PDOException $e) {
            $error = 'Impossible de supprimer le document.';
        }
    }

    // ---------- POST: Nouvelle version ----------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_version'])) {
        $doc_id_v = (int) ($_POST['document_id'] ?? 0);
        $change_notes = trim($_POST['change_notes'] ?? '') ?: null;
        $file_path = trim($_POST['file_path'] ?? '');
        $file_name = trim($_POST['file_name'] ?? '') ?: null;
        $file_size = null;
        $mime_type = null;

        $file = $_FILES['version_file'] ?? null;
        if ($file && !empty($file['tmp_name']) && $file['error'] === UPLOAD_ERR_OK) {
            $allowed_ext = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'odt', 'ods', 'odp', 'csv', 'zip', 'png', 'jpg', 'jpeg', 'gif', 'webp'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed_ext, true)) {
                $error = 'Type de fichier non autorisé. Autorisés : ' . implode(', ', $allowed_ext);
            } else {
                $target_dir = dirname(__DIR__) . '/uploads/documents/';
                if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
                $safe_name = 'doc_' . date('Ymd_His') . '_' . substr(uniqid(), -6) . '.' . $ext;
                $target_path = $target_dir . $safe_name;
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    $file_path = 'uploads/documents/' . $safe_name;
                    $file_name = $file['name'];
                    $file_size = (int) $file['size'];
                    $mime_type = $file['type'];
                    if (function_exists('finfo_open')) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime_type = finfo_file($finfo, $target_path);
                        finfo_close($finfo);
                    }
                } else {
                    $error = 'Impossible d\'enregistrer le fichier sur le serveur.';
                }
            }
        }

        if ($error === '' && $doc_id_v >= 1 && $file_path !== '') {
            $stmt = $pdo->prepare("SELECT COALESCE(MAX(version_number), 0) + 1 FROM document_version WHERE document_id = ?");
            $stmt->execute([$doc_id_v]);
            $next = (int) $stmt->fetchColumn();
            $created_by = $_SESSION['admin_id'] ?? null;
            $pdo->prepare("INSERT INTO document_version (document_id, version_number, file_path, file_name, file_size, mime_type, created_by_user_id, change_notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$doc_id_v, $next, $file_path, $file_name, $file_size, $mime_type, $created_by, $change_notes]);
            $new_ver_id = $pdo->lastInsertId();
            $pdo->prepare("UPDATE document SET current_version_id = ? WHERE id = ?")->execute([$new_ver_id, $doc_id_v]);
            $success = 'Version ' . $next . ' ajoutée.';
            $id = $doc_id_v;
            $action = 'view';
        } elseif ($error === '' && ($doc_id_v < 1 || $file_path === '')) {
            $error = 'Document et fichier (ou chemin) requis.';
        }
    }

    // ---------- POST: Archivage ----------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_document'])) {
        $doc_id_a = (int) ($_POST['document_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '') ?: null;
        $archive_location = trim($_POST['archive_location'] ?? '') ?: null;
        $retention_until = !empty($_POST['retention_until']) ? $_POST['retention_until'] : null;
        $archived_by = $_SESSION['admin_id'] ?? null;

        if ($doc_id_a < 1) {
            $error = 'Document requis.';
        } else {
            $pdo->prepare("INSERT INTO archiving (document_id, archived_by_user_id, archive_location, retention_until, reason) VALUES (?, ?, ?, ?, ?)")
                ->execute([$doc_id_a, $archived_by, $archive_location, $retention_until ?: null, $reason]);
            $success = 'Document archivé.';
            $id = $doc_id_a;
            $action = 'view';
        }
    }

    // ---------- Chargement catégories (pour liste et selects) ----------
    $categories = $pdo->query("SELECT c.id, c.organisation_id, c.name, c.code, c.description, c.parent_id, p.name AS parent_name FROM document_category c LEFT JOIN document_category p ON p.id = c.parent_id ORDER BY p.name, c.name")->fetchAll();

    // ---------- Statistiques (onglet Documents) ----------
    if ($tab === 'documents') {
        $stats['documents'] = (int) $pdo->query("SELECT COUNT(*) FROM document")->fetchColumn();
        $stats['categories'] = (int) $pdo->query("SELECT COUNT(*) FROM document_category")->fetchColumn();
        $stats['versions'] = (int) $pdo->query("SELECT COUNT(*) FROM document_version")->fetchColumn();
        $stats['archived'] = (int) $pdo->query("SELECT COUNT(*) FROM archiving")->fetchColumn();
    }

    // ---------- Liste documents ----------
    if ($tab === 'documents' && $action === 'list') {
        $sql = "SELECT d.id, d.title, d.document_type, d.created_at, c.name AS category_name, o.name AS organisation_name
                FROM document d
                LEFT JOIN document_category c ON c.id = d.document_category_id
                LEFT JOIN organisation o ON o.id = d.organisation_id
                WHERE 1=1";
        $params = [];
        if ($organisation_id) {
            $sql .= " AND d.organisation_id = ?";
            $params[] = $organisation_id;
        }
        if (!empty($_GET['category_id'])) {
            $sql .= " AND d.document_category_id = ?";
            $params[] = (int) $_GET['category_id'];
        }
        $sql .= " ORDER BY d.updated_at DESC";
        $stmt = $params ? $pdo->prepare($sql) : $pdo->query($sql);
        if ($params) $stmt->execute($params);
        $list = $stmt->fetchAll();
    }

    // ---------- Détail document ----------
    if ($tab === 'documents' && $id > 0) {
        $stmt = $pdo->prepare("SELECT d.*, c.name AS category_name, o.name AS organisation_name FROM document d LEFT JOIN document_category c ON c.id = d.document_category_id LEFT JOIN organisation o ON o.id = d.organisation_id WHERE d.id = ?");
        $stmt->execute([$id]);
        $detail = $stmt->fetch();
        if ($detail) {
            $detail->versions = $pdo->prepare("SELECT * FROM document_version WHERE document_id = ? ORDER BY version_number DESC")->execute([$id]) ? [] : $pdo->query("SELECT * FROM document_version WHERE document_id = $id ORDER BY version_number DESC")->fetchAll();
            $stmtV = $pdo->prepare("SELECT * FROM document_version WHERE document_id = ? ORDER BY version_number DESC");
            $stmtV->execute([$id]);
            $detail->versions = $stmtV->fetchAll();
            $stmtA = $pdo->prepare("SELECT a.*, u.email FROM archiving a LEFT JOIN user u ON u.id = a.archived_by_user_id WHERE a.document_id = ? ORDER BY a.archived_at DESC");
            $stmtA->execute([$id]);
            $detail->archivings = $stmtA->fetchAll();
        }
    }
}

require __DIR__ . '/inc/header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Tableau de bord</a></li>
        <li class="breadcrumb-item"><a href="documents.php" class="text-decoration-none">Gestion</a></li>
        <li class="breadcrumb-item"><a href="documents.php" class="text-decoration-none">Documents</a></li>
        <?php if ($tab === 'categories'): ?>
            <li class="breadcrumb-item active">Catégories</li>
        <?php elseif ($detail): ?>
            <li class="breadcrumb-item active"><?= htmlspecialchars($detail->title ?? 'Détail') ?></li>
        <?php endif; ?>
    </ol>
</nav>

<header class="admin-header">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h1><?= $tab === 'categories' ? 'Catégories de documents' : ($detail ? 'Document' : 'Gestion documentaire'); ?></h1>
            <p class="text-muted mb-0"><?= $tab === 'categories' ? 'Organiser les documents par catégories.' : 'Documents, versions et archivage.'; ?></p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($detail && $tab === 'documents'): ?>
                <a href="documents.php?tab=documents" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Liste</a>
                <a href="documents.php?tab=documents&action=edit&id=<?= (int) $detail->id ?>" class="btn btn-admin-primary"><i class="bi bi-pencil me-1"></i> Modifier</a>
            <?php elseif ($tab === 'documents' && !$detail): ?>
                <a href="documents.php?tab=documents&action=add" class="btn btn-admin-primary"><i class="bi bi-file-earmark-plus me-1"></i> Nouveau document</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success">Document supprimé.</div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
    <div class="alert alert-success">Document créé.</div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($tab === 'documents' && !$detail && $action !== 'add'): ?>
    <!-- Raccourci configuration (comme page Missions) -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="admin-card p-3 d-flex flex-wrap gap-2 align-items-center bg-light border shadow-sm">
                <span class="text-muted small fw-bold text-uppercase me-2"><i class="bi bi-sliders me-1"></i> Configuration :</span>
                <a href="documents.php?tab=categories" class="btn btn-sm btn-admin-outline"><i class="bi bi-folder me-1"></i> Gérer les Catégories</a>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($tab === 'categories'): ?>
    <div class="mb-3">
        <a href="documents.php?tab=documents" class="btn btn-admin-outline btn-sm"><i class="bi bi-arrow-left me-1"></i> Retour aux documents</a>
    </div>
    <div class="admin-card admin-section-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted"><?= count($categories) ?> catégorie(s)</span>
            <button type="button" class="btn btn-admin-primary btn-sm" data-bs-toggle="modal" data-bs-target="#categoryModal" onclick="resetCategoryForm()"><i class="bi bi-plus me-1"></i> Nouvelle catégorie</button>
        </div>
        <div class="table-responsive">
            <table class="admin-table table table-hover">
                <thead><tr><th>Nom</th><th>Code</th><th>Parent</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($categories as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c->name) ?></td>
                            <td><?= htmlspecialchars($c->code ?? '—') ?></td>
                            <td><?= htmlspecialchars($c->parent_name ?? '—') ?></td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick='editCategory(<?= json_encode($c) ?>)'><i class="bi bi-pencil"></i></button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette catégorie ?');">
                                    <input type="hidden" name="delete_category" value="<?= (int) $c->id ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($categories)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">Aucune catégorie. Créez-en une.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="categoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <form method="POST">
                    <input type="hidden" name="save_category" value="1">
                    <input type="hidden" name="category_id" id="category_id" value="0">
                    <div class="modal-header"><h5 class="modal-title" id="categoryModalTitle">Nouvelle catégorie</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Organisation</label>
                            <select name="organisation_id" class="form-select" id="cat_organisation_id">
                                <?php foreach ($organisations as $o): ?>
                                    <option value="<?= (int) $o->id ?>"><?= htmlspecialchars($o->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Catégorie parente</label>
                            <select name="parent_id" class="form-select">
                                <option value="">— Aucune —</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= (int) $c->id ?>"><?= htmlspecialchars($c->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nom *</label>
                            <input type="text" name="name" id="cat_name" class="form-control" required placeholder="Ex: Contrats">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Code</label>
                            <input type="text" name="code" id="cat_code" class="form-control" placeholder="Ex: CONTRATS">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Description</label>
                            <textarea name="description" id="cat_description" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-admin-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        function resetCategoryForm() {
            document.getElementById('category_id').value = 0;
            document.getElementById('cat_name').value = '';
            document.getElementById('cat_code').value = '';
            document.getElementById('cat_description').value = '';
            document.getElementById('categoryModalTitle').textContent = 'Nouvelle catégorie';
        }
        function editCategory(c) {
            document.getElementById('category_id').value = c.id;
            document.getElementById('cat_name').value = c.name || '';
            document.getElementById('cat_code').value = c.code || '';
            document.getElementById('cat_description').value = c.description || '';
            var orgSel = document.getElementById('cat_organisation_id');
            if (orgSel && c.organisation_id) { orgSel.value = c.organisation_id; }
            document.getElementById('categoryModalTitle').textContent = 'Modifier la catégorie';
            var sel = document.querySelector('#categoryModal select[name="parent_id"]');
            if (sel) { for (var i = 0; i < sel.options.length; i++) { if (sel.options[i].value == c.parent_id) { sel.selectedIndex = i; break; } } }
            new bootstrap.Modal(document.getElementById('categoryModal')).show();
        }
    </script>
<?php endif; ?>

<?php if ($tab === 'documents' && ($action === 'add' || ($action === 'edit' && $detail))): ?>
    <div class="admin-card admin-section-card">
        <form method="POST" action="documents.php?tab=documents&action=<?= $action ?><?= $id ? '&id=' . $id : '' ?>" enctype="multipart/form-data">
            <input type="hidden" name="save_document" value="1">
            <input type="hidden" name="document_id" value="<?= (int) ($detail->id ?? 0) ?>">
            <?php if (ini_get('file_uploads') !== '1'): ?>
                <div class="alert alert-warning small">L'upload de fichiers est désactivé sur le serveur (file_uploads=Off).</div>
            <?php endif; ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Organisation</label>
                    <select name="organisation_id" class="form-select">
                        <?php foreach ($organisations as $o): ?>
                            <option value="<?= (int) $o->id ?>" <?= ($detail && (int)$detail->organisation_id === (int)$o->id) ? 'selected' : '' ?>><?= htmlspecialchars($o->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Catégorie</label>
                    <select name="document_category_id" class="form-select">
                        <option value="">— Aucune —</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int) $c->id ?>" <?= ($detail && (int)$detail->document_category_id === (int)$c->id) ? 'selected' : '' ?>><?= htmlspecialchars($c->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Titre *</label>
                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($detail->title ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Type de document</label>
                    <input type="text" name="document_type" class="form-control" value="<?= htmlspecialchars($detail->document_type ?? '') ?>" placeholder="Ex: PDF, Procédure">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Photo de couverture (miniature)</label>
                    <?php
                    $cover_image = $detail->cover_image ?? null;
                    $cover_url = $cover_image ? (rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/../' . $cover_image) : '';
                    ?>
                    <?php if ($cover_url && $detail): ?>
                        <div class="mb-2">
                            <img src="<?= htmlspecialchars($cover_url) ?>" alt="Couverture actuelle" class="img-thumbnail" style="max-height: 120px; max-width: 160px; object-fit: cover;">
                        </div>
                        <div class="form-check mb-2">
                            <input type="checkbox" name="remove_cover_image" value="1" id="remove_cover_image" class="form-check-input">
                            <label class="form-check-label" for="remove_cover_image">Supprimer la photo de couverture</label>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="cover_image" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp">
                    <div class="form-text">Image utilisée comme miniature (liste, page Responsabilité). JPG, PNG, GIF, WebP. Optionnel.</div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold"><?= $detail ? 'Nouvelle version (optionnel)' : 'Fichier (optionnel)' ?></label>
                    <input type="file" name="document_file" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.odt,.ods,.odp,.csv,.zip,.png,.jpg,.jpeg,.gif,.webp">
                    <div class="form-text"><?= $detail ? 'Choisir un fichier pour ajouter une nouvelle version au document.' : '' ?> PDF, Word, Excel, PowerPoint, TXT, ODT, CSV, ZIP, images. Taille max : <?= ini_get('upload_max_filesize') ?: 'défaut serveur' ?>.</div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($detail->description ?? '') ?></textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-admin-primary">Enregistrer</button>
                    <a href="documents.php?tab=documents<?= $id ? '&id=' . $id : '' ?>" class="btn btn-secondary">Annuler</a>
                </div>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php if ($tab === 'documents' && $detail && $action !== 'edit'): ?>
    <div class="admin-card admin-section-card mb-4">
        <h5 class="card-title"><i class="bi bi-file-earmark-text"></i> Informations</h5>
        <table class="admin-table mb-0">
            <tr><th style="width:180px;">Titre</th><td><?= htmlspecialchars($detail->title) ?></td></tr>
            <tr><th>Organisation</th><td><?= htmlspecialchars($detail->organisation_name ?? '—') ?></td></tr>
            <tr><th>Catégorie</th><td><?= htmlspecialchars($detail->category_name ?? '—') ?></td></tr>
            <tr><th>Type</th><td><?= htmlspecialchars($detail->document_type ?? '—') ?></td></tr>
            <tr><th>Photo de couverture</th><td>
                <?php if (!empty($detail->cover_image)): ?>
                    <?php $cover_url = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/../' . $detail->cover_image; ?>
                    <img src="<?= htmlspecialchars($cover_url) ?>" alt="Couverture" class="img-thumbnail" style="max-height: 80px; max-width: 120px; object-fit: cover;">
                <?php else: ?>—<?php endif; ?>
            </td></tr>
            <tr><th>Description</th><td><?= nl2br(htmlspecialchars($detail->description ?? '—')) ?></td></tr>
        </table>
        <div class="mt-3 d-flex gap-2">
            <a href="documents.php?tab=documents&action=edit&id=<?= (int) $detail->id ?>" class="btn btn-admin-primary btn-sm"><i class="bi bi-pencil me-1"></i> Modifier</a>
            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#versionModal"><i class="bi bi-plus-circle me-1"></i> Nouvelle version</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#archiveModal"><i class="bi bi-archive me-1"></i> Archiver</button>
            <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce document et ses versions ?');">
                <input type="hidden" name="delete_document" value="<?= (int) $detail->id ?>">
                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i> Supprimer</button>
            </form>
        </div>
    </div>

    <div class="admin-card admin-section-card mb-4">
        <h5 class="card-title"><i class="bi bi-collection"></i> Versions</h5>
        <?php if (!empty($detail->versions)): ?>
            <div class="table-responsive">
                <table class="admin-table table table-hover">
                    <thead><tr><th>Version</th><th>Fichier</th><th>Date</th><th>Notes</th></tr></thead>
                    <tbody>
                        <?php foreach ($detail->versions as $v): ?>
                            <tr>
                                <td><span class="badge bg-primary">v<?= (int) $v->version_number ?></span></td>
                                <td><code><?= htmlspecialchars($v->file_path) ?></code></td>
                                <td><?= date('d/m/Y H:i', strtotime($v->created_at)) ?></td>
                                <td><?= htmlspecialchars($v->change_notes ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted mb-0">Aucune version. Ajoutez une version (fichier) ci-dessus.</p>
        <?php endif; ?>
    </div>

    <div class="admin-card admin-section-card mb-4">
        <h5 class="card-title"><i class="bi bi-archive"></i> Archivages</h5>
        <?php if (!empty($detail->archivings)): ?>
            <div class="table-responsive">
                <table class="admin-table table table-hover">
                    <thead><tr><th>Date</th><th>Par</th><th>Lieu / Raison</th><th>Conservation jusqu'au</th></tr></thead>
                    <tbody>
                        <?php foreach ($detail->archivings as $a): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($a->archived_at)) ?></td>
                                <td><?= htmlspecialchars($a->email ?? '—') ?></td>
                                <td><?= htmlspecialchars($a->archive_location ?? $a->reason ?? '—') ?></td>
                                <td><?= $a->retention_until ? date('d/m/Y', strtotime($a->retention_until)) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted mb-0">Aucun archivage enregistré.</p>
        <?php endif; ?>
    </div>

    <div class="modal fade" id="versionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="add_version" value="1">
                    <input type="hidden" name="document_id" value="<?= (int) $detail->id ?>">
                    <div class="modal-header"><h5 class="modal-title">Nouvelle version</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Fichier (recommandé)</label>
                            <input type="file" name="version_file" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.odt,.ods,.odp,.csv,.zip,.png,.jpg,.jpeg,.gif,.webp">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Ou chemin du fichier</label>
                            <input type="text" name="file_path" class="form-control" placeholder="Ex: uploads/documents/rapport_v2.pdf">
                            <div class="form-text">Renseignez le chemin si le fichier est déjà sur le serveur.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Notes de version</label>
                            <textarea name="change_notes" class="form-control" rows="2" placeholder="Modifications apportées"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-admin-primary">Ajouter la version</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="archiveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <form method="POST">
                    <input type="hidden" name="archive_document" value="1">
                    <input type="hidden" name="document_id" value="<?= (int) $detail->id ?>">
                    <div class="modal-header"><h5 class="modal-title">Archiver le document</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Lieu d'archivage</label>
                            <input type="text" name="archive_location" class="form-control" placeholder="Ex: Armoire A1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Conservation jusqu'au</label>
                            <input type="date" name="retention_until" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Raison / Commentaire</label>
                            <textarea name="reason" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-admin-primary">Enregistrer l'archivage</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($tab === 'documents' && !$detail && $action !== 'add'): ?>
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="admin-card text-center p-3 h-100">
                <div class="text-muted small text-uppercase mb-1 fw-bold">Documents</div>
                <div class="h3 mb-0 fw-bold"><?= $stats['documents'] ?></div>
                <div class="mt-2"><span class="badge bg-primary-subtle text-primary border">Total</span></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="admin-card text-center p-3 h-100">
                <div class="text-muted small text-uppercase mb-1 fw-bold">Catégories</div>
                <div class="h3 mb-0 fw-bold"><?= $stats['categories'] ?></div>
                <div class="mt-2"><span class="badge bg-secondary-subtle text-secondary border">Répertoires</span></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="admin-card text-center p-3 h-100">
                <div class="text-muted small text-uppercase mb-1 fw-bold">Versions</div>
                <div class="h3 mb-0 fw-bold text-info"><?= $stats['versions'] ?></div>
                <div class="mt-2"><span class="badge bg-info-subtle text-info border">Fichiers</span></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="admin-card text-center p-3 h-100">
                <div class="text-muted small text-uppercase mb-1 fw-bold">Archivés</div>
                <div class="h3 mb-0 fw-bold text-success"><?= $stats['archived'] ?></div>
                <div class="mt-2"><span class="badge bg-success-subtle text-success border">Archivage</span></div>
            </div>
        </div>
    </div>
    <div class="admin-card admin-section-card mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="tab" value="documents">
            <div class="col-auto">
                <label class="form-label small mb-0">Organisation</label>
                <select name="organisation_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Toutes</option>
                    <?php foreach ($organisations as $o): ?>
                        <option value="<?= (int) $o->id ?>" <?= $organisation_id === (int)$o->id ? 'selected' : '' ?>><?= htmlspecialchars($o->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">Catégorie</label>
                <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Toutes</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c->id ?>" <?= (!empty($_GET['category_id']) && (int)$_GET['category_id'] === (int)$c->id) ? 'selected' : '' ?>><?= htmlspecialchars($c->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
    <div class="admin-card admin-section-card">
        <div class="table-responsive">
            <table class="admin-table table table-hover" id="docTable">
                <thead><tr><th>Titre</th><th>Catégorie</th><th>Type</th><th>Date</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($list as $d): ?>
                        <tr>
                            <td><a href="documents.php?tab=documents&id=<?= (int) $d->id ?>"><?= htmlspecialchars($d->title) ?></a></td>
                            <td><?= htmlspecialchars($d->category_name ?? '—') ?></td>
                            <td><?= htmlspecialchars($d->document_type ?? '—') ?></td>
                            <td><?= date('d/m/Y', strtotime($d->created_at)) ?></td>
                            <td class="text-end">
                                <a href="documents.php?tab=documents&id=<?= (int) $d->id ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                <a href="documents.php?tab=documents&action=edit&id=<?= (int) $d->id ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($list)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Aucun document. <a href="documents.php?tab=documents&action=add">Créer un document</a></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (!empty($list)): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('docTable') && typeof $ !== 'undefined' && $.fn.DataTable) {
            $('#docTable').DataTable({ language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json' }, order: [[3, 'desc']], pageLength: 15 });
        }
    });
    </script>
    <?php endif; ?>
<?php endif; ?>

<footer class="admin-main-footer mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <a href="index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Tableau de bord</a>
        <span class="small text-muted">&copy; <?= date('Y') ?> Expertise</span>
    </div>
</footer>
<?php require __DIR__ . '/inc/footer.php'; ?>
