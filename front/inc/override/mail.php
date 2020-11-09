<?php

use AcyMailing\Helpers\MailerHelper;

defined('_JEXEC') or die('Restricted access');

// The Joomla mail class already exists, can't override
if (class_exists('JMail', false)) return;

jimport('phpmailer.phpmailer');
if (!class_exists('PHPMailer') && class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    class PHPMailer extends PHPMailer\PHPMailer\PHPMailer
    {
    }
}

$jversion = preg_replace('#[^0-9\.]#i', '', JVERSION);
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'mail'.DIRECTORY_SEPARATOR.'j3.php';
if (!class_exists('jMail_acym')) return;

// Redefine the Joomla mail class
class JMail extends jMail_acym
{
    public function Send()
    {
        $success = false;

        // Send the email with AcyMailing if possible
        $ds = DIRECTORY_SEPARATOR;
        if (include_once rtrim(JPATH_ADMINISTRATOR, $ds).$ds.'components'.$ds.'com_acym'.$ds.'helpers'.$ds.'helper.php') {
            $mailerHelper = new MailerHelper();
            $success = $mailerHelper->overrideEmail($this->Subject, $this->Body, $this->to[0][0]);
        }

        // We sent the email using AcyMailing
        if ($success) {
            return true;
        }

        // Let Joomla send the email
        return parent::Send();
    }
}

// Handle the new Joomla mail structure
if (version_compare($jversion, '3.8.0', '>=')) {
    include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'mail'.DIRECTORY_SEPARATOR.'j38.php';
}
