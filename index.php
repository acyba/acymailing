<?php
/*
Plugin Name: AcyMailing
Description: Manage your contact lists and send newsletters from your site.
Author: AcyMailing Newsletter Team
Author URI: https://www.acymailing.com
License: GPLv3
Version: 7.1.1
Text Domain: acymailing
Domain Path: /language
*/
defined('ABSPATH') || die('Restricted Access');

// Load Acy library
$helperFile = __DIR__.DIRECTORY_SEPARATOR.'back'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'helper.php';
if (file_exists($helperFile) && include_once $helperFile) {
    //__START__development_
    acym_displayErrors();
    //__END__development_
    include_once __DIR__.DS.'wpinit'.DS.'init.php';
    include_once __DIR__.DS.'wpinit'.DS.'activation.php';
    include_once __DIR__.DS.'wpinit'.DS.'update.php';
    include_once __DIR__.DS.'wpinit'.DS.'widget.php';
    include_once __DIR__.DS.'wpinit'.DS.'router.php';
    include_once __DIR__.DS.'wpinit'.DS.'menu.php';
    include_once __DIR__.DS.'wpinit'.DS.'usersynch.php';
    include_once __DIR__.DS.'wpinit'.DS.'message.php';
    include_once __DIR__.DS.'wpinit'.DS.'elementor.php';
    include_once __DIR__.DS.'wpinit'.DS.'wprocket.php';
    include_once __DIR__.DS.'wpinit'.DS.'addons.php';
    include_once __DIR__.DS.'wpinit'.DS.'forms.php';
    include_once __DIR__.DS.'wpinit'.DS.'override_email.php';
    include_once __DIR__.DS.'wpinit'.DS.'cron.php';
}
