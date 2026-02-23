<?php
session_start();
$pageTitle = 'Projets';
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$baseUrl = ($scriptDir === '/' || $scriptDir === '\\') ? '' : rtrim($scriptDir, '/') . '/';
$projects = [];
$totalProjects = 0;
$perPage = 12;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $perPage;
$searchQ = isset($_GET['q']) ? trim($_GET['q']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';
$allowedSort = ['date_desc' => 1, 'date_asc' => 1, 'name_asc' => 1, 'name_desc' => 1, 'code_asc' => 1, 'code_desc' => 1, 'status_asc' => 1, 'status_desc' => 1];
if (!isset($allowedSort[$sort])) $sort = 'date_desc';

require_once __DIR__ . '/inc/db.php';
$organisation = null;
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT id, name, description FROM organisation WHERE is_active = 1 LIMIT 1");
        if ($row = $stmt->fetch()) {
            $organisation = $row;
            $pageTitle = 'Projets — ' . $organisation->name;
        }

        $whereBase = "p.organisation_id = (SELECT id FROM organisation WHERE is_active = 1 LIMIT 1)";
        $whereSearch = '';
        $paramsCount = [];
        if ($searchQ !== '') {
            $whereSearch = " AND (p.name LIKE ? OR p.code LIKE ? OR p.description LIKE ? OR p.status LIKE ?)";
            $like = '%' . $searchQ . '%';
            $paramsCount = [$like, $like, $like, $like];
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM project p WHERE $whereBase$whereSearch");
        $stmt->execute($paramsCount);
        $totalProjects = (int) $stmt->fetchColumn();

        $orderBy = "p.start_date DESC, p.updated_at DESC";
        if ($sort === 'date_asc') $orderBy = "p.start_date ASC, p.updated_at ASC";
        elseif ($sort === 'name_asc') $orderBy = "p.name ASC";
        elseif ($sort === 'name_desc') $orderBy = "p.name DESC";
        elseif ($sort === 'code_asc') $orderBy = "p.code ASC";
        elseif ($sort === 'code_desc') $orderBy = "p.code DESC";
        elseif ($sort === 'status_asc') $orderBy = "p.status ASC";
        elseif ($sort === 'status_desc') $orderBy = "p.status DESC";

        $stmt = $pdo->prepare("
            SELECT p.id, p.name, p.code, p.description, p.cover_image, p.start_date, p.end_date, p.status
            FROM project p
            WHERE $whereBase$whereSearch
            ORDER BY $orderBy
            LIMIT ? OFFSET ?
        ");
        $bindIdx = 1;
        foreach ($paramsCount as $p) { $stmt->bindValue($bindIdx++, $p, PDO::PARAM_STR); }
        $stmt->bindValue($bindIdx++, $perPage, PDO::PARAM_INT);
        $stmt->bindValue($bindIdx, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $projects = $stmt->fetchAll();
    } catch (PDOException $e) {
        $pdo = null;
    }
}
$totalPages = $totalProjects > 0 ? (int) ceil($totalProjects / $perPage) : 1;

$projectsQueryString = [];
if ($searchQ !== '') $projectsQueryString['q'] = $searchQ;
if ($sort !== 'date_desc') $projectsQueryString['sort'] = $sort;
$projectsQueryPrefix = $baseUrl . 'projects.php' . (count($projectsQueryString) ? '?' . http_build_query($projectsQueryString) . '&' : '?');

require_once __DIR__ . '/inc/asset_url.php';
require_once __DIR__ . '/inc/page-static.php';
?>
    <section class="py-5 page-content">
        <div class="container">
            <nav aria-label="Fil d'Ariane" class="mb-4">
                <a href="<?= $baseUrl ?>index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Accueil</a>
            </nav>
            <h1 class="section-heading mb-4">Projets</h1>
            <p class="lead text-muted mb-4">Portfolios et programmes, suivi des projets et livrables.</p>

            <form method="get" action="<?= $baseUrl ?>projects.php" class="row g-3 mb-4 list-toolbar">
                <div class="col-md-5 col-lg-4">
                    <label for="projects-q" class="form-label visually-hidden">Rechercher</label>
                    <input type="search" name="q" id="projects-q" class="form-control" value="<?= htmlspecialchars($searchQ) ?>" placeholder="Rechercher (nom, code, statut)…">
                </div>
                <div class="col-md-4 col-lg-3">
                    <label for="projects-sort" class="form-label visually-hidden">Trier par</label>
                    <select name="sort" id="projects-sort" class="form-select" onchange="this.form.submit()">
                        <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>Plus récents d'abord</option>
                        <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>Plus anciens d'abord</option>
                        <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Nom A → Z</option>
                        <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Nom Z → A</option>
                        <option value="code_asc" <?= $sort === 'code_asc' ? 'selected' : '' ?>>Code A → Z</option>
                        <option value="code_desc" <?= $sort === 'code_desc' ? 'selected' : '' ?>>Code Z → A</option>
                        <option value="status_asc" <?= $sort === 'status_asc' ? 'selected' : '' ?>>Statut A → Z</option>
                        <option value="status_desc" <?= $sort === 'status_desc' ? 'selected' : '' ?>>Statut Z → A</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search me-1"></i> Rechercher</button>
                </div>
                <?php if ($searchQ !== '' || $sort !== 'date_desc'): ?>
                <div class="col-auto">
                    <a href="<?= $baseUrl ?>projects.php" class="btn btn-outline-secondary">Réinitialiser</a>
                </div>
                <?php endif; ?>
            </form>

            <?php if ($searchQ !== ''): ?>
            <p class="text-muted mb-3"><?= $totalProjects ?> résultat<?= $totalProjects !== 1 ? 's' : '' ?> pour « <?= htmlspecialchars($searchQ) ?> ».</p>
            <?php endif; ?>

            <?php if (count($projects) > 0): ?>
                <div class="row g-4">
                    <?php foreach ($projects as $p):
                        $cardCover = !empty($p->cover_image) ? client_asset_url($baseUrl, $p->cover_image) : '';
                    ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card card-mission h-100">
                                <?php if ($cardCover): ?>
                                <div class="card-mission-img" style="background-image: url('<?= htmlspecialchars($cardCover) ?>');"></div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <h2 class="card-title h5"><?= htmlspecialchars($p->name) ?></h2>
                                    <?php if (!empty($p->code)): ?>
                                        <p class="card-meta mb-1"><?= htmlspecialchars($p->code) ?></p>
                                    <?php endif; ?>
                                    <p class="card-meta mb-0">
                                        <?php if ($p->start_date): ?><?= date('d/m/Y', strtotime($p->start_date)) ?><?php endif; ?>
                                        <?php if ($p->end_date): ?> — <?= date('d/m/Y', strtotime($p->end_date)) ?><?php endif; ?>
                                        <?php if (!empty($p->status)): ?> · <?= htmlspecialchars($p->status) ?><?php endif; ?>
                                    </p>
                                    <?php if (!empty($p->description)): ?>
                                        <p class="mt-2 small text-muted"><?= htmlspecialchars(mb_substr(strip_tags($p->description), 0, 120)) ?><?= mb_strlen(strip_tags($p->description)) > 120 ? '…' : '' ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                <nav aria-label="Pagination des projets" class="mt-4 d-flex justify-content-center">
                    <ul class="pagination mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item"><a class="page-link" href="<?= $projectsQueryPrefix ?>page=<?= $page - 1 ?>"><i class="bi bi-chevron-left"></i></a></li>
                        <?php endif; ?>
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="<?= $projectsQueryPrefix ?>page=<?= $i ?>"><?= $i ?></a></li>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item"><a class="page-link" href="<?= $projectsQueryPrefix ?>page=<?= $page + 1 ?>"><i class="bi bi-chevron-right"></i></a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-muted">Aucun projet pour le moment.<?= $searchQ !== '' ? ' Essayez d\'autres termes de recherche.' : '' ?></p>
            <?php endif; ?>
        </div>
    </section>
<?php require __DIR__ . '/inc/footer.php'; ?>
