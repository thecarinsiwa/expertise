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

if ($pdo) {
    try {
        $organisations = $pdo->query("SELECT id, name FROM organisation ORDER BY name")->fetchAll();
        $channels = $pdo->query("SELECT id, name, channel_type FROM channel ORDER BY name")->fetchAll();
        $users = $pdo->query("SELECT id, first_name, last_name, email FROM user WHERE is_active = 1 ORDER BY last_name, first_name")->fetchAll();
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
        if (isset($_POST['save_announcement'])) {
            $organisation_id = (int) ($_POST['organisation_id'] ?? 0);
            $channel_id = trim($_POST['channel_id'] ?? '') !== '' ? (int) $_POST['channel_id'] : null;
            $author_user_id = (int) ($_POST['author_user_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
            $published_at = trim($_POST['published_at'] ?? '') ?: null;
            $expires_at = trim($_POST['expires_at'] ?? '') ?: null;

            if ($title === '' || $organisation_id <= 0 || $author_user_id <= 0) {
                $error = 'Le titre, l\'organisation et l\'auteur sont obligatoires.';
            } else {
                try {
                    if ($id > 0) {
                        $pdo->prepare("UPDATE announcement SET organisation_id = ?, channel_id = ?, author_user_id = ?, title = ?, content = ?, is_pinned = ?, published_at = ?, expires_at = ? WHERE id = ?")
                            ->execute([$organisation_id, $channel_id, $author_user_id, $title, $content, $is_pinned, $published_at, $expires_at, $id]);
                        $success = 'Annonce mise à jour.';
                        $action = 'view';
                    } else {
                        $pdo->prepare("INSERT INTO announcement (organisation_id, channel_id, author_user_id, title, content, is_pinned, published_at, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                            ->execute([$organisation_id, $channel_id, $author_user_id, $title, $content, $is_pinned, $published_at, $expires_at]);
                        header('Location: announcements.php?id=' . $pdo->lastInsertId() . '&msg=created');
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
        <form method="POST" action="<?= $id ? 'announcements.php?action=edit&id=' . $id : 'announcements.php?action=add' ?>">
            <input type="hidden" name="save_announcement" value="1">
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
                    <textarea name="content" class="form-control" rows="5" required placeholder="Contenu de l'annonce"><?= htmlspecialchars($detail->content ?? '') ?></textarea>
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
                <div class="mt-1"><?= nl2br(htmlspecialchars($detail->content)) ?></div>
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
<?php elseif (count($list) > 0): ?>
    <div class="admin-card admin-section-card">
        <div class="table-responsive">
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
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                        <li><a class="dropdown-item" href="announcements.php?id=<?= (int) $a->id ?>"><i class="bi bi-eye me-2"></i> Voir</a></li>
                                        <li><a class="dropdown-item" href="announcements.php?action=edit&id=<?= (int) $a->id ?>"><i class="bi bi-pencil me-2"></i> Modifier</a></li>
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
    <style>.breadcrumb{font-size:0.85rem;} .admin-table th{background:#f8f9fa;padding:1rem 0.5rem;} .admin-table td{padding:1rem 0.5rem;}</style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('announcementsTable') && typeof $ !== 'undefined' && $.fn.DataTable) {
            $('#announcementsTable').DataTable({ language: { url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json" }, order: [[4, "desc"]], pageLength: 10, dom: '<"d-flex justify-content-between align-items-center mb-3"f>t<"d-flex justify-content-between align-items-center mt-3"ip>' });
        }
    });
    </script>
<?php else: ?>
    <div class="admin-card admin-section-card">
        <p class="admin-empty py-4 mb-0"><i class="bi bi-megaphone"></i> Aucune annonce. <a href="announcements.php?action=add">Créer une annonce</a>. <?php if (empty($organisations)): ?> Créez d'abord une <strong><a href="organisations.php">organisation</a></strong>.<?php endif; ?></p>
    </div>
<?php endif; ?>

<footer class="admin-main-footer mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <a href="channels.php" class="text-muted text-decoration-none small"><i class="bi bi-chat-dots me-1"></i> Canaux & Messages</a>
        <span class="small text-muted">&copy; <?= date('Y') ?> Expertise</span>
    </div>
</footer>
<?php require __DIR__ . '/inc/footer.php'; ?>
