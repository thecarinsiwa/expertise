<?php
if (!isset($pageTitle)) $pageTitle = 'Expertise';
$baseUrl = isset($baseUrl) ? $baseUrl : '';
if (isset($pdo) && $pdo) {
    $GLOBALS['pdo'] = $pdo;
}
if (!function_exists('get_site_favicon_url')) {
    require_once __DIR__ . '/asset_url.php';
}
$faviconUrl = get_site_favicon_url($baseUrl, $organisation ?? null);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="icon" href="<?= htmlspecialchars($faviconUrl) ?>" sizes="any">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= $baseUrl ?>inc/style.css" rel="stylesheet">
</head>
