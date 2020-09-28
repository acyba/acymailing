<?php
function addFormTagAcymsubHandler($tag)
{
    include_once __DIR__.DIRECTORY_SEPARATOR.'plugin.php';
    $contactFormClass = new plgAcymContactform7();

    return $contactFormClass->displayAcymsub($tag);
}

function addTagGeneratorAcymsubHandler($contact_form, $args = '')
{
    include_once __DIR__.DIRECTORY_SEPARATOR.'plugin.php';
    $contactFormClass = new plgAcymContactform7();

    return $contactFormClass->setAcymsubParameters($contact_form, $args);
}
