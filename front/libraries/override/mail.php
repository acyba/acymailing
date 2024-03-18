<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;

defined('_JEXEC') or die('Restricted access');

// The Joomla mail class already exists, can't override
if (class_exists('JMail', false) || class_exists('Joomla\CMS\Mail\Mail', false)) return;

if (class_exists('ComponentHelper') && method_exists('ComponentHelper', 'isEnabled') && ComponentHelper::isEnabled('com_eesender', true)) {
    $app = Factory::getApplication();
    if ($app->isAdmin()) {
        $app->enqueueMessage('The AcyMailing email override plugin isn\'t compatible with the Elastic Email Sender extension, please disable one of these two extensions', 'error');
    }
}

jimport('phpmailer.phpmailer');
if (!class_exists('PHPMailer') && class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    class PHPMailer extends PHPMailer\PHPMailer\PHPMailer
    {
    }
}

$jversion = preg_replace('#[^0-9\.]#i', '', JVERSION);
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'mail'.DIRECTORY_SEPARATOR.'override.php';
if (!class_exists('jMail_acym')) return;


// Handle the new Joomla mail structure
if (version_compare($jversion, '4.4.0', '>=')) {
    include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'mail'.DIRECTORY_SEPARATOR.'mail44.php';
} elseif (version_compare($jversion, '3.8.0', '>=')) {
    include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'mail'.DIRECTORY_SEPARATOR.'mail.php';
} else {
    include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'mail'.DIRECTORY_SEPARATOR.'jmail.php';
}
