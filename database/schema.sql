--
-- Index pour la table `mission_assignment`
--
ALTER TABLE `mission_assignment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mission_assignment_mission` (`mission_id`),
  ADD KEY `idx_mission_assignment_user` (`user_id`);

--
-- Index pour la table `mission_bailleur`
--
ALTER TABLE `mission_bailleur`
  ADD PRIMARY KEY (`mission_id`,`bailleur_id`),
  ADD KEY `idx_mission_bailleur_bailleur` (`bailleur_id`);

--
-- Index pour la table `mission_expense`
--
ALTER TABLE `mission_expense`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mission_expense_mission` (`mission_id`),
  ADD KEY `idx_mission_expense_created_by` (`created_by_user_id`);

--
-- Index pour la table `mission_order`
--
ALTER TABLE `mission_order`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mission_order_mission` (`mission_id`),
  ADD KEY `idx_mission_order_authorised` (`authorised_by_user_id`);

--
-- Index pour la table `mission_plan`
--
ALTER TABLE `mission_plan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mission_plan_mission` (`mission_id`);

--
-- Index pour la table `mission_report`
--
ALTER TABLE `mission_report`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mission_report_mission` (`mission_id`),
  ADD KEY `idx_mission_report_author` (`author_user_id`);

--
-- Index pour la table `mission_status`
--
ALTER TABLE `mission_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_mission_status_code` (`code`);

--
-- Index pour la table `mission_type`
--
ALTER TABLE `mission_type`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mission_type_organisation` (`organisation_id`);

--
-- Index pour la table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notification_user` (`user_id`),
  ADD KEY `idx_notification_read` (`is_read`);

--
-- Index pour la table `objective`
--
ALTER TABLE `objective`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_objective_mission` (`mission_id`);

--
-- Index pour la table `offer`
--
ALTER TABLE `offer`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_offer_organisation` (`organisation_id`),
  ADD KEY `idx_offer_mission` (`mission_id`),
  ADD KEY `idx_offer_project` (`project_id`),
  ADD KEY `idx_offer_status` (`status`);

--
-- Index pour la table `offer_application`
--
ALTER TABLE `offer_application`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_offer_application_offer_user` (`offer_id`,`user_id`),
  ADD KEY `idx_offer_application_offer` (`offer_id`),
  ADD KEY `idx_offer_application_user` (`user_id`);

--
-- Index pour la table `organisation`
--
ALTER TABLE `organisation`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_organisation_code` (`code`);

--
-- Index pour la table `organisational_entity`
--
ALTER TABLE `organisational_entity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_organisation` (`organisation_id`),
  ADD KEY `idx_parent` (`parent_entity_id`);

--
-- Index pour la table `organisational_hierarchy`
--
ALTER TABLE `organisational_hierarchy`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hierarchy_organisation` (`organisation_id`),
  ADD KEY `idx_hierarchy_parent` (`parent_entity_type`,`parent_entity_id`),
  ADD KEY `idx_hierarchy_child` (`child_entity_type`,`child_entity_id`);

--
-- Index pour la table `organisation_organisation_type`
--
ALTER TABLE `organisation_organisation_type`
  ADD PRIMARY KEY (`organisation_id`,`type_code`),
  ADD KEY `idx_org_type_org` (`organisation_id`);

--
-- Index pour la table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_prt_token` (`token`),
  ADD KEY `idx_prt_user` (`user_id`);

--
-- Index pour la table `performance_indicator`
--
ALTER TABLE `performance_indicator`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_perf_ind_organisation` (`organisation_id`);

--
-- Index pour la table `permission`
--
ALTER TABLE `permission`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_permission_code` (`code`);

--
-- Index pour la table `portfolio`
--
ALTER TABLE `portfolio`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_portfolio_organisation` (`organisation_id`);

--
-- Index pour la table `position`
--
ALTER TABLE `position`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_position_organisation` (`organisation_id`),
  ADD KEY `idx_position_unit` (`unit_id`);

--
-- Index pour la table `position_history`
--
ALTER TABLE `position_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pos_hist_staff` (`staff_id`),
  ADD KEY `idx_pos_hist_position` (`position_id`),
  ADD KEY `fk_pos_hist_unit` (`unit_id`);

--
-- Index pour la table `profile`
--
ALTER TABLE `profile`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_profile_user` (`user_id`);

--
-- Index pour la table `programme`
--
ALTER TABLE `programme`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_programme_portfolio` (`portfolio_id`);

--
-- Index pour la table `project`
--
ALTER TABLE `project`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_organisation` (`organisation_id`),
  ADD KEY `idx_project_programme` (`programme_id`),
  ADD KEY `idx_project_manager` (`manager_user_id`);

--
-- Index pour la table `project_assignment`
--
ALTER TABLE `project_assignment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_assignment_project` (`project_id`),
  ADD KEY `idx_project_assignment_user` (`user_id`);

--
-- Index pour la table `project_bailleur`
--
ALTER TABLE `project_bailleur`
  ADD PRIMARY KEY (`project_id`,`bailleur_id`),
  ADD KEY `idx_project_bailleur_bailleur` (`bailleur_id`);

--
-- Index pour la table `project_budget`
--
ALTER TABLE `project_budget`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_budget_project` (`project_id`);

--
-- Index pour la table `project_phase`
--
ALTER TABLE `project_phase`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_phase_project` (`project_id`);

--
-- Index pour la table `project_resource`
--
ALTER TABLE `project_resource`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_resource_project` (`project_id`);

--
-- Index pour la table `reports_finances_page`
--
ALTER TABLE `reports_finances_page`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_reports_finances_page_organisation` (`organisation_id`);

--
-- Index pour la table `responsibility_commitment`
--
ALTER TABLE `responsibility_commitment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_responsibility_commitment_organisation` (`organisation_id`);

--
-- Index pour la table `responsibility_page`
--
ALTER TABLE `responsibility_page`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_responsibility_page_organisation` (`organisation_id`);

--
-- Index pour la table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_role_code_org` (`organisation_id`,`code`);

--
-- Index pour la table `role_permission`
--
ALTER TABLE `role_permission`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `idx_role_perm_permission` (`permission_id`);

--
-- Index pour la table `schedule`
--
ALTER TABLE `schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_schedule_organisation` (`organisation_id`),
  ADD KEY `idx_schedule_created_by` (`created_by_user_id`);

--
-- Index pour la table `service`
--
ALTER TABLE `service`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_service_department` (`department_id`);

--
-- Index pour la table `session`
--
ALTER TABLE `session`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session_user` (`user_id`);

--
-- Index pour la table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_staff_user_org` (`user_id`,`organisation_id`),
  ADD KEY `idx_staff_organisation` (`organisation_id`);

--
-- Index pour la table `sub_task`
--
ALTER TABLE `sub_task`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sub_task_task` (`task_id`),
  ADD KEY `idx_sub_task_assigned` (`assigned_user_id`);

--
-- Index pour la table `task`
--
ALTER TABLE `task`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_task_project` (`project_id`),
  ADD KEY `idx_task_phase` (`project_phase_id`),
  ADD KEY `idx_task_parent` (`parent_task_id`),
  ADD KEY `idx_task_assigned` (`assigned_user_id`);

--
-- Index pour la table `unit`
--
ALTER TABLE `unit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_unit_service` (`service_id`);

--
-- Index pour la table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_user_email` (`email`),
  ADD KEY `idx_user_organisation` (`organisation_id`);

--
-- Index pour la table `user_group`
--
ALTER TABLE `user_group`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_group_organisation` (`organisation_id`);

--
-- Index pour la table `user_group_member`
--
ALTER TABLE `user_group_member`
  ADD PRIMARY KEY (`user_group_id`,`user_id`),
  ADD KEY `idx_ugm_user` (`user_id`);

--
-- Index pour la table `user_role`
--
ALTER TABLE `user_role`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `idx_user_role_role` (`role_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `announcement`
--
ALTER TABLE `announcement`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `archiving`
--
ALTER TABLE `archiving`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `assignment`
--
ALTER TABLE `assignment`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `attachment`
--
ALTER TABLE `attachment`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `audit`
--
ALTER TABLE `audit`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `authentication`
--
ALTER TABLE `authentication`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `authorization`
--
ALTER TABLE `authorization`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `bailleur`
--
ALTER TABLE `bailleur`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `calendar`
--
ALTER TABLE `calendar`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `channel`
--
ALTER TABLE `channel`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `comment`
--
ALTER TABLE `comment`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `communication_history`
--
ALTER TABLE `communication_history`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `contract`
--
ALTER TABLE `contract`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `conversation`
--
ALTER TABLE `conversation`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `deliverable`
--
ALTER TABLE `deliverable`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `department`
--
ALTER TABLE `department`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `document`
--
ALTER TABLE `document`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `document_category`
--
ALTER TABLE `document_category`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `document_version`
--
ALTER TABLE `document_version`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `event`
--
ALTER TABLE `event`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `governance_page`
--
ALTER TABLE `governance_page`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `message`
--
ALTER TABLE `message`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `milestone`
--
ALTER TABLE `milestone`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `mission`
--
ALTER TABLE `mission`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `mission_assignment`
--
ALTER TABLE `mission_assignment`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT pour la table `mission_expense`
--
ALTER TABLE `mission_expense`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `mission_order`
--
ALTER TABLE `mission_order`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `mission_plan`
--
ALTER TABLE `mission_plan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT pour la table `mission_report`
--
ALTER TABLE `mission_report`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `mission_status`
--
ALTER TABLE `mission_status`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `mission_type`
--
ALTER TABLE `mission_type`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `notification`
--
ALTER TABLE `notification`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `objective`
--
ALTER TABLE `objective`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT pour la table `offer`
--
ALTER TABLE `offer`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `offer_application`
--
ALTER TABLE `offer_application`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `organisation`
--
ALTER TABLE `organisation`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `organisational_entity`
--
ALTER TABLE `organisational_entity`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `organisational_hierarchy`
--
ALTER TABLE `organisational_hierarchy`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `performance_indicator`
--
ALTER TABLE `performance_indicator`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `permission`
--
ALTER TABLE `permission`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=936;

--
-- AUTO_INCREMENT pour la table `portfolio`
--
ALTER TABLE `portfolio`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `position`
--
ALTER TABLE `position`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `position_history`
--
ALTER TABLE `position_history`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `profile`
--
ALTER TABLE `profile`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `programme`
--
ALTER TABLE `programme`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `project`
--
ALTER TABLE `project`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `project_assignment`
--
ALTER TABLE `project_assignment`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `project_budget`
--
ALTER TABLE `project_budget`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `project_phase`
--
ALTER TABLE `project_phase`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `project_resource`
--
ALTER TABLE `project_resource`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `reports_finances_page`
--
ALTER TABLE `reports_finances_page`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `responsibility_commitment`
--
ALTER TABLE `responsibility_commitment`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `responsibility_page`
--
ALTER TABLE `responsibility_page`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `role`
--
ALTER TABLE `role`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `schedule`
--
ALTER TABLE `schedule`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `service`
--
ALTER TABLE `service`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `sub_task`
--
ALTER TABLE `sub_task`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `task`
--
ALTER TABLE `task`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `unit`
--
ALTER TABLE `unit`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `user`
--
ALTER TABLE `user`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `user_group`
--
ALTER TABLE `user_group`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `fk_activity_log_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `announcement`
--
ALTER TABLE `announcement`
  ADD CONSTRAINT `fk_announcement_author` FOREIGN KEY (`author_user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_announcement_channel` FOREIGN KEY (`channel_id`) REFERENCES `channel` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_announcement_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisation` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `announcement_reaction`
--
ALTER TABLE `announcement_reaction`
  ADD CONSTRAINT `fk_announcement_reaction_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `announcement` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_announcement_reaction_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `archiving`
--
ALTER TABLE `archiving`
  ADD CONSTRAINT `fk_archiving_archived_by` FOREIGN KEY (`archived_by_user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_archiving_document` FOREIGN KEY (`document_id`) REFERENCES `document` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `assignment`
--
ALTER TABLE `assignment`
  ADD CONSTRAINT `fk_assignment_position` FOREIGN KEY (`position_id`) REFERENCES `position` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_assignment_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_assignment_unit` FOREIGN KEY (`unit_id`) REFERENCES `unit` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `attachment`
--
ALTER TABLE `attachment`
  ADD CONSTRAINT `fk_attachment_uploaded_by` FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `audit`
--
ALTER TABLE `audit`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `authentication`
--
ALTER TABLE `authentication`
  ADD CONSTRAINT `fk_authentication_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `authorization`
--
ALTER TABLE `authorization`
  ADD CONSTRAINT `fk_authorization_granted_by` FOREIGN KEY (`granted_by_user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_authorization_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `calendar`
--
ALTER TABLE `calendar`
  ADD CONSTRAINT `fk_calendar_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisation` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_calendar_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `channel`
--
ALTER TABLE `channel`
  ADD CONSTRAINT `fk_channel_created_by` FOREIGN KEY (`created_by_user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_channel_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisation` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `channel_member`
--
ALTER TABLE `channel_member`
  ADD CONSTRAINT `fk_channel_member_channel` FOREIGN KEY (`channel_id`) REFERENCES `channel` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_channel_member_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `comment`
--
ALTER TABLE `comment`
  ADD CONSTRAINT `fk_comment_parent` FOREIGN KEY (`parent_comment_id`) REFERENCES `comment` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `communication_history`
--
ALTER TABLE `communication_history`
  ADD CONSTRAINT `fk_comm_hist_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `contract`
--
ALTER TABLE `contract`
  ADD CONSTRAINT `fk_contract_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `conversation`
--
ALTER TABLE `conversation`
  ADD CONSTRAINT `fk_conversation_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `announcement` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_conversation_channel` FOREIGN KEY (`channel_id`) REFERENCES `channel` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_conversation_created_by` FOREIGN KEY (`created_by_user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `conversation_participant`
--
ALTER TABLE `conversation_participant`
  ADD CONSTRAINT `fk_conv_part_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversation` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_conv_part_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `deliverable`
--
ALTER TABLE `deliverable`
  ADD CONSTRAINT `fk_deliverable_project` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_deliverable_task` FOREIGN KEY (`task_id`) REFERENCES `task` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `department`
--
ALTER TABLE `department`
  ADD CONSTRAINT `fk_department_entity` FOREIGN KEY (`entity_id`) REFERENCES `organisational_entity` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_department_head` FOREIGN KEY (`head_user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_department_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisation` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `document`
--
ALTER TABLE `document`
  ADD CONSTRAINT `fk_document_category` FOREIGN KEY (`document_category_id`) REFERENCES `document_category` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_document_created_by` FOREIGN KEY (`created_by_user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_document_current_version` FOREIGN KEY (`current_version_id`) REFERENCES `document_version` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_document_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisation` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `document_category`
--
ALTER TABLE `document_category`
  ADD CONSTRAINT `fk_doc_category_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisation` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_doc_category_parent` FOREIGN KEY (`parent_id`) REFERENCES `document_category` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `document_version`
--
ALTER TABLE `document_version`
  ADD CONSTRAINT `fk_doc_version_created_by` FOREIGN KEY (`created_by_user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_doc_version_document` FOREIGN KEY (`document_id`) REFERENCES `document` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `event`
--
ALTER TABLE `event`
  ADD CONSTRAINT `fk_event_calendar` FOREIGN KEY (`calendar_id`) REFERENCES `calendar` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_event_created_by` FOREIGN KEY (`created_by_user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `governance_page`
--
ALTER TABLE `governance_page`
  ADD CONSTRAINT `fk_governance_page_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisation` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `message`
--
ALTER TABLE `message`
  ADD CONSTRAINT `fk_message_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversation` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_message_parent` FOREIGN KEY (`parent_message_id`) REFERENCES `message` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_message_sender` FOREIGN KEY (`sender_user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `milestone`
--
ALTER TABLE `milestone`
  ADD CONSTRAINT `fk_milestone_phase` FOREIGN KEY (`project_phase_id`) REFERENCES `project_phase` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_milestone_project` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `mission`
--
ALTER TABLE `mission`
  ADD CONSTRAINT `fk_mission_created_by` FOREIGN KEY (`created_by_user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_mission_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisation` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mission_status` FOREIGN KEY (`mission_status_id`) REFERENCES `mission_status` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_mission_type` FOREIGN KEY (`mission_type_id`) REFERENCES `mission_type` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `mission_assignment`
--
ALTER TABLE `mission_assignment`
  ADD CONSTRAINT `fk_mission_assignment_mission` FOREIGN KEY (`mission_id`) REFERENCES `mission` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mission_assignment_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `mission_bailleur`
--
ALTER TABLE `mission_bailleur`
  ADD CONSTRAINT `fk_mission_bailleur_bailleur` FOREIGN KEY (`bailleur_id`) REFERENCES `bailleur` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mission_bailleur_mission` FOREIGN KEY (`mission_id`) REFERENCES `mission` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `mission_expense`
--
ALTER TABLE `mission_expense`
  ADD CONSTRAINT `fk_mission_expense_created_by` FOREIGN KEY (`created_by_user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_mission_expense_mission` FOREIGN KEY (`mission_id`) REFERENCES `mission` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `mission_order`
--
ALTER TABLE `mission_order`
  ADD CONSTRAINT `fk_mission_order_authorised` FOREIGN KEY (`authorised_by_user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_mission_order_mission` FOREIGN KEY (`mission_id`) REFERENCES `mission` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `mission_plan`
--
ALTER TABLE `mission_plan`
  ADD CONSTRAINT `fk_mission_plan_mission` FOREIGN KEY (`mission_id`) REFERENCES `mission` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `mission_report`
--
ALTER TABLE `mission_report`
  ADD CONSTRAINT `fk_mission_report_author` FOREIGN KEY (`author_user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mission_report_mission` FOREIGN KEY (`mission_id`) REFERENCES `mission` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `mission_type`
--
ALTER TABLE `mission_type`
  ADD CONSTRAINT `fk_mission_type_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisation` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `objective`
--
ALTER TABLE `objective`
  ADD CONSTRAINT `fk_objective_mission` FOREIGN KEY (`mission_id`) REFERENCES `mission` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `offer`
--
ALTER TABLE `offer`
  ADD CONSTRAINT `fk_offer_mission` FOREIGN KEY (`mission_id`) REFERENCES `mission` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_offer_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisation` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_offer_project` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `offer_application`
--
ALTER TABLE `offer_application`
  ADD CONSTRAINT `fk_offer_application_offer` FOREIGN KEY (`offer_id`) REFERENCES `offer` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_offer_application_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `organisational_entity`
--
ALTER TABLE `organisational_entity`
  ADD CONSTRAINT `fk_entity_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisation` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_entity_parent` FOREIGN KEY (`parent_entity_id`) REFERENCES `organisational_entity` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `organisational_hierarchy`
--
ALTER TABLE `organisational_hierarchy`
  ADD CONSTRAINT `fk_hierarchy_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisation` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `organisation_organisation_type`
--
ALTER TABLE `organisation_organisation_type`
  ADD CONSTRAINT `fk_org_type_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisation` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `fk_prt_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `performance_indicator`
--
ALTER TABLE `performance_indicator`
  ADD CONSTRAINT `fk_perf_ind_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisation` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `portfolio`
--
ALTER TABLE `portfolio`
  ADD CONSTRAINT `fk_portfolio_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisation` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `position`
--
ALTER TABLE `position`
  ADD CONSTRAINT `fk_position_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisation` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_position_unit` FOREIGN KEY (`unit_id`) REFERENCES `unit` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `position_history`
--
ALTER TABLE `position_history`
  ADD CONSTRAINT `fk_pos_hist_position` FOREIGN KEY (`position_id`) REFERENCES `position` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pos_hist_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pos_hist_unit` FOREIGN KEY (`unit_id`) REFERENCES `unit` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `profile`
--
ALTER TABLE `profile`
  ADD CONSTRAINT `fk_profile_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `programme`
--
ALTER TABLE `programme`
  ADD CONSTRAINT `fk_programme_portfolio` FOREIGN KEY (`portfolio_id`) REFERENCES `portfolio` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `project`
--
ALTER TABLE `project`
  ADD CONSTRAINT `fk_project_manager` FOREIGN KEY (`manager_user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_project_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisation` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_project_programme` FOREIGN KEY (`programme_id`) REFERENCES `programme` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `project_assignment`
--
ALTER TABLE `project_assignment`
  ADD CONSTRAINT `fk_project_assignment_project` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_project_assignment_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `project_bailleur`
--
ALTER TABLE `project_bailleur`
  ADD CONSTRAINT `fk_project_bailleur_bailleur` FOREIGN KEY (`bailleur_id`) REFERENCES `bailleur` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_project_bailleur_project` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `project_budget`
--
ALTER TABLE `project_budget`
  ADD CONSTRAINT `fk_project_budget_project` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `project_phase`
--
ALTER TABLE `project_phase`
  ADD CONSTRAINT `fk_project_phase_project` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `project_resource`
--
ALTER TABLE `project_resource`
  ADD CONSTRAINT `fk_project_resource_project` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `reports_finances_page`
--
ALTER TABLE `reports_finances_page`
  ADD CONSTRAINT `fk_reports_finances_page_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisation` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `responsibility_commitment`
--
ALTER TABLE `responsibility_commitment`
  ADD CONSTRAINT `fk_responsibility_commitment_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisation` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `responsibility_page`
--
ALTER TABLE `responsibility_page`
  ADD CONSTRAINT `fk_responsibility_page_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisation` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `role`
--
ALTER TABLE `role`
  ADD CONSTRAINT `fk_role_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisation` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `role_permission`
--
ALTER TABLE `role_permission`
  ADD CONSTRAINT `fk_role_perm_permission` FOREIGN KEY (`permission_id`) REFERENCES `permission` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_role_perm_role` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `schedule`
--
ALTER TABLE `schedule`
  ADD CONSTRAINT `fk_schedule_created_by` FOREIGN KEY (`created_by_user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_schedule_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisation` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `service`
--
ALTER TABLE `service`
  ADD CONSTRAINT `fk_service_department` FOREIGN KEY (`department_id`) REFERENCES `department` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `session`
--
ALTER TABLE `session`
  ADD CONSTRAINT `fk_session_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `fk_staff_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisation` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_staff_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `sub_task`
--
ALTER TABLE `sub_task`
  ADD CONSTRAINT `fk_sub_task_assigned` FOREIGN KEY (`assigned_user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sub_task_task` FOREIGN KEY (`task_id`) REFERENCES `task` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `task`
--
ALTER TABLE `task`
  ADD CONSTRAINT `fk_task_assigned` FOREIGN KEY (`assigned_user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_task_parent` FOREIGN KEY (`parent_task_id`) REFERENCES `task` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_task_phase` FOREIGN KEY (`project_phase_id`) REFERENCES `project_phase` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_task_project` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `unit`
--
ALTER TABLE `unit`
  ADD CONSTRAINT `fk_unit_service` FOREIGN KEY (`service_id`) REFERENCES `service` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `fk_user_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisation` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `user_group`
--
ALTER TABLE `user_group`
  ADD CONSTRAINT `fk_user_group_organisation` FOREIGN KEY (`organisation_id`) REFERENCES `organisation` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `user_group_member`
--
ALTER TABLE `user_group_member`
  ADD CONSTRAINT `fk_ugm_group` FOREIGN KEY (`user_group_id`) REFERENCES `user_group` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ugm_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `user_role`
--
ALTER TABLE `user_role`
  ADD CONSTRAINT `fk_user_role_role` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_role_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
