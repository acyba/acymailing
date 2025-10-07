<?php

namespace AcyMailing\WpInit;

class Menu
{
    private Router $router;

    public function __construct($router)
    {
        $this->router = $router;

        if (defined('WP_ADMIN') && WP_ADMIN) {
            // Add AcyMailing menu in the back-end's left menu of WordPress
            add_action('admin_menu', [$this, 'addMenus'], 99);
        }

        // Add "settings" link for acym on plugins listing
        add_filter('plugin_action_links_'.plugin_basename(dirname(__DIR__).'/index.php'), [$this, 'addPluginLinks']);
    }

    // Add AcyMailing menu to WP left menu and define controllers
    public function addMenus()
    {
        // Everyone in WordPress can read, the real test is made bellow
        $capability = 'read';

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
        $svg = acym_loaderLogo(false);
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

        $menuExtra = [];
        $menus = [
            'ACYM_SUBSCRIPTION_FORMS' => 'forms',
            'ACYM_SUBSCRIBERS' => 'users',
            'ACYM_CUSTOM_FIELDS' => 'fields',
            'ACYM_LISTS' => 'lists',
        ];
        $menus['ACYM_EMAILS'] = 'campaigns';
        $menus['ACYM_TEMPLATES'] = 'mails';
        $menus['ACYM_EMAILS_OVERRIDE'] = 'override';
        $menus['ACYM_QUEUE'] = 'queue';
        $menus['ACYM_STATISTICS'] = 'stats';
        $menus['ACYM_ADD_ONS'] = 'plugins';
        $menus['ACYM_CONFIGURATION'] = 'configuration';

        if (!acym_level(ACYM_ESSENTIAL)) {
            $menus['ACYM_GOPRO'] = 'gopro';
            $menuExtra['ACYM_GOPRO']['icon'] = '<i class="acymicon-star"></i>';
        }

        foreach ($menus as $title => $ctrl) {
            if (!acym_isAllowed($ctrl)) continue;

            $text = acym_translation($title);
            if (!empty($menuExtra[$title]['icon'])) $text .= ' '.$menuExtra[$title]['icon'];
            add_submenu_page(
                ACYM_COMPONENT.'_dashboard',
                acym_translation($title),
                $text,
                $capability,
                ACYM_COMPONENT.'_'.$ctrl,
                [$this->router, 'router']
            );
        }

        // Declare invisible menus
        $controllers = ['dynamics', 'file', 'language'];
        foreach ($controllers as $oneCtrl) {
            add_submenu_page(
                ACYM_COMPONENT.'_'.$oneCtrl,
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

    // Add links on the plugins listing
    public function addPluginLinks($links): array
    {
        $settings_link = '<a href="admin.php?page='.ACYM_COMPONENT.'_configuration">'.acym_translation('ACYM_SETTINGS').'</a>';

        return array_merge([$settings_link], $links);
    }
}
