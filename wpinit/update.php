<?php

namespace AcyMailing\Init;

class acyUpdate extends acyHook
{
    public function __construct()
    {
        add_filter('site_transient_update_plugins', [$this, 'checkUpdates'], 10, 1);
        add_action('upgrader_process_complete', [$this, 'after_update'], 20, 2);
        wp_cache_delete('plugins', 'plugins');
    }

    public function checkUpdates($transient)
    {
        $plugin_slug = plugin_basename(dirname(__DIR__).'/index.php');
        if (!empty($transient->no_update[$plugin_slug])) return $transient;

        // Get latest version

        $config = acym_config();
        $downloadURL = $config->get('downloadurl', '');
        $lastCheck = $config->get('lastupdatecheck', 0);

        if (!empty($transient->response[$plugin_slug])) $transient->response[$plugin_slug]->package = $downloadURL;


        // We already have a downloadURL, no need to call acymailing.com
        if (!empty($downloadURL)) return $transient;

        // If acymailing.com has been called in the last 24h and we didn't click on force-check
        if ($lastCheck > time() - 86400 && (empty($_REQUEST['force-check']) || $_REQUEST['force-check'] != 1)) return $transient;

        // Don't call 50 times per page load, only useful when clicking the "Check again" button
        static $alreadyChecked = false;
        if ($alreadyChecked) return $transient;
        $alreadyChecked = true;


        $currentVersion = $config->get('version');
        $url = ACYM_UPDATEMEURL.'updatexml&component=acymailing&cms=wp&level='.$config->get('level').'&version='.$currentVersion;
        if (acym_level(ACYM_ESSENTIAL)) {
            $url .= '&li='.urlencode(base64_encode(ACYM_LIVE));
        }

        $updateInformation = acym_fileGetContent($url, 10);
        $updateInformation = substr($updateInformation, strpos($updateInformation, '<?xml'));

        try {
            $xml = new \SimpleXMLElement($updateInformation);
            $latestVersion = (string)$xml->update[0]->version;
            $downloadURL = (string)$xml->update[0]->downloadurl;
        } catch (\Exception $err) {
            return $transient;
        }

        if (!empty($currentVersion) && version_compare($currentVersion, $latestVersion, '>=')) {
            if (!empty($transient->response[$plugin_slug])) {
                unset($transient->response[$plugin_slug]);
            }

            return $transient;
        }

        // Add the update to transient if any
        if (strpos($downloadURL, 'http') === false) {
            $downloadURL = '';
            add_action('admin_notices', [$this, 'notice_update'], 110);
        }

        if (empty($transient->response[$plugin_slug])) {
            // Avoid error on wp update when nothing needs to be updated
            if (empty($transient) && !is_object($transient)) $transient = new \stdClass();
            if (empty($transient->response) && (!isset($transient->response) || !is_array($transient->response))) $transient->response = [];

            $transient->response[$plugin_slug] = (object)[
                'new_version' => $latestVersion,
                'package' => $downloadURL,
                'slug' => $plugin_slug,
                'icons' => [
                    '1x' => ACYM_IMAGES.'logo_icon.png',
                ],
                'url' => 'https://wordpress.org/plugins/acymailing/',
            ];
        } else {
            $transient->response[$plugin_slug]->package = $downloadURL;
        }

        // Save the latest version and the license expiration date to warn the user when something's wrong
        $newConfig = new \stdClass();
        $newConfig->lastupdatecheck = time();
        $newConfig->latestversion = $latestVersion;
        $newConfig->downloadurl = $downloadURL;

        $config->save($newConfig);

        if (acym_level(ACYM_ESSENTIAL)) acym_checkVersion();

        return $transient;
    }

    /**
     * Reset the package download URL when successfully updated the plugin
     *
     * @param $upgrader_object
     * @param $options
     */
    public function after_update($upgrader_object, $options)
    {
        if ($options['action'] != 'update' || $options['type'] != 'plugin') return;

        $current_plugin_path_name = plugin_basename(dirname(__DIR__).'/index.php');

        if (!empty($options['plugin']) && $options['plugin'] == $current_plugin_path_name) {
            $config = acym_config();

            $newConfig = new \stdClass();
            $newConfig->downloadurl = '';
            $newConfig->lastupdatecheck = 0;
            $config->save($newConfig);
        } elseif (!empty($options['plugins'])) {
            foreach ($options['plugins'] as $each_plugin) {
                if ($each_plugin != $current_plugin_path_name) continue;
                $config = acym_config();

                // If the website isn't attached to any license
                $downloadURL = $config->get('downloadurl');
                if (empty($downloadURL)) {
                    $dummyTransient = new \stdClass();
                    $this->checkUpdates($dummyTransient);

                    $downloadURL = $config->get('downloadurl');
                    if (empty($downloadURL)) {
                        echo acym_translation('ACYM_PAID_VERSION_NEED_UPDATE_ERROR_LICENSE_ATTACH');
                        exit;
                    }
                }

                $newConfig = new \stdClass();
                $newConfig->downloadurl = '';
                $newConfig->lastupdatecheck = 0;
                $config->save($newConfig);
            }
        }
    }

    public function notice_update()
    {
        global $pagenow;
        if (!in_array($pagenow, ['update-core.php', 'plugins.php'])) return;

        echo '<div class="notice notice-error is-dismissible">
                 <p>'.acym_translation('ACYM_PAID_VERSION_NEED_UPDATE_ERROR_LICENSE_ATTACH').'</p>
             </div>';
    }
}

new acyUpdate();
