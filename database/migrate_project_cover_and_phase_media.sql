-- Migration: photo de couverture projet + photo par phase
-- Exécuter une fois sur la base existante (project_phase.description existe déjà).

ALTER TABLE `project` ADD COLUMN `cover_image` VARCHAR(255) NULL AFTER `description`;
ALTER TABLE `project_phase` ADD COLUMN `image_url` VARCHAR(255) NULL AFTER `description`;
