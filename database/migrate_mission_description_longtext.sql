-- Migration: mission.description en LONGTEXT pour contenu long (HTML / texte)
-- Erreur évitée: SQLSTATE[22001]: String data, right truncated: 1406 Data too long for column 'description'

ALTER TABLE `mission` MODIFY COLUMN `description` LONGTEXT NULL;
