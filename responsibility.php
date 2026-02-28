<?php
session_start();
$pageTitle = 'Responsabilité';
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$baseUrl = ($scriptDir === '/' || $scriptDir === '\\') ? '' : rtrim($scriptDir, '/') . '/';
$organisation = null;
$categoriesWithDocs = [];
$responsibilityPage = null;
$commitments = [];

require_once __DIR__ . '/inc/db.php';

if ($pdo) {
    $stmt = $pdo->query("SELECT id, name, code, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) $organisation = $row;
    if ($organisation) $pageTitle = 'Responsabilité — ' . $organisation->name;

    $orgId = $organisation ? (int) $organisation->id : null;

    // Load responsibility page intro (org-specific first, then global fallback)
    try {
        $stmt = $pdo->prepare("SELECT id, intro_block1, intro_block2 FROM responsibility_page WHERE organisation_id = ? OR organisation_id IS NULL ORDER BY organisation_id IS NULL ASC LIMIT 1");
        $stmt->execute([$orgId]);
        $responsibilityPage = $stmt->fetch(PDO::FETCH_OBJ);

    // Load commitments: org-specific if any, else global
    if ($orgId !== null) {
        $stmt = $pdo->prepare("SELECT id, title, description, icon FROM responsibility_commitment WHERE organisation_id = ? ORDER BY sort_order ASC, id ASC");
        $stmt->execute([$orgId]);
        $commitments = $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    if (empty($commitments)) {
        $stmt = $pdo->query("SELECT id, title, description, icon FROM responsibility_commitment WHERE organisation_id IS NULL ORDER BY sort_order ASC, id ASC");
        $commitments = $stmt ? $stmt->fetchAll(PDO::FETCH_OBJ) : [];
    }
    } catch (PDOException $e) {
        $responsibilityPage = null;
        $commitments = [];
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
    <section class="py-5 page-content responsibility-page">
        <div class="container">
            <nav aria-label="Fil d'Ariane" class="mb-4">
                <a href="<?= htmlspecialchars($baseUrl) ?>index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Accueil</a>
            </nav>
            <h1 class="section-heading mb-4">Responsabilité</h1>

            <div class="content-prose mb-5">
                <?php if ($responsibilityPage && (trim($responsibilityPage->intro_block1 ?? '') !== '' || trim($responsibilityPage->intro_block2 ?? '') !== '')): ?>
                    <?php if (trim($responsibilityPage->intro_block1 ?? '') !== ''): ?>
                    <p><?= nl2br(htmlspecialchars($responsibilityPage->intro_block1)) ?></p>
                    <?php endif; ?>
                    <?php if (trim($responsibilityPage->intro_block2 ?? '') !== ''): ?>
                    <p><?= nl2br(htmlspecialchars($responsibilityPage->intro_block2)) ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p>Politiques et rapports sur nos engagements éthiques, diversité et impact environnemental.</p>
                    <p>Notre responsabilité s'exerce vis-à-vis des personnes que nous accompagnons, de nos équipes et de l'environnement.</p>
                <?php endif; ?>
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
                                    'pdf' => 'bi-file-earmark-pdf',
                                    'doc' => 'bi-file-earmark-word',
                                    'docx' => 'bi-file-earmark-word',
                                    'xls' => 'bi-file-earmark-excel',
                                    'xlsx' => 'bi-file-earmark-excel',
                                    'ppt' => 'bi-file-ppt',
                                    'pptx' => 'bi-file-ppt',
                                    'txt' => 'bi-file-earmark-text',
                                    'zip' => 'bi-file-earmark-zip',
                                ];
                                $thumbIcon = $iconByExt[$ext] ?? 'bi-file-earmark';
                            ?>
                            <li class="responsibility-doc-item d-flex align-items-stretch gap-3 py-3 border-bottom border-light">
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
                                    <?php if ($version && !empty($filePath)):
                                        $fileUrl = client_asset_url($baseUrl, $filePath);
                                        $dlFileName = $fileName ?: basename($filePath);
                                    ?>
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

            <section class="responsibility-commitments content-prose" aria-labelledby="responsibility-commitments-heading">
                <h2 id="responsibility-commitments-heading" class="section-heading mb-4">Nos engagements</h2>
                <div class="row g-4">
                    <?php
                    $defaultIcon = 'bi-file-earmark';
                    if (!empty($commitments)):
                        foreach ($commitments as $commitment):
                            $iconClass = !empty($commitment->icon) ? $commitment->icon : $defaultIcon;
                    ?>
                    <div class="col-md-4">
                        <div class="card border-0 h-100 shadow-sm responsibility-commitment-card">
                            <div class="card-body p-4">
                                <div class="responsibility-commitment-icon mb-3"><i class="bi <?= htmlspecialchars($iconClass) ?> text-primary"></i></div>
                                <h3 class="h6 text-uppercase text-muted mb-2"><?= htmlspecialchars($commitment->title) ?></h3>
                                <p class="mb-0 small"><?= nl2br(htmlspecialchars($commitment->description ?? '')) ?></p>
                            </div>
                        </div>
                    </div>
                    <?php
                        endforeach;
                    else:
                    ?>
                    <div class="col-12">
                        <p class="text-muted">Aucun engagement défini pour le moment.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </section>
<?php require __DIR__ . '/inc/footer.php'; ?>
