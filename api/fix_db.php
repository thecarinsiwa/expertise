<?php
/**
 * Expertise - Correctif de la structure de la base de données
 * Ce script ajoute les colonnes manquantes dans les tables existantes.
 * Pour une installation neuve, utiliser database/schema.sql (ou ?action=init_db dans l'API).
 */

require_once __DIR__ . '/../config/database.php';
$DB_PORT = (int) (getenv('DB_PORT') ?: 3306);

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$DB_PORT};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}",
        $dbConfig['user'],
        $dbConfig['pass'],
        [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    echo "<h1>Mise à jour de la base de données...</h1>";

    $alters = [
        // Table mission_order
        "ALTER TABLE mission_order ADD COLUMN status ENUM('draft', 'sent', 'signed', 'cancelled') DEFAULT 'draft' AFTER notes",

        // Table mission_report
        "ALTER TABLE mission_report ADD COLUMN summary TEXT AFTER content",
        "ALTER TABLE mission_report ADD COLUMN report_date DATE AFTER summary",
        "ALTER TABLE mission_report ADD COLUMN status ENUM('draft', 'submitted', 'final') DEFAULT 'draft' AFTER report_date",

        // Table mission_expense
        "ALTER TABLE mission_expense ADD COLUMN user_id INT UNSIGNED DEFAULT NULL AFTER status"
    ];

    foreach ($alters as $sql) {
        try {
            // Note: IF NOT EXISTS for columns is MySQL 8.0.19+. 
            // For older versions, we catch the "Duplicate column name" error.
            $pdo->exec($sql);
            echo "<p style='color:green;'>Succès : " . htmlspecialchars($sql) . "</p>";
        } catch (PDOException $e) {
            if ($e->getCode() == '42S21') { // Column already exists
                echo "<p style='color:orange;'>Info (déjà présent) : " . htmlspecialchars($sql) . "</p>";
            } else {
                echo "<p style='color:red;'>Erreur : " . htmlspecialchars($e->getMessage()) . " (SQL: $sql)</p>";
            }
        }
    }

    echo "<h2>Mise à jour terminée !</h2>";
    echo "<p><a href='../admin/missions.php'>Retourner aux missions</a></p>";

} catch (PDOException $e) {
    die("<h2 style='color:red;'>Erreur de connexion : " . $e->getMessage() . "</h2>");
}
