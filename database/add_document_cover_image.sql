-- Add cover_image to document (photo de couverture / miniature)
-- Run once on existing databases that don't have this column yet.

SET NAMES utf8mb4;

ALTER TABLE `document`
  ADD COLUMN `cover_image` VARCHAR(500) DEFAULT NULL COMMENT 'Photo de couverture (miniature)' AFTER `document_type`;
