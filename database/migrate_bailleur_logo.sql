-- Migration: logo du bailleur
-- Exécuter une fois sur la base existante.

ALTER TABLE `bailleur` ADD COLUMN `logo` VARCHAR(255) NULL AFTER `description`;
