<?php
/*
Plugin Name: AcyMailing
Description: Manage your contact lists and send newsletters from your site.
Author: AcyMailing Newsletter Team
Author URI: https://www.acymailing.com
License: GPLv3
Version: 7.9.0
Text Domain: acymailing
Domain Path: /language
*/

use AcyMailing\Init\acyActivation;

defined('ABSPATH') || die('Restricted Access');

$acyMailingLoader = new acymailingLoader();

// Install Acy DB and sample data on first activation (not on installation because of FTP install)
register_activation_hook(__DIR__.'/index.php', [$acyMailingLoader, 'activation']);

// Prevent bad plugins from loading on AcyMailing pages
add_action('plugins_loaded', [$acyMailingLoader, 'protectAcyMailingPages'], 5);

// Init widgets. According to the WP doc widgets_init should be loaded after init, but it isn't
add_action('widgets_init', [$acyMailingLoader, 'initWidgets']);

// Init AcyMailing
add_action('init', [$acyMailingLoader, 'initAcyMailing'], 0);


class acymailingLoader
{
    public function activation()
    {
        // Load Acy library
        $helperFile = __DIR__.DIRECTORY_SEPARATOR.'back'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'helper.php';
        if (file_exists($helperFile) && include_once $helperFile) {
            $acyActivation = new acyActivation();
            $acyActivation->install();
        }
    }

    public function protectAcyMailingPages()
    {
        // Make sure we're on an AcyMailing page
        $page = isset($_REQUEST['page']) ? sanitize_text_field(wp_unslash($_REQUEST['page'])) : '';
        if (empty($page) || strpos($page, 'acymailing_') === false) return;

        // Prevent plugins from breaking AcyMailing pages (mainly JS scripts loaded without the WP way)
        remove_action('plugins_loaded', 'mailchimp_on_all_plugins_loaded', 12);
        remove_action('plugins_loaded', '_imagify_init');
        remove_action('plugins_loaded', 'plugins_loaded_wps_hide_login_plugin');
        remove_action('plugins_loaded', ['WPAS_Gas', 'get_instance'], 11);
    }

    public function initWidgets()
    {
        $ds = DIRECTORY_SEPARATOR;
        include_once __DIR__.$ds.'widgets'.$ds.'archive'.$ds.'widget.php';
        include_once __DIR__.$ds.'widgets'.$ds.'profile'.$ds.'widget.php';
        include_once __DIR__.$ds.'widgets'.$ds.'subscriptionform'.$ds.'widget.php';

        register_widget('acym_archive_widget');
        register_widget('acym_profile_widget');
        register_widget('acym_subscriptionform_widget');
    }

    public function initAcyMailing()
    {
        // Load Acy library
        $helperFile = __DIR__.DIRECTORY_SEPARATOR.'back'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'helper.php';
        if (file_exists($helperFile) && include_once $helperFile) {
            //__START__development_
            acym_displayErrors();
            acym_query('SET SESSION query_cache_type=0;');
            //__END__development_

            include_once __DIR__.DS.'wpinit'.DS.'update.php';
            include_once __DIR__.DS.'wpinit'.DS.'router.php';
            include_once __DIR__.DS.'wpinit'.DS.'menu.php';
            include_once __DIR__.DS.'wpinit'.DS.'usersynch.php';
            include_once __DIR__.DS.'wpinit'.DS.'message.php';
            include_once __DIR__.DS.'wpinit'.DS.'elementor.php';
            include_once __DIR__.DS.'wpinit'.DS.'beaver.php';
            include_once __DIR__.DS.'wpinit'.DS.'wprocket.php';
            include_once __DIR__.DS.'wpinit'.DS.'addons.php';
            include_once __DIR__.DS.'wpinit'.DS.'forms.php';
            include_once __DIR__.DS.'wpinit'.DS.'override_email.php';
            include_once __DIR__.DS.'wpinit'.DS.'cron.php';
            include_once __DIR__.DS.'wpinit'.DS.'gutenberg.php';
            include_once __DIR__.DS.'wpinit'.DS.'security.php';
            include_once __DIR__.DS.'wpinit'.DS.'deactivate.php';
            include_once __DIR__.DS.'wpinit'.DS.'Oauth.php';
        }
    }
}
