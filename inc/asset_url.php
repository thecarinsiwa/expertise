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
