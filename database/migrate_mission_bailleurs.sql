-- Migration: liaison mission – bailleur(s) de fonds
-- Exécuter une fois sur la base existante (table bailleur doit exister).

CREATE TABLE IF NOT EXISTS `mission_bailleur` (
  `mission_id` INT UNSIGNED NOT NULL,
  `bailleur_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`mission_id`, `bailleur_id`),
  KEY `idx_mission_bailleur_bailleur` (`bailleur_id`),
  CONSTRAINT `fk_mission_bailleur_mission` FOREIGN KEY (`mission_id`) REFERENCES `mission` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mission_bailleur_bailleur` FOREIGN KEY (`bailleur_id`) REFERENCES `bailleur` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
