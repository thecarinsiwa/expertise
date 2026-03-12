-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : jeu. 12 mars 2026 à 19:55
-- Version du serveur : 8.0.30
-- Version de PHP : 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `expertise`
--

-- --------------------------------------------------------

--
-- Structure de la table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` int UNSIGNED DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `announcement`
--

CREATE TABLE `announcement` (
  `id` int UNSIGNED NOT NULL,
  `organisation_id` int UNSIGNED NOT NULL,
  `channel_id` int UNSIGNED DEFAULT NULL,
  `author_user_id` int UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `cover_image` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT '0',
  `published_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `announcement_reaction`
--

CREATE TABLE `announcement_reaction` (
  `announcement_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `reaction_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'like',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `archiving`
--

CREATE TABLE `archiving` (
  `id` int UNSIGNED NOT NULL,
  `document_id` int UNSIGNED NOT NULL,
  `archived_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `archived_by_user_id` int UNSIGNED DEFAULT NULL,
  `archive_location` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `retention_until` date DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `assignment`
--

CREATE TABLE `assignment` (
  `id` int UNSIGNED NOT NULL,
  `staff_id` int UNSIGNED NOT NULL,
  `position_id` int UNSIGNED NOT NULL,
  `unit_id` int UNSIGNED DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `attachment`
--

CREATE TABLE `attachment` (
  `id` int UNSIGNED NOT NULL,
  `attachable_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachable_id` int UNSIGNED NOT NULL,
  `uploaded_by_user_id` int UNSIGNED NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` int UNSIGNED DEFAULT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `audit`
--

CREATE TABLE `audit` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `auditable_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `auditable_id` int UNSIGNED NOT NULL,
  `event` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `authentication`
--

CREATE TABLE `authentication` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `provider` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_user_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token` text COLLATE utf8mb4_unicode_ci,
  `refresh_token` text COLLATE utf8mb4_unicode_ci,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `authorization`
--

CREATE TABLE `authorization` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `authorizable_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `authorizable_id` int UNSIGNED NOT NULL,
  `permission` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `granted_by_user_id` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `bailleur`
--

CREATE TABLE `bailleur` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `calendar`
--

CREATE TABLE `calendar` (
  `id` int UNSIGNED NOT NULL,
  `organisation_id` int UNSIGNED DEFAULT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `colour` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `channel`
--

CREATE TABLE `channel` (
  `id` int UNSIGNED NOT NULL,
  `organisation_id` int UNSIGNED DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `channel_type` enum('public','private','direct','announcement') COLLATE utf8mb4_unicode_ci DEFAULT 'public',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_by_user_id` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `channel_member`
--

CREATE TABLE `channel_member` (
  `channel_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `role` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'member',
  `joined_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `comment`
--

CREATE TABLE `comment` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `commentable_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `commentable_id` int UNSIGNED NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_comment_id` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `communication_history`
--

CREATE TABLE `communication_history` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `entity_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` int UNSIGNED NOT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `contract`
--

CREATE TABLE `contract` (
  `id` int UNSIGNED NOT NULL,
  `staff_id` int UNSIGNED NOT NULL,
  `contract_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `document_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `conversation`
--

CREATE TABLE `conversation` (
  `id` int UNSIGNED NOT NULL,
  `channel_id` int UNSIGNED DEFAULT NULL,
  `announcement_id` int UNSIGNED DEFAULT NULL,
  `subject` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conversation_type` enum('channel','direct','thread') COLLATE utf8mb4_unicode_ci DEFAULT 'direct',
  `created_by_user_id` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `conversation_participant`
--

CREATE TABLE `conversation_participant` (
  `conversation_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `joined_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `deliverable`
--

CREATE TABLE `deliverable` (
  `id` int UNSIGNED NOT NULL,
  `project_id` int UNSIGNED NOT NULL,
  `task_id` int UNSIGNED DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('pending','in_progress','submitted','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `department`
--

CREATE TABLE `department` (
  `id` int UNSIGNED NOT NULL,
  `organisation_id` int UNSIGNED NOT NULL,
  `entity_id` int UNSIGNED DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `photo` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Photo du d??partement',
  `head_user_id` int UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `document`
--

CREATE TABLE `document` (
  `id` int UNSIGNED NOT NULL,
  `organisation_id` int UNSIGNED DEFAULT NULL,
  `document_category_id` int UNSIGNED DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `document_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_image` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Photo de couverture (miniature)',
  `current_version_id` int UNSIGNED DEFAULT NULL,
  `created_by_user_id` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `document_category`
--

CREATE TABLE `document_category` (
  `id` int UNSIGNED NOT NULL,
  `organisation_id` int UNSIGNED DEFAULT NULL,
  `parent_id` int UNSIGNED DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `document_version`
--

CREATE TABLE `document_version` (
  `id` int UNSIGNED NOT NULL,
  `document_id` int UNSIGNED NOT NULL,
  `version_number` int UNSIGNED NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` int UNSIGNED DEFAULT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `checksum` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by_user_id` int UNSIGNED DEFAULT NULL,
  `change_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `event`
--

CREATE TABLE `event` (
  `id` int UNSIGNED NOT NULL,
  `calendar_id` int UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `start_at` timestamp NOT NULL,
  `end_at` timestamp NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_all_day` tinyint(1) NOT NULL DEFAULT '0',
  `recurrence_rule` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by_user_id` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `governance_page`
--

CREATE TABLE `governance_page` (
  `id` int UNSIGNED NOT NULL,
  `organisation_id` int UNSIGNED DEFAULT NULL COMMENT 'NULL = contenu par d??faut',
  `intro_block1` text COLLATE utf8mb4_unicode_ci,
  `intro_block2` text COLLATE utf8mb4_unicode_ci,
  `section_instances_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `section_instances_text` text COLLATE utf8mb4_unicode_ci,
  `section_bureaux_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `section_bureaux_text` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `message`
--

CREATE TABLE `message` (
  `id` int UNSIGNED NOT NULL,
  `conversation_id` int UNSIGNED NOT NULL,
  `parent_message_id` int UNSIGNED DEFAULT NULL,
  `sender_user_id` int UNSIGNED NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_edited` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `milestone`
--

CREATE TABLE `milestone` (
  `id` int UNSIGNED NOT NULL,
  `project_id` int UNSIGNED NOT NULL,
  `project_phase_id` int UNSIGNED DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `due_date` date NOT NULL,
  `status` enum('pending','achieved','delayed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `mission`
--

CREATE TABLE `mission` (
  `id` int UNSIGNED NOT NULL,
  `organisation_id` int UNSIGNED NOT NULL,
  `mission_type_id` int UNSIGNED DEFAULT NULL,
  `mission_status_id` int UNSIGNED DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `cover_image` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by_user_id` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `mission_assignment`
--

CREATE TABLE `mission_assignment` (
  `id` int UNSIGNED NOT NULL,
  `mission_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `role_in_mission` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `mission_bailleur`
--

CREATE TABLE `mission_bailleur` (
  `mission_id` int UNSIGNED NOT NULL,
  `bailleur_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `mission_expense`
--

CREATE TABLE `mission_expense` (
  `id` int UNSIGNED NOT NULL,
  `mission_id` int UNSIGNED NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `amount` decimal(15,2) NOT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci DEFAULT 'USD',
  `expense_date` date DEFAULT NULL,
  `receipt_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','submitted','approved','reimbursed','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `user_id` int UNSIGNED DEFAULT NULL,
  `created_by_user_id` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `mission_order`
--

CREATE TABLE `mission_order` (
  `id` int UNSIGNED NOT NULL,
  `mission_id` int UNSIGNED NOT NULL,
  `order_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `authorised_by_user_id` int UNSIGNED DEFAULT NULL,
  `document_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('draft','sent','signed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `mission_plan`
--

CREATE TABLE `mission_plan` (
  `id` int UNSIGNED NOT NULL,
  `mission_id` int UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` text COLLATE utf8mb4_unicode_ci,
  `image_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `planned_date` date DEFAULT NULL,
  `sequence` int UNSIGNED DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `mission_report`
--

CREATE TABLE `mission_report` (
  `id` int UNSIGNED NOT NULL,
  `mission_id` int UNSIGNED NOT NULL,
  `author_user_id` int UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` text COLLATE utf8mb4_unicode_ci,
  `summary` text COLLATE utf8mb4_unicode_ci,
  `report_date` date DEFAULT NULL,
  `status` enum('draft','submitted','final') COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `document_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `mission_status`
--

CREATE TABLE `mission_status` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `sort_order` int UNSIGNED DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `mission_type`
--

CREATE TABLE `mission_type` (
  `id` int UNSIGNED NOT NULL,
  `organisation_id` int UNSIGNED DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notification`
--

CREATE TABLE `notification` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `related_entity_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `related_entity_id` int UNSIGNED DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `objective`
--

CREATE TABLE `objective` (
  `id` int UNSIGNED NOT NULL,
  `mission_id` int UNSIGNED NOT NULL,
  `title` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `sequence` int UNSIGNED DEFAULT '0',
  `is_achieved` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `offer`
--

CREATE TABLE `offer` (
  `id` int UNSIGNED NOT NULL,
  `organisation_id` int UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `cover_image` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mission_id` int UNSIGNED DEFAULT NULL,
  `project_id` int UNSIGNED DEFAULT NULL,
  `status` enum('draft','published','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `published_at` date DEFAULT NULL,
  `deadline_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `offer_application`
--

CREATE TABLE `offer_application` (
  `id` int UNSIGNED NOT NULL,
  `offer_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `cv_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','reviewed','accepted','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `organisation`
--

CREATE TABLE `organisation` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `address` text COLLATE utf8mb4_unicode_ci,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Code postal',
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ville',
  `country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Pays',
  `rccm` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'RCCM ??? Registre de Commerce et de Cr??dit Mobilier',
  `nif` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'NIF ??? Num??ro d''Identification Fiscal (DGI)',
  `sector` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Secteur d''activit??',
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT 'Notes internes',
  `logo` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Logo de l''organisation',
  `favicon` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Favicon de l''organisation (icône onglet)',
  `cover_image` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Photo de couverture (bannière)',
  `facebook_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Facebook',
  `linkedin_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'LinkedIn',
  `twitter_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Twitter / X',
  `instagram_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Instagram',
  `youtube_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'YouTube',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `organisational_entity`
--

CREATE TABLE `organisational_entity` (
  `id` int UNSIGNED NOT NULL,
  `organisation_id` int UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entity_type` enum('department','service','unit','division') COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_entity_id` int UNSIGNED DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `organisational_hierarchy`
--

CREATE TABLE `organisational_hierarchy` (
  `id` int UNSIGNED NOT NULL,
  `organisation_id` int UNSIGNED NOT NULL,
  `parent_entity_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_entity_id` int UNSIGNED NOT NULL,
  `child_entity_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `child_entity_id` int UNSIGNED NOT NULL,
  `level` int UNSIGNED DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `organisation_organisation_type`
--

CREATE TABLE `organisation_organisation_type` (
  `organisation_id` int UNSIGNED NOT NULL,
  `type_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT '0',
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `performance_indicator`
--

CREATE TABLE `performance_indicator` (
  `id` int UNSIGNED NOT NULL,
  `organisation_id` int UNSIGNED DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `indicator_type` enum('numeric','percentage','boolean','rating') COLLATE utf8mb4_unicode_ci DEFAULT 'numeric',
  `unit` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_value` decimal(15,4) DEFAULT NULL,
  `current_value` decimal(15,4) DEFAULT NULL,
  `period_type` enum('daily','weekly','monthly','quarterly','yearly') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `permission`
--

CREATE TABLE `permission` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `portfolio`
--

CREATE TABLE `portfolio` (
  `id` int UNSIGNED NOT NULL,
  `organisation_id` int UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `position`
--

CREATE TABLE `position` (
  `id` int UNSIGNED NOT NULL,
  `organisation_id` int UNSIGNED NOT NULL,
  `unit_id` int UNSIGNED DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `grade_level` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `position_history`
--

CREATE TABLE `position_history` (
  `id` int UNSIGNED NOT NULL,
  `staff_id` int UNSIGNED NOT NULL,
  `position_id` int UNSIGNED NOT NULL,
  `unit_id` int UNSIGNED DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `profile`
--

CREATE TABLE `profile` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `job_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cv_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `skills` json DEFAULT NULL,
  `experience` json DEFAULT NULL,
  `education` json DEFAULT NULL,
  `preferences` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `programme`
--

CREATE TABLE `programme` (
  `id` int UNSIGNED NOT NULL,
  `portfolio_id` int UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `project`
--

CREATE TABLE `project` (
  `id` int UNSIGNED NOT NULL,
  `organisation_id` int UNSIGNED NOT NULL,
  `programme_id` int UNSIGNED DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `cover_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('draft','planned','in_progress','on_hold','completed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `priority` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `manager_user_id` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `project_assignment`
--

CREATE TABLE `project_assignment` (
  `id` int UNSIGNED NOT NULL,
  `project_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `role_in_project` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `allocation_percent` decimal(5,2) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `project_bailleur`
--

CREATE TABLE `project_bailleur` (
  `project_id` int UNSIGNED NOT NULL,
  `bailleur_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `project_budget`
--

CREATE TABLE `project_budget` (
  `id` int UNSIGNED NOT NULL,
  `project_id` int UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci DEFAULT 'USD',
  `budget_type` enum('initial','revised','actual') COLLATE utf8mb4_unicode_ci DEFAULT 'initial',
  `fiscal_year` int UNSIGNED DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `project_phase`
--

CREATE TABLE `project_phase` (
  `id` int UNSIGNED NOT NULL,
  `project_id` int UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sequence` int UNSIGNED DEFAULT '0',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `project_resource`
--

CREATE TABLE `project_resource` (
  `id` int UNSIGNED NOT NULL,
  `project_id` int UNSIGNED NOT NULL,
  `resource_type` enum('human','material','equipment','financial') COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) DEFAULT '1.00',
  `unit` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cost_per_unit` decimal(15,2) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `reports_finances_page`
--

CREATE TABLE `reports_finances_page` (
  `id` int UNSIGNED NOT NULL,
  `organisation_id` int UNSIGNED DEFAULT NULL COMMENT 'NULL = contenu par d??faut',
  `intro_block1` text COLLATE utf8mb4_unicode_ci,
  `intro_block2` text COLLATE utf8mb4_unicode_ci,
  `section_activity_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `section_activity_text` text COLLATE utf8mb4_unicode_ci,
  `section_finance_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `section_finance_text` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `responsibility_commitment`
--

CREATE TABLE `responsibility_commitment` (
  `id` int UNSIGNED NOT NULL,
  `organisation_id` int UNSIGNED DEFAULT NULL COMMENT 'NULL = global',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Bootstrap Icons class e.g. bi-shield-check',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `responsibility_page`
--

CREATE TABLE `responsibility_page` (
  `id` int UNSIGNED NOT NULL,
  `organisation_id` int UNSIGNED DEFAULT NULL COMMENT 'NULL = contenu par d??faut',
  `intro_block1` text COLLATE utf8mb4_unicode_ci,
  `intro_block2` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `role`
--

CREATE TABLE `role` (
  `id` int UNSIGNED NOT NULL,
  `organisation_id` int UNSIGNED DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `role_permission`
--

CREATE TABLE `role_permission` (
  `role_id` int UNSIGNED NOT NULL,
  `permission_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `schedule`
--

CREATE TABLE `schedule` (
  `id` int UNSIGNED NOT NULL,
  `organisation_id` int UNSIGNED DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `schedule_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by_user_id` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `service`
--

CREATE TABLE `service` (
  `id` int UNSIGNED NOT NULL,
  `department_id` int UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `photo` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Photo du service',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `session`
--

CREATE TABLE `session` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` text COLLATE utf8mb4_unicode_ci,
  `last_activity` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `staff`
--

CREATE TABLE `staff` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `organisation_id` int UNSIGNED NOT NULL,
  `employee_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_extension` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Poste / Extension',
  `work_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Email professionnel',
  `hire_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL COMMENT 'Date de fin de contrat',
  `employment_type` enum('full_time','part_time','contract','intern','freelance') COLLATE utf8mb4_unicode_ci DEFAULT 'full_time',
  `department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'D??partement / Service',
  `job_title` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Intitul?? du poste',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT 'Notes RH',
  `photo` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Photo du personnel',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sub_task`
--

CREATE TABLE `sub_task` (
  `id` int UNSIGNED NOT NULL,
  `task_id` int UNSIGNED NOT NULL,
  `title` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `sequence` int UNSIGNED DEFAULT '0',
  `status` enum('todo','in_progress','done','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'todo',
  `due_date` date DEFAULT NULL,
  `assigned_user_id` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `task`
--

CREATE TABLE `task` (
  `id` int UNSIGNED NOT NULL,
  `project_id` int UNSIGNED NOT NULL,
  `project_phase_id` int UNSIGNED DEFAULT NULL,
  `parent_task_id` int UNSIGNED DEFAULT NULL,
  `title` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `sequence` int UNSIGNED DEFAULT '0',
  `status` enum('todo','in_progress','review','done','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'todo',
  `priority` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `estimated_hours` decimal(10,2) DEFAULT NULL,
  `assigned_user_id` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `unit`
--

CREATE TABLE `unit` (
  `id` int UNSIGNED NOT NULL,
  `service_id` int UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `photo` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Photo de l''unit??',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

CREATE TABLE `user` (
  `id` int UNSIGNED NOT NULL,
  `organisation_id` int UNSIGNED DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user_group`
--

CREATE TABLE `user_group` (
  `id` int UNSIGNED NOT NULL,
  `organisation_id` int UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user_group_member`
--

CREATE TABLE `user_group_member` (
  `user_group_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user_role`
--

CREATE TABLE `user_role` (
  `user_id` int UNSIGNED NOT NULL,
  `role_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
