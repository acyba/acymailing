<?php
function addFormTagAcymsubHandler($tag)
{
    include_once ACYM_ADDONS_FOLDER_PATH.'contactform7'.DS.'plugin.php';
    $contactFormClass = new plgAcymContactform7();

    return $contactFormClass->displayAcymsub($tag);
}

function addTagGeneratorAcymsubHandler($contact_form, $args = '')
{
    include_once ACYM_ADDONS_FOLDER_PATH.'contactform7'.DS.'plugin.php';
    $contactFormClass = new plgAcymContactform7();

    return $contactFormClass->setAcymsubParameters($contact_form, $args);
}