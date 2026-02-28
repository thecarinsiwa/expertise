-- Add cover_image to organisation (photo de couverture / bannière)
-- Run once on existing databases that don't have this column yet.

SET NAMES utf8mb4;

-- MySQL 8.0.12+ supports ADD COLUMN IF NOT EXISTS; for older MySQL use the line below instead.
ALTER TABLE `organisation`
  ADD COLUMN `cover_image` VARCHAR(500) DEFAULT NULL COMMENT 'Photo de couverture (bannière)' AFTER `logo`;
