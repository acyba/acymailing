<?php

namespace AcyMailing\Helpers\Update;

use AcyMailing\Classes\ConfigurationClass;

trait Patchv11
{
    private function updateFor1102(): void
    {
        if ($this->isPreviousVersionAtLeast('11.0.2')) {
            return;
        }

        $this->updateQuery('UPDATE #__acym_user_has_list SET `status` = 0 WHERE `status` = -1');
    }

    private function updateFor1105(): void
    {
        if ($this->isPreviousVersionAtLeast('11.0.5')) {
            return;
        }

        $this->updateQuery(
            'CREATE TABLE IF NOT EXISTS `#__acym_mail_stat_detail` (
            `mail_id` INT NOT NULL,
            `detail_type` VARCHAR(20) NOT NULL,
            `detail_key` VARCHAR(100) NOT NULL,
            `number` INT NOT NULL DEFAULT 0,
            PRIMARY KEY (`mail_id`, `detail_type`, `detail_key`)
            )'
        );
    }

    private function updateFor1110(): void
    {
        if ($this->isPreviousVersionAtLeast('11.1.0')) {
            return;
        }

        $this->updateQuery(
            'UPDATE #__acym_configuration
            SET `value` = 0
            WHERE `name` = "favorite_template"
                AND `value` != 0
                AND `value` NOT IN (SELECT `id` FROM #__acym_mail)'
        );
    }

    private function updateFor1111(ConfigurationClass $config): void
    {
        if ($this->isPreviousVersionAtLeast('11.1.1')) {
            return;
        }

        $sendMethod = $config->get('mailer_method');
        if ($sendMethod === 'acymailer') {
            $config->saveConfig(
                [
                    'embed_images' => 0,
                    'embed_files' => 0,
                ]
            );
        }
    }

    private function updateFor1112(): void
    {
        if ($this->isPreviousVersionAtLeast('11.1.2')) {
            return;
        }

        $this->updateQuery('ALTER TABLE `#__acym_user_stat` ADD INDEX `#__index_acym_user_stat1` (`open_date`, `open`), ADD INDEX `#__index_acym_user_stat2` (`send_date`)');
        $this->updateQuery('ALTER TABLE `#__acym_url_click` ADD INDEX `#__index_acym_url_click1` (`date_click`, `click`)');
        $this->updateQuery('ALTER TABLE `#__acym_history` ADD INDEX `#__index_acym_history1` (`date`)');
        $this->updateQuery(
            'CREATE TABLE IF NOT EXISTS `#__acym_stats_cache` (
            `name` VARCHAR(100) NOT NULL,
            `value` LONGTEXT NOT NULL,
            `expiration_date` INT NOT NULL,
            PRIMARY KEY (`name`)
            )'
        );
    }
}
