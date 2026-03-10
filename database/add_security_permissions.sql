-- Ajoute les permissions du module Sécurité si elles n'existent pas (pour bases existantes)
INSERT IGNORE INTO `permission` (`module`, `code`, `name`) VALUES
('Sécurité', 'admin.security.view', 'Sécurité – Voir'),
('Sécurité', 'admin.security.modify', 'Sécurité – Modifier');

-- Lie Super Admin (role_id 1) et Administrateur (role_id 2) à ces permissions
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT 1, id FROM `permission` WHERE `code` IN ('admin.security.view', 'admin.security.modify');
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT 2, id FROM `permission` WHERE `code` IN ('admin.security.view', 'admin.security.modify');
