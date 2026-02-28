<?php
session_start();
$pageTitle = 'Qui nous sommes';
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$baseUrl = ($scriptDir === '/' || $scriptDir === '\\') ? '' : rtrim($scriptDir, '/') . '/';
$organisation = null;
$organisationTypes = [];
$departments = [];
$entities = [];

require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/asset_url.php';

if ($pdo) {
    $stmt = $pdo->query("
        SELECT id, name, code, description, address, phone, email, website,
               postal_code, city, country, rccm, nif, sector, logo, cover_image,
               facebook_url, linkedin_url, twitter_url, instagram_url, youtube_url
        FROM organisation WHERE is_active = 1 LIMIT 1
    ");
    if ($row = $stmt->fetch()) $organisation = $row;
    if ($organisation) $pageTitle = 'Qui nous sommes — ' . $organisation->name;

    if ($organisation) {
        $orgId = (int) $organisation->id;
        $stmt = $pdo->prepare("SELECT type_code FROM organisation_organisation_type WHERE organisation_id = ?");
        $stmt->execute([$orgId]);
        $organisationTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    $orgId = $organisation ? (int) $organisation->id : 0;
    if ($orgId) {
        $stmt = $pdo->prepare("
            SELECT d.id, d.name, d.code, d.description, d.photo,
                   d.head_user_id, u.first_name AS head_first_name, u.last_name AS head_last_name
            FROM department d
            LEFT JOIN user u ON u.id = d.head_user_id AND u.is_active = 1
            WHERE d.organisation_id = ? AND d.is_active = 1
            ORDER BY d.name
        ");
        $stmt->execute([$orgId]);
        $deptRows = $stmt->fetchAll(PDO::FETCH_OBJ);
        $deptIds = array_map(function ($d) { return (int) $d->id; }, $deptRows);

        if (!empty($deptIds)) {
            $placeholders = implode(',', array_fill(0, count($deptIds), '?'));
            $stmt = $pdo->prepare("SELECT id, department_id, name, code, description, photo FROM service WHERE department_id IN ($placeholders) AND is_active = 1 ORDER BY name");
            $stmt->execute($deptIds);
            $servicesByDept = [];
            while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                $did = (int) $row->department_id;
                if (!isset($servicesByDept[$did])) $servicesByDept[$did] = [];
                $servicesByDept[$did][] = $row;
            }
            $serviceIds = [];
            foreach ($servicesByDept as $list) foreach ($list as $s) { $serviceIds[] = (int) $s->id; }
            $unitsByService = [];
            if (!empty($serviceIds)) {
                $placeholders = implode(',', array_fill(0, count($serviceIds), '?'));
                $stmt = $pdo->prepare("SELECT id, service_id, name, code, description, photo FROM unit WHERE service_id IN ($placeholders) AND is_active = 1 ORDER BY name");
                $stmt->execute($serviceIds);
                while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                    $sid = (int) $row->service_id;
                    if (!isset($unitsByService[$sid])) $unitsByService[$sid] = [];
                    $unitsByService[$sid][] = $row;
                }
            }
            foreach ($deptRows as $dept) {
                $dept->services = $servicesByDept[(int) $dept->id] ?? [];
                foreach ($dept->services as $svc) {
                    $svc->units = $unitsByService[(int) $svc->id] ?? [];
                }
            }
            $departments = $deptRows;
        }

        $stmt = $pdo->prepare("SELECT id, name, code, entity_type, parent_entity_id, description FROM organisational_entity WHERE organisation_id = ? AND is_active = 1 ORDER BY entity_type, name");
        $stmt->execute([$orgId]);
        $entities = $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}

require_once __DIR__ . '/inc/page-static.php';

$orgLogoUrl = '';
if ($organisation && !empty($organisation->logo)) {
    $orgLogoUrl = client_asset_url($baseUrl, $organisation->logo);
}
$orgCoverUrl = '';
if ($organisation && !empty($organisation->cover_image)) {
    $orgCoverUrl = client_asset_url($baseUrl, $organisation->cover_image);
}
$typeLabels = [
    'ngo' => 'ONG',
    'association' => 'Association',
    'foundation' => 'Fondation',
    'company' => 'Entreprise',
    'public' => 'Organisation publique',
    'other' => 'Autre',
];
?>
    <?php if ($organisation && $orgCoverUrl): ?>
    <header class="about-hero">
        <div class="about-hero-bg" style="background-image: url('<?= htmlspecialchars($orgCoverUrl) ?>');"></div>
        <div class="about-hero-overlay"></div>
        <div class="container about-hero-content">
            <nav aria-label="Fil d'Ariane" class="about-hero-breadcrumb">
                <a href="<?= htmlspecialchars($baseUrl) ?>index.php" class="text-white text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Accueil</a>
            </nav>
            <h1 class="about-hero-title">Qui nous sommes</h1>
        </div>
    </header>
    <?php endif; ?>
    <section class="py-5 page-content about-page <?= ($organisation && $orgCoverUrl) ? 'about-page-has-hero' : '' ?>">
        <div class="container">
            <?php if (!$organisation || !$orgCoverUrl): ?>
            <nav aria-label="Fil d'Ariane" class="mb-4">
                <a href="<?= htmlspecialchars($baseUrl) ?>index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Accueil</a>
            </nav>
            <?php endif; ?>
            <?php if (!$orgCoverUrl): ?>
            <h1 class="section-heading mb-4">Qui nous sommes</h1>
            <?php endif; ?>

            <?php if ($organisation): ?>
            <div class="about-intro content-prose mb-5">
                <!-- En-tête identité : logo + nom + types + secteur -->
                <div class="about-org-identity card border-0 shadow-sm mb-4 p-4">
                    <div class="row align-items-center g-4">
                        <?php if ($orgLogoUrl): ?>
                        <div class="col-auto">
                            <img src="<?= htmlspecialchars($orgLogoUrl) ?>" alt="Logo de <?= htmlspecialchars($organisation->name) ?>" class="about-org-logo" width="120" height="120" loading="lazy">
                        </div>
                        <?php endif; ?>
                        <div class="col">
                            <h2 class="h4 mb-2"><?= htmlspecialchars($organisation->name) ?><?php if (!empty($organisation->code)): ?> <span class="text-muted fw-normal">(<?= htmlspecialchars($organisation->code) ?>)</span><?php endif; ?></h2>
                            <?php if (!empty($organisationTypes)): ?>
                            <p class="about-org-types mb-1">
                                <?php
                                $labels = array_map(function ($t) use ($typeLabels) {
                                    return $typeLabels[$t] ?? ucfirst($t);
                                }, $organisationTypes);
                                echo htmlspecialchars(implode(' · ', $labels));
                                ?>
                            </p>
                            <?php endif; ?>
                            <?php if (!empty($organisation->sector)): ?>
                            <p class="text-muted small mb-0"><i class="bi bi-briefcase me-1"></i> <?= htmlspecialchars($organisation->sector) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if (!empty($organisation->description)): ?>
                <div class="about-org-description mb-4 about-org-description-html">
                    <?= client_rewrite_uploads_in_html($baseUrl, $organisation->description) ?>
                </div>
                <?php else: ?>
                <p>Découvrez notre mission, notre charte et nos principes. Nous sommes une organisation indépendante qui place les personnes au cœur de son action.</p>
                <?php endif; ?>

                <div class="about-org-contact card border-0 shadow-sm mt-4 p-4 about-contact-card">
                    <h3 class="h6 text-uppercase text-muted mb-3">Coordonnées</h3>
                    <div class="row g-3">
                        <?php
                        $addrParts = array_filter([
                            $organisation->address ?? '',
                            trim(($organisation->postal_code ?? '') . ' ' . ($organisation->city ?? '')),
                            $organisation->country ?? '',
                        ]);
                        $fullAddress = implode(', ', $addrParts);
                        if ($fullAddress !== ''):
                        ?>
                        <div class="col-12"><p class="mb-0"><i class="bi bi-geo-alt me-2 text-muted"></i><?= nl2br(htmlspecialchars($fullAddress)) ?></p></div>
                        <?php elseif (!empty($organisation->address)): ?>
                        <div class="col-12"><p class="mb-0"><i class="bi bi-geo-alt me-2 text-muted"></i><?= nl2br(htmlspecialchars($organisation->address)) ?></p></div>
                        <?php endif; ?>
                        <?php if (!empty($organisation->phone)): ?>
                        <div class="col-12 col-md-6"><p class="mb-0"><i class="bi bi-telephone me-2 text-muted"></i><a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $organisation->phone)) ?>"><?= htmlspecialchars($organisation->phone) ?></a></p></div>
                        <?php endif; ?>
                        <?php if (!empty($organisation->email)): ?>
                        <div class="col-12 col-md-6"><p class="mb-0"><i class="bi bi-envelope me-2 text-muted"></i><a href="mailto:<?= htmlspecialchars($organisation->email) ?>"><?= htmlspecialchars($organisation->email) ?></a></p></div>
                        <?php endif; ?>
                        <?php if (!empty($organisation->website)): ?>
                        <div class="col-12"><p class="mb-0"><i class="bi bi-globe me-2 text-muted"></i><a href="<?= htmlspecialchars($organisation->website) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($organisation->website) ?></a></p></div>
                        <?php endif; ?>
                        <?php if (!empty($organisation->rccm) || !empty($organisation->nif)): ?>
                        <div class="col-12 pt-2 border-top mt-2">
                            <?php if (!empty($organisation->rccm)): ?><span class="me-4"><strong>RCCM</strong> <?= htmlspecialchars($organisation->rccm) ?></span><?php endif; ?>
                            <?php if (!empty($organisation->nif)): ?><span><strong>NIF</strong> <?= htmlspecialchars($organisation->nif) ?></span><?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php
                $hasSocial = !empty($organisation->facebook_url) || !empty($organisation->linkedin_url) || !empty($organisation->twitter_url) || !empty($organisation->instagram_url) || !empty($organisation->youtube_url);
                if ($hasSocial):
                ?>
                <div class="about-org-social mt-3">
                    <p class="h6 text-uppercase text-muted mb-2">Suivez-nous</p>
                    <div class="d-flex flex-wrap gap-3">
                        <?php if (!empty($organisation->facebook_url)): ?><a href="<?= htmlspecialchars($organisation->facebook_url) ?>" target="_blank" rel="noopener noreferrer" class="about-social-link" aria-label="Facebook"><i class="bi bi-facebook"></i></a><?php endif; ?>
                        <?php if (!empty($organisation->linkedin_url)): ?><a href="<?= htmlspecialchars($organisation->linkedin_url) ?>" target="_blank" rel="noopener noreferrer" class="about-social-link" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a><?php endif; ?>
                        <?php if (!empty($organisation->twitter_url)): ?><a href="<?= htmlspecialchars($organisation->twitter_url) ?>" target="_blank" rel="noopener noreferrer" class="about-social-link" aria-label="X (Twitter)"><i class="bi bi-twitter-x"></i></a><?php endif; ?>
                        <?php if (!empty($organisation->instagram_url)): ?><a href="<?= htmlspecialchars($organisation->instagram_url) ?>" target="_blank" rel="noopener noreferrer" class="about-social-link" aria-label="Instagram"><i class="bi bi-instagram"></i></a><?php endif; ?>
                        <?php if (!empty($organisation->youtube_url)): ?><a href="<?= htmlspecialchars($organisation->youtube_url) ?>" target="_blank" rel="noopener noreferrer" class="about-social-link" aria-label="YouTube"><i class="bi bi-youtube"></i></a><?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($departments)): ?>
            <section class="about-structure mt-5" aria-labelledby="about-structure-heading">
                <h2 id="about-structure-heading" class="section-heading mb-4">Notre structure</h2>
                <p class="text-muted mb-4">Organisation en départements, services et unités.</p>
                <div class="about-structure-list">
                    <?php foreach ($departments as $dept):
                        $deptPhotoUrl = !empty($dept->photo) ? client_asset_url($baseUrl, $dept->photo) : '';
                        $headName = null;
                        if (!empty($dept->head_first_name) || !empty($dept->head_last_name)) {
                            $headName = trim(($dept->head_first_name ?? '') . ' ' . ($dept->head_last_name ?? ''));
                        }
                    ?>
                    <div class="about-structure-block card border-0 shadow-sm mb-3 overflow-hidden">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-start gap-3">
                                <?php if ($deptPhotoUrl): ?>
                                <div class="about-structure-photo flex-shrink-0">
                                    <img src="<?= htmlspecialchars($deptPhotoUrl) ?>" alt="" class="about-dept-img" width="80" height="80" loading="lazy">
                                </div>
                                <?php else: ?>
                                <span class="about-structure-icon text-primary flex-shrink-0"><i class="bi bi-building"></i></span>
                                <?php endif; ?>
                                <div class="flex-grow-1">
                                    <h3 class="h5 mb-1"><?= htmlspecialchars($dept->name) ?><?php if (!empty($dept->code)): ?> <span class="text-muted fw-normal small">(<?= htmlspecialchars($dept->code) ?>)</span><?php endif; ?></h3>
                                    <?php if ($headName): ?>
                                    <p class="text-muted small mb-2"><i class="bi bi-person me-1"></i> Responsable : <?= htmlspecialchars($headName) ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($dept->description)): ?>
                                    <p class="text-muted small mb-3"><?= nl2br(htmlspecialchars($dept->description)) ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($dept->services)): ?>
                                    <ul class="about-services-list list-unstyled mb-0">
                                        <?php foreach ($dept->services as $svc):
                                            $svcPhotoUrl = !empty($svc->photo) ? client_asset_url($baseUrl, $svc->photo) : '';
                                        ?>
                                        <li class="about-service-item mb-3">
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <?php if ($svcPhotoUrl): ?>
                                                <img src="<?= htmlspecialchars($svcPhotoUrl) ?>" alt="" class="about-svc-img" width="40" height="40" loading="lazy">
                                                <?php endif; ?>
                                                <span class="about-service-name d-inline-flex align-items-center gap-2"><i class="bi bi-diagram-3 text-muted small"></i><strong><?= htmlspecialchars($svc->name) ?><?php if (!empty($svc->code)): ?> <span class="text-muted fw-normal small">(<?= htmlspecialchars($svc->code) ?>)</span><?php endif; ?></strong></span>
                                            </div>
                                            <?php if (!empty($svc->description)): ?>
                                            <p class="about-service-desc text-muted small ms-4 mb-2"><?= nl2br(htmlspecialchars($svc->description)) ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($svc->units)): ?>
                                            <ul class="about-units-list list-unstyled ms-4 mb-0">
                                                <?php foreach ($svc->units as $unit):
                                                    $unitPhotoUrl = !empty($unit->photo) ? client_asset_url($baseUrl, $unit->photo) : '';
                                                ?>
                                                <li class="about-unit-item small d-flex align-items-center gap-2 py-1">
                                                    <?php if ($unitPhotoUrl): ?>
                                                    <img src="<?= htmlspecialchars($unitPhotoUrl) ?>" alt="" class="about-unit-img" width="28" height="28" loading="lazy">
                                                    <?php else: ?>
                                                    <i class="bi bi-arrow-return-right text-muted"></i>
                                                    <?php endif; ?>
                                                    <?= htmlspecialchars($unit->name) ?><?php if (!empty($unit->code)): ?> <span class="text-muted">(<?= htmlspecialchars($unit->code) ?>)</span><?php endif; ?>
                                                    <?php if (!empty($unit->description)): ?>
                                                    <span class="text-muted">— <?= htmlspecialchars(mb_substr($unit->description, 0, 120)) ?><?= mb_strlen($unit->description) > 120 ? '…' : '' ?></span>
                                                    <?php endif; ?>
                                                </li>
                                                <?php endforeach; ?>
                                            </ul>
                                            <?php endif; ?>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <?php if (!empty($entities) && empty($departments)): ?>
            <section class="about-entities mt-5" aria-labelledby="about-entities-heading">
                <h2 id="about-entities-heading" class="section-heading mb-4">Entités organisationnelles</h2>
                <?php
                $byType = ['division' => 'Divisions', 'department' => 'Départements', 'service' => 'Services', 'unit' => 'Unités'];
                foreach ($byType as $type => $label):
                    $list = array_filter($entities, function ($e) use ($type) { return $e->entity_type === $type; });
                    if (empty($list)) continue;
                ?>
                <div class="mb-4">
                    <h3 class="h6 text-uppercase text-muted mb-2"><?= $label ?></h3>
                    <ul class="about-entities-list list-unstyled">
                        <?php foreach ($list as $e): ?>
                        <li class="d-flex align-items-start gap-2 py-2 border-bottom border-light">
                            <i class="bi bi-chevron-right text-primary small mt-1"></i>
                            <div>
                                <strong><?= htmlspecialchars($e->name) ?><?php if (!empty($e->code)): ?> <span class="text-muted fw-normal small">(<?= htmlspecialchars($e->code) ?>)</span><?php endif; ?></strong>
                                <?php if (!empty($e->description)): ?>
                                <p class="text-muted small mb-0 mt-1"><?= nl2br(htmlspecialchars($e->description)) ?></p>
                                <?php endif; ?>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endforeach; ?>
            </section>
            <?php endif; ?>

            <?php if (empty($departments) && empty($entities)): ?>
            <div class="content-prose mt-4">
                <h2 class="h5 mt-4 mb-2">Notre mission</h2>
                <p>Agir pour et avec les populations que nous accompagnons, dans le respect de leur dignité et de leurs droits.</p>
                <h2 class="h5 mt-4 mb-2">Nos principes</h2>
                <ul>
                    <li>Indépendance et impartialité</li>
                    <li>Neutralité et accès aux populations</li>
                    <li>Redevabilité et transparence</li>
                </ul>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div class="content-prose">
                <p>Découvrez notre mission, notre charte et nos principes.</p>
                <p>Nous sommes une organisation indépendante qui place les personnes au cœur de son action. Notre identité repose sur des valeurs partagées et un cadre commun qui guide nos interventions sur le terrain.</p>
                <h2 class="h5 mt-4 mb-2">Notre mission</h2>
                <p>Agir pour et avec les populations que nous accompagnons, dans le respect de leur dignité et de leurs droits.</p>
                <h2 class="h5 mt-4 mb-2">Nos principes</h2>
                <ul>
                    <li>Indépendance et impartialité</li>
                    <li>Neutralité et accès aux populations</li>
                    <li>Redevabilité et transparence</li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </section>
<?php require __DIR__ . '/inc/footer.php'; ?>
