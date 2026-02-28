<?php
session_start();
$pageTitle = 'Médias & ressources';
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$baseUrl = ($scriptDir === '/' || $scriptDir === '\\') ? '' : rtrim($scriptDir, '/') . '/';
$organisation = null;
$categoriesWithDocs = [];
$attachments = [];

require_once __DIR__ . '/inc/db.php';

if ($pdo) {
    $stmt = $pdo->query("SELECT id, name, code, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) $organisation = $row;
    if ($organisation) $pageTitle = 'Médias & ressources — ' . $organisation->name;

    $orgId = $organisation ? (int) $organisation->id : 0;
    if ($orgId) {
        $stmt = $pdo->prepare("
            SELECT c.id, c.name, c.code, c.description
            FROM document_category c
            INNER JOIN document d ON d.document_category_id = c.id AND (d.organisation_id = ? OR d.organisation_id IS NULL)
            WHERE (c.organisation_id = ? OR c.organisation_id IS NULL)
            GROUP BY c.id, c.name, c.code, c.description
            ORDER BY c.name
        ");
        $stmt->execute([$orgId, $orgId]);
        $allCategories = $stmt->fetchAll(PDO::FETCH_OBJ);

        foreach ($allCategories as $cat) {
            $stmt2 = $pdo->prepare("
                SELECT d.id, d.title, d.description, d.document_type, d.cover_image, d.current_version_id
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
                $stmt3 = $pdo->prepare("SELECT id, document_id, file_path, file_name, file_size, mime_type FROM document_version WHERE id IN ($ph)");
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

        $stmt = $pdo->prepare("
            SELECT a.id, a.attachable_type, a.attachable_id, a.file_name, a.file_path, a.file_size, a.mime_type, a.created_at,
                   m.title AS mission_title,
                   an.title AS announcement_title
            FROM attachment a
            LEFT JOIN mission m ON a.attachable_type = 'mission' AND m.id = a.attachable_id AND m.organisation_id = ?
            LEFT JOIN announcement an ON a.attachable_type = 'announcement' AND an.id = a.attachable_id AND an.organisation_id = ?
            WHERE (a.attachable_type = 'mission' AND m.id IS NOT NULL)
               OR (a.attachable_type = 'announcement' AND an.id IS NOT NULL)
            ORDER BY a.created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$orgId, $orgId]);
        $attachments = $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}

require_once __DIR__ . '/inc/asset_url.php';
require_once __DIR__ . '/inc/page-static.php';

function format_file_size($bytes) {
    if ($bytes === null || $bytes === '') return '';
    $bytes = (int) $bytes;
    if ($bytes < 1024) return $bytes . ' o';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' Ko';
    return round($bytes / 1048576, 1) . ' Mo';
}
?>
    <section class="py-5 page-content media-resources-page">
        <div class="container">
            <nav aria-label="Fil d'Ariane" class="mb-4">
                <a href="<?= htmlspecialchars($baseUrl) ?>index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Accueil</a>
            </nav>
            <h1 class="section-heading mb-4">Médias & ressources</h1>

            <div class="content-prose mb-5">
                <p class="lead text-muted">Documents, publications et ressources utiles.</p>
                <p class="mb-4">Cette section rassemble les supports de communication, rapports et documents mis à disposition du public.</p>
                <div class="media-quick-links d-flex flex-wrap gap-2">
                    <a href="<?= htmlspecialchars($baseUrl) ?>index.php#actualites" class="btn btn-outline-primary btn-sm"><i class="bi bi-newspaper me-1"></i> Actualités</a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>reports-finances.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-graph-up me-1"></i> Rapports et finances</a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>responsibility.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-shield-check me-1"></i> Responsabilité</a>
                </div>
            </div>

            <?php if (!empty($categoriesWithDocs)): ?>
            <section class="media-documents mb-5" aria-labelledby="media-docs-heading">
                <h2 id="media-docs-heading" class="section-heading mb-4">Documents par catégorie</h2>
                <?php foreach ($categoriesWithDocs as $cat): ?>
                <div class="card border-0 shadow-sm mb-4 media-category-card">
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
                        <div class="row g-3">
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
                                    'doc' => 'bi-file-earmark-word', 'docx' => 'bi-file-earmark-word',
                                    'xls' => 'bi-file-earmark-excel', 'xlsx' => 'bi-file-earmark-excel',
                                    'ppt' => 'bi-file-ppt', 'pptx' => 'bi-file-ppt',
                                    'txt' => 'bi-file-earmark-text', 'zip' => 'bi-file-earmark-zip',
                                ];
                                $thumbIcon = $iconByExt[$ext] ?? 'bi-file-earmark';
                            ?>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="media-doc-item card border-0 shadow-sm h-100">
                                    <div class="card-body p-3 d-flex align-items-stretch gap-3">
                                        <div class="media-doc-thumb flex-shrink-0">
                                            <?php if ($thumbUrl): ?>
                                                <img src="<?= htmlspecialchars($thumbUrl) ?>" alt="" class="media-doc-thumb-img" loading="lazy">
                                            <?php else: ?>
                                                <div class="media-doc-thumb-icon"><i class="bi <?= $thumbIcon ?>"></i></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1 min-w-0 d-flex flex-column">
                                            <span class="fw-semibold d-block text-truncate" title="<?= htmlspecialchars($doc->title) ?>"><?= htmlspecialchars($doc->title) ?></span>
                                            <?php if (!empty($doc->document_type)): ?>
                                            <span class="badge bg-secondary bg-opacity-25 text-dark small mt-1"><?= htmlspecialchars($doc->document_type) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($doc->description)): ?>
                                            <p class="text-muted small mb-2 mt-1 flex-grow-1"><?= htmlspecialchars(mb_substr($doc->description, 0, 80)) ?><?= mb_strlen($doc->description) > 80 ? '…' : '' ?></p>
                                            <?php endif; ?>
                                            <?php if ($version && !empty($filePath)): ?>
                                            <?php
                                                $fileUrl = client_asset_url($baseUrl, $filePath);
                                                $sz = format_file_size($version->file_size ?? null);
                                            ?>
                                            <a href="<?= htmlspecialchars($fileUrl) ?>" class="btn btn-sm btn-outline-primary align-self-start" target="_blank" rel="noopener" download="<?= htmlspecialchars($fileName ?: basename($filePath)) ?>">
                                                <i class="bi bi-download me-1"></i> Télécharger<?= $sz ? ' (' . $sz . ')' : '' ?>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </section>
            <?php endif; ?>

            <?php if (!empty($attachments)): ?>
            <section class="media-attachments mb-5" aria-labelledby="media-attachments-heading">
                <h2 id="media-attachments-heading" class="section-heading mb-4">Fichiers joints (missions & annonces)</h2>
                <p class="text-muted mb-4">Documents attachés aux missions et aux actualités.</p>
                <div class="table-responsive">
                    <table class="table table-hover align-middle media-attachments-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 48px;"></th>
                                <th>Fichier</th>
                                <th>Lié à</th>
                                <th class="text-end">Taille</th>
                                <th class="text-end" style="width: 80px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attachments as $att): ?>
                            <?php
                            $fileUrl = client_asset_url($baseUrl, $att->file_path);
                            $parentTitle = $att->attachable_type === 'mission' ? ($att->mission_title ?? 'Mission #' . $att->attachable_id) : ($att->announcement_title ?? 'Annonce #' . $att->attachable_id);
                            $parentUrl = $att->attachable_type === 'mission'
                                ? $baseUrl . 'mission.php?id=' . (int) $att->attachable_id
                                : $baseUrl . 'announcement.php?id=' . (int) $att->attachable_id;
                            $typeLabel = $att->attachable_type === 'mission' ? 'Mission' : 'Annonce';
                            $attExt = $att->file_name ? strtolower(pathinfo($att->file_name, PATHINFO_EXTENSION)) : '';
                            $attIcon = 'bi-file-earmark';
                            if (in_array($attExt, ['jpg','jpeg','png','gif','webp'])) $attIcon = 'bi-file-earmark-image';
                            elseif ($attExt === 'pdf') $attIcon = 'bi-file-earmark-pdf';
                            elseif (in_array($attExt, ['doc','docx'])) $attIcon = 'bi-file-earmark-word';
                            elseif (in_array($attExt, ['xls','xlsx'])) $attIcon = 'bi-file-earmark-excel';
                            ?>
                            <tr>
                                <td class="text-center">
                                    <span class="media-attachment-icon"><i class="bi <?= $attIcon ?> text-primary"></i></span>
                                </td>
                                <td>
                                    <span class="fw-medium" title="<?= htmlspecialchars($att->file_name) ?>"><?= htmlspecialchars($att->file_name) ?></span>
                                </td>
                                <td>
                                    <a href="<?= htmlspecialchars($parentUrl) ?>"><?= htmlspecialchars($parentTitle) ?></a>
                                    <span class="text-muted small">(<?= $typeLabel ?>)</span>
                                </td>
                                <td class="text-end text-muted small"><?= format_file_size($att->file_size) ?></td>
                                <td class="text-end">
                                    <a href="<?= htmlspecialchars($fileUrl) ?>" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" download="<?= htmlspecialchars($att->file_name) ?>" title="Télécharger">
                                        <i class="bi bi-download"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <?php endif; ?>

            <?php if (empty($categoriesWithDocs) && empty($attachments)): ?>
            <div class="alert alert-light border text-muted mb-0">
                <i class="bi bi-info-circle me-2"></i>
                Aucun document ni fichier joint pour le moment. Les ressources apparaîtront ici une fois publiées depuis l’administration.
            </div>
            <?php endif; ?>
        </div>
    </section>
<?php require __DIR__ . '/inc/footer.php'; ?>
