<?php

namespace AcyMailing\Helpers\Update;

trait Patchv10
{
    private function updateFor1000()
    {
        if ($this->isPreviousVersionAtLeast('10.0.0')) {
            return;
        }

        $this->updateQuery('ALTER TABLE #__acym_queue ADD COLUMN `params` TEXT NULL');

        $scenarioTable = 'CREATE TABLE IF NOT EXISTS `#__acym_scenario` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`trigger` VARCHAR(255) NOT NULL,
	`name` VARCHAR(255) NOT NULL,
	`active` TINYINT(1) NOT NULL DEFAULT 0,
	`trigger_params` TEXT NULL,
	`trigger_once` TINYINT(1) NOT NULL DEFAULT 0,
	PRIMARY KEY (`id`)
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;';

        $scenarioStepTable = 'CREATE TABLE IF NOT EXISTS `#__acym_scenario_step` (
	`id` VARCHAR(30) NOT NULL,
	`previous_id` VARCHAR(30) NULL,
	`type` VARCHAR(30) NOT NULL,
	`params` TEXT NULL,
	`scenario_id` INT NOT NULL,
	`condition_valid` TINYINT(1) NULL,
	PRIMARY KEY (`id`),
	CONSTRAINT `#__fk_acym_scenario_step1`
    		FOREIGN KEY (`scenario_id`)
    			REFERENCES `#__acym_scenario`(`id`)
    			ON DELETE NO ACTION
    			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;';

        $scenarioProcessTable = 'CREATE TABLE IF NOT EXISTS `#__acym_scenario_process` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`scenario_id` INT NOT NULL,
	`user_id` INT NOT NULL,
	`start_at` DATETIME NOT NULL,
	`end_at` DATETIME NULL,
	PRIMARY KEY (`id`),
	CONSTRAINT `#__fk_acym_scenario_process1`
    		FOREIGN KEY (`scenario_id`)
    			REFERENCES `#__acym_scenario`(`id`)
    			ON DELETE NO ACTION
    			ON UPDATE NO ACTION,
	CONSTRAINT `#__fk_acym_scenario_process2`
    		FOREIGN KEY (`user_id`)
    			REFERENCES `#__acym_user`(`id`)
    			ON DELETE NO ACTION
    			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;';

        $scenarioQueueTable = 'CREATE TABLE IF NOT EXISTS `#__acym_scenario_queue` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`scenario_process_id` INT NOT NULL,
	`step_id` VARCHAR(30) NOT NULL,
	`execution_date` DATETIME NOT NULL,
	PRIMARY KEY (`id`),
	CONSTRAINT `#__fk_acym_scenario_queue1`
    		FOREIGN KEY (`scenario_process_id`)
    			REFERENCES `#__acym_scenario_process`(`id`)
    			ON DELETE NO ACTION
    			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;';

        $scenarioHistoryLineTable = 'CREATE TABLE IF NOT EXISTS `#__acym_scenario_history_line` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`scenario_process_id` INT NOT NULL,
	`date` DATETIME NOT NULL,
	`type` VARCHAR(10) NOT NULL,
	`params` TEXT NULL,
	`result` TEXT NULL,
	`log` TEXT NULL,
	`scenario_step_id` VARCHAR(40) NULL,
	PRIMARY KEY (`id`),
	CONSTRAINT `#__fk_acym_scenario_history_line1`
    		FOREIGN KEY (`scenario_process_id`)
    			REFERENCES `#__acym_scenario_process`(`id`)
    			ON DELETE NO ACTION
    			ON UPDATE NO ACTION
)
	ENGINE = InnoDB
	/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;';

        $this->updateQuery($scenarioTable);
        $this->updateQuery($scenarioStepTable);
        $this->updateQuery($scenarioProcessTable);
        $this->updateQuery($scenarioQueueTable);
        $this->updateQuery($scenarioHistoryLineTable);
    }
}
