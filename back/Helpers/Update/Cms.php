<?php

namespace AcyMailing\Helpers\Update;

use AcyMailing\Helpers\UpdatemeHelper;

trait Cms
{
    public function installTables(): void
    {
        $queries = file_get_contents(ACYM_BACK.'tables.sql');
        $tables = explode('CREATE TABLE IF NOT EXISTS', $queries);

        foreach ($tables as $oneTable) {
            $oneTable = trim($oneTable);
            if (empty($oneTable)) {
                continue;
            }
            acym_query('CREATE TABLE IF NOT EXISTS'.$oneTable);
        }
    }

    public function installLanguages(): void
    {
        $siteLanguages = acym_getLanguages();
        if (!empty($siteLanguages[ACYM_DEFAULT_LANGUAGE])) {
            unset($siteLanguages[ACYM_DEFAULT_LANGUAGE]);
        }

        $installedLanguages = array_keys($siteLanguages);
        if (empty($installedLanguages) || !class_exists(UpdatemeHelper::class)) {
            return;
        }

        ob_start();
        $languagesContent = UpdatemeHelper::call('public/download/translations?version=latest&codes='.implode(',', $installedLanguages));
        $warnings = ob_get_clean();
        if (!empty($warnings) && acym_isDebug()) {
            acym_enqueueMessage($warnings, 'warning');
        }

        if (empty($languagesContent) || $languagesContent['status'] === 'error') {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_LOAD_LANGUAGES'), 'error');

            return;
        }

        $decodedLanguages = $languagesContent['translations'];

        $success = [];
        $error = [];
        $errorLoad = [];

        foreach ($decodedLanguages as $code => $content) {
            if (empty($content)) {
                $errorLoad[] = $code;
                continue;
            }

            $path = acym_getLanguagePath(ACYM_ROOT, $code).DS.$code.'.'.ACYM_LANGUAGE_FILE.'.ini';
            if (acym_writeFile($path, $content)) {
                $this->installBackLanguages($code);
                $success[] = $code;
            } else {
                $error[] = acym_translationSprintf('ACYM_FAIL_SAVE_FILE', $path);
            }
        }

        if (!empty($success)) acym_enqueueMessage(acym_translationSprintf('ACYM_TRANSLATION_INSTALLED', implode(', ', $success)));
        if (!empty($error)) acym_enqueueMessage($error, 'error');
        if (!empty($errorLoad)) acym_enqueueMessage(acym_translationSprintf('ACYM_ERROR_LOAD_LANGUAGE', implode(', ', $errorLoad)), 'warning');
    }

    // translates the Acy menus on back-end and Joomla menus
    public function installBackLanguages(string $onlyCode = ''): void
    {
        if (ACYM_CMS !== 'joomla') {
            return;
        }

        $menuStrings = [
            'ACYM_DASHBOARD',
            'ACYM_SUBSCRIBERS',
            'ACYM_CUSTOM_FIELDS',
            'ACYM_LISTS',
            'ACYM_SEGMENTS',
            'ACYM_EMAILS',
            'ACYM_TEMPLATES',
            'ACYM_AUTOMATION',
            'ACYM_QUEUE',
            'ACYM_STATISTICS',
            'ACYM_BOUNCE_HANDLING',
            'ACYM_ADD_ONS',
            'ACYM_CONFIGURATION',
            'ACYM_MENU_PROFILE',
            'ACYM_MENU_PROFILE_DESC',
            'ACYM_MENU_ARCHIVE',
            'ACYM_MENU_ARCHIVE_DESC',
            'ACYM_MENU_LISTS',
            'ACYM_MENU_LISTS_DESC',
            'ACYM_MENU_SUBSCRIBERS',
            'ACYM_MENU_SUBSCRIBERS_DESC',
            'ACYM_MENU_CAMPAIGNS',
            'ACYM_MENU_CAMPAIGNS_DESC',
            'ACYM_SUBSCRIPTION_FORMS',
            'ACYM_EMAILS_OVERRIDE',
            'ACYM_MAILBOX_ACTIONS',
            'ACYM_GOPRO',
            'ACYM_SCENARIO',
        ];

        $siteLanguages = empty($onlyCode) ? array_keys(acym_getLanguages()) : [$onlyCode];

        foreach ($siteLanguages as $code) {
            $path = acym_getLanguagePath(ACYM_ROOT, $code).DS.$code.'.com_acym.ini';
            if (!file_exists($path)) continue;

            $content = file_get_contents($path);
            if (empty($content)) continue;

            // The first key is to translate "Acym" into "AcyMailing blabla" in the Joomla Extension manager
            // The second key is to translate "com_acym" into "AcyMailing" in the Joomla global configuration page
            // DON'T CHANGE THE KEY !!
            $menuFileContent = 'ACYM="AcyMailing"'."\r\n";
            $menuFileContent .= 'COM_ACYM="AcyMailing"'."\r\n";
            $menuFileContent .= 'COM_ACYM_CONFIGURATION="AcyMailing"'."\r\n";

            foreach ($menuStrings as $oneString) {
                preg_match('#[^_]'.$oneString.'="(.*)"#i', $content, $matches);
                if (empty($matches[1])) continue;

                $menuFileContent .= $oneString.'="'.$matches[1].'"'."\r\n";
            }

            $menuPath = ACYM_ROOT.'administrator'.DS.'language'.DS.$code.DS.$code.'.com_acym.sys.ini';

            if (!acym_writeFile($menuPath, $menuFileContent)) {
                acym_enqueueMessage(acym_translationSprintf('ACYM_FAIL_SAVE_FILE', $menuPath), 'error');
            }
        }
    }
}
