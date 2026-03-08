<?php
/**
 * Encodage réversible des IDs pour les URLs du site public (paramètre h).
 * Les liens générés n'incluent pas l'extension .php (ex. mission?h=xxx).
 *
 * Usage : require_once __DIR__ . '/inc/url_hash.php';
 *         $url = public_entity_url($baseUrl, 'mission', $id);
 *         $id = decode_id($_GET['h'] ?? '');
 */
if (!function_exists('url_hash_get_secret')) {
    function url_hash_get_secret() {
        static $secret = null;
        if ($secret === null) {
            if (!getenv('APP_URL_SECRET') && empty($_ENV['APP_URL_SECRET'])) {
                $loadEnv = dirname(__DIR__) . '/config/load_env.php';
                if (is_file($loadEnv)) {
                    require_once $loadEnv;
                }
            }
            $raw = getenv('APP_URL_SECRET') ?: ($_ENV['APP_URL_SECRET'] ?? '');
            $secret = $raw !== '' ? $raw : 'dev-default-change-in-production';
        }
        return $secret;
    }
}

if (!function_exists('encode_id')) {
    /**
     * Encode un ID entier en token opaque (URL-safe).
     * @param int $id
     * @return string
     */
    function encode_id($id) {
        $id = (int) $id;
        if ($id <= 0) {
            return '';
        }
        $secret = url_hash_get_secret();
        $key = substr(hash('sha256', $secret, true), 0, 16);
        $iv = substr(hash('sha256', $secret . 'iv', true), 0, 16);
        $cipher = openssl_encrypt((string) $id, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
        if ($cipher === false) {
            return '';
        }
        $b64 = base64_encode($cipher);
        return strtr($b64, '+/', '-_');
    }
}

if (!function_exists('decode_id')) {
    /**
     * Décode un token en ID entier, ou null si invalide.
     * @param string $token
     * @return int|null
     */
    function decode_id($token) {
        if (!is_string($token) || $token === '') {
            return null;
        }
        $token = strtr($token, '-_', '+/');
        $raw = base64_decode($token, true);
        if ($raw === false || $raw === '') {
            return null;
        }
        $secret = url_hash_get_secret();
        $key = substr(hash('sha256', $secret, true), 0, 16);
        $iv = substr(hash('sha256', $secret . 'iv', true), 0, 16);
        $dec = openssl_decrypt($raw, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
        if ($dec === false || $dec === '') {
            return null;
        }
        $id = (int) $dec;
        return $id > 0 ? $id : null;
    }
}

if (!function_exists('public_entity_url')) {
    /**
     * Construit l'URL publique d'une fiche (sans extension .php).
     * @param string $baseUrl ex. '' ou 'expertise/'
     * @param string $page nom de la page sans .php : 'mission', 'announcement', 'project', 'offre', 'teams'
     * @param int $id
     * @return string
     */
    function public_entity_url($baseUrl, $page, $id) {
        $base = $baseUrl !== '' ? rtrim($baseUrl, '/') . '/' : '';
        $token = encode_id($id);
        if ($token === '') {
            return $base . $page;
        }
        return $base . $page . '?h=' . urlencode($token);
    }
}
