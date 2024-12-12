CREATE TABLE IF NOT EXISTS `#__acym_user` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NULL,
	`email` VARCHAR(255) NOT NULL,
	`creation_date` DATETIME NOT NULL,
	`active` TINYINT(1) NOT NULL DEFAULT 1,
	`cms_id` INT NOT NULL DEFAULT 0,
	`source` VARCHAR(255) NULL,
	`confirmed` TINYINT(1) NOT NULL DEFAULT 0,
	`key` VARCHAR(40) NULL,
	`automation` VARCHAR(50) NOT NULL DEFAULT '',
	`confirmation_date` DATETIME DEFAULT NULL,
	`confirmation_ip` VARCHAR(50) DEFAULT NULL,
	`tracking` TINYINT(1) NOT NULL DEFAULT 1,
	`language` VARCHAR(20) NOT NULL DEFAULT '',
	`last_sent_date` DATETIME NULL,
	`last_open_date` DATETIME NULL,
	`last_click_date` DATETIME NULL,
	PRIMARY KEY (`id`),
	UNIQUE INDEX `email_UNIQUE` (`email`(191) ASC),
	INDEX `#__index_acym_user1`(`cms_id`)
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;

CREATE TABLE IF NOT EXISTS `#__acym_configuration` (
	`name` VARCHAR(190) NOT NULL,
	`value` TEXT NOT NULL,
	PRIMARY KEY (`name`)
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;

CREATE TABLE IF NOT EXISTS `#__acym_mail` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL,
	`creation_date` DATETIME NOT NULL,
	`thumbnail` LONGTEXT NULL,
	`drag_editor` TINYINT(1) NULL,
	`type` VARCHAR(30) NOT NULL,
	`body` LONGTEXT NOT NULL,
	`subject` VARCHAR(255) NULL,
	`from_name` VARCHAR(100) NULL,
	`from_email` VARCHAR(100) NULL,
	`reply_to_name` VARCHAR(100) NULL,
	`reply_to_email` VARCHAR(100) NULL,
	`bcc` VARCHAR(255) NULL,
	`settings` TEXT NULL,
	`stylesheet` TEXT NULL,
	`attachments` TEXT NULL,
	`creator_id` INT NOT NULL,
	`mail_settings` TEXT NULL,
	`headers` TEXT NULL,
	`autosave` LONGTEXT NULL,
	`preheader` TEXT NULL,
	`links_language` VARCHAR(20) NOT NULL DEFAULT '',
	`access` VARCHAR(50) NOT NULL DEFAULT '',
	`tracking` TINYINT(1) NOT NULL DEFAULT 1,
	`language` VARCHAR(20) NOT NULL DEFAULT '',
	`parent_id` INT NULL,
	`translation` TEXT NULL,
	`bounce_email` VARCHAR(100) NULL,
	PRIMARY KEY (`id`),
	INDEX `#__index_acym_mail1`(`parent_id` ASC),
	INDEX `#__index_acym_mail2` (`type`),
	CONSTRAINT `#__fk_acym_mail1`
		FOREIGN KEY (`parent_id`)
			REFERENCES `#__acym_mail`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;

CREATE TABLE IF NOT EXISTS `#__acym_list` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL,
	`display_name` VARCHAR(255) NULL,
	`active` TINYINT(1) NOT NULL DEFAULT 1,
	`visible` TINYINT(1) NOT NULL DEFAULT 1,
	`clean` TINYINT(1) NULL,
	`color` VARCHAR(30) NULL,
	`creation_date` DATETIME NULL,
	`welcome_id` INT NULL,
	`unsubscribe_id` INT NULL,
	`cms_user_id` INT NOT NULL,
	`access` VARCHAR(50) NOT NULL DEFAULT '',
	`description` TEXT NOT NULL,
	`tracking` TINYINT(1) NOT NULL DEFAULT 1,
	`type` VARCHAR(20) NOT NULL DEFAULT 'standard',
	`translation` LONGTEXT NULL,
	PRIMARY KEY (`id`),
	INDEX `#__index_acym_list_has_mail1`(`welcome_id` ASC),
	INDEX `#__index_acym_list_has_mail2`(`unsubscribe_id` ASC),
	CONSTRAINT `#__fk_acym_list_has_mail1`
		FOREIGN KEY (`welcome_id`)
			REFERENCES `#__acym_mail`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION,
	CONSTRAINT `#__fk_acym_list_has_mail2`
		FOREIGN KEY (`unsubscribe_id`)
			REFERENCES `#__acym_mail`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;

CREATE TABLE IF NOT EXISTS `#__acym_campaign` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`sending_date` DATETIME NULL,
	`draft` TINYINT(1) NULL,
	`active` TINYINT(1) NOT NULL DEFAULT 1,
	`mail_id` INT NULL,
	`sent` TINYINT(1) NOT NULL DEFAULT 0,
	`sending_type` VARCHAR(16) DEFAULT NULL,
	`sending_params` TEXT DEFAULT NULL,
	`parent_id` INT DEFAULT NULL,
	`last_generated` INT DEFAULT NULL,
	`next_trigger` INT DEFAULT NULL,
	`visible` TINYINT(1) NOT NULL DEFAULT 1,
	PRIMARY KEY (`id`),
	INDEX `#__index_acym_campaign_has_mail1`(`mail_id` ASC),
	CONSTRAINT `#__fk_acym_campaign_has_mail1`
		FOREIGN KEY (`mail_id`)
			REFERENCES `#__acym_mail`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;

CREATE TABLE IF NOT EXISTS `#__acym_user_has_list` (
	`user_id` INT NOT NULL,
	`list_id` INT NOT NULL,
	`status` TINYINT(1) NOT NULL,
	`subscription_date` DATETIME NOT NULL,
	`unsubscribe_date` DATETIME NULL,
	PRIMARY KEY (`user_id`, `list_id`),
	INDEX `#__index_acym_user_has_list1`(`list_id` ASC),
	INDEX `#__index_acym_user_has_list2`(`user_id` ASC),
	INDEX `#__index_acym_user_has_list3`(`subscription_date` ASC),
	INDEX `#__index_acym_user_has_list4`(`unsubscribe_date` ASC),
	CONSTRAINT `#__fk_acym_user_has_list1`
		FOREIGN KEY (`user_id`)
			REFERENCES `#__acym_user`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION,
	CONSTRAINT `#__fk_acym_user_has_list2`
		FOREIGN KEY (`list_id`)
			REFERENCES `#__acym_list`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;

CREATE TABLE IF NOT EXISTS `#__acym_automation` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL,
	`description` LONGTEXT NULL,
	`active` TINYINT(3) NOT NULL,
	`report` TEXT NULL,
	`tree` LONGTEXT NULL,
	`admin` TINYINT(3) NULL,
	PRIMARY KEY (`id`)
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;

CREATE TABLE IF NOT EXISTS `#__acym_step` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL,
	`triggers` LONGTEXT NULL,
	`automation_id` INT NOT NULL,
	`last_execution` INT NULL,
	`next_execution` INT NULL,
	PRIMARY KEY (`id`),
	CONSTRAINT `#__fk_acym__step1`
		FOREIGN KEY (`automation_id`)
			REFERENCES `#__acym_automation`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;

CREATE TABLE IF NOT EXISTS `#__acym_tag` (
	`name` VARCHAR(50) NOT NULL,
	`type` VARCHAR(20) NOT NULL,
	`id_element` INT NOT NULL,
	PRIMARY KEY (`name`, `type`, `id_element`)
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;

CREATE TABLE IF NOT EXISTS `#__acym_mail_has_list` (
	`mail_id` INT NOT NULL,
	`list_id` INT NOT NULL,
	PRIMARY KEY (`mail_id`, `list_id`),
	INDEX `#__index_acym_mail_has_list1`(`list_id` ASC),
	INDEX `#__index_acym_mail_has_list2`(`mail_id` ASC),
	CONSTRAINT `#__fk_acym_mail_has_list1`
		FOREIGN KEY (`mail_id`)
			REFERENCES `#__acym_mail`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION,
	CONSTRAINT `#__fk_acym_mail_has_list2`
		FOREIGN KEY (`list_id`)
			REFERENCES `#__acym_list`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;

CREATE TABLE IF NOT EXISTS `#__acym_queue` (
	`mail_id` INT NOT NULL,
	`user_id` INT NOT NULL,
	`sending_date` DATETIME NOT NULL,
	`priority` INT NOT NULL DEFAULT 2,
	`try` TINYINT NOT NULL DEFAULT 0,
	PRIMARY KEY (`mail_id`, `user_id`),
	INDEX `#__index_acym_queue1`(`mail_id` ASC),
	INDEX `#__index_acym_queue2`(`user_id` ASC),
	CONSTRAINT `#__fk_acym_queue1`
		FOREIGN KEY (`mail_id`)
			REFERENCES `#__acym_mail`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION,
	CONSTRAINT `#__fk_acym_queue2`
		FOREIGN KEY (`user_id`)
			REFERENCES `#__acym_user`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;

CREATE TABLE IF NOT EXISTS `#__acym_mail_stat` (
	`mail_id` INT NOT NULL,
	`total_subscribers` INT NOT NULL DEFAULT 0,
	`sent` INT NOT NULL DEFAULT 0,
	`send_date` DATETIME NULL,
	`fail` INT NOT NULL DEFAULT 0,
	`open_unique` INT NOT NULL DEFAULT 0,
	`open_total` INT NOT NULL DEFAULT 0,
	`click_unique` INT NOT NULL DEFAULT 0,
	`click_total` INT NOT NULL DEFAULT 0,
	`bounce_unique` MEDIUMINT(8) NOT NULL DEFAULT 0,
	`bounce_details` LONGTEXT NULL,
	`unsubscribe_total` INT NOT NULL DEFAULT 0,
	`tracking_sale` FLOAT NULL,
	`currency` VARCHAR(5) NULL,
	PRIMARY KEY (`mail_id`),
	CONSTRAINT `#__fk_acym_mail_stat1`
		FOREIGN KEY (`mail_id`)
			REFERENCES `#__acym_mail`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;

CREATE TABLE IF NOT EXISTS `#__acym_user_stat` (
	`user_id` INT NOT NULL,
	`mail_id` INT NOT NULL,
	`send_date` DATETIME NULL,
	`fail` INT NOT NULL DEFAULT 0,
	`sent` INT NOT NULL DEFAULT 0,
	`open` INT NOT NULL DEFAULT 0,
	`open_date` DATETIME NULL,
	`bounce` TINYINT(4) NOT NULL DEFAULT 0,
	`bounce_rule` VARCHAR(255) NULL,
	`tracking_sale` FLOAT NULL,
	`currency` VARCHAR(10) NULL,
	`unsubscribe` INT NOT NULL DEFAULT 0,
	`device` VARCHAR(50) NULL,
	`opened_with` VARCHAR(50) NULL,
	PRIMARY KEY (`user_id`, `mail_id`),
	CONSTRAINT `#__fk_acym_user_stat1`
		FOREIGN KEY (`mail_id`)
			REFERENCES `#__acym_mail`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;

CREATE TABLE IF NOT EXISTS `#__acym_url` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` LONGTEXT NULL,
	`url` LONGTEXT NOT NULL,
	PRIMARY KEY (`id`)
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;

CREATE TABLE IF NOT EXISTS `#__acym_url_click` (
	`mail_id` INT NOT NULL,
	`url_id` INT NOT NULL,
	`user_id` INT NOT NULL,
	`click` INT NOT NULL DEFAULT 0,
	`date_click` DATETIME NULL,
	PRIMARY KEY (`mail_id`, `url_id`, `user_id`),
	INDEX `#__index_acym_url_has_url1`(`url_id` ASC),
	CONSTRAINT `#__fk_acym_url_click_has_mail`
		FOREIGN KEY (`mail_id`)
			REFERENCES `#__acym_mail`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION,
	CONSTRAINT `#__fk_acym_url_has_url`
		FOREIGN KEY (`url_id`)
			REFERENCES `#__acym_url`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;

CREATE TABLE IF NOT EXISTS `#__acym_field` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL,
	`type` VARCHAR(255) NOT NULL,
	`value` LONGTEXT NULL,
	`active` TINYINT(3) NOT NULL,
	`default_value` LONGTEXT NULL,
	`required` TINYINT(3) NULL,
	`ordering` INT NOT NULL,
	`option` LONGTEXT NULL,
	`core` TINYINT(3) NULL,
	`backend_edition` TINYINT(3) NULL,
	`backend_listing` TINYINT(3) NULL,
	`frontend_edition` TINYINT(3) NULL,
	`frontend_listing` TINYINT(3) NULL,
	`namekey` VARCHAR(255) NOT NULL,
	`translation` LONGTEXT NULL,
	PRIMARY KEY (`id`)
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;

CREATE TABLE IF NOT EXISTS `#__acym_user_has_field` (
	`user_id` INT NOT NULL,
	`field_id` INT NOT NULL,
	`value` LONGTEXT NULL,
	PRIMARY KEY (`user_id`, `field_id`),
	INDEX `#__index_acym_user_has_field1`(`field_id` ASC),
	INDEX `#__index_acym_user_has_field2`(`user_id` ASC),
	CONSTRAINT `#__fk_acym_user_has_field1`
		FOREIGN KEY (`user_id`)
			REFERENCES `#__acym_user`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION,
	CONSTRAINT `#__fk_acym_user_has_field2`
		FOREIGN KEY (`field_id`)
			REFERENCES `#__acym_field`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;

CREATE TABLE IF NOT EXISTS `#__acym_rule` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(250) NOT NULL,
	`description` VARCHAR(250) NULL,
	`ordering` SMALLINT(6) NULL,
	`regex` TEXT NOT NULL,
	`executed_on` TEXT NOT NULL,
	`action_message` TEXT NOT NULL,
	`action_user` TEXT NOT NULL,
	`active` TINYINT(3) NOT NULL,
	`increment_stats` TINYINT(3) NOT NULL,
	`execute_action_after` INT NOT NULL,
	PRIMARY KEY (`id`)
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;

CREATE TABLE IF NOT EXISTS `#__acym_history` (
	`user_id` INT NOT NULL,
	`date` INT NOT NULL,
	`ip` VARCHAR(50) DEFAULT NULL,
	`action` VARCHAR(50) NOT NULL,
	`data` text,
	`source` text,
	`mail_id` MEDIUMINT DEFAULT NULL,
	`unsubscribe_reason` TEXT NULL,
	PRIMARY KEY (`user_id`, `date`)
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;

CREATE TABLE IF NOT EXISTS `#__acym_condition` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`step_id` INT NOT NULL,
	`conditions` LONGTEXT NULL,
	PRIMARY KEY (`id`),
	CONSTRAINT `#__fk_acym_condition1`
		FOREIGN KEY (`step_id`)
			REFERENCES `#__acym_step`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;

CREATE TABLE IF NOT EXISTS `#__acym_action` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`condition_id` INT NOT NULL,
	`actions` LONGTEXT NULL,
	`filters` LONGTEXT NULL,
	`order` INT NOT NULL,
	PRIMARY KEY (`id`),
	CONSTRAINT `#__fk_acym_action1`
		FOREIGN KEY (`condition_id`)
			REFERENCES `#__acym_condition`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;

CREATE TABLE IF NOT EXISTS `#__acym_plugin` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`title` VARCHAR(100) NOT NULL,
	`folder_name` VARCHAR(100) NOT NULL,
	`version` VARCHAR(10) NULL,
	`active` INT NOT NULL,
	`category` VARCHAR(100) NOT NULL,
	`level` VARCHAR(50) NOT NULL,
	`uptodate` INT NOT NULL,
	`description` LONGTEXT NOT NULL,
	`latest_version` VARCHAR(10) NOT NULL,
	`settings` LONGTEXT NULL,
	`type` VARCHAR(20) NOT NULL DEFAULT 'ADDON',
	PRIMARY KEY (`id`)
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;

CREATE TABLE IF NOT EXISTS `#__acym_form` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL,
	`creation_date` DATETIME NOT NULL,
	`active` TINYINT(1) NOT NULL DEFAULT 1,
	`type` VARCHAR(20) NOT NULL,
	`pages` TEXT,
	`display_languages` VARCHAR(255),
	`settings` TEXT,
	PRIMARY KEY (`id`)
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;

CREATE TABLE IF NOT EXISTS `#__acym_segment` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL,
	`creation_date` DATETIME NOT NULL,
	`active` TINYINT(1) NOT NULL DEFAULT 1,
	`filters` LONGTEXT NULL,
	PRIMARY KEY (`id`)
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;

CREATE TABLE IF NOT EXISTS `#__acym_followup` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL,
	`display_name` VARCHAR(255) NOT NULL,
	`creation_date` DATETIME NOT NULL,
	`trigger` VARCHAR(50),
	`condition` LONGTEXT,
	`active` TINYINT(1) NOT NULL DEFAULT 1,
	`send_once` TINYINT(1) NOT NULL DEFAULT 1,
	`list_id` INT NULL,
	`last_trigger` INT NULL,
	`loop` TINYINT(1) NOT NULL DEFAULT 0,
	`loop_delay` INT NULL,
	`loop_mail_skip` VARCHAR(255) NULL,
	PRIMARY KEY (`id`),
	INDEX `#__index_acym_followup_has_list`(`list_id` ASC),
	CONSTRAINT `#__fk_acym_followup_has_list`
		FOREIGN KEY (`list_id`)
			REFERENCES `#__acym_list`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;

CREATE TABLE IF NOT EXISTS `#__acym_followup_has_mail` (
	`mail_id` INT NOT NULL,
	`followup_id` INT NOT NULL,
	`delay` INT NOT NULL,
	`delay_unit` INT NOT NULL,
	PRIMARY KEY (`mail_id`, `followup_id`),
	INDEX `#__index_acym_mail_has_followup1`(`followup_id` ASC),
	INDEX `#__index_acym_mail_has_followup2`(`mail_id` ASC),
	CONSTRAINT `#__fk_acym_mail_has_followup1`
		FOREIGN KEY (`mail_id`)
			REFERENCES `#__acym_mail`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION,
	CONSTRAINT `#__fk_acym_mail_has_followup2`
		FOREIGN KEY (`followup_id`)
			REFERENCES `#__acym_followup`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;

CREATE TABLE IF NOT EXISTS `#__acym_mail_override` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`mail_id` INT NOT NULL,
	`description` VARCHAR(255) NOT NULL,
	`source` VARCHAR (20) NOT NULL,
	`active` TINYINT(1) NOT NULL DEFAULT 1,
	`base_subject` TEXT NOT NULL,
	`base_body` TEXT NOT NULL,
	PRIMARY KEY(`id`),
	CONSTRAINT `#__fk_acym_mail_override1`
		FOREIGN KEY (`mail_id`)
			REFERENCES `#__acym_mail`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;

CREATE TABLE IF NOT EXISTS `#__acym_custom_zone` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL,
	`content` TEXT NOT NULL,
	`image` VARCHAR(255) NULL,
	PRIMARY KEY(`id`)
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;

CREATE TABLE IF NOT EXISTS `#__acym_mailbox_action` (
	`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`name` varchar(255) DEFAULT NULL,
	`frequency` int(10) UNSIGNED NOT NULL DEFAULT 0,
	`nextdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
	`description` text DEFAULT NULL,
	`server` varchar(255) NULL,
	`port` varchar(50) NULL,
	`connection_method` ENUM('imap', 'pop3', 'pear') NULL,
	`secure_method` ENUM('ssl', 'tls') NULL,
	`self_signed` tinyint(4) NULL,
	`username` varchar(255) NULL,
	`password` varchar(50) NULL,
	`conditions` text DEFAULT NULL,
	`actions` text DEFAULT NULL,
	`report` text DEFAULT NULL,
	`delete_wrong_emails` tinyint(4) NOT NULL DEFAULT 0,
	`senderfrom` tinyint(4) NOT NULL DEFAULT 0,
	`senderto` tinyint(4) NOT NULL DEFAULT 0,
	`active` tinyint(4) NOT NULL DEFAULT 0,
	PRIMARY KEY (`id`)
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;

CREATE TABLE IF NOT EXISTS `#__acym_mail_archive` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`mail_id` INT NOT NULL,
	`date` INT(10) UNSIGNED NOT NULL,
	`body` LONGTEXT NOT NULL,
	`subject` VARCHAR(255) NULL,
	`settings` TEXT NULL,
	`stylesheet` TEXT NULL,
	`attachments` TEXT NULL,
	PRIMARY KEY (`id`),
	CONSTRAINT `#__fk_acym_mail_archive1`
		FOREIGN KEY (`mail_id`)
			REFERENCES `#__acym_mail`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;
