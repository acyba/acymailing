<?php

defined('_JEXEC') or die('Restricted access');

class plgSystemAcymailoverride extends JPlugin
{
    public function __construct(&$subject, $config = [])
    {
        $ds = DIRECTORY_SEPARATOR;
        $file = rtrim(JPATH_SITE, $ds).$ds.'components'.$ds.'com_acym'.$ds.'inc'.$ds.'override'.$ds.'mail.php';

        try {
            if (file_exists($file)) require_once $file;
        } catch (Exception $e) {
            echo 'Could not load the AcyMailing JMail class at '.$file.'<br />Please disable the plugin named AcyMailing - Override Joomla emails';
        }

        parent::__construct($subject, $config);
    }
}
