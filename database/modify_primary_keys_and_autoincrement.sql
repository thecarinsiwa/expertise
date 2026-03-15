-- =============================================================================
-- Script utilitaire : modification des clés primaires et AUTO_INCREMENT
-- Base : expertise (MySQL / MariaDB)
-- =============================================================================
-- À exécuter section par section selon le besoin. Lire les commentaires avant
-- chaque bloc. Faire une sauvegarde avant toute modification.
-- =============================================================================

USE expertise;

-- =============================================================================
-- 1. DIAGNOSTIC : lister les tables avec PK et AUTO_INCREMENT
-- =============================================================================
-- Exécuter ce SELECT pour voir l’état actuel de toutes les tables.

SELECT
    t.TABLE_NAME AS `Table`,
    GROUP_CONCAT(k.COLUMN_NAME ORDER BY k.ORDINAL_POSITION) AS `Colonnes PK`,
    c.COLUMN_NAME AS `Colonne AUTO_INCREMENT`,
    c.DATA_TYPE AS `Type`,
    t.AUTO_INCREMENT AS `Valeur AUTO_INCREMENT actuelle`
FROM information_schema.TABLES t
LEFT JOIN information_schema.KEY_COLUMN_USAGE k
    ON k.TABLE_SCHEMA = t.TABLE_SCHEMA
   AND k.TABLE_NAME = t.TABLE_NAME
   AND k.CONSTRAINT_NAME = 'PRIMARY'
LEFT JOIN information_schema.COLUMNS c
    ON c.TABLE_SCHEMA = t.TABLE_SCHEMA
   AND c.TABLE_NAME = t.TABLE_NAME
   AND c.EXTRA LIKE '%auto_increment%'
WHERE t.TABLE_SCHEMA = 'expertise'
  AND t.TABLE_TYPE = 'BASE TABLE'
GROUP BY t.TABLE_NAME, c.COLUMN_NAME, c.DATA_TYPE, t.AUTO_INCREMENT
ORDER BY t.TABLE_NAME;


-- =============================================================================
-- 2. RÉINITIALISER l’AUTO_INCREMENT d’une table (exemple : après purge de données)
-- =============================================================================
-- Remplacer `nom_table` par le nom de la table.
-- Option A : repartir à 1 (table vide ou vidée)
-- ALTER TABLE `nom_table` AUTO_INCREMENT = 1;

-- Option B : repartir à MAX(id) + 1 (conserver les IDs existants, éviter les conflits)
-- Exemple pour la table `user` :
/*
SET @max_id = (SELECT COALESCE(MAX(id), 0) + 1 FROM `user`);
SET @sql = CONCAT('ALTER TABLE `user` AUTO_INCREMENT = ', @max_id);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
*/

-- Exemple ciblé (sans variable) pour une table donnée :
-- ALTER TABLE `organisation` AUTO_INCREMENT = 100;


-- =============================================================================
-- 3. FORCER une nouvelle valeur d’AUTO_INCREMENT pour une table
-- =============================================================================
-- Utile pour “décaler” les prochains IDs (ex. commencer à 1000).
-- Remplacer nom_table et la valeur.

-- ALTER TABLE `nom_table` AUTO_INCREMENT = 1000;


-- =============================================================================
-- 4. AJOUTER une clé primaire sur une colonne (table sans PK ou nouvelle PK)
-- =============================================================================
-- La colonne doit être NOT NULL et sans doublons.
-- Exemple : mettre une PK sur `id` si elle n’existe pas encore.

-- ALTER TABLE `nom_table` ADD PRIMARY KEY (`id`);


-- =============================================================================
-- 5. SUPPRIMER la clé primaire (pour en mettre une autre ensuite)
-- =============================================================================
-- Attention : si la colonne est AUTO_INCREMENT, il faut d’abord la modifier
-- (voir section 6). Les tables avec clé composite : une seule PRIMARY KEY.

-- ALTER TABLE `nom_table` DROP PRIMARY KEY;


-- =============================================================================
-- 6. METTRE une colonne en AUTO_INCREMENT (avec clé primaire)
-- =============================================================================
-- La colonne doit être dans la PRIMARY KEY (souvent la seule colonne).
-- Adapter le type (INT UNSIGNED, BIGINT UNSIGNED, etc.) selon votre schéma.

-- Exemple pour une table avec `id` INT UNSIGNED :
-- ALTER TABLE `nom_table` MODIFY `id` INT UNSIGNED NOT NULL AUTO_INCREMENT;

-- Exemple pour une table avec `id` BIGINT UNSIGNED (ex. activity_log, audit) :
-- ALTER TABLE `activity_log` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;


-- =============================================================================
-- 7. RETIRER l’AUTO_INCREMENT d’une colonne (garder la PK)
-- =============================================================================
-- Conserver le type (INT UNSIGNED / BIGINT UNSIGNED) pour ne pas casser les données.

-- ALTER TABLE `nom_table` MODIFY `id` INT UNSIGNED NOT NULL;


-- =============================================================================
-- 8. CHANGER la clé primaire (nouvelle colonne ou clé composite)
-- =============================================================================
-- Étapes : supprimer l’ancienne PK, ajouter la nouvelle. Si l’ancienne colonne
-- était AUTO_INCREMENT, la modifier avant (section 7) ou après selon le cas.

-- Exemple : passer d’une PK `id` à une PK composite (table de liaison).
-- ALTER TABLE `role_permission` DROP PRIMARY KEY;
-- ALTER TABLE `role_permission` ADD PRIMARY KEY (`role_id`, `permission_id`);

-- Exemple : nouvelle PK sur une autre colonne (table sans id auto_inc)
-- ALTER TABLE `session` DROP PRIMARY KEY;
-- ALTER TABLE `session` ADD PRIMARY KEY (`id`);   -- id est varchar(255) ici


-- =============================================================================
-- 9. RÉINITIALISER l’AUTO_INCREMENT sur TOUTES les tables qui en ont un
-- =============================================================================
-- Définit AUTO_INCREMENT = MAX(id) + 1 pour chaque table concernée.
-- À exécuter avec précaution (par ex. après import / migration).

/*
-- Génère les requêtes ALTER (à copier/coller ou exécuter dans un script)
SELECT CONCAT(
    'ALTER TABLE `', TABLE_NAME, '` AUTO_INCREMENT = ',
    COALESCE(
        (SELECT AUTO_INCREMENT FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = 'expertise' AND TABLE_NAME = t.TABLE_NAME),
        1
    ), ';'
) AS `Requête`
FROM information_schema.COLUMNS c
JOIN information_schema.TABLES t
    ON t.TABLE_SCHEMA = c.TABLE_SCHEMA AND t.TABLE_NAME = c.TABLE_NAME
WHERE c.TABLE_SCHEMA = 'expertise'
  AND c.EXTRA LIKE '%auto_increment%'
GROUP BY c.TABLE_NAME;
*/

-- Variante : réinitialiser à 1 pour toutes les tables (à n’utiliser qu’en dev
-- ou sur des tables vides, sinon risque de conflit d’IDs)
/*
SELECT CONCAT('ALTER TABLE `', TABLE_NAME, '` AUTO_INCREMENT = 1;') AS `Requête`
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'expertise'
  AND AUTO_INCREMENT IS NOT NULL
ORDER BY TABLE_NAME;
*/


-- =============================================================================
-- 10. EXEMPLES CONCRETS pour la base expertise
-- =============================================================================

-- Réinitialiser l’auto-increment de `user` au prochain ID libre :
-- SET @next = (SELECT COALESCE(MAX(id), 0) + 1 FROM `user`);
-- SET @s = CONCAT('ALTER TABLE `user` AUTO_INCREMENT = ', @next);
-- PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Réinitialiser l’auto-increment de `organisation` au prochain ID libre :
-- SET @next = (SELECT COALESCE(MAX(id), 0) + 1 FROM `organisation`);
-- SET @s = CONCAT('ALTER TABLE `organisation` AUTO_INCREMENT = ', @next);
-- PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- S’assurer que activity_log et audit ont bien AUTO_INCREMENT sur id (BIGINT) :
-- ALTER TABLE `activity_log` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;
-- ALTER TABLE `audit` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;

COMMIT;
