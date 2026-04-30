<?php
if (!isset($baseUrl))
    $baseUrl = '';
if (strpos($baseUrl, ':') !== false || preg_match('#^[a-zA-Z]:#', $baseUrl)) {
    $sn = $_SERVER['SCRIPT_NAME'] ?? '';
    if ($sn !== '' && strpos($sn, ':') === false && $sn !== '/' && $sn !== '\\') {
        $baseUrl = rtrim(dirname($sn), '/\\') . '/';
    } else {
        if (!getenv('SITE_BASE_URL') && empty($_ENV['SITE_BASE_URL']) && is_file(__DIR__ . '/../config/load_env.php')) {
            require_once __DIR__ . '/../config/load_env.php';
        }
        $baseUrl = (getenv('SITE_BASE_URL') ?: ($_ENV['SITE_BASE_URL'] ?? '/expertise/'));
        if ($baseUrl !== '' && $baseUrl !== '/' && substr($baseUrl, -1) !== '/')
            $baseUrl .= '/';
    }
}
if (!isset($organisation))
    $organisation = null;
if (!function_exists('get_site_logo_url')) {
    require_once __DIR__ . '/asset_url.php';
}
$siteLogoUrl = get_site_logo_url($baseUrl, $organisation);
$siteLogoAlt = $organisation ? htmlspecialchars($organisation->name) : 'EXPERTISE';
?>
<nav class="navbar navbar-expand-lg navbar-main navbar-light">
    <div class="container">
        <a class="navbar-brand" href="<?= $baseUrl ?>index">
            <img src="<?= htmlspecialchars($siteLogoUrl) ?>" alt="<?= $siteLogoAlt ?>">
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain"
            aria-controls="navbarMain" aria-expanded="false" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMain">
            <div class="d-lg-none mobile-menu-header">
                <button type="button" class="btn-close-mobile" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-label="Fermer"></button>
            </div>
            <ul class="navbar-nav nav-center mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link nav-mega-trigger" href="<?= $baseUrl ?>about.php" data-mega="mega-about">
                        <span class="nav-link-text">Qui nous sommes</span>
                        <i class="bi bi-chevron-down nav-link-icon d-none d-lg-inline-flex" aria-hidden="true"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-mega-trigger" href="<?= $baseUrl ?>missions.php" data-mega="mega-what">
                        <span class="nav-link-text">Notre travail</span>
                        <i class="bi bi-chevron-down nav-link-icon d-none d-lg-inline-flex" aria-hidden="true"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-mega-trigger" href="<?= $baseUrl ?>projects.php" data-mega="mega-where">
                        <span class="nav-link-text">Nos projets ASBL</span>
                        <i class="bi bi-chevron-down nav-link-icon d-none d-lg-inline-flex" aria-hidden="true"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $baseUrl ?>offres.php">
                        <span class="nav-link-text">Nos offres</span>
                    </a>
                </li>
            </ul>
            <div class="navbar-nav nav-right ms-auto">
                <a class="nav-link d-lg-inline-flex" href="#" aria-label="Recherche">
                    <i class="bi bi-search"></i>
                    <span class="ms-2 d-lg-none">Recherche</span>
                </a>
                <span class="nav-link" style="cursor:pointer;">
                    <span class="nav-link-text">FR</span>
                    <i class="bi bi-chevron-down nav-link-icon small d-none d-lg-inline-flex" aria-hidden="true"></i>
                </span>
                <?php if (!empty($_SESSION['client_logged_in'])): ?>
                    <a class="btn btn-read-more nav-link-icon-only d-none d-lg-inline-flex" href="<?= $baseUrl ?>client/index.php" aria-label="Mon espace" title="Mon espace"><i class="bi bi-person-circle"></i></a>
                    <a class="btn btn-read-more nav-link-icon-only ms-2 d-none d-lg-inline-flex" href="<?= $baseUrl ?>client/logout.php" aria-label="Déconnexion" title="Déconnexion"><i class="bi bi-box-arrow-right"></i></a>
                    <a class="nav-link d-lg-none" href="<?= $baseUrl ?>client/index.php">Mon espace</a>
                    <a class="nav-link d-lg-none" href="<?= $baseUrl ?>client/logout.php">Déconnexion</a>
                <?php elseif (!empty($_SESSION['admin_logged_in'])): ?>
                    <a class="btn btn-read-more" href="<?= $baseUrl ?>admin/index.php">Dashboard</a>
                <?php else: ?>
                    <div class="ms-lg-3 mt-3 mt-lg-0 px-3 px-lg-0">
                        <a class="btn btn-read-more d-block d-lg-inline-block" href="<?= $baseUrl ?>client/login.php">Se connecter</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>