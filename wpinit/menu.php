<?php

namespace AcyMailing\Init;

use AcyMailing\Classes\PluginClass;

class acyMenu extends acyHook
{
    var $router;

    public function __construct($router)
    {
        $this->router = $router;

        if (defined('WP_ADMIN') && WP_ADMIN) {
            // Add AcyMailing menu in the back-end's left menu of WordPress
            add_action('admin_menu', [$this, 'addMenus'], 99);
        }
    }

    // Add AcyMailing menu to WP left menu and define controllers
    public function addMenus()
    {
        // Everyone in WordPress can read, the real test is made bellow
        $capability = 'read';

        // For the front ajax
        add_submenu_page(
            null,
            'front',
            'front',
            $capability,
            ACYM_COMPONENT.'_front',
            [$this->router, 'frontRouter']
        );

        // Make sure the user is allowed to see admin features
        $config = acym_config();
        $allowedGroups = explode(',', $config->get('wp_access', 'administrator'));
        $userGroups = acym_getGroupsByUser();

        $allowed = false;
        foreach ($userGroups as $oneGroup) {
            if ($oneGroup == 'administrator' || in_array($oneGroup, $allowedGroups)) {
                $allowed = true;
                break;
            }
        }
        if (!$allowed) return;

        // Add the Acy menu items to the WP menu
        $svg = acym_fileGetContent(ACYM_IMAGES.'loader.svg');
        add_menu_page(
            acym_translation('ACYM_DASHBOARD'),
            'AcyMailing',
            $capability,
            ACYM_COMPONENT.'_dashboard',
            [$this->router, 'router'],
            'data:image/svg+xml;base64,'.base64_encode(
                $svg
            ),
            42
        );

        $menus = [
            'ACYM_USERS' => 'users',
            'ACYM_CUSTOM_FIELDS' => 'fields',
            'ACYM_LISTS' => 'lists',
            'ACYM_SEGMENTS' => 'segments',
            'ACYM_EMAILS' => 'campaigns',
            'ACYM_TEMPLATES' => 'mails',
            'ACYM_AUTOMATION' => 'automation',
            'ACYM_QUEUE' => 'queue',
            'ACYM_STATISTICS' => 'stats',
            'ACYM_BOUNCE_HANDLING' => 'bounces',
            'ACYM_ADD_ONS' => 'plugins',
            'ACYM_SUBSCRIPTION_FORMS' => 'forms',
            'ACYM_CONFIGURATION' => 'configuration',
        ];
        foreach ($menus as $title => $ctrl) {
            if (!acym_isAllowed($ctrl)) continue;

            add_submenu_page(
                ACYM_COMPONENT.'_dashboard',
                acym_translation($title),
                acym_translation($title),
                $capability,
                ACYM_COMPONENT.'_'.$ctrl,
                [$this->router, 'router']
            );
        }

        // Declare invisible menus
        $controllers = ['dynamics', 'file', 'language'];
        foreach ($controllers as $oneCtrl) {
            add_submenu_page(
                null,
                $oneCtrl,
                $oneCtrl,
                $capability,
                ACYM_COMPONENT.'_'.$oneCtrl,
                [$this->router, 'router']
            );
        }

        // In WordPress, the first submenu is called "AcyMailing" instead of "Dashboard" so we rename it manually
        global $submenu;
        if (isset($submenu[ACYM_COMPONENT.'_dashboard'])) {
            $submenu[ACYM_COMPONENT.'_dashboard'][0][0] = acym_translation('ACYM_DASHBOARD');
        }
    }
}

$acyMenu = new acyMenu($acyRouter);
