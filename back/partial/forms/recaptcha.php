<?php

$currentUserEmail = acym_currentUserEmail();
$userClass = acym_get('class.user');
$identifiedUser = $userClass->getOneByEmail($currentUserEmail);
$config = acym_config();

if (empty($identifiedUser) && $config->get('captcha', '') == 1) {
    echo '<div class="onefield fieldacycaptcha" id="field_captcha_'.$form->form_tag_name.'">';
    $captcha = acym_get('helper.captcha');
    echo $captcha->display($form->form_tag_name);
    echo '</div>';
}
