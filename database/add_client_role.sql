-- Rôle système Client pour l'espace client (inscription publique, tableau de bord)
-- Organisation 1 = Expertise Humanitaire et Sociale SARL (défaut)
INSERT IGNORE INTO `role` (`id`, `organisation_id`, `name`, `code`, `is_system`)
VALUES (5, 1, 'Client', 'client', 1);
