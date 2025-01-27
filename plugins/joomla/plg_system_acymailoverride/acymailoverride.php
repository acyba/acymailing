<?php

use Joomla\CMS\Plugin\CMSPlugin;

defined('_JEXEC') or die('Restricted access');

class plgSystemAcymailoverride extends CMSPlugin
{
    public function __construct(&$subject, $config = [])
    {
        $ds = DIRECTORY_SEPARATOR;
        $file = rtrim(JPATH_ADMINISTRATOR, $ds).$ds.'components'.$ds.'com_acym'.$ds.'Libraries'.$ds.'Override'.$ds.'Override.php';

        try {
            if (file_exists($file)) require_once $file;
        } catch (Exception $e) {
            echo 'Could not load the AcyMailing JMail class at '.$file.'<br />Please disable the plugin named AcyMailing - Override Joomla emails';
        }

        parent::__construct($subject, $config);
    }
}
