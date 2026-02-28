-- Réactions aux actualités (j'aime, j'adore, bravo, etc.)

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `announcement_reaction` (
  `announcement_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `reaction_type` VARCHAR(50) NOT NULL DEFAULT 'like',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`announcement_id`, `user_id`),
  KEY `idx_announcement_reaction_user` (`user_id`),
  CONSTRAINT `fk_announcement_reaction_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `announcement` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_announcement_reaction_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
