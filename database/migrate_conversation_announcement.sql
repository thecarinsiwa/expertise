-- Lier les conversations à une annonce (optionnel)
ALTER TABLE `conversation`
  ADD COLUMN `announcement_id` INT UNSIGNED DEFAULT NULL AFTER `channel_id`,
  ADD KEY `idx_conversation_announcement` (`announcement_id`),
  ADD CONSTRAINT `fk_conversation_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `announcement` (`id`) ON DELETE SET NULL;
