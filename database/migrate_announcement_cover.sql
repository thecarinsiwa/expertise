-- Migration: photo de couverture pour les annonces
ALTER TABLE `announcement` ADD COLUMN `cover_image` VARCHAR(500) NULL AFTER `content`;
