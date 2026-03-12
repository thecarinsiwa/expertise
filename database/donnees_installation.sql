-- Données d'installation pour la base expertise
-- À exécuter après schema.sql
-- Ordre respectant les clés étrangères

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
/*!40101 SET NAMES utf8mb4 */;

SET FOREIGN_KEY_CHECKS=0;

-- permission
INSERT INTO `permission` (`id`, `name`, `code`, `module`, `description`, `created_at`) VALUES
(1, 'Accès administration', 'admin.access', 'admin', NULL, '2026-02-23 01:36:15'),
(2, 'Gérer les utilisateurs', 'users.manage', 'users', NULL, '2026-02-23 01:36:15'),
(3, 'Gérer les projets', 'projects.manage', 'projects', NULL, '2026-02-23 01:36:15'),
(4, 'Gérer les missions', 'missions.manage', 'missions', NULL, '2026-02-23 01:36:15'),
(5, 'Tableau de bord – Accès', 'admin.dashboard', 'Tableau de bord', NULL, '2026-02-26 12:47:09'),
(6, 'Organisations – Voir', 'admin.organisations.view', 'Organisations', NULL, '2026-02-26 12:47:09'),
(7, 'Organisations – Ajout', 'admin.organisations.add', 'Organisations', NULL, '2026-02-26 12:47:09'),
(8, 'Organisations – Modifier', 'admin.organisations.modify', 'Organisations', NULL, '2026-02-26 12:47:09'),
(9, 'Organisations – Supprimer', 'admin.organisations.delete', 'Organisations', NULL, '2026-02-26 12:47:09'),
(10, 'Unités & Services – Voir', 'admin.units.view', 'Unités & Services', NULL, '2026-02-26 12:47:09'),
(11, 'Unités & Services – Ajout', 'admin.units.add', 'Unités & Services', NULL, '2026-02-26 12:47:09'),
(12, 'Unités & Services – Modifier', 'admin.units.modify', 'Unités & Services', NULL, '2026-02-26 12:47:09'),
(13, 'Unités & Services – Supprimer', 'admin.units.delete', 'Unités & Services', NULL, '2026-02-26 12:47:09'),
(14, 'Utilisateurs – Voir', 'admin.users.view', 'Utilisateurs', NULL, '2026-02-26 12:47:09'),
(15, 'Utilisateurs – Ajout', 'admin.users.add', 'Utilisateurs', NULL, '2026-02-26 12:47:09'),
(16, 'Utilisateurs – Modifier', 'admin.users.modify', 'Utilisateurs', NULL, '2026-02-26 12:47:09'),
(17, 'Utilisateurs – Supprimer', 'admin.users.delete', 'Utilisateurs', NULL, '2026-02-26 12:47:09'),
(18, 'Personnel – Voir', 'admin.staff.view', 'Personnel', NULL, '2026-02-26 12:47:09'),
(19, 'Personnel – Ajout', 'admin.staff.add', 'Personnel', NULL, '2026-02-26 12:47:09'),
(20, 'Personnel – Modifier', 'admin.staff.modify', 'Personnel', NULL, '2026-02-26 12:47:09'),
(21, 'Personnel – Supprimer', 'admin.staff.delete', 'Personnel', NULL, '2026-02-26 12:47:09'),
(22, 'Rôles & Accès – Voir', 'admin.roles.view', 'Rôles & Accès', NULL, '2026-02-26 12:47:09'),
(23, 'Rôles & Accès – Ajout', 'admin.roles.add', 'Rôles & Accès', NULL, '2026-02-26 12:47:09'),
(24, 'Rôles & Accès – Modifier', 'admin.roles.modify', 'Rôles & Accès', NULL, '2026-02-26 12:47:09'),
(25, 'Rôles & Accès – Supprimer', 'admin.roles.delete', 'Rôles & Accès', NULL, '2026-02-26 12:47:09'),
(26, 'Sécurité – Voir', 'admin.security.view', 'Sécurité', NULL, '2026-02-26 12:47:09'),
(27, 'Sécurité – Modifier', 'admin.security.modify', 'Sécurité', NULL, '2026-02-26 12:47:09'),
(28, 'Projets – Voir', 'admin.projects.view', 'Projets', NULL, '2026-02-26 12:47:09'),
(29, 'Projets – Ajout', 'admin.projects.add', 'Projets', NULL, '2026-02-26 12:47:09'),
(30, 'Projets – Modifier', 'admin.projects.modify', 'Projets', NULL, '2026-02-26 12:47:09'),
(31, 'Projets – Supprimer', 'admin.projects.delete', 'Projets', NULL, '2026-02-26 12:47:09'),
(32, 'Programmes – Voir', 'admin.programmes.view', 'Programmes', NULL, '2026-02-26 12:47:09'),
(33, 'Programmes – Ajout', 'admin.programmes.add', 'Programmes', NULL, '2026-02-26 12:47:09'),
(34, 'Programmes – Modifier', 'admin.programmes.modify', 'Programmes', NULL, '2026-02-26 12:47:09'),
(35, 'Programmes – Supprimer', 'admin.programmes.delete', 'Programmes', NULL, '2026-02-26 12:47:09'),
(36, 'Portfolios – Voir', 'admin.portfolios.view', 'Portfolios', NULL, '2026-02-26 12:47:09'),
(37, 'Portfolios – Ajout', 'admin.portfolios.add', 'Portfolios', NULL, '2026-02-26 12:47:09'),
(38, 'Portfolios – Modifier', 'admin.portfolios.modify', 'Portfolios', NULL, '2026-02-26 12:47:09'),
(39, 'Portfolios – Supprimer', 'admin.portfolios.delete', 'Portfolios', NULL, '2026-02-26 12:47:09'),
(40, 'Bailleurs – Voir', 'admin.bailleurs.view', 'Bailleurs', NULL, '2026-02-26 12:47:09'),
(41, 'Bailleurs – Ajout', 'admin.bailleurs.add', 'Bailleurs', NULL, '2026-02-26 12:47:09'),
(42, 'Bailleurs – Modifier', 'admin.bailleurs.modify', 'Bailleurs', NULL, '2026-02-26 12:47:09'),
(43, 'Bailleurs – Supprimer', 'admin.bailleurs.delete', 'Bailleurs', NULL, '2026-02-26 12:47:09'),
(44, 'Missions – Voir', 'admin.missions.view', 'Missions', NULL, '2026-02-26 12:47:09'),
(45, 'Missions – Ajout', 'admin.missions.add', 'Missions', NULL, '2026-02-26 12:47:09'),
(46, 'Missions – Modifier', 'admin.missions.modify', 'Missions', NULL, '2026-02-26 12:47:09'),
(47, 'Missions – Supprimer', 'admin.missions.delete', 'Missions', NULL, '2026-02-26 12:47:09'),
(48, 'Types de mission – Voir', 'admin.mission_types.view', 'Types de mission', NULL, '2026-02-26 12:47:09'),
(49, 'Types de mission – Ajout', 'admin.mission_types.add', 'Types de mission', NULL, '2026-02-26 12:47:09'),
(50, 'Types de mission – Modifier', 'admin.mission_types.modify', 'Types de mission', NULL, '2026-02-26 12:47:09'),
(51, 'Types de mission – Supprimer', 'admin.mission_types.delete', 'Types de mission', NULL, '2026-02-26 12:47:09'),
(52, 'Ordres de mission – Voir', 'admin.mission_orders.view', 'Ordres de mission', NULL, '2026-02-26 12:47:09'),
(53, 'Ordres de mission – Ajout', 'admin.mission_orders.add', 'Ordres de mission', NULL, '2026-02-26 12:47:09'),
(54, 'Ordres de mission – Modifier', 'admin.mission_orders.modify', 'Ordres de mission', NULL, '2026-02-26 12:47:09'),
(55, 'Ordres de mission – Supprimer', 'admin.mission_orders.delete', 'Ordres de mission', NULL, '2026-02-26 12:47:09'),
(56, 'Plans de mission – Voir', 'admin.mission_plans.view', 'Plans de mission', NULL, '2026-02-26 12:47:09'),
(57, 'Plans de mission – Ajout', 'admin.mission_plans.add', 'Plans de mission', NULL, '2026-02-26 12:47:09'),
(58, 'Plans de mission – Modifier', 'admin.mission_plans.modify', 'Plans de mission', NULL, '2026-02-26 12:47:09'),
(59, 'Plans de mission – Supprimer', 'admin.mission_plans.delete', 'Plans de mission', NULL, '2026-02-26 12:47:09'),
(60, 'Rapports de mission – Voir', 'admin.mission_reports.view', 'Rapports de mission', NULL, '2026-02-26 12:47:09'),
(61, 'Rapports de mission – Ajout', 'admin.mission_reports.add', 'Rapports de mission', NULL, '2026-02-26 12:47:09'),
(62, 'Rapports de mission – Modifier', 'admin.mission_reports.modify', 'Rapports de mission', NULL, '2026-02-26 12:47:09'),
(63, 'Rapports de mission – Supprimer', 'admin.mission_reports.delete', 'Rapports de mission', NULL, '2026-02-26 12:47:09'),
(64, 'Dépenses de mission – Voir', 'admin.mission_expenses.view', 'Dépenses de mission', NULL, '2026-02-26 12:47:09'),
(65, 'Dépenses de mission – Ajout', 'admin.mission_expenses.add', 'Dépenses de mission', NULL, '2026-02-26 12:47:09'),
(66, 'Dépenses de mission – Modifier', 'admin.mission_expenses.modify', 'Dépenses de mission', NULL, '2026-02-26 12:47:09'),
(67, 'Dépenses de mission – Supprimer', 'admin.mission_expenses.delete', 'Dépenses de mission', NULL, '2026-02-26 12:47:09'),
(68, 'Annonces – Voir', 'admin.announcements.view', 'Annonces', NULL, '2026-02-26 12:47:09'),
(69, 'Annonces – Ajout', 'admin.announcements.add', 'Annonces', NULL, '2026-02-26 12:47:09'),
(70, 'Annonces – Modifier', 'admin.announcements.modify', 'Annonces', NULL, '2026-02-26 12:47:09'),
(71, 'Annonces – Supprimer', 'admin.announcements.delete', 'Annonces', NULL, '2026-02-26 12:47:09'),
(72, 'Canaux – Voir', 'admin.channels.view', 'Canaux', NULL, '2026-02-26 12:47:09'),
(73, 'Canaux – Ajout', 'admin.channels.add', 'Canaux', NULL, '2026-02-26 12:47:09'),
(74, 'Canaux – Modifier', 'admin.channels.modify', 'Canaux', NULL, '2026-02-26 12:47:09'),
(75, 'Canaux – Supprimer', 'admin.channels.delete', 'Canaux', NULL, '2026-02-26 12:47:09'),
(76, 'Types de canaux – Voir', 'admin.channel_types.view', 'Types de canaux', NULL, '2026-02-26 12:47:09'),
(77, 'Types de canaux – Ajout', 'admin.channel_types.add', 'Types de canaux', NULL, '2026-02-26 12:47:09'),
(78, 'Types de canaux – Modifier', 'admin.channel_types.modify', 'Types de canaux', NULL, '2026-02-26 12:47:09'),
(79, 'Types de canaux – Supprimer', 'admin.channel_types.delete', 'Types de canaux', NULL, '2026-02-26 12:47:09'),
(80, 'Conversations – Voir', 'admin.conversations.view', 'Conversations', NULL, '2026-02-26 12:47:09'),
(81, 'Conversations – Ajout', 'admin.conversations.add', 'Conversations', NULL, '2026-02-26 12:47:09'),
(82, 'Conversations – Modifier', 'admin.conversations.modify', 'Conversations', NULL, '2026-02-26 12:47:09'),
(83, 'Conversations – Supprimer', 'admin.conversations.delete', 'Conversations', NULL, '2026-02-26 12:47:09'),
(84, 'Notifications – Voir', 'admin.notifications.view', 'Notifications', NULL, '2026-02-26 12:47:09'),
(85, 'Notifications – Ajout', 'admin.notifications.add', 'Notifications', NULL, '2026-02-26 12:47:09'),
(86, 'Notifications – Modifier', 'admin.notifications.modify', 'Notifications', NULL, '2026-02-26 12:47:09'),
(87, 'Notifications – Supprimer', 'admin.notifications.delete', 'Notifications', NULL, '2026-02-26 12:47:09'),
(88, 'Commentaires – Voir', 'admin.comments.view', 'Commentaires', NULL, '2026-02-26 12:47:09'),
(89, 'Commentaires – Ajout', 'admin.comments.add', 'Commentaires', NULL, '2026-02-26 12:47:09'),
(90, 'Commentaires – Modifier', 'admin.comments.modify', 'Commentaires', NULL, '2026-02-26 12:47:09'),
(91, 'Commentaires – Supprimer', 'admin.comments.delete', 'Commentaires', NULL, '2026-02-26 12:47:09'),
(92, 'Pièces jointes – Voir', 'admin.attachments.view', 'Pièces jointes', NULL, '2026-02-26 12:47:09'),
(93, 'Pièces jointes – Ajout', 'admin.attachments.add', 'Pièces jointes', NULL, '2026-02-26 12:47:09'),
(94, 'Pièces jointes – Modifier', 'admin.attachments.modify', 'Pièces jointes', NULL, '2026-02-26 12:47:09'),
(95, 'Pièces jointes – Supprimer', 'admin.attachments.delete', 'Pièces jointes', NULL, '2026-02-26 12:47:09'),
(96, 'Historique communication – Voir', 'admin.communication_history.view', 'Historique communication', NULL, '2026-02-26 12:47:09'),
(97, 'Historique communication – Ajout', 'admin.communication_history.add', 'Historique communication', NULL, '2026-02-26 12:47:09'),
(98, 'Historique communication – Modifier', 'admin.communication_history.modify', 'Historique communication', NULL, '2026-02-26 12:47:09'),
(99, 'Historique communication – Supprimer', 'admin.communication_history.delete', 'Historique communication', NULL, '2026-02-26 12:47:09'),
(100, 'Documents – Voir', 'admin.documents.view', 'Documents', NULL, '2026-02-26 12:47:09'),
(101, 'Documents – Ajout', 'admin.documents.add', 'Documents', NULL, '2026-02-26 12:47:09'),
(102, 'Documents – Modifier', 'admin.documents.modify', 'Documents', NULL, '2026-02-26 12:47:09'),
(103, 'Documents – Supprimer', 'admin.documents.delete', 'Documents', NULL, '2026-02-26 12:47:09'),
(104, 'Planning & KPI – Voir', 'admin.planning.view', 'Planning & KPI', NULL, '2026-02-26 12:47:09'),
(105, 'Planning & KPI – Modifier', 'admin.planning.modify', 'Planning & KPI', NULL, '2026-02-26 12:47:09'),
(106, 'Nos offres ??? Voir', 'admin.offers.view', 'Nos offres', NULL, '2026-02-28 14:41:14'),
(107, 'Nos offres ??? Ajout', 'admin.offers.add', 'Nos offres', NULL, '2026-02-28 14:41:14'),
(108, 'Nos offres ??? Modifier', 'admin.offers.modify', 'Nos offres', NULL, '2026-02-28 14:41:14'),
(109, 'Nos offres ??? Supprimer', 'admin.offers.delete', 'Nos offres', NULL, '2026-02-28 14:41:14');

-- organisation
INSERT INTO `organisation` (`id`, `name`, `code`, `description`, `address`, `phone`, `email`, `website`, `postal_code`, `city`, `country`, `rccm`, `nif`, `sector`, `notes`, `logo`, `favicon`, `cover_image`, `facebook_url`, `linkedin_url`, `twitter_url`, `instagram_url`, `youtube_url`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Expertise Humanitaire et Sociale', 'EXPERT', '\"<b>Expertise Humanitaire et Sociale</b> est une plateforme logicielle, connue pour structurer et piloter l\'organisation : gestion des équipes, des projets, des missions sur le terrain, de la communication interne et des documents, dans un cadre humanitaire et sociale\"&nbsp;', 'AV. ITEBERO N°100 Q/ MABANGA NORD C/ KARISIMBI\r\nAV. ITEBERO N°100 Q/ MABANGA NORD Kivu', '0975579097', 'expertisehs@gmail.com', 'https://www.expertisehs.org/', 'dsdsdsd', 'Goma', 'Congo-Kinshasa', 'RCCM/CD-NK-0012', 'xcccdefd', 'Humanitaire et Expertise', 'fdfdfdf', 'uploads/organisations/logo_1_1773072696_69aef138c68f7.png', 'uploads/organisations/favicon_1_1773090564_69af37046d7d5.jpg', 'uploads/organisations/cover_1_1772279108_69a2d5440e312.jpg', 'https://web.facebook.com/expertisehumanitaireetsociale', 'https://www.linkedin.com/in/expertisehsociale/', 'https://x.com/expertisehs_', 'https://www.instagram.com/expertisehs_/', 'https://www.youtube.com/@expertisehs', 1, '2026-02-23 01:36:15', '2026-03-09 21:09:24');

-- role
INSERT INTO `role` (`id`, `organisation_id`, `name`, `code`, `description`, `is_system`, `created_at`, `updated_at`) VALUES
(1, 1, 'Super Administrateur', 'superadmin', NULL, 1, '2026-02-23 01:36:15', '2026-02-23 01:36:15'),
(2, 1, 'Administrateur', 'admin', NULL, 1, '2026-02-23 01:36:15', '2026-02-23 01:36:15'),
(3, 1, 'Manager', 'manager', NULL, 1, '2026-02-23 01:36:15', '2026-02-23 01:36:15'),
(4, 1, 'Collaborateure', 'staff', NULL, 1, '2026-02-23 01:36:15', '2026-02-23 15:06:53'),
(5, 1, 'Client', 'client', NULL, 1, '2026-02-28 13:11:02', '2026-02-28 13:11:02');

-- mission_status
INSERT INTO `mission_status` (`id`, `name`, `code`, `description`, `sort_order`, `created_at`) VALUES
(1, 'Planifiée', 'planned', NULL, 1, '2026-02-23 02:01:01'),
(2, 'En cours', 'in_progress', NULL, 2, '2026-02-23 02:01:01'),
(3, 'Terminée', 'completed', NULL, 3, '2026-02-23 02:01:01'),
(4, 'Annulée', 'cancelled', NULL, 4, '2026-02-23 02:01:01');

-- mission_type
INSERT INTO `mission_type` (`id`, `organisation_id`, `name`, `code`, `description`, `created_at`, `updated_at`) VALUES
(1, 1, 'Évaluation', 'eval', NULL, '2026-02-23 02:01:01', '2026-02-23 02:01:01'),
(2, 1, 'Urgence', 'emerg', NULL, '2026-02-23 02:01:01', '2026-02-23 02:01:01'),
(3, 1, 'Développement', 'dev', NULL, '2026-02-23 02:01:01', '2026-02-23 02:01:01');

-- organisation_organisation_type
INSERT INTO `organisation_organisation_type` (`organisation_id`, `type_code`) VALUES
(1, 'association'),
(1, 'company'),
(1, 'ngo');

-- user
INSERT INTO `user` (`id`, `organisation_id`, `email`, `password_hash`, `first_name`, `last_name`, `phone`, `avatar_url`, `is_active`, `email_verified_at`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'thecarinsiwa@gmail.com', '$2y$10$Vjxm/UiO25zeda8kReYQ..DLFRVTzxqxU426KNUgq6/FrQpC00hVq', 'Carin', 'Siwa', '0975579097', NULL, 1, '2026-02-23 01:36:45', NULL, '2026-02-23 01:36:45', '2026-02-23 15:11:00');

-- user_role
INSERT INTO `user_role` (`user_id`, `role_id`, `created_at`) VALUES
(1, 1, '2026-02-26 22:18:33');

-- staff
INSERT INTO `staff` (`id`, `user_id`, `organisation_id`, `employee_number`, `phone_extension`, `work_email`, `hire_date`, `end_date`, `employment_type`, `department`, `job_title`, `is_active`, `notes`, `photo`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'STAFF-001', NULL, 'carin@expertisehs.org', '2026-02-23', NULL, 'intern', 'IT', 'Développeur senior', 1, 'fdfd', 'uploads/staff/staff_1_1772273594_69a2bfba3af24.jpg', '2026-02-23 02:14:36', '2026-02-28 10:13:14');

-- channel
INSERT INTO `channel` (`id`, `organisation_id`, `name`, `code`, `channel_type`, `description`, `created_by_user_id`, `created_at`, `updated_at`) VALUES
(1, 1, 'Information sur le projet ASBL', 'CANAL-01', 'public', 'fdfdfdf', 1, '2026-02-23 19:30:44', '2026-02-23 19:30:44');

-- governance_page
INSERT INTO `governance_page` (`id`, `organisation_id`, `intro_block1`, `intro_block2`, `section_instances_title`, `section_instances_text`, `section_bureaux_title`, `section_bureaux_text`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Notre gouvernance et ce que signifie ??tre une association. Guide de nos instances et de nos bureaux.', 'La gouvernance de l\'organisation assure la coh??rence des d??cisions, le respect des mandats et la redevabilit?? envers les b??n??ficiaires et les partenaires.', 'Instances', 'Les instances dirigeantes d??finissent les orientations strat??giques et contr??lent la mise en ??uvre des activit??s.', 'Bureaux', 'Nos bureaux dans le monde assurent la coordination op??rationnelle et le lien avec les terrains d\'intervention.', '2026-02-28 12:39:23', '2026-02-28 12:39:23'),
(2, 1, 'dsdsdsd', 'dsdsd', 'dsdsd', 'dsdsd', 'dsds', 'sdsdsd', '2026-03-03 12:36:26', '2026-03-03 12:36:26');

-- reports_finances_page
INSERT INTO `reports_finances_page` (`id`, `organisation_id`, `intro_block1`, `intro_block2`, `section_activity_title`, `section_activity_text`, `section_finance_title`, `section_finance_text`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Rapports annuels d\'activité et financiers, origine des fonds et utilisation.', 'Nous nous engageons à rendre compte de notre activité et de l\'usage des ressources qui nous sont confiées.', 'Rapports d\'activité', 'Les rapports annuels présentent les réalisations, les enseignements et les perspectives de l\'organisation.', 'Transparence financière', 'L\'origine des fonds et leur répartition sont détaillées dans nos documents financiers publics.', '2026-02-28 12:28:16', '2026-02-28 12:31:21');

-- responsibility_commitment
INSERT INTO `responsibility_commitment` (`id`, `organisation_id`, `title`, `description`, `icon`, `sort_order`, `created_at`, `updated_at`) VALUES
(4, NULL, 'Éthique et intégrité', 'Des politiques et dispositifs encadrent nos pratiques pour garantir l\'intégrité de nos actions.', 'bi-shield-check', 1, '2026-02-28 12:08:37', '2026-02-28 12:08:37'),
(5, NULL, 'Diversité et inclusion', 'Nous œuvrons pour un environnement inclusif et une représentation équitable au sein de l\'organisation.', 'bi-people', 2, '2026-02-28 12:08:37', '2026-02-28 12:08:37'),
(6, NULL, 'Environnement', 'Nous nous efforçons de réduire l\'impact environnemental de nos activités et de nos déplacements.', 'bi-globe2', 3, '2026-02-28 12:08:37', '2026-02-28 12:08:37'),
(7, NULL, 'VBG', 'Lutte contre la Violence', 'bi-award', 4, '2026-03-10 12:24:13', '2026-03-10 12:24:13');

-- responsibility_page
INSERT INTO `responsibility_page` (`id`, `organisation_id`, `intro_block1`, `intro_block2`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Politiques et rapports sur nos engagements éthiques, diversité et impact environnemental.', 'Notre responsabilité s\'exerce vis-à-vis des personnes que nous accompagnons, de nos équipes et de l\'environnement.', '2026-02-28 12:02:31', '2026-02-28 12:08:37');


SET FOREIGN_KEY_CHECKS=1;
COMMIT;
