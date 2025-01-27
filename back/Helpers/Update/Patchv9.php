<?php

namespace AcyMailing\Helpers\Update;

use AcyMailing\Classes\UrlClickClass;

trait Patchv9
{
    private function updateFor920($config)
    {
        if ($this->isPreviousVersionAtLeast('9.2.0')) {
            return;
        }

        $socialIcons = json_decode($config->get('social_icons', '{}'), true);
        if (empty($socialIcons['telegram'])) {
            $socialIcons['telegram'] = ACYM_IMAGES.'logo/telegram.png';

            $newConfig = new \stdClass();
            $newConfig->social_icons = json_encode($socialIcons);
            $config->save($newConfig);
        }

        $this->updateQuery('ALTER TABLE #__acym_mail_stat ADD COLUMN `click_unique` INT NOT NULL DEFAULT 0');
        $this->updateQuery('ALTER TABLE #__acym_mail_stat ADD COLUMN `click_total` INT NOT NULL DEFAULT 0');

        $urlClickClass = new UrlClickClass();
        $mailClicks = $urlClickClass->getTotalClicksPerMail();
        if (!empty($mailClicks)) {
            foreach ($mailClicks as $mailId => $stats) {
                $this->updateQuery(
                    'UPDATE #__acym_mail_stat 
                    SET click_unique = '.intval($stats->unique_clicks).', click_total = '.intval($stats->total_clicks).' 
                    WHERE mail_id = '.intval($mailId)
                );
            }
        }
    }

    private function updateFor930()
    {
        if ($this->isPreviousVersionAtLeast('9.3.0')) {
            return;
        }

        $this->updateQuery('ALTER TABLE #__acym_rule ADD COLUMN `description` VARCHAR(250) NULL AFTER `name`');
    }

    private function updateFor931()
    {
        if ($this->isPreviousVersionAtLeast('9.3.1')) {
            return;
        }

        $maxOrdering = acym_loadResult('SELECT MAX(ordering) FROM #__acym_rule');
        $this->updateQuery('UPDATE #__acym_rule SET `ordering` = '.intval($maxOrdering + 1).' WHERE `id` = 17');
    }

    private function updateFor940($config)
    {
        if ($this->isPreviousVersionAtLeast('9.4.0')) {
            return;
        }

        $config->save([
            'from_email' => acym_strtolower($config->get('from_email')),
            'replyto_email' => acym_strtolower($config->get('replyto_email')),
            'bounce_email' => acym_strtolower($config->get('bounce_email')),
        ]);

        // Make sure that all domains in AcyMailer configuration are in lower cases
        $acymailerParams = $config->get('acymailer_domains', '[]');
        $acymailerParams = @json_decode($acymailerParams, true);
        if (!empty($acymailerParams)) {
            foreach ($acymailerParams as $domain => $domainParams) {
                $acymailerParams[$domain]['domain'] = acym_strtolower($domainParams['domain']);
                if (acym_strtolower($domain) === $domain) {
                    continue;
                }

                $acymailerParams[acym_strtolower($domain)] = $acymailerParams[$domain];
                unset($acymailerParams[$domain]);
            }
            $config->save(['acymailer_domains' => json_encode($acymailerParams)]);
        }

        $this->updateQuery('ALTER TABLE `#__acym_field` DROP COLUMN `access`');
    }

    private function updateFor961()
    {
        if ($this->isPreviousVersionAtLeast('9.6.1')) {
            return;
        }

        $this->updateQuery('ALTER TABLE `#__acym_mail` ADD INDEX `#__index_acym_mail2` (`type`)');
    }

    private function updateFor970()
    {
        if ($this->isPreviousVersionAtLeast('9.7.0')) {
            return;
        }

        $this->updateQuery('ALTER TABLE `#__acym_user` ADD INDEX `#__index_acym_user1` (`cms_id`)');
        $this->updateQuery('ALTER TABLE #__acym_followup ADD COLUMN `loop` TINYINT(1) NOT NULL DEFAULT 0');
        $this->updateQuery('ALTER TABLE #__acym_followup ADD COLUMN `loop_delay` INT NULL');
        $this->updateQuery('ALTER TABLE #__acym_followup ADD COLUMN `loop_mail_skip` VARCHAR(255) NULL');
    }

    private function updateFor980()
    {
        if ($this->isPreviousVersionAtLeast('9.8.0')) {
            return;
        }

        $this->updateQuery('UPDATE #__acym_plugin SET `type` = "CORE" WHERE `type` = "ADDON" AND `folder_name` = "contact"');
        $this->updateQuery('ALTER TABLE #__acym_mail ADD COLUMN `bounce_email` VARCHAR(100) NULL');
    }

    private function updateFor990()
    {
        if ($this->isPreviousVersionAtLeast('9.9.0')) {
            return;
        }

        $this->updateQuery(
            'UPDATE #__acym_mail 
            SET `body` = REPLACE(`body`, "images/poweredby_", "images/editor/poweredby_") 
            WHERE `body` LIKE "%images/poweredby_%"'
        );

        $this->updateQuery(
            'UPDATE #__acym_mail_archive 
            SET `body` = REPLACE(`body`, "images/poweredby_", "images/editor/poweredby_") 
            WHERE `body` LIKE "%images/poweredby_%"'
        );
    }

    private function updateFor9101()
    {
        if ($this->isPreviousVersionAtLeast('9.10.1')) {
            return;
        }

        // On some servers, there is a limit of 767 bytes for the index length, which corresponds to VARCHAR(191)
        $this->updateQuery('ALTER TABLE #__acym_user DROP INDEX `email_UNIQUE`');
        $this->updateQuery('ALTER TABLE #__acym_configuration CHANGE `name` `name` VARCHAR(190) NOT NULL');

        $this->updateQuery('ALTER TABLE #__acym_mail_archive      CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->updateQuery('ALTER TABLE #__acym_mailbox_action    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->updateQuery('ALTER TABLE #__acym_custom_zone       CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->updateQuery('ALTER TABLE #__acym_mail_override     CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->updateQuery('ALTER TABLE #__acym_followup_has_mail CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->updateQuery('ALTER TABLE #__acym_followup          CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->updateQuery('ALTER TABLE #__acym_segment           CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->updateQuery('ALTER TABLE #__acym_form              CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->updateQuery('ALTER TABLE #__acym_plugin            CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->updateQuery('ALTER TABLE #__acym_action            CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->updateQuery('ALTER TABLE #__acym_condition         CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->updateQuery('ALTER TABLE #__acym_history           CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->updateQuery('ALTER TABLE #__acym_rule              CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->updateQuery('ALTER TABLE #__acym_user_has_field    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->updateQuery('ALTER TABLE #__acym_field             CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->updateQuery('ALTER TABLE #__acym_url_click         CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->updateQuery('ALTER TABLE #__acym_url               CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->updateQuery('ALTER TABLE #__acym_user_stat         CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->updateQuery('ALTER TABLE #__acym_mail_stat         CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->updateQuery('ALTER TABLE #__acym_queue             CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->updateQuery('ALTER TABLE #__acym_mail_has_list     CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->updateQuery('ALTER TABLE #__acym_tag               CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->updateQuery('ALTER TABLE #__acym_step              CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->updateQuery('ALTER TABLE #__acym_automation        CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->updateQuery('ALTER TABLE #__acym_user_has_list     CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->updateQuery('ALTER TABLE #__acym_campaign          CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->updateQuery('ALTER TABLE #__acym_list              CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->updateQuery('ALTER TABLE #__acym_mail              CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->updateQuery('ALTER TABLE #__acym_configuration     CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->updateQuery('ALTER TABLE #__acym_user              CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        $this->updateQuery('TRUNCATE TABLE #__acym_mail_archive');
    }

    private function updateFor9102()
    {
        if ($this->isPreviousVersionAtLeast('9.10.2')) {
            return;
        }

        $duplicatedUsers = acym_loadObjectList(
            'SELECT GROUP_CONCAT(`id` ORDER BY `creation_date` SEPARATOR "-") AS `concatenated_ids`, COUNT(*) AS `nb_duplicates` 
            FROM #__acym_user 
            GROUP BY `email` 
            HAVING `nb_duplicates` > 1'
        );

        if (!empty($duplicatedUsers)) {
            $userIdsToDelete = [];

            foreach ($duplicatedUsers as $duplicatedUser) {
                $ids = explode('-', $duplicatedUser->concatenated_ids);
                $userIdsToDelete = array_merge($userIdsToDelete, array_slice($ids, 1));
            }

            acym_arrayToInteger($userIdsToDelete);
            $idsToDelete = implode(',', $userIdsToDelete);

            $this->updateQuery('DELETE FROM #__acym_user_has_list WHERE user_id IN ('.$idsToDelete.')');
            $this->updateQuery('DELETE FROM #__acym_queue WHERE user_id IN ('.$idsToDelete.')');
            $this->updateQuery('DELETE FROM #__acym_user_has_field WHERE user_id IN ('.$idsToDelete.')');
            $this->updateQuery('DELETE FROM #__acym_history WHERE user_id IN ('.$idsToDelete.')');
            $this->updateQuery('DELETE FROM #__acym_user_stat WHERE user_id IN ('.$idsToDelete.')');
            $this->updateQuery('DELETE FROM #__acym_user WHERE id IN ('.$idsToDelete.')');
        }

        $this->updateQuery('ALTER TABLE #__acym_user ADD UNIQUE INDEX `email_UNIQUE` (`email`(191) ASC)');
    }

    private function updateFor9110()
    {
        if ($this->isPreviousVersionAtLeast('9.11.0')) {
            return;
        }

        $templateThumbnails = acym_loadResultArray('SELECT `thumbnail` FROM #__acym_mail WHERE `thumbnail` IS NOT NULL');
        if (!empty($templateThumbnails)) {
            $generatedThumbnails = acym_getFiles(ACYM_UPLOAD_FOLDER_THUMBNAIL, 'thumbnail_.*');
            foreach ($generatedThumbnails as $generatedThumbnail) {
                if (!in_array($generatedThumbnail, $templateThumbnails)) {
                    acym_deleteFile(ACYM_UPLOAD_FOLDER_THUMBNAIL.$generatedThumbnail);
                }
            }
        }
    }
}
