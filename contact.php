<?php
session_start();
$pageTitle = 'Nous contacter';
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$baseUrl = ($scriptDir === '/' || $scriptDir === '\\') ? '' : rtrim($scriptDir, '/') . '/';
$organisation = null;

require_once __DIR__ . '/inc/db.php';

if ($pdo) {
    $stmt = $pdo->query("
        SELECT id, name, description, address, phone, email, website,
               postal_code, city, country, sector,
               facebook_url, linkedin_url, twitter_url, instagram_url, youtube_url
        FROM organisation WHERE is_active = 1 LIMIT 1
    ");
    if ($row = $stmt->fetch()) $organisation = $row;
    if ($organisation) $pageTitle = 'Nous contacter — ' . $organisation->name;
}

require_once __DIR__ . '/inc/page-static.php';

$hasContact = $organisation && (trim($organisation->address ?? '') !== '' || trim($organisation->phone ?? '') !== '' || trim($organisation->email ?? '') !== '' || trim($organisation->website ?? '') !== '');
$hasSocial = $organisation && (trim($organisation->facebook_url ?? '') !== '' || trim($organisation->linkedin_url ?? '') !== '' || trim($organisation->twitter_url ?? '') !== '' || trim($organisation->instagram_url ?? '') !== '' || trim($organisation->youtube_url ?? '') !== '');
?>
    <section class="py-5 page-content contact-page">
        <div class="container">
            <nav aria-label="Fil d'Ariane" class="mb-4">
                <a href="<?= htmlspecialchars($baseUrl) ?>index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Accueil</a>
            </nav>
            <h1 class="section-heading mb-4">Nous contacter</h1>

            <div class="content-prose mb-4">
                <p class="lead text-muted">Coordonnées de nos bureaux. N'hésitez pas à nous écrire pour toute question.</p>
                <div class="contact-quick-links d-flex flex-wrap gap-2 mt-3 mb-4">
                    <a href="<?= htmlspecialchars($baseUrl) ?>about.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-building me-1"></i> Qui nous sommes</a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>media-resources.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-folder2 me-1"></i> Médias & ressources</a>
                    <a href="<?= htmlspecialchars($baseUrl) ?>index.php#actualites" class="btn btn-outline-primary btn-sm"><i class="bi bi-newspaper me-1"></i> Actualités</a>
                </div>
            </div>

            <?php if ($hasContact): ?>
            <div class="contact-card card border-0 shadow-sm mb-4">
                <div class="card-body p-4 p-md-5">
                    <div class="row align-items-start">
                        <div class="col-lg-8">
                            <h2 class="h5 mb-2"><?= htmlspecialchars($organisation->name) ?></h2>
                            <?php if (!empty($organisation->sector)): ?>
                            <p class="text-muted small mb-3"><?= htmlspecialchars($organisation->sector) ?></p>
                            <?php endif; ?>
                            <ul class="list-unstyled mb-0 contact-details-list">
                                <?php if (!empty($organisation->address) || !empty($organisation->postal_code) || !empty($organisation->city) || !empty($organisation->country)): ?>
                                <li class="contact-detail-item mb-3">
                                    <i class="bi bi-geo-alt contact-detail-icon"></i>
                                    <span>
                                        <?php if (!empty($organisation->address)): ?>
                                            <?= nl2br(htmlspecialchars($organisation->address)) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($organisation->postal_code) || !empty($organisation->city) || !empty($organisation->country)): ?>
                                            <?php if (!empty($organisation->address)): ?><br><?php endif; ?>
                                            <?= htmlspecialchars(trim(($organisation->postal_code ?? '') . ' ' . ($organisation->city ?? '') . (!empty($organisation->country) ? ' ' . $organisation->country : ''))) ?>
                                        <?php endif; ?>
                                    </span>
                                </li>
                                <?php endif; ?>
                                <?php if (!empty($organisation->phone)): ?>
                                <li class="contact-detail-item mb-3">
                                    <i class="bi bi-telephone contact-detail-icon"></i>
                                    <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $organisation->phone)) ?>"><?= htmlspecialchars($organisation->phone) ?></a>
                                </li>
                                <?php endif; ?>
                                <?php if (!empty($organisation->email)): ?>
                                <li class="contact-detail-item mb-3">
                                    <i class="bi bi-envelope contact-detail-icon"></i>
                                    <a href="mailto:<?= htmlspecialchars($organisation->email) ?>"><?= htmlspecialchars($organisation->email) ?></a>
                                </li>
                                <?php endif; ?>
                                <?php if (!empty($organisation->website)): ?>
                                <li class="contact-detail-item mb-0">
                                    <i class="bi bi-globe contact-detail-icon"></i>
                                    <a href="<?= htmlspecialchars($organisation->website) ?>" target="_blank" rel="noopener"><?= htmlspecialchars(parse_url($organisation->website, PHP_URL_HOST) ?: $organisation->website) ?></a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <?php if ($hasSocial): ?>
                        <div class="col-lg-4 mt-4 mt-lg-0 pt-lg-2">
                            <p class="text-muted small text-uppercase fw-semibold mb-2">Suivez-nous</p>
                            <div class="contact-social-links d-flex flex-wrap gap-2">
                                <?php if (!empty($organisation->facebook_url)): ?>
                                <a href="<?= htmlspecialchars($organisation->facebook_url) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm contact-social-btn" title="Facebook"><i class="bi bi-facebook"></i></a>
                                <?php endif; ?>
                                <?php if (!empty($organisation->linkedin_url)): ?>
                                <a href="<?= htmlspecialchars($organisation->linkedin_url) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm contact-social-btn" title="LinkedIn"><i class="bi bi-linkedin"></i></a>
                                <?php endif; ?>
                                <?php if (!empty($organisation->twitter_url)): ?>
                                <a href="<?= htmlspecialchars($organisation->twitter_url) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm contact-social-btn" title="Twitter / X"><i class="bi bi-twitter-x"></i></a>
                                <?php endif; ?>
                                <?php if (!empty($organisation->instagram_url)): ?>
                                <a href="<?= htmlspecialchars($organisation->instagram_url) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm contact-social-btn" title="Instagram"><i class="bi bi-instagram"></i></a>
                                <?php endif; ?>
                                <?php if (!empty($organisation->youtube_url)): ?>
                                <a href="<?= htmlspecialchars($organisation->youtube_url) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm contact-social-btn" title="YouTube"><i class="bi bi-youtube"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-light border text-muted">
                <i class="bi bi-info-circle me-2"></i>
                Les coordonnées de l'organisation seront affichées ici une fois renseignées dans l'administration.
            </div>
            <?php endif; ?>
        </div>
    </section>
<?php require __DIR__ . '/inc/footer.php'; ?>
