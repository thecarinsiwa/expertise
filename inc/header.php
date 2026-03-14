<?php
if (!isset($baseUrl)) $baseUrl = '';
if (!isset($organisation)) $organisation = null;

// Charger .env une fois pour avoir SITE_BASE_URL (navbar, footer, assets en dépendent)
$loadEnvPath = __DIR__ . '/../config/load_env.php';
if (empty($_ENV['SITE_BASE_URL']) && !getenv('SITE_BASE_URL') && is_file($loadEnvPath)) {
    try {
        require_once $loadEnvPath;
    } catch (Throwable $e) {
        // Ne pas casser la page si .env ou load_env échoue (ex. permissions)
    }
}
$envBase = isset($_ENV['SITE_BASE_URL']) ? (string) $_ENV['SITE_BASE_URL'] : (getenv('SITE_BASE_URL') ?: null);
if ($envBase !== null && $envBase !== '' && $envBase !== false) {
    $envBase = trim($envBase);
    if ($envBase === '/' || rtrim($envBase, '/') === '') {
        $baseUrl = '';
    } else {
        $baseUrl = rtrim($envBase, '/') . '/';
    }
} else {
    // Pas de SITE_BASE_URL : garder la base de la page ou corriger si chemin disque
    if (strpos((string) $baseUrl, ':') !== false || preg_match('#^[a-zA-Z]:#', (string) $baseUrl)) {
        $sn = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
        if ($sn !== '' && strpos($sn, ':') === false && $sn !== '/' && $sn !== '\\') {
            $baseUrl = rtrim(dirname($sn), '/\\') . '/';
        } else {
            $baseUrl = '/expertise/';
            if (substr($baseUrl, -1) !== '/') $baseUrl .= '/';
        }
    }
}
$sn = str_replace('\\', '/', isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '');
// Dériver la base réelle depuis l'URL quand le site est dans un sous-dossier (ex. /expertise/...) pour éviter liens vers /client/login.php au lieu de /expertise/client/login.php
$baseFromScript = null;
if (preg_match('#^/([^/]+)(/|$)#', $sn, $m)) {
    $firstSegment = $m[1];
    if (!in_array($firstSegment, ['client', 'admin'], true) && strpos($firstSegment, '.') === false) {
        $baseFromScript = '/' . $firstSegment . '/';
    }
}
if ($baseFromScript !== null) {
    $baseUrl = $baseFromScript;
}
// Base URL racine du site (sans /client/ ou /admin/) pour les liens "Mon espace" / "Espace Utilisateur"
$siteBaseUrl = preg_replace('#/(client|admin)/?$#', '/', rtrim($baseUrl, '/')) . '/';
// Sur les pages client/ ou admin/, forcer $baseUrl = racine du site pour que les liens et assets pointent vers la bonne base
if (preg_match('#/(client|admin)(/|$)#', $sn)) {
    $baseFromScript = preg_replace('#/(client|admin)(/.*)?$#', '/', $sn);
    if ($baseFromScript !== '' && $baseFromScript !== '/') {
        $siteBaseUrl = rtrim($baseFromScript, '/') . '/';
    }
    $baseUrl = $siteBaseUrl;
}
?>
<body id="top">
    <header class="site-header">
        <?php require __DIR__ . '/navbar.php'; ?>
    </header>

    <?php require __DIR__ . '/mega-menus.php'; ?>
