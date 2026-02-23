-- Migration: bailleurs de fonds et liaison projet – bailleur(s)
-- Exécuter une fois sur la base existante.

CREATE TABLE IF NOT EXISTS `bailleur` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(50) DEFAULT NULL,
  `description` TEXT,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `project_bailleur` (
  `project_id` INT UNSIGNED NOT NULL,
  `bailleur_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`project_id`, `bailleur_id`),
  KEY `idx_project_bailleur_bailleur` (`bailleur_id`),
  CONSTRAINT `fk_project_bailleur_project` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_project_bailleur_bailleur` FOREIGN KEY (`bailleur_id`) REFERENCES `bailleur` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
