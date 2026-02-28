<?php
session_start();
$pageTitle = 'Rapports et finances';
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$baseUrl = ($scriptDir === '/' || $scriptDir === '\\') ? '' : rtrim($scriptDir, '/') . '/';
$organisation = null;
$categoriesWithDocs = [];
$pageContent = null;

require_once __DIR__ . '/inc/db.php';

if ($pdo) {
    $stmt = $pdo->query("SELECT id, name, code, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) $organisation = $row;
    if ($organisation) $pageTitle = 'Rapports et finances — ' . $organisation->name;

    $orgId = $organisation ? (int) $organisation->id : null;

    try {
        $stmt = $pdo->prepare("SELECT id, intro_block1, intro_block2, section_activity_title, section_activity_text, section_finance_title, section_finance_text FROM reports_finances_page WHERE organisation_id = ? OR organisation_id IS NULL ORDER BY organisation_id IS NULL ASC LIMIT 1");
        $stmt->execute([$orgId]);
        $pageContent = $stmt->fetch(PDO::FETCH_OBJ);
    } catch (PDOException $e) {
        $pageContent = null;
    }

    $orgIdForDocs = $organisation ? (int) $organisation->id : 0;
    if ($orgIdForDocs) {
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
        $stmt->execute([$orgIdForDocs, $orgIdForDocs]);
        $allCategories = $stmt->fetchAll(PDO::FETCH_OBJ);

        $reportsKeywords = ['rapport', 'rapports', 'financ', 'activité', 'activite', 'transparence', 'budget', 'annuel', 'annuelle', 'compte', 'financial'];
        $categoriesWithDocs = [];
        foreach ($allCategories as $cat) {
            $code = strtolower(trim($cat->code ?? ''));
            $name = strtolower(trim($cat->name ?? ''));
            $match = false;
            foreach ($reportsKeywords as $kw) {
                if ($code !== '' && strpos($code, $kw) !== false) { $match = true; break; }
                if (strpos($name, $kw) !== false) { $match = true; break; }
            }
            if (!$match && count($categoriesWithDocs) < 1) continue;
            if ($match) {
                $stmt2 = $pdo->prepare("
                    SELECT d.id, d.title, d.description, d.document_type, d.cover_image, d.current_version_id
                    FROM document d
                    WHERE d.document_category_id = ? AND (d.organisation_id = ? OR d.organisation_id IS NULL)
                    ORDER BY d.updated_at DESC, d.title
                ");
                $stmt2->execute([$cat->id, $orgIdForDocs]);
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
            $stmt->execute([$orgIdForDocs, $orgIdForDocs]);
            $fallbackCats = $stmt->fetchAll(PDO::FETCH_OBJ);
            foreach ($fallbackCats as $cat) {
                $stmt2 = $pdo->prepare("
                    SELECT d.id, d.title, d.description, d.document_type, d.cover_image, d.current_version_id
                    FROM document d
                    WHERE d.document_category_id = ? AND (d.organisation_id = ? OR d.organisation_id IS NULL)
                    ORDER BY d.updated_at DESC, d.title
                ");
                $stmt2->execute([$cat->id, $orgIdForDocs]);
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
    <section class="py-5 page-content reports-finances-page">
        <div class="container">
            <nav aria-label="Fil d'Ariane" class="mb-4">
                <a href="<?= htmlspecialchars($baseUrl) ?>index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Accueil</a>
            </nav>
            <h1 class="section-heading mb-4">Rapports et finances</h1>

            <div class="content-prose mb-4">
                <?php if ($pageContent && (trim($pageContent->intro_block1 ?? '') !== '' || trim($pageContent->intro_block2 ?? '') !== '')): ?>
                    <?php if (trim($pageContent->intro_block1 ?? '') !== ''): ?>
                    <p class="lead text-muted"><?= nl2br(htmlspecialchars($pageContent->intro_block1)) ?></p>
                    <?php endif; ?>
                    <?php if (trim($pageContent->intro_block2 ?? '') !== ''): ?>
                    <p><?= nl2br(htmlspecialchars($pageContent->intro_block2)) ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="lead text-muted">Rapports annuels d'activité et financiers, origine des fonds et utilisation.</p>
                    <p>Nous nous engageons à rendre compte de notre activité et de l'usage des ressources qui nous sont confiées.</p>
                <?php endif; ?>
                <div class="reports-finances-quick-links d-flex flex-wrap gap-2 mt-4">
                    <a href="<?= htmlspecialchars($baseUrl) ?>media-resources.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-folder2 me-1"></i> Médias & ressources</a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>responsibility.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-shield-check me-1"></i> Responsabilité</a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>index.php#actualites" class="btn btn-outline-primary btn-sm"><i class="bi bi-newspaper me-1"></i> Actualités</a>
                </div>
            </div>

            <?php
            $actTitle = $pageContent && trim($pageContent->section_activity_title ?? '') !== '' ? $pageContent->section_activity_title : 'Rapports d\'activité';
            $actText = $pageContent && trim($pageContent->section_activity_text ?? '') !== '' ? $pageContent->section_activity_text : 'Les rapports annuels présentent les réalisations, les enseignements et les perspectives de l\'organisation.';
            $finTitle = $pageContent && trim($pageContent->section_finance_title ?? '') !== '' ? $pageContent->section_finance_title : 'Transparence financière';
            $finText = $pageContent && trim($pageContent->section_finance_text ?? '') !== '' ? $pageContent->section_finance_text : 'L\'origine des fonds et leur répartition sont détaillées dans nos documents financiers publics.';
            ?>
            <div class="row g-4 mb-5">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100 reports-finances-section-card">
                        <div class="card-body p-4">
                            <div class="reports-finances-section-icon mb-3"><i class="bi bi-file-text text-primary"></i></div>
                            <h2 class="h5 mb-2"><?= htmlspecialchars($actTitle) ?></h2>
                            <p class="text-muted small mb-0"><?= nl2br(htmlspecialchars($actText)) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100 reports-finances-section-card">
                        <div class="card-body p-4">
                            <div class="reports-finances-section-icon mb-3"><i class="bi bi-graph-up-arrow text-primary"></i></div>
                            <h2 class="h5 mb-2"><?= htmlspecialchars($finTitle) ?></h2>
                            <p class="text-muted small mb-0"><?= nl2br(htmlspecialchars($finText)) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($categoriesWithDocs)): ?>
            <section class="reports-finances-documents mb-5" aria-labelledby="reports-docs-heading">
                <h2 id="reports-docs-heading" class="section-heading mb-4">Documents</h2>
                <?php foreach ($categoriesWithDocs as $cat): ?>
                <div class="card border-0 shadow-sm mb-4 reports-finances-category-card">
                    <div class="card-body p-4">
                        <h3 class="h5 mb-2">
                            <i class="bi bi-folder2-open text-primary me-2"></i>
                            <?= htmlspecialchars($cat->name) ?>
                            <?php if (!empty($cat->code)): ?>
                            <span class="text-muted fw-normal small">(<?= htmlspecialchars($cat->code) ?>)</span>
                            <?php endif; ?>
                            <span class="badge bg-light text-dark ms-2 small"><?= count($cat->documents) ?> document<?= count($cat->documents) > 1 ? 's' : '' ?></span>
                        </h3>
                        <?php if (!empty($cat->description)): ?>
                        <p class="text-muted small mb-3"><?= nl2br(htmlspecialchars($cat->description)) ?></p>
                        <?php endif; ?>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($cat->documents as $doc):
                                $version = $doc->version ?? null;
                                $filePath = $version ? ($version->file_path ?? null) : null;
                                $fileName = $version ? ($version->file_name ?? null) : null;
                                $ext = $filePath ? strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) : '';
                                $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
                                if (!empty($doc->cover_image)) {
                                    $thumbUrl = client_asset_url($baseUrl, $doc->cover_image);
                                } elseif ($isImage && $filePath) {
                                    $thumbUrl = client_asset_url($baseUrl, $filePath);
                                } else {
                                    $thumbUrl = '';
                                }
                                $iconByExt = [
                                    'pdf' => 'bi-file-earmark-pdf', 'doc' => 'bi-file-earmark-word', 'docx' => 'bi-file-earmark-word',
                                    'xls' => 'bi-file-earmark-excel', 'xlsx' => 'bi-file-earmark-excel',
                                    'ppt' => 'bi-file-ppt', 'pptx' => 'bi-file-ppt',
                                    'txt' => 'bi-file-earmark-text', 'zip' => 'bi-file-earmark-zip',
                                ];
                                $thumbIcon = $iconByExt[$ext] ?? 'bi-file-earmark';
                            ?>
                            <li class="reports-finances-doc-item d-flex align-items-stretch gap-3 py-3 border-bottom border-light">
                                <div class="responsibility-doc-thumb flex-shrink-0">
                                    <?php if ($thumbUrl): ?>
                                        <img src="<?= htmlspecialchars($thumbUrl) ?>" alt="" class="responsibility-doc-thumb-img" loading="lazy">
                                    <?php else: ?>
                                        <div class="responsibility-doc-thumb-icon"><i class="bi <?= $thumbIcon ?>"></i></div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <span class="fw-semibold"><?= htmlspecialchars($doc->title) ?></span>
                                    <?php if (!empty($doc->document_type)): ?>
                                    <span class="badge bg-light text-dark ms-2 small"><?= htmlspecialchars($doc->document_type) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($doc->description)): ?>
                                    <p class="text-muted small mb-0 mt-1"><?= nl2br(htmlspecialchars(mb_substr($doc->description, 0, 200))) ?><?= mb_strlen($doc->description) > 200 ? '…' : '' ?></p>
                                    <?php endif; ?>
                                    <?php if ($version && !empty($filePath)): ?>
                                    <?php $fileUrl = client_asset_url($baseUrl, $filePath); $dlFileName = $fileName ?: basename($filePath); ?>
                                    <a href="<?= htmlspecialchars($fileUrl) ?>" class="btn btn-sm btn-outline-primary mt-2" target="_blank" rel="noopener" download="<?= htmlspecialchars($dlFileName) ?>">
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

            <?php if (empty($categoriesWithDocs)): ?>
            <div class="alert alert-light border text-muted mb-0">
                <i class="bi bi-info-circle me-2"></i>
                Aucun document pour le moment. Les rapports et documents financiers apparaîtront ici une fois publiés depuis l'administration.
            </div>
            <?php endif; ?>
        </div>
    </section>
<?php require __DIR__ . '/inc/footer.php'; ?>
