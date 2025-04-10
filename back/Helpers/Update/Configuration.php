<?php

namespace AcyMailing\Helpers\Update;

trait Configuration
{
    public function addPref(): bool
    {
        $this->level = ucfirst($this->level);

        $allPref = acym_getDefaultConfigValues();

        $allPref['level'] = $this->level;
        $allPref['version'] = $this->version;

        $allPref['from_name'] = acym_getCMSConfig('fromname');
        $allPref['from_email'] = acym_getCMSConfig('mailfrom');
        $allPref['bounce_email'] = acym_getCMSConfig('mailfrom');
        $cmsMailer = acym_getCMSConfig('mailer', 'phpmail');
        $allPref['mailer_method'] = $cmsMailer === 'mail' ? 'phpmail' : $cmsMailer;
        $allPref['sendmail_path'] = acym_getCMSConfig('sendmail');
        $allPref['smtp_port'] = acym_getCMSConfig('smtpport');
        $allPref['smtp_secured'] = acym_getCMSConfig('smtpsecure');
        $allPref['smtp_auth'] = acym_getCMSConfig('smtpauth');
        $allPref['smtp_username'] = acym_getCMSConfig('smtpuser');
        $allPref['smtp_password'] = acym_getCMSConfig('smtppass');

        $allPref['replyto_name'] = $allPref['from_name'];
        $allPref['replyto_email'] = $allPref['from_email'];
        $allPref['cron_sendto'] = $allPref['from_email'];

        $allPref['add_names'] = '1';
        $allPref['encoding_format'] = '8bit';
        $allPref['charset'] = 'UTF-8';
        $allPref['word_wrapping'] = '150';
        $allPref['hostname'] = '';
        $allPref['embed_images'] = '0';
        $allPref['embed_files'] = '0';
        $allPref['editor'] = 'codemirror';
        $allPref['multiple_part'] = '1';

        $smtpinfos = explode(':', acym_getCMSConfig('smtphost', ''));
        $allPref['smtp_host'] = $smtpinfos[0];
        if (isset($smtpinfos[1])) {
            $allPref['smtp_port'] = $smtpinfos[1];
        }
        if (!in_array($allPref['smtp_secured'], ['tls', 'ssl'])) {
            $allPref['smtp_secured'] = '';
        }

        $allPref['social_icons'] = json_encode([
                'facebook' => ACYM_IMAGES.'logo/facebook.png',
                'twitter' => ACYM_IMAGES.'logo/twitter.png',
                'x' => ACYM_IMAGES.'logo/x.png',
                'instagram' => ACYM_IMAGES.'logo/instagram.png',
                'linkedin' => ACYM_IMAGES.'logo/linkedin.png',
                'pinterest' => ACYM_IMAGES.'logo/pinterest.png',
                'vimeo' => ACYM_IMAGES.'logo/vimeo.png',
                'wordpress' => ACYM_IMAGES.'logo/wordpress.png',
                'youtube' => ACYM_IMAGES.'logo/youtube.png',
                'telegram' => ACYM_IMAGES.'logo/telegram.png',
            ]
        );

        $allPref['queue_nbmail'] = '40';
        $allPref['queue_nbmail_auto'] = '70';
        $allPref['queue_type'] = 'auto';
        $allPref['queue_try'] = '3';
        $allPref['queue_pause'] = '120';
        $allPref['allow_visitor'] = '1';
        $allPref['require_confirmation'] = '1';
        $allPref['priority_newsletter'] = '3';
        $allPref['allowed_files'] = 'zip,doc,docx,pdf,xls,txt,gzip,rar,jpg,jpeg,gif,xlsx,pps,csv,bmp,ico,odg,odp,ods,odt,png,ppt,swf,xcf,mp3,wma';
        $allPref['confirm_redirect'] = '';
        $allPref['subscription_message'] = '1';
        $allPref['notification_unsuball'] = '';
        $allPref['cron_next'] = '1251990901';
        $allPref['confirmation_message'] = '1';
        $allPref['welcome_message'] = '1';
        $allPref['unsub_message'] = '1';
        $allPref['cron_last'] = '0';
        $allPref['cron_fromip'] = '';
        $allPref['cron_report'] = '';
        $allPref['cron_frequency'] = '900';
        $allPref['cron_sendreport'] = '2';
        $allPref['cron_fullreport'] = '1';
        $allPref['cron_savereport'] = '2';
        $allPref['uploadfolder'] = str_replace('\\', '/', ACYM_UPLOAD_FOLDER);
        $allPref['notification_created'] = '';
        $allPref['notification_accept'] = '';
        $allPref['notification_refuse'] = '';
        $allPref['forward'] = '0';
        $allPref['priority_followup'] = '2';
        $allPref['unsub_redirect'] = '';
        $allPref['use_sef'] = '0';
        $allPref['css_frontend'] = '';
        $allPref['css_backend'] = '';
        $allPref['last_import'] = '';
        $allPref['unsub_reasons'] = serialize(['UNSUB_SURVEY_FREQUENT', 'UNSUB_SURVEY_RELEVANT']);
        $allPref['security_key'] = acym_generateKey(30);
        $allPref['export_excelsecurity'] = 1;
        $allPref['gdpr_export'] = 0;
        $allPref['gdpr_delete'] = 0;
        $allPref['anonymous_tracking'] = '0';
        $allPref['anonymizeold'] = '0';
        $allPref['trackingsystem'] = 'acymailing';
        $allPref['trackingsystemexternalwebsite'] = 1;
        $allPref['generate_name'] = 1;
        $allPref['allow_modif'] = 'data';
        $allPref['from_as_replyto'] = '1';
        $allPref['templates_installed'] = '0';
        $allPref['bounceVersion'] = self::BOUNCE_VERSION;
        $allPref['numberThumbnail'] = 2;
        $allPref['daily_hour'] = '12';
        $allPref['daily_minute'] = '00';
        $allPref['regacy'] = 1;
        $allPref['regacy_delete'] = 1;
        $allPref['regacy_forceconf'] = 0;
        $allPref['remindme'] = '[]';
        $allPref['notifications'] = '{}';
        $allPref['unsubscribe_page'] = 1;
        $allPref['delete_stats_enabled'] = 1;
        $allPref['delete_stats'] = 86400 * 360;
        $allPref['delete_archive_history_after'] = 86400 * 90;

        $allPref['install_date'] = time();
        $allPref['license_key'] = '';
        $allPref['active_cron'] = 0;
        $allPref['multilingual'] = 0;

        $allPref['walk_through'] = '1';
        $allPref['migration'] = '0';
        $allPref['installcomplete'] = '0';

        $allPref['Starter'] = ACYM_STARTER;
        $allPref['Essential'] = ACYM_ESSENTIAL;
        $allPref['Enterprise'] = ACYM_ENTERPRISE;
        $allPref['previous_version'] = '{__VERSION__}';

        $allPref['display_built_by'] = acym_level(ACYM_ESSENTIAL) ? 0 : 1;
        $allPref['php_overrides'] = 0;

        $query = 'INSERT IGNORE INTO `#__acym_configuration` (`name`,`value`) VALUES ';
        foreach ($allPref as $namekey => $value) {
            $query .= '('.acym_escapeDB($namekey).','.acym_escapeDB($value).'),';
        }
        $query = rtrim($query, ',');

        return $this->updateQuery($query, 'display');
    }

    public function updatePref(): bool
    {
        try {
            $results = acym_loadObjectList('SELECT `name`, `value` FROM `#__acym_configuration` WHERE `name` IN ("version", "level") LIMIT 2', 'name');
        } catch (\Exception $e) {
            $results = null;
        }

        if ($results === null) {
            acym_display(isset($e) ? $e->getMessage() : substr(strip_tags(acym_getDBError()), 0, 200).'...', 'error');

            return false;
        }

        if (!empty($results['version'])) {
            $this->firstInstallation = false;
        }

        if ($results['version']->value === $this->version && $results['level']->value === $this->level) {
            return true;
        }

        $this->isUpdating = true;
        $this->previousVersion = $results['version']->value;

        //We update the version properly as it's a new one which is now used.
        $query = 'REPLACE INTO `#__acym_configuration` (`name`,`value`) VALUES ("level",'.acym_escapeDB($this->level).')';
        $query .= ',("version",'.acym_escapeDB($this->version).')';
        $query .= ',("installcomplete","0")';
        $query .= ',("previous_version",'.acym_escapeDB($this->previousVersion).')';

        return $this->updateQuery($query);
    }
}
