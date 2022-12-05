<?php

namespace AcyMailing\Init;

use AcyMailing\Helpers\MailerHelper;

class acyOverrideEmail
{
    public function __construct()
    {
        add_filter('wp_mail', [$this, 'overrideEmailFunction']);
    }

    public function overrideEmailFunction($args)
    {
        $mailerHelper = new MailerHelper();
        $overridden = $mailerHelper->overrideEmail($args['subject'], $args['message'], $args['to']);

        if ($overridden) {
            add_action('phpmailer_init', [$this, 'blockEmailSending']);
            add_filter('post_smtp_do_send_email', [$this, 'blockEmailSendingPostSMTP']);
        }

        return $args;
    }

    public function blockEmailSending(&$phpmailer)
    {
        $phpmailer = new acyFakePhpMailer();
    }

    public function blockEmailSendingPostSMTP($shouldSend)
    {
        return false;
    }
}

new acyOverrideEmail();
