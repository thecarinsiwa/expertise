<?php
if (!isset($organisation)) $organisation = null;
if (!isset($baseUrl)) $baseUrl = '';
$footerOrgName = $organisation ? htmlspecialchars($organisation->name) : 'Expertise';
$hasFooterSocial = $organisation && (
    !empty($organisation->facebook_url) || !empty($organisation->linkedin_url) || !empty($organisation->twitter_url)
    || !empty($organisation->instagram_url) || !empty($organisation->youtube_url)
);
?>
    <footer class="site-footer">
        <div class="site-footer-accent" aria-hidden="true"></div>
        <div class="container">
            <div class="row g-4 site-footer-grid">
                <div class="col-lg-4 site-footer-brand">
                    <p class="footer-title footer-brand-name"><?= $footerOrgName ?></p>
                    <p class="footer-tagline"><?= $organisation && !empty($organisation->description) ? htmlspecialchars(mb_substr($organisation->description, 0, 140)) . (mb_strlen($organisation->description) > 140 ? '…' : '') : 'Plateforme de gestion des missions et des projets.' ?></p>
                    <?php if ($hasFooterSocial): ?>
                    <div class="footer-social" aria-label="Réseaux sociaux">
                        <?php if (!empty($organisation->facebook_url)): ?>
                            <a href="<?= htmlspecialchars($organisation->facebook_url) ?>" class="footer-social-icon" aria-label="Facebook" target="_blank" rel="noopener noreferrer"><i class="bi bi-facebook"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($organisation->twitter_url)): ?>
                            <a href="<?= htmlspecialchars($organisation->twitter_url) ?>" class="footer-social-icon" aria-label="X (Twitter)" target="_blank" rel="noopener noreferrer"><i class="bi bi-twitter-x"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($organisation->linkedin_url)): ?>
                            <a href="<?= htmlspecialchars($organisation->linkedin_url) ?>" class="footer-social-icon" aria-label="LinkedIn" target="_blank" rel="noopener noreferrer"><i class="bi bi-linkedin"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($organisation->instagram_url)): ?>
                            <a href="<?= htmlspecialchars($organisation->instagram_url) ?>" class="footer-social-icon" aria-label="Instagram" target="_blank" rel="noopener noreferrer"><i class="bi bi-instagram"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($organisation->youtube_url)): ?>
                            <a href="<?= htmlspecialchars($organisation->youtube_url) ?>" class="footer-social-icon" aria-label="YouTube" target="_blank" rel="noopener noreferrer"><i class="bi bi-youtube"></i></a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <p class="footer-title">Navigation</p>
                    <ul class="footer-links">
                        <li><a href="<?= $baseUrl ?>index.php">Accueil</a></li>
                        <li><a href="<?= $baseUrl ?>about.php">Qui nous sommes</a></li>
                        <li><a href="<?= $baseUrl ?>index.php#missions">Missions</a></li>
                        <li><a href="<?= $baseUrl ?>offres.php">Nos offres</a></li>
                        <li><a href="<?= $baseUrl ?>index.php#actualites">Actualités</a></li>
                        <li><a href="<?= $baseUrl ?>where-we-work.php">Où nous travaillons</a></li>
                        <li><a href="<?= $baseUrl ?>contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <p class="footer-title">Accès directs</p>
                    <ul class="footer-links">
                        <li><a href="<?= $baseUrl ?>missions.php">Toutes les missions</a></li>
                        <li><a href="<?= $baseUrl ?>news.php">Toutes les actualités</a></li>
                        <li><a href="<?= $baseUrl ?>client/">Espace client</a></li>
                        <li><a href="<?= $baseUrl ?>admin/">Administration</a></li>
                    </ul>
                </div>
                <div class="col-12 col-md-4 col-lg-4 site-footer-cta">
                    <p class="footer-title">Agir avec nous</p>
                    <p class="footer-cta-text">Une question, un projet ou envie de nous rejoindre ?</p>
                    <a href="<?= $baseUrl ?>contact.php" class="btn btn-footer-cta">Nous contacter</a>
                </div>
            </div>
            <div class="footer-bottom">
                <div class="footer-bottom-inner">
                    <span class="footer-copy">&copy; <?= date('Y') ?> <?= $footerOrgName ?></span>
                    <nav class="footer-legal" aria-label="Mentions légales et informations">
                        <a href="<?= $baseUrl ?>legal-notice.php">Mentions légales</a>
                        <a href="<?= $baseUrl ?>contact.php">Contact</a>
                        <a href="#top" class="footer-back-top" aria-label="Retour en haut de la page"><i class="bi bi-arrow-up"></i></a>
                    </nav>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= $baseUrl ?>inc/scripts.js"></script>
</body>
</html>
