<?php
function acym_addFormTagAcymsubHandler($tag)
{
    include_once __DIR__.DIRECTORY_SEPARATOR.'plugin.php';
    $contactFormClass = new plgAcymContactform7();

    return $contactFormClass->displayAcymsub($tag);
}

function acym_addTagGeneratorAcymsubHandler($contact_form, $args = '')
{
    include_once __DIR__.DIRECTORY_SEPARATOR.'plugin.php';
    $contactFormClass = new plgAcymContactform7();

    return $contactFormClass->setAcymsubParameters($contact_form, $args);
}
