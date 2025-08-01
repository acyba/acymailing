<?php
/**
 * Plugin Name: AcyMailing
 * Description: Manage your contact lists and send newsletters from your site.
 * Author: AcyMailing Newsletter Team
 * Author URI: https://www.acymailing.com
 * License: GPLv3
 * Version: 10.4.0
 * Text Domain: acymailing
 * Domain Path: /language
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

use AcyMailing\WpInit\Activation;
use AcyMailing\WpInit\Addons;
use AcyMailing\WpInit\Beaver;
use AcyMailing\WpInit\Menu;
use AcyMailing\WpInit\Cron;
use AcyMailing\WpInit\Deactivate;
use AcyMailing\WpInit\Elementor;
use AcyMailing\WpInit\Forms;
use AcyMailing\WpInit\Gutenberg;
use AcyMailing\WpInit\Message;
use AcyMailing\WpInit\Oauth;
use AcyMailing\WpInit\OverrideEmail;
use AcyMailing\WpInit\Router;
use AcyMailing\WpInit\Security;
use AcyMailing\WpInit\Update;
use AcyMailing\WpInit\UserSync;
use AcyMailing\WpInit\WpRocket;

defined('ABSPATH') || die('Restricted Access');

class acymailingLoader
{
    public function __construct()
    {
        // Install Acy DB and sample data on first activation (not on installation because of FTP install)
        register_activation_hook(__DIR__.'/'.basename(__FILE__), [$this, 'activation']);
        add_action('wp_initialize_site', [$this, 'subsiteCreation'], 101);

        // Prevent bad plugins from loading on AcyMailing pages
        add_action('plugins_loaded', [$this, 'protectAcyMailingPages'], 5);

        // Init widgets. According to the WP doc widgets_init should be loaded after init, but it isn't
        add_action('widgets_init', [$this, 'initWidgets']);

        // Init AcyMailing
        add_action('init', [$this, 'initAcyMailing'], 0);

        add_filter('wpml_show_admin_language_switcher', [$this, 'disableWpml']);
    }

    public function subsiteCreation(): void
    {
        if (is_plugin_active_for_network(basename(__DIR__).'/'.basename(__FILE__))) {
            $this->activation();
        }
    }

    public function activation(): void
    {
        // Load Acy library
        $helperFile = __DIR__.DIRECTORY_SEPARATOR.'back'.DIRECTORY_SEPARATOR.'Core'.DIRECTORY_SEPARATOR.'init.php';
        if (file_exists($helperFile) && include_once $helperFile) {
            $activation = new Activation();
            $activation->install();
        }
    }

    public function protectAcyMailingPages()
    {
        if (!$this->isCurrentlyOnAcyPage()) {
            return;
        }

        // Prevent plugins from breaking AcyMailing pages (mainly JS scripts loaded without the WP way)
        remove_action('plugins_loaded', 'mailchimp_on_all_plugins_loaded', 12);
        remove_action('plugins_loaded', '_imagify_init');
        remove_action('plugins_loaded', 'plugins_loaded_wps_hide_login_plugin');
        remove_action('plugins_loaded', ['WPAS_Gas', 'get_instance'], 11);
        remove_action('plugins_loaded', 'woosb_init', 12);
    }

    public function disableWpml(): bool
    {
        if (!$this->isCurrentlyOnAcyPage() || !$this->loadAcyMailingLibrary()) {
            return true;
        }

        $config = acym_config();

        return intval($config->get('multilingual')) !== 1;
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

    private function loadAcyMailingLibrary()
    {
        $helperFile = __DIR__.DIRECTORY_SEPARATOR.'back'.DIRECTORY_SEPARATOR.'Core'.DIRECTORY_SEPARATOR.'init.php';

        return file_exists($helperFile) && include_once $helperFile;
    }

    public function initAcyMailing()
    {
        if (!$this->loadAcyMailingLibrary()) {
            return;
        }

        //__START__development_
        acym_displayErrors();
        acym_query('SET SESSION query_cache_type=0;');
        //__END__development_

        new Update();
        $router = new Router();
        new Menu($router);
        new UserSync();
        new Message();
        new Elementor();
        new Beaver();
        new WpRocket();
        new Addons();
        new Forms();
        new OverrideEmail();
        new Cron();
        new Gutenberg();
        new Security();
        new Deactivate();
        new Oauth();
    }

    private function isCurrentlyOnAcyPage(): bool
    {
        // Make sure we're on an AcyMailing page
        $page = isset($_REQUEST['page']) ? sanitize_text_field(wp_unslash($_REQUEST['page'])) : '';

        return !empty($page) && strpos($page, 'acymailing_') !== false;
    }
}

new acymailingLoader();
