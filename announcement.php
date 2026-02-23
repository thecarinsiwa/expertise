<?php
session_start();
$pageTitle = 'Actualité';
$organisation = null;
$announcement = null;
$baseUrl = '';

require_once __DIR__ . '/inc/db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

try {
    $stmt = $pdo->query("SELECT id, name, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) {
        $organisation = $row;
        $pageTitle = $row->name;
    }

    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.content, a.published_at, a.created_at, a.is_pinned,
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
?>

    <main class="announcement-detail mission-detail">
        <div class="mission-detail-no-hero">
            <div class="container">
                <nav aria-label="Fil d'Ariane" class="mission-detail-breadcrumb">
                    <a href="index.php"><i class="bi bi-arrow-left"></i> Retour à l'accueil</a>
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

        <div class="mission-detail-body">
            <div class="container">
                <div class="mission-detail-grid">
                    <article class="mission-detail-main">
                        <div class="mission-detail-description announcement-content">
                            <?= client_rewrite_uploads_in_html($baseUrl, $announcement->content ?? '') ?>
                        </div>
                    </article>

                    <aside class="mission-detail-sidebar">
                        <?php if (count($recentAnnouncements) > 0): ?>
                            <div class="mission-detail-block">
                                <h2 class="mission-detail-block-title"><i class="bi bi-megaphone"></i> Autres actualités</h2>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach ($recentAnnouncements as $a): ?>
                                        <li class="mb-2 pb-2 border-bottom border-light">
                                            <a href="announcement.php?id=<?= (int) $a->id ?>" class="text-decoration-none text-dark">
                                                <strong class="d-block"><?= htmlspecialchars($a->title) ?></strong>
                                                <span class="small text-muted"><?= $a->published_at ? date('d/m/Y', strtotime($a->published_at)) : date('d/m/Y', strtotime($a->created_at)) ?></span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <a href="news.php" class="btn btn-view-all btn-sm mt-2">Toutes les actualités</a>
                            </div>
                        <?php endif; ?>
                    </aside>
                </div>
            </div>
        </div>
    </main>

<?php require __DIR__ . '/inc/footer.php'; ?>
