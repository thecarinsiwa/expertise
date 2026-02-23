<?php
$pageTitle = 'Canaux & Messages – Administration';
$currentNav = 'channels';
require_once __DIR__ . '/inc/auth.php';
require __DIR__ . '/inc/db.php';

$list = [];
$detail = null;
$members = [];
$stats = ['total' => 0, 'public' => 0, 'private' => 0];
$error = '';
$success = '';
$organisations = [];
$users = [];

if ($pdo) {
    try {
        $organisations = $pdo->query("SELECT id, name FROM organisation ORDER BY name")->fetchAll();
        $users = $pdo->query("SELECT id, first_name, last_name, email FROM user WHERE is_active = 1 ORDER BY last_name, first_name")->fetchAll();
    } catch (PDOException $e) {
        $organisations = [];
        $users = [];
    }

    $action = $_GET['action'] ?? 'list';
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['delete_id'])) {
            $delId = (int) $_POST['delete_id'];
            try {
                $pdo->prepare("DELETE FROM channel WHERE id = ?")->execute([$delId]);
                header('Location: channels.php?msg=deleted');
                exit;
            } catch (PDOException $e) {
                $error = 'Impossible de supprimer le canal (conversations ou annonces peuvent y être liées).';
            }
        }
        if (isset($_POST['add_member']) && $id > 0) {
            $user_id = (int) ($_POST['user_id'] ?? 0);
            $role = trim($_POST['role'] ?? 'member') ?: 'member';
            if ($user_id > 0) {
                try {
                    $pdo->prepare("INSERT IGNORE INTO channel_member (channel_id, user_id, role) VALUES (?, ?, ?)")->execute([$id, $user_id, $role]);
                    $success = 'Membre ajouté.';
                } catch (PDOException $e) {
                    $error = 'Ce membre est déjà dans le canal ou erreur.';
                }
            }
        }
        if (isset($_POST['remove_member']) && $id > 0) {
            $user_id = (int) ($_POST['user_id'] ?? 0);
            if ($user_id > 0) {
                try {
                    $pdo->prepare("DELETE FROM channel_member WHERE channel_id = ? AND user_id = ?")->execute([$id, $user_id]);
                    $success = 'Membre retiré.';
                } catch (PDOException $e) {
                    $error = 'Erreur lors du retrait.';
                }
            }
        }
        if (isset($_POST['save_channel'])) {
            $organisation_id = trim($_POST['organisation_id'] ?? '') !== '' ? (int) $_POST['organisation_id'] : null;
            $name = trim($_POST['name'] ?? '');
            $code = trim($_POST['code'] ?? '') ?: null;
            $channel_type = in_array($_POST['channel_type'] ?? '', ['public', 'private', 'direct', 'announcement']) ? $_POST['channel_type'] : 'public';
            $description = trim($_POST['description'] ?? '') ?: null;
            $created_by_user_id = trim($_POST['created_by_user_id'] ?? '') !== '' ? (int) $_POST['created_by_user_id'] : null;

            if ($name === '') {
                $error = 'Le nom du canal est obligatoire.';
            } else {
                try {
                    if ($id > 0) {
                        $pdo->prepare("UPDATE channel SET organisation_id = ?, name = ?, code = ?, channel_type = ?, description = ?, created_by_user_id = ? WHERE id = ?")
                            ->execute([$organisation_id, $name, $code, $channel_type, $description, $created_by_user_id, $id]);
                        $success = 'Canal mis à jour.';
                        $action = 'view';
                    } else {
                        $pdo->prepare("INSERT INTO channel (organisation_id, name, code, channel_type, description, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?)")
                            ->execute([$organisation_id, $name, $code, $channel_type, $description, $created_by_user_id]);
                        header('Location: channels.php?id=' . $pdo->lastInsertId() . '&msg=created');
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
            SELECT c.*, o.name AS organisation_name,
                   u.first_name AS created_by_first_name, u.last_name AS created_by_last_name, u.email AS created_by_email
            FROM channel c
            LEFT JOIN organisation o ON c.organisation_id = o.id
            LEFT JOIN user u ON c.created_by_user_id = u.id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        $detail = $stmt->fetch();
        if ($detail) {
            $stmtM = $pdo->prepare("
                SELECT cm.*, u.first_name, u.last_name, u.email
                FROM channel_member cm
                JOIN user u ON cm.user_id = u.id
                WHERE cm.channel_id = ?
                ORDER BY cm.joined_at
            ");
            $stmtM->execute([$id]);
            $members = $stmtM->fetchAll();
        }
    }

    if (!$detail || $action === 'list') {
        $stats['total'] = (int) $pdo->query("SELECT COUNT(*) FROM channel")->fetchColumn();
        $stats['public'] = (int) $pdo->query("SELECT COUNT(*) FROM channel WHERE channel_type = 'public'")->fetchColumn();
        $stats['private'] = (int) $pdo->query("SELECT COUNT(*) FROM channel WHERE channel_type = 'private'")->fetchColumn();
        $stmt = $pdo->query("
            SELECT c.id, c.name, c.code, c.channel_type, c.created_at,
                   o.name AS organisation_name,
                   (SELECT COUNT(*) FROM channel_member cm WHERE cm.channel_id = c.id) AS members_count,
                   (SELECT COUNT(*) FROM conversation cv WHERE cv.channel_id = c.id) AS conversations_count
            FROM channel c
            LEFT JOIN organisation o ON c.organisation_id = o.id
            ORDER BY c.name
        ");
        if ($stmt) $list = $stmt->fetchAll();
    }
}
require __DIR__ . '/inc/header.php';

$isForm = ($action === 'add') || ($action === 'edit' && $detail);
$channelTypes = ['public' => 'Public', 'private' => 'Privé', 'direct' => 'Direct', 'announcement' => 'Annonce'];
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Tableau de bord</a></li>
        <li class="breadcrumb-item"><a href="announcements.php" class="text-decoration-none">Communication</a></li>
        <li class="breadcrumb-item"><a href="channels.php" class="text-decoration-none">Canaux</a></li>
        <?php if ($action === 'add'): ?>
            <li class="breadcrumb-item active">Nouveau canal</li>
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
                if ($action === 'add') echo 'Nouveau canal';
                elseif ($action === 'edit' && $detail) echo 'Modifier le canal';
                elseif ($detail) echo htmlspecialchars($detail->name);
                else echo 'Canaux & Messages';
                ?>
            </h1>
            <p class="text-muted mb-0">
                <?php
                if ($detail && !$isForm) echo 'Fiche canal et membres';
                elseif ($action === 'add') echo 'Créer un canal pour les conversations et annonces.';
                else echo 'Gestion des canaux de communication.';
                ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($detail && $action !== 'edit'): ?>
                <a href="channels.php?action=edit&id=<?= (int) $detail->id ?>" class="btn btn-admin-primary"><i class="bi bi-pencil me-1"></i> Modifier</a>
                <a href="announcements.php" class="btn btn-admin-outline"><i class="bi bi-megaphone me-1"></i> Annonces</a>
                <a href="channels.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Liste</a>
            <?php elseif ($isForm): ?>
                <a href="announcements.php" class="btn btn-admin-outline"><i class="bi bi-megaphone me-1"></i> Annonces</a>
                <a href="channels.php<?= $id ? '?id=' . $id : '' ?>" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Annuler</a>
            <?php else: ?>
                <a href="announcements.php" class="btn btn-admin-outline"><i class="bi bi-megaphone me-1"></i> Annonces</a>
                <a href="channels.php?action=add" class="btn btn-admin-primary"><i class="bi bi-plus-lg me-1"></i> Nouveau canal</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success">Le canal a été supprimé.</div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
    <div class="alert alert-success">Canal créé.</div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (!$detail && !$isForm): ?>
    <!-- Raccourcis de configuration (même logique que page Missions) -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="admin-card p-3 d-flex flex-wrap gap-2 align-items-center bg-light border shadow-sm">
                <span class="text-muted small fw-bold text-uppercase me-2"><i class="bi bi-sliders me-1"></i> Configuration :</span>
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
                <div class="mt-2"><span class="badge bg-secondary">Canaux</span></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="admin-card text-center p-3 h-100">
                <div class="text-muted small text-uppercase mb-1 fw-bold">Publics</div>
                <div class="h3 mb-0 fw-bold text-primary"><?= $stats['public'] ?></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="admin-card text-center p-3 h-100">
                <div class="text-muted small text-uppercase mb-1 fw-bold">Privés</div>
                <div class="h3 mb-0 fw-bold text-muted"><?= $stats['private'] ?></div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($isForm): ?>
    <div class="admin-card admin-section-card">
        <h5 class="card-title mb-4"><i class="bi bi-chat-dots"></i> <?= $id ? 'Modifier le canal' : 'Nouveau canal' ?></h5>
        <form method="POST" action="<?= $id ? 'channels.php?action=edit&id=' . $id : 'channels.php?action=add' ?>">
            <input type="hidden" name="save_channel" value="1">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Organisation</label>
                    <select name="organisation_id" class="form-select">
                        <option value="">— Aucune —</option>
                        <?php foreach ($organisations as $o): ?>
                            <option value="<?= (int) $o->id ?>" <?= ($detail && $detail->organisation_id == $o->id) ? 'selected' : '' ?>><?= htmlspecialchars($o->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Type de canal *</label>
                    <select name="channel_type" class="form-select" required>
                        <?php foreach ($channelTypes as $k => $v): ?>
                            <option value="<?= htmlspecialchars($k) ?>" <?= ($detail && $detail->channel_type === $k) ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Nom *</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($detail->name ?? '') ?>" required placeholder="Nom du canal">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Code</label>
                    <input type="text" name="code" class="form-control" value="<?= htmlspecialchars($detail->code ?? '') ?>" placeholder="ex: CANAL-01">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Créé par</label>
                    <select name="created_by_user_id" class="form-select">
                        <option value="">— Non renseigné —</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= (int) $u->id ?>" <?= ($detail && $detail->created_by_user_id == $u->id) ? 'selected' : '' ?>><?= htmlspecialchars($u->last_name . ' ' . $u->first_name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Description</label>
                    <textarea name="description" id="channel_description" class="form-control" rows="5" placeholder="Description du canal"></textarea>
                <?php if (!empty($detail->description)): ?>
                <script type="application/json" id="channel_description_data"><?= json_encode($detail->description, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
                <?php endif; ?>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-admin-primary"><i class="bi bi-check-lg me-1"></i> Enregistrer</button>
                </div>
            </div>
        </form>
    </div>
<?php elseif ($detail): ?>
    <div class="admin-card admin-section-card mb-4">
        <h5 class="card-title"><i class="bi bi-chat-dots"></i> Informations canal</h5>
        <table class="admin-table mb-0">
            <tr><th style="width:180px;">Nom</th><td><?= htmlspecialchars($detail->name) ?></td></tr>
            <tr><th>Code</th><td><?= htmlspecialchars($detail->code ?? '—') ?></td></tr>
            <tr><th>Type</th><td><span class="badge bg-secondary"><?= $channelTypes[$detail->channel_type] ?? $detail->channel_type ?></span></td></tr>
            <tr><th>Organisation</th><td><?= htmlspecialchars($detail->organisation_name ?? '—') ?></td></tr>
            <tr><th>Créé par</th><td><?= htmlspecialchars(trim(($detail->created_by_first_name ?? '') . ' ' . ($detail->created_by_last_name ?? '')) ?: $detail->created_by_email ?? '—') ?></td></tr>
            <tr><th>Description</th><td><?= !empty($detail->description) ? '<div class="channel-description">' . $detail->description . '</div>' : '—' ?></td></tr>
        </table>
        <div class="mt-4 d-flex gap-2">
            <a href="channels.php?action=edit&id=<?= (int) $detail->id ?>" class="btn btn-admin-primary"><i class="bi bi-pencil me-1"></i> Modifier</a>
            <a href="announcements.php" class="btn btn-admin-outline"><i class="bi bi-megaphone me-1"></i> Annonces</a>
            <a href="channels.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Liste</a>
            <button type="button" class="btn btn-outline-danger ms-auto" data-bs-toggle="modal" data-bs-target="#deleteChannelModal"><i class="bi bi-trash me-1"></i> Supprimer</button>
        </div>
    </div>

    <div class="admin-card admin-section-card mb-4">
        <h5 class="card-title mb-3"><i class="bi bi-people"></i> Membres du canal</h5>
        <form method="POST" class="row g-2 align-items-end mb-3">
            <input type="hidden" name="add_member" value="1">
            <div class="col-md-5">
                <label class="form-label small mb-0">Ajouter un membre</label>
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">— Choisir un utilisateur —</option>
                    <?php
                    $memberIds = array_column($members, 'user_id');
                    foreach ($users as $u):
                        if (in_array($u->id, $memberIds)) continue;
                    ?>
                        <option value="<?= (int) $u->id ?>"><?= htmlspecialchars($u->last_name . ' ' . $u->first_name) ?> (<?= htmlspecialchars($u->email) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="role" class="form-select form-select-sm">
                    <option value="member">Membre</option>
                    <option value="admin">Admin</option>
                    <option value="moderator">Modérateur</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-admin-primary btn-sm"><i class="bi bi-plus me-1"></i> Ajouter</button>
            </div>
        </form>
        <?php if (count($members) > 0): ?>
            <div class="table-responsive">
                <table class="admin-table table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Rôle</th>
                            <th>Rejoint le</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $m): ?>
                            <tr>
                                <td><?= htmlspecialchars($m->last_name . ' ' . $m->first_name) ?> <span class="text-muted small">(<?= htmlspecialchars($m->email) ?>)</span></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($m->role ?? 'member') ?></span></td>
                                <td class="text-muted small"><?= $m->joined_at ? date('d/m/Y H:i', strtotime($m->joined_at)) : '—' ?></td>
                                <td class="text-end">
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Retirer ce membre du canal ?');">
                                        <input type="hidden" name="remove_member" value="1">
                                        <input type="hidden" name="user_id" value="<?= (int) $m->user_id ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-person-dash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted mb-0">Aucun membre. Utilisez le formulaire ci-dessus pour en ajouter.</p>
        <?php endif; ?>
    </div>

    <div class="modal fade" id="deleteChannelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Supprimer ce canal ?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">Le canal <strong><?= htmlspecialchars($detail->name) ?></strong>, ses membres et les conversations liées seront supprimés. Cette action est irréversible.</div>
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
            <table class="admin-table table table-hover" id="channelsTable">
                <thead>
                    <tr>
                        <th>Canal</th>
                        <th>Organisation</th>
                        <th>Type</th>
                        <th>Membres</th>
                        <th>Conversations</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($list as $c): ?>
                        <tr>
                            <td><a href="channels.php?id=<?= (int) $c->id ?>"><?= htmlspecialchars($c->name) ?></a></td>
                            <td><?= htmlspecialchars($c->organisation_name ?? '—') ?></td>
                            <td><span class="badge bg-secondary"><?= $channelTypes[$c->channel_type] ?? $c->channel_type ?></span></td>
                            <td><span class="badge bg-light text-dark"><?= (int) ($c->members_count ?? 0) ?></span></td>
                            <td><span class="badge bg-light text-dark"><?= (int) ($c->conversations_count ?? 0) ?></span></td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                        <li><a class="dropdown-item" href="channels.php?id=<?= (int) $c->id ?>"><i class="bi bi-eye me-2"></i> Voir</a></li>
                                        <li><a class="dropdown-item" href="channels.php?action=edit&id=<?= (int) $c->id ?>"><i class="bi bi-pencil me-2"></i> Modifier</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" onsubmit="return confirm('Supprimer ce canal ?');">
                                                <input type="hidden" name="delete_id" value="<?= (int) $c->id ?>">
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
        if (document.getElementById('channelsTable') && typeof $ !== 'undefined' && $.fn.DataTable) {
            $('#channelsTable').DataTable({ language: { url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json" }, order: [[0, "asc"]], pageLength: 10, dom: '<"d-flex justify-content-between align-items-center mb-3"f>t<"d-flex justify-content-between align-items-center mt-3"ip>' });
        }
    });
    </script>
<?php else: ?>
    <div class="admin-card admin-section-card">
        <p class="admin-empty py-4 mb-0"><i class="bi bi-chat-dots"></i> Aucun canal. <a href="channels.php?action=add">Créer un canal</a> pour organiser les conversations et annonces.</p>
    </div>
<?php endif; ?>

<?php if ($isForm): ?>
<style>.channel-description { vertical-align: top; } .channel-description h1, .channel-description h2, .channel-description h3, .channel-description h4 { margin: 1rem 0 0.5rem; font-size: 1rem; font-weight: 600; } .channel-description p { margin: 0.5rem 0; } .channel-description ul, .channel-description ol { margin: 0.5rem 0; padding-left: 1.5rem; } .channel-description img { max-width: 100%; height: auto; }</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof $ !== 'undefined' && $.fn.summernote) {
        $('#channel_description').summernote({
            placeholder: 'Description du canal...',
            tabsize: 2,
            height: 200,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });
        var dataEl = document.getElementById('channel_description_data');
        if (dataEl) {
            try {
                var content = JSON.parse(dataEl.textContent);
                if (content) $('#channel_description').summernote('code', content);
            } catch (e) {}
        }
    }
});
</script>
<?php endif; ?>

<footer class="admin-main-footer mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <a href="announcements.php" class="text-muted text-decoration-none small"><i class="bi bi-megaphone me-1"></i> Annonces</a>
        <span class="small text-muted">&copy; <?= date('Y') ?> Expertise</span>
    </div>
</footer>
<?php require __DIR__ . '/inc/footer.php'; ?>
