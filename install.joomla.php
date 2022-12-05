<?php

use AcyMailing\Helpers\UpdateHelper;

if (version_compare(PHP_VERSION, '5.6.0', '<')) {
    echo '<p style="color:red">This version of AcyMailing requires at least PHP 5.6.0, it is time to upgrade the PHP version of your server!</p>';
    exit;
}

function installAcym()
{
    try {
        include_once(rtrim(
                JPATH_ADMINISTRATOR,
                DIRECTORY_SEPARATOR
            ).DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_acym'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'helper.php');
    } catch (Exception $e) {
        echo 'Initialization error, please re-install';

        return;
    }

    //First we increase the perfs so that we won't have any surprise.
    acym_increasePerf();

    $installClass = new acymInstall();
    $installClass->installTables();
    $installClass->addPref();
    $installClass->updatePref();
    $installClass->updateSQL();
    $installClass->checkDB();

    $updateHelper = new UpdateHelper();
    $updateHelper->fromLevel = $installClass->fromLevel;
    $updateHelper->fromVersion = $installClass->fromVersion;
    $updateHelper->installList();
    $updateHelper->installNotifications();

    if ($installClass->firstInstallation) {
        $updateHelper->installTemplates();
        $updateHelper->installDefaultAutomations();
    }

    if (!$installClass->update) {
        $installClass->deleteNewSplashScreenInstall();
    } elseif (!empty($installClass->fromVersion)) {
        $fromVersion = substr($installClass->fromVersion, 0, strrpos($installClass->fromVersion, '.'));
        $toVersion = substr($installClass->version, 0, strrpos($installClass->version, '.'));

        if (version_compare($fromVersion, $toVersion, '=')) {
            $installClass->deleteNewSplashScreenInstall();
        }
    }

    $updateHelper->installFields();
    $updateHelper->installLanguages();
    $updateHelper->installBackLanguages();
    $updateHelper->addUpdateSite();
    $updateHelper->installBounceRules();
    $updateHelper->installAddons();
    $updateHelper->installOverrideEmails();

    $newConfig = new stdClass();
    $newConfig->installcomplete = 1;
    $config = acym_config();
    $config->save($newConfig);
}

function uninstallAcym()
{
    $db = JFactory::getDBO();
    $jversion = preg_replace('#[^0-9\.]#i', '', JVERSION);
    $method = version_compare($jversion, '4.0.0', '>=') ? 'execute' : 'query';

    // Unpublish modules
    $db->setQuery("UPDATE `#__modules` SET `published` = 0 WHERE `module` = 'mod_acym'");
    $db->$method();

    ?>
	AcyMailing successfully uninstalled.<br />
	Its modules have been disabled.<br /><br />
	If you want to completely uninstall AcyMailing and remove its data, please uninstall all the AcyMailing modules and plugins from the Joomla Extensions Manager then run the following query on your database manager:
	<br /><br />
    <?php

    $tables = [
        'custom_zone',
        'mail_override',
        'followup_has_mail',
        'followup',
        'segment',
        'form',
        'plugin',
        'action',
        'condition',
        'history',
        'rule',
        'user_has_field',
        'field',
        'url_click',
        'url',
        'user_stat',
        'mail_stat',
        'queue',
        'mail_has_list',
        'tag',
        'step',
        'automation',
        'user_has_list',
        'campaign',
        'list',
        'mail',
        'configuration',
        'user',
    ];

    $prefix = $db->getPrefix().'acym_';
    echo 'DROP TABLE '.$prefix.implode(', '.$prefix, $tables).';';

    ?>
	<br /><br />
	If you don't do this, you will be able to install AcyMailing again without losing your data.<br />
	Please note that you don't have to uninstall AcyMailing to install a new version, simply install it over the current version.<br /><br />
    <?php
}

/**
 * This is for J2.5
 */
if (!function_exists('com_install')) {
    function com_install()
    {
        return installAcym();
    }
}

if (!function_exists('com_uninstall')) {
    function com_uninstall()
    {
        return uninstallAcym();
    }
}

/**
 * This is for J3
 */
class com_acymInstallerScript
{
    public function install($parent)
    {
        installAcym();
    }

    public function update($parent)
    {
        installAcym();
    }

    public function uninstall($parent)
    {
        return uninstallAcym();
    }

    public function preflight($type, $parent)
    {
        if ($type === 'update') {
            $db = JFactory::getDbo();
            $query = $db->getQuery(true)->select('*')->from('#__extensions');
            $query->where(
                'type = "component" AND element = "com_acym"'
            );
            $db->setQuery($query);

            try {
                $extension = $db->loadObject();
            } catch (Exception $e) {
                echo JText::sprintf('JLIB_DATABASE_ERROR_FUNCTION_FAILED', $e->getCode(), $e->getMessage()).'<br />';

                return false;
            }

            $installer = new JInstaller();
            $installer->refreshManifestCache($extension->extension_id);
        }

        return true;
    }

    public function postflight($type, $parent)
    {
        return true;
    }
}

include_once __DIR__.DIRECTORY_SEPARATOR.'install.class.php';
