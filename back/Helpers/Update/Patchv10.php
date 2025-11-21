<?php

namespace AcyMailing\Helpers\Update;

use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\ConfigurationClass;
use AcyMailing\Helpers\BounceHelper;

trait Patchv10
{
    private function updateFor1000(): void
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

    private function updateFor1020(ConfigurationClass $config): void
    {
        if ($this->isPreviousVersionAtLeast('10.2.0')) {
            return;
        }

        $smtpHost = strtolower($config->get('smtp_host'));
        if (in_array($smtpHost, ['smtp.gmail.com', 'smtp-mail.outlook.com', 'smtp.office365.com'])) {
            $sendingMethod = $config->get('mailer_method');
            $clientId = $config->get('smtp_clientId');
            $clientSecret = $config->get('smtp_secret');
            $redirectUrl = $config->get('smtp_redirectUrl');
            $accessToken = str_replace('Bearer ', '', $config->get('smtp_token'));
            $accessTokenExpiration = $config->get('smtp_token_expireIn');
            $refreshToken = $config->get('smtp_refresh_token');

            if ($smtpHost === 'smtp.gmail.com') {
                $newConfig = [
                    'mailer_method' => $sendingMethod === 'smtp' ? 'google' : $sendingMethod,
                    'google_client_id' => $clientId,
                    'google_client_secret' => $clientSecret,
                    'google_redirect_url' => $redirectUrl,
                    'google_access_token' => $accessToken,
                    'google_access_token_expiration' => $accessTokenExpiration,
                    'google_refresh_token' => $refreshToken,
                    'google_refresh_token_expiration' => 0,
                ];
            } else {
                $newConfig = [
                    'mailer_method' => $sendingMethod === 'smtp' ? 'outlook' : $sendingMethod,
                    'outlook_tenant' => $config->get('smtp_tenant'),
                    'outlook_client_id' => $clientId,
                    'outlook_client_secret' => $clientSecret,
                    'outlook_redirect_url' => $redirectUrl,
                    'outlook_access_token' => $accessToken,
                    'outlook_access_token_expiration' => $accessTokenExpiration,
                    'outlook_refresh_token' => $refreshToken,
                    'outlook_refresh_token_expiration' => 0,
                ];
            }

            if (!empty($newConfig)) {
                $config->saveConfig($newConfig);
            }
        }

        $bounceHost = strtolower($config->get('bounce_server'));
        if (in_array($bounceHost, BounceHelper::HOSTS_NEEDING_OAUTH)) {
            $config->saveConfig(
                [
                    'bounce_server' => $bounceHost,
                    'bounce_access_token' => str_replace('Bearer ', '', $config->get('bounce_token')),
                    'bounce_access_token_expiration' => $config->get('bounce_token_expireIn'),
                    'bounce_refresh_token_expiration' => 0,
                    'bounce_port' => 993,
                    'bounce_connection' => 'imap',
                    'bounce_secured' => 'ssl',
                    'bounce_certif' => 1,
                ]
            );
        }

        $this->updateQuery('ALTER TABLE #__acym_list ADD COLUMN `subscribers` INT DEFAULT 0');
        $this->updateQuery('ALTER TABLE #__acym_list ADD COLUMN `unsubscribed_users` INT DEFAULT 0');
        $this->updateQuery('ALTER TABLE #__acym_list ADD COLUMN `new_sub` INT DEFAULT 0');
        $this->updateQuery('ALTER TABLE #__acym_list ADD COLUMN `new_unsub` INT DEFAULT 0');
    }

    private function updateFor1050(): void
    {
        if ($this->isPreviousVersionAtLeast('10.5.0')) {
            return;
        }

        $this->updateQuery('ALTER TABLE #__acym_automation CHANGE `active` `active` TINYINT(3) NOT NULL DEFAULT 0');
    }

    private function updateFor1060(): void
    {
        if ($this->isPreviousVersionAtLeast('10.6.0')) {
            return;
        }

        $this->updateQuery('ALTER TABLE #__acym_scenario_process ADD COLUMN `unsubscribed` TINYINT(1) NOT NULL DEFAULT 0');
    }

    private function updateFor1062(): void
    {
        if ($this->isPreviousVersionAtLeast('10.6.2')) {
            return;
        }

        $this->updateQuery('ALTER TABLE #__acym_list CHANGE `access` `access` VARCHAR(150) NOT NULL DEFAULT ""');
    }

    private function updateFor1065(): void
    {
        if ($this->isPreviousVersionAtLeast('10.6.5')) {
            return;
        }

        $campaignClass = new CampaignClass();
        $autoCampaigns = $campaignClass->getCampaignsByTypes([CampaignClass::SENDING_TYPE_AUTO], true);
        $campaignIds = [];

        foreach ($autoCampaigns as $autoCampaign) {
            if (empty($autoCampaign->sending_params)) {
                $autoCampaign->active = 0;
                $campaignClass->save($autoCampaign);
                $campaignIds[] = $autoCampaign->id;
            }
        }

        if (!empty($campaignIds)) {
            $campaignIdsText = implode(', ', $campaignIds);

            $message = acym_translationSprintf('ACYM_ERROR_WHILE_RECOVERING_TRIGGERS_NOTIF_X', $campaignIdsText);
            $message .= ' <a id="acym__queue__configure-cron" href="'.acym_completeLink('campaigns&task=campaigns_auto').'">'.acym_translation(
                    'ACYM_GOTO_CAMPAIGNS_AUTO'
                ).'</a>';
            $message .= '<p class="acym__do__not__remindme" title="auto_campaigns_triggers_reminder">'.acym_translation('ACYM_DO_NOT_REMIND_ME').'</p>';

            $notification = [
                'name' => 'auto_campaigns_triggers_reminder',
                'removable' => 1,
            ];
            acym_enqueueMessage($message, 'warning', true, [$notification]);
        }
    }
}
