-- Coordonnées géographiques pour le lieu de la mission (sélection sur carte)
ALTER TABLE `mission`
    ADD COLUMN `latitude` DECIMAL(10,8) DEFAULT NULL COMMENT 'Latitude du lieu' AFTER `location`,
    ADD COLUMN `longitude` DECIMAL(11,8) DEFAULT NULL COMMENT 'Longitude du lieu' AFTER `latitude`;
