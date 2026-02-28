<?php
session_start();
$pageTitle = 'Nos offres';
$organisation = null;
$offers = [];
$totalOffers = 0;
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$baseUrl = ($scriptDir === '/' || $scriptDir === '\\') ? '' : rtrim($scriptDir, '/') . '/';
$perPage = 12;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $perPage;
$searchQ = isset($_GET['q']) ? trim($_GET['q']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';
$allowedSort = ['date_desc' => 1, 'date_asc' => 1, 'title_asc' => 1, 'title_desc' => 1, 'deadline_asc' => 1, 'deadline_desc' => 1];
if (!isset($allowedSort[$sort])) $sort = 'date_desc';

require_once __DIR__ . '/inc/db.php';

try {
    $stmt = $pdo->query("SELECT id, name, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) {
        $organisation = $row;
        $pageTitle = $row->name . ' — Nos offres';
    }

    $whereOrg = "o.organisation_id = (SELECT id FROM organisation WHERE is_active = 1 LIMIT 1) AND o.status = 'published'";
    $whereSearch = '';
    $paramsCount = [];
    if ($searchQ !== '') {
        $whereSearch = " AND (o.title LIKE ? OR o.reference LIKE ?)";
        $like = '%' . $searchQ . '%';
        $paramsCount[] = $like;
        $paramsCount[] = $like;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM offer o WHERE $whereOrg$whereSearch");
    $stmt->execute($paramsCount);
    $totalOffers = (int) $stmt->fetchColumn();

    $orderBy = "o.updated_at DESC, o.published_at DESC";
    if ($sort === 'date_asc') $orderBy = "o.updated_at ASC, o.published_at ASC";
    elseif ($sort === 'title_asc') $orderBy = "o.title ASC";
    elseif ($sort === 'title_desc') $orderBy = "o.title DESC";
    elseif ($sort === 'deadline_asc') $orderBy = "o.deadline_at ASC";
    elseif ($sort === 'deadline_desc') $orderBy = "o.deadline_at DESC";

    $stmt = $pdo->prepare("
        SELECT o.id, o.title, o.reference, o.cover_image, o.deadline_at, o.updated_at, o.published_at,
               o.mission_id, o.project_id, m.title AS mission_title, p.name AS project_name
        FROM offer o
        LEFT JOIN mission m ON o.mission_id = m.id
        LEFT JOIN project p ON o.project_id = p.id
        WHERE $whereOrg$whereSearch
        ORDER BY $orderBy
        LIMIT ? OFFSET ?
    ");
    $bindIdx = 1;
    foreach ($paramsCount as $p) { $stmt->bindValue($bindIdx++, $p, PDO::PARAM_STR); }
    $stmt->bindValue($bindIdx++, $perPage, PDO::PARAM_INT);
    $stmt->bindValue($bindIdx, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $offers = $stmt->fetchAll();
} catch (PDOException $e) {
    $pdo = null;
}
$totalPages = $totalOffers > 0 ? (int) ceil($totalOffers / $perPage) : 1;

$offersQueryString = [];
if ($searchQ !== '') $offersQueryString['q'] = $searchQ;
if ($sort !== 'date_desc') $offersQueryString['sort'] = $sort;
$offersQueryPrefix = $baseUrl . 'offres.php' . (count($offersQueryString) ? '?' . http_build_query($offersQueryString) . '&' : '?');

require_once __DIR__ . '/inc/asset_url.php';
require __DIR__ . '/inc/head.php';
require __DIR__ . '/inc/header.php';
?>

    <section class="py-5">
        <div class="container">
            <nav aria-label="Fil d'Ariane" class="mb-4">
                <a href="<?= $baseUrl ?>index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Retour à l'accueil</a>
            </nav>

            <h1 class="mb-4">Nos offres</h1>

            <form method="get" action="<?= $baseUrl ?>offres.php" class="row g-3 mb-4 list-toolbar">
                <div class="col-md-5 col-lg-4">
                    <label for="offres-q" class="form-label visually-hidden">Rechercher</label>
                    <input type="search" name="q" id="offres-q" class="form-control" value="<?= htmlspecialchars($searchQ) ?>" placeholder="Rechercher (titre, référence)…">
                </div>
                <div class="col-md-4 col-lg-3">
                    <label for="offres-sort" class="form-label visually-hidden">Trier par</label>
                    <select name="sort" id="offres-sort" class="form-select" onchange="this.form.submit()">
                        <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>Plus récentes d'abord</option>
                        <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>Plus anciennes d'abord</option>
                        <option value="title_asc" <?= $sort === 'title_asc' ? 'selected' : '' ?>>Titre A → Z</option>
                        <option value="title_desc" <?= $sort === 'title_desc' ? 'selected' : '' ?>>Titre Z → A</option>
                        <option value="deadline_asc" <?= $sort === 'deadline_asc' ? 'selected' : '' ?>>Date limite proche</option>
                        <option value="deadline_desc" <?= $sort === 'deadline_desc' ? 'selected' : '' ?>>Date limite lointaine</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search me-1"></i> Rechercher</button>
                </div>
                <?php if ($searchQ !== '' || $sort !== 'date_desc'): ?>
                <div class="col-auto">
                    <a href="<?= $baseUrl ?>offres.php" class="btn btn-outline-secondary">Réinitialiser filtres</a>
                </div>
                <?php endif; ?>
            </form>

            <?php if ($searchQ !== ''): ?>
            <p class="text-muted mb-3"><?= $totalOffers ?> résultat<?= $totalOffers !== 1 ? 's' : '' ?> pour « <?= htmlspecialchars($searchQ) ?> ».</p>
            <?php endif; ?>

            <?php if (count($offers) > 0): ?>
                <div class="row g-4">
                    <?php foreach ($offers as $o):
                        $cardCover = !empty($o->cover_image) ? client_asset_url($baseUrl, $o->cover_image) : '';
                        $linkLabel = '';
                        if (!empty($o->mission_id)) $linkLabel = $o->mission_title ?? 'Mission';
                        elseif (!empty($o->project_id)) $linkLabel = $o->project_name ?? 'Projet';
                        else $linkLabel = 'Offre';
                    ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card card-mission h-100">
                                <?php if ($cardCover): ?>
                                <div class="card-mission-img" style="background-image: url('<?= htmlspecialchars($cardCover) ?>');"></div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <h2 class="card-title h5"><a href="<?= $baseUrl ?>offre.php?id=<?= (int) $o->id ?>"><?= htmlspecialchars($o->title) ?></a></h2>
                                    <p class="card-meta mb-1"><?= htmlspecialchars($linkLabel) ?></p>
                                    <p class="card-meta mb-0">
                                        <?php if ($o->deadline_at): ?>Date limite : <?= date('d/m/Y', strtotime($o->deadline_at)) ?><?php else: ?><?= $o->updated_at ? date('d M Y', strtotime($o->updated_at)) : '' ?><?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Pagination des offres" class="mt-4 d-flex justify-content-center">
                        <ul class="pagination mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item"><a class="page-link" href="<?= $offersQueryPrefix ?>page=<?= $page - 1 ?>"><i class="bi bi-chevron-left"></i></a></li>
                            <?php endif; ?>
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="<?= $offersQueryPrefix ?>page=<?= $i ?>"><?= $i ?></a></li>
                            <?php endfor; ?>
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item"><a class="page-link" href="<?= $offersQueryPrefix ?>page=<?= $page + 1 ?>"><i class="bi bi-chevron-right"></i></a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-muted">Aucune offre pour le moment.</p>
            <?php endif; ?>
        </div>
    </section>

<?php require __DIR__ . '/inc/footer.php'; ?>
