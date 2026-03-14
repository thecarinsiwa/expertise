<?php
$pageTitle = 'Conversations – Administration';
$currentNav = 'conversations';
require_once __DIR__ . '/inc/auth.php';
require_permission('admin.conversations.view');
require __DIR__ . '/inc/db.php';

$list = [];
$detail = null;
$messages = [];
$stats = ['total' => 0, 'channel' => 0, 'direct' => 0];
$error = '';
$success = '';
$channels = [];
$users = [];

if ($pdo) {
    try {
        $channels = $pdo->query("SELECT id, name, channel_type FROM channel ORDER BY name")->fetchAll();
        $users = $pdo->query("SELECT id, first_name, last_name, email FROM user WHERE is_active = 1 ORDER BY last_name, first_name")->fetchAll();
    } catch (PDOException $e) {
        $channels = [];
        $users = [];
    }

    $action = $_GET['action'] ?? 'list';
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $channel_filter = isset($_GET['channel_id']) ? (int) $_GET['channel_id'] : null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['delete_conversation_id'])) {
            $delId = (int) $_POST['delete_conversation_id'];
            try {
                $pdo->prepare("DELETE FROM conversation WHERE id = ?")->execute([$delId]);
                header('Location: conversations.php?msg=deleted');
                exit;
            } catch (PDOException $e) {
                $error = 'Impossible de supprimer la conversation.';
            }
        }
        if (isset($_POST['delete_message_id'])) {
            $delMsgId = (int) $_POST['delete_message_id'];
            $convId = (int) ($_POST['conversation_id'] ?? 0);
            try {
                $pdo->prepare("DELETE FROM message WHERE id = ?")->execute([$delMsgId]);
                $success = 'Message supprimé.';
                $id = $convId;
            } catch (PDOException $e) {
                $error = 'Impossible de supprimer le message.';
            }
        }
        if (isset($_POST['add_message']) && $id > 0) {
            $sender_user_id = (int) ($_POST['sender_user_id'] ?? 0);
            $body = trim($_POST['body'] ?? '');
            if ($sender_user_id > 0 && $body !== '') {
                try {
                    $pdo->prepare("INSERT INTO message (conversation_id, sender_user_id, body) VALUES (?, ?, ?)")->execute([$id, $sender_user_id, $body]);
                    $success = 'Message ajouté.';
                } catch (PDOException $e) {
                    $error = 'Erreur : ' . $e->getMessage();
                }
            } else {
                $error = 'Expéditeur et contenu obligatoires.';
            }
        }
    }

    if ($id > 0) {
        $stmt = $pdo->prepare("
            SELECT cv.*, c.name AS channel_name, c.channel_type,
                   u.first_name AS created_by_first_name, u.last_name AS created_by_last_name
            FROM conversation cv
            LEFT JOIN channel c ON cv.channel_id = c.id
            LEFT JOIN user u ON cv.created_by_user_id = u.id
            WHERE cv.id = ?
        ");
        $stmt->execute([$id]);
        $detail = $stmt->fetch();
        if ($detail) {
            $stmtM = $pdo->prepare("
                SELECT m.*, u.first_name AS sender_first_name, u.last_name AS sender_last_name, u.email AS sender_email
                FROM message m
                JOIN user u ON m.sender_user_id = u.id
                WHERE m.conversation_id = ?
                ORDER BY m.created_at ASC
            ");
            $stmtM->execute([$id]);
            $messages = $stmtM->fetchAll();
        }
    }

    if (!$detail || $action === 'list') {
        $stats['total'] = (int) $pdo->query("SELECT COUNT(*) FROM conversation")->fetchColumn();
        $stats['channel'] = (int) $pdo->query("SELECT COUNT(*) FROM conversation WHERE conversation_type = 'channel'")->fetchColumn();
        $stats['direct'] = (int) $pdo->query("SELECT COUNT(*) FROM conversation WHERE conversation_type = 'direct'")->fetchColumn();
        $sql = "
            SELECT cv.id, cv.subject, cv.conversation_type, cv.created_at,
                   c.name AS channel_name,
                   u.first_name AS created_by_first_name, u.last_name AS created_by_last_name,
                   (SELECT COUNT(*) FROM message m WHERE m.conversation_id = cv.id) AS messages_count
            FROM conversation cv
            LEFT JOIN channel c ON cv.channel_id = c.id
            LEFT JOIN user u ON cv.created_by_user_id = u.id
            WHERE 1=1
        ";
        $params = [];
        if ($channel_filter > 0) {
            $sql .= " AND cv.channel_id = ?";
            $params[] = $channel_filter;
        }
        $sql .= " ORDER BY cv.updated_at DESC, cv.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $list = $stmt->fetchAll();
    }
}
require __DIR__ . '/inc/header.php';

$convTypes = ['channel' => 'Canal', 'direct' => 'Direct', 'thread' => 'Fil'];
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Tableau de bord</a></li>
        <li class="breadcrumb-item"><a href="announcements.php">Communication</a></li>
        <li class="breadcrumb-item"><a href="conversations.php">Conversations</a></li>
        <?php if ($detail): ?>
            <li class="breadcrumb-item active"><?= htmlspecialchars($detail->subject ?: 'Conversation #' . $detail->id) ?></li>
        <?php endif; ?>
    </ol>
</nav>

<header class="admin-header">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h1><?= $detail ? ('Conversation' . ($detail->subject ? ': ' . htmlspecialchars($detail->subject) : ' #' . $detail->id)) : 'Conversations' ?></h1>
            <p class="text-muted mb-0"><?= $detail ? 'Messages de la conversation' : 'Liste des conversations (canal, direct, fil).' ?></p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($detail): ?>
                <a href="conversations.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Liste</a>
                <a href="channels.php" class="btn btn-admin-outline"><i class="bi bi-chat-dots me-1"></i> Canaux</a>
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteConvModal"><i class="bi bi-trash me-1"></i> Supprimer</button>
            <?php else: ?>
                <a href="channels.php" class="btn btn-admin-outline"><i class="bi bi-chat-dots me-1"></i> Canaux</a>
                <a href="announcements.php" class="btn btn-admin-outline"><i class="bi bi-megaphone me-1"></i> Annonces</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success">Conversation supprimée.</div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (!$detail): ?>
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="admin-card p-3 d-flex flex-wrap gap-2 align-items-center bg-light border shadow-sm">
                <span class="text-muted small fw-bold text-uppercase me-2"><i class="bi bi-sliders me-1"></i> Configuration :</span>
                <a href="channels.php" class="btn btn-sm btn-admin-outline"><i class="bi bi-chat-dots me-1"></i> Canaux</a>
                <a href="channel_types.php" class="btn btn-sm btn-admin-outline"><i class="bi bi-tag me-1"></i> Types de canal</a>
                <span class="text-muted small fw-bold text-uppercase ms-2 me-2"><i class="bi bi-funnel me-1"></i> Filtre</span>
                <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
                    <select name="channel_id" class="form-select form-select-sm" style="max-width:220px;" onchange="this.form.submit()">
                        <option value="">— Tous les canaux —</option>
                        <?php foreach ($channels as $c): ?>
                            <option value="<?= (int) $c->id ?>" <?= $channel_filter === (int) $c->id ? 'selected' : '' ?>><?= htmlspecialchars($c->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-4">
            <div class="admin-card text-center p-3 h-100">
                <div class="text-muted small text-uppercase mb-1 fw-bold">Total</div>
                <div class="h3 mb-0 fw-bold"><?= $stats['total'] ?></div>
                <div class="mt-2"><span class="badge bg-secondary">Conversations</span></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="admin-card text-center p-3 h-100">
                <div class="text-muted small text-uppercase mb-1 fw-bold">Canal</div>
                <div class="h3 mb-0 fw-bold text-primary"><?= $stats['channel'] ?></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="admin-card text-center p-3 h-100">
                <div class="text-muted small text-uppercase mb-1 fw-bold">Direct</div>
                <div class="h3 mb-0 fw-bold text-muted"><?= $stats['direct'] ?></div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($detail): ?>
    <div class="admin-card admin-section-card mb-4">
        <h5 class="card-title"><i class="bi bi-chat-text"></i> Informations</h5>
        <table class="admin-table mb-0">
            <tr><th style="width:160px;">Sujet</th><td><?= htmlspecialchars($detail->subject ?: '—') ?></td></tr>
            <tr><th>Type</th><td><span class="badge bg-secondary"><?= $convTypes[$detail->conversation_type] ?? $detail->conversation_type ?></span></td></tr>
            <tr><th>Canal</th><td><?= htmlspecialchars($detail->channel_name ?? '—') ?></td></tr>
            <tr><th>Créé par</th><td><?= htmlspecialchars(trim(($detail->created_by_first_name ?? '') . ' ' . ($detail->created_by_last_name ?? '')) ?: '—') ?></td></tr>
            <tr><th>Créée le</th><td><?= $detail->created_at ? date('d/m/Y H:i', strtotime($detail->created_at)) : '—' ?></td></tr>
        </table>
    </div>

    <div class="admin-card admin-section-card mb-4">
        <h5 class="card-title mb-3"><i class="bi bi-chat-quote"></i> Messages (<?= count($messages) ?>)</h5>
        <form method="POST" class="mb-4 p-3 bg-light rounded">
            <input type="hidden" name="add_message" value="1">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-0">Expéditeur</label>
                    <select name="sender_user_id" class="form-select form-select-sm" required>
                        <option value="">— Choisir —</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= (int) $u->id ?>"><?= htmlspecialchars($u->last_name . ' ' . $u->first_name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small mb-0">Message</label>
                    <input type="text" name="body" class="form-control form-control-sm" required placeholder="Contenu du message">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-admin-primary btn-sm w-100"><i class="bi bi-send me-1"></i> Envoyer</button>
                </div>
            </div>
        </form>
        <?php if (count($messages) > 0): ?>
            <div class="list-group list-group-flush">
                <?php foreach ($messages as $m): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <strong><?= htmlspecialchars($m->sender_last_name . ' ' . $m->sender_first_name) ?></strong>
                                <span class="text-muted small"><?= $m->created_at ? date('d/m/Y H:i', strtotime($m->created_at)) : '' ?></span>
                                <?php if ($m->is_edited): ?><span class="badge bg-secondary">modifié</span><?php endif; ?>
                            </div>
                            <div class="text-break"><?= nl2br(htmlspecialchars($m->body)) ?></div>
                        </div>
                        <form method="POST" class="d-inline ms-2" onsubmit="return confirm('Supprimer ce message ?');">
                            <input type="hidden" name="conversation_id" value="<?= (int) $detail->id ?>">
                            <input type="hidden" name="delete_message_id" value="<?= (int) $m->id ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-muted mb-0">Aucun message. Utilisez le formulaire ci-dessus pour en ajouter.</p>
        <?php endif; ?>
    </div>

    <div class="modal fade" id="deleteConvModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Supprimer cette conversation ?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">Tous les messages seront supprimés. Cette action est irréversible.</div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="delete_conversation_id" value="<?= (int) $detail->id ?>">
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php elseif (count($list) > 0): ?>
    <div class="admin-card admin-section-card">
        <div class="table-responsive">
            <table class="admin-table table table-hover" id="conversationsTable">
                <thead>
                    <tr>
                        <th>Id / Sujet</th>
                        <th>Canal</th>
                        <th>Type</th>
                        <th>Créé par</th>
                        <th>Messages</th>
                        <th>Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($list as $cv): ?>
                        <tr>
                            <td><a href="conversations.php?id=<?= (int) $cv->id ?>"><?= htmlspecialchars($cv->subject ?: '#' . $cv->id) ?></a></td>
                            <td><?= htmlspecialchars($cv->channel_name ?? '—') ?></td>
                            <td><span class="badge bg-secondary"><?= $convTypes[$cv->conversation_type] ?? $cv->conversation_type ?></span></td>
                            <td><?= htmlspecialchars(trim(($cv->created_by_first_name ?? '') . ' ' . ($cv->created_by_last_name ?? '')) ?: '—') ?></td>
                            <td><span class="badge bg-light text-dark"><?= (int) ($cv->messages_count ?? 0) ?></span></td>
                            <td class="text-muted small"><?= $cv->created_at ? date('d/m/Y H:i', strtotime($cv->created_at)) : '—' ?></td>
                            <td class="text-end">
                                <a href="conversations.php?id=<?= (int) $cv->id ?>" class="btn btn-sm btn-admin-outline"><i class="bi bi-eye me-1"></i> Voir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <style>.admin-table th{background:#f8f9fa;padding:0.75rem;} .admin-table td{padding:0.75rem;}</style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('conversationsTable') && typeof $ !== 'undefined' && $.fn.DataTable) {
            $('#conversationsTable').DataTable({ language: { url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json" }, order: [[5, "desc"]], pageLength: 15 });
        }
    });
    </script>
<?php else: ?>
    <div class="admin-card admin-section-card">
        <p class="admin-empty py-4 mb-0"><i class="bi bi-chat-text"></i> Aucune conversation. Les conversations sont créées depuis les canaux ou la messagerie.</p>
    </div>
<?php endif; ?>

<footer class="admin-main-footer mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <a href="channels.php" class="text-muted text-decoration-none small"><i class="bi bi-chat-dots me-1"></i> Canaux</a>
        <span class="small text-muted">&copy; <?= date('Y') ?> Expertise</span>
    </div>
</footer>
<?php require __DIR__ . '/inc/footer.php'; ?>
