<?php
session_start();
$pageTitle = 'Responsabilité';
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$baseUrl = ($scriptDir === '/' || $scriptDir === '\\') ? '' : rtrim($scriptDir, '/') . '/';
$organisation = null;
$categoriesWithDocs = [];

require_once __DIR__ . '/inc/db.php';

if ($pdo) {
    $stmt = $pdo->query("SELECT id, name, code, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) $organisation = $row;
    if ($organisation) $pageTitle = 'Responsabilité — ' . $organisation->name;

    $orgId = $organisation ? (int) $organisation->id : 0;
    if ($orgId) {
        $stmt = $pdo->prepare("
            SELECT c.id, c.name, c.code, c.description
            FROM document_category c
            WHERE (c.organisation_id = ? OR c.organisation_id IS NULL)
            AND EXISTS (
                SELECT 1 FROM document d
                WHERE d.document_category_id = c.id AND (d.organisation_id = ? OR d.organisation_id IS NULL)
            )
            ORDER BY c.name
        ");
        $stmt->execute([$orgId, $orgId]);
        $allCategories = $stmt->fetchAll(PDO::FETCH_OBJ);

        $responsibilityKeywords = ['responsabilite', 'responsabilité', 'ethique', 'éthique', 'diversite', 'diversité', 'inclusion', 'environnement', 'politique', 'rapport', 'engagement'];
        $categoriesWithDocs = [];
        foreach ($allCategories as $cat) {
            $code = strtolower(trim($cat->code ?? ''));
            $name = strtolower(trim($cat->name ?? ''));
            $match = false;
            foreach ($responsibilityKeywords as $kw) {
                if ($code !== '' && strpos($code, $kw) !== false) { $match = true; break; }
                if (strpos($name, $kw) !== false) { $match = true; break; }
            }
            if (!$match && count($categoriesWithDocs) < 1) {
                continue;
            }
            if ($match) {
                $stmt2 = $pdo->prepare("
                    SELECT d.id, d.title, d.description, d.document_type, d.current_version_id
                    FROM document d
                    WHERE d.document_category_id = ? AND (d.organisation_id = ? OR d.organisation_id IS NULL)
                    ORDER BY d.updated_at DESC, d.title
                ");
                $stmt2->execute([$cat->id, $orgId]);
                $docs = $stmt2->fetchAll(PDO::FETCH_OBJ);
                $versionIds = array_filter(array_map(function ($d) { return (int) $d->current_version_id; }, $docs));
                $versions = [];
                if (!empty($versionIds)) {
                    $ph = implode(',', array_fill(0, count($versionIds), '?'));
                    $stmt3 = $pdo->prepare("SELECT id, document_id, file_path, file_name FROM document_version WHERE id IN ($ph)");
                    $stmt3->execute(array_values($versionIds));
                    while ($v = $stmt3->fetch(PDO::FETCH_OBJ)) {
                        $versions[(int) $v->id] = $v;
                    }
                }
                foreach ($docs as $d) {
                    $d->version = isset($d->current_version_id) ? ($versions[(int) $d->current_version_id] ?? null) : null;
                }
                $cat->documents = $docs;
                $categoriesWithDocs[] = $cat;
            }
        }

        if (empty($categoriesWithDocs)) {
            $stmt = $pdo->prepare("
                SELECT c.id, c.name, c.code, c.description
                FROM document_category c
                INNER JOIN document d ON d.document_category_id = c.id AND (d.organisation_id = ? OR d.organisation_id IS NULL)
                WHERE (c.organisation_id = ? OR c.organisation_id IS NULL)
                GROUP BY c.id
                ORDER BY c.name
            ");
            $stmt->execute([$orgId, $orgId]);
            $fallbackCats = $stmt->fetchAll(PDO::FETCH_OBJ);
            foreach ($fallbackCats as $cat) {
                $stmt2 = $pdo->prepare("
                    SELECT d.id, d.title, d.description, d.document_type, d.current_version_id
                    FROM document d
                    WHERE d.document_category_id = ? AND (d.organisation_id = ? OR d.organisation_id IS NULL)
                    ORDER BY d.updated_at DESC, d.title
                ");
                $stmt2->execute([$cat->id, $orgId]);
                $docs = $stmt2->fetchAll(PDO::FETCH_OBJ);
                $versionIds = array_filter(array_map(function ($d) { return (int) $d->current_version_id; }, $docs));
                $versions = [];
                if (!empty($versionIds)) {
                    $ph = implode(',', array_fill(0, count($versionIds), '?'));
                    $stmt3 = $pdo->prepare("SELECT id, document_id, file_path, file_name FROM document_version WHERE id IN ($ph)");
                    $stmt3->execute(array_values($versionIds));
                    while ($v = $stmt3->fetch(PDO::FETCH_OBJ)) {
                        $versions[(int) $v->id] = $v;
                    }
                }
                foreach ($docs as $d) {
                    $d->version = isset($d->current_version_id) ? ($versions[(int) $d->current_version_id] ?? null) : null;
                }
                $cat->documents = $docs;
                $categoriesWithDocs[] = $cat;
            }
        }
    }
}

require_once __DIR__ . '/inc/asset_url.php';
require_once __DIR__ . '/inc/page-static.php';
?>
    <section class="py-5 page-content responsibility-page">
        <div class="container">
            <nav aria-label="Fil d'Ariane" class="mb-4">
                <a href="<?= htmlspecialchars($baseUrl) ?>index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Accueil</a>
            </nav>
            <h1 class="section-heading mb-4">Responsabilité</h1>

            <div class="content-prose mb-5">
                <p>Politiques et rapports sur nos engagements éthiques, diversité et impact environnemental.</p>
                <p>Notre responsabilité s'exerce vis-à-vis des personnes que nous accompagnons, de nos équipes et de l'environnement.</p>
            </div>

            <?php if (!empty($categoriesWithDocs)): ?>
            <section class="responsibility-documents mb-5" aria-labelledby="responsibility-docs-heading">
                <h2 id="responsibility-docs-heading" class="section-heading mb-4">Documents et ressources</h2>
                <?php foreach ($categoriesWithDocs as $cat): ?>
                <div class="card border-0 shadow-sm mb-4 responsibility-category-card">
                    <div class="card-body p-4">
                        <h3 class="h5 mb-2">
                            <i class="bi bi-folder2-open text-primary me-2"></i>
                            <?= htmlspecialchars($cat->name) ?>
                            <?php if (!empty($cat->code)): ?>
                            <span class="text-muted fw-normal small">(<?= htmlspecialchars($cat->code) ?>)</span>
                            <?php endif; ?>
                        </h3>
                        <?php if (!empty($cat->description)): ?>
                        <p class="text-muted small mb-3"><?= nl2br(htmlspecialchars($cat->description)) ?></p>
                        <?php endif; ?>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($cat->documents as $doc): ?>
                            <li class="responsibility-doc-item d-flex align-items-start gap-2 py-2 border-bottom border-light">
                                <i class="bi bi-file-earmark-text text-muted mt-1"></i>
                                <div class="flex-grow-1">
                                    <span class="fw-semibold"><?= htmlspecialchars($doc->title) ?></span>
                                    <?php if (!empty($doc->document_type)): ?>
                                    <span class="badge bg-light text-dark ms-2 small"><?= htmlspecialchars($doc->document_type) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($doc->description)): ?>
                                    <p class="text-muted small mb-0 mt-1"><?= nl2br(htmlspecialchars(mb_substr($doc->description, 0, 200))) ?><?= mb_strlen($doc->description) > 200 ? '…' : '' ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($doc->version) && !empty($doc->version->file_path)):
                                        $fileUrl = client_asset_url($baseUrl, $doc->version->file_path);
                                        $fileName = !empty($doc->version->file_name) ? $doc->version->file_name : basename($doc->version->file_path);
                                    ?>
                                    <a href="<?= htmlspecialchars($fileUrl) ?>" class="btn btn-sm btn-outline-primary mt-2" target="_blank" rel="noopener" download="<?= htmlspecialchars($fileName) ?>">
                                        <i class="bi bi-download me-1"></i> Télécharger
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php endforeach; ?>
            </section>
            <?php endif; ?>

            <section class="responsibility-commitments content-prose" aria-labelledby="responsibility-commitments-heading">
                <h2 id="responsibility-commitments-heading" class="section-heading mb-4">Nos engagements</h2>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card border-0 h-100 shadow-sm responsibility-commitment-card">
                            <div class="card-body p-4">
                                <div class="responsibility-commitment-icon mb-3"><i class="bi bi-shield-check text-primary"></i></div>
                                <h3 class="h6 text-uppercase text-muted mb-2">Éthique et intégrité</h3>
                                <p class="mb-0 small">Des politiques et dispositifs encadrent nos pratiques pour garantir l'intégrité de nos actions.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 h-100 shadow-sm responsibility-commitment-card">
                            <div class="card-body p-4">
                                <div class="responsibility-commitment-icon mb-3"><i class="bi bi-people text-primary"></i></div>
                                <h3 class="h6 text-uppercase text-muted mb-2">Diversité et inclusion</h3>
                                <p class="mb-0 small">Nous œuvrons pour un environnement inclusif et une représentation équitable au sein de l'organisation.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 h-100 shadow-sm responsibility-commitment-card">
                            <div class="card-body p-4">
                                <div class="responsibility-commitment-icon mb-3"><i class="bi bi-globe2 text-primary"></i></div>
                                <h3 class="h6 text-uppercase text-muted mb-2">Environnement</h3>
                                <p class="mb-0 small">Nous nous efforçons de réduire l'impact environnemental de nos activités et de nos déplacements.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </section>
<?php require __DIR__ . '/inc/footer.php'; ?>
