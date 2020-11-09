<?php

namespace AcyMailing\Init;

use AcyMailing\Classes\PluginClass;

class acyRouter extends acyHook
{
    var $activation;

    public function __construct($activation)
    {
        $this->activation = $activation;

        add_action('wp_ajax_acymailing_router', [$this, 'router']);
        add_action('wp_ajax_acymailing_frontrouter', [$this, 'frontRouter']);
        add_action('wp_ajax_nopriv_acymailing_frontrouter', [$this, 'frontRouter']);

        // Make sure we can redirect / download / modify headers if needed after some checks
        $pages = ['automation', 'bounces', 'campaigns', 'configuration', 'dashboard', 'dynamics', 'fields', 'file', 'followups', 'forms', 'language', 'lists', 'mails', 'override', 'queue', 'segments', 'stats', 'users'];
        $headerPages = ['automation', 'bounces', 'campaigns', 'dashboard', 'fields', 'followups', 'forms', 'lists', 'mails', 'override', 'segments', 'stats', 'users'];
        foreach ($pages as $page) {
            if (in_array($page, $headerPages)) {
                // Ensure we can set headers in the plugin
                add_action('load-acymailing_page_acymailing_'.$page, [$this, 'waitHeaders']);
            }
            // Disable WP emojis in AcyMailing only
            add_action('admin_print_scripts-acymailing_page_acymailing_'.$page, [$this, 'disableJsBreakingPages']);
            add_action('admin_print_styles-acymailing_page_acymailing_'.$page, [$this, 'removeCssBreakingPages']);
        }
        add_action('admin_print_scripts-toplevel_page_acymailing_dashboard', [$this, 'disableJsBreakingPages']);
        add_action('admin_print_styles-toplevel_page_acymailing_dashboard', [$this, 'removeCssBreakingPages']);
    }

    public function waitHeaders()
    {
        ob_start();
    }

    public function disableJsBreakingPages()
    {
        // Show normal emojis on AcyMailing pages
        remove_action('admin_print_scripts', 'print_emoji_detection_script');

        // Slideshow ck breaks the editor
        remove_action('wp_enqueue_media', '\\Slideshowck\\Tinymce\\register_scripts_styles');

        // Skaut Google Drive gallery breaks the editor
        remove_action('wp_enqueue_media', '\\Sgdg\\Admin\\TinyMCE\\register_scripts_styles');

        // Remove theme loading select2 which breaks select2 in vueJS
        wp_dequeue_script('select2.js');
        wp_dequeue_script('select2_js');

        // The "Checkout Field Manager for WooCommerce" plugin breaks the js on every pages
        wp_dequeue_script('checkout_fields_js');
    }

    public function removeCssBreakingPages()
    {
        wp_dequeue_style('saswp-main-css');
        wp_dequeue_style('WP REST API Controller');
        wp_dequeue_style('wpml-select-2');
    }

    public function frontRouter()
    {
        $this->router(true);
    }

    public function router($front = false)
    {
        if (!$front) auth_redirect();

        if (is_multisite()) {
            $currentBlog = get_current_blog_id();
            $sites = function_exists('get_sites') ? get_sites() : wp_get_sites();

            foreach ($sites as $site) {
                if (is_object($site)) {
                    $site = get_object_vars($site);
                }
                switch_to_blog($site['blog_id']);
                acym_config(true);
                $this->activation->updateAcym();
            }

            switch_to_blog($currentBlog);
        } else {
            $this->activation->updateAcym();
        }

        if (file_exists(ACYM_FOLDER.'update.php')) {
            unlink(ACYM_FOLDER.'update.php');
        }

        $config = acym_config(true);

        // Get controller. If not found, take it from the page
        $ctrl = acym_getVar('cmd', 'ctrl', '');
        $task = acym_getVar('cmd', 'task', '');

        if (file_exists(ACYM_NEW_FEATURES_SPLASHSCREEN) && $task != 'deleteFeatures') {
            $ctrl = 'dashboard';
            $task = 'features';
            acym_setVar('ctrl', $ctrl);
            acym_setVar('task', $task);
        }

        $needToMigrate = $config->get('migration') == 0 && acym_existsAcyMailing59() && acym_getVar('string', 'task') != 'migrationDone';

        if ((($needToMigrate || $config->get('walk_through') == 1) && !(defined('DOING_AJAX') && DOING_AJAX)) && 'dynamics' != $ctrl) {
            $ctrl = 'dashboard';
            acym_setVar('ctrl', $ctrl);
        }

        if (empty($ctrl)) {
            $ctrl = str_replace(ACYM_COMPONENT.'_', '', acym_getVar('cmd', 'page', ''));

            if (empty($ctrl)) {
                echo acym_translation('ACYM_PAGE_NOT_FOUND');

                return;
            }

            acym_setVar('ctrl', $ctrl);
        }

        $this->deactivateHookAdminFooter();
        $subNamespace = $front ? 'Front' : '';
        $controllerNamespace = 'AcyMailing\\'.$subNamespace.'Controllers\\'.ucfirst($ctrl).'Controller';

        if (!class_exists($controllerNamespace)) {
            echo acym_translation('ACYM_PAGE_NOT_FOUND').': '.$ctrl;

            return;
        }

        if ($task != 'edit' && !(defined('DOING_AJAX') && DOING_AJAX)) {
            $pluginClass = new PluginClass();
            $installedPlugins = $pluginClass->getAll('title');
            $newPlugins = json_decode(ACYM_AVAILABLE_PLUGINS);
            foreach ($newPlugins as $onePlugin) {
                if (empty($installedPlugins[$onePlugin->name])) continue;
                if ($installedPlugins[$onePlugin->name]->type !== 'ADDON') continue;

                acym_enqueueMessage(
                    acym_translation_sprintf(
                        'ACYM_NEW_PLUGIN_FORMAT',
                        $onePlugin->name,
                        '<a target="_blank" href="'.$onePlugin->downloadlink.'">wordpress.org</a>'
                    ),
                    'info'
                );
            }
        }

        $controller = new $controllerNamespace();
        if (empty($task)) {
            $task = acym_getVar('cmd', 'defaulttask', $controller->defaulttask);
        }

        $controller->call($task);
    }

    private function deactivateHookAdminFooter()
    {
        //Remove hook function which break AcyMailing pages
        remove_action('admin_footer', 'Freemius::_enrich_ajax_url');
        remove_action('admin_footer', 'Freemius::_open_support_forum_in_new_page');
    }
}

$acyRouter = new acyRouter($acyActivation);
