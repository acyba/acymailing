<?php

namespace AcyMailing\WpInit;

class Update
{
    private bool $cancelUpdate = false;
    private string $pluginSlug;

    public function __construct()
    {
        $this->pluginSlug = plugin_basename(dirname(__DIR__).'/index.php');

        // Set the correct download URL for paid versions updates
        add_filter('pre_set_site_transient_update_plugins', [$this, 'checkUpdates'], 10, 1);
        add_filter('site_transient_update_plugins', [$this, 'checkUpdates'], 10, 1);
        // Last second check on the download URL
        add_filter('upgrader_package_options', [$this, 'checkDownloadUrl']);
        add_action('upgrader_process_complete', [$this, 'afterUpdate'], 20, 2);
    }

    public function checkDownloadUrl($options)
    {
        if (empty($options['hook_extra']['plugin']) || $options['hook_extra']['plugin'] !== $this->pluginSlug || !acym_level(ACYM_ESSENTIAL)) {
            return $options;
        }

        if (isset($options['package']) && strpos($options['package'], 'wordpress.org') !== false) {
            $this->checkVersion(true);
            $config = acym_config(true);
            $options['package'] = $config->get('downloadurl', '');
        }

        return $options;
    }

    public function checkUpdates($transient)
    {
        $this->checkVersion();

        if ($this->cancelUpdate) {
            if (!empty($transient->response[$this->pluginSlug])) {
                unset($transient->response[$this->pluginSlug]);
            }

            return $transient;
        }

        if (!acym_level(ACYM_ESSENTIAL) || !empty($transient->no_update[$this->pluginSlug]) || empty($transient->response[$this->pluginSlug])) {
            return $transient;
        }

        $config = acym_config();
        $downloadURL = $config->get('downloadurl', '');

        if (strpos($downloadURL, 'http') === false) {
            $downloadURL = '';
            add_action('admin_notices', [$this, 'noticeUpdate'], 110);
        }

        $transient->response[$this->pluginSlug]->package = $downloadURL;

        return $transient;
    }

    /**
     * Reset the package download URL when successfully updated the plugin
     *
     * @param $upgrader_object
     * @param $options
     */
    public function afterUpdate($upgrader_object, $options): void
    {
        if ($options['action'] !== 'update' || $options['type'] !== 'plugin') {
            return;
        }

        if (!empty($options['plugin']) && $options['plugin'] === $this->pluginSlug) {
            $this->resetUpdateData();
        } elseif (!empty($options['plugins'])) {
            foreach ($options['plugins'] as $onePluginSlug) {
                if ($onePluginSlug !== $this->pluginSlug) {
                    continue;
                }

                $this->resetUpdateData();
                break;
            }
        }
    }

    public function noticeUpdate()
    {
        global $pagenow;
        if (!in_array($pagenow, ['update-core.php', 'plugins.php'])) {
            return;
        }

        echo '<div class="notice notice-error is-dismissible">
                 <p>AcyMailing: '.acym_translation('ACYM_PAID_VERSION_NEED_UPDATE_ERROR_LICENSE_ATTACH').'</p>
             </div>';
    }

    private function checkVersion(bool $forceCheck = false): void
    {
        $config = acym_config();
        $lastCheck = $config->get('lastupdatecheck', 0);

        static $alreadyChecked = false;
        if (!$forceCheck && ($alreadyChecked || ($lastCheck > time() - 86400 && empty($_REQUEST['force-check'])))) {
            return;
        }
        $alreadyChecked = true;

        $url = ACYM_UPDATEME_API_URL.'public/updatexml/component?extension=acymailing&cms=wordpress&version=latest&level={__LEVEL__}';
        if (acym_level(ACYM_ESSENTIAL)) {
            $url .= '&website='.urlencode(ACYM_LIVE);
        }

        $updateInformation = acym_fileGetContent($url);
        $xmlPos = strpos($updateInformation, '<?xml');
        if ($xmlPos === false) {
            return;
        }

        $updateInformation = substr($updateInformation, $xmlPos);

        try {
            $xml = new \SimpleXMLElement($updateInformation);
            $latestVersion = (string)$xml->update[0]->version;
            $downloadURL = (string)$xml->update[0]->downloadurl;
        } catch (\Exception $err) {
            return;
        }

        $currentVersion = $config->get('version');
        if (!empty($currentVersion) && version_compare($currentVersion, $latestVersion, '>=')) {
            $this->cancelUpdate = true;
        }

        // Save the latest version and the license expiration date to warn the user when something's wrong
        $newConfig = new \stdClass();
        $newConfig->lastupdatecheck = time();
        $newConfig->latestversion = $latestVersion;
        $newConfig->downloadurl = $downloadURL;

        // Save the downloadurl in all the sites' config to ensure a correct download for special ways to update
        if (is_multisite()) {
            $currentBlog = get_current_blog_id();
            $sites = function_exists('get_sites') ? get_sites() : wp_get_sites();

            foreach ($sites as $site) {
                if (is_object($site)) {
                    $site = get_object_vars($site);
                }
                switch_to_blog($site['blog_id']);
                $config->save($newConfig);
            }

            switch_to_blog($currentBlog);
        }

        $config->save($newConfig);

        if (acym_level(ACYM_ESSENTIAL)) {
            acym_checkVersion();
        }
    }

    private function resetUpdateData(): void
    {
        $config = acym_config();
        $newConfig = new \stdClass();
        $newConfig->downloadurl = '';
        $newConfig->lastupdatecheck = 0;
        $config->save($newConfig);
    }
}
