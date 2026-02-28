-- Profil CV client : champs additionnels sur la table profile
-- CV par défaut réutilisable, expériences et formations (JSON)

SET NAMES utf8mb4;

-- cv_path : fichier CV par défaut du profil (réutilisable pour toutes les candidatures)
ALTER TABLE `profile` ADD COLUMN `cv_path` VARCHAR(500) DEFAULT NULL AFTER `job_title`;

-- experience : liste { titre, entreprise, periode, description }
ALTER TABLE `profile` ADD COLUMN `experience` JSON DEFAULT NULL AFTER `skills`;

-- education : liste { diplome, etablissement, periode }
ALTER TABLE `profile` ADD COLUMN `education` JSON DEFAULT NULL AFTER `experience`;
