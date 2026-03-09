-- Add favicon column to organisation (run once on existing database)
ALTER TABLE `organisation` ADD COLUMN `favicon` VARCHAR(500) DEFAULT NULL COMMENT 'Favicon de l''organisation (icône onglet)' AFTER `logo`;
