<?php
/**
 * Exécute l'installation du module Expertises.
 * Lancer depuis le terminal : php run_add_expertises_module.php
 * Ou depuis le navigateur : https://www.expertisehs.org/run_add_expertises_module.php
 */
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

$configPath = __DIR__ . '/config/database.php';
if (!is_file($configPath)) {
    die("Config not found: config/database.php\n");
}
require $configPath;

$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=%s',
    $dbConfig['host'],
    $dbConfig['dbname'],
    $dbConfig['charset']
);

try {
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}

$pdo->exec("SET NAMES utf8mb4");

$statements = [
    "CREATE TABLE IF NOT EXISTS `expertise_item` (
      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `organisation_id` INT UNSIGNED NOT NULL,
      `title` VARCHAR(255) NOT NULL,
      `summary` VARCHAR(500) DEFAULT NULL,
      `description` LONGTEXT,
      `display_order` INT NOT NULL DEFAULT 0,
      `is_active` TINYINT(1) NOT NULL DEFAULT 1,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_expertise_org` (`organisation_id`),
      KEY `idx_expertise_active` (`is_active`),
      KEY `idx_expertise_order` (`display_order`),
      CONSTRAINT `fk_expertise_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisation` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "INSERT IGNORE INTO `permission` (`module`, `code`, `name`) VALUES
    ('Expertises', 'admin.expertises.view', 'Expertises - Voir'),
    ('Expertises', 'admin.expertises.add', 'Expertises - Ajout'),
    ('Expertises', 'admin.expertises.modify', 'Expertises - Modifier'),
    ('Expertises', 'admin.expertises.delete', 'Expertises - Supprimer')",
    "INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`) SELECT 1, id FROM `permission` WHERE `code` LIKE 'admin.expertises.%'",
    "INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`) SELECT 2, id FROM `permission` WHERE `code` LIKE 'admin.expertises.%'",
];

$errors = [];
foreach ($statements as $i => $stmt) {
    try {
        $pdo->exec($stmt);
    } catch (PDOException $e) {
        $errors[] = "Statement " . ($i + 1) . ": " . $e->getMessage();
    }
}

if (count($errors) > 0) {
    echo "Erreurs:\n" . implode("\n", $errors) . "\n";
    exit(1);
}

$check = $pdo->query("SHOW TABLES LIKE 'expertise_item'")->fetch();
if (!$check) {
    echo "Erreur: la table expertise_item n'existe pas après la migration.\n";
    exit(1);
}

echo "Migration OK: table expertise_item et permissions ajoutees.\n";
