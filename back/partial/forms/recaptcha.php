<?php

use AcyMailing\Classes\UserClass;
use AcyMailing\Helpers\CaptchaHelper;

$currentUserEmail = acym_currentUserEmail();
$userClass = new UserClass();
$identifiedUser = $userClass->getOneByEmail($currentUserEmail);
$config = acym_config();

if (empty($identifiedUser) && $config->get('captcha', 'none') !== 'none') {
    echo '<div class="onefield fieldacycaptcha" id="field_captcha_'.$form->form_tag_name.'">';
    $captcha = new CaptchaHelper();
    echo $captcha->display($form->form_tag_name);
    echo '</div>';
}
