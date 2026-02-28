<?php
/**
 * Exécute database/add_offers_module.sql (tables offer, offer_application + permissions).
 * Lancer depuis le terminal : php run_add_offers_module.php
 * Ou depuis le navigateur : http://localhost/expertise/run_add_offers_module.php
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
    "CREATE TABLE IF NOT EXISTS `offer` (
      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `organisation_id` INT UNSIGNED NOT NULL,
      `title` VARCHAR(255) NOT NULL,
      `reference` VARCHAR(100) DEFAULT NULL,
      `description` LONGTEXT,
      `cover_image` VARCHAR(500) DEFAULT NULL,
      `mission_id` INT UNSIGNED DEFAULT NULL,
      `project_id` INT UNSIGNED DEFAULT NULL,
      `status` ENUM('draft','published','closed') NOT NULL DEFAULT 'draft',
      `published_at` DATE DEFAULT NULL,
      `deadline_at` DATE DEFAULT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_offer_organisation` (`organisation_id`),
      KEY `idx_offer_mission` (`mission_id`),
      KEY `idx_offer_project` (`project_id`),
      KEY `idx_offer_status` (`status`),
      CONSTRAINT `fk_offer_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisation` (`id`) ON DELETE CASCADE,
      CONSTRAINT `fk_offer_mission` FOREIGN KEY (`mission_id`) REFERENCES `mission` (`id`) ON DELETE SET NULL,
      CONSTRAINT `fk_offer_project` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS `offer_application` (
      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `offer_id` INT UNSIGNED NOT NULL,
      `user_id` INT UNSIGNED NOT NULL,
      `message` TEXT DEFAULT NULL,
      `cv_path` VARCHAR(500) DEFAULT NULL,
      `status` ENUM('pending','reviewed','accepted','rejected') NOT NULL DEFAULT 'pending',
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uk_offer_application_offer_user` (`offer_id`, `user_id`),
      KEY `idx_offer_application_offer` (`offer_id`),
      KEY `idx_offer_application_user` (`user_id`),
      CONSTRAINT `fk_offer_application_offer` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`id`) ON DELETE CASCADE,
      CONSTRAINT `fk_offer_application_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "INSERT IGNORE INTO `permission` (`module`, `code`, `name`) VALUES
    ('Nos offres', 'admin.offers.view', 'Nos offres – Voir'),
    ('Nos offres', 'admin.offers.add', 'Nos offres – Ajout'),
    ('Nos offres', 'admin.offers.modify', 'Nos offres – Modifier'),
    ('Nos offres', 'admin.offers.delete', 'Nos offres – Supprimer')",
    "INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`) SELECT 1, id FROM `permission` WHERE `code` LIKE 'admin.offers.%'",
    "INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`) SELECT 2, id FROM `permission` WHERE `code` LIKE 'admin.offers.%'",
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

$check = $pdo->query("SHOW TABLES LIKE 'offer'")->fetch();
if (!$check) {
    echo "Erreur: la table offer n'existe pas après la migration.\n";
    exit(1);
}

echo "Migration OK: tables offer et offer_application créées, permissions ajoutées.\n";
