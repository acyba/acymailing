CREATE TABLE IF NOT EXISTS `#__acym_user` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NULL,
	`email` VARCHAR(255) NOT NULL,
	`creation_date` DATETIME NOT NULL,
	`active` TINYINT(1) NOT NULL DEFAULT 1,
	`cms_id` INT NOT NULL DEFAULT 0,
	`source` VARCHAR(255) NULL,
	`confirmed` TINYINT(1) NOT NULL DEFAULT 0,
	`key` VARCHAR(255) NULL,
	`automation` VARCHAR(50) NOT NULL DEFAULT '',
	`confirmation_date` DATETIME DEFAULT NULL,
	`confirmation_ip` VARCHAR(16) DEFAULT NULL,
	`tracking` TINYINT(1) NOT NULL DEFAULT 1,
	`language` VARCHAR(20) NOT NULL DEFAULT '',
	PRIMARY KEY (`id`),
	UNIQUE INDEX `email_UNIQUE`(`email` ASC)
)
	ENGINE = InnoDB
	/*!40100
	DEFAULT CHARACTER SET utf8
	COLLATE utf8_general_ci*/;


-- -----------------------------------------------------
-- Table `#__acym_configuration`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__acym_configuration` (
	`name` VARCHAR(255) NOT NULL,
	`value` TEXT NOT NULL,
	PRIMARY KEY (`name`)
)
	ENGINE = InnoDB
	/*!40100
	DEFAULT CHARACTER SET utf8
	COLLATE utf8_general_ci*/;


-- -----------------------------------------------------
-- Table `#__acym_mail`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__acym_mail` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL,
	`creation_date` DATETIME NOT NULL,
	`thumbnail` LONGTEXT NULL,
	`drag_editor` TINYINT(1) NULL,
	`library` TINYINT(1) NULL,
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
	PRIMARY KEY (`id`),
	INDEX `index_#__acym_mail1`(`parent_id` ASC),
	CONSTRAINT `fk_#__acym_mail1`
		FOREIGN KEY (`parent_id`)
			REFERENCES `#__acym_mail`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100
	DEFAULT CHARACTER SET utf8
	COLLATE utf8_general_ci*/;


-- -----------------------------------------------------
-- Table `#__acym_list`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__acym_list` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL,
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
	INDEX `index_#__acym_list_has_mail1`(`welcome_id` ASC),
	INDEX `index_#__acym_list_has_mail2`(`unsubscribe_id` ASC),
	CONSTRAINT `fk_#__acym_list_has_mail1`
		FOREIGN KEY (`welcome_id`)
			REFERENCES `#__acym_mail`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION,
	CONSTRAINT `fk_#__acym_list_has_mail2`
		FOREIGN KEY (`unsubscribe_id`)
			REFERENCES `#__acym_mail`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100
	DEFAULT CHARACTER SET utf8
	COLLATE utf8_general_ci*/;


-- -----------------------------------------------------
-- Table `#__acym_campaign`
-- -----------------------------------------------------
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
	INDEX `index_#__acym_campaign_has_mail1`(`mail_id` ASC),
	CONSTRAINT `fk_#__acym_campaign_has_mail1`
		FOREIGN KEY (`mail_id`)
			REFERENCES `#__acym_mail`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100
	DEFAULT CHARACTER SET utf8
	COLLATE utf8_general_ci*/;


-- -----------------------------------------------------
-- Table `#__acym_user_has_list`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__acym_user_has_list` (
	`user_id` INT NOT NULL,
	`list_id` INT NOT NULL,
	`status` TINYINT(1) NOT NULL,
	`subscription_date` DATETIME NOT NULL,
	`unsubscribe_date` DATETIME NULL,
	PRIMARY KEY (`user_id`, `list_id`),
	INDEX `index_#__acym_user_has_list1`(`list_id` ASC),
	INDEX `index_#__acym_user_has_list2`(`user_id` ASC),
	INDEX `index_#__acym_user_has_list3`(`subscription_date` ASC),
	INDEX `index_#__acym_user_has_list4`(`unsubscribe_date` ASC),
	CONSTRAINT `fk_#__acym_user_has_list1`
		FOREIGN KEY (`user_id`)
			REFERENCES `#__acym_user`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION,
	CONSTRAINT `fk_#__acym_user_has_list2`
		FOREIGN KEY (`list_id`)
			REFERENCES `#__acym_list`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100
	DEFAULT CHARACTER SET utf8
	COLLATE utf8_general_ci*/;


-- -----------------------------------------------------
-- Table `#__acym_automation`
-- -----------------------------------------------------
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
	/*!40100
	DEFAULT CHARACTER SET utf8
	COLLATE utf8_general_ci*/;

-- -----------------------------------------------------
-- Table `#__acym_step`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__acym_step` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL,
	`triggers` LONGTEXT NULL,
	`automation_id` INT NOT NULL,
	`last_execution` INT NULL,
	`next_execution` INT NULL,
	PRIMARY KEY (`id`),
	CONSTRAINT `fk_#__acym__step1`
		FOREIGN KEY (`automation_id`)
			REFERENCES `#__acym_automation`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100
	DEFAULT CHARACTER SET utf8
	COLLATE utf8_general_ci*/;


-- -----------------------------------------------------
-- Table `#__acym_tag`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__acym_tag` (
	`name` VARCHAR(50) NOT NULL,
	`type` VARCHAR(20) NOT NULL,
	`id_element` INT NOT NULL,
	PRIMARY KEY (`name`, `type`, `id_element`)
)
	ENGINE = InnoDB
	/*!40100
	DEFAULT CHARACTER SET utf8
	COLLATE utf8_general_ci*/;

CREATE TABLE IF NOT EXISTS `#__acym_mail_has_list` (
	`mail_id` INT NOT NULL,
	`list_id` INT NOT NULL,
	PRIMARY KEY (`mail_id`, `list_id`),
	INDEX `index_#__acym_mail_has_list1`(`list_id` ASC),
	INDEX `index_#__acym_mail_has_list2`(`mail_id` ASC),
	CONSTRAINT `fk_#__acym_mail_has_list1`
		FOREIGN KEY (`mail_id`)
			REFERENCES `#__acym_mail`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION,
	CONSTRAINT `fk_#__acym_mail_has_list2`
		FOREIGN KEY (`list_id`)
			REFERENCES `#__acym_list`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100
	DEFAULT CHARACTER SET utf8
	COLLATE utf8_general_ci*/;


-- -----------------------------------------------------
-- Table `#__acym_queue`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__acym_queue` (
	`mail_id` INT NOT NULL,
	`user_id` INT NOT NULL,
	`sending_date` DATETIME NOT NULL,
	`priority` INT NOT NULL DEFAULT 2,
	`try` TINYINT NOT NULL DEFAULT 0,
	PRIMARY KEY (`mail_id`, `user_id`),
	INDEX `index_#__acym_queue1`(`mail_id` ASC),
	INDEX `index_#__acym_queue2`(`user_id` ASC),
	CONSTRAINT `fk_#__acym_queue1`
		FOREIGN KEY (`mail_id`)
			REFERENCES `#__acym_mail`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION,
	CONSTRAINT `fk_#__acym_queue2`
		FOREIGN KEY (`user_id`)
			REFERENCES `#__acym_user`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100
	DEFAULT CHARACTER SET utf8
	COLLATE utf8_general_ci*/;


-- -----------------------------------------------------
-- Table `#__acym_mail_stat`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__acym_mail_stat` (
	`mail_id` INT NOT NULL,
	`total_subscribers` INT NOT NULL DEFAULT 0,
	`sent` INT NULL DEFAULT 0,
	`send_date` DATETIME NULL,
	`fail` INT NULL DEFAULT 0,
	`open_unique` INT NOT NULL DEFAULT 0,
	`open_total` INT NOT NULL DEFAULT 0,
	`bounce_unique` MEDIUMINT(8) NOT NULL DEFAULT 0,
	`bounce_details` LONGTEXT NULL,
	`unsubscribe_total` INT NOT NULL DEFAULT 0,
    `tracking_sale` FLOAT NULL,
    `currency` VARCHAR(5) NULL,
	PRIMARY KEY (`mail_id`),
	CONSTRAINT `fk_#__acym_mail_stat1`
		FOREIGN KEY (`mail_id`)
			REFERENCES `#__acym_mail`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100
	DEFAULT CHARACTER SET utf8
	COLLATE utf8_general_ci*/;


-- -----------------------------------------------------
-- Table `#__acym_user_stat`
-- -----------------------------------------------------
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
	CONSTRAINT `fk_#__acym_user_stat1`
		FOREIGN KEY (`mail_id`)
			REFERENCES `#__acym_mail`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100
	DEFAULT CHARACTER SET utf8
	COLLATE utf8_general_ci*/;


-- -----------------------------------------------------
-- Table `#__acym_url`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__acym_url` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` LONGTEXT NULL,
	`url` LONGTEXT NOT NULL,
	PRIMARY KEY (`id`)
)
	ENGINE = InnoDB
	/*!40100
	DEFAULT CHARACTER SET utf8
	COLLATE utf8_general_ci*/;


-- -----------------------------------------------------
-- Table `#__acym_url_click`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__acym_url_click` (
	`mail_id` INT NOT NULL,
	`url_id` INT NOT NULL,
	`user_id` INT NOT NULL,
	`click` INT NOT NULL DEFAULT 0,
	`date_click` DATETIME NULL,
	PRIMARY KEY (`mail_id`, `url_id`, `user_id`),
	INDEX `index_#__acym_url_has_url1`(`url_id` ASC),
	CONSTRAINT `fk_#__acym_url_click_has_mail`
		FOREIGN KEY (`mail_id`)
			REFERENCES `#__acym_mail`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION,
	CONSTRAINT `fk_#__acym_url_has_url`
		FOREIGN KEY (`url_id`)
			REFERENCES `#__acym_url`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100
	DEFAULT CHARACTER SET utf8
	COLLATE utf8_general_ci*/;


-- -----------------------------------------------------
-- Table `#__acym_field`
-- -----------------------------------------------------
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
	`access` VARCHAR(255) NULL,
	`namekey` VARCHAR(255) NOT NULL,
	`translation` LONGTEXT NULL,
	PRIMARY KEY (`id`)
)
	ENGINE = InnoDB
	/*!40100
	DEFAULT CHARACTER SET utf8
	COLLATE utf8_general_ci*/;


-- -----------------------------------------------------
-- Table `#__acym_user_has_field`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__acym_user_has_field` (
	`user_id` INT NOT NULL,
	`field_id` INT NOT NULL,
	`value` LONGTEXT NULL,
	PRIMARY KEY (`user_id`, `field_id`),
	INDEX `index_#__acym_user_has_field1`(`field_id` ASC),
	INDEX `index_#__acym_user_has_field2`(`user_id` ASC),
	CONSTRAINT `fk_#__acym_user_has_field1`
		FOREIGN KEY (`user_id`)
			REFERENCES `#__acym_user`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION,
	CONSTRAINT `fk_#__acym_user_has_field2`
		FOREIGN KEY (`field_id`)
			REFERENCES `#__acym_field`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100
	DEFAULT CHARACTER SET utf8
	COLLATE utf8_general_ci*/;


-- -----------------------------------------------------
-- Table `#__acym_rule`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__acym_rule` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(250) NOT NULL,
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
	/*!40100
	DEFAULT CHARACTER SET utf8
	COLLATE utf8_general_ci*/;


-- -----------------------------------------------------
-- Table `#__acym_history`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__acym_history` (
	`user_id` INT NOT NULL,
	`date` INT NOT NULL,
	`ip` VARCHAR(16) DEFAULT NULL,
	`action` VARCHAR(50) NOT NULL,
	`data` text,
	`source` text,
	`mail_id` MEDIUMINT DEFAULT NULL,
	PRIMARY KEY (`user_id`, `date`)
)
	ENGINE = InnoDB
	/*!40100
	DEFAULT CHARACTER SET utf8
	COLLATE utf8_general_ci*/;


-- -----------------------------------------------------
-- Table `#__acym_condition`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__acym_condition` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`step_id` INT NOT NULL,
	`conditions` LONGTEXT NULL,
	PRIMARY KEY (`id`),
	CONSTRAINT `fk_#__acym_condition1`
		FOREIGN KEY (`step_id`)
			REFERENCES `#__acym_step`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100
	DEFAULT CHARACTER SET utf8
	COLLATE utf8_general_ci*/;


-- -----------------------------------------------------
-- Table `#__acym_action`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__acym_action` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`condition_id` INT NOT NULL,
	`actions` LONGTEXT NULL,
	`filters` LONGTEXT NULL,
	`order` INT NOT NULL,
	PRIMARY KEY (`id`),
	CONSTRAINT `fk_#__acym_action1`
		FOREIGN KEY (`condition_id`)
			REFERENCES `#__acym_condition`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100
	DEFAULT CHARACTER SET utf8
	COLLATE utf8_general_ci*/;


-- -----------------------------------------------------
-- Table `#__acym_plugin`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__acym_plugin` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`title` VARCHAR(100) NOT NULL,
	`folder_name` VARCHAR(100) NOT NULL,
	`version` VARCHAR(10) NULL,
	`active` INT NOT NULL,
	`category` VARCHAR(100) NOT NULL,
	`level` VARCHAR(50) NOT NULL,
	`uptodate` INT NOT NULL,
	`features` VARCHAR(255) NOT NULL,
	`description` LONGTEXT NOT NULL,
	`latest_version` VARCHAR(10) NOT NULL,
	`settings` LONGTEXT NULL,
	`type` VARCHAR(20) NOT NULL DEFAULT "ADDON",
	PRIMARY KEY (`id`)
)
	ENGINE = InnoDB
	/*!40100
	DEFAULT CHARACTER SET utf8
	COLLATE utf8_general_ci*/;

-- -----------------------------------------------------
-- Table `#__acym_form`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__acym_form` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL,
	`creation_date` DATETIME NOT NULL,
	`active` TINYINT(1) NOT NULL DEFAULT 1,
	`type` VARCHAR(20) NOT NULL,
	`lists_options` LONGTEXT,
	`fields_options` LONGTEXT,
	`style_options` LONGTEXT,
	`button_options` LONGTEXT,
	`image_options` LONGTEXT,
	`termspolicy_options` LONGTEXT,
	`cookie` VARCHAR(30),
	`delay` SMALLINT(10),
	`pages` TEXT,
	`redirection_options` TEXT,
	PRIMARY KEY (`id`)
)
	ENGINE = InnoDB
	/*!40100
	DEFAULT CHARACTER SET utf8
	COLLATE utf8_general_ci*/;

-- -----------------------------------------------------
-- Table `#__acym_segment`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__acym_segment` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL,
	`creation_date` DATETIME NOT NULL,
	`active` TINYINT(1) NOT NULL DEFAULT 1,
	`filters` LONGTEXT NULL,
	PRIMARY KEY (`id`)
)
	ENGINE = InnoDB
	/*!40100
	DEFAULT CHARACTER SET utf8
	COLLATE utf8_general_ci*/;

-- -----------------------------------------------------
-- Table `#__acym_segment`
-- -----------------------------------------------------
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
	PRIMARY KEY (`id`),
	INDEX `index_#__acym_followup_has_list`(`list_id` ASC),
	CONSTRAINT `fk_#__acym_followup_has_list`
		FOREIGN KEY (`list_id`)
			REFERENCES `#__acym_list`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100
	DEFAULT CHARACTER SET utf8
	COLLATE utf8_general_ci*/;

CREATE TABLE IF NOT EXISTS `#__acym_followup_has_mail` (
	`mail_id` INT NOT NULL,
	`followup_id` INT NOT NULL,
	`delay` INT NOT NULL,
	`delay_unit` INT NOT NULL,
	PRIMARY KEY (`mail_id`, `followup_id`),
	INDEX `index_#__acym_mail_has_followup1`(`followup_id` ASC),
	INDEX `index_#__acym_mail_has_followup2`(`mail_id` ASC),
	CONSTRAINT `fk_#__acym_mail_has_followup1`
		FOREIGN KEY (`mail_id`)
			REFERENCES `#__acym_mail`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION,
	CONSTRAINT `fk_#__acym_mail_has_followup2`
		FOREIGN KEY (`followup_id`)
			REFERENCES `#__acym_followup`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100
	DEFAULT CHARACTER SET utf8
	COLLATE utf8_general_ci*/;

-- -----------------------------------------------------
-- Table `#__acym_mail_override`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__acym_mail_override` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`mail_id` INT NOT NULL,
	`description` VARCHAR(255) NOT NULL,
	`source` VARCHAR (20) NOT NULL,
	`active` TINYINT(1) NOT NULL DEFAULT 1,
	`base_subject` TEXT NOT NULL,
	`base_body` TEXT NOT NULL,
	PRIMARY KEY(`id`),
	CONSTRAINT `fk_#__acym_mail_override1`
		FOREIGN KEY (`mail_id`)
			REFERENCES `#__acym_mail`(`id`)
			ON DELETE NO ACTION
			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100
	DEFAULT CHARACTER SET utf8
	COLLATE utf8_general_ci*/;
