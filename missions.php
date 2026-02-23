<?php
session_start();
$pageTitle = 'Missions';
$organisation = null;
$missions = [];
$totalMissions = 0;
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$baseUrl = ($scriptDir === '/' || $scriptDir === '\\') ? '' : rtrim($scriptDir, '/') . '/';
$perPage = 12;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $perPage;
$locationFilter = isset($_GET['location']) ? trim($_GET['location']) : '';
$searchQ = isset($_GET['q']) ? trim($_GET['q']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';
$allowedSort = ['date_desc' => 1, 'date_asc' => 1, 'title_asc' => 1, 'title_desc' => 1, 'location_asc' => 1, 'location_desc' => 1];
if (!isset($allowedSort[$sort])) $sort = 'date_desc';

require_once __DIR__ . '/inc/db.php';

try {
    $stmt = $pdo->query("SELECT id, name, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) {
        $organisation = $row;
        $pageTitle = $row->name . ' — Missions';
    }

    $whereOrg = "m.organisation_id = (SELECT id FROM organisation WHERE is_active = 1 LIMIT 1)";
    $whereLocation = $locationFilter !== '' ? " AND m.location = ?" : "";
    $whereSearch = '';
    $paramsCount = [];
    if ($locationFilter !== '') $paramsCount[] = $locationFilter;
    if ($searchQ !== '') {
        $whereSearch = " AND (m.title LIKE ? OR m.location LIKE ?)";
        $like = '%' . $searchQ . '%';
        $paramsCount[] = $like;
        $paramsCount[] = $like;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM mission m WHERE $whereOrg$whereLocation$whereSearch");
    $stmt->execute($paramsCount);
    $totalMissions = (int) $stmt->fetchColumn();

    $orderBy = "m.updated_at DESC, m.start_date DESC";
    if ($sort === 'date_asc') $orderBy = "m.updated_at ASC, m.start_date ASC";
    elseif ($sort === 'title_asc') $orderBy = "m.title ASC";
    elseif ($sort === 'title_desc') $orderBy = "m.title DESC";
    elseif ($sort === 'location_asc') $orderBy = "m.location ASC";
    elseif ($sort === 'location_desc') $orderBy = "m.location DESC";

    $stmt = $pdo->prepare("
        SELECT m.id, m.title, m.cover_image, m.location, m.start_date, m.updated_at
        FROM mission m
        WHERE $whereOrg$whereLocation$whereSearch
        ORDER BY $orderBy
        LIMIT ? OFFSET ?
    ");
    $bindIdx = 1;
    foreach ($paramsCount as $p) { $stmt->bindValue($bindIdx++, $p, PDO::PARAM_STR); }
    $stmt->bindValue($bindIdx++, $perPage, PDO::PARAM_INT);
    $stmt->bindValue($bindIdx, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $missions = $stmt->fetchAll();
} catch (PDOException $e) {
    $pdo = null;
}
$totalPages = $totalMissions > 0 ? (int) ceil($totalMissions / $perPage) : 1;

$missionsQueryString = [];
if ($searchQ !== '') $missionsQueryString['q'] = $searchQ;
if ($locationFilter !== '') $missionsQueryString['location'] = $locationFilter;
if ($sort !== 'date_desc') $missionsQueryString['sort'] = $sort;
$missionsQueryPrefix = $baseUrl . 'missions.php' . (count($missionsQueryString) ? '?' . http_build_query($missionsQueryString) . '&' : '?');

require_once __DIR__ . '/inc/asset_url.php';
require __DIR__ . '/inc/head.php';
require __DIR__ . '/inc/header.php';
?>

    <section class="py-5">
        <div class="container">
            <nav aria-label="Fil d'Ariane" class="mb-4">
                <a href="<?= $baseUrl ?>index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Retour à l'accueil</a>
            </nav>

            <h1 class="mb-4">Missions<?= $locationFilter !== '' ? ' — ' . htmlspecialchars($locationFilter) : '' ?></h1>
            <?php if ($locationFilter !== ''): ?>
                <p class="text-muted mb-3"><a href="<?= $baseUrl ?>missions.php" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Toutes les missions</a></p>
            <?php endif; ?>

            <form method="get" action="<?= $baseUrl ?>missions.php" class="row g-3 mb-4 list-toolbar">
                <?php if ($locationFilter !== ''): ?>
                <input type="hidden" name="location" value="<?= htmlspecialchars($locationFilter) ?>">
                <?php endif; ?>
                <div class="col-md-5 col-lg-4">
                    <label for="missions-q" class="form-label visually-hidden">Rechercher</label>
                    <input type="search" name="q" id="missions-q" class="form-control" value="<?= htmlspecialchars($searchQ) ?>" placeholder="Rechercher (titre, lieu)…">
                </div>
                <div class="col-md-4 col-lg-3">
                    <label for="missions-sort" class="form-label visually-hidden">Trier par</label>
                    <select name="sort" id="missions-sort" class="form-select" onchange="this.form.submit()">
                        <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>Plus récentes d'abord</option>
                        <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>Plus anciennes d'abord</option>
                        <option value="title_asc" <?= $sort === 'title_asc' ? 'selected' : '' ?>>Titre A → Z</option>
                        <option value="title_desc" <?= $sort === 'title_desc' ? 'selected' : '' ?>>Titre Z → A</option>
                        <option value="location_asc" <?= $sort === 'location_asc' ? 'selected' : '' ?>>Lieu A → Z</option>
                        <option value="location_desc" <?= $sort === 'location_desc' ? 'selected' : '' ?>>Lieu Z → A</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search me-1"></i> Rechercher</button>
                </div>
                <?php if ($searchQ !== '' || $sort !== 'date_desc'): ?>
                <div class="col-auto">
                    <a href="<?= $baseUrl ?>missions.php<?= $locationFilter !== '' ? '?location=' . urlencode($locationFilter) : '' ?>" class="btn btn-outline-secondary">Réinitialiser filtres</a>
                </div>
                <?php endif; ?>
            </form>

            <?php if ($searchQ !== ''): ?>
            <p class="text-muted mb-3"><?= $totalMissions ?> résultat<?= $totalMissions !== 1 ? 's' : '' ?> pour « <?= htmlspecialchars($searchQ) ?> ».</p>
            <?php endif; ?>

            <?php if (count($missions) > 0): ?>
                <div class="row g-4">
                    <?php foreach ($missions as $m):
                        $cardCover = !empty($m->cover_image) ? client_asset_url($baseUrl, $m->cover_image) : '';
                    ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card card-mission h-100">
                                <?php if ($cardCover): ?>
                                <div class="card-mission-img" style="background-image: url('<?= htmlspecialchars($cardCover) ?>');"></div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <h2 class="card-title h5"><a href="<?= $baseUrl ?>mission.php?id=<?= (int) $m->id ?>"><?= htmlspecialchars($m->title) ?></a></h2>
                                    <p class="card-meta mb-1"><?= htmlspecialchars($m->location ?: '—') ?></p>
                                    <p class="card-meta mb-0"><?= $m->updated_at ? date('d M Y', strtotime($m->updated_at)) : ($m->start_date ? date('d M Y', strtotime($m->start_date)) : '') ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Pagination des missions" class="mt-4 d-flex justify-content-center">
                        <ul class="pagination mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item"><a class="page-link" href="<?= $missionsQueryPrefix ?>page=<?= $page - 1 ?>"><i class="bi bi-chevron-left"></i></a></li>
                            <?php endif; ?>
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="<?= $missionsQueryPrefix ?>page=<?= $i ?>"><?= $i ?></a></li>
                            <?php endfor; ?>
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item"><a class="page-link" href="<?= $missionsQueryPrefix ?>page=<?= $page + 1 ?>"><i class="bi bi-chevron-right"></i></a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-muted">Aucune mission pour le moment.</p>
            <?php endif; ?>
        </div>
    </section>

<?php require __DIR__ . '/inc/footer.php'; ?>
