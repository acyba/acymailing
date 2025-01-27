<?php

namespace AcyMailing\WpInit;

use AcyMailing\Helpers\MailerHelper;

class FakePhpMailer
{
    public function send()
    {
        return true;
    }

    public function IsSMTP()
    {

    }

    public function addReplyTo($replyto, $name = '')
    {
        return true;
    }

    public function setFrom($address, $name = '', $auto = true)
    {
        return true;
    }
}
