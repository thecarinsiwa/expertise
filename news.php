<?php
session_start();
$pageTitle = 'Actualités';
$organisation = null;
$announcements = [];
$totalAnnouncements = 0;
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$baseUrl = ($scriptDir === '/' || $scriptDir === '\\') ? '' : rtrim($scriptDir, '/') . '/';
$perPage = 12;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $perPage;
$searchQ = isset($_GET['q']) ? trim($_GET['q']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';
$allowedSort = ['date_desc' => 1, 'date_asc' => 1, 'title_asc' => 1, 'title_desc' => 1];
if (!isset($allowedSort[$sort])) $sort = 'date_desc';

require_once __DIR__ . '/inc/db.php';

try {
    $stmt = $pdo->query("SELECT id, name, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) {
        $organisation = $row;
        $pageTitle = $row->name . ' — Actualités';
    }

    $whereBase = "a.organisation_id = (SELECT id FROM organisation WHERE is_active = 1 LIMIT 1)
          AND (a.published_at IS NULL OR a.published_at <= NOW())
          AND (a.expires_at IS NULL OR a.expires_at > NOW())";
    $whereSearch = '';
    $paramsCount = [];
    if ($searchQ !== '') {
        $whereSearch = " AND (a.title LIKE ? OR a.content LIKE ?)";
        $like = '%' . $searchQ . '%';
        $paramsCount = [$like, $like];
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM announcement a WHERE $whereBase$whereSearch");
    $stmt->execute($paramsCount);
    $totalAnnouncements = (int) $stmt->fetchColumn();

    $orderBy = "COALESCE(a.published_at, a.created_at) DESC, a.created_at DESC";
    if ($sort === 'date_asc') $orderBy = "COALESCE(a.published_at, a.created_at) ASC, a.created_at ASC";
    elseif ($sort === 'title_asc') $orderBy = "a.title ASC";
    elseif ($sort === 'title_desc') $orderBy = "a.title DESC";

    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.cover_image, a.published_at, a.created_at
        FROM announcement a
        WHERE $whereBase$whereSearch
        ORDER BY $orderBy
        LIMIT ? OFFSET ?
    ");
    $bindIdx = 1;
    foreach ($paramsCount as $p) { $stmt->bindValue($bindIdx++, $p, PDO::PARAM_STR); }
    $stmt->bindValue($bindIdx++, $perPage, PDO::PARAM_INT);
    $stmt->bindValue($bindIdx, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $announcements = $stmt->fetchAll();
} catch (PDOException $e) {
    $pdo = null;
}
$totalPages = $totalAnnouncements > 0 ? (int) ceil($totalAnnouncements / $perPage) : 1;

$newsQueryString = [];
if ($searchQ !== '') $newsQueryString['q'] = $searchQ;
if ($sort !== 'date_desc') $newsQueryString['sort'] = $sort;
$newsQueryPrefix = $baseUrl . 'news.php' . (count($newsQueryString) ? '?' . http_build_query($newsQueryString) . '&' : '?');

require_once __DIR__ . '/inc/asset_url.php';
require __DIR__ . '/inc/head.php';
require __DIR__ . '/inc/header.php';
?>

    <section class="py-5">
        <div class="container">
            <nav aria-label="Fil d'Ariane" class="mb-4">
                <a href="<?= $baseUrl ?>index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Retour à l'accueil</a>
            </nav>

            <h1 class="mb-4">Actualités</h1>

            <form method="get" action="<?= $baseUrl ?>news.php" class="row g-3 mb-4 list-toolbar">
                <div class="col-md-5 col-lg-4">
                    <label for="news-q" class="form-label visually-hidden">Rechercher</label>
                    <input type="search" name="q" id="news-q" class="form-control" value="<?= htmlspecialchars($searchQ) ?>" placeholder="Rechercher dans les actualités…">
                </div>
                <div class="col-md-4 col-lg-3">
                    <label for="news-sort" class="form-label visually-hidden">Trier par</label>
                    <select name="sort" id="news-sort" class="form-select" onchange="this.form.submit()">
                        <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>Plus récentes d'abord</option>
                        <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>Plus anciennes d'abord</option>
                        <option value="title_asc" <?= $sort === 'title_asc' ? 'selected' : '' ?>>Titre A → Z</option>
                        <option value="title_desc" <?= $sort === 'title_desc' ? 'selected' : '' ?>>Titre Z → A</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search me-1"></i> Rechercher</button>
                </div>
                <?php if ($searchQ !== '' || $sort !== 'date_desc'): ?>
                <div class="col-auto">
                    <a href="<?= $baseUrl ?>news.php" class="btn btn-outline-secondary">Réinitialiser</a>
                </div>
                <?php endif; ?>
            </form>

            <?php if ($searchQ !== ''): ?>
            <p class="text-muted mb-3"><?= $totalAnnouncements ?> résultat<?= $totalAnnouncements !== 1 ? 's' : '' ?> pour « <?= htmlspecialchars($searchQ) ?> ».</p>
            <?php endif; ?>

            <?php if (count($announcements) > 0): ?>
                <div class="row g-4">
                    <?php foreach ($announcements as $a):
                        $cardCover = !empty($a->cover_image) ? client_asset_url($baseUrl, $a->cover_image) : '';
                    ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card card-announcement h-100">
                                <?php if ($cardCover): ?>
                                <div class="card-mission-img" style="background-image: url('<?= htmlspecialchars($cardCover) ?>');"></div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <h2 class="card-title h5"><a href="<?= $baseUrl ?>announcement.php?id=<?= (int) $a->id ?>"><?= htmlspecialchars($a->title) ?></a></h2>
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
                                <li class="page-item"><a class="page-link" href="<?= $newsQueryPrefix ?>page=<?= $page - 1 ?>"><i class="bi bi-chevron-left"></i></a></li>
                            <?php endif; ?>
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="<?= $newsQueryPrefix ?>page=<?= $i ?>"><?= $i ?></a></li>
                            <?php endfor; ?>
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item"><a class="page-link" href="<?= $newsQueryPrefix ?>page=<?= $page + 1 ?>"><i class="bi bi-chevron-right"></i></a></li>
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
