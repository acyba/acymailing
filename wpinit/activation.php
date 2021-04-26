<?php

namespace AcyMailing\Init;

use AcyMailing\Helpers\UpdateHelper;

class acyActivation extends acyHook
{
    public function __construct()
    {
        $entryPoint = dirname(__DIR__).'/index.php';
        // Install Acy DB and sample data on first activation (not installation because of FTP install)
        register_activation_hook($entryPoint, [$this, 'install']);

        // Add "settings" link for acym on plugins listing
        add_filter('plugin_action_links_'.plugin_basename($entryPoint), [$this, 'addPluginLinks']);
    }

    // Install DB and sample data
    public function install()
    {
        $file_name = rtrim(dirname(__DIR__), DS).DS.'back'.DS.'tables.sql';
        $handle = fopen($file_name, 'r');
        $queries = fread($handle, filesize($file_name));
        fclose($handle);

        if (is_multisite()) {
            $currentBlog = get_current_blog_id();
            $sites = function_exists('get_sites') ? get_sites() : wp_get_sites();

            foreach ($sites as $site) {
                if (is_object($site)) {
                    $site = get_object_vars($site);
                }
                switch_to_blog($site['blog_id']);
                $this->_sampledata($queries);
            }

            switch_to_blog($currentBlog);
        } else {
            $this->_sampledata($queries);
        }

        if (file_exists(ACYM_FOLDER.'update.php')) {
            unlink(ACYM_FOLDER.'update.php');
        }
    }

    private function _sampledata($queries)
    {
        global $wpdb;
        $prefix = acym_getPrefix();

        $acytables = str_replace('#__', $prefix, $queries);
        $tables = explode('CREATE TABLE IF NOT EXISTS', $acytables);

        foreach ($tables as $oneTable) {
            $oneTable = trim($oneTable);
            if (empty($oneTable)) {
                continue;
            }
            $wpdb->query('CREATE TABLE IF NOT EXISTS'.$oneTable);
        }

        $this->updateAcym();
    }

    public function updateAcym()
    {
        $config = acym_config();
        if (!file_exists(ACYM_FOLDER.'update.php') && $config->get('installcomplete', 0) != 0) {
            return;
        }

        require_once ACYM_FOLDER.'install.class.php';

        if (!class_exists('acymInstall')) return;

        //First we increase the perfs so that we won't have any surprise.
        acym_increasePerf();

        $installClass = new \acymInstall();
        $installClass->addPref();
        $installClass->updatePref();
        $installClass->updateSQL();
        $installClass->checkDB();

        $config->save(['downloadurl' => '', 'lastupdatecheck' => '0']);

        $updateHelper = new UpdateHelper();
        $updateHelper->fromLevel = $installClass->fromLevel;
        $updateHelper->fromVersion = $installClass->fromVersion;

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
        // Only install the templates if it is the first install
        if (!$installClass->update) {
            $installClass->deleteNewSplashScreenInstall();
            $updateHelper->installTemplates(true);
        } else {
            if (!empty($installClass->fromVersion)) {
                $fromVersion = substr($installClass->fromVersion, 0, strrpos($installClass->fromVersion, '.'));
                $toVersion = substr($installClass->version, 0, strrpos($installClass->version, '.'));

                if (version_compare($fromVersion, $toVersion, '=')) {
                    $installClass->deleteNewSplashScreenInstall();
                }
            }
        }
        $updateHelper->installFields();
        $updateHelper->installAdminNotif();
        $updateHelper->installAddons();
        $updateHelper->installOverrideEmails();

        $newConfig = new \stdClass();
        $newConfig->installcomplete = 1;
        $config->save($newConfig);

        // Reload conf
        acym_config(true);
    }

    // Add links on the plugins listing
    public function addPluginLinks($links)
    {
        $settings_link = '<a href="admin.php?page='.ACYM_COMPONENT.'_configuration">'.__('Settings').'</a>';
        $links = array_merge([$settings_link], $links);

        return $links;
    }
}

$acyActivation = new acyActivation();
