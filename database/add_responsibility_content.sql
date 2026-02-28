-- Responsibility page dynamic content
-- Tables: responsibility_page (intro text), responsibility_commitment (engagement cards)

SET NAMES utf8mb4;

-- One row per organisation (or NULL for default site content)
CREATE TABLE IF NOT EXISTS `responsibility_page` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `organisation_id` INT UNSIGNED DEFAULT NULL COMMENT 'NULL = contenu par défaut',
  `intro_block1` TEXT DEFAULT NULL,
  `intro_block2` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_responsibility_page_organisation` (`organisation_id`),
  CONSTRAINT `fk_responsibility_page_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisation` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Engagement cards: multiple per organisation (or NULL = global)
CREATE TABLE IF NOT EXISTS `responsibility_commitment` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `organisation_id` INT UNSIGNED DEFAULT NULL COMMENT 'NULL = global',
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `icon` VARCHAR(100) DEFAULT NULL COMMENT 'Bootstrap Icons class e.g. bi-shield-check',
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_responsibility_commitment_organisation` (`organisation_id`),
  CONSTRAINT `fk_responsibility_commitment_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisation` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default intro (global, organisation_id NULL) – insert only if no default exists
INSERT INTO `responsibility_page` (`organisation_id`, `intro_block1`, `intro_block2`)
SELECT NULL,
 'Politiques et rapports sur nos engagements éthiques, diversité et impact environnemental.',
 'Notre responsabilité s''exerce vis-à-vis des personnes que nous accompagnons, de nos équipes et de l''environnement.'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `responsibility_page` WHERE `organisation_id` IS NULL LIMIT 1);

-- Default commitments (global) – insert only if no global commitments exist
INSERT INTO `responsibility_commitment` (`organisation_id`, `title`, `description`, `icon`, `sort_order`)
SELECT NULL, 'Éthique et intégrité', 'Des politiques et dispositifs encadrent nos pratiques pour garantir l''intégrité de nos actions.', 'bi-shield-check', 1
FROM DUAL WHERE (SELECT COUNT(*) FROM `responsibility_commitment` WHERE `organisation_id` IS NULL) = 0
UNION ALL
SELECT NULL, 'Diversité et inclusion', 'Nous œuvrons pour un environnement inclusif et une représentation équitable au sein de l''organisation.', 'bi-people', 2
FROM DUAL WHERE (SELECT COUNT(*) FROM `responsibility_commitment` WHERE `organisation_id` IS NULL) = 0
UNION ALL
SELECT NULL, 'Environnement', 'Nous nous efforçons de réduire l''impact environnemental de nos activités et de nos déplacements.', 'bi-globe2', 3
FROM DUAL WHERE (SELECT COUNT(*) FROM `responsibility_commitment` WHERE `organisation_id` IS NULL) = 0;
