<?php
$pageTitle = 'Annonces – Administration';
$currentNav = 'announcements';
require_once __DIR__ . '/inc/auth.php';
require __DIR__ . '/inc/db.php';

$list = [];
$detail = null;
$stats = ['total' => 0, 'pinned' => 0];
$error = '';
$success = '';
$organisations = [];
$channels = [];
$users = [];
$activeOrganisationId = null;
$announcement_comments = [];
$announcement_attachments = [];
$announcement_notifications = [];
$announcement_history = [];
$announcement_conversations = [];
$conversation_has_announcement_link = false;

if ($pdo) {
    try {
        $organisations = $pdo->query("SELECT id, name FROM organisation ORDER BY name")->fetchAll();
        $channels = $pdo->query("SELECT id, name, channel_type FROM channel ORDER BY name")->fetchAll();
        $users = $pdo->query("SELECT id, first_name, last_name, email FROM user WHERE is_active = 1 ORDER BY last_name, first_name")->fetchAll();
        $row = $pdo->query("SELECT id FROM organisation WHERE is_active = 1 LIMIT 1")->fetch(PDO::FETCH_OBJ);
        $activeOrganisationId = $row ? (int) $row->id : null;
    } catch (PDOException $e) {
        $organisations = [];
        $channels = [];
        $users = [];
    }

    $action = $_GET['action'] ?? 'list';
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['delete_id'])) {
            $delId = (int) $_POST['delete_id'];
            try {
                $pdo->prepare("DELETE FROM announcement WHERE id = ?")->execute([$delId]);
                header('Location: announcements.php?msg=deleted');
                exit;
            } catch (PDOException $e) {
                $error = 'Impossible de supprimer l\'annonce.';
            }
        }
        $announcementId = ($id > 0) ? $id : null;
        if ($announcementId && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['add_comment'])) {
                $user_id = (int) ($_POST['comment_user_id'] ?? 0);
                $body = trim($_POST['comment_body'] ?? '');
                if ($user_id > 0 && $body !== '') {
                    try {
                        $pdo->prepare("INSERT INTO comment (user_id, commentable_type, commentable_id, body) VALUES (?, 'announcement', ?, ?)")->execute([$user_id, $announcementId, $body]);
                        $success = 'Commentaire ajouté.';
                        try { $pdo->prepare("INSERT INTO communication_history (user_id, entity_type, entity_id, action) VALUES (?, 'announcement', ?, 'comment_created')")->execute([$user_id, $announcementId]); } catch (PDOException $e) {}
                    } catch (PDOException $e) { $error = 'Erreur commentaire.'; }
                } else { $error = 'Auteur et contenu obligatoires.'; }
            }
            if (isset($_POST['delete_comment_id'])) {
                $cid = (int) $_POST['delete_comment_id'];
                try {
                    $pdo->prepare("DELETE FROM comment WHERE id = ? AND commentable_type = 'announcement' AND commentable_id = ?")->execute([$cid, $announcementId]);
                    $success = 'Commentaire supprimé.';
                } catch (PDOException $e) { $error = 'Impossible de supprimer.'; }
            }
            if (isset($_POST['add_attachment']) && !empty($_FILES['attachment_file']['name'])) {
                $upload_user_id = (int) ($_POST['attachment_user_id'] ?? 0);
                if ($upload_user_id > 0) {
                    $targetDir = __DIR__ . '/../uploads/announcements/attachments/';
                    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
                    $ext = strtolower(pathinfo($_FILES['attachment_file']['name'], PATHINFO_EXTENSION));
                    $fname = 'att_' . $announcementId . '_' . time() . '_' . uniqid() . '.' . $ext;
                    $relPath = 'uploads/announcements/attachments/' . $fname;
                    if (move_uploaded_file($_FILES['attachment_file']['tmp_name'], $targetDir . $fname)) {
                        $size = (int) $_FILES['attachment_file']['size'];
                        $mime = $_FILES['attachment_file']['type'] ?? null;
                        try {
                            $pdo->prepare("INSERT INTO attachment (attachable_type, attachable_id, uploaded_by_user_id, file_name, file_path, file_size, mime_type) VALUES ('announcement', ?, ?, ?, ?, ?, ?)")
                                ->execute([$announcementId, $upload_user_id, $_FILES['attachment_file']['name'], $relPath, $size, $mime]);
                            $success = 'Pièce jointe ajoutée.';
                            try { $pdo->prepare("INSERT INTO communication_history (user_id, entity_type, entity_id, action) VALUES (?, 'announcement', ?, 'attachment_created')")->execute([$upload_user_id, $announcementId]); } catch (PDOException $e) {}
                        } catch (PDOException $e) { $error = 'Erreur enregistrement.'; @unlink($targetDir . $fname); }
                    } else { $error = 'Erreur upload fichier.'; }
                } else { $error = 'Veuillez sélectionner un utilisateur.'; }
            }
            if (isset($_POST['delete_attachment_id'])) {
                $aid = (int) $_POST['delete_attachment_id'];
                try {
                    $row = $pdo->prepare("SELECT file_path FROM attachment WHERE id = ? AND attachable_type = 'announcement' AND attachable_id = ?");
                    $row->execute([$aid, $announcementId]);
                    $f = $row->fetch();
                    $pdo->prepare("DELETE FROM attachment WHERE id = ? AND attachable_type = 'announcement' AND attachable_id = ?")->execute([$aid, $announcementId]);
                    if ($f && !empty($f->file_path)) { $path = __DIR__ . '/../' . $f->file_path; if (is_file($path)) @unlink($path); }
                    $success = 'Pièce jointe supprimée.';
                } catch (PDOException $e) { $error = 'Impossible de supprimer.'; }
            }
            if (isset($_POST['create_notification'])) {
                $user_id = (int) ($_POST['notif_user_id'] ?? 0);
                $title = trim($_POST['notif_title'] ?? '');
                $body = trim($_POST['notif_body'] ?? '') ?: null;
                if ($user_id > 0 && $title !== '') {
                    try {
                        $pdo->prepare("INSERT INTO notification (user_id, title, body, type, related_entity_type, related_entity_id) VALUES (?, ?, ?, 'announcement', 'announcement', ?)")
                            ->execute([$user_id, $title, $body, $announcementId]);
                        $success = 'Notification envoyée.';
                        try { $pdo->prepare("INSERT INTO communication_history (user_id, entity_type, entity_id, action) VALUES (?, 'announcement', ?, 'notification_sent')")->execute([$user_id, $announcementId]); } catch (PDOException $e) {}
                    } catch (PDOException $e) { $error = 'Erreur notification.'; }
                } else { $error = 'Destinataire et titre obligatoires.'; }
            }
            if (isset($_POST['create_conversation'])) {
                $subject = trim($_POST['conv_subject'] ?? '') ?: null;
                $channel_id = trim($_POST['conv_channel_id'] ?? '') !== '' ? (int) $_POST['conv_channel_id'] : null;
                $created_by = trim($_POST['conv_created_by_user_id'] ?? '') !== '' ? (int) $_POST['conv_created_by_user_id'] : null;
                try {
                    $hasAnnouncementCol = false;
                    try { $pdo->query("SELECT announcement_id FROM conversation LIMIT 1"); $hasAnnouncementCol = true; } catch (PDOException $e) {}
                    if ($hasAnnouncementCol && $announcementId) {
                        $pdo->prepare("INSERT INTO conversation (channel_id, announcement_id, subject, conversation_type, created_by_user_id) VALUES (?, ?, ?, 'thread', ?)")
                            ->execute([$channel_id, $announcementId, $subject ?: 'Annonce #' . $announcementId, $created_by]);
                    } else {
                        $pdo->prepare("INSERT INTO conversation (channel_id, subject, conversation_type, created_by_user_id) VALUES (?, ?, 'thread', ?)")
                            ->execute([$channel_id, $subject ?: 'Annonce #' . $announcementId, $created_by]);
                    }
                    $success = 'Conversation créée.';
                    if ($created_by) { try { $pdo->prepare("INSERT INTO communication_history (user_id, entity_type, entity_id, action) VALUES (?, 'announcement', ?, 'conversation_created')")->execute([$created_by, $announcementId]); } catch (PDOException $e) {} }
                } catch (PDOException $e) { $error = 'Erreur : ' . $e->getMessage(); }
            }
        }
        if (isset($_POST['save_announcement'])) {
            $organisation_id = (int) ($_POST['organisation_id'] ?? 0);
            $channel_id = trim($_POST['channel_id'] ?? '') !== '' ? (int) $_POST['channel_id'] : null;
            $author_user_id = (int) ($_POST['author_user_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
            $published_at = trim($_POST['published_at'] ?? '') ?: null;
            $expires_at = trim($_POST['expires_at'] ?? '') ?: null;

            $cover_image = ($id > 0 && $detail && isset($detail->cover_image)) ? $detail->cover_image : null;
            if (!empty($_FILES['cover_image']['name'])) {
                $target_dir = __DIR__ . '/../uploads/announcements/covers/';
                if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
                $ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($ext, $allowed)) {
                    $fname = 'cover_' . time() . '_' . uniqid() . '.' . $ext;
                    $rel_path = 'uploads/announcements/covers/' . $fname;
                    if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $target_dir . $fname)) {
                        $cover_image = $rel_path;
                    }
                }
            }

            if ($title === '' || $organisation_id <= 0 || $author_user_id <= 0) {
                $error = 'Le titre, l\'organisation et l\'auteur sont obligatoires.';
            } else {
                try {
                    if ($id > 0) {
                        $has_cover = false;
                        try { $pdo->query("SELECT cover_image FROM announcement LIMIT 1"); $has_cover = true; } catch (PDOException $e) {}
                        if ($has_cover) {
                            $pdo->prepare("UPDATE announcement SET organisation_id = ?, channel_id = ?, author_user_id = ?, title = ?, content = ?, cover_image = ?, is_pinned = ?, published_at = ?, expires_at = ? WHERE id = ?")
                                ->execute([$organisation_id, $channel_id, $author_user_id, $title, $content, $cover_image, $is_pinned, $published_at, $expires_at, $id]);
                        } else {
                            $pdo->prepare("UPDATE announcement SET organisation_id = ?, channel_id = ?, author_user_id = ?, title = ?, content = ?, is_pinned = ?, published_at = ?, expires_at = ? WHERE id = ?")
                                ->execute([$organisation_id, $channel_id, $author_user_id, $title, $content, $is_pinned, $published_at, $expires_at, $id]);
                        }
                        $success = 'Annonce mise à jour.';
                        $action = 'view';
                        $stmt = $pdo->prepare("SELECT a.*, o.name AS organisation_name, c.name AS channel_name, u.first_name AS author_first_name, u.last_name AS author_last_name, u.email AS author_email FROM announcement a LEFT JOIN organisation o ON a.organisation_id = o.id LEFT JOIN channel c ON a.channel_id = c.id LEFT JOIN user u ON a.author_user_id = u.id WHERE a.id = ?");
                        $stmt->execute([$id]);
                        $detail = $stmt->fetch();
                    } else {
                        $has_cover = false;
                        try { $pdo->query("SELECT cover_image FROM announcement LIMIT 1"); $has_cover = true; } catch (PDOException $e) {}
                        if ($has_cover) {
                            $pdo->prepare("INSERT INTO announcement (organisation_id, channel_id, author_user_id, title, content, cover_image, is_pinned, published_at, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
                                ->execute([$organisation_id, $channel_id, $author_user_id, $title, $content, $cover_image, $is_pinned, $published_at, $expires_at]);
                        } else {
                            $pdo->prepare("INSERT INTO announcement (organisation_id, channel_id, author_user_id, title, content, is_pinned, published_at, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                                ->execute([$organisation_id, $channel_id, $author_user_id, $title, $content, $is_pinned, $published_at, $expires_at]);
                        }
                        $newId = (int) $pdo->lastInsertId();
                        header('Location: announcements.php?action=edit&id=' . $newId . '&msg=created');
                        exit;
                    }
                } catch (PDOException $e) {
                    $error = 'Erreur : ' . $e->getMessage();
                }
            }
        }
    }

    if ($id > 0) {
        $stmt = $pdo->prepare("
            SELECT a.*, o.name AS organisation_name, c.name AS channel_name,
                   u.first_name AS author_first_name, u.last_name AS author_last_name, u.email AS author_email
            FROM announcement a
            LEFT JOIN organisation o ON a.organisation_id = o.id
            LEFT JOIN channel c ON a.channel_id = c.id
            LEFT JOIN user u ON a.author_user_id = u.id
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        $detail = $stmt->fetch();
        if ($detail) {
            $stmt = $pdo->prepare("SELECT c.*, u.first_name, u.last_name, u.email FROM comment c JOIN user u ON c.user_id = u.id WHERE c.commentable_type = 'announcement' AND c.commentable_id = ? ORDER BY c.created_at DESC");
            $stmt->execute([$id]);
            $announcement_comments = $stmt->fetchAll();
            $stmt = $pdo->prepare("SELECT a.*, u.first_name, u.last_name FROM attachment a JOIN user u ON a.uploaded_by_user_id = u.id WHERE a.attachable_type = 'announcement' AND a.attachable_id = ? ORDER BY a.created_at DESC");
            $stmt->execute([$id]);
            $announcement_attachments = $stmt->fetchAll();
            $stmt = $pdo->prepare("SELECT n.*, u.first_name, u.last_name, u.email FROM notification n JOIN user u ON n.user_id = u.id WHERE n.related_entity_type = 'announcement' AND n.related_entity_id = ? ORDER BY n.created_at DESC");
            $stmt->execute([$id]);
            $announcement_notifications = $stmt->fetchAll();
            $stmt = $pdo->prepare("SELECT ch.*, u.first_name, u.last_name FROM communication_history ch LEFT JOIN user u ON ch.user_id = u.id WHERE ch.entity_type = 'announcement' AND ch.entity_id = ? ORDER BY ch.created_at DESC");
            $stmt->execute([$id]);
            $announcement_history = $stmt->fetchAll();
            $conversation_has_announcement_link = false;
            try { $pdo->query("SELECT announcement_id FROM conversation LIMIT 1"); $conversation_has_announcement_link = true; } catch (PDOException $e) {}
            if ($conversation_has_announcement_link) {
                $stmt = $pdo->prepare("SELECT cv.*, c.name AS channel_name, u.first_name AS created_by_first_name, u.last_name AS created_by_last_name FROM conversation cv LEFT JOIN channel c ON cv.channel_id = c.id LEFT JOIN user u ON cv.created_by_user_id = u.id WHERE cv.announcement_id = ? ORDER BY cv.updated_at DESC");
                $stmt->execute([$id]);
                $announcement_conversations = $stmt->fetchAll();
            } else {
                $announcement_conversations = [];
            }
        }
    }

    if (!$detail || $action === 'list') {
        $stats['total'] = (int) $pdo->query("SELECT COUNT(*) FROM announcement")->fetchColumn();
        $stats['pinned'] = (int) $pdo->query("SELECT COUNT(*) FROM announcement WHERE is_pinned = 1")->fetchColumn();
        $stmt = $pdo->query("
            SELECT a.id, a.title, a.is_pinned, a.published_at, a.expires_at, a.created_at,
                   o.name AS organisation_name,
                   u.first_name AS author_first_name, u.last_name AS author_last_name
            FROM announcement a
            LEFT JOIN organisation o ON a.organisation_id = o.id
            LEFT JOIN user u ON a.author_user_id = u.id
            ORDER BY a.is_pinned DESC, COALESCE(a.published_at, a.created_at) DESC, a.created_at DESC
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
        <li class="breadcrumb-item"><a href="announcements.php" class="text-decoration-none">Communication</a></li>
        <li class="breadcrumb-item"><a href="announcements.php" class="text-decoration-none">Annonces</a></li>
        <?php if ($action === 'add'): ?>
            <li class="breadcrumb-item active">Nouvelle annonce</li>
        <?php elseif ($action === 'edit' && $detail): ?>
            <li class="breadcrumb-item active">Modifier</li>
        <?php elseif ($detail): ?>
            <li class="breadcrumb-item active"><?= htmlspecialchars($detail->title) ?></li>
        <?php endif; ?>
    </ol>
</nav>

<header class="admin-header">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h1>
                <?php
                if ($action === 'add') echo 'Nouvelle annonce';
                elseif ($action === 'edit' && $detail) echo 'Modifier l\'annonce';
                elseif ($detail) echo htmlspecialchars($detail->title);
                else echo 'Annonces';
                ?>
            </h1>
            <p class="text-muted mb-0">
                <?php
                if ($detail && !$isForm) echo 'Détail de l\'annonce';
                elseif ($action === 'add') echo 'Créer une annonce pour communiquer avec les équipes.';
                else echo 'Liste des annonces publiées.';
                ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($detail && $action !== 'edit'): ?>
                <a href="announcements.php?action=edit&id=<?= (int) $detail->id ?>" class="btn btn-admin-primary"><i class="bi bi-pencil me-1"></i> Modifier</a>
                <a href="channels.php" class="btn btn-admin-outline"><i class="bi bi-chat-dots me-1"></i> Canaux</a>
                <a href="announcements.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Liste</a>
            <?php elseif ($isForm): ?>
                <a href="channels.php" class="btn btn-admin-outline"><i class="bi bi-chat-dots me-1"></i> Canaux</a>
                <a href="announcements.php<?= $id ? '?id=' . $id : '' ?>" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Annuler</a>
            <?php else: ?>
                <a href="channels.php" class="btn btn-admin-outline"><i class="bi bi-chat-dots me-1"></i> Canaux & Messages</a>
                <a href="announcements.php?action=add" class="btn btn-admin-primary"><i class="bi bi-plus-lg me-1"></i> Nouvelle annonce</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success">L'annonce a été supprimée.</div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
    <div class="alert alert-success">Annonce créée.</div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (!$detail && !$isForm): ?>
    <!-- Raccourcis de configuration (même logique que Missions / Canaux) -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="admin-card p-3 d-flex flex-wrap gap-2 align-items-center bg-light border shadow-sm">
                <span class="text-muted small fw-bold text-uppercase me-2"><i class="bi bi-sliders me-1"></i> Configuration :</span>
                <a href="channels.php" class="btn btn-sm btn-admin-outline"><i class="bi bi-chat-dots me-1"></i> Gérer les canaux</a>
                <a href="channel_types.php" class="btn btn-sm btn-admin-outline"><i class="bi bi-tag me-1"></i> Gérer les types de canal</a>
            </div>
        </div>
    </div>
    <!-- Cartes statistiques -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-4">
            <div class="admin-card text-center p-3 h-100">
                <div class="text-muted small text-uppercase mb-1 fw-bold">Total</div>
                <div class="h3 mb-0 fw-bold"><?= $stats['total'] ?></div>
                <div class="mt-2"><span class="badge bg-secondary">Annonces</span></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="admin-card text-center p-3 h-100">
                <div class="text-muted small text-uppercase mb-1 fw-bold">Épinglées</div>
                <div class="h3 mb-0 fw-bold text-primary"><?= $stats['pinned'] ?></div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($isForm): ?>
    <div class="admin-card admin-section-card">
        <h5 class="card-title mb-4"><i class="bi bi-megaphone"></i> <?= $id ? 'Modifier l\'annonce' : 'Nouvelle annonce' ?></h5>
        <form method="POST" action="<?= $id ? 'announcements.php?action=edit&id=' . $id : 'announcements.php?action=add' ?>" enctype="multipart/form-data">
            <input type="hidden" name="save_announcement" value="1">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-bold">Photo de couverture</label>
                    <?php if ($detail && !empty($detail->cover_image)): ?>
                    <div class="mb-2">
                        <img src="../<?= htmlspecialchars($detail->cover_image) ?>" alt="Couverture" class="rounded border" style="max-height: 180px; object-fit: cover;">
                        <p class="form-text small mb-0">Image actuelle. Choisir un nouveau fichier pour la remplacer.</p>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="cover_image" class="form-control" accept="image/*">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Organisation *</label>
                    <select name="organisation_id" class="form-select" required>
                        <option value="">— Choisir —</option>
                        <?php foreach ($organisations as $o): ?>
                            <option value="<?= (int) $o->id ?>" <?= ($detail && $detail->organisation_id == $o->id) || (!$detail && $activeOrganisationId && (int) $o->id === $activeOrganisationId) ? 'selected' : '' ?>><?= htmlspecialchars($o->name) ?><?= $activeOrganisationId && (int) $o->id === $activeOrganisationId ? ' (site client)' : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Sur le site client, seules les annonces de l’organisation marquée « site client » sont affichées.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Canal (optionnel)</label>
                    <select name="channel_id" class="form-select">
                        <option value="">— Aucun —</option>
                        <?php foreach ($channels as $c): ?>
                            <option value="<?= (int) $c->id ?>" <?= ($detail && $detail->channel_id == $c->id) ? 'selected' : '' ?>><?= htmlspecialchars($c->name) ?> (<?= htmlspecialchars($c->channel_type) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Auteur *</label>
                    <select name="author_user_id" class="form-select" required>
                        <option value="">— Choisir —</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= (int) $u->id ?>" <?= ($detail && $detail->author_user_id == $u->id) ? 'selected' : '' ?>><?= htmlspecialchars($u->last_name . ' ' . $u->first_name) ?> (<?= htmlspecialchars($u->email) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Titre *</label>
                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($detail->title ?? '') ?>" required placeholder="Titre de l'annonce">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Contenu *</label>
                    <textarea name="content" id="announcement_content" class="form-control" rows="6" required placeholder="Contenu de l'annonce"></textarea>
                <?php if (!empty($detail->content)): ?>
                <script type="application/json" id="announcement_content_data"><?= json_encode($detail->content, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
                <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Publiée le</label>
                    <input type="datetime-local" name="published_at" class="form-control" value="<?= $detail && $detail->published_at ? date('Y-m-d\TH:i', strtotime($detail->published_at)) : '' ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Expire le</label>
                    <input type="datetime-local" name="expires_at" class="form-control" value="<?= $detail && $detail->expires_at ? date('Y-m-d\TH:i', strtotime($detail->expires_at)) : '' ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check">
                        <input type="checkbox" name="is_pinned" id="is_pinned" class="form-check-input" value="1" <?= ($detail->is_pinned ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_pinned">Épinglée</label>
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
        <h5 class="card-title"><i class="bi bi-megaphone"></i> Informations annonce</h5>
        <?php if (!empty($detail->cover_image)): ?>
        <div class="mb-3">
            <strong>Photo de couverture</strong>
            <div class="mt-1"><img src="../<?= htmlspecialchars($detail->cover_image) ?>" alt="Couverture" class="rounded border" style="max-height: 200px; object-fit: cover;"></div>
        </div>
        <?php endif; ?>
        <table class="admin-table mb-0">
            <tr><th style="width:180px;">Titre</th><td><?= htmlspecialchars($detail->title) ?></td></tr>
            <tr><th>Organisation</th><td><?= htmlspecialchars($detail->organisation_name ?? '—') ?></td></tr>
            <tr><th>Canal</th><td><?= htmlspecialchars($detail->channel_name ?? '—') ?></td></tr>
            <tr><th>Auteur</th><td><?= htmlspecialchars(trim(($detail->author_first_name ?? '') . ' ' . ($detail->author_last_name ?? '')) ?: $detail->author_email ?? '—') ?></td></tr>
            <tr><th>Épinglée</th><td><?= $detail->is_pinned ? 'Oui' : 'Non' ?></td></tr>
            <tr><th>Publiée le</th><td><?= $detail->published_at ? date('d/m/Y H:i', strtotime($detail->published_at)) : '—' ?></td></tr>
            <tr><th>Expire le</th><td><?= $detail->expires_at ? date('d/m/Y H:i', strtotime($detail->expires_at)) : '—' ?></td></tr>
            <tr><th>Créée le</th><td><?= $detail->created_at ? date('d/m/Y H:i', strtotime($detail->created_at)) : '—' ?></td></tr>
        </table>
        <?php if (!empty($detail->content)): ?>
            <div class="mt-3 pt-3 border-top">
                <strong>Contenu</strong>
                <div class="mt-1 announcement-content"><?= $detail->content ?></div>
            </div>
        <?php endif; ?>
        <div class="mt-4 d-flex gap-2">
            <a href="announcements.php?action=edit&id=<?= (int) $detail->id ?>" class="btn btn-admin-primary"><i class="bi bi-pencil me-1"></i> Modifier</a>
            <a href="channels.php" class="btn btn-admin-outline"><i class="bi bi-chat-dots me-1"></i> Canaux</a>
            <a href="announcements.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Liste</a>
            <button type="button" class="btn btn-outline-danger ms-auto" data-bs-toggle="modal" data-bs-target="#deleteAnnouncementModal"><i class="bi bi-trash me-1"></i> Supprimer</button>
        </div>
    </div>
    <div class="modal fade" id="deleteAnnouncementModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Supprimer cette annonce ?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">L'annonce <strong><?= htmlspecialchars($detail->title) ?></strong> sera supprimée. Cette action est irréversible.</div>
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

    <!-- Onglets / sections liées à l'annonce -->
    <ul class="nav nav-tabs mt-4 mb-3 border-0 admin-header-tabs" id="announcementTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-comments" type="button"><i class="bi bi-chat-quote me-1"></i> Commentaires (<?= count($announcement_comments) ?>)</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-attachments" type="button"><i class="bi bi-paperclip me-1"></i> Pièces jointes (<?= count($announcement_attachments) ?>)</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-notifications" type="button"><i class="bi bi-bell me-1"></i> Notifications (<?= count($announcement_notifications) ?>)</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-conversations" type="button"><i class="bi bi-chat-text me-1"></i> Conversations (<?= count($announcement_conversations) ?>)</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-history" type="button"><i class="bi bi-clock-history me-1"></i> Historique (<?= count($announcement_history) ?>)</button></li>
    </ul>
    <div class="tab-content" id="announcementTabsContent">
        <div class="tab-pane fade show active" id="tab-comments" role="tabpanel">
            <div class="admin-card admin-section-card">
                <h5 class="card-title mb-3"><i class="bi bi-chat-quote"></i> Commentaires sur cette annonce</h5>
                <form method="POST" enctype="multipart/form-data" class="mb-4 p-3 bg-light rounded">
                    <input type="hidden" name="add_comment" value="1">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3"><label class="form-label small mb-0">Auteur</label><select name="comment_user_id" class="form-select form-select-sm" required><option value="">— Choisir —</option><?php foreach ($users as $u): ?><option value="<?= (int) $u->id ?>"><?= htmlspecialchars($u->last_name . ' ' . $u->first_name) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-6"><label class="form-label small mb-0">Commentaire</label><input type="text" name="comment_body" class="form-control form-control-sm" required placeholder="Votre commentaire"></div>
                        <div class="col-md-2"><button type="submit" class="btn btn-admin-primary btn-sm w-100"><i class="bi bi-plus me-1"></i> Ajouter</button></div>
                    </div>
                </form>
                <?php if (count($announcement_comments) > 0): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($announcement_comments as $c): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div><strong><?= htmlspecialchars($c->last_name . ' ' . $c->first_name) ?></strong> <span class="text-muted small"><?= $c->created_at ? date('d/m/Y H:i', strtotime($c->created_at)) : '' ?></span><br><span class="text-break"><?= nl2br(htmlspecialchars($c->body)) ?></span></div>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce commentaire ?');"><input type="hidden" name="delete_comment_id" value="<?= (int) $c->id ?>"><button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">Aucun commentaire. Utilisez le formulaire ci-dessus pour en ajouter.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="tab-pane fade" id="tab-attachments" role="tabpanel">
            <div class="admin-card admin-section-card">
                <h5 class="card-title mb-3"><i class="bi bi-paperclip"></i> Pièces jointes de cette annonce</h5>
                <form method="POST" enctype="multipart/form-data" class="mb-4 p-3 bg-light rounded">
                    <input type="hidden" name="add_attachment" value="1">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3"><label class="form-label small mb-0">Déposé par</label><select name="attachment_user_id" class="form-select form-select-sm" required><option value="">— Choisir —</option><?php foreach ($users as $u): ?><option value="<?= (int) $u->id ?>"><?= htmlspecialchars($u->last_name . ' ' . $u->first_name) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-5"><label class="form-label small mb-0">Fichier</label><input type="file" name="attachment_file" class="form-control form-control-sm" required></div>
                        <div class="col-md-2"><button type="submit" class="btn btn-admin-primary btn-sm w-100"><i class="bi bi-upload me-1"></i> Joindre</button></div>
                    </div>
                </form>
                <?php if (count($announcement_attachments) > 0): ?>
                    <div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead class="bg-light"><tr><th>Fichier</th><th>Déposé par</th><th>Date</th><th class="text-end">Actions</th></tr></thead><tbody>
                        <?php foreach ($announcement_attachments as $a): ?>
                            <tr><td><a href="../<?= htmlspecialchars($a->file_path) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($a->file_name) ?></a></td><td><?= htmlspecialchars($a->last_name . ' ' . $a->first_name) ?></td><td class="text-muted small"><?= $a->created_at ? date('d/m/Y H:i', strtotime($a->created_at)) : '' ?></td><td class="text-end"><a href="../<?= htmlspecialchars($a->file_path) ?>" target="_blank" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-download"></i></a><form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette pièce jointe ?');"><input type="hidden" name="delete_attachment_id" value="<?= (int) $a->id ?>"><button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></td></tr>
                        <?php endforeach; ?>
                    </tbody></table></div>
                <?php else: ?>
                    <p class="text-muted mb-0">Aucune pièce jointe.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="tab-pane fade" id="tab-notifications" role="tabpanel">
            <div class="admin-card admin-section-card">
                <h5 class="card-title mb-3"><i class="bi bi-bell"></i> Notifications liées à cette annonce</h5>
                <form method="POST" class="mb-4 p-3 bg-light rounded">
                    <input type="hidden" name="create_notification" value="1">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3"><label class="form-label small mb-0">Destinataire</label><select name="notif_user_id" class="form-select form-select-sm" required><option value="">— Choisir —</option><?php foreach ($users as $u): ?><option value="<?= (int) $u->id ?>"><?= htmlspecialchars($u->last_name . ' ' . $u->first_name) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-2"><label class="form-label small mb-0">Titre</label><input type="text" name="notif_title" class="form-control form-control-sm" required placeholder="Titre" value="Annonce : <?= htmlspecialchars($detail->title ?? '') ?>"></div>
                        <div class="col-md-4"><label class="form-label small mb-0">Message (optionnel)</label><input type="text" name="notif_body" class="form-control form-control-sm" placeholder="Contenu"></div>
                        <div class="col-md-2"><button type="submit" class="btn btn-admin-primary btn-sm w-100"><i class="bi bi-send me-1"></i> Envoyer</button></div>
                    </div>
                </form>
                <?php if (count($announcement_notifications) > 0): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($announcement_notifications as $n): ?>
                            <li class="list-group-item d-flex justify-content-between"><div><strong><?= htmlspecialchars($n->title) ?></strong> → <?= htmlspecialchars($n->last_name . ' ' . $n->first_name) ?> <span class="text-muted small"><?= $n->created_at ? date('d/m/Y H:i', strtotime($n->created_at)) : '' ?></span> <?= $n->is_read ? '<span class="badge bg-success">Lu</span>' : '<span class="badge bg-warning text-dark">Non lu</span>' ?></div></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">Aucune notification envoyée pour cette annonce.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="tab-pane fade" id="tab-conversations" role="tabpanel">
            <div class="admin-card admin-section-card">
                <h5 class="card-title mb-3"><i class="bi bi-chat-text"></i> Conversations liées à cette annonce</h5>
                <?php if (!empty($conversation_has_announcement_link)): ?>
                <form method="POST" class="mb-4 p-3 bg-light rounded">
                    <input type="hidden" name="create_conversation" value="1">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3"><label class="form-label small mb-0">Sujet</label><input type="text" name="conv_subject" class="form-control form-control-sm" placeholder="Sujet (optionnel)"></div>
                        <div class="col-md-2"><label class="form-label small mb-0">Canal</label><select name="conv_channel_id" class="form-select form-select-sm"><option value="">— Aucun —</option><?php foreach ($channels as $c): ?><option value="<?= (int) $c->id ?>"><?= htmlspecialchars($c->name) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-2"><label class="form-label small mb-0">Créé par</label><select name="conv_created_by_user_id" class="form-select form-select-sm"><option value="">—</option><?php foreach ($users as $u): ?><option value="<?= (int) $u->id ?>"><?= htmlspecialchars($u->last_name . ' ' . $u->first_name) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-2"><button type="submit" class="btn btn-admin-primary btn-sm w-100"><i class="bi bi-plus me-1"></i> Nouvelle conversation</button></div>
                    </div>
                </form>
                <?php endif; ?>
                <?php if (count($announcement_conversations) > 0): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($announcement_conversations as $cv): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div><strong><?= htmlspecialchars($cv->subject ?: 'Conversation #' . $cv->id) ?></strong> <?= $cv->channel_name ? ' <span class="badge bg-secondary">' . htmlspecialchars($cv->channel_name) . '</span>' : '' ?> <span class="text-muted small"><?= $cv->created_at ? date('d/m/Y H:i', strtotime($cv->created_at)) : '' ?></span></div>
                                <a href="conversations.php?id=<?= (int) $cv->id ?>" class="btn btn-sm btn-admin-outline"><i class="bi bi-eye me-1"></i> Voir / Répondre</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">Aucune conversation liée.<?= !empty($conversation_has_announcement_link) ? ' Créez-en une avec le formulaire ci-dessus.' : ' Le schéma <code>database/schema.sql</code> inclut la liaison conversation–annonce.' ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="tab-pane fade" id="tab-history" role="tabpanel">
            <div class="admin-card admin-section-card">
                <h5 class="card-title mb-3"><i class="bi bi-clock-history"></i> Historique des actions sur cette annonce</h5>
                <?php if (count($announcement_history) > 0): ?>
                    <div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead class="bg-light"><tr><th>Date</th><th>Utilisateur</th><th>Action</th><th>Métadonnées</th></tr></thead><tbody>
                        <?php foreach ($announcement_history as $h): ?>
                            <tr><td class="text-muted small"><?= $h->created_at ? date('d/m/Y H:i:s', strtotime($h->created_at)) : '—' ?></td><td><?= $h->user_id ? htmlspecialchars(trim(($h->first_name ?? '') . ' ' . ($h->last_name ?? '')) ?: '—') : '—' ?></td><td><span class="badge bg-light text-dark"><?= htmlspecialchars($h->action) ?></span></td><td class="small text-break"><?= $h->metadata ? htmlspecialchars(is_string($h->metadata) ? $h->metadata : json_encode($h->metadata)) : '—' ?></td></tr>
                        <?php endforeach; ?>
                    </tbody></table></div>
                <?php else: ?>
                    <p class="text-muted mb-0">Aucune entrée dans l'historique pour cette annonce.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var hash = window.location.hash;
        if (hash && document.querySelector('#announcementTabsContent ' + hash)) {
            var btn = document.querySelector('#announcementTabs button[data-bs-target="' + hash + '"]');
            if (btn && typeof bootstrap !== 'undefined' && bootstrap.Tab) {
                var t = new bootstrap.Tab(btn);
                t.show();
            }
        }
    });
    </script>
<?php elseif (count($list) > 0): ?>
    <div class="admin-card admin-section-card announcements-datatable-wrapper">
        <div class="table-responsive announcements-table-responsive">
            <table class="admin-table table table-hover" id="announcementsTable">
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Organisation</th>
                        <th>Auteur</th>
                        <th>Épinglée</th>
                        <th>Publiée le</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($list as $a): ?>
                        <tr>
                            <td><a href="announcements.php?id=<?= (int) $a->id ?>"><?= htmlspecialchars($a->title) ?></a></td>
                            <td><?= htmlspecialchars($a->organisation_name ?? '—') ?></td>
                            <td><?= htmlspecialchars(trim(($a->author_first_name ?? '') . ' ' . ($a->author_last_name ?? '')) ?: '—') ?></td>
                            <td><?= $a->is_pinned ? '<span class="badge bg-warning text-dark">Épinglée</span>' : '—' ?></td>
                            <td class="text-muted"><?= $a->published_at ? date('d/m/Y H:i', strtotime($a->published_at)) : '—' ?></td>
                            <td class="text-end">
                                <div class="dropdown" data-bs-boundary="viewport">
                                    <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" data-bs-reference="toggle" aria-expanded="false" title="Actions"><i class="bi bi-three-dots-vertical"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                        <li><a class="dropdown-item" href="announcements.php?id=<?= (int) $a->id ?>"><i class="bi bi-eye me-2"></i> Voir la fiche</a></li>
                                        <li><a class="dropdown-item" href="announcements.php?action=edit&id=<?= (int) $a->id ?>"><i class="bi bi-pencil me-2"></i> Modifier</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><h6 class="dropdown-header py-1">Accès rapide</h6></li>
                                        <li><a class="dropdown-item" href="announcements.php?id=<?= (int) $a->id ?>#tab-comments"><i class="bi bi-chat-quote me-2"></i> Commentaires</a></li>
                                        <li><a class="dropdown-item" href="announcements.php?id=<?= (int) $a->id ?>#tab-attachments"><i class="bi bi-paperclip me-2"></i> Pièces jointes</a></li>
                                        <li><a class="dropdown-item" href="announcements.php?id=<?= (int) $a->id ?>#tab-notifications"><i class="bi bi-bell me-2"></i> Notifications</a></li>
                                        <li><a class="dropdown-item" href="announcements.php?id=<?= (int) $a->id ?>#tab-conversations"><i class="bi bi-chat-text me-2"></i> Conversations</a></li>
                                        <li><a class="dropdown-item" href="announcements.php?id=<?= (int) $a->id ?>#tab-history"><i class="bi bi-clock-history me-2"></i> Historique</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" onsubmit="return confirm('Supprimer cette annonce ?');">
                                                <input type="hidden" name="delete_id" value="<?= (int) $a->id ?>">
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
    <style>
        .breadcrumb{font-size:0.85rem;}
        .admin-table th{background:#f8f9fa;padding:1rem 0.5rem;}
        .admin-table td{padding:1rem 0.5rem;}
        /* Le popup des actions doit s'afficher en dehors du datatable (pas coupé par overflow) */
        .announcements-datatable-wrapper,
        .announcements-datatable-wrapper .announcements-table-responsive,
        .announcements-datatable-wrapper .dataTables_wrapper { overflow: visible !important; }
        .announcements-datatable-wrapper .dataTables_scrollBody { overflow: visible !important; }
        #announcementsTable td:last-child { overflow: visible; position: relative; z-index: 1; }
        .announcements-datatable-wrapper .dropdown-menu { z-index: 1060; }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('announcementsTable') && typeof $ !== 'undefined' && $.fn.DataTable) {
            $('#announcementsTable').DataTable({
                language: { url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json" },
                order: [[4, "desc"]],
                pageLength: 10,
                dom: '<"d-flex justify-content-between align-items-center mb-3"f>t<"d-flex justify-content-between align-items-center mt-3"ip>',
                columnDefs: [{ orderable: false, targets: 5 }]
            });
        }
    });
    </script>
<?php else: ?>
    <div class="admin-card admin-section-card">
        <p class="admin-empty py-4 mb-0"><i class="bi bi-megaphone"></i> Aucune annonce. <a href="announcements.php?action=add">Créer une annonce</a>. <?php if (empty($organisations)): ?> Créez d'abord une <strong><a href="organisations.php">organisation</a></strong>.<?php endif; ?></p>
    </div>
<?php endif; ?>

<?php if ($isForm): ?>
<style>.announcement-content { vertical-align: top; } .announcement-content h1, .announcement-content h2, .announcement-content h3, .announcement-content h4 { margin: 1rem 0 0.5rem; font-size: 1rem; font-weight: 600; } .announcement-content p { margin: 0.5rem 0; } .announcement-content ul, .announcement-content ol { margin: 0.5rem 0; padding-left: 1.5rem; } .announcement-content img { max-width: 100%; height: auto; }</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof $ !== 'undefined' && $.fn.summernote) {
        $('#announcement_content').summernote({
            placeholder: 'Contenu de l\'annonce...',
            tabsize: 2,
            height: 220,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ],
            callbacks: {
                onImageUpload: function(files) {
                    for (var i = 0; i < files.length; i++) {
                        var data = new FormData();
                        data.append('file', files[i]);
                        $.ajax({
                            url: 'upload_announcement_image.php',
                            data: data,
                            processData: false,
                            contentType: false,
                            type: 'POST',
                            success: function(res) {
                                if (res && res.url) $('#announcement_content').summernote('insertImage', res.url);
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
        var dataEl = document.getElementById('announcement_content_data');
        if (dataEl) {
            try {
                var content = JSON.parse(dataEl.textContent);
                if (content) $('#announcement_content').summernote('code', content);
            } catch (e) {}
        }
    }
});
</script>
<?php endif; ?>

<footer class="admin-main-footer mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <a href="channels.php" class="text-muted text-decoration-none small"><i class="bi bi-chat-dots me-1"></i> Canaux & Messages</a>
        <span class="small text-muted">&copy; <?= date('Y') ?> Expertise</span>
    </div>
</footer>
<?php require __DIR__ . '/inc/footer.php'; ?>
