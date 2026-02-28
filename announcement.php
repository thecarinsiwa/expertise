<?php
session_start();
$pageTitle = 'Actualité';
$organisation = null;
$announcement = null;
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$baseUrl = ($scriptDir === '/' || $scriptDir === '\\') ? '' : rtrim($scriptDir, '/') . '/';

require_once __DIR__ . '/inc/db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$currentUserId = 0;
if (!empty($_SESSION['client_id'])) {
    $currentUserId = (int) $_SESSION['client_id'];
} elseif (!empty($_SESSION['admin_id'])) {
    $currentUserId = (int) $_SESSION['admin_id'];
}
$comments = [];
$comment_replies = [];
$reactionCounts = [];
$myReactionType = null;
$commentSuccess = false;
$reactionSuccess = false;

try {
    $stmt = $pdo->query("SELECT id, name, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) {
        $organisation = $row;
        $pageTitle = $row->name;
    }

    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.content, a.cover_image, a.published_at, a.created_at, a.is_pinned,
               u.first_name, u.last_name
        FROM announcement a
        INNER JOIN user u ON a.author_user_id = u.id
        WHERE a.id = ? AND a.organisation_id = (SELECT id FROM organisation WHERE is_active = 1 LIMIT 1)
          AND (a.published_at IS NULL OR a.published_at <= NOW())
          AND (a.expires_at IS NULL OR a.expires_at > NOW())
    ");
    $stmt->execute([$id]);
    $announcement = $stmt->fetch();

    if (!$announcement) {
        header('Location: index.php');
        exit;
    }

    $pageTitle = $announcement->title . ' — ' . ($organisation ? $organisation->name : 'Expertise');

    // Traitement POST : commentaire ou réponse
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $currentUserId > 0) {
        if (isset($_POST['add_comment'])) {
            $body = trim((string) ($_POST['comment_body'] ?? ''));
            $parent_comment_id = isset($_POST['parent_comment_id']) && $_POST['parent_comment_id'] !== '' ? (int) $_POST['parent_comment_id'] : null;
            if ($body !== '' && (int) ($_POST['announcement_id'] ?? 0) === $id) {
                if ($parent_comment_id > 0) {
                    $check = $pdo->prepare("SELECT id FROM comment WHERE id = ? AND commentable_type = 'announcement' AND commentable_id = ?");
                    $check->execute([$parent_comment_id, $id]);
                    if ($check->fetch()) {
                        $pdo->prepare("INSERT INTO comment (user_id, commentable_type, commentable_id, body, parent_comment_id) VALUES (?, 'announcement', ?, ?, ?)")
                            ->execute([$currentUserId, $id, $body, $parent_comment_id]);
                        header('Location: ' . $baseUrl . 'announcement.php?id=' . $id . '&comment=1');
                        exit;
                    }
                } else {
                    $pdo->prepare("INSERT INTO comment (user_id, commentable_type, commentable_id, body) VALUES (?, 'announcement', ?, ?)")
                        ->execute([$currentUserId, $id, $body]);
                    header('Location: ' . $baseUrl . 'announcement.php?id=' . $id . '&comment=1');
                    exit;
                }
            }
        }
        if (isset($_POST['set_reaction']) && (int) ($_POST['announcement_id'] ?? 0) === $id) {
            $reactionType = trim((string) ($_POST['reaction_type'] ?? ''));
            $allowed = ['like', 'love', 'celebrate'];
            if (in_array($reactionType, $allowed, true)) {
                $stmt = $pdo->prepare("SELECT reaction_type FROM announcement_reaction WHERE announcement_id = ? AND user_id = ?");
                $stmt->execute([$id, $currentUserId]);
                $existing = $stmt->fetch();
                if ($existing && $existing->reaction_type === $reactionType) {
                    $pdo->prepare("DELETE FROM announcement_reaction WHERE announcement_id = ? AND user_id = ?")->execute([$id, $currentUserId]);
                } else {
                    $pdo->prepare("INSERT INTO announcement_reaction (announcement_id, user_id, reaction_type) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE reaction_type = VALUES(reaction_type)")
                        ->execute([$id, $currentUserId, $reactionType]);
                }
                header('Location: ' . $baseUrl . 'announcement.php?id=' . $id . '&reaction=1');
                exit;
            }
        }
    }

    // Commentaires (commentable_type = 'announcement') – racines uniquement
    $stmt = $pdo->prepare("
        SELECT c.id, c.body, c.created_at, u.first_name, u.last_name
        FROM comment c
        INNER JOIN user u ON c.user_id = u.id
        WHERE c.commentable_type = 'announcement' AND c.commentable_id = ? AND c.parent_comment_id IS NULL
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$id]);
    $comments = $stmt->fetchAll(PDO::FETCH_OBJ);

    // Réponses aux commentaires
    $stmt = $pdo->prepare("
        SELECT c.id, c.parent_comment_id, c.body, c.created_at, u.first_name, u.last_name
        FROM comment c
        INNER JOIN user u ON c.user_id = u.id
        WHERE c.commentable_type = 'announcement' AND c.commentable_id = ? AND c.parent_comment_id IS NOT NULL
        ORDER BY c.parent_comment_id, c.created_at ASC
    ");
    $stmt->execute([$id]);
    $replies_raw = $stmt->fetchAll(PDO::FETCH_OBJ);
    $comment_replies = [];
    foreach ($replies_raw as $r) {
        $pid = (int) $r->parent_comment_id;
        if (!isset($comment_replies[$pid])) $comment_replies[$pid] = [];
        $comment_replies[$pid][] = $r;
    }
    $total_comments_count = count($comments) + count($replies_raw);

    // Réactions : totaux par type
    $stmt = $pdo->prepare("SELECT reaction_type, COUNT(*) AS cnt FROM announcement_reaction WHERE announcement_id = ? GROUP BY reaction_type");
    $stmt->execute([$id]);
    while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
        $reactionCounts[$row->reaction_type] = (int) $row->cnt;
    }
    if ($currentUserId > 0) {
        $stmt = $pdo->prepare("SELECT reaction_type FROM announcement_reaction WHERE announcement_id = ? AND user_id = ?");
        $stmt->execute([$id, $currentUserId]);
        $r = $stmt->fetch(PDO::FETCH_OBJ);
        if ($r) $myReactionType = $r->reaction_type;
    }

    $recentAnnouncements = [];
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.published_at, a.created_at
        FROM announcement a
        WHERE a.organisation_id = (SELECT id FROM organisation WHERE is_active = 1 LIMIT 1)
          AND a.id != ?
          AND (a.published_at IS NULL OR a.published_at <= NOW())
          AND (a.expires_at IS NULL OR a.expires_at > NOW())
        ORDER BY COALESCE(a.published_at, a.created_at) DESC
        LIMIT 5
    ");
    $stmt->execute([$id]);
    $recentAnnouncements = $stmt->fetchAll();
} catch (PDOException $e) {
    $pdo = null;
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/inc/asset_url.php';
require __DIR__ . '/inc/head.php';
require __DIR__ . '/inc/header.php';
$hasCover = !empty($announcement->cover_image);
$coverUrl = $hasCover ? client_asset_url($baseUrl, $announcement->cover_image) : '';
$commentSuccess = isset($_GET['comment']) && $_GET['comment'] === '1';
$reactionSuccess = isset($_GET['reaction']) && $_GET['reaction'] === '1';
?>

    <main class="announcement-detail mission-detail">
        <?php if ($hasCover): ?>
        <header class="mission-detail-hero">
            <div class="mission-detail-hero-bg" style="background-image: url('<?= htmlspecialchars($coverUrl) ?>');"></div>
            <div class="mission-detail-hero-overlay"></div>
            <div class="container mission-detail-hero-content">
                <nav aria-label="Fil d'Ariane" class="mission-detail-breadcrumb">
                    <a href="<?= $baseUrl ?>index.php"><i class="bi bi-arrow-left"></i> Retour à l'accueil</a>
                </nav>
                <span class="mission-detail-badge">Annonce</span>
                <h1 class="mission-detail-title"><?= htmlspecialchars($announcement->title) ?></h1>
                <p class="mission-detail-dates">
                    <?php if ($announcement->published_at): ?>
                        <?= date('d M Y', strtotime($announcement->published_at)) ?>
                    <?php else: ?>
                        <?= date('d M Y', strtotime($announcement->created_at)) ?>
                    <?php endif; ?>
                    <?php if (!empty($announcement->first_name) || !empty($announcement->last_name)): ?>
                        <span class="mission-detail-updated"> · <?= htmlspecialchars(trim($announcement->first_name . ' ' . $announcement->last_name)) ?></span>
                    <?php endif; ?>
                </p>
            </div>
        </header>
        <?php else: ?>
        <div class="mission-detail-no-hero">
            <div class="container">
                <nav aria-label="Fil d'Ariane" class="mission-detail-breadcrumb">
                    <a href="<?= $baseUrl ?>index.php"><i class="bi bi-arrow-left"></i> Retour à l'accueil</a>
                </nav>
                <span class="mission-detail-badge mission-detail-badge--dark">Annonce</span>
                <h1 class="mission-detail-title mission-detail-title--dark"><?= htmlspecialchars($announcement->title) ?></h1>
                <p class="mission-detail-dates mission-detail-dates--dark">
                    <?php if ($announcement->published_at): ?>
                        <?= date('d M Y', strtotime($announcement->published_at)) ?>
                    <?php else: ?>
                        <?= date('d M Y', strtotime($announcement->created_at)) ?>
                    <?php endif; ?>
                    <?php if (!empty($announcement->first_name) || !empty($announcement->last_name)): ?>
                        <span class="mission-detail-updated"> · <?= htmlspecialchars(trim($announcement->first_name . ' ' . $announcement->last_name)) ?></span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <?php endif; ?>

        <div class="mission-detail-body">
            <div class="container">
                <div class="mission-detail-grid">
                    <article class="mission-detail-main">
                        <div class="mission-detail-description announcement-content">
                            <?= client_rewrite_uploads_in_html($baseUrl, $announcement->content ?? '') ?>
                        </div>

                        <!-- Réactions -->
                        <div class="announcement-actions mt-4 pt-4 border-top">
                            <span class="announcement-actions-label me-2">Réagir :</span>
                            <?php if ($currentUserId > 0): ?>
                                <form method="post" class="d-inline-block announcement-reactions-form" action="<?= $baseUrl ?>announcement.php?id=<?= $id ?>">
                                    <input type="hidden" name="announcement_id" value="<?= $id ?>">
                                    <input type="hidden" name="set_reaction" value="1">
                                    <?php
                                    $reactionButtons = [
                                        'like' => ['label' => 'J\'aime', 'icon' => 'bi-hand-thumbs-up'],
                                        'love' => ['label' => 'J\'adore', 'icon' => 'bi-heart'],
                                        'celebrate' => ['label' => 'Bravo', 'icon' => 'bi-emoji-smile'],
                                    ];
                                    foreach ($reactionButtons as $type => $conf):
                                        $count = $reactionCounts[$type] ?? 0;
                                        $active = $myReactionType === $type;
                                    ?>
                                    <button type="submit" name="reaction_type" value="<?= htmlspecialchars($type) ?>" class="btn btn-sm announcement-reaction-btn <?= $active ? 'active' : '' ?>" title="<?= htmlspecialchars($conf['label']) ?>">
                                        <i class="bi <?= $conf['icon'] ?>"></i>
                                        <?php if ($count > 0): ?><span class="announcement-reaction-count"><?= $count ?></span><?php endif; ?>
                                    </button>
                                    <?php endforeach; ?>
                                </form>
                            <?php else: ?>
                                <p class="text-muted small mb-0">Connectez-vous pour réagir à cette actualité.</p>
                                <a href="<?= $baseUrl ?>client/login.php?redirect=<?= urlencode($baseUrl . 'announcement.php?id=' . $id) ?>" class="btn btn-sm btn-outline-primary mt-1">Se connecter</a>
                            <?php endif; ?>
                        </div>

                        <!-- Commentaires -->
                        <div class="announcement-comments mt-4 pt-4 border-top">
                            <h2 class="h5 mb-3"><i class="bi bi-chat-text me-2"></i>Commentaires <?php if (isset($total_comments_count) && $total_comments_count > 0): ?><span class="badge bg-secondary"><?= $total_comments_count ?></span><?php endif; ?></h2>
                            <?php if ($commentSuccess): ?>
                                <div class="alert alert-success py-2 small">Votre commentaire a été publié.</div>
                            <?php endif; ?>
                            <?php if ($currentUserId > 0): ?>
                                <form method="post" action="<?= $baseUrl ?>announcement.php?id=<?= $id ?>" class="mb-4">
                                    <input type="hidden" name="announcement_id" value="<?= $id ?>">
                                    <input type="hidden" name="add_comment" value="1">
                                    <label for="comment_body" class="form-label small">Ajouter un commentaire</label>
                                    <textarea name="comment_body" id="comment_body" class="form-control" rows="3" placeholder="Votre message..." required maxlength="2000"></textarea>
                                    <button type="submit" class="btn btn-read-more btn-sm mt-2">Publier</button>
                                </form>
                            <?php else: ?>
                                <p class="text-muted small">Connectez-vous pour laisser un commentaire.</p>
                                <a href="<?= $baseUrl ?>client/login.php?redirect=<?= urlencode($baseUrl . 'announcement.php?id=' . $id) ?>" class="btn btn-sm btn-outline-primary">Se connecter</a>
                            <?php endif; ?>
                            <?php if (count($comments) > 0): ?>
                                <ul class="list-unstyled announcement-comment-list">
                                    <?php foreach ($comments as $c):
                                        $replies = $comment_replies[(int) $c->id] ?? [];
                                    ?>
                                        <li class="announcement-comment-item mb-3 pb-3 border-bottom">
                                            <p class="mb-1 small text-muted">
                                                <strong><?= htmlspecialchars(trim($c->first_name . ' ' . $c->last_name)) ?></strong>
                                                · <?= date('d/m/Y à H:i', strtotime($c->created_at)) ?>
                                            </p>
                                            <p class="mb-0"><?= nl2br(htmlspecialchars($c->body)) ?></p>
                                            <?php if (count($replies) > 0): ?>
                                                <ul class="list-unstyled mt-2 ms-3 ps-3 border-start border-2 border-light">
                                                    <?php foreach ($replies as $reply): ?>
                                                        <li class="mb-2 py-2">
                                                            <p class="mb-1 small text-muted">
                                                                <strong><?= htmlspecialchars(trim($reply->first_name . ' ' . $reply->last_name)) ?></strong>
                                                                · <?= date('d/m/Y à H:i', strtotime($reply->created_at)) ?>
                                                            </p>
                                                            <p class="mb-0 small"><?= nl2br(htmlspecialchars($reply->body)) ?></p>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                            <?php if ($currentUserId > 0): ?>
                                                <div class="mt-2 pt-2">
                                                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none announcement-reply-toggle" data-target="reply-form-<?= (int) $c->id ?>" aria-expanded="false">
                                                        <i class="bi bi-reply me-1"></i> Répondre
                                                    </button>
                                                    <form method="post" action="<?= $baseUrl ?>announcement.php?id=<?= $id ?>" class="announcement-reply-form mt-2" id="reply-form-<?= (int) $c->id ?>" style="display:none;">
                                                        <input type="hidden" name="announcement_id" value="<?= $id ?>">
                                                        <input type="hidden" name="add_comment" value="1">
                                                        <input type="hidden" name="parent_comment_id" value="<?= (int) $c->id ?>">
                                                        <textarea name="comment_body" class="form-control form-control-sm" rows="2" placeholder="Votre réponse..." required maxlength="2000"></textarea>
                                                        <button type="submit" class="btn btn-read-more btn-sm mt-1">Envoyer la réponse</button>
                                                    </form>
                                                </div>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted small mb-0">Aucun commentaire pour le moment.</p>
                            <?php endif; ?>
                        </div>
                    </article>

                    <aside class="mission-detail-sidebar">
                        <?php if (count($recentAnnouncements) > 0): ?>
                            <div class="mission-detail-block">
                                <h2 class="mission-detail-block-title"><i class="bi bi-megaphone"></i> Autres actualités</h2>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach ($recentAnnouncements as $a): ?>
                                        <li class="mb-2 pb-2 border-bottom border-light">
                                            <a href="<?= $baseUrl ?>announcement.php?id=<?= (int) $a->id ?>" class="text-decoration-none text-dark">
                                                <strong class="d-block"><?= htmlspecialchars($a->title) ?></strong>
                                                <span class="small text-muted"><?= $a->published_at ? date('d/m/Y', strtotime($a->published_at)) : date('d/m/Y', strtotime($a->created_at)) ?></span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <a href="<?= $baseUrl ?>news.php" class="btn btn-view-all btn-sm mt-2">Toutes les actualités</a>
                            </div>
                        <?php endif; ?>
                    </aside>
                </div>
            </div>
        </div>
    </main>

<?php require __DIR__ . '/inc/footer.php'; ?>

<script>
document.querySelectorAll('.announcement-reply-toggle').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = this.getAttribute('data-target');
        var form = id ? document.getElementById(id) : null;
        if (form) {
            var isHidden = form.style.display === 'none';
            form.style.display = isHidden ? 'block' : 'none';
            this.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
            if (isHidden && form.querySelector('textarea')) form.querySelector('textarea').focus();
        }
    });
});
</script>
