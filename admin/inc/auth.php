<?php
/**
 * Gardien d'authentification admin (RBAC).
 * À inclure EN PREMIER dans chaque page protégée.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['admin_logged_in'])) {
    // Charger la config pour obtenir le chemin web de base (éviter /admin/login.php au lieu de /expertise/admin/login.php)
    if (!function_exists('admin_base_url')) {
        function admin_base_url() {
            $sn = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
            if (preg_match('#^/([^/]+)/#', $sn, $m) && strpos($m[1], '.') === false) {
                return '/' . $m[1] . '/';
            }
            if (!getenv('SITE_BASE_URL') && empty($_ENV['SITE_BASE_URL'])) {
                $envFile = dirname(__DIR__, 2) . '/config/load_env.php';
                if (is_file($envFile)) require_once $envFile;
            }
            $base = getenv('SITE_BASE_URL') ?: ($_ENV['SITE_BASE_URL'] ?? '/expertise/');
            return ($base !== '' && $base !== '/') ? rtrim($base, '/') . '/' : '/expertise/';
        }
    }
    $adminBase = admin_base_url() . 'admin/';
    $redirect = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($redirect, ':') !== false) $redirect = $adminBase;
    $loginUrl = $adminBase . 'login.php' . ($redirect ? '?redirect=' . urlencode($redirect) : '');
    header('Location: ' . $loginUrl);
    exit;
}

/**
 * Retourne la liste des codes de permission de l'utilisateur (via ses rôles, RBAC).
 * Lit depuis la session si présente, sinon charge depuis la DB et met en cache.
 * @return string[]
 */
function get_admin_permissions() {
    if (!empty($_SESSION['admin_permissions']) && is_array($_SESSION['admin_permissions'])) {
        return $_SESSION['admin_permissions'];
    }
    $userId = isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : 0;
    if ($userId <= 0) {
        return [];
    }
    require_once __DIR__ . '/db.php';
    $perms = [];
    if (isset($pdo) && $pdo) {
        $stmt = $pdo->prepare("
            SELECT DISTINCT p.code
            FROM permission p
            JOIN role_permission rp ON p.id = rp.permission_id
            JOIN user_role ur ON ur.role_id = rp.role_id
            WHERE ur.user_id = ?
        ");
        $stmt->execute([$userId]);
        while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $perms[] = $row->code;
        }
        $_SESSION['admin_permissions'] = $perms;
    }
    return $perms;
}

/**
 * Indique si l'utilisateur a la permission donnée (via ses rôles ou rôle superadmin).
 * @param string $code Code de la permission (ex. admin.organisations.view)
 * @return bool
 */
function has_permission($code) {
    if (!empty($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'superadmin') {
        return true;
    }
    return in_array($code, get_admin_permissions(), true);
}

/**
 * Exige la permission ; redirige vers 403 en cas d'absence.
 * @param string $code Code de la permission
 */
function require_permission($code) {
    if (has_permission($code)) {
        return;
    }
    if (!function_exists('admin_base_url')) {
        function admin_base_url() {
            $sn = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
            if (preg_match('#^/([^/]+)/#', $sn, $m) && strpos($m[1], '.') === false) {
                return '/' . $m[1] . '/';
            }
            if (!getenv('SITE_BASE_URL') && empty($_ENV['SITE_BASE_URL'])) {
                $envFile = dirname(__DIR__, 2) . '/config/load_env.php';
                if (is_file($envFile)) require_once $envFile;
            }
            $base = getenv('SITE_BASE_URL') ?: ($_ENV['SITE_BASE_URL'] ?? '/expertise/');
            return ($base !== '' && $base !== '/') ? rtrim($base, '/') . '/' : '/expertise/';
        }
    }
    header('Location: ' . admin_base_url() . 'admin/403.php');
    exit;
}
