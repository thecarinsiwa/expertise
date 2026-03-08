<?php
if (!isset($baseUrl)) $baseUrl = '';
if (!isset($organisation)) $organisation = null;
// Empêcher l'utilisation d'un chemin disque (ex. C:/laragon/...) dans les liens
if (strpos($baseUrl, ':') !== false || preg_match('#^[a-zA-Z]:#', $baseUrl)) {
    $sn = $_SERVER['SCRIPT_NAME'] ?? '';
    if ($sn !== '' && strpos($sn, ':') === false && $sn !== '/' && $sn !== '\\') {
        $baseUrl = rtrim(dirname($sn), '/\\') . '/';
    } else {
        if (!getenv('SITE_BASE_URL') && empty($_ENV['SITE_BASE_URL']) && is_file(__DIR__ . '/../config/load_env.php')) {
            require_once __DIR__ . '/../config/load_env.php';
        }
        $baseUrl = (getenv('SITE_BASE_URL') ?: ($_ENV['SITE_BASE_URL'] ?? '/expertise/'));
        if ($baseUrl !== '' && $baseUrl !== '/' && substr($baseUrl, -1) !== '/') $baseUrl .= '/';
    }
}
?>
<body id="top">
    <header class="site-header">
        <?php require __DIR__ . '/navbar.php'; ?>
    </header>

    <?php require __DIR__ . '/mega-menus.php'; ?>
