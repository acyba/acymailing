<?php

namespace AcyMailing\Init;

use AcyMailing\Helpers\MailerHelper;

class acyOverrideEmail extends acyHook
{
    public function __construct()
    {
        add_filter('wp_mail', [$this, 'overrideEmailFunction']);
    }

    public function blockEmailSending(&$phpmailer)
    {
        $phpmailer = new acyFakePhpMailer();
    }

    public function overrideEmailFunction($args)
    {
        $mailerHelper = new MailerHelper();
        $overridden = $mailerHelper->overrideEmail($args['subject'], $args['message'], $args['to']);

        if ($overridden) add_action('phpmailer_init', [$this, 'blockEmailSending']);

        return $args;
    }
}

new acyOverrideEmail();
