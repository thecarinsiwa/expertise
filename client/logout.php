<?php
/**
 * Déconnexion – Espace client Expertise
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$clientKeys = ['client_logged_in', 'client_id', 'client_email', 'client_name'];
foreach ($clientKeys as $k) {
    unset($_SESSION[$k]);
}
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $p['path'],
        $p['domain'],
        $p['secure'],
        $p['httponly']
    );
}
session_destroy();

header('Location: login.php?logout=1');
exit;
