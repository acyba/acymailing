<?php

namespace AcyMailing\Init;

use AcyMailing\Helpers\MailerHelper;

class acyFakePhpMailer
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
