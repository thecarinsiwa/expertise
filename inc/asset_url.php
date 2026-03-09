<?php
/**
 * URL des assets (photos de couverture, logos, images dans le contenu) côté client.
 * Normalise les chemins stockés en BDD (uploads/... ou ../uploads/...)
 * pour qu’ils fonctionnent depuis les pages client (index, mission, announcement, etc.).
 *
 * Usage : require_once __DIR__ . '/inc/asset_url.php';
 *        $url = client_asset_url($baseUrl, $storedPath);
 *        $html = client_rewrite_uploads_in_html($baseUrl, $htmlContent);
 */
if (!function_exists('client_asset_url')) {
    function client_asset_url($baseUrl, $path) {
        if ($path === null || $path === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        $path = preg_replace('#^\.\./#', '', $path);
        $path = ltrim($path, '/');
        $base = $baseUrl !== '' ? rtrim($baseUrl, '/') . '/' : '';
        return $base . $path;
    }
}

if (!function_exists('client_rewrite_uploads_in_html')) {
    function client_rewrite_uploads_in_html($baseUrl, $html) {
        if ($html === null || $html === '') {
            return '';
        }
        $uploadsBase = ($baseUrl !== '' ? rtrim($baseUrl, '/') . '/' : '') . 'uploads/';
        $html = preg_replace('~(src|href)=(["\'])(?:\\.\\./)*uploads/~', '$1=$2' . $uploadsBase, $html);
        $html = preg_replace('~(src|href)=(["\'])/uploads/~', '$1=$2' . $uploadsBase, $html);
        return $html;
    }
}

/**
 * Logo du site : organisation active (logo) ou fallback assets/images/logo.jpg.
 * @param string $baseUrl Base URL du site
 * @param object|null $organisation Objet organisation (optionnel, avec ->logo)
 * @return string URL absolue du logo
 */
if (!function_exists('get_site_logo_url')) {
    function get_site_logo_url($baseUrl, $organisation = null) {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $path = '';
        if ($organisation && !empty($organisation->logo)) {
            $path = $organisation->logo;
        } else {
            if (isset($GLOBALS['pdo']) && $GLOBALS['pdo']) {
                $stmt = $GLOBALS['pdo']->query("SELECT logo FROM organisation WHERE is_active = 1 LIMIT 1");
                if ($stmt && ($row = $stmt->fetch(PDO::FETCH_OBJ)) && !empty($row->logo)) {
                    $path = $row->logo;
                }
            }
        }
        if ($path === '') {
            $path = 'assets/images/logo.jpg';
        }
        $cached = client_asset_url($baseUrl, $path);
        return $cached;
    }
}

/**
 * Favicon du site : organisation active (favicon) ou fallback assets/images/favicon.ico, sinon logo.
 * @param string $baseUrl Base URL du site
 * @param object|null $organisation Objet organisation (optionnel, avec ->favicon ou ->logo)
 * @return string URL absolue du favicon
 */
if (!function_exists('get_site_favicon_url')) {
    function get_site_favicon_url($baseUrl, $organisation = null) {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $path = '';
        if ($organisation && !empty($organisation->favicon)) {
            $path = $organisation->favicon;
        } elseif ($organisation && !empty($organisation->logo)) {
            $path = $organisation->logo;
        } else {
            if (isset($GLOBALS['pdo']) && $GLOBALS['pdo']) {
                $stmt = $GLOBALS['pdo']->query("SELECT favicon, logo FROM organisation WHERE is_active = 1 LIMIT 1");
                if ($stmt && ($row = $stmt->fetch(PDO::FETCH_OBJ))) {
                    if (!empty($row->favicon)) {
                        $path = $row->favicon;
                    } elseif (!empty($row->logo)) {
                        $path = $row->logo;
                    }
                }
            }
        }
        if ($path === '') {
            $faviconPath = __DIR__ . '/../assets/images/favicon.ico';
            if (file_exists($faviconPath)) {
                $path = 'assets/images/favicon.ico';
            } else {
                $path = ''; // will use logo fallback below
            }
        }
        if ($path === '') {
            $path = get_site_logo_url($baseUrl, $organisation);
            $cached = $path;
            return $cached;
        }
        $cached = client_asset_url($baseUrl, $path);
        return $cached;
    }
}
