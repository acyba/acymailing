<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification

use AcyMailing\Classes\UserClass;
use AcyMailing\Helpers\CaptchaHelper;

$currentUserEmail = acym_currentUserEmail();
$userClass = new UserClass();
$identifiedUser = $userClass->getOneByEmail($currentUserEmail);

if (empty($identifiedUser) && $config->get('captcha', 'none') !== 'none' && acym_level(ACYM_ESSENTIAL)) {
    echo '<div class="onefield fieldacycaptcha" id="field_captcha_'.acym_escape($form->form_tag_name).'">';
    $captcha = new CaptchaHelper();
    $captcha->display($form->form_tag_name);
    echo '</div>';
}
