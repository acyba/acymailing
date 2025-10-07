<?php

namespace AcyMailing\WpInit;

use AcyMailing\Helpers\UpdateHelper;

class Activation
{
    // Install DB and sample data
    public function install(): void
    {
        $file_name = rtrim(dirname(__DIR__), DS).DS.'back'.DS.'tables.sql';
        $handle = fopen($file_name, 'r');
        $queries = fread($handle, filesize($file_name));
        fclose($handle);

        // If it is a network activation (activate on all websites)
        if (is_multisite() && is_network_admin()) {
            $currentBlog = get_current_blog_id();
            $sites = function_exists('get_sites') ? get_sites() : wp_get_sites();

            // Install on all websites
            foreach ($sites as $site) {
                if (is_object($site)) {
                    $site = get_object_vars($site);
                }
                switch_to_blog($site['blog_id']);
                $this->sampledata($queries);
            }

            // Switch back to network main site
            switch_to_blog($currentBlog);
        } else {
            $this->sampledata($queries);
        }

        if (file_exists(ACYM_FOLDER.'update.php')) {
            unlink(ACYM_FOLDER.'update.php');
        }
    }

    private function sampledata(string $queries): void
    {
        global $wpdb;
        $prefix = acym_getPrefix();

        $tableCreationQueries = explode('CREATE TABLE IF NOT EXISTS', str_replace('#__', $prefix, $queries));

        foreach ($tableCreationQueries as $oneTable) {
            $oneTable = trim($oneTable);
            if (empty($oneTable)) {
                continue;
            }

            $wpdb->query('CREATE TABLE IF NOT EXISTS'.$oneTable);
        }

        $this->updateAcym();
    }

    public function updateAcym(): void
    {
        if (!in_array(acym_getPrefix().'acym_configuration', acym_getTables(true))) {
            return;
        }

        $config = acym_config(true);
        if (!file_exists(ACYM_FOLDER.'update.php') && $config->get('installcomplete', 0) != 0) {
            return;
        }

        //First we increase the perfs so that we won't have any surprise.
        acym_increasePerf();

        $updateHelper = new UpdateHelper();
        $updateHelper->addPref();
        $updateHelper->updatePref();
        $updateHelper->updateSQL();
        $updateHelper->checkDB();

        $config->save(['downloadurl' => '', 'lastupdatecheck' => '0']);

        $languageFiles = acym_getFiles(ACYM_FOLDER.'language'.DS, '\.ini');
        acym_createFolder(ACYM_LANGUAGE);
        acym_createFolder(ACYM_UPLOAD_FOLDER_THUMBNAIL);
        if (!empty($languageFiles)) {
            foreach ($languageFiles as $oneFile) {
                acym_copyFile(ACYM_FOLDER.'language'.DS.$oneFile, ACYM_LANGUAGE.$oneFile);
            }
        }

        $updateHelper->installBounceRules();
        $updateHelper->installList();
        $updateHelper->installNotifications();

        if ($updateHelper->firstInstallation) {
            $updateHelper->installTemplates();
            $updateHelper->installDefaultAutomations();
        }

        $updateHelper->deleteNewSplashScreenInstall();

        $updateHelper->installFields();
        $updateHelper->installAddons();
        $updateHelper->installOverrideEmails();

        $config->save(['installcomplete' => 1]);

        // Reload conf
        acym_config(true);
    }
}
