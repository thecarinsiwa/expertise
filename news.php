<?php
session_start();
$pageTitle = 'Actualités';
$organisation = null;
$announcements = [];
$totalAnnouncements = 0;
$baseUrl = '';
$perPage = 12;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

require_once __DIR__ . '/inc/db.php';

try {
    $stmt = $pdo->query("SELECT id, name, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) {
        $organisation = $row;
        $pageTitle = $row->name . ' — Actualités';
    }

    $stmt = $pdo->query("
        SELECT COUNT(*) FROM announcement a
        WHERE a.organisation_id = (SELECT id FROM organisation WHERE is_active = 1 LIMIT 1)
          AND (a.published_at IS NULL OR a.published_at <= NOW())
          AND (a.expires_at IS NULL OR a.expires_at > NOW())
    ");
    $totalAnnouncements = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.published_at, a.created_at
        FROM announcement a
        WHERE a.organisation_id = (SELECT id FROM organisation WHERE is_active = 1 LIMIT 1)
          AND (a.published_at IS NULL OR a.published_at <= NOW())
          AND (a.expires_at IS NULL OR a.expires_at > NOW())
        ORDER BY COALESCE(a.published_at, a.created_at) DESC, a.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $announcements = $stmt->fetchAll();
} catch (PDOException $e) {
    $pdo = null;
}
$totalPages = $totalAnnouncements > 0 ? (int) ceil($totalAnnouncements / $perPage) : 1;

require __DIR__ . '/inc/head.php';
require __DIR__ . '/inc/header.php';
?>

    <section class="py-5">
        <div class="container">
            <nav aria-label="Fil d'Ariane" class="mb-4">
                <a href="index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Retour à l'accueil</a>
            </nav>

            <h1 class="mb-4">Actualités</h1>

            <?php if (count($announcements) > 0): ?>
                <div class="row g-4">
                    <?php foreach ($announcements as $a): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card card-announcement h-100">
                                <div class="card-body">
                                    <h2 class="card-title h5"><a href="announcement.php?id=<?= (int) $a->id ?>"><?= htmlspecialchars($a->title) ?></a></h2>
                                    <p class="card-meta mb-0"><?= $a->published_at ? date('d M Y', strtotime($a->published_at)) : ($a->created_at ? date('d M Y', strtotime($a->created_at)) : '') ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Pagination des actualités" class="mt-4 d-flex justify-content-center">
                        <ul class="pagination mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item"><a class="page-link" href="news.php?page=<?= $page - 1 ?>"><i class="bi bi-chevron-left"></i></a></li>
                            <?php endif; ?>
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="news.php?page=<?= $i ?>"><?= $i ?></a></li>
                            <?php endfor; ?>
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item"><a class="page-link" href="news.php?page=<?= $page + 1 ?>"><i class="bi bi-chevron-right"></i></a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-muted">Aucune actualité pour le moment.</p>
            <?php endif; ?>
        </div>
    </section>

<?php require __DIR__ . '/inc/footer.php'; ?>
