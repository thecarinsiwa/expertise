<?php
/**
 * Expertise API – Vérification de la base de données
 * URL: http://localhost/expertise/api/
 */

// ─── Configuration de la connexion ──────────────────────────────────────────
$DB_HOST = 'localhost';
$DB_PORT = 3306;
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'expertise';
$DEFAULT_SCHEMA_FILE = '/database/schema.sql';

// ─── Liste des tables attendues dans le schéma ───────────────────────────────
$EXPECTED_TABLES = [
    // 1. Organizational
    'organisation' => 'Organisationnel',
    'organisational_entity' => 'Organisationnel',
    'department' => 'Organisationnel',
    'service' => 'Organisationnel',
    'unit' => 'Organisationnel',
    'position' => 'Organisationnel',
    'organisational_hierarchy' => 'Organisationnel',
    // 2. User & Staff
    'user' => 'Utilisateurs & Staff',
    'role' => 'Utilisateurs & Staff',
    'permission' => 'Utilisateurs & Staff',
    'role_permission' => 'Utilisateurs & Staff',
    'profile' => 'Utilisateurs & Staff',
    'user_role' => 'Utilisateurs & Staff',
    'staff' => 'Utilisateurs & Staff',
    'assignment' => 'Utilisateurs & Staff',
    'contract' => 'Utilisateurs & Staff',
    'position_history' => 'Utilisateurs & Staff',
    'user_group' => 'Utilisateurs & Staff',
    'user_group_member' => 'Utilisateurs & Staff',
    // 3. Project
    'portfolio' => 'Gestion de projets',
    'programme' => 'Gestion de projets',
    'project' => 'Gestion de projets',
    'project_phase' => 'Gestion de projets',
    'task' => 'Gestion de projets',
    'sub_task' => 'Gestion de projets',
    'deliverable' => 'Gestion de projets',
    'milestone' => 'Gestion de projets',
    'project_budget' => 'Gestion de projets',
    'project_resource' => 'Gestion de projets',
    'project_assignment' => 'Gestion de projets',
    // 4. Mission
    'mission_type' => 'Gestion de missions',
    'mission_status' => 'Gestion de missions',
    'mission' => 'Gestion de missions',
    'mission_order' => 'Gestion de missions',
    'objective' => 'Gestion de missions',
    'mission_plan' => 'Gestion de missions',
    'mission_report' => 'Gestion de missions',
    'mission_expense' => 'Gestion de missions',
    'mission_assignment' => 'Gestion de missions',
    // 5. Communication
    'channel' => 'Communication',
    'conversation' => 'Communication',
    'message' => 'Communication',
    'notification' => 'Communication',
    'announcement' => 'Communication',
    'comment' => 'Communication',
    'attachment' => 'Communication',
    'communication_history' => 'Communication',
    'channel_member' => 'Communication',
    'conversation_participant' => 'Communication',
    // 6. Security
    'session' => 'Sécurité',
    'activity_log' => 'Sécurité',
    'audit' => 'Sécurité',
    'authentication' => 'Sécurité',
    'authorization' => 'Sécurité',
    // 7. Document
    'document_category' => 'Documents',
    'document' => 'Documents',
    'document_version' => 'Documents',
    'archiving' => 'Documents',
    // 8. Planning & Tracking
    'calendar' => 'Performance',
    'event' => 'Performance',
    'schedule' => 'Performance',
    'performance_indicator' => 'Performance'
];

// ─── Action : initialiser la DB ─────────────────────────────────────────────
$action = $_GET['action'] ?? '';
$actionMsg = '';
$actionMsgType = '';

if ($action === 'init_db') {
    try {
        $pdo = new PDO("mysql:host={$DB_HOST};port={$DB_PORT};charset=utf8mb4", $DB_USER, $DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        // Créer la base si elle n'existe pas
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$DB_NAME}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$DB_NAME}`");
        // Charger et exécuter le schéma
        $schemaFile = dirname(__DIR__) . $DEFAULT_SCHEMA_FILE;
        if (file_exists($schemaFile)) {
            $sql = file_get_contents($schemaFile);

            // Exécuter le SQL instruction par instruction pour éviter les erreurs PDO::exec avec plusieurs statements
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($statements as $stmt) {
                $pdo->exec($stmt);
            }

            $actionMsg = 'Base de données et schéma initialisés avec succès !';
            $actionMsgType = 'success';
        } else {
            $actionMsg = "Fichier schéma introuvable : " . htmlspecialchars($DEFAULT_SCHEMA_FILE);
            $actionMsgType = 'warning';
        }
    } catch (PDOException $e) {
        $actionMsg = 'Erreur lors de l\'initialisation : ' . htmlspecialchars($e->getMessage());
        $actionMsgType = 'error';
    }
}

// ─── Vérifications ───────────────────────────────────────────────────────────
$checks = [];

// 1. Connexion au serveur MySQL
$mysqlOk = false;
$mysqlErr = '';
try {
    $pdoServer = new PDO(
        "mysql:host={$DB_HOST};port={$DB_PORT};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
    );
    $mysqlOk = true;
    $mysqlVersion = $pdoServer->query('SELECT VERSION()')->fetchColumn();
} catch (PDOException $e) {
    $mysqlErr = $e->getMessage();
}

// 2. Existence de la base de données
$dbExists = false;
$dbErr = '';
$tableRows = [];
$foundCount = 0;
$missingTables = [];

if ($mysqlOk) {
    try {
        $stmt = $pdoServer->prepare(
            "SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?"
        );
        $stmt->execute([$DB_NAME]);
        $dbExists = (bool) $stmt->fetchColumn();

        // 3. Tables
        if ($dbExists) {
            $pdoDb = new PDO(
                "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4",
                $DB_USER,
                $DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $existingTables = $pdoDb->query(
                "SELECT TABLE_NAME, TABLE_ROWS, DATA_LENGTH + INDEX_LENGTH AS size_bytes
                 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = '{$DB_NAME}'
                 ORDER BY TABLE_NAME"
            )->fetchAll(PDO::FETCH_ASSOC);

            $existingMap = [];
            foreach ($existingTables as $t) {
                $existingMap[$t['TABLE_NAME']] = $t;
            }

            foreach ($EXPECTED_TABLES as $tbl => $module) {
                $exists = isset($existingMap[$tbl]);
                if ($exists) {
                    $foundCount++;
                    $tableRows[$tbl] = [
                        'module' => $module,
                        'exists' => true,
                        'rows' => (int) ($existingMap[$tbl]['TABLE_ROWS'] ?? 0),
                        'size' => (int) ($existingMap[$tbl]['size_bytes'] ?? 0),
                    ];
                } else {
                    $missingTables[] = $tbl;
                    $tableRows[$tbl] = ['module' => $module, 'exists' => false, 'rows' => 0, 'size' => 0];
                }
            }
        }
    } catch (PDOException $e) {
        $dbErr = $e->getMessage();
    }
}

$totalExpected = count($EXPECTED_TABLES);
$schemaOk = $dbExists && $foundCount === $totalExpected;

// ─── Helper : taille lisible ─────────────────────────────────────────────────
function humanSize(int $bytes): string
{
    if ($bytes < 1024)
        return "{$bytes} B";
    if ($bytes < 1048576)
        return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 2) . ' MB';
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Vérification BDD – Expertise API</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg: #0d1117;
            --bg2: #161b22;
            --bg3: #21262d;
            --border: rgba(255, 255, 255, 0.08);
            --text: #e6edf3;
            --muted: #8b949e;
            --accent: #1D1C3E;
            --yellow: #FFC107;
            --green: #3fb950;
            --red: #f85149;
            --orange: #d29922;
            --blue: #58a6ff;
            --radius: 12px;
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            --font: 'Montserrat', system-ui, sans-serif;
        }

        body {
            font-family: var(--font);
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding: 2rem 1rem 4rem;
        }

        /* ── Page header ── */
        .page-header {
            max-width: 960px;
            margin: 0 auto 2.5rem;
            display: flex;
            align-items: flex-start;
            gap: 1.25rem;
        }

        .header-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--yellow) 0%, #FF9800 100%);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            color: var(--accent);
            flex-shrink: 0;
            box-shadow: 0 4px 16px rgba(255, 193, 7, 0.3);
        }

        .header-text h1 {
            font-size: 1.6rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #fff;
        }

        .header-text p {
            color: var(--muted);
            font-size: 0.9rem;
            margin-top: .25rem;
        }

        .header-breadcrumb {
            font-size: 0.78rem;
            color: var(--muted);
            margin-bottom: .35rem;
        }

        .header-breadcrumb a {
            color: var(--yellow);
            text-decoration: none;
        }

        /* ── Action message ── */
        .action-msg {
            max-width: 960px;
            margin: 0 auto 1.5rem;
            padding: .9rem 1.2rem;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            gap: .75rem;
            font-size: .9rem;
            font-weight: 600;
        }

        .action-msg.success {
            background: rgba(63, 185, 80, .15);
            border: 1px solid rgba(63, 185, 80, .4);
            color: #3fb950;
        }

        .action-msg.warning {
            background: rgba(210, 153, 34, .15);
            border: 1px solid rgba(210, 153, 34, .4);
            color: var(--orange);
        }

        .action-msg.error {
            background: rgba(248, 81, 73, .15);
            border: 1px solid rgba(248, 81, 73, .4);
            color: var(--red);
        }

        /* ── Grid ── */
        .grid {
            max-width: 960px;
            margin: 0 auto;
            display: grid;
            gap: 1.25rem;
        }

        .grid-2 {
            grid-template-columns: repeat(2, 1fr);
        }

        @media (max-width: 640px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
        }

        /* ── Card ── */
        .card {
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: border-color .2s;
        }

        .card:hover {
            border-color: rgba(255, 255, 255, .16);
        }

        .card-title {
            font-size: .75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .card-title i {
            font-size: .95rem;
        }

        /* ── Status badge ── */
        .status {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .35rem .8rem;
            border-radius: 99px;
            font-size: .82rem;
            font-weight: 700;
            letter-spacing: .02em;
        }

        .status.ok {
            background: rgba(63, 185, 80, .15);
            color: var(--green);
        }

        .status.fail {
            background: rgba(248, 81, 73, .15);
            color: var(--red);
        }

        .status.warn {
            background: rgba(210, 153, 34, .15);
            color: var(--orange);
        }

        .status .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
            display: inline-block;
        }

        .status.ok .dot {
            box-shadow: 0 0 6px var(--green);
        }

        .status.fail .dot {
            box-shadow: 0 0 6px var(--red);
        }

        /* ── Info row ── */
        .info-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .6rem 0;
            border-bottom: 1px solid var(--border);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-size: .85rem;
            color: var(--muted);
        }

        .info-value {
            font-size: .85rem;
            font-weight: 600;
            color: var(--text);
            font-family: 'Courier New', monospace;
        }

        /* ── Big stat ── */
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: #fff;
            line-height: 1;
        }

        .stat-label {
            font-size: .8rem;
            color: var(--muted);
            margin-top: .3rem;
        }

        /* ── Progress bar ── */
        .prog-track {
            background: var(--bg3);
            border-radius: 99px;
            height: 10px;
            overflow: hidden;
            margin: .75rem 0 .5rem;
        }

        .prog-fill {
            height: 100%;
            border-radius: 99px;
            background: linear-gradient(90deg, var(--green), #26a641);
            transition: width 1s ease;
        }

        .prog-fill.partial {
            background: linear-gradient(90deg, var(--orange), #e3b341);
        }

        .prog-fill.none {
            background: linear-gradient(90deg, var(--red), #da3633);
        }

        /* ── Table ── */
        .tbl-wrap {
            max-width: 960px;
            margin: 1.25rem auto 0;
            overflow-x: auto;
        }

        .tbl-header {
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: var(--radius) var(--radius) 0 0;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .75rem;
        }

        .tbl-header h2 {
            font-size: 1rem;
            font-weight: 700;
            color: #fff;
        }

        .tbl-search {
            background: var(--bg3);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: .45rem .9rem;
            color: var(--text);
            font-size: .85rem;
            font-family: var(--font);
            outline: none;
            width: 220px;
        }

        .tbl-search:focus {
            border-color: var(--yellow);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--bg2);
            font-size: .85rem;
            border: 1px solid var(--border);
            border-top: none;
        }

        thead th {
            background: var(--bg3);
            color: var(--muted);
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            padding: .75rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background .15s;
        }

        tbody tr:hover {
            background: var(--bg3);
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        td {
            padding: .7rem 1rem;
            vertical-align: middle;
        }

        .tbl-footer {
            background: var(--bg2);
            border: 1px solid var(--border);
            border-top: none;
            border-radius: 0 0 var(--radius) var(--radius);
            padding: .75rem 1.5rem;
            font-size: .8rem;
            color: var(--muted);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .5rem;
        }

        /* ── Tag ── */
        .tag {
            display: inline-block;
            padding: .2rem .6rem;
            border-radius: 6px;
            font-size: .72rem;
            font-weight: 600;
            background: var(--bg3);
            color: var(--muted);
        }

        /* ── Table status icons ── */
        .tbl-ok {
            color: var(--green);
            font-size: 1rem;
        }

        .tbl-miss {
            color: var(--red);
            font-size: 1rem;
        }

        /* ── Button ── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .7rem 1.5rem;
            border-radius: 10px;
            font-family: var(--font);
            font-size: .88rem;
            font-weight: 700;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all .2s;
        }

        .btn-primary {
            background: var(--yellow);
            color: var(--accent);
        }

        .btn-primary:hover {
            background: #FFB300;
            box-shadow: 0 4px 16px rgba(255, 193, 7, .35);
        }

        .btn-ghost {
            background: var(--bg3);
            color: var(--text);
            border: 1px solid var(--border);
        }

        .btn-ghost:hover {
            background: var(--bg2);
            border-color: rgba(255, 255, 255, .2);
        }

        .btn-danger {
            background: rgba(248, 81, 73, .15);
            color: var(--red);
            border: 1px solid rgba(248, 81, 73, .35);
        }

        .btn-danger:hover {
            background: rgba(248, 81, 73, .25);
        }

        /* ── Actions bar ── */
        .actions-bar {
            max-width: 960px;
            margin: 1.5rem auto 0;
            display: flex;
            gap: .75rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .actions-bar .sep {
            color: var(--border);
        }

        /* ── Module group color dots ── */
        .mod-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            display: inline-block;
            margin-right: .45rem;
            flex-shrink: 0;
        }

        /* ── Error box ── */
        .err-box {
            background: rgba(248, 81, 73, .08);
            border: 1px solid rgba(248, 81, 73, .3);
            border-radius: 8px;
            padding: .75rem 1rem;
            margin-top: .75rem;
            font-size: .8rem;
            color: var(--red);
            font-family: 'Courier New', monospace;
        }

        /* ── Timestamp ── */
        .timestamp {
            font-size: .75rem;
            color: var(--muted);
        }

        /* ── Pulse animation ── */
        @keyframes pulse {

            0%,
            100% {
                opacity: 1
            }

            50% {
                opacity: .4
            }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        /* ── Responsive ── */
        @media (max-width:640px) {
            .page-header {
                flex-direction: column;
            }

            .tbl-search {
                width: 100%;
            }

            .actions-bar {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>

    <!-- ══════════════════════════════════════════════
     EN-TÊTE
══════════════════════════════════════════════ -->
    <header class="page-header">
        <div class="header-icon"><i class="bi bi-database-check"></i></div>
        <div class="header-text">
            <p class="header-breadcrumb">
                <a href="../">Expertise</a> › <a href="./">API</a> › Vérification Base de données
            </p>
            <h1>Vérification de la Base de données</h1>
            <p>Contrôle de la connexion MySQL et de l'intégrité du schéma <strong>
                    <?= htmlspecialchars($DB_NAME) ?>
                </strong></p>
        </div>
    </header>

    <!-- ══════════════════════════════════════════════
     MESSAGE D'ACTION
══════════════════════════════════════════════ -->
    <?php if ($actionMsg): ?>
        <div class="action-msg <?= $actionMsgType ?>">
            <i
                class="bi bi-<?= $actionMsgType === 'success' ? 'check-circle-fill' : ($actionMsgType === 'error' ? 'x-circle-fill' : 'exclamation-triangle-fill') ?>"></i>
            <?= $actionMsg ?>
        </div>
    <?php endif; ?>

    <!-- ══════════════════════════════════════════════
     CARTES DE STATUT
══════════════════════════════════════════════ -->
    <div class="grid grid-2">

        <!-- Connexion MySQL -->
        <div class="card">
            <div class="card-title"><i class="bi bi-server"></i> Connexion MySQL</div>
            <?php if ($mysqlOk): ?>
                <span class="status ok"><span class="dot"></span> Connecté</span>
                <div style="margin-top:1.25rem;">
                    <div class="info-row">
                        <span class="info-label">Hôte</span>
                        <span class="info-value">
                            <?= htmlspecialchars($DB_HOST) ?>:
                            <?= $DB_PORT ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Version MySQL</span>
                        <span class="info-value">
                            <?= htmlspecialchars($mysqlVersion ?? '—') ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Utilisateur</span>
                        <span class="info-value">
                            <?= htmlspecialchars($DB_USER) ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Encodage</span>
                        <span class="info-value">utf8mb4 / unicode_ci</span>
                    </div>
                </div>
            <?php else: ?>
                <span class="status fail"><span class="dot"></span> Échec de connexion</span>
                <div class="err-box">
                    <?= htmlspecialchars($mysqlErr) ?>
                </div>
                <p style="margin-top:.75rem;font-size:.82rem;color:var(--muted);">
                    Vérifiez que Laragon/MySQL est démarré et que les paramètres de connexion sont corrects.
                </p>
            <?php endif; ?>
        </div>

        <!-- Base de données -->
        <div class="card">
            <div class="card-title"><i class="bi bi-database"></i> Base de données</div>
            <?php if (!$mysqlOk): ?>
                <span class="status warn"><span class="dot"></span> Connexion requise</span>
                <p style="margin-top:.75rem;font-size:.82rem;color:var(--muted);">Impossible de vérifier sans connexion
                    MySQL.</p>
            <?php elseif ($dbExists): ?>
                <span class="status ok"><span class="dot"></span> Existe</span>
                <div style="margin-top:1.25rem;">
                    <div class="info-row">
                        <span class="info-label">Nom</span>
                        <span class="info-value">
                            <?= htmlspecialchars($DB_NAME) ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tables trouvées</span>
                        <span class="info-value">
                            <?= $foundCount ?> /
                            <?= $totalExpected ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tables manquantes</span>
                        <span class="info-value" style="color:<?= count($missingTables) ? 'var(--red)' : 'var(--green)' ?>">
                            <?= count($missingTables) ?: '0 ✓' ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Schéma complet</span>
                        <span class="info-value" style="color:<?= $schemaOk ? 'var(--green)' : 'var(--orange)' ?>">
                            <?= $schemaOk ? 'Oui ✓' : 'Non — tables manquantes' ?>
                        </span>
                    </div>
                </div>
            <?php else: ?>
                <span class="status fail"><span class="dot"></span> N'existe pas</span>
                <p style="margin-top:.75rem;font-size:.82rem;color:var(--muted);">
                    La base <code style="color:var(--yellow)"><?= htmlspecialchars($DB_NAME) ?></code> n'a pas été trouvée.
                    Cliquez sur <strong>Initialiser</strong> pour la créer.
                </p>
                <?php if ($dbErr): ?>
                    <div class="err-box">
                        <?= htmlspecialchars($dbErr) ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Schéma global -->
        <?php if ($dbExists): ?>
            <div class="card">
                <div class="card-title"><i class="bi bi-diagram-3"></i> Intégrité du Schéma</div>
                <?php
                $pct = $totalExpected > 0 ? round($foundCount / $totalExpected * 100) : 0;
                $progClass = $pct === 100 ? '' : ($pct >= 50 ? 'partial' : 'none');
                ?>
                <div style="display:flex;align-items:baseline;gap:.75rem;">
                    <div class="stat-number"
                        style="color:<?= $pct === 100 ? 'var(--green)' : ($pct >= 50 ? 'var(--orange)' : 'var(--red)') ?>">
                        <?= $pct ?>%
                    </div>
                    <div class="stat-label">des tables présentes</div>
                </div>
                <div class="prog-track">
                    <div class="prog-fill <?= $progClass ?>" style="width:<?= $pct ?>%"></div>
                </div>
                <div style="font-size:.82rem;color:var(--muted);">
                    <strong style="color:var(--green)">
                        <?= $foundCount ?>
                    </strong> trouvées ·
                    <strong style="color:var(--red)">
                        <?= $totalExpected - $foundCount ?>
                    </strong> manquantes ·
                    <strong>
                        <?= $totalExpected ?>
                    </strong> attendues
                </div>
            </div>
        <?php endif; ?>

        <!-- Timestamp & Refresh -->
        <div class="card" style="display:flex;flex-direction:column;justify-content:space-between;gap:1.25rem;">
            <div>
                <div class="card-title"><i class="bi bi-clock-history"></i> Dernière vérification</div>
                <div class="stat-number" style="font-size:1.4rem;color:var(--blue)">
                    <?= date('H:i:s') ?>
                </div>
                <div class="stat-label">
                    <?= date('d/m/Y') ?>
                </div>
            </div>
            <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
                <a href="?" class="btn btn-ghost" style="font-size:.8rem;padding:.5rem 1rem;">
                    <i class="bi bi-arrow-clockwise"></i> Rafraîchir
                </a>
                <a href="../" class="btn btn-ghost" style="font-size:.8rem;padding:.5rem 1rem;">
                    <i class="bi bi-house"></i> Accueil
                </a>
            </div>
        </div>

    </div>

    <!-- ══════════════════════════════════════════════
     BARRE D'ACTIONS
══════════════════════════════════════════════ -->
    <?php if ($mysqlOk): ?>
        <div class="actions-bar">
            <?php if (!$dbExists || !$schemaOk): ?>
                <a href="?action=init_db" class="btn btn-primary"
                    onclick="return confirm('Voulez-vous initialiser la base de données et appliquer le schéma SQL ?')">
                    <i class="bi bi-play-circle-fill"></i>
                    <?= !$dbExists ? 'Créer et initialiser la base' : 'Appliquer le schéma manquant' ?>
                </a>
            <?php endif; ?>
            <a href="?" class="btn btn-ghost">
                <i class="bi bi-arrow-repeat"></i> Tester à nouveau
            </a>
            <span class="sep">|</span>
            <span style="font-size:.8rem;color:var(--muted);">
                <i class="bi bi-info-circle"></i>
                Schéma source : <code style="color:var(--yellow)">database/schema_final.sql</code>
            </span>
        </div>
    <?php endif; ?>

    <!-- ══════════════════════════════════════════════
     TABLEAU DES TABLES
══════════════════════════════════════════════ -->
    <?php if ($dbExists && !empty($tableRows)): ?>

        <?php
        // Map des couleurs par module
        $moduleColors = [
            'Gestion organisationnelle' => '#58a6ff',
            'Utilisateurs & Staff' => '#bc8cff',
            'Gestion de projets' => '#3fb950',
            'Gestion de missions' => '#ffa657',
            'Communication' => '#FFC107',
            'Documents' => '#8b949e',
            'Sécurité' => '#f85149',
            'Performance' => '#d29922',
        ];
        ?>

        <div class="tbl-wrap">
            <div class="tbl-header">
                <h2><i class="bi bi-table" style="color:var(--yellow)"></i> &nbsp;Tables du schéma</h2>
                <input class="tbl-search" type="search" id="tableSearch" placeholder="&#128269; Filtrer les tables…">
            </div>

            <table id="tableList">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nom de la table</th>
                        <th>Module</th>
                        <th>Statut</th>
                        <th style="text-align:right">Lignes (approx.)</th>
                        <th style="text-align:right">Taille</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1;
                    foreach ($tableRows as $tbl => $info): ?>
                        <tr data-name="<?= htmlspecialchars($tbl) ?>" data-module="<?= htmlspecialchars($info['module']) ?>">
                            <td style="color:var(--muted);font-size:.78rem">
                                <?= $i++ ?>
                            </td>
                            <td>
                                <code
                                    style="font-size:.85rem;color:<?= $info['exists'] ? 'var(--text)' : 'var(--red)' ?>"><?= htmlspecialchars($tbl) ?></code>
                            </td>
                            <td>
                                <?php $mc = $moduleColors[$info['module']] ?? '#8b949e'; ?>
                                <span style="display:inline-flex;align-items:center;">
                                    <span class="mod-dot" style="background:<?= $mc ?>"></span>
                                    <span class="tag" style="background:<?= $mc ?>22;color:<?= $mc ?>;font-size:.72rem">
                                        <?= htmlspecialchars($info['module']) ?>
                                    </span>
                                </span>
                            </td>
                            <td>
                                <?php if ($info['exists']): ?>
                                    <i class="bi bi-check-circle-fill tbl-ok" title="Table présente"></i>
                                    <span style="font-size:.78rem;color:var(--green);margin-left:.25rem">Présente</span>
                                <?php else: ?>
                                    <i class="bi bi-x-circle-fill tbl-miss" title="Table manquante"></i>
                                    <span style="font-size:.78rem;color:var(--red);margin-left:.25rem">Manquante</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right;font-size:.82rem;color:var(--muted)">
                                <?= $info['exists'] ? number_format($info['rows']) : '—' ?>
                            </td>
                            <td style="text-align:right;font-size:.82rem;color:var(--muted)">
                                <?= $info['exists'] ? humanSize($info['size']) : '—' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="tbl-footer">
                <span id="visibleCount">
                    <?= count($tableRows) ?> table(s) affichées
                </span>
                <span class="timestamp">Généré le
                    <?= date('d/m/Y à H:i:s') ?>
                </span>
            </div>
        </div>

    <?php endif; ?>

    <!-- ══════════════════════════════════════════════
     LÉGENDE DES MODULES
══════════════════════════════════════════════ -->
    <?php if ($dbExists): ?>
        <div style="max-width:960px;margin:1.25rem auto 0;display:flex;gap:.75rem;flex-wrap:wrap;align-items:center;">
            <span
                style="font-size:.75rem;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-right:.25rem">Modules
                :</span>
            <?php foreach ($moduleColors as $mod => $col): ?>
                <span style="display:inline-flex;align-items:center;gap:.35rem;font-size:.75rem;color:<?= $col ?>">
                    <span style="width:8px;height:8px;border-radius:50%;background:<?= $col ?>;display:inline-block"></span>
                    <?= htmlspecialchars($mod) ?>
                </span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ══════════════════════════════════════════════
     SCRIPT DE FILTRAGE
══════════════════════════════════════════════ -->
    <script>
        (function () {
            var input = document.getElementById('tableSearch');
            var table = document.getElementById('tableList');
            var countEl = document.getElementById('visibleCount');
            if (!input || !table) return;

            input.addEventListener('input', function () {
                var q = input.value.toLowerCase().trim();
                var rows = table.querySelectorAll('tbody tr');
                var visible = 0;
                rows.forEach(function (tr) {
                    var name = tr.dataset.name || '';
                    var mod = tr.dataset.module || '';
                    var show = !q || name.includes(q) || mod.toLowerCase().includes(q);
                    tr.style.display = show ? '' : 'none';
                    if (show) visible++;
                });
                if (countEl) countEl.textContent = visible + ' table(s) affichées';
            });
        })();
    </script>

</body>

</html>