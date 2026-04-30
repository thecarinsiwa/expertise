<?php
/**
 * Expertise API – Clés primaires et AUTO_INCREMENT
 * Diagnostic des PK et AUTO_INCREMENT + lien vers le script SQL utilitaire.
 */

require_once __DIR__ . '/../config/database.php';
$DB_HOST = $dbConfig['host'];
$DB_PORT = (int) (getenv('DB_PORT') ?: 3306);
$DB_USER = $dbConfig['user'];
$DB_PASS = $dbConfig['pass'];
$DB_NAME = $dbConfig['dbname'];

$error = null;
$rows = [];
$actionMsg = '';
$actionMsgType = '';

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $stmt = $pdo->query("
        SELECT
            t.TABLE_NAME AS `table_name`,
            GROUP_CONCAT(k.COLUMN_NAME ORDER BY k.ORDINAL_POSITION) AS `pk_columns`,
            c.COLUMN_NAME AS `auto_increment_column`,
            c.DATA_TYPE AS `data_type`,
            t.AUTO_INCREMENT AS `auto_increment_value`
        FROM information_schema.TABLES t
        LEFT JOIN information_schema.KEY_COLUMN_USAGE k
            ON k.TABLE_SCHEMA = t.TABLE_SCHEMA
           AND k.TABLE_NAME = t.TABLE_NAME
           AND k.CONSTRAINT_NAME = 'PRIMARY'
        LEFT JOIN information_schema.COLUMNS c
            ON c.TABLE_SCHEMA = t.TABLE_SCHEMA
           AND c.TABLE_NAME = t.TABLE_NAME
           AND c.EXTRA LIKE '%auto_increment%'
        WHERE t.TABLE_SCHEMA = " . $pdo->quote($DB_NAME) . "
          AND t.TABLE_TYPE = 'BASE TABLE'
        GROUP BY t.TABLE_NAME, c.COLUMN_NAME, c.DATA_TYPE, t.AUTO_INCREMENT
        ORDER BY t.TABLE_NAME
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Action : réinitialiser AUTO_INCREMENT à MAX(col)+1 pour toutes les tables concernées
    if (!empty($_POST['action']) && $_POST['action'] === 'reset_autoincrement') {
        $done = 0;
        $failed = [];
        foreach ($rows as $r) {
            if (empty($r['auto_increment_column']) || empty($r['table_name'])) {
                continue;
            }
            $table = $r['table_name'];
            $col = $r['auto_increment_column'];
            try {
                $nextStmt = $pdo->query("SELECT COALESCE(MAX(`" . str_replace('`', '``', $col) . "`), 0) + 1 FROM `" . str_replace('`', '``', $table) . "`");
                $nextVal = (int) $nextStmt->fetchColumn();
                $pdo->exec("ALTER TABLE `" . str_replace('`', '``', $table) . "` AUTO_INCREMENT = " . $nextVal);
                $done++;
            } catch (PDOException $e) {
                $failed[$table] = $e->getMessage();
            }
        }
        if ($done > 0 && empty($failed)) {
            $actionMsg = "AUTO_INCREMENT réinitialisé à MAX+1 pour {$done} table(s).";
            $actionMsgType = 'success';
        } elseif ($done > 0) {
            $actionMsg = "{$done} table(s) mises à jour. " . count($failed) . " erreur(s) : " . implode(', ', array_keys($failed));
            $actionMsgType = 'warning';
        } elseif (!empty($failed)) {
            $first = reset($failed);
            $actionMsg = "Aucune table mise à jour. Ex. " . key($failed) . " : " . $first;
            $actionMsgType = 'error';
        } else {
            $actionMsg = "Aucune table avec colonne AUTO_INCREMENT trouvée.";
            $actionMsgType = 'warning';
        }
    }
} catch (PDOException $e) {
    $error = $e->getMessage();
}

$scriptPath = dirname(__DIR__) . '/database/modify_primary_keys_and_autoincrement.sql';
$scriptExists = file_exists($scriptPath);
$hasAutoIncrementTables = count(array_filter($rows, function ($r) {
    return !empty($r['auto_increment_column']);
})) > 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Clés primaires &amp; AUTO_INCREMENT – Expertise API</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        :root{
            --bg:#0d1117;--bg2:#161b22;--bg3:#21262d;--border:rgba(255,255,255,.08);
            --text:#e6edf3;--muted:#8b949e;--accent:#1D1C3E;--yellow:#FFC107;
            --green:#3fb950;--red:#f85149;--orange:#d29922;--blue:#58a6ff;
            --radius:12px;--shadow:0 8px 32px rgba(0,0,0,.4);--font:'Montserrat',system-ui,sans-serif;
        }
        body{font-family:var(--font);background:var(--bg);color:var(--text);min-height:100vh;padding:2rem 1rem 4rem;}
        .page-header{max-width:960px;margin:0 auto 2.5rem;display:flex;align-items:flex-start;gap:1.25rem;}
        .header-icon{width:56px;height:56px;background:linear-gradient(135deg,var(--yellow) 0%,#FF9800 100%);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.6rem;color:var(--accent);flex-shrink:0;box-shadow:0 4px 16px rgba(255,193,7,.3);}
        .header-text h1{font-size:1.6rem;font-weight:800;letter-spacing:-.02em;color:#fff;}
        .header-text p{color:var(--muted);font-size:.9rem;margin-top:.25rem;}
        .header-breadcrumb{font-size:.78rem;color:var(--muted);margin-bottom:.35rem;}
        .header-breadcrumb a{color:var(--yellow);text-decoration:none;}
        .actions-bar{max-width:960px;margin:1.5rem auto;display:flex;gap:.75rem;flex-wrap:wrap;align-items:center;}
        .btn{display:inline-flex;align-items:center;gap:.5rem;padding:.7rem 1.5rem;border-radius:10px;font-family:var(--font);font-size:.88rem;font-weight:700;cursor:pointer;border:none;text-decoration:none;transition:all .2s;}
        .btn-primary{background:var(--yellow);color:var(--accent);}
        .btn-primary:hover{background:#FFB300;box-shadow:0 4px 16px rgba(255,193,7,.35);}
        .btn-ghost{background:var(--bg3);color:var(--text);border:1px solid var(--border);}
        .btn-ghost:hover{background:var(--bg2);border-color:rgba(255,255,255,.2);}
        .tbl-wrap{max-width:960px;margin:0 auto;overflow-x:auto;}
        .tbl-header{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius) var(--radius) 0 0;padding:1rem 1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;}
        .tbl-header h2{font-size:1rem;font-weight:700;color:#fff;}
        table{width:100%;border-collapse:collapse;background:var(--bg2);font-size:.85rem;border:1px solid var(--border);border-top:none;}
        thead th{background:var(--bg3);color:var(--muted);font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;padding:.75rem 1rem;text-align:left;border-bottom:1px solid var(--border);}
        tbody tr{border-bottom:1px solid var(--border);}
        tbody tr:hover{background:var(--bg3);}
        td{padding:.7rem 1rem;vertical-align:middle;}
        .tbl-footer{background:var(--bg2);border:1px solid var(--border);border-top:none;border-radius:0 0 var(--radius) var(--radius);padding:.75rem 1.5rem;font-size:.8rem;color:var(--muted);}
        .err-box{background:rgba(248,81,73,.08);border:1px solid rgba(248,81,73,.3);border-radius:8px;padding:.75rem 1rem;margin-top:.75rem;font-size:.8rem;color:var(--red);font-family:'Courier New',monospace;}
        .card{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:1.5rem;margin-bottom:1.5rem;max-width:960px;margin-left:auto;margin-right:auto;}
        .card-title{font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:1rem;}
        code{background:var(--bg3);padding:.2rem .5rem;border-radius:6px;font-size:.85rem;}
        .action-msg{max-width:960px;margin:0 auto 1.5rem;padding:.9rem 1.2rem;border-radius:var(--radius);display:flex;align-items:center;gap:.75rem;font-size:.9rem;font-weight:600;}
        .action-msg.success{background:rgba(63,185,80,.15);border:1px solid rgba(63,185,80,.4);color:#3fb950;}
        .action-msg.warning{background:rgba(210,153,34,.15);border:1px solid rgba(210,153,34,.4);color:var(--orange);}
        .action-msg.error{background:rgba(248,81,73,.15);border:1px solid rgba(248,81,73,.4);color:var(--red);}
    </style>
</head>
<body>

<header class="page-header">
    <div class="header-icon"><i class="bi bi-key-fill"></i></div>
    <div class="header-text">
        <p class="header-breadcrumb">
            <a href="../">Expertise</a> › <a href="./">API</a> › Clés primaires &amp; AUTO_INCREMENT
        </p>
        <h1>Clés primaires et AUTO_INCREMENT</h1>
        <p>Diagnostic des tables de la base <strong><?= htmlspecialchars($DB_NAME) ?></strong> et script SQL utilitaire.</p>
    </div>
</header>

<?php if ($actionMsg): ?>
<div class="action-msg <?= $actionMsgType ?>">
    <i class="bi bi-<?= $actionMsgType === 'success' ? 'check-circle-fill' : ($actionMsgType === 'error' ? 'x-circle-fill' : 'exclamation-triangle-fill') ?>"></i>
    <?= htmlspecialchars($actionMsg) ?>
</div>
<?php endif; ?>

<div class="actions-bar">
    <a href="index.php" class="btn btn-ghost"><i class="bi bi-arrow-left"></i> Vérification BDD</a>
    <a href="../" class="btn btn-ghost"><i class="bi bi-house-door"></i> Retour au site</a>
    <?php if (!$error && $hasAutoIncrementTables): ?>
    <form method="post" style="display:inline;" onsubmit="return confirm('Réinitialiser l\'AUTO_INCREMENT à MAX(id)+1 pour toutes les tables concernées ?');">
        <input type="hidden" name="action" value="reset_autoincrement">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-arrow-repeat"></i> Réinitialiser les AUTO_INCREMENT (MAX+1)
        </button>
    </form>
    <?php endif; ?>
    <?php if ($scriptExists): ?>
    <a href="../database/modify_primary_keys_and_autoincrement.sql" class="btn btn-ghost" download>
        <i class="bi bi-file-earmark-code"></i> Télécharger le script SQL
    </a>
    <?php endif; ?>
</div>

<?php if ($error): ?>
<div class="card" style="border-color:var(--red);">
    <div class="card-title" style="color:var(--red);"><i class="bi bi-x-circle"></i> Erreur de connexion</div>
    <div class="err-box"><?= htmlspecialchars($error) ?></div>
    <p style="margin-top:.75rem;font-size:.82rem;color:var(--muted);">Vérifiez que la base existe et que les paramètres dans <code>config/database.php</code> sont corrects.</p>
</div>
<?php else: ?>

<div class="tbl-wrap">
    <div class="tbl-header">
        <h2><i class="bi bi-table" style="color:var(--yellow)"></i> &nbsp;Tables – PK et AUTO_INCREMENT</h2>
    </div>
    <table>
        <thead>
            <tr>
                <th>Table</th>
                <th>Colonnes PK</th>
                <th>Colonne AUTO_INCREMENT</th>
                <th>Type</th>
                <th style="text-align:right">Valeur AUTO_INCREMENT</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td><code><?= htmlspecialchars($r['table_name']) ?></code></td>
                <td><?= $r['pk_columns'] !== null ? htmlspecialchars($r['pk_columns']) : '<span style="color:var(--red)">—</span>' ?></td>
                <td><?= $r['auto_increment_column'] !== null ? '<code>' . htmlspecialchars($r['auto_increment_column']) . '</code>' : '<span style="color:var(--muted)">—</span>' ?></td>
                <td><?= $r['data_type'] !== null ? htmlspecialchars($r['data_type']) : '—' ?></td>
                <td style="text-align:right"><?= $r['auto_increment_value'] !== null ? number_format((int)$r['auto_increment_value']) : '—' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="tbl-footer">
        <?= count($rows) ?> table(s) · Généré le <?= date('d/m/Y à H:i:s') ?>
    </div>
</div>

<?php if ($scriptExists): ?>
<div class="card">
    <div class="card-title"><i class="bi bi-file-earmark-text"></i> Script SQL utilitaire</div>
    <p style="font-size:.9rem;color:var(--muted);margin-bottom:.75rem;">
        Le fichier <code>database/modify_primary_keys_and_autoincrement.sql</code> contient des exemples pour :
    </p>
    <ul style="font-size:.85rem;color:var(--text);margin-left:1.25rem;line-height:1.8;">
        <li>Réinitialiser l’AUTO_INCREMENT (à 1 ou à MAX(id)+1)</li>
        <li>Forcer une valeur d’AUTO_INCREMENT</li>
        <li>Ajouter / supprimer une clé primaire</li>
        <li>Mettre ou retirer AUTO_INCREMENT sur une colonne</li>
        <li>Changer la clé primaire (simple ou composite)</li>
    </ul>
    <p style="margin-top:1rem;">
        <a href="../database/modify_primary_keys_and_autoincrement.sql" class="btn btn-primary" download>
            <i class="bi bi-download"></i> Télécharger le script
        </a>
        <span style="font-size:.8rem;color:var(--muted);margin-left:.5rem;">À exécuter section par section dans phpMyAdmin ou en ligne de commande MySQL.</span>
    </p>
</div>
<?php endif; ?>

<?php endif; ?>

</body>
</html>
