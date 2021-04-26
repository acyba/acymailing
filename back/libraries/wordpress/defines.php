<?php

define('ACYM_CMS', 'wordpress');
define('ACYM_CMS_TITLE', 'WordPress');
define('ACYM_COMPONENT', 'acymailing');
define('ACYM_DEFAULT_LANGUAGE', 'en-US');

define('ACYM_BASE', '');
// On wordpress.com, websites have access restrictions on the base folder. According to them we need $_SERVER['DOCUMENT_ROOT'] instead of the standard ABSPATH
$acyAbsPath = ABSPATH;
if (!empty($_SERVER['DOCUMENT_ROOT'])) {
    $docRoot = $_SERVER['DOCUMENT_ROOT'];
    $pos = strpos(ABSPATH, $docRoot);
    if ($pos !== false) {
        $docRoot .= substr(ABSPATH, $pos + strlen($docRoot));
    }
    $docRoot = rtrim($docRoot, DS.'/').DS;
    if (str_replace($docRoot, '', WP_PLUGIN_DIR.DS) === 'wp-content/plugins/') {
        $acyAbsPath = $docRoot;
    }
}
define('ACYM_ROOT', rtrim($acyAbsPath, DS.'/').DS);
define('ACYM_FOLDER', WP_PLUGIN_DIR.DS.ACYM_COMPONENT.DS);
define('ACYM_WIDGETS', ACYM_FOLDER.'widgets'.DS);
define('ACYM_FRONT', ACYM_FOLDER.'front'.DS);
define('ACYM_BACK', ACYM_FOLDER.'back'.DS);
define('ACYM_VIEW', ACYM_BACK.'views'.DS);
define('ACYM_PARTIAL', ACYM_BACK.'partial'.DS);
define('ACYM_NEW_FEATURES_SPLASHSCREEN', ACYM_BACK.'partial'.DS.'update'.DS.'new_features.php');
define('ACYM_VIEW_FRONT', ACYM_FRONT.'views'.DS);
define('ACYM_HELPER', ACYM_BACK.'helpers'.DS);
define('ACYM_CLASS', ACYM_BACK.'classes'.DS);
define('ACYM_LIBRARY', ACYM_BACK.'libraries'.DS);
define('ACYM_TYPE', ACYM_BACK.'types'.DS);
define('ACYM_CONTROLLER', ACYM_BACK.'controllers'.DS);
define('ACYM_CONTROLLER_FRONT', ACYM_FRONT.'controllers'.DS);
define('ACYM_MEDIA', ACYM_FOLDER.'media'.DS);

define('ACYM_WP_UPLOADS', basename(WP_CONTENT_DIR).DS.'uploads'.DS.ACYM_COMPONENT.DS);
define('ACYM_UPLOADS_PATH', ACYM_ROOT.ACYM_WP_UPLOADS);
define('ACYM_UPLOADS_URL', WP_CONTENT_URL.'/uploads/'.ACYM_COMPONENT.'/');
define('ACYM_OVERRIDES', ACYM_UPLOADS_PATH.'overrides'.DS);

define('ACYM_LANGUAGE', ACYM_UPLOADS_PATH.'language'.DS);
define('ACYM_INC', ACYM_FRONT.'inc'.DS);
define('ACYM_UPLOAD_FOLDER', ACYM_WP_UPLOADS.'upload'.DS);
define('ACYM_TEMPLATE', ACYM_UPLOADS_PATH.'templates'.DS);
define('ACYM_TEMPLATE_URL', ACYM_UPLOADS_URL.'templates/');
define('ACYM_TMP_FOLDER', ACYM_UPLOADS_PATH.'tmp'.DS);
define('ACYM_TMP_URL', ACYM_UPLOADS_URL.'tmp/');

define('ACYM_PLUGINS_URL', plugins_url());
define('ACYM_MEDIA_RELATIVE', str_replace(ACYM_ROOT, '', ACYM_MEDIA));
define('ACYM_MEDIA_URL', ACYM_PLUGINS_URL.'/'.ACYM_COMPONENT.'/media/');
define('ACYM_IMAGES', ACYM_MEDIA_URL.'images/');
define('ACYM_CSS', ACYM_MEDIA_URL.'css/');
define('ACYM_JS', ACYM_MEDIA_URL.'js/');
define('ACYM_TEMPLATE_THUMBNAILS', ACYM_UPLOADS_URL.'thumbnails/');
define('ACYM_CORE_DYNAMICS_URL', ACYM_PLUGINS_URL.'/'.ACYM_COMPONENT.'/back/dynamics/');
define('ACYM_DYNAMICS_URL', ACYM_UPLOADS_URL.'addons/');
define('ACYM_ADDONS_FOLDER_PATH', ACYM_UPLOADS_PATH.'addons'.DS);

define('ACYM_MEDIA_FOLDER', str_replace([ABSPATH, ACYM_ROOT], '', WP_PLUGIN_DIR).'/'.ACYM_COMPONENT.'/media');
define('ACYM_UPLOAD_FOLDER_THUMBNAIL', WP_CONTENT_DIR.DS.'uploads'.DS.ACYM_COMPONENT.DS.'thumbnails'.DS);
define('ACYM_CUSTOM_PLUGIN_LAYOUT', ACYM_UPLOADS_PATH.'plugins'.DS);
define('ACYM_LOGS_FOLDER', ACYM_WP_UPLOADS.'logs'.DS);

define('ACYM_CMSV', get_bloginfo('version'));

define('ACYM_ALLOWRAW', 2);
define('ACYM_ALLOWHTML', 4);
define('ACYM_ADMIN_GROUP', 'administrator');
define(
    'ACYM_AVAILABLE_PLUGINS',
    json_encode(
        [
            (object)[
                'name' => 'Contact Form 7',
                'description' => '- Add AcyMailing lists on contact forms',
                'image' => 'contactform7.png',
                'level' => 'starter',
                'documentation' => ACYM_DOCUMENTATION.'addons/wordpress-add-ons/contact-form-7',
                'category' => 'Subscription system',
                'downloadlink' => 'https://wordpress.org/plugins/acymailing-integration-for-contact-form-7/',
            ],
            (object)[
                'name' => 'Export in automations',
                'description' => '- Export the filtered users in the automations',
                'image' => 'automationexport.png',
                'level' => 'starter',
                'documentation' => ACYM_DOCUMENTATION.'addons/all-cms-add-ons/automation-export-action',
                'category' => 'User management',
                'downloadlink' => 'https://wordpress.org/plugins/acymailing-automation-export/',
            ],
            (object)[
                'name' => 'Gravity Forms',
                'description' => '- Add AcyMailing lists to your forms',
                'image' => 'gravityforms.png',
                'level' => 'starter',
                'documentation' => ACYM_DOCUMENTATION.'addons/wordpress-add-ons/gravity-forms',
                'category' => 'Subscription system',
                'downloadlink' => 'https://wordpress.org/plugins/acymailing-integration-for-gravity-forms/',
            ],
            (object)[
                'name' => 'MemberPress',
                'description' => '- Insert MemberPress custom fields in your emails<br />- Filter users based on their subscription<br />-Trigger automation when a user subscribe to a membership',
                'image' => 'memberpress.png',
                'level' => 'starter',
                'documentation' => ACYM_DOCUMENTATION.'addons/wordpress-add-ons/memberspress',
                'category' => 'User management',
                'downloadlink' => 'https://wordpress.org/plugins/acymailing-integration-for-memberpress/',
            ],
            (object)[
                'name' => 'Modern Events Calendar',
                'description' => '- Insert events in your emails<br />- Filter users attending your events',
                'image' => 'moderneventscalendar.png',
                'level' => 'starter',
                'documentation' => ACYM_DOCUMENTATION.'addons/wordpress-add-ons/modern-events-calendar',
                'category' => 'Events management',
                'downloadlink' => 'https://wordpress.org/plugins/acymailing-integration-for-modern-events-calendar/',
            ],
            (object)[
                'name' => 'RSS content',
                'description' => '- Insert content in your emails from an RSS link',
                'image' => 'rss.png',
                'level' => 'starter',
                'documentation' => ACYM_DOCUMENTATION.'addons/all-cms-add-ons/rss-feed',
                'category' => 'Content management',
                'downloadlink' => 'https://wordpress.org/plugins/acymailing-rss-content/',
            ],
            (object)[
                'name' => 'Table of contents',
                'description' => '- Insert a dynamic table of contents in your emails based on their contents',
                'image' => 'tableofcontents.png',
                'level' => 'starter',
                'documentation' => 'https://docs.acymailing.com/addons/all-cms-add-ons/table-of-contents-generator',
                'category' => 'Content management',
                'downloadlink' => 'https://wordpress.org/plugins/acymailing-table-of-contents-generator/',
            ],
            (object)[
                'name' => 'The Events Calendar',
                'description' => '- Insert events in your emails<br />- Filter users by event subscription',
                'image' => 'theeventscalendar.png',
                'level' => 'starter',
                'documentation' => ACYM_DOCUMENTATION.'addons/wordpress-add-ons/the-events-calendar',
                'category' => 'Events management',
                'downloadlink' => 'https://wordpress.org/plugins/acymailing-integration-for-the-events-calendar/',
            ],
            (object)[
                'name' => 'Ultimate Member',
                'description' => '- insert AcyMailing list on your Ultimate Member register form',
                'image' => 'ultimatemember.png',
                'level' => 'starter',
                'documentation' => ACYM_DOCUMENTATION.'addons/wordpress-add-ons/ultimate-member',
                'category' => 'Subscription system',
                'downloadlink' => 'https://wordpress.org/plugins/acymailing-integration-for-ultimate-member/',
            ],
            (object)[
                'name' => 'Universal filter',
                'description' => '- Filter AcyMailing subscribers based on any data from your database<br />- Filter users based on email addresses in a specified text',
                'image' => 'universalfilter.png',
                'level' => 'starter',
                'documentation' => ACYM_DOCUMENTATION.'addons/all-cms-add-ons/universal-filter',
                'category' => 'User management',
                'downloadlink' => 'https://wordpress.org/plugins/acymailing-universal-filter/',
            ],
            (object)[
                'name' => 'WooCommerce',
                'description' => '- Insert products and generate coupons in your emails<br />- Filter users based on their purchases',
                'image' => 'woocommerce.png',
                'level' => 'starter',
                'documentation' => ACYM_DOCUMENTATION.'addons/wordpress-add-ons/woocommerce',
                'category' => 'E-commerce solutions',
                'downloadlink' => 'https://wordpress.org/plugins/acymailing-integration-for-woocommerce/',
            ],
        ]
    )
);
