<?php
if (!isset($pageTitle))
    $pageTitle = 'Mon espace – Expertise';
require_once __DIR__ . '/auth.php';
header('Content-Type: text/html; charset=UTF-8');

$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$baseUrl = ($scriptDir === '/' || $scriptDir === '\\') ? '' : rtrim($scriptDir, '/') . '/';
$siteRoot = preg_replace('#/client.*$#', '/', $baseUrl) ?: '../';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --client-bg: #f0f2f5;
            --client-dark: #1D1C3E;
            --client-accent: #FFC107;
            --client-accent-h: #FFB300;
            --client-card: #fff;
            --client-text: #1a1a1a;
            --client-muted: #6c757d;
            --client-font: "Montserrat", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        * { font-family: var(--client-font); box-sizing: border-box; }
        body { background: var(--client-bg); min-height: 100vh; margin: 0; }
        .client-nav {
            background: var(--client-dark);
            color: #fff;
            padding: .75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .5rem;
        }
        .client-nav-brand {
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: .02em;
        }
        .client-nav-brand a { color: #fff; text-decoration: none; }
        .client-nav-brand a:hover { color: var(--client-accent); }
        .client-nav-links {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .client-nav-links a {
            color: rgba(255, 255, 255, .9);
            text-decoration: none;
            font-size: .9rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }
        .client-nav-links a:hover { color: var(--client-accent); }
        .client-main {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }
        .client-card {
            background: var(--client-card);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .08);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .client-card h1, .client-card h2 {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--client-dark);
            margin-bottom: .5rem;
        }
        .client-card p { color: var(--client-muted); margin-bottom: 1rem; }
        .client-footer {
            text-align: center;
            font-size: .8rem;
            color: var(--client-muted);
            padding: 1.5rem;
        }
    </style>
</head>

<body>
    <nav class="client-nav">
        <div class="client-nav-brand">
            <a href="<?= htmlspecialchars($siteRoot) ?>">Expertise</a>
        </div>
        <div class="client-nav-links">
            <a href="index.php"><i class="bi bi-house-door"></i> Mon espace</a>
            <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
        </div>
    </nav>
    <main class="client-main">
